<?php
  
namespace App\Controllers\Dashboard;

use App\Controllers\DesignControllers;
use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\LogModule;
use Base\Module\ResponseModule;
use Base\Module\Session;

class DashboardControllers extends Control{

  public function panel(string $user){

    $user = mb_strtolower($user, 'UTF-8');
    $dataUser = new UserModels;
    $dataUser = $dataUser->dataUser($user);

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