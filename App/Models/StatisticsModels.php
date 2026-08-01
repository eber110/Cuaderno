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
   * Obtiene la estructura completa de estadísticas y métricas para un perfil de usuario,
   * incluyendo desgloses por días más visitados, horarios de mayor tráfico y recomendación inteligente.
   *
   * @param string $user Nombre de usuario del perfil.
   * @return array Resumen de métricas, dispositivos, navegadores, países, fuentes, días/horas top, recomendación y registros recientes.
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

      // 6. Desglose por Días de la Semana (0 = Domingo, 1 = Lunes, ..., 6 = Sábado)
      $stmtDays = $pdo->prepare("
        SELECT strftime('%w', created_at) as day_num, COUNT(*) as total 
        FROM profile_views 
        WHERE profile_id = :user 
        GROUP BY day_num 
        ORDER BY total DESC
      ");
      $stmtDays->execute([":user" => $userClean]);
      $rawDays = $stmtDays->fetchAll() ?: [];

      $dayMap = [
        "0" => "Domingo",
        "1" => "Lunes",
        "2" => "Martes",
        "3" => "Miércoles",
        "4" => "Jueves",
        "5" => "Viernes",
        "6" => "Sábado"
      ];

      $topDays = [];
      foreach ($rawDays as $d) {
        $num = (string)($d["day_num"] ?? "");
        if (isset($dayMap[$num])) {
          $topDays[] = [
            "day_num"  => $num,
            "day_name" => $dayMap[$num],
            "total"    => (int)$d["total"]
          ];
        }
      }

      // 7. Desglose por Horarios de Mayor Tráfico (Franja de 00:00 a 23:00)
      $stmtHours = $pdo->prepare("
        SELECT strftime('%H', created_at) as hour_num, COUNT(*) as total 
        FROM profile_views 
        WHERE profile_id = :user 
        GROUP BY hour_num 
        ORDER BY total DESC 
        LIMIT 6
      ");
      $stmtHours->execute([":user" => $userClean]);
      $rawHours = $stmtHours->fetchAll() ?: [];

      $topHours = [];
      foreach ($rawHours as $h) {
        $hNum = (int)($h["hour_num"] ?? 0);
        $nextH = ($hNum + 1) % 24;
        $label = sprintf("%02d:00 - %02d:00", $hNum, $nextH);
        $topHours[] = [
          "hour_num" => sprintf("%02d", $hNum),
          "label"    => $label,
          "total"    => (int)$h["total"]
        ];
      }

      // 8. Recomendación Inteligente de Horario y Día Pico
      $bestDayName   = !empty($topDays)  ? $topDays[0]["day_name"] : "Lunes";
      $bestHourLabel = !empty($topHours) ? $topHours[0]["label"]   : "18:00 - 19:00";
      $bestDayTotal  = !empty($topDays)  ? $topDays[0]["total"]    : 0;
      $bestHourTotal = !empty($topHours) ? $topHours[0]["total"]   : 0;

      $recommendation = [
        "bestDay"       => $bestDayName,
        "bestHour"      => $bestHourLabel,
        "bestDayTotal"  => $bestDayTotal,
        "bestHourTotal" => $bestHourTotal
      ];

      // 9. Últimos registros recientes para depuración
      $stmtRecentViews = $pdo->prepare("SELECT ip_address, country_name, device_type, os, browser, referrer, created_at FROM profile_views WHERE profile_id = :user ORDER BY id DESC LIMIT 5");
      $stmtRecentViews->execute([":user" => $userClean]);
      $recentViews = $stmtRecentViews->fetchAll() ?: [];

      return [
        "summary"        => $summary,
        "devices"        => $devices,
        "browsers"       => $browsers,
        "countries"      => $countries,
        "referrers"      => $referrers,
        "clicksByLink"   => $clicksByLink,
        "topDays"        => $topDays,
        "topHours"       => $topHours,
        "recommendation" => $recommendation,
        "recentViews"    => $recentViews
      ];
    } catch (Exception $e) {
      error_log("Error en StatisticsModels::getStatsData: " . $e->getMessage());
      return [
        "summary"        => $summary,
        "devices"        => [],
        "browsers"       => [],
        "countries"      => [],
        "referrers"      => [],
        "clicksByLink"   => [],
        "topDays"        => [],
        "topHours"       => [],
        "recommendation" => [
          "bestDay"       => "Lunes",
          "bestHour"      => "18:00 - 19:00",
          "bestDayTotal"  => 0,
          "bestHourTotal" => 0
        ],
        "recentViews"    => []
      ];
    }
  }

  /**
   * Genera registros simulados de prueba en la base de datos SQLite para depuración,
   * distribuyendo fechas entre varios días y horas para alimentar las estadísticas de tráfico.
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

    // Generar marca temporal distribuida en los últimos 14 días y horas variadas
    $randomDays = rand(0, 14);
    $randomHours = rand(0, 23);
    $randomMinutes = rand(0, 59);
    $randomCreatedAt = date("Y-m-d H:i:s", strtotime("-{$randomDays} days -{$randomHours} hours -{$randomMinutes} minutes"));

    // Insertar visita de prueba
    $viewLogged = AnalyticsModule::logProfileView($userClean, [
      "ip_address"   => rand(170, 200) . "." . rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255),
      "country_code" => $sample["code"],
      "country_name" => $sample["country"],
      "city_name"    => $sample["city"],
      "device_type"  => $sample["device"],
      "os"           => $sample["os"],
      "browser"      => $sample["browser"],
      "referrer"     => $sample["ref"],
      "created_at"   => $randomCreatedAt
    ]);

    // Insertar clic de prueba
    $clickLogged = AnalyticsModule::logLinkClick($userClean, "enlace_" . rand(1, 4), [
      "country_code" => $sample["code"],
      "device_type"  => $sample["device"],
      "created_at"   => $randomCreatedAt
    ]);

    return $viewLogged && $clickLogged;
  }

}
