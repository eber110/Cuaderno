<?php

namespace App\Controllers;

use App\Models\DesignModels;
use App\Models\StatisticsModels;
use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\ResponseModule;

/**
 * Clase StatisticsControllers
 * 
 * Controlador encargado de la orquestación HTTP de peticiones de estadísticas del panel.
 * Delega la obtención y procesamiento de datos al modelo StatisticsModels.
 */
class StatisticsControllers extends Control {

  /**
   * Obtiene los datos analíticos de un usuario delegando al modelo.
   *
   * @param string $user Nombre de usuario.
   * @return array Datos de estadísticas procesados.
   */
  public static function getStatsData(string $user): array {
    return StatisticsModels::getStatsData($user);
  }

  /**
   * Carga bajo demanda la vista de estadísticas vía AJAX / Fetch para el panel.
   *
   * @param string $user Nombre de usuario.
   * @return void
   */
  public function loadStatsHtml(string $user): void {
    $userClean = mb_strtolower($user, "UTF-8");
    $stats     = StatisticsModels::getStatsData($userClean);
    $dataUser  = DesignModels::dataUser($userClean);
    $cardData  = (isset($dataUser["card"]) && is_array($dataUser["card"])) 
      ? UserModels::formatCardImages($dataUser["card"]) 
      : [];

    $uri = [
      "formDesign"    => "/panel/{$userClean}/diseno",
      "saveDesign"    => "/panel/{$userClean}/guardar",
      "discardDesign" => "/panel/{$userClean}/descartar",
      "estadisticas"  => "/panel/{$userClean}/estadisticas"
    ];

    $statsHtml = _partToString("Dashboard.statisticsPanel", [
      "stats" => $stats,
      "card"  => $cardData,
      "user"  => $userClean,
      "uri"   => $uri
    ]);

    ResponseModule::json([
      "success"   => true,
      "statsHtml" => $statsHtml
    ]);
  }

  /**
   * Endpoint de compatibilidad temporal para redireccionar peticiones de datos de prueba obsoletas.
   *
   * @param string $user Nombre de usuario objetivo.
   * @return void
   */
  public function generateTestData(string $user): void {
    $userClean = mb_strtolower($user, "UTF-8");
    ResponseModule::redirect("/panel/{$userClean}");
  }

}