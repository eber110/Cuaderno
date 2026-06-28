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
        "backCard" => ["#e2e2e2", "solid"],
        "colorText" => "#000000",
        "style" => "Regular",
        "borders" => ["br10", "br5"],
        "shadow" => "shadow-3",
        "back" => "#e2e2e2",
        "hover" => true,
        "color" => "#e00808"
      ]// este parámetro se recupera de el modelo DesignPreferenceModels
    ];
    
    return $this->view("User.index", $data);
  
  }

}