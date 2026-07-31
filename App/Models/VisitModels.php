<?php

namespace App\Models;

use Base\Builder\Builder;
use Base\Module\AnalyticsModule;
use Base\Module\MovilDetectorModule;
use Base\Module\VisitModule;
use Exception;

/**
 * Clase VisitModels
 * 
 * Modelo encargado del procesamiento de reglas de negocio relativas a visitas de usuarios,
 * control de frecuencia en sesión, extracción de geolocalización y guardado en SQLite.
 */
class VisitModels extends Builder {

  protected $table = "profile_views";

  /**
   * Procesa y registra la visita a un perfil de usuario respetando la frecuencia de sesión.
   *
   * @param string $visitedUser Nombre del usuario visitado.
   * @return bool True si la visita fue procesada y registrada, false si fue omitida o falló.
   */
  public static function processVisit(string $visitedUser): bool {
    $visitedUserClean = mb_strtolower($visitedUser, "UTF-8");

    // Controlar en sesión la frecuencia de visitas al mismo perfil (1 vez cada 1 hora / 3600s)
    $sessionKey = "visit_registered_" . $visitedUserClean;
    $lastVisitTime = $_SESSION[$sessionKey] ?? 0;

    if ((time() - (int)$lastVisitTime) <= 3600) {
      return false; // Visita omitida por restricción de tiempo en sesión
    }

    try {
      $_SESSION[$sessionKey] = time();

      // Inicializar geolocalización
      VisitModule::initSession("Visit_registration");
      $location = VisitModule::getLocation() ?? [];

      $ip          = $location["ip"] ?? VisitModule::getClientIp() ?? $_SESSION["location"]["ip"] ?? "";
      $countryCode = !empty($location["codigo"]) ? $location["codigo"] : ($_SESSION["location"]["codigo"] ?? "N/A");
      $countryName = !empty($location["pais"]) ? $location["pais"] : ($_SESSION["location"]["pais"] ?? "Desconocido");
      $cityName    = !empty($location["ciudad"]) ? $location["ciudad"] : ($_SESSION["location"]["ciudad"] ?? "Desconocido");
      $deviceType  = MovilDetectorModule::getDeviceType();
      $os          = MovilDetectorModule::getOS();
      $browser     = MovilDetectorModule::getBrowser();
      $referrer    = $_SERVER["HTTP_REFERER"] ?? "";

      // Registrar la visita directamente en la base de datos SQLite (profile_views)
      return AnalyticsModule::logProfileView($visitedUserClean, [
        "ip_address"   => $ip,
        "country_code" => $countryCode,
        "country_name" => $countryName,
        "city_name"    => $cityName,
        "device_type"  => $deviceType,
        "os"           => $os,
        "browser"      => $browser,
        "referrer"     => $referrer
      ]);
    } catch (Exception $e) {
      error_log("Error en VisitModels::processVisit: " . $e->getMessage());
      return false;
    }
  }

}
