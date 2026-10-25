<?php
  use Base\Module\GraphicsModule;
  use App\Models\StatisticsModels;

  /**
   * Vista de Estadísticas Generales (Dashboard.StatisticsPanel.statisticsPanel)
   * 
   * Orquestador modular de paneles de estadísticas del perfil:
   * 1. Visitas del mes actual (MonthlyViews) [Básico]
   * 2. Feed «¿Quién te visitó?» (RecentVisitors) [Avanzado / Dev]
   * 3. Geolocalización (GeoRanking) [Avanzado / Dev]
   * 4. Fuentes de Tráfico (TrafficSources) [Avanzado / Dev]
   * 5. Actividad Semanal Heatmap (Heatmap) [Avanzado / Dev]
   *
   * @var array  $stats              Estructura completa retornada por StatisticsModels::getStatsData()
   * @var array  $monthlyViews       Métricas evaluadas de visitas del mes actual
   * @var bool   $isPremium          Booleano que indica si el creador cuenta con suscripción activa
   * @var bool   $canViewDevGraphics Booleano que indica si se autoriza ver gráficos en desarrollo
   * @var bool   $canViewSubs        Booleano que indica si las referencias a suscripciones están publicadas en producción
   * @var array  $card               Datos de diseño y perfil del usuario
   * @var string $user               Nombre de usuario
   * @var array  $uri                Rutas de navegación
   */

  $userProfile        = isset($user) && !empty($user) ? $user : (isset($card["profile"]) ? $card["profile"] : "user");
  $canViewDevGraphics = isset($canViewDevGraphics) ? (bool)$canViewDevGraphics : StatisticsModels::canViewDevGraphics($userProfile);
  $canViewSubs        = isset($canViewSubs) ? (bool)$canViewSubs : StatisticsModels::canViewSubscriptions();
  $isPremium          = isset($isPremium) ? (bool)$isPremium : false;
  $stats              = isset($stats) && is_array($stats) ? $stats : [];
  $monthlyViews       = isset($monthlyViews) && is_array($monthlyViews) ? $monthlyViews : (isset($stats["monthly_views"]) && is_array($stats["monthly_views"]) ? $stats["monthly_views"] : []);

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
      <?php if ($canViewDevGraphics): ?>
        <p class="m0 color-secondary">Métricas de rendimiento de tu perfil, visitantes recientes, geolocalización y mejores horarios de publicación.</p>
      <?php else: ?>
        <p class="m0 color-secondary">Métricas de rendimiento y visitas de tu perfil.</p>
      <?php endif; ?>
    </div>

    <?php if ($canViewSubs): ?>
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
    <?php endif; ?>
  </div>

  <!-- ========================================================
       1. Visitas del Mes Actual (Tendencia + Hybrid Spline) [Básico]
       ======================================================== -->
  <?php
    _part("Dashboard.monthlyViews", [
      "monthly" => $monthlyViews ?? ($stats["monthly_views"] ?? [])
    ]);
  ?>

  <?php if ($canViewDevGraphics): ?>
    <!-- ========================================================
         2. Feed «¿Quién te visitó?» [Avanzado / Dev]
         ======================================================== -->
    <?php
      _part("Dashboard.recentVisitors", [
        "visitors"    => $stats["visitors"] ?? [],
        "isPremium"   => $canViewSubs ? $isPremium : true,
        "canViewSubs" => $canViewSubs
      ]);
    ?>

    <!-- ========================================================
         3. Geolocalización (Países y Ciudades) [Avanzado / Dev]
         ======================================================== -->
    <?php
      _part("Dashboard.geoRanking", [
        "geo"         => $stats["geo"] ?? [],
        "isPremium"   => $canViewSubs ? $isPremium : true,
        "canViewSubs" => $canViewSubs
      ]);
    ?>

    <!-- ========================================================
         4. Fuentes de Tráfico (Referrers) [Avanzado / Dev]
         ======================================================== -->
    <?php
      _part("Dashboard.trafficSources", [
        "referrers"   => $stats["referrers"] ?? [],
        "isPremium"   => $canViewSubs ? $isPremium : true,
        "canViewSubs" => $canViewSubs
      ]);
    ?>

    <!-- ========================================================
         5. Actividad Semanal (Heatmap) [Avanzado / Dev]
         ======================================================== -->
    <?php
      _part("Dashboard.heatmap", [
        "heatmap"     => $stats["heatmap"] ?? [],
        "isPremium"   => $canViewSubs ? $isPremium : true,
        "canViewSubs" => $canViewSubs
      ]);
    ?>
  <?php endif; ?>

</div>
