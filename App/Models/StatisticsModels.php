<?php

namespace App\Models;

use Base\Builder\BuilderSqlite;
use Base\Module\DateTimeModule;

/**
 * Clase StatisticsModels
 * 
 * Modelo encargado de la consulta y procesamiento de estadísticas y métricas de perfil.
 * Todas las consultas se estructuran bajo los métodos fluidos de BuilderSqlite y se referencian
 * estrictamente mediante el index_user del usuario para garantizar seguridad, integridad y aislamiento de datos.
 */
class StatisticsModels extends BuilderSqlite {

  protected $table = "profile_views";

  /**
   * Obtiene la estructura completa de datos estadísticos para un perfil.
   *
   * @param string $userOrIndex Nombre de usuario o index_user.
   * @return array Resumen de estadísticas del perfil.
   */
  public static function getStatsData(string $userOrIndex): array {
    $cleanParam = trim($userOrIndex);
    $userClean  = mb_strtolower($cleanParam, "UTF-8");

    // Resolver index_user a partir del username o usar directamente si ya es index_user
    $indexUser = UserModels::getIndexUserByUsername($userClean) ?? $cleanParam;

    $summary = self::getSummaryByIndexUser($indexUser);
    $monthly = self::getMonthlyViewsData($indexUser);
    $cto     = self::getCtoData($indexUser);

    return [
      "user"           => $userClean,
      "index_user"     => $indexUser,
      "summary"        => $summary,
      "monthly_views"  => $monthly,
      "cto"            => $cto,
      "visitors"       => self::getRecentVisitors($indexUser, 10),
      "geo"            => self::getGeoRanking($indexUser, 10),
      "referrers"      => self::getReferrersSummary($indexUser),
      "heatmap"        => self::getHeatmapData($indexUser),
      "links_detailed" => self::getLinksDetailedStats($indexUser)
    ];
  }

  /**
   * Obtiene el número total de clics registrados para el usuario en el mes actual.
   *
   * @param string $indexUser Identificador único index_user del usuario.
   * @return int Total de clics en el mes en curso.
   */
  public static function getCurrentMonthClicks(string $indexUser): int {
    try {
      $startOfMonth = date("Y-m-01 00:00:00");
      $endOfMonth   = date("Y-m-t 23:59:59");

      return (int)(new self("link_clicks"))
        ->where("index_user", $indexUser)
        ->whereBetween("created_at", [$startOfMonth, $endOfMonth])
        ->count();
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getCurrentMonthClicks: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Obtiene el desglose de clics del mes actual clasificados por tipo:
   * enlaces regulares (contenido, botones, productos) y redes sociales (rrss_*).
   *
   * @param string $indexUser Identificador único index_user del usuario.
   * @return array Desglose con claves 'enlaces', 'rrss' y 'total'.
   */
  public static function getCurrentMonthClicksByType(string $indexUser): array {
    try {
      $startOfMonth = date("Y-m-01 00:00:00");
      $endOfMonth   = date("Y-m-t 23:59:59");

      $enlaces = (int)(new self("link_clicks"))
        ->where("index_user", $indexUser)
        ->where("link_id", "NOT LIKE", "rrss_%")
        ->whereBetween("created_at", [$startOfMonth, $endOfMonth])
        ->count();

      $rrss = (int)(new self("link_clicks"))
        ->where("index_user", $indexUser)
        ->where("link_id", "LIKE", "rrss_%")
        ->whereBetween("created_at", [$startOfMonth, $endOfMonth])
        ->count();

      return [
        "enlaces" => $enlaces,
        "rrss"    => $rrss,
        "total"   => $enlaces + $rrss
      ];
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getCurrentMonthClicksByType: " . $e->getMessage());
      return ["enlaces" => 0, "rrss" => 0, "total" => 0];
    }
  }

  /**
   * Obtiene el desglose histórico acumulado de clics clasificados por tipo:
   * enlaces regulares y redes sociales.
   *
   * @param string $indexUser Identificador único index_user del usuario.
   * @return array Desglose acumulado con claves 'enlaces', 'rrss' y 'total'.
   */
  public static function getAllTimeClicksByType(string $indexUser): array {
    try {
      $enlaces = (int)(new self("link_clicks"))
        ->where("index_user", $indexUser)
        ->where("link_id", "NOT LIKE", "rrss_%")
        ->count();

      $rrss = (int)(new self("link_clicks"))
        ->where("index_user", $indexUser)
        ->where("link_id", "LIKE", "rrss_%")
        ->count();

      return [
        "enlaces" => $enlaces,
        "rrss"    => $rrss,
        "total"   => $enlaces + $rrss
      ];
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getAllTimeClicksByType: " . $e->getMessage());
      return ["enlaces" => 0, "rrss" => 0, "total" => 0];
    }
  }

  /**
   * Obtiene el total de enlaces configurados que realmente existen por este usuario en su tarjeta de diseño
   * sumando los bloques de contenido ($card["content"]) y redes sociales ($card["rrss"]).
   *
   * @param string $indexUser Identificador único index_user del usuario.
   * @return array Conteo de content_links, rrss_links y total_links.
   */
  public static function getUserConfiguredLinksCount(string $indexUser): array {
    try {
      $userRecord = UserModels::getUserByIndex($indexUser);
      $username   = $userRecord["username"] ?? "";

      $card = [];
      if (!empty($username)) {
        $dataUser = DesignModels::dataUser($username);
        if (is_array($dataUser) && isset($dataUser["card"]) && is_array($dataUser["card"])) {
          $card = $dataUser["card"];
        }
      }

      // Si no se obtuvo mediante dataUser, consultar directamente por index_user en user_designs
      if (empty($card)) {
        $designRow = (new self("user_designs"))
          ->where("index_user", $indexUser)
          ->where("is_draft", 0)
          ->get_one();

        if ($designRow && !empty($designRow[0])) {
          $rawContent = $designRow[0]["content"] ?? [];
          $rawRrss    = $designRow[0]["rrss"] ?? [];
          $content    = is_string($rawContent) ? json_decode($rawContent, true) : $rawContent;
          $rrss       = is_string($rawRrss) ? json_decode($rawRrss, true) : $rawRrss;

          $card = [
            "content" => is_array($content) ? $content : [],
            "rrss"    => is_array($rrss) ? $rrss : []
          ];
        }
      }

      $content = is_array($card["content"] ?? null) ? $card["content"] : [];
      $rrss    = is_array($card["rrss"] ?? null) ? $card["rrss"] : [];

      $contentLinks = count($content);
      $rrssLinks    = count($rrss);
      $totalLinks   = $contentLinks + $rrssLinks;

      return [
        "content_links" => $contentLinks,
        "rrss_links"    => $rrssLinks,
        "total_links"   => $totalLinks
      ];
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getUserConfiguredLinksCount: " . $e->getMessage());
      return ["content_links" => 0, "rrss_links" => 0, "total_links" => 0];
    }
  }

  /**
   * Obtiene la cantidad total de enlaces que realmente existen configurados por el usuario ($card["rrss"] + $card["content"]).
   *
   * @param string $indexUser Identificador único index_user del usuario.
   * @return int Total de enlaces existentes.
   */
  public static function getTotalUserLinks(string $indexUser): int {
    $links = self::getUserConfiguredLinksCount($indexUser);
    return (int)($links["total_links"] ?? 0);
  }

  /**
   * Obtiene la estructura completa de datos requerida para el cálculo de CTO y métricas de clics.
   *
   * @param string $indexUser Identificador único index_user del usuario.
   * @return array Datos brutos de visitas, clics, desglose por tipo y enlaces configurados.
   */
  public static function getCtoData(string $indexUser): array {
    $monthlyViews       = self::getCurrentMonthViews($indexUser);
    $monthlyClicksData  = self::getCurrentMonthClicksByType($indexUser);
    $allTimeClicksData  = self::getAllTimeClicksByType($indexUser);
    $configuredLinks    = self::getUserConfiguredLinksCount($indexUser);
    $allTimeViews       = (int)(new self("profile_views"))->where("index_user", $indexUser)->count();
    $monthLabel         = ucfirst(DateTimeModule::formatSpanish(date("Y-m-d"), "F Y"));

    return [
      "monthly_views"    => $monthlyViews,
      "monthly_clicks"   => $monthlyClicksData["total"],
      "monthly_enlaces"  => $monthlyClicksData["enlaces"],
      "monthly_rrss"     => $monthlyClicksData["rrss"],
      "all_time_views"   => $allTimeViews,
      "all_time_clicks"  => $allTimeClicksData["total"],
      "all_time_enlaces" => $allTimeClicksData["enlaces"],
      "all_time_rrss"    => $allTimeClicksData["rrss"],
      "configured_links" => $configuredLinks,
      "month_label"      => $monthLabel
    ];
  }

  /**
   * Obtiene el número total de visitas del perfil durante el mes actual en curso.
   *
   * @param string $indexUser Identificador único index_user del usuario.
   * @return int Total de visitas registradas en el mes actual.
   */
  public static function getCurrentMonthViews(string $indexUser): int {
    try {
      $startOfMonth = date("Y-m-01 00:00:00");
      $endOfMonth   = date("Y-m-t 23:59:59");

      return (int)(new self("profile_views"))
        ->where("index_user", $indexUser)
        ->whereBetween("created_at", [$startOfMonth, $endOfMonth])
        ->count();
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getCurrentMonthViews: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Obtiene el total de visitas de cada uno de los meses anteriores al mes actual,
   * agrupados por año y mes cronológico.
   *
   * @param string $indexUser Identificador único index_user del usuario.
   * @return array Lista de meses anteriores con su respectivo total de visitas.
   */
  public static function getPreviousMonthsViews(string $indexUser): array {
    try {
      $startOfMonth = date("Y-m-01 00:00:00");

      $rows = (new self("profile_views"))
        ->select("strftime('%Y-%m', created_at) as mes")
        ->count("*", "total")
        ->where("index_user", $indexUser)
        ->where("created_at", "<", $startOfMonth)
        ->group("mes")
        ->order("mes", "ASC")
        ->get();

      return is_array($rows) ? $rows : [];
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getPreviousMonthsViews: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Obtiene las métricas del mes actual, el desglose de meses anteriores
   * y calcula el promedio mensual histórico de los meses anteriores.
   *
   * @param string $indexUser Identificador único index_user del usuario.
   * @return array Estructura de datos con total actual, meses previos y promedio histórico.
   */
  public static function getMonthlyViewsData(string $indexUser): array {
    $currentTotal    = self::getCurrentMonthViews($indexUser);
    $previousMonths  = self::getPreviousMonthsViews($indexUser);
    $previousCount   = count($previousMonths);
    $previousSum     = 0;

    foreach ($previousMonths as $row) {
      $previousSum += (int)($row["total"] ?? 0);
    }

    $previousAvg = $previousCount > 0 ? round($previousSum / $previousCount, 1) : 0.0;
    $monthLabel  = ucfirst(DateTimeModule::formatSpanish(date("Y-m-d"), "F Y"));

    return [
      "current_month_total"   => $currentTotal,
      "current_month_label"   => $monthLabel,
      "previous_months"       => $previousMonths,
      "previous_months_count" => $previousCount,
      "previous_months_sum"   => $previousSum,
      "previous_months_avg"   => $previousAvg,
      "has_history"           => $previousCount > 0
    ];
  }

  /**
   * Obtiene el resumen de métricas clave (total_views, unique_views, total_clicks, ctr)
   * consultando por index_user mediante BuilderSqlite.
   *
   * @param string $indexUser Identificador único index_user del usuario.
   * @return array Array con total_views, unique_views, total_clicks y ctr.
   */
  public static function getSummaryByIndexUser(string $indexUser): array {
    try {
      $totalViews = (int)(new self("profile_views"))
        ->where("index_user", $indexUser)
        ->count();

      $uniqueRows = (new self("profile_views"))
        ->select("COUNT(DISTINCT ip_address) as unique_views")
        ->where("index_user", $indexUser)
        ->get();
      $uniqueViews = (int)($uniqueRows[0]["unique_views"] ?? 0);

      $totalClicks = (int)(new self("link_clicks"))
        ->where("index_user", $indexUser)
        ->count();

      $ctr = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 2) : 0.0;

      return [
        'total_views'  => $totalViews,
        'unique_views' => $uniqueViews,
        'total_clicks' => $totalClicks,
        'ctr'          => $ctr
      ];
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getSummaryByIndexUser: " . $e->getMessage());
      return ['total_views' => 0, 'unique_views' => 0, 'total_clicks' => 0, 'ctr' => 0];
    }
  }

  /**
   * Obtiene la cronología de visitas agrupadas por fecha para generación de gráficos temporales.
   *
   * @param string $indexUser Identificador index_user del usuario.
   * @param int $days Cantidad de días hacia atrás a consultar (por defecto 30).
   * @return array Lista de registros [date, views, unique_ips].
   */
  public static function getViewsTimeline(string $indexUser, int $days = 30): array {
    try {
      $sql = "
        SELECT 
          strftime('%Y-%m-%d', created_at) as date,
          COUNT(*) as views,
          COUNT(DISTINCT ip_address) as unique_views
        FROM profile_views
        WHERE (index_user = ? OR profile_id = ?)
          AND created_at >= datetime('now', '-' || ? || ' days')
        GROUP BY date
        ORDER BY date ASC
      ";
      return (new self())->rawQuery($sql, [$indexUser, $indexUser, $days]);
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getViewsTimeline: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Obtiene la cronología de clics en enlaces agrupados por fecha.
   *
   * @param string $indexUser Identificador index_user del usuario.
   * @param int $days Cantidad de días hacia atrás a consultar (por defecto 30).
   * @return array Lista de registros [date, clicks].
   */
  public static function getClicksTimeline(string $indexUser, int $days = 30): array {
    try {
      $sql = "
        SELECT 
          strftime('%Y-%m-%d', created_at) as date,
          COUNT(*) as clicks
        FROM link_clicks
        WHERE (index_user = ? OR profile_id = ?)
          AND created_at >= datetime('now', '-' || ? || ' days')
        GROUP BY date
        ORDER BY date ASC
      ";
      return (new self())->rawQuery($sql, [$indexUser, $indexUser, $days]);
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getClicksTimeline: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Obtiene el desglose de clics recibidos por cada enlace individual del usuario.
   *
   * @param string $indexUser Identificador index_user del usuario.
   * @return array Lista de enlaces con su recuento de clics.
   */
  public static function getClicksByLink(string $indexUser): array {
    try {
      $sql = "
        SELECT 
          link_id,
          COUNT(*) as total_clicks
        FROM link_clicks
        WHERE (index_user = ? OR profile_id = ?)
        GROUP BY link_id
        ORDER BY total_clicks DESC
      ";
      return (new self())->rawQuery($sql, [$indexUser, $indexUser]);
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getClicksByLink: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Obtiene la distribución de dispositivos (desktop, mobile, tablet) de los visitantes.
   *
   * @param string $indexUser Identificador index_user del usuario.
   * @return array Distribución por tipo de dispositivo.
   */
  public static function getDeviceBreakdown(string $indexUser): array {
    try {
      $sql = "
        SELECT 
          COALESCE(NULLIF(device_type, ''), 'desktop') as device,
          COUNT(*) as count
        FROM profile_views
        WHERE (index_user = ? OR profile_id = ?)
        GROUP BY device
        ORDER BY count DESC
      ";
      return (new self())->rawQuery($sql, [$indexUser, $indexUser]);
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getDeviceBreakdown: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Obtiene el desglose de visitantes agrupados por país.
   *
   * @param string $indexUser Identificador index_user del usuario.
   * @param int $limit Cantidad máxima de países a retornar.
   * @return array Lista de países con su respectivo conteo.
   */
  public static function getCountryBreakdown(string $indexUser, int $limit = 10): array {
    try {
      $sql = "
        SELECT 
          country_code,
          country_name,
          COUNT(*) as count
        FROM profile_views
        WHERE (index_user = ? OR profile_id = ?)
          AND country_code != 'DEV'
        GROUP BY country_code, country_name
        ORDER BY count DESC
        LIMIT ?
      ";
      return (new self())->rawQuery($sql, [$indexUser, $indexUser, $limit]);
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getCountryBreakdown: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Obtiene las fuentes de referencia (referrers) principales de las visitas.
   *
   * @param string $indexUser Identificador index_user del usuario.
   * @param int $limit Cantidad máxima de fuentes a retornar.
   * @return array Lista de referrers con su recuento.
   */
  public static function getReferrerBreakdown(string $indexUser, int $limit = 10): array {
    try {
      $sql = "
        SELECT 
          COALESCE(NULLIF(referrer, ''), 'Directo') as referrer_source,
          COUNT(*) as count
        FROM profile_views
        WHERE (index_user = ? OR profile_id = ?)
        GROUP BY referrer_source
        ORDER BY count DESC
        LIMIT ?
      ";
      return (new self())->rawQuery($sql, [$indexUser, $indexUser, $limit]);
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getReferrerBreakdown: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Obtiene la lista de usuarios registrados que han visitado recientemente el perfil.
   * Agrupa por visitante para obtener la fecha de última visita y conteo de interacciones,
   * excluyendo auto-visitas y respetando a aquellos visitantes con Modo Incógnito (visitor_user_id IS NULL).
   *
   * @param string $indexUser Identificador único index_user del perfil visitado.
   * @param int $limit Cantidad máxima de visitantes a retornar (por defecto 10).
   * @return array Lista de visitantes con avatar, username, nombre para mostrar, fecha relativa y visitas.
   */
  public static function getRecentVisitors(string $indexUser, int $limit = 10): array {
    try {
      $cleanIndex = trim($indexUser);
      if (empty($cleanIndex)) {
        return [];
      }

      $sql = "
        SELECT 
          pv.visitor_user_id,
          MAX(pv.created_at) as last_visit,
          COUNT(pv.id) as visit_count,
          u.username,
          u.full_name,
          COALESCE(ud.avatar, 'no-user.webp') as avatar,
          COALESCE(NULLIF(ud.title, ''), NULLIF(u.full_name, ''), u.username) as display_name
        FROM profile_views pv
        INNER JOIN users u ON u.index_user = pv.visitor_user_id
        LEFT JOIN user_designs ud ON ud.index_user = pv.visitor_user_id AND ud.is_draft = 0
        WHERE (pv.index_user = ? OR pv.profile_id = ?)
          AND pv.visitor_user_id IS NOT NULL
          AND pv.visitor_user_id != ''
          AND pv.visitor_user_id != ?
        GROUP BY pv.visitor_user_id
        ORDER BY last_visit DESC
        LIMIT ?
      ";
      $rows = (new self())->rawQuery($sql, [$cleanIndex, $cleanIndex, $cleanIndex, $limit]);
      $visitors = [];

      $defaultAvatar = defined("DIR_UPLOAD_MEDIA_STATIC") 
        ? DIR_UPLOAD_MEDIA_STATIC . "Custom/no-user.webp" 
        : "/Rsc/Library/Custom/no-user.webp";

      foreach ($rows as $row) {
        $avatarVal = $row["avatar"] ?? "no-user.webp";
        $isDefault = (empty($avatarVal) || $avatarVal === "no-user.webp" || strpos($avatarVal, "Origin/") !== false || strpos($avatarVal, "Custom/") !== false);
        
        $avatarUrl = $defaultAvatar;
        if (!$isDefault) {
          $diskFile = defined("ROOT_PATH") ? ROOT_PATH . "/Uploads/Avatar/" . $avatarVal : null;
          if ($diskFile && file_exists($diskFile)) {
            $avatarUrl = defined("DIR_SHOW_MEDIA") ? DIR_SHOW_MEDIA . "Avatar/" . $avatarVal : "/Uploads/Avatar/" . $avatarVal;
          }
        }

        $lastVisitTime = (string)($row["last_visit"] ?? "");
        $timeAgo = !empty($lastVisitTime) ? DateTimeModule::timeAgo($lastVisitTime) : "Recientemente";

        $visitors[] = [
          "index_user"   => (string)$row["visitor_user_id"],
          "username"     => (string)$row["username"],
          "display_name" => (string)($row["display_name"] ?: $row["username"]),
          "avatar"       => $avatarUrl,
          "profile_url"  => "/" . rawurlencode((string)$row["username"]),
          "last_visit"   => $lastVisitTime,
          "time_ago"     => $timeAgo,
          "visit_count"  => (int)($row["visit_count"] ?? 1)
        ];
      }

      return $visitors;
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getRecentVisitors: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Obtiene el ranking detallado de geolocalización por países y principales ciudades.
   * Calcula totales, porcentajes normalizados y flags de países.
   *
   * @param string $indexUser Identificador único index_user del perfil.
   * @param int $limit Cantidad máxima de registros por ranking (por defecto 10).
   * @return array Estructura con 'countries', 'cities', 'total_geo_views' y 'unique_countries'.
   */
  public static function getGeoRanking(string $indexUser, int $limit = 10): array {
    try {
      $cleanIndex = trim($indexUser);

      // 1. Total de visitas con país detectado
      $totalGeo = (int)(new self("profile_views"))
        ->where(function ($q) use ($cleanIndex) {
          $q->where("index_user", $cleanIndex)->orWhere("profile_id", $cleanIndex);
        })
        ->whereNotNull("country_code")
        ->where("country_code", "!=", "")
        ->where("country_code", "!=", "DEV")
        ->count();

      // 2. Ranking de Países
      $sqlCountries = "
        SELECT 
          country_code,
          country_name,
          COUNT(*) as count
        FROM profile_views
        WHERE (index_user = ? OR profile_id = ?)
          AND country_code IS NOT NULL
          AND country_code != ''
          AND country_code != 'DEV'
        GROUP BY country_code, country_name
        ORDER BY count DESC
        LIMIT ?
      ";
      $countryRows = (new self())->rawQuery($sqlCountries, [$cleanIndex, $cleanIndex, $limit]);

      $countries = [];
      foreach ($countryRows as $row) {
        $cnt  = (int)$row["count"];
        $pct  = $totalGeo > 0 ? round(($cnt / $totalGeo) * 100, 1) : 0.0;
        $code = strtoupper(trim((string)$row["country_code"]));
        $name = trim((string)$row["country_name"]);

        $countries[] = [
          "country_code" => $code,
          "country_name" => !empty($name) ? $name : $code,
          "count"        => $cnt,
          "percentage"   => $pct,
          "flag"         => strtolower($code)
        ];
      }

      // 3. Ranking de Ciudades
      $sqlCities = "
        SELECT 
          city_name,
          country_code,
          country_name,
          COUNT(*) as count
        FROM profile_views
        WHERE (index_user = ? OR profile_id = ?)
          AND city_name IS NOT NULL
          AND city_name != ''
          AND city_name != 'Local'
          AND city_name != 'Desconocida'
        GROUP BY city_name, country_code
        ORDER BY count DESC
        LIMIT ?
      ";
      $cityRows = (new self())->rawQuery($sqlCities, [$cleanIndex, $cleanIndex, $limit]);

      $cities = [];
      foreach ($cityRows as $row) {
        $cnt = (int)$row["count"];
        $pct = $totalGeo > 0 ? round(($cnt / $totalGeo) * 100, 1) : 0.0;
        $cities[] = [
          "city_name"    => trim((string)$row["city_name"]),
          "country_code" => strtoupper(trim((string)$row["country_code"])),
          "country_name" => trim((string)$row["country_name"]),
          "count"        => $cnt,
          "percentage"   => $pct
        ];
      }

      return [
        "total_geo_views"  => $totalGeo,
        "unique_countries" => count($countries),
        "countries"        => $countries,
        "cities"           => $cities,
        "top_country"      => $countries[0] ?? null
      ];
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getGeoRanking: " . $e->getMessage());
      return [
        "total_geo_views"  => 0,
        "unique_countries" => 0,
        "countries"        => [],
        "cities"           => [],
        "top_country"      => null
      ];
    }
  }

  /**
   * Obtiene y clasifica las fuentes de tráfico (referrers) en plataformas reconocibles
   * (Instagram, TikTok, WhatsApp, X/Twitter, Facebook, YouTube, Buscadores, Tráfico Directo y Otros).
   *
   * @param string $indexUser Identificador único index_user del perfil.
   * @return array Desglose clasificado por plataforma, porcentajes y plataforma predominante.
   */
  public static function getReferrersSummary(string $indexUser): array {
    try {
      $cleanIndex = trim($indexUser);
      $sql = "
        SELECT 
          COALESCE(NULLIF(referrer, ''), 'direct') as ref,
          COUNT(*) as count
        FROM profile_views
        WHERE (index_user = ? OR profile_id = ?)
        GROUP BY ref
        ORDER BY count DESC
      ";
      $rows = (new self())->rawQuery($sql, [$cleanIndex, $cleanIndex]);

      // Plataformas estándar con sus definiciones de matching e icono
      $categories = [
        "instagram" => ["name" => "Instagram", "icon" => "instagram", "count" => 0, "domains" => ["instagram.com", "l.instagram.com"]],
        "tiktok"    => ["name" => "TikTok", "icon" => "tiktok", "count" => 0, "domains" => ["tiktok.com"]],
        "whatsapp"  => ["name" => "WhatsApp", "icon" => "whatsapp", "count" => 0, "domains" => ["whatsapp.com", "wa.me", "api.whatsapp.com"]],
        "x"         => ["name" => "X (Twitter)", "icon" => "x", "count" => 0, "domains" => ["twitter.com", "t.co", "x.com"]],
        "facebook"  => ["name" => "Facebook", "icon" => "facebook", "count" => 0, "domains" => ["facebook.com", "l.facebook.com", "m.facebook.com", "fb.me"]],
        "youtube"   => ["name" => "YouTube", "icon" => "youtube", "count" => 0, "domains" => ["youtube.com", "youtu.be"]],
        "search"    => ["name" => "Buscadores (Google/Bing)", "icon" => "search", "count" => 0, "domains" => ["google.", "bing.com", "yahoo.com", "duckduckgo.com"]],
        "direct"    => ["name" => "Directo / Bio enlace", "icon" => "link", "count" => 0, "domains" => ["direct", "directo"]],
        "other"     => ["name" => "Otras fuentes", "icon" => "globe", "count" => 0, "domains" => []]
      ];

      $totalViews = 0;

      foreach ($rows as $row) {
        $refRaw = strtolower(trim((string)$row["ref"]));
        $cnt    = (int)$row["count"];
        $totalViews += $cnt;

        $matchedKey = "other";
        if ($refRaw === "direct" || $refRaw === "directo" || empty($refRaw)) {
          $matchedKey = "direct";
        } else {
          foreach ($categories as $catKey => $catData) {
            if ($catKey === "other" || $catKey === "direct") continue;
            foreach ($catData["domains"] as $domain) {
              if (str_contains($refRaw, $domain)) {
                $matchedKey = $catKey;
                break 2;
              }
            }
          }
        }

        $categories[$matchedKey]["count"] += $cnt;
      }

      // Calcular porcentajes y ordenar plataformas activas de mayor a menor
      $activePlatforms = [];
      foreach ($categories as $key => $cat) {
        $cnt = $cat["count"];
        $pct = $totalViews > 0 ? round(($cnt / $totalViews) * 100, 1) : 0.0;

        $item = [
          "key"        => $key,
          "name"       => $cat["name"],
          "icon"       => $cat["icon"],
          "count"      => $cnt,
          "percentage" => $pct
        ];

        if ($cnt > 0) {
          $activePlatforms[] = $item;
        }
      }

      usort($activePlatforms, function ($a, $b) {
        return $b["count"] <=> $a["count"];
      });

      $topPlatform = $activePlatforms[0] ?? null;

      return [
        "total_referrers" => $totalViews,
        "platforms"       => $activePlatforms,
        "top_platform"    => $topPlatform,
        "all_categories"  => $categories
      ];
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getReferrersSummary: " . $e->getMessage());
      return [
        "total_referrers" => 0,
        "platforms"       => [],
        "top_platform"    => null,
        "all_categories"  => []
      ];
    }
  }

  /**
   * Genera la matriz de actividad horaria semanal (Heatmap 7x24) de visitas al perfil.
   * Calcula el día y la hora de mayor audiencia y genera recomendaciones de publicación.
   *
   * @param string $indexUser Identificador único index_user del perfil.
   * @return array Matriz 7x24, valor máximo de visitas, día pico, hora pico y texto de recomendación.
   */
  public static function getHeatmapData(string $indexUser): array {
    try {
      $cleanIndex = trim($indexUser);
      $sql = "
        SELECT 
          CAST(strftime('%w', created_at) AS INTEGER) as day_of_week,
          CAST(strftime('%H', created_at) AS INTEGER) as hour_of_day,
          COUNT(*) as visits
        FROM profile_views
        WHERE (index_user = ? OR profile_id = ?)
        GROUP BY day_of_week, hour_of_day
      ";
      $rows = (new self())->rawQuery($sql, [$cleanIndex, $cleanIndex]);

      $daysMap = [
        1 => "Lunes",
        2 => "Martes",
        3 => "Miércoles",
        4 => "Jueves",
        5 => "Viernes",
        6 => "Sábado",
        0 => "Domingo"
      ];

      // Inicializar matriz completa 7 x 24 con ceros
      $matrix = [];
      $dayTotals = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
      $hourTotals = array_fill(0, 24, 0);
      $maxVisits = 0;
      $totalVisits = 0;

      foreach ($daysMap as $dNum => $dLabel) {
        $matrix[$dNum] = array_fill(0, 24, 0);
      }

      foreach ($rows as $row) {
        $d = (int)$row["day_of_week"];
        $h = (int)$row["hour_of_day"];
        $v = (int)$row["visits"];

        if (isset($matrix[$d][$h])) {
          $matrix[$d][$h] = $v;
          $dayTotals[$d] += $v;
          $hourTotals[$h] += $v;
          $totalVisits += $v;
          if ($v > $maxVisits) {
            $maxVisits = $v;
          }
        }
      }

      // Encontrar día pico
      $peakDayNum = 1;
      $maxDayVisits = -1;
      foreach ($dayTotals as $dNum => $dSum) {
        if ($dSum > $maxDayVisits) {
          $maxDayVisits = $dSum;
          $peakDayNum = $dNum;
        }
      }

      // Encontrar hora pico
      $peakHour = 12;
      $maxHourVisits = -1;
      foreach ($hourTotals as $hNum => $hSum) {
        if ($hSum > $maxHourVisits) {
          $maxHourVisits = $hSum;
          $peakHour = $hNum;
        }
      }

      $peakDayName = $daysMap[$peakDayNum] ?? "Lunes";
      $peakHourFmt = sprintf("%02d:00", $peakHour);

      if ($totalVisits > 0) {
        $recommendation = "Tu mayor pico de audiencia ocurre los {$peakDayName} alrededor de las {$peakHourFmt} hrs. ¡Es tu mejor momento para publicar nuevos enlaces o contenido!";
      } else {
        $recommendation = "Aún no registras suficientes visitas para determinar tu hora pico de audiencia. ¡Comparte tu enlace en redes sociales!";
      }

      return [
        "matrix"              => $matrix,
        "days_map"            => $daysMap,
        "max_visits"          => $maxVisits,
        "total_visits"        => $totalVisits,
        "peak_day_num"        => $peakDayNum,
        "peak_day_name"       => $peakDayName,
        "peak_hour"           => $peakHour,
        "peak_hour_formatted" => $peakHourFmt,
        "recommendation"      => $recommendation
      ];
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getHeatmapData: " . $e->getMessage());
      return [
        "matrix"              => [],
        "days_map"            => [],
        "max_visits"          => 0,
        "total_visits"        => 0,
        "peak_day_num"        => 1,
        "peak_day_name"       => "Lunes",
        "peak_hour"           => 12,
        "peak_hour_formatted" => "12:00",
        "recommendation"      => "Sin datos suficientes."
      ];
    }
  }

  /**
   * Obtiene las estadísticas detalladas individuales para cada botón, enlace y red social configurado.
   * Calcula clics del mes, clics acumulados, desglose de móvil vs desktop, tasa de conversión (CTR)
   * y distingue al "Enlace Estrella" con mayor rendimiento.
   *
   * @param string $indexUser Identificador único index_user del perfil.
   * @return array Detalle completo con 'items', 'rrss', 'star_link', métricas globales y dispositivos.
   */
  public static function getLinksDetailedStats(string $indexUser): array {
    try {
      $cleanIndex = trim($indexUser);
      $userRecord = UserModels::getUserByIndex($cleanIndex);
      $username   = $userRecord["username"] ?? "";

      // 1. Obtener la tarjeta de diseño publicada del usuario
      $card = [];
      if (!empty($username)) {
        $dataUser = DesignModels::dataUser($username);
        if (is_array($dataUser) && isset($dataUser["card"]) && is_array($dataUser["card"])) {
          $card = $dataUser["card"];
        }
      }

      if (empty($card)) {
        $designRow = (new self("user_designs"))
          ->where("index_user", $cleanIndex)
          ->where("is_draft", 0)
          ->get_one();

        if ($designRow && !empty($designRow[0])) {
          $rawContent = $designRow[0]["content"] ?? [];
          $rawRrss    = $designRow[0]["rrss"] ?? [];
          $card = [
            "content" => is_string($rawContent) ? json_decode($rawContent, true) : (is_array($rawContent) ? $rawContent : []),
            "rrss"    => is_string($rawRrss) ? json_decode($rawRrss, true) : (is_array($rawRrss) ? $rawRrss : [])
          ];
        }
      }

      $contentItems = is_array($card["content"] ?? null) ? $card["content"] : [];
      $rrssItems    = is_array($card["rrss"] ?? null) ? $card["rrss"] : [];

      // 2. Visitas totales y del mes para cálculo de CTR
      $monthViews   = self::getCurrentMonthViews($cleanIndex);
      $allTimeViews = (int)(new self("profile_views"))->where("index_user", $cleanIndex)->count();

      // 3. Consultar todos los clics agregados en SQLite por link_id
      $startOfMonth = date("Y-m-01 00:00:00");
      $endOfMonth   = date("Y-m-t 23:59:59");

      $sql = "
        SELECT 
          link_id,
          COUNT(*) as all_time_clicks,
          SUM(CASE WHEN created_at >= ? AND created_at <= ? THEN 1 ELSE 0 END) as month_clicks,
          SUM(CASE WHEN device_type = 'mobile' THEN 1 ELSE 0 END) as mobile_clicks,
          SUM(CASE WHEN device_type != 'mobile' THEN 1 ELSE 0 END) as desktop_clicks
        FROM link_clicks
        WHERE (index_user = ? OR profile_id = ?)
        GROUP BY link_id
      ";
      $clickRows = (new self())->rawQuery($sql, [$startOfMonth, $endOfMonth, $cleanIndex, $cleanIndex]);

      // Mapear los datos de clics indexados en minúsculas
      $clicksMap = [];
      $totalMonthClicks = 0;
      $totalAllTimeClicks = 0;
      $totalMobileClicks = 0;
      $totalDesktopClicks = 0;

      foreach ($clickRows as $cr) {
        $rawLid = mb_strtolower(trim((string)$cr["link_id"]), "UTF-8");
        $aClicks = (int)($cr["all_time_clicks"] ?? 0);
        $mClicks = (int)($cr["month_clicks"] ?? 0);
        $mob     = (int)($cr["mobile_clicks"] ?? 0);
        $desk    = (int)($cr["desktop_clicks"] ?? 0);

        $clicksMap[$rawLid] = [
          "all_time_clicks" => $aClicks,
          "month_clicks"    => $mClicks,
          "mobile_clicks"   => $mob,
          "desktop_clicks"  => $desk
        ];

        $totalMonthClicks   += $mClicks;
        $totalAllTimeClicks += $aClicks;
        $totalMobileClicks  += $mob;
        $totalDesktopClicks += $desk;
      }

      // Helper para buscar clics en el mapa intentando varias claves (url, id, enlace_idx)
      $findClicks = function(array $candidateKeys) use ($clicksMap): array {
        foreach ($candidateKeys as $k) {
          $clean = mb_strtolower(trim((string)$k), "UTF-8");
          if (!empty($clean) && isset($clicksMap[$clean])) {
            return $clicksMap[$clean];
          }
        }
        return [
          "all_time_clicks" => 0,
          "month_clicks"    => 0,
          "mobile_clicks"   => 0,
          "desktop_clicks"  => 0
        ];
      };

      // 4. Procesar enlaces de contenido
      $processedItems = [];
      $highestClicks = -1;
      $starIndex = -1;

      foreach ($contentItems as $idx => $item) {
        $url       = $item["url"] ?? "";
        $itemId    = $item["id"] ?? "";
        $title     = $item["title"] ?? ($item["metaTitle"] ?? "Enlace " . ($idx + 1));
        $type      = $item["type"] ?? "link";
        $legacyKey = "enlace_" . ($idx + 1);

        $clickData = $findClicks([$url, $itemId, $legacyKey]);
        $mClicks   = $clickData["month_clicks"];
        $aClicks   = $clickData["all_time_clicks"];
        $mobClicks = $clickData["mobile_clicks"];
        $dskClicks = $clickData["desktop_clicks"];

        $ctrMonth = $monthViews > 0 ? round(($mClicks / $monthViews) * 100, 1) : 0.0;
        $ctrTotal = $allTimeViews > 0 ? round(($aClicks / $allTimeViews) * 100, 1) : 0.0;

        $mobPct = $aClicks > 0 ? round(($mobClicks / $aClicks) * 100, 1) : 0.0;
        $dskPct = $aClicks > 0 ? round(($dskClicks / $aClicks) * 100, 1) : 0.0;

        // Candidato a Enlace Estrella (evaluando clics del mes o acumulados)
        $score = $mClicks > 0 ? $mClicks : $aClicks;
        if ($score > $highestClicks && $score > 0) {
          $highestClicks = $score;
          $starIndex = count($processedItems);
        }

        $processedItems[] = [
          "index"              => $idx,
          "id"                 => $itemId,
          "title"              => $title,
          "url"                => $url,
          "type"               => $type,
          "active"             => !empty($item["active"]),
          "month_clicks"       => $mClicks,
          "all_time_clicks"    => $aClicks,
          "ctr_month"          => $ctrMonth,
          "ctr_total"          => $ctrTotal,
          "mobile_clicks"      => $mobClicks,
          "desktop_clicks"     => $dskClicks,
          "mobile_percentage"  => $mobPct,
          "desktop_percentage" => $dskPct,
          "is_star"            => false
        ];
      }

      // Asignar distinción de Enlace Estrella
      $starLink = null;
      if ($starIndex >= 0 && isset($processedItems[$starIndex])) {
        $processedItems[$starIndex]["is_star"] = true;
        $starLink = $processedItems[$starIndex];
      }

      // 5. Procesar Redes Sociales (RRSS)
      $processedRrss = [];
      foreach ($rrssItems as $rIdx => $rItem) {
        $network = is_array($rItem) ? ($rItem[0] ?? "") : "";
        $rUrl    = is_array($rItem) ? ($rItem[1] ?? "") : "";
        if (empty($network)) continue;

        $rrssKey = "rrss_" . mb_strtolower($network, "UTF-8");
        $clickData = $findClicks([$rrssKey, $rUrl]);

        $mClicks   = $clickData["month_clicks"];
        $aClicks   = $clickData["all_time_clicks"];
        $mobClicks = $clickData["mobile_clicks"];
        $dskClicks = $clickData["desktop_clicks"];

        $ctrMonth = $monthViews > 0 ? round(($mClicks / $monthViews) * 100, 1) : 0.0;
        $ctrTotal = $allTimeViews > 0 ? round(($aClicks / $allTimeViews) * 100, 1) : 0.0;

        $processedRrss[] = [
          "network"          => $network,
          "url"              => $rUrl,
          "icon"             => $network,
          "month_clicks"     => $mClicks,
          "all_time_clicks"  => $aClicks,
          "ctr_month"        => $ctrMonth,
          "ctr_total"        => $ctrTotal,
          "mobile_clicks"    => $mobClicks,
          "desktop_clicks"   => $dskClicks
        ];
      }

      $totalMobilePct  = $totalAllTimeClicks > 0 ? round(($totalMobileClicks / $totalAllTimeClicks) * 100, 1) : 0.0;
      $totalDesktopPct = $totalAllTimeClicks > 0 ? round(($totalDesktopClicks / $totalAllTimeClicks) * 100, 1) : 0.0;

      return [
        "total_links_configured" => count($processedItems) + count($processedRrss),
        "total_content_links"    => count($processedItems),
        "total_rrss_links"       => count($processedRrss),
        "total_month_clicks"     => $totalMonthClicks,
        "total_all_time_clicks"  => $totalAllTimeClicks,
        "global_mobile_pct"      => $totalMobilePct,
        "global_desktop_pct"     => $totalDesktopPct,
        "star_link"              => $starLink,
        "items"                  => $processedItems,
        "rrss"                   => $processedRrss
      ];
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getLinksDetailedStats: " . $e->getMessage());
      return [
        "total_links_configured" => 0,
        "total_content_links"    => 0,
        "total_rrss_links"       => 0,
        "total_month_clicks"     => 0,
        "total_all_time_clicks"  => 0,
        "global_mobile_pct"      => 0.0,
        "global_desktop_pct"     => 0.0,
        "star_link"              => null,
        "items"                  => [],
        "rrss"                   => []
      ];
    }
  }

}
