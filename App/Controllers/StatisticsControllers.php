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

    // Redirigir al panel del usuario
    ResponseModule::redirect("/panel/{$userClean}");
  }

}