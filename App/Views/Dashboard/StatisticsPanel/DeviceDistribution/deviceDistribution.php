<?php
  /**
   * Vista de Distribución de Dispositivos Móvil vs Escritorio (Dashboard.StatisticsPanel.DeviceDistribution.deviceDistribution)
   *
   * @var float|null $mobilePct     Porcentaje de clics originados en smartphones
   * @var float|null $desktopPct    Porcentaje de clics originados en ordenadores
   * @var array|null $linksDetailed Detalle completo de enlaces
   * @var array|null $stats         Estructura analítica global
   */

  $statsList     = isset($stats) && is_array($stats) ? $stats : [];
  $linksDetailed = isset($linksDetailed) && is_array($linksDetailed) 
    ? $linksDetailed 
    : (isset($statsList["links_detailed"]) && is_array($statsList["links_detailed"]) ? $statsList["links_detailed"] : []);
  $mobilePct     = isset($mobilePct) ? (float)$mobilePct : (float)($linksDetailed["global_mobile_pct"] ?? ($linksDetailed["mobile_percentage"] ?? 0.0));
  $desktopPct    = isset($desktopPct) ? (float)$desktopPct : (float)($linksDetailed["global_desktop_pct"] ?? ($linksDetailed["desktop_percentage"] ?? 0.0));
?>

<div class="back-card-graphic shadow-card-graphic stats-card flex-column gap18 w100">
  <div class="flex-row top-start gap12 w100">
    <span class="stats-icon-box">
      <?= svg("sliders", "x18"); ?>
    </span>
    <div class="ui-field-header">
      <p class="m0 bold500 texto">Dispositivos de Conversión</p>
      <p class="m0 color-secondary">Proporción general de clics originados en smartphones versus ordenadores.</p>
    </div>
  </div>

  <div class="stats-subcard flex-column gap12 w100">
    <div class="flex-row center-between wrap gap10 w100">
      <span class="flex-row center-start gap8">
        <span class="stats-dot-primary"></span>
        <span class="color-secondary">Móvil: <strong class="texto bold500"><?= e((string)$mobilePct); ?>%</strong></span>
      </span>
      <span class="flex-row center-start gap8">
        <span class="stats-dot-secondary"></span>
        <span class="color-secondary">Escritorio: <strong class="texto bold500"><?= e((string)$desktopPct); ?>%</strong></span>
      </span>
    </div>

    <!-- Barra de proporción visual coherente con back1/color1 -->
    <div class="stats-dual-bar">
      <div class="stats-bar-primary" style="width: <?= min(100, max(0, (float)$mobilePct)); ?>%;" title="Móvil: <?= e((string)$mobilePct); ?>%"></div>
      <div class="stats-bar-secondary" style="width: <?= min(100, max(0, (float)$desktopPct)); ?>%;" title="Escritorio: <?= e((string)$desktopPct); ?>%"></div>
    </div>
  </div>
</div>
