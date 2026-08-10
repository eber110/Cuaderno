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
   * Verifica el token CSRF para peticiones POST.
   */
  public static function verifyCsrf(bool $throwException = false): bool
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return true;
    }

    $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    if (!self::validateCsrfToken($token)) {
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
      if (defined('ALLOWED_TABLES') && is_array(ALLOWED_TABLES)) {
        self::$allowedTables = array_map('strtolower', ALLOWED_TABLES);
      } else {
        self::$allowedTables = [
          'sitesettings',
          'users',
          'roles',
          'userroles',
          'media',
          'mediables',
          'pages',
          'blogposts',
          'categories',
          'tags',
          'blogpostcategories',
          'blogposttags',
          'comments',
          'survey',
          'surveyanswers',
          'products',
          'productcategories',
          'producttags',
          'orders',
          'orderitems',
          'navigationmenus',
          'menuitems',
          'audittrail',
          'productvariants',
          'addresses',
          'payments',
          'notifications',
          'userpreferences',
          'visitorlog',
          'emailregister',
          'interactions'
        ];
      }
    }

    return self::$allowedTables;
  }

  public static function isAllowedTable(string $tableName): bool
  {
    $normalized = strtolower(trim($tableName));
    return in_array($normalized, self::getAllowedTables(), true);
  }

  public static function validateTableName(string $tableName): string
  {
    if (!self::isAllowedTable($tableName)) {
      throw new \InvalidArgumentException("Tabla '$tableName' no está en la lista de tablas permitidas");
    }

    return strtolower(trim($tableName));
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
   * Registra un intento de intrusión o amenaza en Logs/intrusions.log.
   */
  public static function logIntrusion(string $reason, array $context = []): void
  {
    IntrusionLogger::log($reason, $context);
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

  public static function isProduction(): bool
  {
    return defined('ENVIRONMENT') && ENVIRONMENT === 'production';
  }

  public static function isDevelopment(): bool
  {
    return !defined('ENVIRONMENT') || ENVIRONMENT === 'development';
  }

  public static function configureErrorHandling(): void
  {
    if (self::isProduction()) {
      ini_set('display_errors', '0');
      ini_set('display_startup_errors', '0');
      error_reporting(0);
    } else {
      ini_set('display_errors', '1');
      ini_set('display_startup_errors', '1');
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
}
