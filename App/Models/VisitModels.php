<?php

namespace App\Models;

use Base\Builder\Builder;
use Base\Module\AnalyticsModule;
use Base\Module\GeoIpModule;
use Base\Module\MovilDetectorModule;
use Exception;

/**
 * Clase VisitModels
 * 
 * Modelo encargado del procesamiento de reglas de negocio relativas a visitas de usuarios,
 * control de frecuencia en sesión, extracción de geolocalización exclusivamente mediante
 * MaxMind MMDB local (GeoIpModule) y guardado en SQLite.
 */
class VisitModels extends Builder {

  protected $table = "profile_views";

  /**
   * Obtiene la dirección IP del cliente a partir de cabeceras de proxy o REMOTE_ADDR.
   *
   * @return string Dirección IP resuelta.
   */
  public static function getClientIp(): string {
    $headers = ["HTTP_CF_CONNECTING_IP", "HTTP_X_FORWARDED_FOR", "HTTP_X_REAL_IP", "HTTP_CLIENT_IP"];
    foreach ($headers as $header) {
      if (!empty($_SERVER[$header])) {
        $ips = explode(",", $_SERVER[$header]);
        $candidate = trim($ips[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
          return $candidate;
        }
      }
    }
    return $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
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
   * Procesa y registra la visita a un perfil de usuario respetando la frecuencia de sesión.
   *
   * @param string $visitedUser Nombre del usuario visitado.
   * @return bool True si la visita fue procesada y registrada, false si fue omitida o falló.
   */
  public static function processVisit(string $visitedUser): bool {
    $visitedUserClean = mb_strtolower($visitedUser, "UTF-8");

    // Omitir registro si el usuario está logueado y visita su propio perfil
    if (\Base\Module\Session::session_active()) {
      $sessionUser = \Base\Module\Session::session_data("username");
      if (!empty($sessionUser) && mb_strtolower($sessionUser, "UTF-8") === $visitedUserClean) {
        return false;
      }
    }

    // Controlar en sesión la frecuencia de visitas al mismo perfil (1 vez cada 24 horas / 86400s)
    $sessionKey = "visit_registered_" . $visitedUserClean;
    $lastVisitTime = $_SESSION[$sessionKey] ?? 0;

    if ((time() - (int)$lastVisitTime) <= 86400) {
      return false; // Visita omitida por restricción de tiempo diaria (24h)
    }

    try {
      $_SESSION[$sessionKey] = time();

      $ip         = self::getClientIp();
      $geo        = self::getGeoData($ip);
      $deviceType = MovilDetectorModule::getDeviceType();
      $os         = MovilDetectorModule::getOS();
      $browser    = MovilDetectorModule::getBrowser();
      $referrer   = $_SERVER["HTTP_REFERER"] ?? "";

      // Guardar también en sesión para lecturas inmediatas
      $_SESSION["location"] = array_merge([
        "ip"     => $ip,
        "pais"   => $geo["country_name"],
        "codigo" => $geo["country_code"],
        "ciudad" => $geo["city_name"]
      ], $_SESSION["location"] ?? []);

      // Liberar el bloqueo de archivo de sesión inmediatamente
      if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
      }

      // Registrar la visita directamente en la base de datos SQLite (profile_views)
      return AnalyticsModule::logProfileView($visitedUserClean, [
        "ip_address"   => $ip,
        "country_code" => $geo["country_code"],
        "country_name" => $geo["country_name"],
        "city_name"    => $geo["city_name"],
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

  /**
   * Procesa y registra un clic en un enlace individual respetando la frecuencia por link en sesión.
   *
   * @param string $visitedUser Nombre del usuario visitado.
   * @param string $linkId Identificador o URL del enlace.
   * @return bool True si el clic fue registrado en la BD, false si fue omitido o falló.
   */
  public static function processClick(string $visitedUser, string $linkId): bool {
    $visitedUserClean = mb_strtolower(trim($visitedUser), "UTF-8");
    $linkIdClean      = mb_strtolower(trim($linkId), "UTF-8");

    if (empty($visitedUserClean) || empty($linkIdClean)) {
      return false;
    }

    if (session_status() === PHP_SESSION_NONE) {
      @session_start();
    }

    // Omitir registro si el usuario está logueado y hace clic en su propio perfil
    if (\Base\Module\Session::session_active()) {
      $sessionUser = \Base\Module\Session::session_data("username");
      if (!empty($sessionUser) && mb_strtolower($sessionUser, "UTF-8") === $visitedUserClean) {
        return false;
      }
    }

    // Controlar en sesión la frecuencia de clics por enlace individual (1 clic por link cada 24 horas / 86400s)
    $sessionKey = "click_registered_" . $visitedUserClean . "_" . $linkIdClean;
    $lastClickTime = $_SESSION[$sessionKey] ?? 0;

    if ((time() - (int)$lastClickTime) <= 86400) {
      return false; // Clic en este enlace omitido por restricción de tiempo diaria (24h)
    }

    try {
      $_SESSION[$sessionKey] = time();

      $ip         = self::getClientIp();
      $geo        = self::getGeoData($ip);
      $deviceType = MovilDetectorModule::getDeviceType();

      // Liberar el bloqueo de archivo de sesión inmediatamente
      if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
      }

      return AnalyticsModule::logLinkClick($visitedUserClean, $linkIdClean, [
        "country_code" => $geo["country_code"],
        "device_type"  => $deviceType
      ]);
    } catch (Exception $e) {
      error_log("Error en VisitModels::processClick: " . $e->getMessage());
      return false;
    }
  }

}
