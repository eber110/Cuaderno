<?php

namespace Base\Module;

use Base\Builder\Builder;
use Exception;

/**
 * Módulo de Autenticación Ligero, Seguro y Agnóstico de Esquema.
 * 
 * Ofrece gestión de autenticación desacoplada sin imponer nombres rígidos
 * de tablas ni columnas. Compatible con Argon2id y Bcrypt nativo.
 * 
 * @example
 * // Iniciar sesión con email y contraseña:
 * if (AuthModule::attempt($email, $password)) {
 *     // Usuario autenticado
 *     $user = AuthModule::user();
 * }
 * 
 * // Verificar si hay usuario autenticado:
 * if (AuthModule::check()) { ... }
 * 
 * // Cerrar sesión:
 * AuthModule::logout();
 */
class AuthModule
{
  /**
   * Genera un hash seguro para la contraseña.
   * Utiliza PASSWORD_ARGON2ID si está disponible en PHP, de lo contrario PASSWORD_BCRYPT.
   *
   * @param string $password Contraseña en texto plano
   * @return string Hash de la contraseña
   */
  public static function hashPassword(string $password): string
  {
    if (defined('PASSWORD_ARGON2ID')) {
      return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost'   => 4,
        'threads'     => 3
      ]);
    }
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
  }

  /**
   * Comprueba si una contraseña coincide con su hash almacenado.
   *
   * @param string $password Contraseña en texto plano
   * @param string $hash Hash almacenado
   * @return bool True si es válida
   */
  public static function verifyPassword(string $password, string $hash): bool
  {
    return password_verify($password, $hash);
  }

  /**
   * Intenta autenticar a un usuario buscando en la base de datos y verificando su contraseña.
   *
   * @param string $identifier Identificador (correo, nombre de usuario, etc.)
   * @param string $password Contraseña en texto plano
   * @param string $table Nombre de la tabla de usuarios (por defecto 'users')
   * @param string $column Nombre de la columna de identificación (por defecto 'email')
   * @param string $passwordColumn Nombre de la columna que almacena el hash de la clave (por defecto 'password')
   * @return bool True si la autenticación fue exitosa
   */
  public static function attempt(
    string $identifier,
    string $password,
    string $table = 'users',
    string $column = 'email',
    string $passwordColumn = 'password'
  ): bool {
    try {
      $builder = new Builder($table);
      $user = $builder->where($column, $identifier)->get_one();

      if (empty($user) || !isset($user[0])) {
        return false;
      }

      $userData = $user[0];
      $storedHash = $userData[$passwordColumn] ?? null;

      if (!$storedHash || !self::verifyPassword($password, (string)$storedHash)) {
        return false;
      }

      // No guardar el hash de la contraseña en la sesión
      unset($userData[$passwordColumn]);

      self::login($userData);
      return true;
    } catch (Exception $e) {
      error_log("AuthModule::attempt error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Inicia sesión manualmente pasando el array con datos del usuario.
   *
   * @param array $userData Datos del usuario
   * @return void
   */
  public static function login(array $userData): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      Session::start();
    }

    // Normalizar ID de usuario para Session
    $userId = $userData['id'] ?? $userData['id_user'] ?? $userData['user_id'] ?? null;
    if ($userId !== null && !isset($userData['user_id'])) {
      $userData['user_id'] = $userId;
    }

    $_SESSION['user'] = $userData;

    // Sincronizar token JWT persistente si TokenModule está configurado
    if (class_exists(TokenModule::class) && !empty($userId)) {
      try {
        $jwt = TokenModule::configJWT(['user_id' => $userId]);
        if ($jwt) {
          CookieModule::set('auth_token', [
            'value'    => $jwt,
            'httponly' => true,
            'secure'   => defined('SSL_SESSION') ? SSL_SESSION : false
          ]);
        }
      } catch (Exception $e) {
        // Token opcional
      }
    }
  }

  /**
   * Inicia sesión para un usuario a partir de su ID en la base de datos.
   *
   * @param int|string $id ID del usuario
   * @param string $table Nombre de la tabla
   * @param string $idColumn Nombre de la columna ID
   * @return bool True si el usuario fue encontrado y logueado
   */
  public static function loginUsingId(int|string $id, string $table = 'users', string $idColumn = 'id'): bool
  {
    try {
      $builder = new Builder($table);
      $user = $builder->where($idColumn, $id)->get_one();

      if (empty($user) || !isset($user[0])) {
        return false;
      }

      $userData = $user[0];
      unset($userData['password'], $userData['pass']);

      self::login($userData);
      return true;
    } catch (Exception $e) {
      error_log("AuthModule::loginUsingId error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Comprueba si hay un usuario actualmente autenticado en la sesión.
   *
   * @return bool True si está autenticado
   */
  public static function check(): bool
  {
    return Session::session_active();
  }

  /**
   * Obtiene todos los datos del usuario autenticado actual.
   *
   * @return array|null Array con los datos del usuario o null si no está logueado
   */
  public static function user(): ?array
  {
    if (!self::check()) {
      return null;
    }
    return $_SESSION['user'] ?? null;
  }

  /**
   * Obtiene el identificador principal (ID) del usuario autenticado.
   *
   * @return int|string|null ID del usuario o null
   */
  public static function id(): int|string|null
  {
    $user = self::user();
    if (!$user) {
      return null;
    }
    return $user['id'] ?? $user['id_user'] ?? $user['user_id'] ?? null;
  }

  /**
   * Cierra la sesión del usuario actual y elimina tokens asociados.
   *
   * @return void
   */
  public static function logout(): void
  {
    Session::clear();
    if (class_exists(CookieModule::class)) {
      CookieModule::delete('auth_token');
    }
  }
}
