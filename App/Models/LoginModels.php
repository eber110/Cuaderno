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

}