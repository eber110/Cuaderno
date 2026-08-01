<?php

namespace App\Models;

use Base\Builder\Builder;
use Base\Module\AnalyticsModule;
use Exception;

/**
 * Clase StatisticsModels
 * 
 * Modelo encargado de la consulta, procesamiento y generación de datos estadísticos y analíticas.
 * Respeta la zona horaria del servidor/aplicación (America/Santiago) garantizando coherencia absoluta.
 */
class StatisticsModels extends Builder {

  protected $table = "profile_views";

  /**
   * Obtiene la estructura completa de estadísticas y métricas para un perfil de usuario,
   * utilizando la fecha/mes local de PHP según date.timezone (ej: America/Santiago).
   *
   * @param string $user Nombre de usuario del perfil.
   * @return array Resumen de métricas, dispositivos, navegadores, países, fuentes, días/horas top, redes sociales y registros recientes.
   */
  public static function getStatsData(string $user): array {
    $userClean = mb_strtolower($user, "UTF-8");

    try {
      $pdo = AnalyticsModule::getPdo();

      // Utilizar la fecha local configurada en PHP (date.timezone)
      $currentLocalMonth = date("Y-m");

      // Verificar si existen registros para el mes local actual
      $stmtCheckCurrent = $pdo->prepare("SELECT COUNT(*) FROM profile_views WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :currentMonth");
      $stmtCheckCurrent->execute([":user" => $userClean, ":currentMonth" => $currentLocalMonth]);
      $hasCurrentMonthData = ((int)$stmtCheckCurrent->fetchColumn()) > 0;

      if ($hasCurrentMonthData) {
        $activeMonth = $currentLocalMonth;
      } else {
        // Si no hay datos este mes local, buscar el último mes registrado
        $stmtLatestMonth = $pdo->prepare("SELECT strftime('%Y-%m', created_at) as active_month FROM profile_views WHERE profile_id = :user ORDER BY created_at DESC LIMIT 1");
        $stmtLatestMonth->execute([":user" => $userClean]);
        $activeMonth = $stmtLatestMonth->fetch()['active_month'] ?? $currentLocalMonth;
      }

      // 1. Resumen de Métricas del Mes Activo
      $stmtMonthViews = $pdo->prepare("SELECT COUNT(*) as total_views, COUNT(DISTINCT ip_address) as unique_views FROM profile_views WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth");
      $stmtMonthViews->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $monthViewsData = $stmtMonthViews->fetch() ?: ['total_views' => 0, 'unique_views' => 0];

      $stmtMonthClicks = $pdo->prepare("SELECT COUNT(*) as total_clicks FROM link_clicks WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth");
      $stmtMonthClicks->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $monthClicksData = $stmtMonthClicks->fetch() ?: ['total_clicks' => 0];

      $mViews   = (int)($monthViewsData['total_views'] ?? 0);
      $mUniques = (int)($monthViewsData['unique_views'] ?? 0);
      $mClicks  = (int)($monthClicksData['total_clicks'] ?? 0);
      $mCtr     = ($mViews > 0) ? round(($mClicks / $mViews) * 100, 2) : 0;

      $monthlySummary = [
        'total_views'  => $mViews,
        'unique_views' => $mUniques,
        'total_clicks' => $mClicks,
        'ctr'          => $mCtr
      ];

      // 2. Resumen Histórico Acumulado (Todos los tiempos)
      $stmtAllViews = $pdo->prepare("SELECT COUNT(*) as total_views_all_time FROM profile_views WHERE profile_id = :user");
      $stmtAllViews->execute([":user" => $userClean]);
      $allViewsCount = (int)($stmtAllViews->fetch()['total_views_all_time'] ?? 0);

      $stmtAllClicks = $pdo->prepare("SELECT COUNT(*) as total_clicks_all_time FROM link_clicks WHERE profile_id = :user");
      $stmtAllClicks->execute([":user" => $userClean]);
      $allClicksCount = (int)($stmtAllClicks->fetch()['total_clicks_all_time'] ?? 0);

      $allTimeSummary = [
        'total_views'  => $allViewsCount,
        'total_clicks' => $allClicksCount
      ];

      // 3. Desglose de Visitas por Día del Mes Activo
      $stmtDayMonth = $pdo->prepare("
        SELECT strftime('%Y-%m-%d', created_at) as date_str, strftime('%d', created_at) as day_num, COUNT(*) as total
        FROM profile_views
        WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth
        GROUP BY date_str
        ORDER BY date_str ASC
      ");
      $stmtDayMonth->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $viewsByDayOfMonth = $stmtDayMonth->fetchAll() ?: [];

      // 4. Desglose de Visitas por Semana del Mes Activo
      $stmtWeekMonth = $pdo->prepare("
        SELECT strftime('%W', created_at) as week_num, COUNT(*) as total
        FROM profile_views
        WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth
        GROUP BY week_num
        ORDER BY week_num ASC
      ");
      $stmtWeekMonth->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $rawWeeks = $stmtWeekMonth->fetchAll() ?: [];

      $viewsByWeekOfMonth = [];
      foreach ($rawWeeks as $idx => $w) {
        $viewsByWeekOfMonth[] = [
          'week_label' => "Semana " . ($idx + 1),
          'week_num'   => $w['week_num'],
          'total'      => (int)$w['total']
        ];
      }

      // 5. Desglose por tipo de dispositivo del Mes Activo
      $stmtDevices = $pdo->prepare("
        SELECT device_type, COUNT(*) as total 
        FROM profile_views 
        WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth 
        GROUP BY device_type 
        ORDER BY total DESC
      ");
      $stmtDevices->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $devices = $stmtDevices->fetchAll() ?: [];

      // 6. Desglose por navegador del Mes Activo
      $stmtBrowsers = $pdo->prepare("
        SELECT browser, COUNT(*) as total 
        FROM profile_views 
        WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth 
        GROUP BY browser 
        ORDER BY total DESC 
        LIMIT 5
      ");
      $stmtBrowsers->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $browsers = $stmtBrowsers->fetchAll() ?: [];

      // 7. Desglose por ubicación geográfica (Países) del Mes Activo
      $stmtCountries = $pdo->prepare("
        SELECT country_name, country_code, COUNT(*) as total 
        FROM profile_views 
        WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth 
        GROUP BY country_name 
        ORDER BY total DESC 
        LIMIT 5
      ");
      $stmtCountries->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $countries = $stmtCountries->fetchAll() ?: [];

      // 8. Desglose por fuentes de tráfico (Referrers) del Mes Activo
      $stmtReferrers = $pdo->prepare("
        SELECT referrer, COUNT(*) as total 
        FROM profile_views 
        WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth 
        GROUP BY referrer 
        ORDER BY total DESC 
        LIMIT 5
      ");
      $stmtReferrers->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $referrers = $stmtReferrers->fetchAll() ?: [];

      // 9. Desglose por Días de la Semana del Mes Activo
      $stmtDays = $pdo->prepare("
        SELECT strftime('%w', created_at) as day_num, COUNT(*) as total 
        FROM profile_views 
        WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth 
        GROUP BY day_num 
        ORDER BY total DESC
      ");
      $stmtDays->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $rawDays = $stmtDays->fetchAll() ?: [];

      $dayMap = [
        "0" => "Domingo", "1" => "Lunes", "2" => "Martes", "3" => "Miércoles",
        "4" => "Jueves", "5" => "Viernes", "6" => "Sábado"
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

      // 10. Desglose por Horarios de Mayor Tráfico del Mes Activo
      $stmtHours = $pdo->prepare("
        SELECT strftime('%H', created_at) as hour_num, COUNT(*) as total 
        FROM profile_views 
        WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth 
        GROUP BY hour_num 
        ORDER BY total DESC 
        LIMIT 6
      ");
      $stmtHours->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
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

      // 11. Recomendación Inteligente de Horario y Día Pico del Mes Activo
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

      // 12. Métricas detalladas por Red Social del Mes Activo
      $socialStats = self::getSocialNetworksStats($pdo, $userClean, $activeMonth);

      // 13. Registros recientes para depuración
      $stmtRecentViews = $pdo->prepare("SELECT ip_address, country_name, device_type, os, browser, referrer, created_at FROM profile_views WHERE profile_id = :user ORDER BY id DESC LIMIT 5");
      $stmtRecentViews->execute([":user" => $userClean]);
      $recentViews = $stmtRecentViews->fetchAll() ?: [];

      return [
        "summary"             => $monthlySummary,
        "allTimeSummary"      => $allTimeSummary,
        "viewsByDayOfMonth"   => $viewsByDayOfMonth,
        "viewsByWeekOfMonth"  => $viewsByWeekOfMonth,
        "devices"             => $devices,
        "browsers"            => $browsers,
        "countries"           => $countries,
        "referrers"           => $referrers,
        "topDays"             => $topDays,
        "topHours"            => $topHours,
        "recommendation"      => $recommendation,
        "socialStats"         => $socialStats,
        "recentViews"         => $recentViews
      ];
    } catch (Exception $e) {
      error_log("Error en StatisticsModels::getStatsData: " . $e->getMessage());
      return [
        "summary"             => ['total_views' => 0, 'unique_views' => 0, 'total_clicks' => 0, 'ctr' => 0],
        "allTimeSummary"      => ['total_views' => 0, 'total_clicks' => 0],
        "viewsByDayOfMonth"   => [],
        "viewsByWeekOfMonth"  => [],
        "devices"             => [],
        "browsers"            => [],
        "countries"           => [],
        "referrers"           => [],
        "topDays"             => [],
        "topHours"            => [],
        "recommendation"      => [
          "bestDay"       => "Lunes",
          "bestHour"      => "18:00 - 19:00",
          "bestDayTotal"  => 0,
          "bestHourTotal" => 0
        ],
        "socialStats"        => [],
        "recentViews"        => []
      ];
    }
  }

  /**
   * Obtiene desgloses detallados por Red Social del Mes Activo.
   */
  private static function getSocialNetworksStats($pdo, string $userClean, string $activeMonth): array {
    $networksConfig = [
      'instagram' => ['name' => 'Instagram',       'icon' => 'instagram',   'patterns' => ['%instagram%']],
      'tiktok'    => ['name' => 'TikTok',          'icon' => 'tiktok',      'patterns' => ['%tiktok%']],
      'facebook'  => ['name' => 'Facebook',        'icon' => 'facebook',    'patterns' => ['%facebook%']],
      'twitter'   => ['name' => 'Twitter / X',     'icon' => 'x',           'patterns' => ['%twitter%', '%t.co%', '%x.com%']],
      'youtube'   => ['name' => 'YouTube',         'icon' => 'globe-solid', 'patterns' => ['%youtube%']],
      'linkedin'  => ['name' => 'LinkedIn',        'icon' => 'linkedin',    'patterns' => ['%linkedin%']],
      'google'    => ['name' => 'Google',          'icon' => 'google',      'patterns' => ['%google%']],
      'direct'    => ['name' => 'Tráfico Directo', 'icon' => 'globe-solid', 'patterns' => ['']]
    ];

    $dayMap = [
      "0" => "Domingo", "1" => "Lunes", "2" => "Martes", "3" => "Miércoles",
      "4" => "Jueves", "5" => "Viernes", "6" => "Sábado"
    ];

    $socialStats = [];

    foreach ($networksConfig as $key => $net) {
      if ($key === 'direct') {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) as total FROM profile_views WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth AND (referrer IS NULL OR referrer = '' OR referrer = 'Tráfico Directo')");
        $stmtCount->execute([':user' => $userClean, ':activeMonth' => $activeMonth]);
      } else {
        $whereConditions = [];
        $params = [':user' => $userClean, ':activeMonth' => $activeMonth];
        foreach ($net['patterns'] as $i => $pattern) {
          $paramName = ":p{$i}";
          $whereConditions[] = "referrer LIKE {$paramName}";
          $params[$paramName] = $pattern;
        }
        $whereClause = implode(" OR ", $whereConditions);
        $stmtCount = $pdo->prepare("SELECT COUNT(*) as total FROM profile_views WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth AND ({$whereClause})");
        $stmtCount->execute($params);
      }

      $total = (int)($stmtCount->fetch()['total'] ?? 0);
      if ($total === 0) continue;

      // Obtener día pico para esta red social en el mes activo
      if ($key === 'direct') {
        $stmtDay = $pdo->prepare("SELECT strftime('%w', created_at) as day_num, COUNT(*) as cnt FROM profile_views WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth AND (referrer IS NULL OR referrer = '' OR referrer = 'Tráfico Directo') GROUP BY day_num ORDER BY cnt DESC LIMIT 1");
        $stmtDay->execute([':user' => $userClean, ':activeMonth' => $activeMonth]);
      } else {
        $stmtDay = $pdo->prepare("SELECT strftime('%w', created_at) as day_num, COUNT(*) as cnt FROM profile_views WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth AND ({$whereClause}) GROUP BY day_num ORDER BY cnt DESC LIMIT 1");
        $stmtDay->execute($params);
      }
      $rowDay = $stmtDay->fetch();
      $bestDayNum = (string)($rowDay['day_num'] ?? "1");
      $bestDayName = $dayMap[$bestDayNum] ?? "Lunes";

      // Obtener hora pico para esta red social en el mes activo
      if ($key === 'direct') {
        $stmtHour = $pdo->prepare("SELECT strftime('%H', created_at) as hour_num, COUNT(*) as cnt FROM profile_views WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth AND (referrer IS NULL OR referrer = '' OR referrer = 'Tráfico Directo') GROUP BY hour_num ORDER BY cnt DESC LIMIT 1");
        $stmtHour->execute([':user' => $userClean, ':activeMonth' => $activeMonth]);
      } else {
        $stmtHour = $pdo->prepare("SELECT strftime('%H', created_at) as hour_num, COUNT(*) as cnt FROM profile_views WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth AND ({$whereClause}) GROUP BY hour_num ORDER BY cnt DESC LIMIT 1");
        $stmtHour->execute($params);
      }
      $rowHour = $stmtHour->fetch();
      $hNum = (int)($rowHour['hour_num'] ?? 18);
      $nextH = ($hNum + 1) % 24;
      $bestHourLabel = sprintf("%02d:00 - %02d:00", $hNum, $nextH);

      $socialStats[] = [
        'key'      => $key,
        'name'     => $net['name'],
        'icon'     => $net['icon'],
        'total'    => $total,
        'bestDay'  => $bestDayName,
        'bestHour' => $bestHourLabel
      ];
    }

    usort($socialStats, fn($a, $b) => $b['total'] <=> $a['total']);

    return $socialStats;
  }

  /**
   * Genera un lote de registros simulados de prueba (por defecto 25 visitas y clics)
   * distribuidos exclusivamente dentro del mes actual según date.timezone de PHP.
   *
   * @param string $user Nombre de usuario objetivo.
   * @param int $count Cantidad de visitas simuladas a insertar.
   * @return bool True si los registros fueron insertados con éxito.
   */
  public static function generateTestData(string $user, int $count = 25): bool {
    $userClean = mb_strtolower($user, "UTF-8");

    $samples = [
      ["device" => "mobile",  "os" => "iOS",     "browser" => "Instagram App", "country" => "Chile",  "code" => "CL", "city" => "Santiago",        "ref" => "https://instagram.com"],
      ["device" => "mobile",  "os" => "Android", "browser" => "TikTok App",    "country" => "Chile",  "code" => "CL", "city" => "Valparaíso",      "ref" => "https://tiktok.com"],
      ["device" => "desktop", "os" => "Windows", "browser" => "Chrome",        "country" => "México", "code" => "MX", "city" => "Ciudad de México", "ref" => "https://google.com"],
      ["device" => "desktop", "os" => "macOS",   "browser" => "Safari",        "country" => "España", "code" => "ES", "city" => "Madrid",           "ref" => "https://facebook.com"],
      ["device" => "mobile",  "os" => "iOS",     "browser" => "Safari",        "country" => "Chile",  "code" => "CL", "city" => "Concepción",      "ref" => "https://t.co"],
      ["device" => "desktop", "os" => "Windows", "browser" => "Firefox",       "country" => "Argentina", "code" => "AR", "city" => "Buenos Aires",  "ref" => ""]
    ];

    $currentMonth = date("Y-m");
    $currentDay   = max(1, (int)date("d"));
    $successCount = 0;

    for ($i = 0; $i < $count; $i++) {
      $sample = $samples[array_rand($samples)];

      $day     = sprintf("%02d", rand(1, $currentDay));
      $hours   = sprintf("%02d", rand(0, 23));
      $minutes = sprintf("%02d", rand(0, 59));
      $seconds = sprintf("%02d", rand(0, 59));

      $formattedDate = "{$currentMonth}-{$day} {$hours}:{$minutes}:{$seconds}";

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
        "created_at"   => $formattedDate
      ]);

      // Generar clic en 70% de las visitas simuladas
      if (rand(1, 100) <= 70) {
        AnalyticsModule::logLinkClick($userClean, "enlace_" . rand(1, 4), [
          "country_code" => $sample["code"],
          "device_type"  => $sample["device"],
          "created_at"   => $formattedDate
        ]);
      }

      if ($viewLogged) $successCount++;
    }

    return $successCount > 0;
  }

}
