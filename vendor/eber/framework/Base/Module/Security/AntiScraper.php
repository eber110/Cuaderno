<?php

namespace Base\Module\Security;

use Core\ErrorHandler;

/**
 * Filtro Anti-Scraping y Protección de Stream Wrappers (SRP).
 * 
 * Responsabilidad Única: Identificar bots de extracción de contenido (scrapers, cURL, Python),
 * verificar encabezados de navegadores web legítimos, sanitizar y bloquear el abuso de stream wrappers PHP
 * y aplicar control de frecuencia (rate limiting) por IP.
 */
class AntiScraper
{
  /**
   * Agentes de usuario (User-Agents) conocidos de scraping y scripts.
   */
  private const KNOWN_SCRAPER_USER_AGENTS = [
    'curl',
    'wget',
    'python-requests',
    'python-urllib',
    'scrapy',
    'guzzlehttp',
    'postmanruntime',
    'go-http-client',
    'java/',
    'apache-httpclient',
    'node-fetch',
    'axios',
    'selenium',
    'puppeteer',
    'phantomjs',
    'headlesschrome',
    'httpclient',
    'http-client',
    'winhttp',
    'libwww-perl',
    'mechanize',
    'httplib',
    'faraday',
    'rest-client'
  ];

  /**
   * Stream wrappers de PHP no permitidos.
   */
  private const DANGEROUS_WRAPPERS = [
    'php://',
    'data://',
    'expect://',
    'phar://',
    'zip://',
    'glob://',
    'rar://',
    'bzip2://',
    'zlib://',
    'ogg://',
    'ftp://'
  ];

  /**
   * Obtiene la ruta de almacenamiento de la tasa de peticiones.
   */
  public static function getRateStoragePath(): string
  {
    $dir = defined('ROUTE_SAFETY') ? rtrim(ROUTE_SAFETY, '/') : (defined('ROOT_PATH') ? ROOT_PATH . '/App/Safety' : str_replace('\\', '/', getcwd()) . '/App/Safety');
    if (!is_dir($dir)) {
      @mkdir($dir, 0755, true);
    }
    return $dir . '/scraping_rate.json';
  }

  /**
   * Detecta si un User-Agent es un script o scraper conocido.
   *
   * @param string|null $userAgent
   * @return bool
   */
  public static function isScraperUserAgent(?string $userAgent = null): bool
  {
    $ua = strtolower($userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? '');

    if (empty($ua)) {
      return true;
    }

    foreach (self::KNOWN_SCRAPER_USER_AGENTS as $botSign) {
      if (str_contains($ua, $botSign)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Evalúa si la solicitud carece de los encabezados estándar enviados por navegadores web.
   *
   * @return bool
   */
  public static function isMissingBrowserHeaders(): bool
  {
    $hasUserAgent = !empty($_SERVER['HTTP_USER_AGENT']);
    $hasAccept = !empty($_SERVER['HTTP_ACCEPT']);

    if (!$hasUserAgent || !$hasAccept) {
      return true;
    }

    $hasLang = !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $hasEncoding = !empty($_SERVER['HTTP_ACCEPT_ENCODING']);

    if (!$hasLang && !$hasEncoding && self::isScraperUserAgent()) {
      return true;
    }

    return false;
  }

  /**
   * Verifica si una cadena contiene stream wrappers PHP maliciosos.
   *
   * @param string $input
   * @return bool
   */
  public static function isMaliciousWrapper(string $input): bool
  {
    $inputLower = strtolower(trim($input));

    foreach (self::DANGEROUS_WRAPPERS as $wrapper) {
      if (str_contains($inputLower, $wrapper)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Sanitiza o remueve referencias a stream wrappers PHP en una cadena.
   *
   * @param string $input
   * @return string
   */
  public static function sanitizeWrappers(string $input): string
  {
    $sanitized = $input;
    foreach (self::DANGEROUS_WRAPPERS as $wrapper) {
      $sanitized = str_ireplace($wrapper, '', $sanitized);
    }
    return preg_replace('#[a-z0-9\+\.\-]+://#i', '', $sanitized);
  }

  /**
   * Rate limiting anti-scraping por IP.
   *
   * @param string $ip
   * @param int $maxRequestsPerMinute
   * @return bool True si sobrepasa el límite.
   */
  public static function checkScrapingRateLimit(string $ip, int $maxRequestsPerMinute = 60): bool
  {
    $file = self::getRateStoragePath();
    $data = [];

    if (file_exists($file)) {
      $content = file_get_contents($file);
      $data = json_decode($content, true) ?? [];
    }

    $currentTime = time();
    $window = 60;

    if (!isset($data[$ip])) {
      $data[$ip] = [];
    }

    $data[$ip] = array_values(array_filter($data[$ip], function ($timestamp) use ($currentTime, $window) {
      return ($currentTime - $timestamp) <= $window;
    }));

    $data[$ip][] = $currentTime;
    $exceeded = count($data[$ip]) > $maxRequestsPerMinute;

    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);

    if ($exceeded && class_exists('\\Base\\Module\\Security\\AccessBlocker')) {
      AccessBlocker::blockIp($ip, "Límite de solicitudes de scraping superado ({$maxRequestsPerMinute}/min)", 3600);
      IntrusionLogger::log("IP bloqueada por tasa excesiva de scraping ({$maxRequestsPerMinute} req/min)", [
        'ip' => $ip,
        'rate' => count($data[$ip])
      ]);
    }

    return $exceeded;
  }

  /**
   * Establece cabeceras de seguridad HTTP anti-scraping.
   *
   * @return void
   */
  public static function setSecurityHeaders(): void
  {
    if (!headers_sent()) {
      header('X-Content-Type-Options: nosniff');
      header('X-Frame-Options: SAMEORIGIN');
      header('X-Permitted-Cross-Domain-Policies: none');
    }
  }

  /**
   * Ejecuta la protección anti-scraping y de wrappers para la petición HTTP actual.
   *
   * @param bool $enableRateLimiter
   * @param int $maxRequestsPerMin
   * @return void
   */
  public static function protectRequest(bool $enableRateLimiter = true, int $maxRequestsPerMin = 60): void
  {
    self::setSecurityHeaders();

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // 1. Scraper detectado por User-Agent
    if (self::isScraperUserAgent($ua)) {
      IntrusionLogger::log("Scraper/Script detectado (User-Agent: {$ua})", [
        'ip' => $ip,
        'uri' => $uri,
        'user_agent' => $ua
      ], true);

      ErrorHandler::handle403('Acceso denegado. Las solicitudes automatizadas mediante scripts o bots no están permitidas.');
    }

    // 2. Encabezados no válidos
    if (self::isMissingBrowserHeaders()) {
      IntrusionLogger::log("Solicitud sospechosa sin encabezados de navegador estándar", [
        'ip' => $ip,
        'uri' => $uri
      ], true);

      ErrorHandler::handle403('Acceso denegado. Encabezados de navegador no válidos.');
    }

    // 3. Wrappers PHP maliciosos
    $requestString = json_encode($_REQUEST ?? []);
    if (self::isMaliciousWrapper($requestString) || self::isMaliciousWrapper($uri)) {
      IntrusionLogger::log("Intento de uso de wrapper PHP/SSRF no permitido", [
        'ip' => $ip,
        'uri' => $uri,
        'payload' => $requestString
      ], true);

      ErrorHandler::handle403('Acceso denegado. Stream wrapper no permitido.');
    }

    // 4. Rate Limiter
    if ($enableRateLimiter) {
      if (self::checkScrapingRateLimit($ip, $maxRequestsPerMin)) {
        ErrorHandler::handle403('Ha superado el límite de solicitudes por minuto.');
      }
    }
  }
}
