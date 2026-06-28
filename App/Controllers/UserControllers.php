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
        "backCard" => ["#b12929", "gradientUp"],
        "colorText" => "#ffffff",
        "style" => "Regular",
        "borders" => ["br10", "br5"],
        "shadow" => "shadow-3",
        "back" => "#ff7676",
        "hover" => true,
        "color" => "#ffffff"
      ]// este parámetro se recupera de el modelo DesignPreferenceModels
    ];
    
    return $this->view("User.index", $data);
  
  }

}