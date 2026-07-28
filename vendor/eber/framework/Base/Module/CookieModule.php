<?php

namespace Base\Module;

/**
 * Módulo para gestión de cookies.
 * 
 * Proporciona una interfaz limpia para manejar cookies.
 * 
 * @example
 * // Crear cookie
 * $value = CookieModule::set('user_token', ['expired' => 86400]);
 * 
 * // Obtener cookie
 * $token = CookieModule::get('user_token');
 * 
 * // Eliminar cookie
 * CookieModule::delete('user_token');
 */
class CookieModule
{
  /**
   * Crea o recupera una cookie.
   * 
   * Si no se pasa 'value' explícito y la cookie ya existe, devuelve su valor actual.
   * Si se pasa 'value' explícito o la cookie no existe, la crea/actualiza con setcookie().
   * 
   * @param string $name Nombre de la cookie.
   * @param array $options Opciones de configuración:
   *   - value: string - Valor de la cookie (default: aleatorio)
   *   - expired: int - Tiempo de expiración en segundos (default: sesión)
   *   - path: string - Ruta válida (default: '/')
   *   - domain: string - Dominio válido (default: '')
   *   - secure: bool - Solo HTTPS (default: false)
   *   - httponly: bool - Solo HTTP, no JavaScript (default: false)
   *   - samesite: string - Política SameSite: 'Lax', 'Strict', 'None' (default: 'Lax')
   * @return string Valor de la cookie.
   */
  public static function set(string $name, array $options = []): string
  {
    // Si NO se especifica un valor explícito en $options y la cookie ya existe, retornar valor actual
    if (!array_key_exists('value', $options) && isset($_COOKIE[$name])) {
      return $_COOKIE[$name];
    }

    // Configurar valores
    $value = $options['value'] ?? bin2hex(random_bytes(15));
    $expires = isset($options['expired']) ? time() + (int)$options['expired'] : 0;
    $path = $options['path'] ?? '/';
    $domain = !empty($options['domain']) ? $options['domain'] : '';
    $secure = $options['secure'] ?? (defined('SSL_SESSION') ? SSL_SESSION : false);
    $httponly = $options['httponly'] ?? false;
    $samesite = $options['samesite'] ?? 'Lax';

    $cookieParams = [
      'expires' => $expires,
      'path' => $path,
      'secure' => $secure,
      'httponly' => $httponly,
      'samesite' => $samesite
    ];

    if (!empty($domain)) {
      $cookieParams['domain'] = $domain;
    }

    // Usar la sintaxis moderna de setcookie (PHP 7.3+)
    setcookie($name, $value, $cookieParams);

    // Actualizar $_COOKIE para disponibilidad inmediata en el script actual
    $_COOKIE[$name] = $value;

    return $value;
  }

  /**
   * Obtiene el valor de una cookie.
   * 
   * @param string $name Nombre de la cookie.
   * @param mixed $default Valor por defecto si no existe.
   * @return mixed Valor de la cookie o el default.
   */
  public static function get(string $name, mixed $default = null): mixed
  {
    return $_COOKIE[$name] ?? $default;
  }

  /**
   * Verifica si una cookie existe.
   * 
   * @param string $name Nombre de la cookie.
   * @return bool True si existe.
   */
  public static function exists(string $name): bool
  {
    return isset($_COOKIE[$name]);
  }

  /**
   * Elimina una cookie.
   * 
   * @param string $name Nombre de la cookie.
   * @param string $path Ruta de la cookie (debe coincidir con la creación).
   * @param string $domain Dominio de la cookie.
   * @return bool True si existía y se eliminó.
   */
  public static function delete(string $name, string $path = '/', string $domain = ''): bool
  {
    if (!isset($_COOKIE[$name])) {
      return false;
    }

    $cookieParams = [
      'expires' => time() - 3600,
      'path' => $path
    ];

    if (!empty($domain)) {
      $cookieParams['domain'] = $domain;
    }

    // Establecer cookie expirada
    setcookie($name, '', $cookieParams);

    unset($_COOKIE[$name]);

    return true;
  }

  /**
   * Obtiene todas las cookies.
   * 
   * @return array Todas las cookies actuales.
   */
  public static function all(): array
  {
    return $_COOKIE;
  }

  /**
   * Crea una cookie segura (HTTPS + HttpOnly).
   * 
   * @param string $name Nombre de la cookie.
   * @param string $value Valor de la cookie.
   * @param int $expires Tiempo de expiración en segundos.
   * @return string Valor de la cookie.
   */
  public static function setSecure(string $name, string $value, int $expires = 86400): string
  {
    return self::set($name, [
      'value' => $value,
      'expired' => $expires,
      'secure' => true,
      'httponly' => true,
      'samesite' => 'Strict'
    ]);
  }
}
