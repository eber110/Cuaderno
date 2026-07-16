<?php
  
namespace App\Controllers\Dashboard;

use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\LogModule;
use Base\Module\ResponseModule;
use Base\Module\Session;

class DashboardControllers extends Control{

  public function panel(string $user){

    $dataUser = new UserModels;
    $dataUser = $dataUser->dataUser($user);

    //en el caso de que no este creado ekl profile del usuario, se creara uno inicial, para que el usuario lo rellene
    if (!$dataUser) {
      $data = [
      "card" => [
        "active" => false,//control de activación del perfil, esto lo decide un json el cual tenga todos los datos necesarios para poder visualizar el perfil
        "hide" => false,//control de visualización del perfil. esto lo decide el usuario
        "profile" => $user,
        "avatar" => "no-user.webp",
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
        "rrss" => [],
        "content" => []
        ]
      ];
      
      LogModule::simpleLog([
        "dir" => "/Cache/UserData/",
        "name" => "{$user}",
        "content" => $data
      ]);
    }

    if ($dataUser) {
      $dataUser = $dataUser["card"];
    }

    $data = [
      "card" => $dataUser,
      "session" => $_SESSION["user"] ?? false
    ];

    return $this->view("Dashboard.Panel.panel", $data);
  
  }

}