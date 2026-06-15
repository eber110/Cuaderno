<?php
  
namespace App\Controllers;

use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\ResponseModule;
use Base\Module\Session;

class UserControllers extends Control{

  public function userPage(string $user){
  
    $userData = new UserModels;
    $userData = $userData->userExists($user);

    if (!$userData) {
      return ResponseModule::redirect("/", "El usuario {$user}, no existe!", 2);
    }

    $data = [
      "user" => "Hola, {$user}",
      "dataUser" => $userData[0],
      "connect" => Session::session_active()
      ];
    return $this->view("User.index", $data);
  
  }

}