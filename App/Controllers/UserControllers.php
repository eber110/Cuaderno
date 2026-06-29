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
      "user" => $user,
      "dataUser" => $userData[0],
      "card" => [
        "header" => "regularHero",
        "backCard" => ["#283380", "gradientDown"],
        "colorText" => "#ffffff",
        "style" => "Regular",
        "borders" => ["br100", "br50"],
        "shadow" => "shadow-1",
        "back" => "#ffffff",
        "hover" => true,
        "color" => "#272727"
      ]// este parámetro se recupera de el modelo DesignPreferenceModels
    ];
    
    return $this->view("User.index", $data);
  
  }

}