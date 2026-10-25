<?php
  use Base\Module\GraphicsModule;
  use App\Models\StatisticsModels;

  /**
   * Vista de Estadísticas por Enlace (Dashboard.StatisticsPanel.statisticsLink)
   * 
   * Orquestador modular de paneles de rendimiento por botón:
   * 1. Métricas globales de CTO y rendimiento (Cto) [Básico]
   * 2. Destacado "Enlace Estrella" (StarLink) [Avanzado / Dev]
   * 3. Tabla / Tarjetas de enlaces individuales (LinkPerformance) [Avanzado / Dev]
   * 4. Desglose de Redes Sociales (SocialNetworks) [Avanzado / Dev]
   * 5. Distribución global de dispositivos (DeviceDistribution) [Básico]
   *
   * @var array  $stats              Estructura completa retornada por StatisticsModels::getStatsData()
   * @var array  $cto                Métricas evaluadas de CTO global
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
  $cto                = isset($cto) && is_array($cto) ? $cto : (isset($stats["cto"]) && is_array($stats["cto"]) ? $stats["cto"] : []);
  $linksDetailed      = isset($stats["links_detailed"]) && is_array($stats["links_detailed"]) ? $stats["links_detailed"] : [];

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
       Cabecera de Estadísticas por Enlace
       ======================================================== -->
  <div class="flex-row center-between wrap gap15 w100">
    <div class="flex-column gap5">
      <h2 class="m0 x24 bold700 texto flex-row center-start gap10">
        <span class="color-secondary"><?= svg("link", "x24"); ?></span>
        Estadísticas por Enlace
      </h2>
      <?php if ($canViewDevGraphics): ?>
        <p class="m0 color-secondary">Rendimiento individual de cada botón, tasa de conversión (CTR), Enlace Estrella y análisis por dispositivos.</p>
      <?php else: ?>
        <p class="m0 color-secondary">Métricas de conversión (CTR) y rendimiento general de tus enlaces.</p>
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
          <a href="/planes" class="btn-premium-unlock" title="Desbloquea el análisis detallado por botón">
            <?= svg("lock", "x12"); ?>
            Mejorar a Premium
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ========================================================
       1. Métricas Globales de Rendimiento y CTO [Básico]
       ======================================================== -->
  <?php
    _part("Dashboard.cto", [
      "cto" => $cto ?? ($stats["cto"] ?? [])
    ]);
  ?>

  <?php if ($canViewDevGraphics): ?>
    <!-- ========================================================
         2. Destacado: «Enlace Estrella» [Avanzado / Dev]
         ======================================================== -->
    <?php
      _part("Dashboard.starLink", [
        "starLink"    => $linksDetailed["star_link"] ?? null,
        "isPremium"   => $canViewSubs ? $isPremium : true,
        "canViewSubs" => $canViewSubs
      ]);
    ?>

    <!-- ========================================================
         3. Detalle Individual por Enlace (Tabla / Tarjetas) [Avanzado / Dev]
         ======================================================== -->
    <?php
      _part("Dashboard.linkPerformance", [
        "items"       => $linksDetailed["items"] ?? [],
        "isPremium"   => $canViewSubs ? $isPremium : true,
        "canViewSubs" => $canViewSubs
      ]);
    ?>

    <!-- ========================================================
         4. Desglose de Redes Sociales (RRSS) [Avanzado / Dev]
         ======================================================== -->
    <?php
      _part("Dashboard.socialNetworks", [
        "rrss"        => $linksDetailed["rrss"] ?? [],
        "isPremium"   => $canViewSubs ? $isPremium : true,
        "canViewSubs" => $canViewSubs
      ]);
    ?>
  <?php endif; ?>

  <!-- ========================================================
       5. Distribución de Dispositivos (Móvil vs Escritorio) [Básico]
       ======================================================== -->
  <?php
    _part("Dashboard.deviceDistribution", [
      "mobilePct"  => (float)($linksDetailed["global_mobile_pct"] ?? $linksDetailed["mobile_percentage"] ?? 0.0),
      "desktopPct" => (float)($linksDetailed["global_desktop_pct"] ?? $linksDetailed["desktop_percentage"] ?? 0.0)
    ]);
  ?>

</div>
