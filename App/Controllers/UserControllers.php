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
        "title" => "Esc",
        "desc" => "Llevo tus ideas al código sin intermediarios. Especialista en PHP y JavaScript puro, enfocado en diseñar sistemas estables, veloces y preparados para escalar al ritmo de tu proyecto.",
        "backCard" => ["#212347", "gradientUp"],
        "colorText" => "#ffffff",
        "style" => "Regular",
        "borders" => ["br10", "br5"],
        "shadow" => "shadow-1",
        "back" => "#ffffff",
        "hover" => true,
        "color" => "#272727",
        "rrss" => [
          [svg("x"),"x","https://x.com/eberestudio"],
          [svg("linkedin"),"Linkedin","https://www.linkedin.com/in/eber-s%C3%A1nchez-cornejo-08b1456a/"]
        ]
      ]// este parámetro se recupera de el modelo DesignPreferenceModels
    ];
    
    return $this->view("User.index", $data);
  
  }

}