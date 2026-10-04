<?php

namespace App\Models;

use Base\Builder\BuilderSqlite;
use Base\Module\AnalyticsModule;
use Base\Module\DateTimeModule;
use PDO;

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
      "user"          => $userClean,
      "index_user"    => $indexUser,
      "summary"       => $summary,
      "monthly_views" => $monthly,
      "cto"           => $cto
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
      $pdo = AnalyticsModule::getPdo();
      $stmt = $pdo->prepare("
        SELECT 
          strftime('%Y-%m-%d', created_at) as date,
          COUNT(*) as views,
          COUNT(DISTINCT ip_address) as unique_views
        FROM profile_views
        WHERE (index_user = :idx OR profile_id = :idx)
          AND created_at >= datetime('now', '-' || :days || ' days')
        GROUP BY date
        ORDER BY date ASC
      ");
      $stmt->bindValue(':idx', $indexUser);
      $stmt->bindValue(':days', $days, PDO::PARAM_INT);
      $stmt->execute();

      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
      $pdo = AnalyticsModule::getPdo();
      $stmt = $pdo->prepare("
        SELECT 
          strftime('%Y-%m-%d', created_at) as date,
          COUNT(*) as clicks
        FROM link_clicks
        WHERE (index_user = :idx OR profile_id = :idx)
          AND created_at >= datetime('now', '-' || :days || ' days')
        GROUP BY date
        ORDER BY date ASC
      ");
      $stmt->bindValue(':idx', $indexUser);
      $stmt->bindValue(':days', $days, PDO::PARAM_INT);
      $stmt->execute();

      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
      $pdo = AnalyticsModule::getPdo();
      $stmt = $pdo->prepare("
        SELECT 
          link_id,
          COUNT(*) as total_clicks
        FROM link_clicks
        WHERE (index_user = :idx OR profile_id = :idx)
        GROUP BY link_id
        ORDER BY total_clicks DESC
      ");
      $stmt->execute([':idx' => $indexUser]);

      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
      $pdo = AnalyticsModule::getPdo();
      $stmt = $pdo->prepare("
        SELECT 
          COALESCE(NULLIF(device_type, ''), 'desktop') as device,
          COUNT(*) as count
        FROM profile_views
        WHERE (index_user = :idx OR profile_id = :idx)
        GROUP BY device
        ORDER BY count DESC
      ");
      $stmt->execute([':idx' => $indexUser]);

      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
      $pdo = AnalyticsModule::getPdo();
      $stmt = $pdo->prepare("
        SELECT 
          country_code,
          country_name,
          COUNT(*) as count
        FROM profile_views
        WHERE (index_user = :idx OR profile_id = :idx)
          AND country_code != 'DEV'
        GROUP BY country_code, country_name
        ORDER BY count DESC
        LIMIT :limit
      ");
      $stmt->bindValue(':idx', $indexUser);
      $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
      $stmt->execute();

      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
      $pdo = AnalyticsModule::getPdo();
      $stmt = $pdo->prepare("
        SELECT 
          COALESCE(NULLIF(referrer, ''), 'Directo') as referrer_source,
          COUNT(*) as count
        FROM profile_views
        WHERE (index_user = :idx OR profile_id = :idx)
        GROUP BY referrer_source
        ORDER BY count DESC
        LIMIT :limit
      ");
      $stmt->bindValue(':idx', $indexUser);
      $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
      $stmt->execute();

      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (\Throwable $e) {
      error_log("Error en StatisticsModels::getReferrerBreakdown: " . $e->getMessage());
      return [];
    }
  }

}
