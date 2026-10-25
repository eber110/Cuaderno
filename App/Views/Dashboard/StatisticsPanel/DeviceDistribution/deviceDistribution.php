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

<div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
  <div class="flex-row center-between wrap gap10 w100">
    <div class="flex-row center-start gap10">
      <span class="flex-row center-center color-secondary x20">
        <?= svg("sliders", "x20"); ?>
      </span>
      <div class="flex-column gap2">
        <p class="m0 bold600 texto">Dispositivos de Conversión</p>
        <p class="m0 x13 color-secondary">Proporción general de clics originados en smartphones versus ordenadores.</p>
      </div>
    </div>

    <div class="flex-row center-end gap15 x13">
      <span class="flex-row center-start gap5">
        <span class="back-success" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%;"></span>
        <span class="color-secondary">Móvil: <strong class="texto"><?= e((string)$mobilePct); ?>%</strong></span>
      </span>
      <span class="flex-row center-start gap5">
        <span class="back8" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%;"></span>
        <span class="color-secondary">Escritorio: <strong class="texto"><?= e((string)$desktopPct); ?>%</strong></span>
      </span>
    </div>
  </div>

  <div class="flex-column gap8 w100 pt5">
    <!-- Barra de proporción visual bicolor idéntica a cto.php -->
    <div class="w100 hpx12 br10 flex-row overflow-hidden" style="background: rgba(128,128,128,0.15);">
      <div class="back-success" style="width: <?= min(100, max(0, (float)$mobilePct)); ?>%; height: 100%; transition: width 0.3s;" title="Móvil: <?= e((string)$mobilePct); ?>%"></div>
      <div class="back8" style="width: <?= min(100, max(0, (float)$desktopPct)); ?>%; height: 100%; transition: width 0.3s;" title="Escritorio: <?= e((string)$desktopPct); ?>%"></div>
    </div>
  </div>
</div>
