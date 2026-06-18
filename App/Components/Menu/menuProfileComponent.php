<?php

namespace App\Components\Menu;

use Base\Module\Session;

class menuProfileComponent
{

  public static function data($view = "User.menuUser", $viewType = 'menu', $params = [])
  {

    $requestUri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
    $user = trim($requestUri, "/");
    $connect = false;

    if (Session::session_active() && Session::session_data("username") == $user) {
      $connect = true;
    }

    return [
      "connect" => $connect,
      "username" => Session::session_data("username")
    ];
  }
}