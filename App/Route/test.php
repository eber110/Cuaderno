<?php

use Base\Module\LogModule;
use Base\Module\ResponseModule;
use Base\Module\Session;
use Core\Route;

Route::get("/test/2", function(){

  
  $data = LogModule::readLogLines("/Cache/UserData/tomi.json");
  if (!$data) {
    $data = false;
  }

  var_dump($data[0]["card"]);

});

Route::post("/test/1/", function($param){

  $user = Session::session_data("username");

  $dataRequest = LogModule::readLogLines("/Cache/UserData/{$user}.json");
  $dataRequest = $dataRequest[0]["card"];
  LogModule::deleteLog("/Cache/UserData/{$user}.json");

  extract($param);


  if (isset($param["borders"])) {
    $dataRequest["borders"] = explode(",",$borders);
  }

  $data = [
    "card" => [
      "active" => true ?? $dataRequest["active"],//control de activación del perfil, esto lo decide un json el cual tenga todos los datos necesarios para poder visualizar el perfil
      "hide" => false ?? $dataRequest["hide"],//control de visualización del perfil. esto lo decide el usuario
      "profile" => $profile ?? $dataRequest["profile"],
      "avatar" => $avatar ?? $dataRequest["avatar"],
      "title" => $title ?? $dataRequest["title"],
      "desc" => $desc ?? $dataRequest["desc"],
      "header" => $header ?? $dataRequest["header"],
      "backCard" => [
        "back_perfil" => $back_perfil ?? $dataRequest["backCard"]["back_perfil"],//color del background del perfil
        "style_back" => $style_back ?? $dataRequest["backCard"]["style_back"]//Tipo de background (solido, gradiente, etc.)
      ],
      "colorText" => $colorText ?? $dataRequest["colorText"],
      "style" => $style ?? $dataRequest["style"],
      "borders" => $dataRequest["borders"],
      "shadow" => $shadow ?? $dataRequest["shadow"],
      "back" => $back ?? $dataRequest["back"],
      "hover" => true ?? $dataRequest["hover"],
      "color" => $color ?? $dataRequest["color"],
      "rrss" => [
        [
          "x",
          "https://x.com/eberestudio"
        ],
        [
          "Linkedin",
          "https://www.linkedin.com/in/eber-s%C3%A1nchez-cornejo-08b1456a/"
        ]
      ],
      "content" => [
        [
          "link",
          "prod.webp",
          "Este es mi primer link",
          "https://www.ebersanchez.cl"
        ],
        [
          "link",
          "hero.webp",
          "Este es mi segundo link",
          "https://www.ebersanchez.cl"
        ],
        [
          "link",
          "desc.webp",
          "Este es mi tercero link",
          "https://www.ebersanchez.cl"
        ],
        [
          "link",
          "hero.webp",
          "Este es mi super cuarto link",
          "https://www.ebersanchez.cl"
        ],
        [
          "link",
          "prod.webp",
          "Este es mi lindo y especial quinto link",
          "https://www.ebersanchez.cl"
        ]
      ]
    ]
  ];

  LogModule::simpleLog([
    "dir" => "/Cache/UserData/",
    "name" => "{$user}",
    "content" => $data
  ]);

  $user = mb_strtolower($user, 'UTF-8');

  ResponseModule::redirect("/panel/{$user}"); 

});

Route::post("/test/3", function($param){

  extract($param);
  $user = Session::session_data("username");
  $data = LogModule::readLogLines("/Cache/UserData/{$user}.json");

  if (isset($param["borders"])) {
    # code...
    $data[0]["card"]["borders"] = explode(",",$borders);
  }

  var_dump($user);
  var_dump($param);
  var_dump($data[0]["card"]);
});