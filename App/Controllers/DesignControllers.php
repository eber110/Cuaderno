<?php
  
namespace App\Controllers;

use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\LogModule;

class DesignControllers extends Control{

  public static function initialDesign(string $user){

    $user = mb_strtolower($user, 'UTF-8');
    $existsProfile = new UserModels;
    $dataUser = $existsProfile->dataUser($user);
    if (!$dataUser) {
      $data = [
      "card" => [
        "active" => false,//control de activación del perfil, esto lo decide un json el cual tenga todos los datos necesarios para poder visualizar el perfil
        "hide" => false,//control de visualización del perfil. esto lo decide el usuario
        "profile" => $user,
        "avatar" => "Origin/no-user.webp",
        "title" => "Titulo",
        "desc" => "Descripción del usuario",
        "header" => "regularHero",
        "backCard" => [
          "back_perfil" => "#a0a0a0",//color del background del perfil
          "style_back" => "solid"//Tipo de background (solido, gradiente, etc.)
        ],
        "colorText" => "#383838",
        "style" => "Regular",
        "borders" => ["br0", "br0"],
        "shadow" => "shadow-1",
        "back" => "#d6d6d6",
        "hover" => false,
        "color" => "#494949",
        "colorShadow3" => "#000000",
        "rrss" => [],
        "content" => []
        ]
      ];
      
      LogModule::simpleLog([
        "dir" => ROOT_PATH."/Cache/UserData/",
        "name" => "{$user}",
        "content" => $data
      ]);
    }
  }

  public function configDesign(){
    
  
  
  }

}