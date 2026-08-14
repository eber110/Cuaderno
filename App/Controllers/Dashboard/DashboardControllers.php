<?php

namespace App\Controllers\Dashboard;

use App\Models\DesignModels;
use App\Models\LemonSqueezyModels;
use App\Models\StatisticsModels;
use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\Session;

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
    DesignModels::createInitialDesign($userClean);

    $dataUser   = DesignModels::dataUser($userClean);
    $hasCustom  = DesignModels::hasCustomDesign($userClean);

    if ($dataUser && isset($dataUser["card"])) {
      // Formatear imágenes a través de UserModels
      $cardData = UserModels::formatCardImages($dataUser["card"]);
    } else {
      $cardData = UserModels::formatCardImages(DesignModels::getDefaultCard($userClean));
    }

    // Asegurar que el perfil esté configurado en cardData
    if (empty($cardData["profile"])) {
      $cardData["profile"] = $userClean;
    }

    // Consultar métricas a través de StatisticsModels
    $stats = StatisticsModels::getStatsData($userClean);

    $data = [
      "user"      => $userClean,
      "card"      => $cardData,
      "stats"     => $stats,
      "hasCustom" => $hasCustom,
      "uri"       => [
        "formDesign"    => "/panel/{$userClean}/diseno",
        "saveDesign"    => "/panel/{$userClean}/guardar",
        "discardDesign" => "/panel/{$userClean}/descartar",
        "simularDatos"  => "/panel/{$userClean}/simular-datos"
      ],
      "session" => $_SESSION["user"] ?? false,
      "premium" => LemonSqueezyModels::isUserSubscribedFast(Session::session_data("user_id"))
    ];


    return $this->view("Dashboard.Panel.panel", $data);
  }

}