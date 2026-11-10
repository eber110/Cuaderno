<?php

namespace App\Components\Share;

use App\Models\StatisticsModels;
use App\Models\UserModels;
use Base\Module\Session;

/**
 * Clase shareOwnerStatsComponent
 *
 * Componente autocontenido que alimenta el pie de los modales de compartir.
 * Si el visitante es el dueño del perfil con sesión activa, entrega el saludo y las
 * métricas del enlace (o del perfil); en caso contrario, la vista muestra la invitación a registrarse.
 */
class shareOwnerStatsComponent
{
  /**
   * Datos del componente.
   *
   * @param string $view Ruta de la vista a renderizar.
   * @param string $viewType Tipo: part|form|menu|template.
   * @param array $params Parámetros: 'profile' (usuario dueño), 'url' (enlace) y 'type' ('link'|'profile').
   * @return array Datos para la vista.
   */
  public static function data($view = "User.shareOwnerStats", $viewType = "part", $params = [])
  {
    $profile = mb_strtolower(trim((string)($params["profile"] ?? "")), "UTF-8");
    $url     = trim((string)($params["url"] ?? ""));
    $type    = ($params["type"] ?? "link") === "profile" ? "profile" : "link";

    $sessionUser = Session::session_active() ? (string)(Session::session_data("username") ?? "") : "";
    $isOwner     = $sessionUser !== "" && $profile !== "" && mb_strtolower($sessionUser, "UTF-8") === $profile;

    $stats = ["total" => 0, "week" => 0];

    if ($isOwner) {
      $indexUser = (string)(Session::session_data("index_user") ?? "");
      if ($indexUser === "") {
        $indexUser = (string)(UserModels::getIndexUserByUsername($profile) ?? "");
      }

      $stats = $type === "profile"
        ? StatisticsModels::getProfileViewsSummary($indexUser)
        : StatisticsModels::getLinkClicksSummary($indexUser, $url);
    }

    return [
      "isOwner"     => $isOwner,
      "sessionUser" => $sessionUser,
      "profileName" => $profile,
      "statsType"   => $type,
      "statsTotal"  => (int)($stats["total"] ?? 0),
      "statsWeek"   => (int)($stats["week"] ?? 0),
    ];
  }
}
