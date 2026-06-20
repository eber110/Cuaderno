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
   * @return array|false Datos del usuario si existe, o false.
   */
  public function checkUserExists(string $username) {
    return $this->rate(10, 300)->where("username", $username)->get_one();
  }

  /**
   * Verifica si un correo electrónico ya está registrado.
   * 
   * @param string $email Correo a comprobar.
   * @return array|false Datos del usuario si existe, o false.
   */
  public function checkEmailExists(string $email) {
    return $this->where("email", $email)->get_one();
  }

}