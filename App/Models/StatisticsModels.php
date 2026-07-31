<?php

namespace App\Models;

use Base\Builder\Builder;
use Base\Module\AnalyticsModule;
use Exception;

/**
 * Clase StatisticsModels
 * 
 * Modelo encargado de la consulta, procesamiento y generación de datos estadísticos y analíticas.
 * Cumple con el Principio de Responsabilidad Única (SRP) concentrando todas las consultas a SQLite.
 */
class StatisticsModels extends Builder {

  protected $table = "profile_views";

  /**
   * Obtiene la estructura completa de estadísticas y métricas para un perfil de usuario.
   *
   * @param string $user Nombre de usuario del perfil.
   * @return array Resumen de métricas, dispositivos, navegadores, países, fuentes y registros recientes.
   */
  public static function getStatsData(string $user): array {
    $userClean = mb_strtolower($user, "UTF-8");

    // Resumen general (Totales, Únicas, Clics, CTR)
    $summary = AnalyticsModule::getProfileSummary($userClean);

    try {
      $pdo = AnalyticsModule::getPdo();

      // 1. Desglose por tipo de dispositivo (mobile, tablet, desktop)
      $stmtDevices = $pdo->prepare("SELECT device_type, COUNT(*) as total FROM profile_views WHERE profile_id = :user GROUP BY device_type ORDER BY total DESC");
      $stmtDevices->execute([":user" => $userClean]);
      $devices = $stmtDevices->fetchAll() ?: [];

      // 2. Desglose por navegador (incluyendo aplicaciones In-App)
      $stmtBrowsers = $pdo->prepare("SELECT browser, COUNT(*) as total FROM profile_views WHERE profile_id = :user GROUP BY browser ORDER BY total DESC LIMIT 5");
      $stmtBrowsers->execute([":user" => $userClean]);
      $browsers = $stmtBrowsers->fetchAll() ?: [];

      // 3. Desglose por ubicación geográfica (Países)
      $stmtCountries = $pdo->prepare("SELECT country_name, country_code, COUNT(*) as total FROM profile_views WHERE profile_id = :user GROUP BY country_name ORDER BY total DESC LIMIT 5");
      $stmtCountries->execute([":user" => $userClean]);
      $countries = $stmtCountries->fetchAll() ?: [];

      // 4. Desglose por fuentes de tráfico (Referrers)
      $stmtReferrers = $pdo->prepare("SELECT referrer, COUNT(*) as total FROM profile_views WHERE profile_id = :user GROUP BY referrer ORDER BY total DESC LIMIT 5");
      $stmtReferrers->execute([":user" => $userClean]);
      $referrers = $stmtReferrers->fetchAll() ?: [];

      // 5. Desglose de clics por enlace
      $stmtClicks = $pdo->prepare("SELECT link_id, COUNT(*) as total FROM link_clicks WHERE profile_id = :user GROUP BY link_id ORDER BY total DESC LIMIT 10");
      $stmtClicks->execute([":user" => $userClean]);
      $clicksByLink = $stmtClicks->fetchAll() ?: [];

      // 6. Últimos registros recientes para depuración
      $stmtRecentViews = $pdo->prepare("SELECT ip_address, country_name, device_type, os, browser, referrer, created_at FROM profile_views WHERE profile_id = :user ORDER BY id DESC LIMIT 5");
      $stmtRecentViews->execute([":user" => $userClean]);
      $recentViews = $stmtRecentViews->fetchAll() ?: [];

      return [
        "summary"      => $summary,
        "devices"      => $devices,
        "browsers"     => $browsers,
        "countries"    => $countries,
        "referrers"    => $referrers,
        "clicksByLink" => $clicksByLink,
        "recentViews"  => $recentViews
      ];
    } catch (Exception $e) {
      error_log("Error en StatisticsModels::getStatsData: " . $e->getMessage());
      return [
        "summary"      => $summary,
        "devices"      => [],
        "browsers"     => [],
        "countries"    => [],
        "referrers"    => [],
        "clicksByLink" => [],
        "recentViews"  => []
      ];
    }
  }

  /**
   * Genera registros simulados de prueba en la base de datos SQLite para depuración.
   *
   * @param string $user Nombre de usuario objetivo.
   * @return bool True si los registros simulados fueron insertados con éxito.
   */
  public static function generateTestData(string $user): bool {
    $userClean = mb_strtolower($user, "UTF-8");

    $samples = [
      ["device" => "mobile",  "os" => "iOS",     "browser" => "Instagram App", "country" => "Chile",  "code" => "CL", "city" => "Santiago",        "ref" => "https://instagram.com"],
      ["device" => "mobile",  "os" => "Android", "browser" => "TikTok App",    "country" => "Chile",  "code" => "CL", "city" => "Valparaíso",      "ref" => "https://tiktok.com"],
      ["device" => "desktop", "os" => "Windows", "browser" => "Chrome",        "country" => "México", "code" => "MX", "city" => "Ciudad de México", "ref" => "https://google.com"],
      ["device" => "desktop", "os" => "macOS",   "browser" => "Safari",        "country" => "España", "code" => "ES", "city" => "Madrid",           "ref" => ""]
    ];

    $sample = $samples[array_rand($samples)];

    // Insertar visita de prueba
    $viewLogged = AnalyticsModule::logProfileView($userClean, [
      "ip_address"   => rand(170, 200) . "." . rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255),
      "country_code" => $sample["code"],
      "country_name" => $sample["country"],
      "city_name"    => $sample["city"],
      "device_type"  => $sample["device"],
      "os"           => $sample["os"],
      "browser"      => $sample["browser"],
      "referrer"     => $sample["ref"]
    ]);

    // Insertar clic de prueba
    $clickLogged = AnalyticsModule::logLinkClick($userClean, "enlace_" . rand(1, 4), [
      "country_code" => $sample["code"],
      "device_type"  => $sample["device"]
    ]);

    return $viewLogged && $clickLogged;
  }

}
