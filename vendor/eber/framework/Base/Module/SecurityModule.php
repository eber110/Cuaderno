<?php

namespace Base\Module;

/**
 * Módulo de seguridad centralizado.
 * 
 * Proporciona funciones para:
 * - Sanitización de entrada (XSS)
 * - Protección CSRF
 * - Validación de identificadores SQL
 * 
 * @example
 * // Sanitizar input
 * $safe = SecurityModule::sanitize($_POST['name']);
 * 
 * // Generar token CSRF
 * $token = SecurityModule::generateCsrfToken();
 * 
 * // Validar token CSRF
 * if (!SecurityModule::validateCsrfToken($_POST['_token'])) {
 *     die('CSRF token inválido');
 * }
 */
class SecurityModule
{
  /**
   * Whitelist de tablas SQL permitidas.
   * Se carga desde la constante ALLOWED_TABLES o usa un array por defecto.
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
    // SANITIZACIÓN DE ENTRADA
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
   * 
   * @param string $key Clave del valor.
   * @param mixed $default Valor por defecto si no existe.
   * @param bool $stripTags Si es true, elimina etiquetas HTML.
   * @return mixed Valor sanitizado.
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
   * 
   * @param string $key Clave del valor.
   * @param mixed $default Valor por defecto si no existe.
   * @param bool $stripTags Si es true, elimina etiquetas HTML.
   * @return mixed Valor sanitizado.
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
   * 
   * @param string $key Clave del valor.
   * @param mixed $default Valor por defecto si no existe.
   * @param bool $stripTags Si es true, elimina etiquetas HTML.
   * @return mixed Valor sanitizado.
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
   * 
   * @param string $input Cadena a escapar.
   * @return string Cadena escapada para JS.
   */
  public static function escapeJs(string $input): string
  {
    return json_encode($input, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
  }

  /**
   * Escapa una cadena para uso seguro en atributos HTML.
   * 
   * @param string $input Cadena a escapar.
   * @return string Cadena escapada.
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
   * 
   * @return string Token CSRF generado.
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
   * 
   * @return string Token CSRF.
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
   * 
   * @param string|null $token Token a validar.
   * @return bool True si el token es válido.
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

    // Verificar expiración
    if ($stored['expiry'] <= time()) {
      unset($_SESSION[self::CSRF_SESSION_KEY]);
      return false;
    }

    // Comparación segura contra timing attacks
    return hash_equals($stored['token'], $token);
  }

  /**
   * Genera un campo hidden HTML con el token CSRF.
   * 
   * @return string HTML del campo hidden.
   */
  public static function csrfField(): string
  {
    $token = self::getCsrfToken();
    return '<input type="hidden" name="_token" value="' . self::escapeAttr($token) . '">';
  }

  /**
   * Genera una meta tag con el token CSRF para uso con AJAX.
   * 
   * @return string HTML de la meta tag.
   */
  public static function csrfMeta(): string
  {
    $token = self::getCsrfToken();
    return '<meta name="csrf-token" content="' . self::escapeAttr($token) . '">';
  }

  /**
   * Verifica el token CSRF automáticamente para requests POST.
   * Debe llamarse al inicio del procesamiento de formularios.
   * 
   * @param bool $throwException Si es true, lanza excepción en vez de retornar false.
   * @return bool True si el token es válido o el request no es POST.
   * @throws \RuntimeException Si el token es inválido y $throwException es true.
   */
  public static function verifyCsrf(bool $throwException = false): bool
  {
    // Solo verificar en requests POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return true;
    }

    // Buscar token en POST o headers (para AJAX)
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
    // VALIDACIÓN SQL
    // =========================================================================

  /**
   * Obtiene la lista de tablas permitidas.
   * 
   * @return array Lista de nombres de tabla en minúsculas.
   */
  private static function getAllowedTables(): array
  {
    if (self::$allowedTables === null) {
      if (defined('ALLOWED_TABLES') && is_array(ALLOWED_TABLES)) {
        self::$allowedTables = array_map('strtolower', ALLOWED_TABLES);
      } else {
        // Tablas por defecto basadas en el esquema del proyecto
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

  /**
   * Valida que un nombre de tabla esté en la whitelist.
   * 
   * @param string $tableName Nombre de la tabla a validar.
   * @return bool True si la tabla está permitida.
   */
  public static function isAllowedTable(string $tableName): bool
  {
    $normalized = strtolower(trim($tableName));
    return in_array($normalized, self::getAllowedTables(), true);
  }

  /**
   * Valida un nombre de tabla y lanza excepción si no está permitido.
   * 
   * @param string $tableName Nombre de la tabla.
   * @return string Nombre de tabla validado.
   * @throws \InvalidArgumentException Si la tabla no está en la whitelist.
   */
  public static function validateTableName(string $tableName): string
  {
    if (!self::isAllowedTable($tableName)) {
      throw new \InvalidArgumentException(
        "Tabla '$tableName' no está en la lista de tablas permitidas"
      );
    }

    return strtolower(trim($tableName));
  }

  /**
   * Agrega una tabla a la whitelist en runtime.
   * Útil para migraciones o tablas temporales.
   * 
   * @param string $tableName Nombre de la tabla a agregar.
   */
  public static function addAllowedTable(string $tableName): void
  {
    $tables = self::getAllowedTables();
    $normalized = strtolower(trim($tableName));

    if (!in_array($normalized, $tables, true)) {
      self::$allowedTables[] = $normalized;
    }
  }

    // =========================================================================
    // UTILIDADES
    // =========================================================================

  /**
   * Asegura que la sesión esté iniciada.
   */
  private static function ensureSession(): void
  {
    if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
      if (class_exists('\\Base\\module\\Session')) {
        \Base\Module\Session::start();
      } else {
        session_start();
      }
    }
  }

  /**
   * Verifica si estamos en entorno de producción.
   * 
   * @return bool True si es producción.
   */
  public static function isProduction(): bool
  {
    return defined('ENVIRONMENT') && ENVIRONMENT === 'production';
  }

  /**
   * Verifica si estamos en entorno de desarrollo.
   * 
   * @return bool True si es desarrollo.
   */
  public static function isDevelopment(): bool
  {
    return !defined('ENVIRONMENT') || ENVIRONMENT === 'development';
  }

  /**
   * Configura el manejo de errores según el entorno.
   * Debe llamarse temprano en el bootstrap de la aplicación.
   */
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

  /**
   * Genera un hash seguro para contraseñas.
   * 
   * @param string $password Contraseña en texto plano.
   * @return string Hash de la contraseña.
   */
  public static function hashPassword(string $password): string
  {
    return password_hash($password, PASSWORD_ARGON2ID, [
      'memory_cost' => 65536,
      'time_cost' => 4,
      'threads' => 3
    ]);
  }

  /**
   * Verifica una contraseña contra su hash.
   * 
   * @param string $password Contraseña en texto plano.
   * @param string $hash Hash almacenado.
   * @return bool True si coinciden.
   */
  public static function verifyPassword(string $password, string $hash): bool
  {
    return password_verify($password, $hash);
  }

  /**
   * Genera un token aleatorio seguro.
   * 
   * @param int $length Longitud del token en bytes (resultado será el doble en hex).
   * @return string Token hexadecimal.
   */
  public static function generateToken(int $length = 32): string
  {
    return bin2hex(random_bytes($length));
  }
}
