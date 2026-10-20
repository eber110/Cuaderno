<?php
  use Base\Module\GraphicsModule;

  /**
   * Vista de Estadísticas por Enlace (Dashboard.StatisticsPanel.statisticsLink)
   * 
   * Orquestador modular de paneles de rendimiento por botón:
   * 1. Métricas globales de CTO y rendimiento (Cto)
   * 2. Destacado "Enlace Estrella" (StarLink)
   * 3. Tabla / Tarjetas de enlaces individuales (LinkPerformance)
   * 4. Desglose de Redes Sociales (SocialNetworks)
   * 5. Distribución global de dispositivos (DeviceDistribution)
   *
   * @var array  $stats     Estructura completa retornada por StatisticsModels::getStatsData()
   * @var array  $cto       Métricas evaluadas de CTO global
   * @var bool   $isPremium Booleano que indica si el creador cuenta con suscripción activa
   * @var array  $card      Datos de diseño y perfil del usuario
   * @var string $user      Nombre de usuario
   * @var array  $uri       Rutas de navegación
   */

  $userProfile   = !empty($user) ? $user : ($card["profile"] ?? "user");
  $isPremium     = (bool)($isPremium ?? false);
  $linksDetailed = is_array($stats["links_detailed"] ?? null) ? $stats["links_detailed"] : [];

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
      <p class="m0 color-secondary">Rendimiento individual de cada botón, tasa de conversión (CTR), Enlace Estrella y análisis por dispositivos.</p>
    </div>

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
  </div>

  <!-- ========================================================
       1. Métricas Globales de Rendimiento y CTO
       ======================================================== -->
  <?php
    _part("Dashboard.cto", [
      "cto" => $cto ?? ($stats["cto"] ?? [])
    ]);
  ?>

  <!-- ========================================================
       2. Destacado: «Enlace Estrella»
       ======================================================== -->
  <?php
    _part("Dashboard.starLink", [
      "starLink"  => $linksDetailed["star_link"] ?? null,
      "isPremium" => $isPremium
    ]);
  ?>

  <!-- ========================================================
       3. Detalle Individual por Enlace (Tabla / Tarjetas)
       ======================================================== -->
  <?php
    _part("Dashboard.linkPerformance", [
      "items"     => $linksDetailed["items"] ?? [],
      "isPremium" => $isPremium
    ]);
  ?>

  <!-- ========================================================
       4. Desglose de Redes Sociales (RRSS)
       ======================================================== -->
  <?php
    _part("Dashboard.socialNetworks", [
      "rrss"      => $linksDetailed["rrss"] ?? [],
      "isPremium" => $isPremium
    ]);
  ?>

  <!-- ========================================================
       5. Distribución de Dispositivos (Móvil vs Escritorio)
       ======================================================== -->
  <?php
    _part("Dashboard.deviceDistribution", [
      "mobilePct"  => (float)($linksDetailed["global_mobile_pct"] ?? $linksDetailed["mobile_percentage"] ?? 0.0),
      "desktopPct" => (float)($linksDetailed["global_desktop_pct"] ?? $linksDetailed["desktop_percentage"] ?? 0.0)
    ]);
  ?>

</div>
