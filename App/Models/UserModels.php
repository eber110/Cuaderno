<?php
  
namespace App\Models;

use Base\Builder\Builder;

class UserModels extends Builder{

  protected $table = "users";

  public function dataUser(string $user): bool|array{
    
    $dataUser = $this->find("username", $user);

    if (!$dataUser) {
      return false;
    }else{
      $dataUser = $dataUser[0];
    }

    $dataUser = [
      "card" => [
        "profile" => DOMAIN.$dataUser["username"],
        "title" => $dataUser["full_name"],
        "desc" => $dataUser["bio"],
        "header" => "regularHero",
        "backCard" => ["#212347", "gradientUp"],
        "colorText" => "#ffffff",
        "style" => "Regular",
        "borders" => ["br10", "br5"],
        "shadow" => "shadow-1",
        "back" => "#ffffff",
        "hover" => true,
        "color" => "#272727",
        "rrss" => [
          ["x","https://x.com/eberestudio"],
          ["Linkedin","https://www.linkedin.com/in/eber-s%C3%A1nchez-cornejo-08b1456a/"]
        ],
        "content" => [
          ["link", "prod.jpg", "Este es mi primer link", "https://www.ebersanchez.cl"],
          ["link", "hero.jpg", "Este es mi segundo link", "https://www.ebersanchez.cl"],
          ["link", "desc.jpg", "Este es mi tercero link", "https://www.ebersanchez.cl"],
          ["link", "hero.jpg", "Este es mi super cuarto link", "https://www.ebersanchez.cl"],
          ["link", "prod.jpg", "Este es mi lindo y especial quinto link", "https://www.ebersanchez.cl"],
        ]
      ]// este parámetro se recupera de el modelo DesignPreferenceModels
    ];

    return $dataUser;
  
  }

}