<?php

namespace App\Controllers\Dashboard;

use App\Models\DesignModels;
use App\Models\StatisticsModels;
use App\Models\UserModels;
use Base\Control\Control;

/**
 * Clase DashboardControllers
 * 
 * Controlador encargado de la orquestación de peticiones HTTP para el panel de administración.
 * Invoca a los modelos DesignModels, UserModels y StatisticsModels de forma limpia y desasociada.
 */
class DashboardControllers extends Control {

  /**
   * Carga y renderiza el panel de administración principal del usuario.
   *
   * @param string $user Nombre de usuario.
   * @return mixed Renderizado de la vista Dashboard.Panel.panel.
   */
  public function panel(string $user) {
    $userClean  = mb_strtolower($user, "UTF-8");
    $dataUser   = DesignModels::dataUser($userClean);
    $hasCustom  = DesignModels::hasCustomDesign($userClean);

    if ($dataUser && isset($dataUser["card"])) {
      // Formatear imágenes a través de UserModels
      $cardData = UserModels::formatCardImages($dataUser["card"]);
    } else {
      $cardData = [];
    }

    // Consultar métricas a través de StatisticsModels
    $stats = StatisticsModels::getStatsData($userClean);

    $data = [
      "card"      => $cardData,
      "stats"     => $stats,
      "hasCustom" => $hasCustom,
      "uri"       => [
        "formDesign"   => "/panel/{$userClean}/diseno",
        "saveDesign"   => "/panel/{$userClean}/guardar",
        "simularDatos" => "/panel/{$userClean}/simular-datos"
      ],
      "session"   => $_SESSION["user"] ?? false
    ];

    return $this->view("Dashboard.Panel.panel", $data);
  }

}