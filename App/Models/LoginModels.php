<?php
  
namespace App\Models;

use Base\Builder\BuilderSqlite;

class LoginModels extends BuilderSqlite{

  protected $table = "users";

  public function loginApp(string $userName, string $pass) : bool | array{
    $userNameClean = mb_strtolower(trim($userName), "UTF-8");

    $login = $this->rate(5, 60)->login(
      "password_hash",
      $pass,
      $userNameClean,
      [
        "username",
        "email"
      ],
      [
        "password_hash",
        "email_verification_token",
        "email_verification_token_expires_at",
        "password_reset_token",
        "password_reset_token_expires_at",
        "two_factor_secret"
      ]
    );

    return $login;
  
  }

  /**
   * Verifica si un nombre de usuario existe, aplicando un límite de tasa (Rate Limit).
   * Permite 10 intentos por IP y bloquea por 5 minutos (300 segundos).
   * 
   * @param string $username Nombre de usuario a comprobar.
   * @return array|false Datos del usuario si existe, false si no existe, o array con clave rate_limited si está bloqueado.
   */
  public function checkUserExists(string $username): array|false {
    $usernameClean = mb_strtolower(trim($username), "UTF-8");
    $this->rate(10, 300);
    $actionKey = 'register:check_username';

    $rateStatus = $this->checkRateLimit($actionKey);
    if ($rateStatus !== true) {
      $this->reset();
      return [
        "rate_limited" => true,
        "seconds"      => (int)$rateStatus
      ];
    }

    $this->incrementRateLimit($actionKey);
    $this->rateLimitMaxAttempts = null;

    return $this->where("username", $usernameClean)->get_one();
  }

  /**
   * Verifica si un correo electrónico ya está registrado, aplicando un límite de tasa (Rate Limit).
   * Permite 10 intentos por IP y bloquea por 5 minutos (300 segundos).
   * 
   * @param string $email Correo a comprobar.
   * @return array|false Datos del usuario si existe, false si no existe, o array con clave rate_limited si está bloqueado.
   */
  public function checkEmailExists(string $email): array|false {
    $emailClean = mb_strtolower(trim($email), "UTF-8");
    $this->rate(10, 300);
    $actionKey = 'register:check_email';

    $rateStatus = $this->checkRateLimit($actionKey);
    if ($rateStatus !== true) {
      $this->reset();
      return [
        "rate_limited" => true,
        "seconds"      => (int)$rateStatus
      ];
    }

    $this->incrementRateLimit($actionKey);
    $this->rateLimitMaxAttempts = null;

    return $this->where("email", $emailClean)->get_one();
  }

  /**
   * Verifica sin aplicar rate limit si un nombre de usuario ya existe en base de datos.
   *
   * @param string $username Nombre de usuario a consultar.
   * @return bool True si ya existe, false en caso contrario.
   */
  public function existsByUsername(string $username): bool {
    $this->rateLimitMaxAttempts = null;
    $clean = mb_strtolower(trim($username), "UTF-8");
    return (bool)$this->where("username", $clean)->get_one();
  }

  /**
   * Verifica sin aplicar rate limit si un correo electrónico ya existe en base de datos.
   *
   * @param string $email Correo electrónico a consultar.
   * @return bool True si ya existe, false en caso contrario.
   */
  public function existsByEmail(string $email): bool {
    $this->rateLimitMaxAttempts = null;
    $clean = mb_strtolower(trim($email), "UTF-8");
    return (bool)$this->where("email", $clean)->get_one();
  }

  /**
   * Comprueba y aplica el límite de tasa para el envío final de registro por IP.
   * Permite 10 intentos por IP y bloquea por 5 minutos (300 segundos).
   *
   * @param int $attempts Cantidad máxima de intentos.
   * @param int $seconds Segundos de bloqueo.
   * @return true|int True si está permitido, o número de segundos restantes de bloqueo.
   */
  public function checkSubmitRegisterRate(int $attempts = 10, int $seconds = 300): true|int {
    $this->rate($attempts, $seconds);
    $actionKey = 'register:submit';

    $rateStatus = $this->checkRateLimit($actionKey);
    if ($rateStatus !== true) {
      $this->reset();
      return (int)$rateStatus;
    }

    $this->incrementRateLimit($actionKey);
    $this->rateLimitMaxAttempts = null;
    return true;
  }

  /**
   * Limpia los límites de tasa relacionados con el proceso de registro para la IP actual.
   *
   * @param string|null $ip Dirección IP opcional (por defecto la actual).
   * @return void
   */
  public function clearRegistrationRateLimits(?string $ip = null): void {
    $this->clearRateLimit('register:check_username', $ip);
    $this->clearRateLimit('register:check_email', $ip);
    $this->clearRateLimit('register:submit', $ip);
  }

  /**
   * Obtiene los datos de un usuario por correo electrónico normalizado.
   *
   * @param string $email Correo electrónico del usuario.
   * @return array|false Registro del usuario o false si no existe.
   */
  public function findUserByEmail(string $email): array|false {
    $this->rateLimitMaxAttempts = null;
    $clean = mb_strtolower(trim($email), "UTF-8");
    $rows  = $this->where("email", $clean)->get_one();
    return !empty($rows[0]) ? $rows[0] : false;
  }

  /**
   * Obtiene los datos de un usuario por nombre de usuario normalizado.
   *
   * @param string $username Nombre de usuario.
   * @return array|false Registro del usuario o false si no existe.
   */
  public function findUserByUsername(string $username): array|false {
    $this->rateLimitMaxAttempts = null;
    $clean = mb_strtolower(trim($username), "UTF-8");
    $rows  = $this->where("username", $clean)->get_one();
    return !empty($rows[0]) ? $rows[0] : false;
  }

  /**
   * Guarda el hash del token de verificación de correo y su fecha de expiración.
   *
   * @param int|string $userId ID numérico del usuario.
   * @param string $tokenHash Hash SHA-256 del token.
   * @param string $expiresAt Fecha de expiración en formato Y-m-d H:i:s.
   * @return void
   */
  public function saveEmailVerificationToken(int|string $userId, string $tokenHash, string $expiresAt): void {
    $this->rateLimitMaxAttempts = null;
    $this->update("user_id", $userId, [
      "email_verification_token"            => $tokenHash,
      "email_verification_token_expires_at" => $expiresAt,
      "updated_at_user"                     => date("Y-m-d H:i:s")
    ]);
  }

  /**
   * Busca un usuario por el hash SHA-256 de su token de verificación de correo.
   *
   * @param string $tokenHash Hash SHA-256 del token.
   * @return array|false Registro del usuario o false si no existe.
   */
  public function findByVerificationTokenHash(string $tokenHash): array|false {
    $this->rateLimitMaxAttempts = null;
    $rows = $this->where("email_verification_token", $tokenHash)->get_one();
    return !empty($rows[0]) ? $rows[0] : false;
  }

  /**
   * Activa la cuenta del usuario y elimina el token de verificación (uso único).
   *
   * @param int|string $userId ID numérico del usuario.
   * @return void
   */
  public function activateVerifiedUser(int|string $userId): void {
    $this->rateLimitMaxAttempts = null;
    $this->update("user_id", $userId, [
      "user_status"                         => "active",
      "email_verification_token"            => null,
      "email_verification_token_expires_at" => null,
      "updated_at_user"                     => date("Y-m-d H:i:s")
    ]);
  }

  /**
   * Verifica el límite de tasa para reenvío de verificación (cooldown 60s y máximo 3 por hora).
   *
   * @param string $email Correo electrónico normalizado.
   * @return true|int True si está permitido, o segundos restantes de bloqueo.
   */
  public function checkResendVerificationRate(string $email): true|int {
    $emailHash   = hash("sha256", mb_strtolower(trim($email), "UTF-8"));
    $cooldownKey = "verify:cooldown:" . $emailHash;
    $hourlyKey   = "verify:resend:" . $emailHash;

    $this->rate(1, 60);
    $cooldownStatus = $this->checkRateLimit($cooldownKey);
    if ($cooldownStatus !== true) {
      $this->reset();
      $this->rateLimitMaxAttempts = null;
      return (int)$cooldownStatus;
    }

    $this->rate(3, 3600);
    $hourlyStatus = $this->checkRateLimit($hourlyKey);
    if ($hourlyStatus !== true) {
      $this->reset();
      $this->rateLimitMaxAttempts = null;
      return (int)$hourlyStatus;
    }

    $this->rate(1, 60);
    $this->incrementRateLimit($cooldownKey);
    $this->rate(3, 3600);
    $this->incrementRateLimit($hourlyKey);
    $this->rateLimitMaxAttempts = null;

    return true;
  }

  /**
   * Verifica el límite de tasa para solicitudes de recuperación de contraseña (IP + email).
   *
   * @param string $email Correo electrónico normalizado.
   * @return true|int True si está permitido, o segundos restantes de bloqueo.
   */
  public function checkPasswordResetRequestRate(string $email): true|int {
    $emailHash = hash("sha256", mb_strtolower(trim($email), "UTF-8"));
    $ipKey     = "reset:request_ip";
    $emailKey  = "reset:request_email:" . $emailHash;

    $this->rate(5, 900);
    $ipStatus = $this->checkRateLimit($ipKey);
    if ($ipStatus !== true) {
      $this->reset();
      $this->rateLimitMaxAttempts = null;
      return (int)$ipStatus;
    }

    $this->rate(3, 900);
    $emailStatus = $this->checkRateLimit($emailKey);
    if ($emailStatus !== true) {
      $this->reset();
      $this->rateLimitMaxAttempts = null;
      return (int)$emailStatus;
    }

    $this->rate(5, 900);
    $this->incrementRateLimit($ipKey);
    $this->rate(3, 900);
    $this->incrementRateLimit($emailKey);
    $this->rateLimitMaxAttempts = null;

    return true;
  }

  /**
   * Guarda el hash del token de recuperación de contraseña y su expiración.
   *
   * @param int|string $userId ID numérico del usuario.
   * @param string $tokenHash Hash SHA-256 del token.
   * @param string $expiresAt Fecha de expiración en formato Y-m-d H:i:s.
   * @return void
   */
  public function savePasswordResetToken(int|string $userId, string $tokenHash, string $expiresAt): void {
    $this->rateLimitMaxAttempts = null;
    $this->update("user_id", $userId, [
      "password_reset_token"            => $tokenHash,
      "password_reset_token_expires_at" => $expiresAt,
      "updated_at_user"                 => date("Y-m-d H:i:s")
    ]);
  }

  /**
   * Busca un usuario por el hash SHA-256 de su token de recuperación de contraseña.
   *
   * @param string $tokenHash Hash SHA-256 del token.
   * @return array|false Registro del usuario o false si no existe.
   */
  public function findByPasswordResetTokenHash(string $tokenHash): array|false {
    $this->rateLimitMaxAttempts = null;
    $rows = $this->where("password_reset_token", $tokenHash)->get_one();
    return !empty($rows[0]) ? $rows[0] : false;
  }

  /**
   * Actualiza la contraseña del usuario, invalida el token de recuperación y limpia bloqueos de login.
   *
   * @param int|string $userId ID numérico del usuario.
   * @param string $passwordHash Nuevo hash de contraseña.
   * @param string|null $username Nombre de usuario para limpiar su rate limit de login.
   * @param string|null $email Correo electrónico para limpiar su rate limit de login.
   * @return void
   */
  public function updatePasswordAndClearResetToken(
    int|string $userId,
    string $passwordHash,
    ?string $username = null,
    ?string $email = null
  ): void {
    $this->rateLimitMaxAttempts = null;
    $this->update("user_id", $userId, [
      "password_hash"                   => $passwordHash,
      "password_reset_token"            => null,
      "password_reset_token_expires_at" => null,
      "updated_at_user"                 => date("Y-m-d H:i:s")
    ]);

    if (!empty($username)) {
      $this->clearRateLimit("login:" . mb_strtolower(trim($username), "UTF-8"));
    }
    if (!empty($email)) {
      $this->clearRateLimit("login:" . mb_strtolower(trim($email), "UTF-8"));
    }
  }

  /**
   * Elimina cuentas pendientes de verificación que superaron las horas permitidas.
   *
   * @param int $hoursOld Antigüedad mínima en horas para depurar.
   * @return int Cantidad de cuentas eliminadas.
   */
  public function purgeExpiredPendingUsers(int $hoursOld = 24): int {
    return \App\DatabaseComponent\DatabaseMaintenance::purgeExpiredPendingUsers(null, $hoursOld);
  }

}