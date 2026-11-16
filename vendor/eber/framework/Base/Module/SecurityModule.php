<?php

namespace Base\Module;

use Base\Module\Security\AccessBlocker;
use Base\Module\Security\AntiScraper;
use Base\Module\Security\IntrusionLogger;
use Base\Module\Security\RouteScanner;

/**
 * Módulo de Seguridad Centralizado y Fachada (Facade).
 * 
 * Actúa como punto de acceso unificado para todas las herramientas de seguridad del framework,
 * delegando la ejecución específica a sus clases especializadas en Base\Module\Security (SRP):
 * - RouteScanner: Escaneo de rutas y generación de mapa de seguridad.
 * - IntrusionLogger: Monitoreo y registro de eventos/amenazas de seguridad.
 * - AccessBlocker: Control de IP Banning, rate limiting e IPs bloqueadas.
 * - AntiScraper: Detección de scrapers/bots, cabeceras web y stream wrappers PHP.
 * 
 * @example
 * // Sanitización
 * $safe = SecurityModule::sanitize($_POST['name']);
 * 
 * // Escaneo de rutas
 * SecurityModule::scanRoutes();
 * 
 * // Bloqueo de IP
 * SecurityModule::blockAccess('192.168.1.100', 'Actividad sospechosa');
 */
class SecurityModule
{
  /**
   * Whitelist de tablas SQL permitidas.
   */
  private static ?array $allowedTables = null;

  /**
   * Indica si la whitelist estricta de tablas está activada explícitamente.
   */
  private static bool $whitelistEnabled = false;

  /**
   * Nombre de la clave de sesión para el token CSRF.
   */
  private const CSRF_SESSION_KEY = 'csrf_token';

  /**
   * Tiempo de vida del token CSRF en segundos (1 hora).
   */
  private const CSRF_TOKEN_TTL = 3600;

    // =========================================================================
    // SANITIZACIÓN DE ENTRADA & SEGURIDAD XSS
    // =========================================================================

  /**
   * Sanitiza una cadena para prevenir XSS.
   * 
   * @param string|null $input Cadena a sanitizar.
   * @param bool $stripTags Si es true, elimina etiquetas HTML.
   * @return string Cadena sanitizada.
   */
  public static function sanitize(?string $input, bool $stripTags = false): string
  {
    if ($input === null) {
      return '';
    }

    $input = trim($input);

    if ($stripTags) {
      $input = strip_tags($input);
    }

    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }

  /**
   * Sanitiza un array de datos recursivamente.
   * 
   * @param array $data Array a sanitizar.
   * @param bool $stripTags Si es true, elimina etiquetas HTML.
   * @return array Array sanitizado.
   */
  public static function sanitizeArray(array $data, bool $stripTags = false): array
  {
    $sanitized = [];

    foreach ($data as $key => $value) {
      $safeKey = self::sanitize((string) $key);

      if (is_array($value)) {
        $sanitized[$safeKey] = self::sanitizeArray($value, $stripTags);
      } elseif (is_string($value)) {
        $sanitized[$safeKey] = self::sanitize($value, $stripTags);
      } else {
        $sanitized[$safeKey] = $value;
      }
    }

    return $sanitized;
  }

  /**
   * Obtiene un valor sanitizado de $_POST.
   */
  public static function post(string $key, mixed $default = null, bool $stripTags = false): mixed
  {
    if (!isset($_POST[$key])) {
      return $default;
    }

    $value = $_POST[$key];

    if (is_array($value)) {
      return self::sanitizeArray($value, $stripTags);
    }

    return self::sanitize((string) $value, $stripTags);
  }

  /**
   * Obtiene un valor sanitizado de $_GET.
   */
  public static function get(string $key, mixed $default = null, bool $stripTags = false): mixed
  {
    if (!isset($_GET[$key])) {
      return $default;
    }

    $value = $_GET[$key];

    if (is_array($value)) {
      return self::sanitizeArray($value, $stripTags);
    }

    return self::sanitize((string) $value, $stripTags);
  }

  /**
   * Obtiene un valor sanitizado de $_REQUEST.
   */
  public static function request(string $key, mixed $default = null, bool $stripTags = false): mixed
  {
    if (!isset($_REQUEST[$key])) {
      return $default;
    }

    $value = $_REQUEST[$key];

    if (is_array($value)) {
      return self::sanitizeArray($value, $stripTags);
    }

    return self::sanitize((string) $value, $stripTags);
  }

  /**
   * Escapa una cadena para uso seguro en JavaScript.
   */
  public static function escapeJs(string $input): string
  {
    return json_encode($input, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
  }

  /**
   * Escapa una cadena para uso seguro en atributos HTML.
   */
  public static function escapeAttr(string $input): string
  {
    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }

  /**
   * Sanitiza HTML enriquecido mediante árbol DOM conservando solo las etiquetas permitidas
   * y eliminando todos los manejadores de eventos (`on*`), `style`, scripts y URIs peligrosas.
   *
   * @param string $html Entrada HTML potencialmente insegura.
   * @param string $allowedTagsSpec Lista de etiquetas estilo strip_tags (ej. '<p><strong><ul><li>').
   * @return string HTML seguro sin atributos ni etiquetas peligrosas.
   */
  public static function sanitizeRichHtml(string $html, string $allowedTagsSpec = ''): string
  {
    if (!str_contains($html, '<')) {
      return $html;
    }

    preg_match_all('/<\s*([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', strtolower($allowedTagsSpec), $tagMatches);
    $allowedTags = array_values(array_unique($tagMatches[1] ?? []));

    if (empty($allowedTags)) {
      return strip_tags($html);
    }

    if (!class_exists(\DOMDocument::class)) {
      return htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    $dangerousTags = [
      'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet',
      'svg', 'math', 'link', 'meta', 'base', 'form', 'input', 'textarea', 'select', 'button', 'noscript'
    ];

    $prevErrors = libxml_use_internal_errors(true);
    $dom = new \DOMDocument('1.0', 'UTF-8');
    $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="__frame_root__">' . $html . '</div></body></html>';
    $loaded = @$dom->loadHTML($wrapped, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($prevErrors);

    if (!$loaded) {
      return htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    $root = $dom->getElementById('__frame_root__');
    if (!$root instanceof \DOMElement) {
      return '';
    }

    self::cleanDomNode($root, $allowedTags, $dangerousTags);

    $out = '';
    foreach ($root->childNodes as $child) {
      $out .= $dom->saveHTML($child);
    }
    return $out;
  }

  /**
   * Recorre recursivamente nodos DOM eliminando elementos peligrosos y atributos no permitidos.
   */
  private static function cleanDomNode(\DOMNode $parent, array $allowedTags, array $dangerousTags): void
  {
    $children = [];
    foreach ($parent->childNodes as $child) {
      $children[] = $child;
    }

    foreach ($children as $child) {
      if ($child instanceof \DOMComment || $child instanceof \DOMProcessingInstruction) {
        $parent->removeChild($child);
        continue;
      }

      if (!($child instanceof \DOMElement)) {
        continue;
      }

      $tagName = strtolower($child->tagName);

      if (in_array($tagName, $dangerousTags, true)) {
        $parent->removeChild($child);
        continue;
      }

      self::cleanDomNode($child, $allowedTags, $dangerousTags);

      if (!in_array($tagName, $allowedTags, true)) {
        while ($child->firstChild !== null) {
          $parent->insertBefore($child->firstChild, $child);
        }
        $parent->removeChild($child);
        continue;
      }

      $attrNames = [];
      if ($child->hasAttributes()) {
        foreach ($child->attributes as $attr) {
          $attrNames[] = $attr->name;
        }
      }

      foreach ($attrNames as $attrName) {
        $lowerAttr = strtolower($attrName);
        if ($tagName === 'a' && $lowerAttr === 'href') {
          $safeHref = self::sanitizeRedirectUrl($child->getAttribute($attrName), true);
          if ($safeHref === '#') {
            $child->removeAttribute($attrName);
          } else {
            $child->setAttribute('href', $safeHref);
            $child->setAttribute('rel', 'noopener noreferrer');
          }
          continue;
        }
        $child->removeAttribute($attrName);
      }
    }
  }

  /**
   * Sanitiza una URL de destino o redirección bloqueando CRLF, esquemas peligrosos,
   * rutas protocol-relative (`//`, `/\`, `\/`, `\\`) y redirecciones abiertas no autorizadas.
   */
  public static function sanitizeRedirectUrl(string $url, bool $allowExternal = false, array $allowedHosts = []): string
  {
    $clean = str_replace(["\r", "\n", "\0"], '', trim($url));
    $clean = preg_replace('/[\x00-\x1F\x7F]/', '', $clean) ?? '';
    $clean = preg_replace('/%0[ad]/i', '', $clean) ?? '';

    if ($clean === '' || $clean === '#') {
      return '#';
    }

    // Bloquear rutas ambiguas o protocol-relative (incluyendo barras invertidas /\ o \/ codificadas)
    $decodedPrefix = strtolower(rawurldecode(substr($clean, 0, 12)));
    if (
      str_starts_with($clean, '//') ||
      str_starts_with($clean, '/\\') ||
      str_starts_with($clean, '\\') ||
      str_starts_with($decodedPrefix, '//') ||
      str_starts_with($decodedPrefix, '/\\') ||
      str_starts_with($decodedPrefix, '\\')
    ) {
      return '/';
    }

    // Rutas relativas seguras dentro del mismo origen (una sola / inicial)
    if (str_starts_with($clean, '/') && !str_contains($clean, '\\')) {
      return $clean;
    }

    // Permitir mailto: y tel: solo en contextos de enlace cuando $allowExternal = true
    if ($allowExternal && preg_match('#^(mailto|tel):[^\s<>]+$#i', $clean)) {
      return $clean;
    }

    $parts = @parse_url($clean);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
      return $allowExternal ? '#' : '/';
    }

    $scheme = strtolower((string)$parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
      return $allowExternal ? '#' : '/';
    }

    if ($allowExternal && empty($allowedHosts)) {
      return $clean;
    }

    $targetHost = strtolower(trim((string)$parts['host']));
    $validHosts = array_map(static fn($h) => strtolower(trim((string)$h)), $allowedHosts);

    if (defined('FORCE_DOMAIN') && !empty(FORCE_DOMAIN)) {
      $validHosts[] = strtolower(trim((string)FORCE_DOMAIN));
    } elseif (defined('DOMAIN') && !empty(DOMAIN)) {
      $domHost = @parse_url((string)DOMAIN, PHP_URL_HOST);
      if (is_string($domHost) && $domHost !== '') {
        $validHosts[] = strtolower($domHost);
      }
    }

    if (!empty($_SERVER['HTTP_HOST'])) {
      $curHost = strtolower(preg_replace('/:\d+$/', '', (string)$_SERVER['HTTP_HOST']));
      if ($curHost !== '') {
        $validHosts[] = $curHost;
      }
    }

    $validHosts = array_values(array_unique(array_filter($validHosts)));
    return in_array($targetHost, $validHosts, true) ? $clean : '/';
  }

    // =========================================================================
    // PROTECCIÓN CSRF
    // =========================================================================

  /**
   * Genera un token CSRF y lo almacena en la sesión.
   */
  public static function generateCsrfToken(): string
  {
    self::ensureSession();

    $token = bin2hex(random_bytes(32));
    $expiry = time() + self::CSRF_TOKEN_TTL;

    $_SESSION[self::CSRF_SESSION_KEY] = [
      'token' => $token,
      'expiry' => $expiry
    ];

    return $token;
  }

  /**
   * Obtiene el token CSRF actual o genera uno nuevo.
   */
  public static function getCsrfToken(): string
  {
    self::ensureSession();

    if (
      !isset($_SESSION[self::CSRF_SESSION_KEY]) ||
      !is_array($_SESSION[self::CSRF_SESSION_KEY]) ||
      $_SESSION[self::CSRF_SESSION_KEY]['expiry'] <= time()
    ) {
      return self::generateCsrfToken();
    }

    return $_SESSION[self::CSRF_SESSION_KEY]['token'];
  }

  /**
   * Valida un token CSRF.
   */
  public static function validateCsrfToken(?string $token): bool
  {
    self::ensureSession();

    if ($token === null || empty($token)) {
      return false;
    }

    if (
      !isset($_SESSION[self::CSRF_SESSION_KEY]) ||
      !is_array($_SESSION[self::CSRF_SESSION_KEY])
    ) {
      return false;
    }

    $stored = $_SESSION[self::CSRF_SESSION_KEY];

    if ($stored['expiry'] <= time()) {
      unset($_SESSION[self::CSRF_SESSION_KEY]);
      return false;
    }

    return hash_equals($stored['token'], $token);
  }

  /**
   * Genera un campo hidden HTML con el token CSRF.
   */
  public static function csrfField(): string
  {
    $token = self::getCsrfToken();
    return '<input type="hidden" name="_token" value="' . self::escapeAttr($token) . '">';
  }

  /**
   * Genera una meta tag con el token CSRF para uso con AJAX.
   */
  public static function csrfMeta(): string
  {
    $token = self::getCsrfToken();
    return '<meta name="csrf-token" content="' . self::escapeAttr($token) . '">';
  }

  /**
   * Verifica el token CSRF para peticiones que modifican estado (POST, PUT, PATCH, DELETE).
   *
   * @param bool $throwException Si lanza RuntimeException cuando falla.
   * @param string|null $methodOverride Método HTTP efectivo opcional.
   * @param array|null $bodyData Cuerpo parseado opcional (p. ej. payload JSON).
   */
  public static function verifyCsrf(bool $throwException = false, ?string $methodOverride = null, ?array $bodyData = null): bool
  {
    $method = strtoupper(trim((string)($methodOverride ?? $_SERVER['REQUEST_METHOD'] ?? 'GET')));
    if ($method === 'POST' && isset($_POST['_method'])) {
      $submitted = strtoupper(trim((string)$_POST['_method']));
      if (in_array($submitted, ['PUT', 'PATCH', 'DELETE'], true)) {
        $method = $submitted;
      }
    }

    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
      return true;
    }

    $token = $_POST['_token']
      ?? $_POST['csrf_token']
      ?? ($bodyData['_token'] ?? null)
      ?? ($bodyData['csrf_token'] ?? null)
      ?? $_SERVER['HTTP_X_CSRF_TOKEN']
      ?? $_SERVER['HTTP_X_XSRF_TOKEN']
      ?? null;

    if (!is_string($token) || !self::validateCsrfToken($token)) {
      if ($throwException) {
        throw new \RuntimeException('Token CSRF inválido o expirado');
      }
      return false;
    }

    return true;
  }

    // =========================================================================
    // VALIDACIÓN DE TABLAS SQL
    // =========================================================================

  private static function getAllowedTables(): array
  {
    if (self::$allowedTables === null) {
      if (defined('ALLOWED_TABLES') && is_array(ALLOWED_TABLES) && !empty(ALLOWED_TABLES)) {
        self::$allowedTables = array_map('strtolower', ALLOWED_TABLES);
        self::$whitelistEnabled = true;
      } else {
        self::$allowedTables = [];
        self::$whitelistEnabled = false;
      }
    }

    return self::$allowedTables;
  }

  public static function isAllowedTable(string $tableName): bool
  {
    $normalized = strtolower(trim($tableName));
    $allowed = self::getAllowedTables();

    // Whitelist opt-in: Si se definieron tablas explícitas no vacías en ALLOWED_TABLES, validar contra la whitelist
    if (self::$whitelistEnabled) {
      return in_array($normalized, $allowed, true);
    }

    // Zero-Config (por defecto): validar sintaxis segura de identificador SQL (letras, números y guión bajo)
    return (bool)preg_match('/^[a-zA-Z0-9_]+$/', $normalized);
  }

  public static function validateTableName(string $tableName): string
  {
    $normalized = strtolower(trim($tableName));

    if (!self::isAllowedTable($tableName)) {
      throw new \InvalidArgumentException("Tabla '$tableName' no es válida o no está en la lista de tablas permitidas");
    }

    return $normalized;
  }

  public static function addAllowedTable(string $tableName): void
  {
    $tables = self::getAllowedTables();
    $normalized = strtolower(trim($tableName));

    if (!in_array($normalized, $tables, true)) {
      self::$allowedTables[] = $normalized;
    }
  }

    // =========================================================================
    // ESCANEO Y ANÁLISIS DE RUTAS (Delega en RouteScanner)
    // =========================================================================

  /**
   * Escanea las rutas registradas y guarda el mapa en App/Config/routes_security.json.
   */
  public static function scanRoutes(?string $outputPath = null): array
  {
    return RouteScanner::scanAndSaveRoutes($outputPath);
  }

  /**
   * Obtiene la configuración de rutas válidas guardadas.
   */
  public static function getValidRoutes(): array
  {
    return RouteScanner::getSecurityRoutesConfig();
  }

  /**
   * Comprueba si una URI y método son válidos.
   */
  public static function isRouteValid(string $uri, string $method = 'GET'): bool
  {
    return RouteScanner::isPathValid($uri, $method);
  }

  /**
   * Evalúa si una URI contiene patrones sospechosos de escaneo/intrusión.
   */
  public static function isSuspiciousPath(string $uri): bool
  {
    $uriLower = strtolower($uri);

    $suspiciousPatterns = [
      '.env',
      '.git',
      '.aws',
      '.ssh',
      'wp-admin',
      'wp-login',
      'wp-config',
      'xmlrpc.php',
      'phpmyadmin',
      'eval-stdin',
      '/shell',
      '/cmd',
      '..',
      '%00',
      '<script',
      'select%',
      'union select',
      'base64_decode'
    ];

    foreach ($suspiciousPatterns as $pattern) {
      if (str_contains($uriLower, $pattern)) {
        return true;
      }
    }

    return false;
  }

    // =========================================================================
    // INTRUSIONES & LOGS (Delega en IntrusionLogger)
    // =========================================================================

  /**
   * Registra un intento de intrusión o amenaza en App/Safety/intrusions.log.
   */
  public static function logIntrusion(string $reason, array $context = [], bool $incrementCount = true): void
  {
    IntrusionLogger::log($reason, $context, $incrementCount);
  }

    // =========================================================================
    // BLOQUEO DE ACCESOS (Delega en AccessBlocker)
    // =========================================================================

  /**
   * Bloquea el acceso a una dirección IP.
   */
  public static function blockAccess(string $ip, string $reason = 'Manual Block', int $duration = 86400): bool
  {
    return AccessBlocker::blockIp($ip, $reason, $duration);
  }

  /**
   * Desbloquea una dirección IP.
   */
  public static function unblockAccess(string $ip): bool
  {
    return AccessBlocker::unblockIp($ip);
  }

  /**
   * Verifica si una IP está actualmente bloqueada.
   */
  public static function isAccessBlocked(string $ip): bool
  {
    return AccessBlocker::isIpBlocked($ip);
  }

    // =========================================================================
    // PROTECCIÓN ANTI-SCRAPING & WRAPPERS (Delega en AntiScraper)
    // =========================================================================

  /**
   * Determina si la petición proviene de un script o bot de scraping.
   */
  public static function isScraper(?string $userAgent = null): bool
  {
    return AntiScraper::isScraperUserAgent($userAgent);
  }

  /**
   * Evalúa si una cadena contiene stream wrappers PHP no permitidos.
   */
  public static function containsMaliciousWrapper(string $input): bool
  {
    return AntiScraper::isMaliciousWrapper($input);
  }

  /**
   * Ejecuta la protección anti-scraping y de wrappers completa.
   */
  public static function protectAgainstScraping(bool $enableRateLimiter = true, int $maxRequestsPerMin = 60): void
  {
    AntiScraper::protectRequest($enableRateLimiter, $maxRequestsPerMin);
  }

    // =========================================================================
    // UTILIDADES GENERALES
    // =========================================================================

  private static function ensureSession(): void
  {
    if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
      if (class_exists('\\Base\\Module\\Session')) {
        \Base\Module\Session::start();
      } else {
        session_start();
      }
    }
  }

  /**
   * Resuelve el entorno actual con política estricta fail-closed.
   * Cualquier valor ausente, vacío o ambiguo se normaliza a 'production'.
   */
  public static function resolveEnvironment(): string
  {
    if (defined('ENVIRONMENT')) {
      $normalized = strtolower(trim((string)ENVIRONMENT));
      if (in_array($normalized, ['testing', 'test'], true)) {
        return 'testing';
      }
      if (in_array($normalized, ['dev', 'development', 'local'], true)) {
        return 'development';
      }
      return 'production';
    }

    foreach (['APP_ENV', 'ENVIRONMENT'] as $varName) {
      foreach ([$_ENV[$varName] ?? null, $_SERVER[$varName] ?? null, getenv($varName)] as $raw) {
        if ($raw === false || $raw === null) {
          continue;
        }
        $normalized = strtolower(trim((string)$raw));
        if ($normalized === '') {
          continue;
        }
        if (in_array($normalized, ['testing', 'test'], true)) {
          return 'testing';
        }
        if (in_array($normalized, ['dev', 'development', 'local'], true)) {
          return 'development';
        }
        return 'production';
      }
    }

    return 'production';
  }

  public static function isProduction(): bool
  {
    return self::resolveEnvironment() === 'production';
  }

  public static function isDevelopment(): bool
  {
    return in_array(self::resolveEnvironment(), ['development', 'testing'], true);
  }

  public static function configureErrorHandling(): void
  {
    @ini_set('log_errors', '1');
    if (self::isProduction()) {
      @ini_set('display_errors', '0');
      @ini_set('display_startup_errors', '0');
      error_reporting(0);
    } else {
      @ini_set('display_errors', '1');
      @ini_set('display_startup_errors', '1');
      error_reporting(E_ALL);
    }
  }

  public static function hashPassword(string $password): string
  {
    return password_hash($password, PASSWORD_ARGON2ID, [
      'memory_cost' => 65536,
      'time_cost' => 4,
      'threads' => 3
    ]);
  }

  public static function verifyPassword(string $password, string $hash): bool
  {
    return password_verify($password, $hash);
  }

  public static function generateToken(int $length = 32): string
  {
    return bin2hex(random_bytes($length));
  }

  // =========================================================================
  // RESOLUCIÓN DE IP, PROXIES CONFIABLES (CIDR), HTTPS Y HOST VALIDADO
  // =========================================================================

  /**
   * Obtiene la lista de IPs/CIDRs de proxies confiables desde configuración o entorno.
   *
   * @return array<int, string>
   */
  public static function getTrustedProxies(): array
  {
    $raw = defined('TRUSTED_PROXIES')
      ? TRUSTED_PROXIES
      : ($_ENV['TRUSTED_PROXIES'] ?? $_SERVER['TRUSTED_PROXIES'] ?? getenv('TRUSTED_PROXIES') ?: '');

    if (is_array($raw)) {
      $entries = $raw;
    } else {
      $entries = explode(',', (string)$raw);
    }

    return array_values(array_filter(array_map('trim', $entries), static fn(string $v): bool => $v !== ''));
  }

  /**
   * Comprueba si una IP pertenece a una IP exacta o rango CIDR IPv4/IPv6 mediante máscara binaria.
   */
  public static function isIpInCidr(string $ip, string $cidr): bool
  {
    $ip = trim($ip);
    $cidr = trim($cidr);
    if ($ip === '' || $cidr === '') {
      return false;
    }

    if (!str_contains($cidr, '/')) {
      $binIp = @inet_pton($ip);
      $binTarget = @inet_pton($cidr);
      return $binIp !== false && $binTarget !== false && $binIp === $binTarget;
    }

    [$subnet, $prefixRaw] = explode('/', $cidr, 2);
    if (!is_numeric($prefixRaw)) {
      return false;
    }
    $prefix = (int)$prefixRaw;

    $binIp = @inet_pton($ip);
    $binSubnet = @inet_pton(trim($subnet));
    if ($binIp === false || $binSubnet === false || strlen($binIp) !== strlen($binSubnet)) {
      return false;
    }

    $maxBits = strlen($binIp) * 8;
    if ($prefix < 0 || $prefix > $maxBits) {
      return false;
    }

    $fullBytes = intdiv($prefix, 8);
    $remainderBits = $prefix % 8;

    if ($fullBytes > 0 && substr($binIp, 0, $fullBytes) !== substr($binSubnet, 0, $fullBytes)) {
      return false;
    }

    if ($remainderBits > 0) {
      $mask = (0xFF << (8 - $remainderBits)) & 0xFF;
      if ((ord($binIp[$fullBytes]) & $mask) !== (ord($binSubnet[$fullBytes]) & $mask)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Indica si REMOTE_ADDR (o la IP provista) corresponde a un proxy inverso confiable.
   */
  public static function isTrustedProxy(?string $remoteAddr = null): bool
  {
    $addr = trim((string)($remoteAddr ?? $_SERVER['REMOTE_ADDR'] ?? ''));
    if ($addr === '' || filter_var($addr, FILTER_VALIDATE_IP) === false) {
      return false;
    }

    foreach (self::getTrustedProxies() as $trusted) {
      if (self::isIpInCidr($addr, $trusted)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Resuelve la IP real del cliente validando cabeceras de reenvío únicamente si
   * REMOTE_ADDR pertenece a TRUSTED_PROXIES (previene IP spoofing).
   */
  public static function resolveClientIp(): string
  {
    $remoteAddr = trim((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
    if (filter_var($remoteAddr, FILTER_VALIDATE_IP) === false) {
      $remoteAddr = '127.0.0.1';
    }

    $proxies = self::getTrustedProxies();
    if (empty($proxies) || !self::isTrustedProxy($remoteAddr)) {
      return $remoteAddr;
    }

    // Priorizar cadenas de proxy evaluando de derecha a izquierda para saltar proxies confiables internos
    $forwardedFor = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
    if ($forwardedFor !== '') {
      $chain = array_reverse(array_map('trim', explode(',', $forwardedFor)));
      foreach ($chain as $candidate) {
        if (filter_var($candidate, FILTER_VALIDATE_IP) === false) {
          continue;
        }
        $isProxy = false;
        foreach ($proxies as $trusted) {
          if (self::isIpInCidr($candidate, $trusted)) {
            $isProxy = true;
            break;
          }
        }
        if (!$isProxy) {
          return $candidate;
        }
      }
    }

    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'] as $header) {
      $candidate = trim((string)($_SERVER[$header] ?? ''));
      if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
        return $candidate;
      }
    }

    return $remoteAddr;
  }

  /**
   * Determina si la petición actual usa HTTPS, aceptando cabeceras X-Forwarded-Proto/SSL
   * únicamente cuando proviene de un proxy configurado en TRUSTED_PROXIES.
   */
  public static function isHttpsRequest(): bool
  {
    if ((!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
      || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    ) {
      return true;
    }

    if (self::isTrustedProxy()) {
      if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower(trim((string)$_SERVER['HTTP_X_FORWARDED_PROTO'])) === 'https') {
        return true;
      }
      if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower(trim((string)$_SERVER['HTTP_X_FORWARDED_SSL'])) === 'on') {
        return true;
      }
    }

    return false;
  }
}
