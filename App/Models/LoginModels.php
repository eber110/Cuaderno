<?php
  
namespace App\Models;

use Base\Builder\Builder;

class LoginModels extends Builder{

  protected $table = "users";

  public function loginApp(string $userName, string $pass) : bool | array{

    $login = $this->login(
      "password_hash",
      $pass,
      $userName,
      [
        "username",
        "email"
      ]
    );

    return $login;
  
  }

}