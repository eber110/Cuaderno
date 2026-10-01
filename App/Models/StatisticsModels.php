<?php

namespace App\Models;

use Base\Builder\Builder;

/**
 * Clase StatisticsModels
 * 
 * Modelo encargado de la consulta y procesamiento de estadísticas de perfil.
 * Preparado para la nueva arquitectura y reglas de negocio.
 */
class StatisticsModels extends Builder {

  protected $table = "profile_views";

  /**
   * Obtiene la estructura base de datos estadísticos para un perfil.
   *
   * @param string $user Nombre de usuario del perfil.
   * @return array Resumen base de estadísticas del perfil.
   */
  public static function getStatsData(string $user): array {
    $userClean = mb_strtolower($user, "UTF-8");

    return [
      "user"    => $userClean,
      "summary" => [
        "total_views"  => 0,
        "unique_views" => 0,
        "total_clicks" => 0
      ]
    ];
  }

}
