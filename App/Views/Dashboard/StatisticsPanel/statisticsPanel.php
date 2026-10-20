<?php
  use Base\Module\GraphicsModule;

  /**
   * Vista de Estadísticas Generales (Dashboard.StatisticsPanel.statisticsPanel)
   * 
   * Orquestador modular de paneles de estadísticas del perfil:
   * 1. Visitas del mes actual (MonthlyViews)
   * 2. Feed «¿Quién te visitó?» (RecentVisitors)
   * 3. Geolocalización (GeoRanking)
   * 4. Fuentes de Tráfico (TrafficSources)
   * 5. Actividad Semanal Heatmap (Heatmap)
   *
   * @var array  $stats        Estructura completa retornada por StatisticsModels::getStatsData()
   * @var array  $monthlyViews Métricas evaluadas de visitas del mes actual
   * @var bool   $isPremium    Booleano que indica si el creador cuenta con suscripción activa
   * @var array  $card         Datos de diseño y perfil del usuario
   * @var string $user         Nombre de usuario
   * @var array  $uri          Rutas de navegación
   */

  $userProfile = !empty($user) ? $user : ($card["profile"] ?? "user");
  $isPremium   = (bool)($isPremium ?? false);

  GraphicsModule::configStyle([
    "colorLabel"    => "texto",
    "axisLabel"     => "texto",
    "axisLabelSize" => "16",
    "barWidth"      => "20",
    "color"         => "color8",
    "transition"    => 500,
    "tooltip"       => true
  ]);
?>

<div class="flex-column gap25 w100 texto">

  <!-- ========================================================
       Cabecera de Estadísticas Generales
       ======================================================== -->
  <div class="flex-row center-between wrap gap15 w100">
    <div class="flex-column gap5">
      <h2 class="m0 x24 bold700 texto flex-row center-start gap10">
        <span class="color-secondary"><?= svg("chart", "x24"); ?></span>
        Estadísticas Generales
      </h2>
      <p class="m0 color-secondary">Métricas de rendimiento de tu perfil, visitantes recientes, geolocalización y mejores horarios de publicación.</p>
    </div>

    <div class="flex-row center-center gap10">
      <?php if ($isPremium): ?>
        <span class="premium-lock-badge">
          <?= svg("check-solid", "x12"); ?>
          Plan Premium
        </span>
      <?php else: ?>
        <a href="/planes" class="btn-premium-unlock" title="Desbloquea todas las estadísticas">
          <?= svg("lock", "x12"); ?>
          Mejorar a Premium
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- ========================================================
       1. Visitas del Mes Actual (Tendencia + Hybrid Spline)
       ======================================================== -->
  <?php
    _part("Dashboard.monthlyViews", [
      "monthly" => $monthlyViews ?? ($stats["monthly_views"] ?? [])
    ]);
  ?>

  <!-- ========================================================
       2. Feed «¿Quién te visitó?»
       ======================================================== -->
  <?php
    _part("Dashboard.recentVisitors", [
      "visitors"  => $stats["visitors"] ?? [],
      "isPremium" => $isPremium
    ]);
  ?>

  <!-- ========================================================
       3. Geolocalización (Países y Ciudades)
       ======================================================== -->
  <?php
    _part("Dashboard.geoRanking", [
      "geo"       => $stats["geo"] ?? [],
      "isPremium" => $isPremium
    ]);
  ?>

  <!-- ========================================================
       4. Fuentes de Tráfico (Referrers)
       ======================================================== -->
  <?php
    _part("Dashboard.trafficSources", [
      "referrers" => $stats["referrers"] ?? [],
      "isPremium" => $isPremium
    ]);
  ?>

  <!-- ========================================================
       5. Actividad Semanal (Heatmap)
       ======================================================== -->
  <?php
    _part("Dashboard.heatmap", [
      "heatmap"   => $stats["heatmap"] ?? [],
      "isPremium" => $isPremium
    ]);
  ?>

</div>
