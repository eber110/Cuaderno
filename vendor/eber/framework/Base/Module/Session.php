<?php

namespace Base\Module;

use Base\Builder\Builder;

/**
 * Clase de manejo de sesiones con seguridad mejorada y JWT persistente.
 * 
 * Características de seguridad:
 * - Cookies HttpOnly y Secure
 * - Modo estricto de sesión
 * - Regeneración de ID en login
 * - SameSite cookie attribute
 * - Persistencia de sesión mediante JWT firmado con la semilla SEED de .env
 */
class Session
{
  /**
   * Indica si la sesión ya fue iniciada por esta clase.
   */
  private static bool $started = false;

  /**
   * Inicia la sesión con configuración de seguridad mejorada.
   */
  public static function start(): void
  {
    if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
      self::$started = true;
      self::checkJwtSession();
      return;
    }

    $timeSession = defined('TIME_SESSION') ? TIME_SESSION : (defined('TIME_MONTH_S') ? TIME_MONTH_S : 2592000);
    $pathSession = defined('PATH_SESSION') ? PATH_SESSION : '/';
    $domainSession = defined('DOMAIN_SESSION') ? DOMAIN_SESSION : '';
    $sslSession = defined('SSL_SESSION') ? SSL_SESSION : false;

    // Configurar tiempo de vida del garbage collector
    ini_set('session.gc_maxlifetime', (string)$timeSession);

    // Configuraciones de seguridad
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    // Habilitar SameSite para protección CSRF adicional
    if (PHP_VERSION_ID >= 70300) {
      // PHP 7.3+ soporta SameSite en session_set_cookie_params
      session_set_cookie_params([
        'lifetime' => $timeSession,
        'path' => $pathSession,
        'domain' => $domainSession,
        'secure' => $sslSession,
        'httponly' => true,
        'samesite' => 'Lax'
      ]);
    } else {
      // Versiones anteriores
      session_set_cookie_params(
        $timeSession,
        $pathSession . '; SameSite=Lax',
        $domainSession,
        $sslSession,
        true // httponly siempre true
      );
    }

    session_start();
    self::$started = true;
    self::checkJwtSession();
  }

  /**
   * Verifica si $_SESSION['user'] está vacía y la restaura usando el JWT firmado con SEED.
   */
  private static function checkJwtSession(): void
  {
    if (empty($_SESSION['user']) && class_exists('\Base\Module\CookieModule') && class_exists('\Base\Module\TokenModule')) {
      $jwtToken = CookieModule::get('auth_token');
      if ($jwtToken) {
        $userData = TokenModule::validateJWT($jwtToken);
        if ($userData !== false && is_array($userData)) {
          $_SESSION['user'] = $userData;
        }
      }
    }
  }

  /**
   * Regenera el ID de sesión (usar después de login).
   * Previene ataques de fijación de sesión.
   * 
   * @param bool $deleteOldSession Si es true, elimina la sesión anterior.
   */
  public static function regenerateId(bool $deleteOldSession = true): bool
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      return false;
    }

    return session_regenerate_id($deleteOldSession);
  }

  /**
   * Crea los datos de sesión para un usuario e integra JWT persistente usando SEED.
   * Incluye regeneración de ID por seguridad.
   * 
   * @param array $data Datos del usuario.
   * @param array|null $noSelect Campos a excluir de la sesión.
   */
  public static function create_user_session($data, $noSelect = null): void
  {
    // Regenerar ID de sesión en login (previene session fixation)
    self::regenerateId(true);

    if (empty($data[0])) {
      $data = $data;
    } else {
      $data = $data[0];
    }

    if ($noSelect != null) {
      $noSelectOut = [];
      foreach ($noSelect as $value) {
        $noSelectOut[$value] = $value;
      }
      $data = array_diff_key($data, $noSelectOut);
    }

    $_SESSION['user'] = $data;

    // Crear cookie JWT para persistencia de sesión con el SEED de .env
    if (class_exists('\Base\Module\TokenModule') && class_exists('\Base\Module\CookieModule')) {
      $jwtToken = TokenModule::configJWT($data);
      $expiration = defined('TIME_MONTH_S') ? TIME_MONTH_S : (86400 * 30);
      $path = defined('PATH_SESSION') ? PATH_SESSION : '/';
      $domain = defined('DOMAIN_SESSION') ? DOMAIN_SESSION : '';
      $secure = defined('SSL_SESSION') ? SSL_SESSION : false;

      CookieModule::set('auth_token', [
        'value' => $jwtToken,
        'expired' => $expiration,
        'path' => $path,
        'domain' => $domain,
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
      ]);
    }
  }

  /**
   * Verifica si la sesión pertenece al usuario especificado.
   * 
   * @param mixed $my_id ID del usuario a verificar.
   * @param string $camp Campo de la BD a comparar.
   * @return bool True si la sesión pertenece al usuario.
   */
  public static function my_session($my_id = null, $camp = 'user_id'): bool
  {
    self::checkJwtSession();
    $my_post = false;

    if (!is_null($my_id)) {
      $user = (!empty($_SESSION['user'])) ? $_SESSION['user'][$camp] : null;
    } else {
      $user = ($_SESSION['user'][$camp]) ?? null;
    }

    if ($user !== null) {
      if (!is_null($my_id)) {
        $my_post = ($user == $my_id) ? true : false;
      } else {
        $my_post = true;
      }
    }

    return $my_post;
  }

  /**
   * Muestra los datos de sesión del usuario.
   * 
   * @return array|false Datos del usuario o false si no existe.
   */
  public static function user_session_show()
  {
    self::checkJwtSession();
    if (!empty($_SESSION['user'])) {
      return $_SESSION['user'];
    } else {
      return false;
    }
  }

  /**
   * Verifica si existe una sesión de usuario activa.
   * 
   * @return bool True si hay sesión activa.
   */
  public static function session_active(): bool
  {
    self::checkJwtSession();
    return !empty($_SESSION['user']);
  }

  /**
   * Obtiene un dato específico de la sesión del usuario.
   * 
   * @param string $request_data Nombre del campo a obtener.
   * @return mixed Valor del campo o null si no existe.
   */
  public static function session_data($request_data)
  {
    self::checkJwtSession();
    return $_SESSION['user'][$request_data] ?? null;
  }

  /**
   * Destruye completamente la sesión y elimina el JWT de autenticación.
   * Usar en logout para máxima seguridad.
   */
  public static function session_end_all(): void
  {
    // Limpiar todas las variables de sesión
    $_SESSION = [];

    // Eliminar cookie JWT de autenticación persistente
    if (class_exists('\Base\Module\CookieModule')) {
      $path = defined('PATH_SESSION') ? PATH_SESSION : '/';
      $domain = defined('DOMAIN_SESSION') ? DOMAIN_SESSION : '';
      CookieModule::delete('auth_token', $path, $domain);
    }

    // Eliminar cookie de sesión
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
      );
    }

    // Destruir la sesión
    session_unset();
    session_destroy();
    self::$started = false;
  }

  /**
   * Limpia una variable específica de la sesión.
   * 
   * @param string $session Nombre de la variable a limpiar.
   */
  public static function session_end($session): void
  {
    unset($_SESSION[$session]);
  }

  /**
   * Resolver personalizado para roles de usuario.
   */
  private static $roleResolver = null;

  /**
   * Registra un callback personalizado para resolver roles de usuario.
   * 
   * @param callable $resolver Función que recibe ($userId, $sessionData) y retorna array ['role_id' => ..., 'role_name' => ...]
   */
  public static function setRoleResolver(?callable $resolver): void
  {
    self::$roleResolver = $resolver;
  }

  /**
   * Obtiene el rol del usuario actual.
   * 
   * @param array|null $userContext Contexto o datos opcionales de usuario para resolver el rol.
   * @return array Datos del rol (role_id, role_name).
   */
  public static function role(?array $userContext = null): array
  {
    if (self::$roleResolver !== null) {
      $user = $userContext ?? (Session::session_active() ? Session::session_data('user_id') : null);
      $sessionData = $userContext ?? ($_SESSION['user'] ?? ($_SESSION ?? []));
      $resolved = call_user_func(self::$roleResolver, $user, $sessionData);
      if (is_array($resolved)) {
        return $resolved;
      }
    }

    // Si se pasa contexto con rol explícito
    if ($userContext !== null && isset($userContext['role'])) {
      if (is_array($userContext['role'])) {
        return $userContext['role'];
      }
      return [
        'role_id' => $userContext['role_id'] ?? 1,
        'role_name' => (string)$userContext['role']
      ];
    }

    // Si el rol está guardado directamente en los datos de sesión:
    if (Session::session_active()) {
      $roleData = Session::session_data('role');
      if (is_array($roleData)) {
        return $roleData;
      } elseif (is_string($roleData) && !empty($roleData)) {
        return [
          'role_id' => Session::session_data('role_id') ?? 1,
          'role_name' => $roleData
        ];
      }
    }

    // Fallback seguro: usuario invitado sin privilegios
    return [
      'role_id' => 0,
      'role_name' => 'guest'
    ];
  }

  /**
   * Verifica si el usuario actual es administrador.
   * 
   * @return bool True si es administrador.
   */
  public static function admin(): bool
  {
    $roleName = Session::role()['role_name'] ?? '';
    return in_array($roleName, ['administrator', 'admin'], true);
  }

  /**
   * Alias para SecurityModule::getCsrfToken()
   * 
   * @return string Token CSRF.
   */
  public static function csrfToken(): string
  {
    return SecurityModule::getCsrfToken();
  }

  /**
   * Alias para SecurityModule::validateCsrfToken()
   * 
   * @param string|null $token Token a validar.
   * @return bool True si es válido.
   */
  public static function validateCsrf(?string $token): bool
  {
    return SecurityModule::validateCsrfToken($token);
  }
}
