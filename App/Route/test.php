<?php

use Base\Module\LogModule;
use Base\Module\ResponseModule;
use Core\Route;

Route::get("/test/1", function(){

  LogModule::deleteLog("/Cache/UserData/eber.json");

  $data = [
    "card" => [
      "profile" => "eber",
      "avatar" => "porfolio_eber_dark.webp",
      "title" => "Esc",
      "desc" => "Llevo tus ideas al código sin intermediarios. Especialista en PHP y JavaScript puro, enfocado en diseñar sistemas estables, veloces y preparados para escalar al ritmo de tu proyecto.",
      "header" => "regularHero",
      "backCard" => [
        "#212347",
        "gradientUp"
      ],
      "colorText" => "#ffffff",
      "style" => "Regular",
      "borders" => [
        "br10",
        "br5"
      ],
      "shadow" => "shadow-1",
      "back" => "#ffffff",
      "hover" => true,
      "color" => "#272727",
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
    "name" => "eber",
    "content" => $data
  ]);

  ResponseModule::redirect("/panel/eber");

});