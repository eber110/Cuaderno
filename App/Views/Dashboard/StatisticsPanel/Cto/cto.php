<?php
  use Base\Module\GraphicsModule;

  /**
   * @var array $cto Array con datos de CTO, promedio de enlaces/visitas y distribución de clics Enlaces vs RRSS.
   */
  $monthlyViews          = (int)($cto["monthly_views"] ?? 0);
  $monthlyClicks         = (int)($cto["monthly_clicks"] ?? 0);
  $monthlyEnlaces        = (int)($cto["monthly_enlaces"] ?? 0);
  $monthlyRrss           = (int)($cto["monthly_rrss"] ?? 0);
  $monthLabel            = $cto["month_label"] ?? "";

  $monthlyCtoRate        = $cto["monthly_cto_rate"] ?? 0.0;
  $monthlyClicksPerVisit = $cto["monthly_clicks_per_visit"] ?? 0.0;
  $linksPerVisit         = $cto["links_per_visit"] ?? 0.0;
  $visitsPerLink         = $cto["visits_per_link"] ?? 0.0;

  $configuredLinks       = $cto["configured_links"] ?? [];
  $totalLinks            = (int)($configuredLinks["total_links"] ?? 0);
  $contentLinks          = (int)($configuredLinks["content_links"] ?? 0);
  $rrssLinks             = (int)($configuredLinks["rrss_links"] ?? 0);

  $pctEnlacesMonth       = $cto["pct_enlaces_month"] ?? 0.0;
  $pctRrssMonth          = $cto["pct_rrss_month"] ?? 0.0;

  $allTimeViews          = (int)($cto["all_time_views"] ?? 0);
  $allTimeClicks         = (int)($cto["all_time_clicks"] ?? 0);
  $allTimeEnlaces        = (int)($cto["all_time_enlaces"] ?? 0);
  $allTimeRrss           = (int)($cto["all_time_rrss"] ?? 0);
  $allTimeCtoRate        = $cto["all_time_cto_rate"] ?? 0.0;
  $pctEnlacesAllTime     = $cto["pct_enlaces_all_time"] ?? 0.0;
  $pctRrssAllTime        = $cto["pct_rrss_all_time"] ?? 0.0;

  $predominantChannel    = $cto["predominant_channel"] ?? "equal";

  // Determinar los porcentajes visuales para la barra comparativa:
  // Si el mes actual ya tiene clics, se usa el mes; si no, se usa el histórico acumulado
  $hasCurrentClicks = $monthlyClicks > 0;
  $barPctEnlaces    = $hasCurrentClicks ? $pctEnlacesMonth : $pctEnlacesAllTime;
  $barPctRrss       = $hasCurrentClicks ? $pctRrssMonth : $pctRrssAllTime;

  $arcMeterConfig   = is_array($cto["arc_meter"] ?? null) ? $cto["arc_meter"] : [
    "value"        => min(100.0, max(0.0, (float)$monthlyCtoRate)),
    "displayValue" => $monthlyCtoRate . "%",
    "label"        => "Tasa de conversión",
    "color"        => "texto",
    "colorLabel"   => "texto",
    "unit"         => "Tasa de conversión",
    "transition"   => 500,
    "showNumber"   => true,
    "showLabel"    => true
  ];
?>

<div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap20 w100">

  <!-- Encabezado de la tarjeta -->
  <div class="flex-row center-between wrap gap10 w100">
    <div class="flex-row center-start gap10">
      <span class="flex-row center-center color-secondary x20">
        <?= svg("chart-column-solid", "x20"); ?>
      </span>
      <div class="flex-column gap2">
        <h3 class="m0 x18 bold600 texto">Métricas de CTO y Rendimiento de Enlaces</h3>
        <p class="m0 x13 color-secondary">Promedio entre enlaces activos, visitas y tasa de clics por usuario.</p>
      </div>
    </div>

    <?php if (!empty($monthLabel)): ?>
      <span class="back-card-graphic shadow-card-graphic hover-scale-soft p5 pl10 pr10 br10">
        <?= e($monthLabel); ?>
      </span>
    <?php endif; ?>
  </div>

  <!-- Fila de 3 tarjetas de métricas -->
  <div class="flex-row wrap gap15 w100">

    <!-- 1. Tasa de CTO del Mes con Gráfico Arc Meter -->
    <div class="flex-column gap12 back-card-graphic shadow-card-graphic hover-scale-soft p20 br15 w100">
      <div class="flex-row center-between">
        <div class="flex-row center-start gap8">
          <span class="color-success x16"><?= svg("chart", "x16 color-success"); ?></span>
          <span class="x13 color-secondary bold600 uppercase">CTO del Mes (Clics / Visitas)</span>
        </div>
      </div>

      <div class="flex-row-desk flex-column-mid bottom-center center-center-mid gap20 w100">
        <!-- Columna con el Gráfico vectorial Arc Meter -->
        <div class="w50 w-mid-70 w-sml-100">
          <?= GraphicsModule::arcMeter($arcMeterConfig); ?>
        </div>

        <!-- Columna de métricas numéricas -->
        <div class="flex-column gap8 flex-1 w50 w-mid-100 w-sml-100">
          <!-- <div class="flex-row center-start gap10 my5">
            <span class="x42 bold700 texto leading-none"><?= e((string)$monthlyCtoRate); ?>%</span>
          </div> -->
          <p class="m0 texto">
            Promedio: <strong class="texto bold600"><?= e((string)$monthlyClicksPerVisit); ?></strong> clics por visita en el mes actual.
          </p>
          <div class="texto">
            <strong class="texto bold600"><?= e((string)$monthlyClicks); ?></strong> clics registrados de un total de <strong class="texto bold600"><?= e((string)$monthlyViews); ?></strong> visitas este mes.
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Relación Enlaces y Visitas -->
    <div class="flex-column gap8 back-card-graphic shadow-card-graphic hover-scale-soft p15 br10 w100">
      <div class="flex-row center-between">
        <span class="x13 color-secondary bold500">Promedio Enlaces / Visitas</span>
        <span class="color-primary x16"><?= svg("link", "x16"); ?></span>
      </div>
      <div class="flex-row center-start gap10 my5">
        <span class="x36 bold700 texto leading-none"><?= e((string)$totalLinks); ?></span>
        <span class="x14 color-secondary bold500">enlaces activos</span>
      </div>
      <p class="m0 x13 color-secondary">
        Promedio: <strong class="texto bold600"><?= e((string)$linksPerVisit); ?></strong> enlaces por cada visita recibida.
      </p>
      <div class="x12 color-inactive pt5" style="border-top: 1px solid rgba(128,128,128,0.12);">
        <?= e((string)$contentLinks); ?> enlaces de contenido + <?= e((string)$rrssLinks); ?> redes sociales
      </div>
    </div>

    <!-- 3. Total de Clics Acumulados -->
    <div class="flex-column gap8 back-card-graphic shadow-card-graphic hover-scale-soft p5 pl10 pr10 br10 w100">
      <div class="flex-row center-between">
        <span class="x13 color-secondary bold500">Clics Acumulados Históricos</span>
        <span class="color-secondary x16"><?= svg("globe", "x16"); ?></span>
      </div>
      <div class="flex-row center-start gap10 my5">
        <span class="x36 bold700 texto leading-none"><?= e((string)$allTimeClicks); ?></span>
        <span class="x14 color-secondary bold500">clics totales</span>
      </div>
      <p class="m0 x13 color-secondary">
        CTO histórico global: <strong class="texto bold600"><?= e((string)$allTimeCtoRate); ?>%</strong> en <?= e((string)$allTimeViews); ?> visitas.
      </p>
      <div class="x12 color-inactive pt5" style="border-top: 1px solid rgba(128,128,128,0.12);">
        Historial integral del perfil del creador
      </div>
    </div>

  </div>

  <!-- Sección: Índice de distribución Enlaces vs Redes Sociales -->
  <div class="flex-column gap15 back-card-graphic shadow-card-graphic hover-scale-soft p15 br10 w100">
    
    <div class="flex-row center-between wrap gap10 w100">
      <div class="flex-column gap3">
        <h4 class="m0 x16 bold600 texto">Índice de Clics: Enlaces vs Redes Sociales</h4>
        <p class="m0 x13 color-secondary">
          Distribución de las interacciones hacia enlaces de contenido versus perfiles de redes sociales.
          <?php if (!$hasCurrentClicks && ($allTimeClicks > 0)): ?>
            <span class="color-inactive">(Mostrando histórico acumulado por falta de clics en el mes actual)</span>
          <?php endif; ?>
        </p>
      </div>

      <div class="flex-row center-end gap15 x13">
        <span class="flex-row center-start gap5">
          <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: var(--success, #00b900);"></span>
          <span class="color-secondary">Enlaces: <strong><?= e((string)$barPctEnlaces); ?>%</strong></span>
        </span>
        <span class="flex-row center-start gap5">
          <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: var(--back-color6, #003CFF);"></span>
          <span class="color-secondary">RRSS: <strong><?= e((string)$barPctRrss); ?>%</strong></span>
        </span>
      </div>
    </div>

    <!-- Barra de proporción visual bicolor -->
    <div class="w100 hpx12 br10 flex-row overflow-hidden" style="background: rgba(128,128,128,0.15);">
      <div style="width: <?= e((string)$barPctEnlaces); ?>%; background: var(--success, #00b900); height: 100%; transition: width 0.3s;" title="Enlaces: <?= e((string)$barPctEnlaces); ?>%"></div>
      <div style="width: <?= e((string)$barPctRrss); ?>%; background: var(--back-color6, #003CFF); height: 100%; transition: width 0.3s;" title="RRSS: <?= e((string)$barPctRrss); ?>%"></div>
    </div>

    <!-- Desglose en 2 columnas -->
    <div class="flex-row wrap gap15 w100 pt10">

      <!-- Columna 1: Enlaces de Contenido -->
      <div class="p15 br12 flex-column gap10 flex-1 min-w220" style="background: rgba(0, 185, 0, 0.05); border: 1px solid rgba(0, 185, 0, 0.15);">
        <div class="flex-row center-between">
          <span class="flex-row center-start gap8 color-success bold600 x14">
            <?= svg("link", "x16 color-success"); ?>
            Enlaces de Contenido
          </span>
          <span class="badge p3 pl8 pr8 br8 x12 bold600" style="background: rgba(0, 185, 0, 0.15); color: var(--success, #00b900);">
            <?= e((string)$barPctEnlaces); ?>%
          </span>
        </div>

        <div class="flex-row center-start gap10 my2">
          <span class="x28 bold700 texto"><?= e((string)$monthlyEnlaces); ?></span>
          <span class="x13 color-secondary">clics este mes</span>
        </div>

        <div class="x12 color-secondary">
          Histórico acumulado: <strong class="texto bold600"><?= e((string)$allTimeEnlaces); ?></strong> clics (<?= e((string)$pctEnlacesAllTime); ?>% del total general).
        </div>
      </div>

      <!-- Columna 2: Redes Sociales -->
      <div class="p15 br12 flex-column gap10 flex-1 min-w220" style="background: rgba(0, 60, 255, 0.05); border: 1px solid rgba(0, 60, 255, 0.15);">
        <div class="flex-row center-between">
          <span class="flex-row center-start gap8 bold600 x14" style="color: var(--back-color6, #003CFF);">
            <?= svg("share-node", "x16"); ?>
            Redes Sociales (RRSS)
          </span>
          <span class="badge p3 pl8 pr8 br8 x12 bold600" style="background: rgba(0, 60, 255, 0.15); color: var(--back-color6, #003CFF);">
            <?= e((string)$barPctRrss); ?>%
          </span>
        </div>

        <div class="flex-row center-start gap10 my2">
          <span class="x28 bold700 texto"><?= e((string)$monthlyRrss); ?></span>
          <span class="x13 color-secondary">clics este mes</span>
        </div>

        <div class="x12 color-secondary">
          Histórico acumulado: <strong class="texto bold600"><?= e((string)$allTimeRrss); ?></strong> clics (<?= e((string)$pctRrssAllTime); ?>% del total general).
        </div>
      </div>

    </div>

    <!-- Conclusión inteligente del canal de mayor interés -->
    <div class="p10 pl15 pr15 br10 flex-row center-start gap10 x13" style="background: rgba(128,128,128,0.06);">
      <span class="color-primary x16"><?= svg("globe", "x16"); ?></span>
      <p class="m0 color-secondary">
        <?php if ($predominantChannel === "rrss" || $predominantChannel === "rrss_history"): ?>
          Tus visitantes muestran mayor preferencia por conectar con tus <strong class="texto bold600">Redes Sociales</strong> (<?= e((string)$barPctRrss); ?>% de las interacciones).
        <?php elseif ($predominantChannel === "enlaces" || $predominantChannel === "enlaces_history"): ?>
          Tus visitantes interactúan con mayor frecuencia en tus <strong class="texto bold600">Enlaces de Contenido y Productos</strong> (<?= e((string)$barPctEnlaces); ?>% de las interacciones).
        <?php else: ?>
          La interacción de tus visitantes está <strong class="texto bold600">equilibrada</strong> entre tus Enlaces de Contenido y tus Redes Sociales.
        <?php endif; ?>
      </p>
    </div>

  </div>

</div>
