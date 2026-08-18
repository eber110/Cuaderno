<?php

namespace App\Controllers;

use App\Models\StatisticsModels;
use Base\Control\Control;
use Base\Module\ResponseModule;

/**
 * Clase StatisticsControllers
 * 
 * Controlador encargado únicamente de la orquestación HTTP de peticiones de estadísticas.
 * Delega toda la obtención y procesamiento de datos al modelo StatisticsModels.
 */
class StatisticsControllers extends Control {

  /**
   * Obtiene los datos analíticos de un usuario delegando al modelo.
   *
   * @param string $user Nombre de usuario.
   * @return array Datos de analíticas procesados.
   */
  public static function getStatsData(string $user): array {
    return StatisticsModels::getStatsData($user);
  }

  /**
   * Endpoint para simular e insertar datos de prueba en la base de datos de analíticas.
   *
   * @param string $user Nombre de usuario objetivo.
   * @return void Redirige de vuelta al panel de administración.
   */
  public function generateTestData(string $user): void {
    $userClean = mb_strtolower($user, "UTF-8");
    
    // Delegar procesamiento e inserción de datos de prueba al modelo
    StatisticsModels::generateTestData($userClean);

    // Detectar solicitudes AJAX / Fetch
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
      || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    if ($isAjax) {
      $stats    = StatisticsModels::getStatsData($userClean);
      $dataUser = \App\Models\DesignModels::dataUser($userClean);
      $cardData = (isset($dataUser["card"]) && is_array($dataUser["card"])) 
        ? \App\Models\UserModels::formatCardImages($dataUser["card"]) 
        : [];

      $uri = [
        "formDesign"    => "/panel/{$userClean}/diseno",
        "saveDesign"    => "/panel/{$userClean}/guardar",
        "discardDesign" => "/panel/{$userClean}/descartar",
        "simularDatos"  => "/panel/{$userClean}/simular-datos"
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

    // Redirigir al panel del usuario
    ResponseModule::redirect("/panel/{$userClean}");
  }

}