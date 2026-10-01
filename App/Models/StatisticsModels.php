<?php

namespace App\Models;

use Base\Builder\Builder;
use Base\Module\AnalyticsModule;

/**
 * Clase StatisticsModels
 * 
 * Modelo encargado de la consulta y procesamiento de estadísticas de perfil.
 * Conecta directamente con AnalyticsModule para suministrar métricas reales.
 */
class StatisticsModels extends Builder {

  protected $table = "profile_views";

  /**
   * Obtiene la estructura de datos estadísticos para un perfil.
   *
   * @param string $user Nombre de usuario del perfil.
   * @return array Resumen de estadísticas del perfil.
   */
  public static function getStatsData(string $user): array {
    $userClean = mb_strtolower($user, "UTF-8");
    $summary   = AnalyticsModule::getProfileSummary($userClean);

    return [
      "user"    => $userClean,
      "summary" => $summary
    ];
  }

}
