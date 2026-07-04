<?php
  
namespace App\Controllers\Dashboard;

use Base\Control\Control;
use Base\Module\LogModule;
use Base\Module\ResponseModule;
use Base\Module\Session;

class DashboardControllers extends Control{

  public function panel(string $user){

    $dataUser = LogModule::readLogLines("/Cache/UserData/{$user}.json");

    if ($dataUser) {
      $dataUser = $dataUser[0]["card"];
    }

    $data = [
      "user" => $dataUser,
      "session" => $_SESSION ?? null
    ];

    return $this->view("Dashboard.Panel.panel", $data);
  
  }

}