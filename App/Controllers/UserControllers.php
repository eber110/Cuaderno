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
      "widget" => ["style" => "Regular", "back" => "#001329", "hover" => true, "color" => "#d6d6d6"]// este parámetro se recupera de el modelo DesignPreferenceModels
    ];
    
    return $this->view("User.index", $data);
  
  }

}