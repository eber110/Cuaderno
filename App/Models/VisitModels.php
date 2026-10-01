<?php

namespace App\Models;

use Base\Builder\Builder;
use Base\Module\AnalyticsModule;
use Base\Module\BotDetectorModule;
use Base\Module\GeoIpModule;
use Base\Module\MovilDetectorModule;
use Base\Module\Session;
use Exception;

/**
 * Clase VisitModels
 * 
 * Modelo encargado del procesamiento de reglas de negocio relativas a visitas y clics,
 * filtrado estricto de bots y rastreadores, deduplicación por IP en base de datos y sesión (24h),
 * y extracción de geolocalización exclusivamente mediante MaxMind MMDB local (GeoIpModule).
 */
class VisitModels extends Builder {

  protected $table = "profile_views";

  /**
   * Obtiene la dirección IP del cliente a partir de cabeceras de proxy confiables o REMOTE_ADDR.
   *
   * @return string Dirección IP resuelta.
   */
  public static function getClientIp(): string {
    $remoteAddr = \Base\Module\VisitModule::getClientIp() ?? "127.0.0.1";

    $trustedProxies = defined('TRUSTED_PROXIES') ? explode(',', TRUSTED_PROXIES) : [];
    
    $isTrusted = false;
    foreach ($trustedProxies as $proxy) {
      if (!empty(trim($proxy)) && str_starts_with($remoteAddr, trim($proxy))) {
        $isTrusted = true;
        break;
      }
    }

    if (!$isTrusted) {
      return $remoteAddr;
    }

    $headers = ["HTTP_CF_CONNECTING_IP", "HTTP_X_FORWARDED_FOR", "HTTP_X_REAL_IP", "HTTP_CLIENT_IP"];
    foreach ($headers as $header) {
      if (!empty($_SERVER[$header])) {
        $ips = explode(",", $_SERVER[$header]);
        $candidate = trim(end($ips));
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
          return $candidate;
        }
      }
    }
    return $remoteAddr;
  }

  /**
   * Determina si una IP es de entorno local o privada.
   *
   * @param string $ip Dirección IP.
   * @return bool True si es local/privada.
   */
  public static function isLocalIp(string $ip): bool {
    if (in_array($ip, ["::1", "127.0.0.1", "localhost"], true)) {
      return true;
    }
    return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
  }

  /**
   * Obtiene la información de ubicación utilizando exclusivamente la base de datos local MMDB de MaxMind.
   * Nunca realiza consultas a la red ni a APIs externas.
   *
   * @param string $ip Dirección IP a consultar.
   * @return array Datos con country_code, country_name, city_name.
   */
  private static function getGeoData(string $ip): array {
    if (self::isLocalIp($ip)) {
      return [
        "country_code" => "DEV",
        "country_name" => "Local Development",
        "city_name"    => "Localhost"
      ];
    }

    if (class_exists(GeoIpModule::class)) {
      try {
        $record = GeoIpModule::getCityRecord($ip);
        if ($record) {
          return [
            "country_code" => $record->country->isoCode ?? "N/A",
            "country_name" => $record->country->name ?? "Desconocido",
            "city_name"    => $record->city->name ?? "Desconocido"
          ];
        }
      } catch (Exception $e) {
        // En caso de que la IP no se encuentre en la BD local de MaxMind
      }
    }

    return [
      "country_code" => "N/A",
      "country_name" => "Desconocido",
      "city_name"    => "Desconocido"
    ];
  }

  /**
   * Procesa y registra la visita orgánica a un perfil de usuario.
   * 
   * Filtros aplicados:
   * 1. Descarte instantáneo de bots, scrapers y crawlers (BotDetectorModule).
   * 2. Descarte de visitas del propio dueño del perfil logueado.
   * 3. Control de frecuencia en sesión (1 vez cada 24h).
   * 4. Deduplicación por IP en base de datos (1 vez cada 24h por IP para el mismo perfil).
   * 5. Sanitización y clasificación de referrer anti-spam.
   *
   * @param string $visitedUser Nombre del usuario visitado.
   * @param array $extra Parámetros adicionales opcionales (ej: referrer enviado desde frontend).
   * @return bool True si la visita fue procesada y registrada, false si fue omitida o falló.
   */
  public static function processVisit(string $visitedUser, array $extra = []): bool {
    $visitedUserClean = mb_strtolower(trim($visitedUser), "UTF-8");

    if (empty($visitedUserClean)) {
      return false;
    }

    // 1. Filtrar bots, crawlers, previsualizadores y herramientas de scraping
    if (BotDetectorModule::isBot()) {
      return false;
    }

    // 2. Omitir registro si el usuario está logueado y visita su propio perfil
    if (Session::session_active()) {
      $sessionUser = Session::session_data("username");
      if (!empty($sessionUser) && mb_strtolower($sessionUser, "UTF-8") === $visitedUserClean) {
        return false;
      }
    }

    // 3. Control en sesión (1 vez cada 24 horas)
    $sessionKey = "visit_registered_" . $visitedUserClean;
    $lastVisitTime = $_SESSION[$sessionKey] ?? 0;
    if ((time() - (int)$lastVisitTime) <= 86400) {
      return false;
    }

    $ip = self::getClientIp();

    // 4. Deduplicación en base de datos (evita inflación por bots o clientes que descartan cookies)
    if (AnalyticsModule::hasRecentProfileView($visitedUserClean, $ip, 86400)) {
      return false;
    }

    try {
      if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION[$sessionKey] = time();
      }

      $geo        = self::getGeoData($ip);
      $deviceType = MovilDetectorModule::getDeviceType();
      $os         = MovilDetectorModule::getOS();
      $browser    = MovilDetectorModule::getBrowser();
      
      // Sanitizar el referrer recibido (prioridad frontend -> luego HTTP_REFERER)
      $rawReferrer = $extra["referrer"] ?? ($_SERVER["HTTP_REFERER"] ?? "");
      $cleanReferrer = BotDetectorModule::cleanReferrer($rawReferrer);

      // Guardar también en sesión para lecturas inmediatas de ubicación
      if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION["location"] = array_merge([
          "ip"     => $ip,
          "pais"   => $geo["country_name"],
          "codigo" => $geo["country_code"],
          "ciudad" => $geo["city_name"]
        ], $_SESSION["location"] ?? []);

        session_write_close();
      }

      // Registrar la visita orgánica en SQLite (profile_views)
      return AnalyticsModule::logProfileView($visitedUserClean, [
        "ip_address"   => $ip,
        "country_code" => $geo["country_code"],
        "country_name" => $geo["country_name"],
        "city_name"    => $geo["city_name"],
        "device_type"  => $deviceType,
        "os"           => $os,
        "browser"      => $browser,
        "referrer"     => $cleanReferrer
      ]);
    } catch (Exception $e) {
      error_log("Error en VisitModels::processVisit: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Procesa y registra un clic en un enlace individual respetando reglas anti-bot y deduplicación.
   *
   * @param string $visitedUser Nombre del usuario visitado.
   * @param string $linkId Identificador o URL del enlace.
   * @param array $extra Parámetros adicionales (is_trusted, elapsed, etc.)
   * @return bool True si el clic fue registrado en la BD, false si fue omitido o falló.
   */
  public static function processClick(string $visitedUser, string $linkId, array $extra = []): bool {
    $visitedUserClean = mb_strtolower(trim($visitedUser), "UTF-8");
    $linkIdClean      = mb_strtolower(trim($linkId), "UTF-8");

    if (empty($visitedUserClean) || empty($linkIdClean)) {
      return false;
    }

    // 1. Filtrar bots, scripts automatizados y crawlers
    if (BotDetectorModule::isBot()) {
      return false;
    }

    // 2. Descartar enlaces honeypot (trampa invisible para scrapers/bots)
    if ($linkIdClean === "__hp_trap__" || !empty($extra["is_trap"])) {
      return false;
    }

    // 3. Validar si el cliente reportó un evento no fidedigno (disparado artificialmente por JS)
    if (isset($extra["is_trusted"]) && $extra["is_trusted"] === false) {
      return false;
    }

    if (session_status() === PHP_SESSION_NONE) {
      @session_start();
    }

    // 4. Omitir registro si el usuario está logueado y hace clic en su propio perfil
    if (Session::session_active()) {
      $sessionUser = Session::session_data("username");
      if (!empty($sessionUser) && mb_strtolower($sessionUser, "UTF-8") === $visitedUserClean) {
        return false;
      }
    }

    // 5. Control en sesión por enlace individual (1 clic por link cada 24 horas)
    $sessionKey = "click_registered_" . $visitedUserClean . "_" . $linkIdClean;
    $lastClickTime = $_SESSION[$sessionKey] ?? 0;
    if ((time() - (int)$lastClickTime) <= 86400) {
      return false;
    }

    $ip = self::getClientIp();

    // 6. Deduplicación por IP y enlace en la base de datos (24 horas)
    if (AnalyticsModule::hasRecentLinkClick($visitedUserClean, $linkIdClean, $ip, 86400)) {
      return false;
    }

    try {
      if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION[$sessionKey] = time();
        session_write_close();
      }

      $geo        = self::getGeoData($ip);
      $deviceType = MovilDetectorModule::getDeviceType();

      return AnalyticsModule::logLinkClick($visitedUserClean, $linkIdClean, [
        "ip_address"   => $ip,
        "country_code" => $geo["country_code"],
        "device_type"  => $deviceType
      ]);
    } catch (Exception $e) {
      error_log("Error en VisitModels::processClick: " . $e->getMessage());
      return false;
    }
  }

}
