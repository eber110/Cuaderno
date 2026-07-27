<?php
  
namespace App\Controllers\Dashboard;

use App\Controllers\DesignControllers;
use App\Controllers\UserControllers;
use App\Models\DesignModels;
use Base\Control\Control;
use Base\Module\LogModule;
use Base\Module\ResponseModule;
use Base\Module\Session;

class DashboardControllers extends Control{

  public function panel(string $user){

    $user = mb_strtolower($user, 'UTF-8');
    $dataUser = DesignModels::dataUser($user);
    $hasCustom = DesignModels::hasCustomDesign($user);

    if ($dataUser && isset($dataUser["card"])) {
      $cardData = UserControllers::formatCardImages($dataUser["card"]);
    } else {
      $cardData = [];
    }

    $data = [
      "card" => $cardData,
      "hasCustom" => $hasCustom,
      "uri" => [
        "formDesign" => "/panel/{$user}/diseno",
        "saveDesign" => "/panel/{$user}/guardar"
      ],
      "session" => $_SESSION["user"] ?? false
    ];

    return $this->view("Dashboard.Panel.panel", $data);
  
  }

}