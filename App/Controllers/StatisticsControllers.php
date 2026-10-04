<?php

namespace App\Controllers;

use App\Models\DesignModels;
use App\Models\LemonSqueezyModels;
use App\Models\StatisticsModels;
use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\DateTimeModule;
use Base\Module\ResponseModule;
use Base\Module\Session;

/**
 * Clase StatisticsControllers
 * 
 * Controlador encargado de la orquestación HTTP de peticiones de estadísticas del panel.
 * Delega la obtención y procesamiento de datos al modelo StatisticsModels,
 * referenciando las consultas con index_user para garantizar la privacidad y seguridad,
 * y ejecutando las reglas de negocio requeridas para las métricas del creador.
 */
class StatisticsControllers extends Control {

  /**
   * Obtiene los datos analíticos de un usuario delegando al modelo.
   *
   * @param string $user Nombre de usuario o index_user.
   * @return array Datos de estadísticas procesados.
   */
  public static function getStatsData(string $user): array {
    return StatisticsModels::getStatsData($user);
  }

  /**
   * Evalúa la tendencia de visitas del mes actual comparándolas con el promedio
   * histórico de los meses anteriores según las reglas de negocio:
   * - Si aumentó (> promedio): flecha verde ("arrow-up", color-success).
   * - Si bajó (< promedio): flecha roja ("arrow-down", color-danger).
   * - Si quedó igual (== promedio) o no hay historial previo: sin flecha.
   * Además estructura el dataset cronológico completo para el gráfico vectorial Hybrid Spline.
   *
   * @param array $monthlyData Datos de visitas mensuales del modelo.
   * @return array Datos combinados con tendencia, indicador de flecha, clases semánticas y dataset para Hybrid Spline.
   */
  public static function evaluateMonthlyTrend(array $monthlyData): array {
    $current  = (int)($monthlyData["current_month_total"] ?? 0);
    $avg      = (float)($monthlyData["previous_months_avg"] ?? 0.0);
    $hasHist  = !empty($monthlyData["has_history"]);

    $trend      = "equal";
    $arrow      = null;
    $arrowClass = "";
    $statusText = "";
    $pctChange  = 0.0;

    if ($hasHist) {
      if ($current > $avg) {
        $trend      = "up";
        $arrow      = "arrow-up";
        $arrowClass = "color-success";
        $diff       = $current - $avg;
        $pctChange  = $avg > 0 ? round(($diff / $avg) * 100, 1) : 100.0;
        $statusText = "Aumentó un {$pctChange}% respecto al promedio anterior ({$avg})";
      } elseif ($current < $avg) {
        $trend      = "down";
        $arrow      = "arrow-down";
        $arrowClass = "color-danger";
        $diff       = $avg - $current;
        $pctChange  = $avg > 0 ? round(($diff / $avg) * 100, 1) : 100.0;
        $statusText = "Disminuyó un {$pctChange}% respecto al promedio anterior ({$avg})";
      } else {
        $trend      = "equal";
        $arrow      = null;
        $arrowClass = "";
        $statusText = "Se mantuvo igual al promedio anterior ({$avg})";
      }
    } else {
      $trend      = "first_month";
      $arrow      = null;
      $arrowClass = "";
      $statusText = "Primer mes con registro de visitas";
    }

    // Estructuración de dataset cronológico para el gráfico Hybrid Spline
    $splineValues        = [];
    $splineLabels        = [];
    $splineDisplayLabels = [];
    $splineDisplayValues = [];

    $shortMonthMap = [
      "01" => "Ene", "02" => "Feb", "03" => "Mar", "04" => "Abr",
      "05" => "May", "06" => "Jun", "07" => "Jul", "08" => "Ago",
      "09" => "Sep", "10" => "Oct", "11" => "Nov", "12" => "Dic"
    ];

    $previousList = is_array($monthlyData["previous_months"] ?? null) ? $monthlyData["previous_months"] : [];
    foreach ($previousList as $row) {
      $mesStr = (string)($row["mes"] ?? "");
      $total  = (int)($row["total"] ?? 0);
      if ($mesStr === "") continue;

      $mesNum = substr($mesStr, 5, 2);
      $shortLbl = $shortMonthMap[$mesNum] ?? $mesStr;
      $fullLbl  = ucfirst(DateTimeModule::formatSpanish($mesStr . "-01", "F Y"));

      $splineValues[]        = $total;
      $splineLabels[]        = $shortLbl;
      $splineDisplayLabels[] = $fullLbl;
      $splineDisplayValues[] = $total . " visitas";
    }

    // Incluir el mes actual al final de la línea de tiempo si hay datos o si no hay historial
    if ($current > 0 || !empty($previousList)) {
      $curMonthNum = date("m");
      $curShortLbl = $shortMonthMap[$curMonthNum] ?? date("M");
      $curFullLbl  = ucfirst(DateTimeModule::formatSpanish(date("Y-m-01"), "F Y")) . " (Actual)";

      $splineValues[]        = $current;
      $splineLabels[]        = $curShortLbl;
      $splineDisplayLabels[] = $curFullLbl;
      $splineDisplayValues[] = $current . " visitas";
    }

    $splineData = [
      "values"         => $splineValues,
      "labels"         => $splineLabels,
      "displayLabels"  => $splineDisplayLabels,
      "displayValues"  => $splineDisplayValues,
      "unit"           => "Visitas",
      "autoScale"      => true,
      "color"          => "texto",
      "axisLabel"      => "color-secondary",
      "transition"     => 450,
      "has_points"     => count($splineValues) > 0
    ];

    return array_merge($monthlyData, [
      "trend"       => $trend,
      "arrow"       => $arrow,
      "arrow_class" => $arrowClass,
      "status_text" => $statusText,
      "pct_change"  => $pctChange,
      "spline"      => $splineData
    ]);
  }

  /**
   * Procesa y evalúa las reglas de negocio para la métrica de CTO (Click-Through Rate),
   * el promedio entre total de enlaces y visitas, y el índice de clics hacia enlaces vs RRSS.
   *
   * @param array $rawCto Datos brutos obtenidos desde StatisticsModels::getCtoData.
   * @return array Datos evaluados con tasas de conversión, ratios e índices porcentuales.
   */
  public static function evaluateCtoMetrics(array $rawCto): array {
    $monthlyViews   = (int)($rawCto["monthly_views"] ?? 0);
    $monthlyClicks  = (int)($rawCto["monthly_clicks"] ?? 0);
    $monthlyEnlaces = (int)($rawCto["monthly_enlaces"] ?? 0);
    $monthlyRrss    = (int)($rawCto["monthly_rrss"] ?? 0);

    $configuredLinks = $rawCto["configured_links"] ?? [];
    $totalLinks      = (int)($configuredLinks["total_links"] ?? 0);
    $contentLinks    = (int)($configuredLinks["content_links"] ?? 0);
    $rrssLinks       = (int)($configuredLinks["rrss_links"] ?? 0);

    $allTimeViews   = (int)($rawCto["all_time_views"] ?? 0);
    $allTimeClicks  = (int)($rawCto["all_time_clicks"] ?? 0);
    $allTimeEnlaces = (int)($rawCto["all_time_enlaces"] ?? 0);
    $allTimeRrss    = (int)($rawCto["all_time_rrss"] ?? 0);

    // 1. Tasa de CTO y promedio de clics por visita en el mes actual
    $monthlyCtoRate        = $monthlyViews > 0 ? round(($monthlyClicks / $monthlyViews) * 100, 2) : 0.0;
    $monthlyClicksPerVisit = $monthlyViews > 0 ? round($monthlyClicks / $monthlyViews, 2) : 0.0;

    // 2. Promedio entre total de enlaces configurados y total de visitas del mes
    $linksPerVisit = $monthlyViews > 0 ? round($totalLinks / $monthlyViews, 2) : 0.0;
    $visitsPerLink = $totalLinks > 0 ? round($monthlyViews / $totalLinks, 2) : 0.0;

    // 3. Índice de clics a enlaces vs RRSS en el mes actual
    $pctEnlacesMonth = $monthlyClicks > 0 ? round(($monthlyEnlaces / $monthlyClicks) * 100, 1) : 0.0;
    $pctRrssMonth    = $monthlyClicks > 0 ? round(($monthlyRrss / $monthlyClicks) * 100, 1) : 0.0;

    // 4. Índices acumulados históricos para contexto
    $allTimeCtoRate     = $allTimeViews > 0 ? round(($allTimeClicks / $allTimeViews) * 100, 2) : 0.0;
    $pctEnlacesAllTime  = $allTimeClicks > 0 ? round(($allTimeEnlaces / $allTimeClicks) * 100, 1) : 0.0;
    $pctRrssAllTime     = $allTimeClicks > 0 ? round(($allTimeRrss / $allTimeClicks) * 100, 1) : 0.0;

    // 5. Canal de clics predominante
    $predominantChannel = "equal";
    if ($monthlyClicks > 0) {
      if ($monthlyEnlaces > $monthlyRrss) {
        $predominantChannel = "enlaces";
      } elseif ($monthlyRrss > $monthlyEnlaces) {
        $predominantChannel = "rrss";
      }
    } else {
      if ($allTimeEnlaces > $allTimeRrss) {
        $predominantChannel = "enlaces_history";
      } elseif ($allTimeRrss > $allTimeEnlaces) {
        $predominantChannel = "rrss_history";
      }
    }

    // Configuración para el gráfico semicircular Arc Meter del CTO mensual
    $arcMeterData = [
      "value"        => min(100.0, max(0.0, (float)$monthlyCtoRate)),
      "displayValue" => $monthlyCtoRate . "%",
      "label"        => "Tasa de conversión",
      "color"        => "texto",
      "colorLabel"   => "texto",
      "unit"         => "Tasa CTO",
      "transition"   => 450,
      "showNumber"   => true,
      "showLabel"    => true
    ];

    return array_merge($rawCto, [
      "monthly_cto_rate"          => $monthlyCtoRate,
      "monthly_clicks_per_visit"  => $monthlyClicksPerVisit,
      "links_per_visit"           => $linksPerVisit,
      "visits_per_link"           => $visitsPerLink,
      "pct_enlaces_month"         => $pctEnlacesMonth,
      "pct_rrss_month"            => $pctRrssMonth,
      "all_time_cto_rate"         => $allTimeCtoRate,
      "pct_enlaces_all_time"      => $pctEnlacesAllTime,
      "pct_rrss_all_time"         => $pctRrssAllTime,
      "predominant_channel"       => $predominantChannel,
      "arc_meter"                 => $arcMeterData
    ]);
  }

  /**
   * Carga bajo demanda la vista de estadísticas vía AJAX / Fetch para el panel.
   *
   * @param string $user Nombre de usuario.
   * @return void
   */
  public function loadStatsHtml(string $user): void {
    $userClean = mb_strtolower($user, "UTF-8");

    // Siempre que esté logueado un usuario, referenciar con su index_user de sesión
    $sessionIndex = Session::session_active() ? Session::session_data("index_user") : null;
    $indexUser    = !empty($sessionIndex) ? $sessionIndex : (UserModels::getIndexUserByUsername($userClean) ?? $userClean);

    $statsRaw     = StatisticsModels::getStatsData($indexUser);
    $monthlyRaw   = $statsRaw["monthly_views"] ?? StatisticsModels::getMonthlyViewsData($indexUser);
    $monthlyEval  = self::evaluateMonthlyTrend($monthlyRaw);
    $ctoRaw       = $statsRaw["cto"] ?? StatisticsModels::getCtoData($indexUser);
    $ctoEval      = self::evaluateCtoMetrics($ctoRaw);
    $isPremium    = LemonSqueezyModels::isUserSubscribedFast($indexUser);

    $dataUser  = DesignModels::dataUser($userClean);
    $cardData  = (isset($dataUser["card"]) && is_array($dataUser["card"])) 
      ? UserModels::formatCardImages($dataUser["card"]) 
      : [];

    $uri = [
      "formDesign"    => "/panel/{$userClean}/diseno",
      "saveDesign"    => "/panel/{$userClean}/guardar",
      "discardDesign" => "/panel/{$userClean}/descartar",
      "estadisticas"  => "/panel/{$userClean}/estadisticas"
    ];

    $statsHtml = _partToString("Dashboard.statisticsPanel", [
      "stats"        => $statsRaw,
      "monthlyViews" => $monthlyEval,
      "cto"          => $ctoEval,
      "isPremium"    => $isPremium,
      "card"         => $cardData,
      "user"         => $userClean,
      "uri"          => $uri
    ]);

    ResponseModule::json([
      "success"   => true,
      "statsHtml" => $statsHtml
    ]);
  }

  /**
   * Endpoint de compatibilidad temporal para redireccionar peticiones de datos de prueba obsoletas.
   *
   * @param string $user Nombre de usuario objetivo.
   * @return void
   */
  public function generateTestData(string $user): void {
    $userClean = mb_strtolower($user, "UTF-8");
    ResponseModule::redirect("/panel/{$userClean}");
  }

}