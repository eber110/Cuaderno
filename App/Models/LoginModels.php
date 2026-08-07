<?php
  
namespace App\Models;

use Base\Builder\Builder;

class LoginModels extends Builder{

  protected $table = "users";

  public function loginApp(string $userName, string $pass) : bool | array{

    $login = $this->rate(5, 60)->login(
      "password_hash",
      $pass,
      $userName,
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

    return $this->where("username", $username)->get_one();
  }

  /**
   * Verifica si un correo electrónico ya está registrado, aplicando un límite de tasa (Rate Limit).
   * Permite 10 intentos por IP y bloquea por 5 minutos (300 segundos).
   * 
   * @param string $email Correo a comprobar.
   * @return array|false Datos del usuario si existe, false si no existe, o array con clave rate_limited si está bloqueado.
   */
  public function checkEmailExists(string $email): array|false {
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

    return $this->where("email", $email)->get_one();
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

}