<?php

namespace App\Models;

use Base\Builder\Builder;
use Base\Module\AnalyticsModule;
use App\Models\DesignModels;
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

      // Cargar datos de la tarjeta del usuario para mapear nombres de enlaces de contenido y redes sociales (RRSS)
      $userData    = DesignModels::dataUser($userClean);
      $userContent = $userData['card']['content'] ?? [];
      $userRrss    = $userData['card']['rrss'] ?? [];

      $linkTitleMap = [];

      // 1. Mapear enlaces de contenido principal
      foreach ($userContent as $idx => $linkItem) {
        $rawTitle = !empty($linkItem['title']) ? $linkItem['title'] : (!empty($linkItem['metaTitle']) ? $linkItem['metaTitle'] : "Enlace #" . ($idx + 1));
        $cleanTitle = (mb_strlen($rawTitle, 'UTF-8') > 22) ? mb_strimwidth($rawTitle, 0, 20, '...', 'UTF-8') : $rawTitle;

        $linkTitleMap["enlace_" . ($idx + 1)] = $cleanTitle;
        $linkTitleMap["enlace_" . $idx]       = $cleanTitle;
        $linkTitleMap["content_" . $idx]      = $cleanTitle;
        $linkTitleMap[(string)$idx]          = $cleanTitle;
        $linkTitleMap[(string)($idx + 1)]    = $cleanTitle;
        if (!empty($linkItem['url'])) {
          $linkTitleMap[$linkItem['url']] = $cleanTitle;
        }
      }

      // 2. Mapear enlaces de redes sociales (RRSS)
      $netNamesMap = [
        'github'    => 'GitHub (Red Social)',
        'linkedin'  => 'LinkedIn (Red Social)',
        'x'         => 'X / Twitter (Red Social)',
        'twitter'   => 'X / Twitter (Red Social)',
        'instagram' => 'Instagram (Red Social)',
        'tiktok'    => 'TikTok (Red Social)',
        'facebook'  => 'Facebook (Red Social)',
        'youtube'   => 'YouTube (Red Social)',
        'pinterest' => 'Pinterest (Red Social)',
        'whatsapp'  => 'WhatsApp (Contacto)'
      ];

      foreach ($userRrss as $idx => $rItem) {
        $netKey = strtolower(trim($rItem[0] ?? ''));
        $netUrl = trim($rItem[1] ?? '');
        if (empty($netKey)) continue;

        $netTitle = $netNamesMap[$netKey] ?? (ucwords($netKey) . " (Red)");
        $cleanTitle = (mb_strlen($netTitle, 'UTF-8') > 22) ? mb_strimwidth($netTitle, 0, 20, '...', 'UTF-8') : $netTitle;

        $linkTitleMap[$netKey]          = $cleanTitle;
        $linkTitleMap["rrss_" . $netKey] = $cleanTitle;
        $linkTitleMap["rrss_" . $idx]    = $cleanTitle;
        if (!empty($netUrl)) {
          $linkTitleMap[$netUrl] = $cleanTitle;
        }
      }

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

      // 1. Resumen de Métricas del Mes Activo (Solo Clics en Enlaces del Widget de Contenido)
      $stmtMonthViews = $pdo->prepare("SELECT COUNT(*) as total_views, COUNT(DISTINCT ip_address) as unique_views FROM profile_views WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth");
      $stmtMonthViews->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $monthViewsData = $stmtMonthViews->fetch() ?: ['total_views' => 0, 'unique_views' => 0];

      $stmtMonthClicks = $pdo->prepare("
        SELECT COUNT(*) as total_clicks 
        FROM link_clicks 
        WHERE profile_id = :user 
          AND strftime('%Y-%m', created_at) = :activeMonth
          AND link_id NOT LIKE 'rrss_%'
          AND LOWER(link_id) NOT IN ('github','linkedin','x','twitter','facebook','instagram','tiktok','youtube','pinterest','whatsapp')
      ");
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
      $stmtAllViews = $pdo->prepare("SELECT COUNT(*) as total_views_all_time, COUNT(DISTINCT ip_address) as unique_views_all_time FROM profile_views WHERE profile_id = :user");
      $stmtAllViews->execute([":user" => $userClean]);
      $allViewsData = $stmtAllViews->fetch() ?: ['total_views_all_time' => 0, 'unique_views_all_time' => 0];

      $stmtAllClicks = $pdo->prepare("
        SELECT COUNT(*) as total_clicks_all_time 
        FROM link_clicks 
        WHERE profile_id = :user
          AND link_id NOT LIKE 'rrss_%'
          AND LOWER(link_id) NOT IN ('github','linkedin','x','twitter','facebook','instagram','tiktok','youtube','pinterest','whatsapp')
      ");
      $stmtAllClicks->execute([":user" => $userClean]);
      $allClicksCount = (int)($stmtAllClicks->fetch()['total_clicks_all_time'] ?? 0);

      $allTimeSummary = [
        'total_views'  => (int)($allViewsData['total_views_all_time'] ?? 0),
        'unique_views' => (int)($allViewsData['unique_views_all_time'] ?? 0),
        'total_clicks' => $allClicksCount
      ];

      // 3. Desglose de Visitas y Clics por Día del Mes Activo (Solo Enlaces del Widget)
      $stmtDayMonthViews = $pdo->prepare("
        SELECT strftime('%Y-%m-%d', created_at) as date_str, strftime('%d', created_at) as day_num, COUNT(*) as total_views, COUNT(DISTINCT ip_address) as unique_views
        FROM profile_views
        WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth
        GROUP BY date_str
        ORDER BY date_str ASC
      ");
      $stmtDayMonthViews->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $rawDayViews = $stmtDayMonthViews->fetchAll() ?: [];

      $stmtDayMonthClicks = $pdo->prepare("
        SELECT strftime('%Y-%m-%d', created_at) as date_str, COUNT(*) as total_clicks
        FROM link_clicks
        WHERE profile_id = :user 
          AND strftime('%Y-%m', created_at) = :activeMonth
          AND link_id NOT LIKE 'rrss_%'
          AND LOWER(link_id) NOT IN ('github','linkedin','x','twitter','facebook','instagram','tiktok','youtube','pinterest','whatsapp')
        GROUP BY date_str
        ORDER BY date_str ASC
      ");
      $stmtDayMonthClicks->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $rawDayClicks = $stmtDayMonthClicks->fetchAll() ?: [];

      $clicksMap = [];
      foreach ($rawDayClicks as $c) {
        $clicksMap[$c['date_str']] = (int)$c['total_clicks'];
      }

      $viewsByDayOfMonth = [];
      foreach ($rawDayViews as $v) {
        $dateStr = $v['date_str'];
        $viewsByDayOfMonth[] = [
          'date_str'     => $dateStr,
          'day_num'      => $v['day_num'],
          'total'        => (int)$v['total_views'],
          'total_views'  => (int)$v['total_views'],
          'unique_views' => (int)($v['unique_views'] ?? 0),
          'total_clicks' => $clicksMap[$dateStr] ?? 0
        ];
      }

      // 4. Desglose de Visitas y Visitas Únicas por Semana del Mes Activo
      $stmtWeekMonth = $pdo->prepare("
        SELECT strftime('%W', created_at) as week_num, COUNT(*) as total, COUNT(DISTINCT ip_address) as unique_total
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
          'week_label'   => "Semana " . ($idx + 1),
          'week_num'     => $w['week_num'],
          'total'        => (int)$w['total'],
          'unique_total' => (int)($w['unique_total'] ?? 0)
        ];
      }

      // 5. Top Enlaces más Clicados del Mes Activo (Solo Enlaces del Widget de Contenido)
      $stmtTopLinks = $pdo->prepare("
        SELECT link_id, COUNT(*) as total
        FROM link_clicks
        WHERE profile_id = :user 
          AND strftime('%Y-%m', created_at) = :activeMonth
          AND link_id NOT LIKE 'rrss_%'
          AND LOWER(link_id) NOT IN ('github','linkedin','x','twitter','facebook','instagram','tiktok','youtube','pinterest','whatsapp')
        GROUP BY link_id
        ORDER BY total DESC
      ");
      $stmtTopLinks->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $rawTopLinks = $stmtTopLinks->fetchAll() ?: [];

      $processedClicks = [];
      $activeContentCount = count($userContent);

      foreach ($rawTopLinks as $l) {
        $rawId = trim($l['link_id'] ?? '');
        if (empty($rawId)) continue;
        if (strpos($rawId, 'rrss_') === 0 || in_array(strtolower($rawId), ['github','linkedin','x','twitter','facebook','instagram','tiktok','youtube','pinterest','whatsapp'])) {
          continue; // Excluir redes sociales de los enlaces del widget
        }

        if (isset($linkTitleMap[$rawId]) && strpos($rawId, 'rrss_') !== 0 && !in_array(strtolower($rawId), ['github','linkedin','x','twitter','facebook','instagram','tiktok','youtube','pinterest','whatsapp'])) {
          $realName = $linkTitleMap[$rawId];
        } else {
          if (preg_match('/(\d+)/', $rawId, $matches)) {
            $num = (int)$matches[1];
            if (isset($userContent[$num - 1]['title'])) {
              $realName = $userContent[$num - 1]['title'];
            } elseif (isset($userContent[$num]['title'])) {
              $realName = $userContent[$num]['title'];
            } else {
              if ($activeContentCount > 0 && $num > $activeContentCount) {
                continue;
              }
              $realName = "Enlace " . $num;
            }
          } else {
            $realName = ucwords(str_replace(['_', '-'], ' ', $rawId));
          }
        }

        $displayName = (mb_strlen($realName, 'UTF-8') > 22) ? mb_strimwidth($realName, 0, 20, '...', 'UTF-8') : $realName;

        if (!isset($processedClicks[$displayName])) {
          $processedClicks[$displayName] = 0;
        }
        $processedClicks[$displayName] += (int)$l['total'];
      }

      arsort($processedClicks);

      $rawFormattedLinks = [];
      foreach ($processedClicks as $name => $totalClicks) {
        $rawFormattedLinks[] = [
          'link_id'   => $name,
          'link_name' => $name,
          'total'     => $totalClicks
        ];
      }

      // Regla de Top 9 + "Otros Enlaces"
      $topLinks = [];
      if (count($rawFormattedLinks) > 10) {
        $top9 = array_slice($rawFormattedLinks, 0, 9);
        foreach ($top9 as $l) {
          $topLinks[] = $l;
        }

        $others = array_slice($rawFormattedLinks, 9);
        $othersTotal = 0;
        foreach ($others as $o) {
          $othersTotal += (int)($o['total'] ?? 0);
        }

        if ($othersTotal > 0) {
          $topLinks[] = [
            'link_id'   => 'otros',
            'link_name' => 'Otros Enlaces',
            'total'     => $othersTotal
          ];
        }
      } else {
        $topLinks = $rawFormattedLinks;
      }

      // 5.5 Clics Exclusivos en Redes Sociales (RRSS)
      $stmtTopRrss = $pdo->prepare("
        SELECT link_id, COUNT(*) as total
        FROM link_clicks
        WHERE profile_id = :user 
          AND strftime('%Y-%m', created_at) = :activeMonth
          AND (link_id LIKE 'rrss_%' OR LOWER(link_id) IN ('github','linkedin','x','twitter','facebook','instagram','tiktok','youtube','pinterest','whatsapp'))
        GROUP BY link_id
        ORDER BY total DESC
      ");
      $stmtTopRrss->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $rawTopRrss = $stmtTopRrss->fetchAll() ?: [];

      $rrssClicksMap = [];
      $totalRrssClicks = 0;
      foreach ($rawTopRrss as $l) {
        $rawId = trim($l['link_id'] ?? '');
        if (empty($rawId)) continue;

        $netKey = str_replace('rrss_', '', strtolower($rawId));
        $netTitle = $netNamesMap[$netKey] ?? (ucwords($netKey) . " (Red Social)");
        $displayName = (mb_strlen($netTitle, 'UTF-8') > 22) ? mb_strimwidth($netTitle, 0, 20, '...', 'UTF-8') : $netTitle;

        if (!isset($rrssClicksMap[$displayName])) {
          $rrssClicksMap[$displayName] = 0;
        }
        $val = (int)$l['total'];
        $rrssClicksMap[$displayName] += $val;
        $totalRrssClicks += $val;
      }

      arsort($rrssClicksMap);
      $rrssLinks = [];
      foreach ($rrssClicksMap as $name => $tot) {
        $rrssLinks[] = [
          'link_id'   => $name,
          'link_name' => $name,
          'total'     => $tot
        ];
      }

      // 6. Desglose por tipo de dispositivo del Mes Activo
      $stmtDevices = $pdo->prepare("
        SELECT device_type, COUNT(*) as total 
        FROM profile_views 
        WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth 
        GROUP BY device_type 
        ORDER BY total DESC
      ");
      $stmtDevices->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $devices = $stmtDevices->fetchAll() ?: [];

      // 7. Desglose por navegador del Mes Activo
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

      // 8. Desglose por ubicación geográfica (Países) del Mes Activo (Top 10)
      $stmtCountries = $pdo->prepare("
        SELECT country_name, country_code, COUNT(*) as total 
        FROM profile_views 
        WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth 
        GROUP BY country_name 
        ORDER BY total DESC 
        LIMIT 10
      ");
      $stmtCountries->execute([":user" => $userClean, ":activeMonth" => $activeMonth]);
      $countries = $stmtCountries->fetchAll() ?: [];

      // 9. Desglose por fuentes de tráfico (Referrers) del Mes Activo
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

      // 10. Desglose por Días de la Semana del Mes Activo
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

      // 11. Desglose por Horarios de Mayor Tráfico del Mes Activo
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

      // 12. Recomendación Inteligente de Horario y Día Pico del Mes Activo
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

      // 13. Métricas detalladas por Red Social del Mes Activo con Desglose Semanal (Stacked Column)
      $socialStats = self::getSocialNetworksStats($pdo, $userClean, $activeMonth);

      // 14. Registros recientes para depuración
      $stmtRecentViews = $pdo->prepare("SELECT ip_address, country_name, device_type, os, browser, referrer, created_at FROM profile_views WHERE profile_id = :user ORDER BY id DESC LIMIT 5");
      $stmtRecentViews->execute([":user" => $userClean]);
      $recentViews = $stmtRecentViews->fetchAll() ?: [];

      return [
        "summary"             => $monthlySummary,
        "allTimeSummary"      => $allTimeSummary,
        "viewsByDayOfMonth"   => $viewsByDayOfMonth,
        "viewsByWeekOfMonth"  => $viewsByWeekOfMonth,
        "topLinks"            => $topLinks,
        "totalRrssClicks"     => $totalRrssClicks,
        "rrssLinks"           => $rrssLinks,
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
        "allTimeSummary"      => ['total_views' => 0, 'unique_views' => 0, 'total_clicks' => 0],
        "viewsByDayOfMonth"   => [],
        "viewsByWeekOfMonth"  => [],
        "topLinks"            => [],
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
   * Obtiene desgloses detallados por Red Social del Mes Activo incluyendo datos para gráfico de columnas apiladas (Stacked Column) por día y rango de horario.
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

    // Días de la semana en orden de lunes a domingo
    $daysOrderKeys = ["1", "2", "3", "4", "5", "6", "0"];
    $dayLabels     = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"];

    $timeSlots = [
      'Mañana (06:00-12:00)',
      'Tarde (12:00-18:00)',
      'Noche (18:00-00:00)',
      'Madrugada (00:00-06:00)'
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

      // Obtener desglose por día de la semana y rangos de horarios para el Stacked Column chart
      if ($key === 'direct') {
        $stmtDetail = $pdo->prepare("
          SELECT 
            strftime('%w', created_at) as day_num,
            CASE 
              WHEN CAST(strftime('%H', created_at) AS INTEGER) BETWEEN 6 AND 11 THEN 'Mañana (06:00-12:00)'
              WHEN CAST(strftime('%H', created_at) AS INTEGER) BETWEEN 12 AND 17 THEN 'Tarde (12:00-18:00)'
              WHEN CAST(strftime('%H', created_at) AS INTEGER) BETWEEN 18 AND 23 THEN 'Noche (18:00-00:00)'
              ELSE 'Madrugada (00:00-06:00)'
            END as time_slot,
            COUNT(*) as total
          FROM profile_views 
          WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth AND (referrer IS NULL OR referrer = '' OR referrer = 'Tráfico Directo')
          GROUP BY day_num, time_slot
        ");
        $stmtDetail->execute([':user' => $userClean, ':activeMonth' => $activeMonth]);
      } else {
        $stmtDetail = $pdo->prepare("
          SELECT 
            strftime('%w', created_at) as day_num,
            CASE 
              WHEN CAST(strftime('%H', created_at) AS INTEGER) BETWEEN 6 AND 11 THEN 'Mañana (06:00-12:00)'
              WHEN CAST(strftime('%H', created_at) AS INTEGER) BETWEEN 12 AND 17 THEN 'Tarde (12:00-18:00)'
              WHEN CAST(strftime('%H', created_at) AS INTEGER) BETWEEN 18 AND 23 THEN 'Noche (18:00-00:00)'
              ELSE 'Madrugada (00:00-06:00)'
            END as time_slot,
            COUNT(*) as total
          FROM profile_views 
          WHERE profile_id = :user AND strftime('%Y-%m', created_at) = :activeMonth AND ({$whereClause})
          GROUP BY day_num, time_slot
        ");
        $stmtDetail->execute($params);
      }

      $rawDetails = $stmtDetail->fetchAll() ?: [];

      // Mapear matriz [slot][dayIndex]
      $slotData = [];
      foreach ($timeSlots as $slot) {
        $slotData[$slot] = array_fill(0, 7, 0);
      }

      foreach ($rawDetails as $rd) {
        $dNum = (string)$rd['day_num'];
        $slot = $rd['time_slot'];
        $countVal = (int)$rd['total'];

        $dayIndex = array_search($dNum, $daysOrderKeys);
        if ($dayIndex !== false && isset($slotData[$slot])) {
          $slotData[$slot][$dayIndex] = $countVal;
        }
      }

      $stackedSeries = [];
      foreach ($timeSlots as $slot) {
        $stackedSeries[] = [
          'name' => $slot,
          'data' => $slotData[$slot]
        ];
      }

      $socialStats[] = [
        'key'            => $key,
        'name'           => $net['name'],
        'icon'           => $net['icon'],
        'total'          => $total,
        'bestDay'        => $bestDayName,
        'bestHour'       => $bestHourLabel,
        'dayLabels'      => $dayLabels,
        'stackedSeries'  => $stackedSeries
      ];
    }

    usort($socialStats, fn($a, $b) => $b['total'] <=> $a['total']);

    return $socialStats;
  }

  /**
   * Genera un lote rico de registros simulados de prueba (por defecto 20 visitas y clics)
   * distribuidos a lo largo del mes actual según date.timezone de PHP para alimentar todas las estadísticas.
   *
   * @param string $user Nombre de usuario objetivo.
   * @param int $count Cantidad de visitas simuladas a insertar por clic (por defecto 20).
   * @return bool True si los registros fueron insertados con éxito.
   */
  public static function generateTestData(string $user, int $count = 20): bool {
    $userClean = mb_strtolower($user, "UTF-8");

    $userData    = DesignModels::dataUser($userClean);
    $userContent = $userData['card']['content'] ?? [];
    $userRrss    = $userData['card']['rrss'] ?? [];

    $possibleTargets = [];

    // 1. Incluir todos los enlaces de contenido principal
    foreach ($userContent as $idx => $item) {
      $possibleTargets[] = "enlace_" . ($idx + 1);
    }

    // 2. Incluir todas las redes sociales configuradas
    foreach ($userRrss as $rItem) {
      $netKey = strtolower(trim($rItem[0] ?? ''));
      if (!empty($netKey)) {
        $possibleTargets[] = "rrss_" . $netKey;
      }
    }

    if (empty($possibleTargets)) {
      $possibleTargets = ["enlace_1", "enlace_2", "enlace_3", "rrss_github", "rrss_linkedin"];
    }

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

      // Generar clic en 75% de las visitas simuladas distribuyendo entre TODOS los enlaces (Contenido + Redes Sociales)
      if (rand(1, 100) <= 75) {
        $randomTarget = $possibleTargets[array_rand($possibleTargets)];
        AnalyticsModule::logLinkClick($userClean, $randomTarget, [
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
