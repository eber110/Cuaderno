<?php

namespace App\Components\Menu;

use Base\Module\Session;

class menuHomeComponent
{

  public static function data($view = 'Home.menuHome', $viewType = 'menu', $params = [])
  {

    return [
      "connect" => Session::session_active(),
      "username" => Session::session_data("username")
    ];
  }
}
