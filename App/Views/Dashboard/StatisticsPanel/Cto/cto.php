<?php
  use Base\Module\GraphicsModule;

  /**
   * @var array|null $cto   Array con datos de CTO, promedio de enlaces/visitas y distribución de clics Enlaces vs RRSS.
   * @var array|null $stats Estructura analítica global opcional
   */
  $monthlyViews          = (int)($cto["monthly_views"] ?? 0);
  $monthlyClicks         = (int)($cto["monthly_clicks"] ?? 0);
  $monthlyEnlaces        = (int)($cto["monthly_enlaces"] ?? 0);
  $monthlyRrss           = (int)($cto["monthly_rrss"] ?? 0);
  $statsList             = isset($stats) && is_array($stats) ? $stats : [];
  $ctoData               = isset($cto) && is_array($cto) ? $cto : (isset($statsList["cto"]) && is_array($statsList["cto"]) ? $statsList["cto"] : []);
  $cto                   = $ctoData;
  $monthLabel            = isset($cto["month_label"]) ? (string)$cto["month_label"] : "";

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

  // $arcMeterConfig   = is_array($cto["arc_meter"] ?? null) ? $cto["arc_meter"] : [
  //   "value"        => min(100.0, max(0.0, (float)$monthlyCtoRate)),
  //   "displayValue" => $monthlyCtoRate . "%",
  //   "label"        => "Tasa de conversión",
  //   "color"        => "color1",
  //   "colorLabel"   => "texto",
  //   "unit"         => "Tasa de conversión",
  //   "transition"   => 500,
  //   "showNumber"   => true,
  //   "showLabel"    => true
  // ];
?>

<div class="back-card-graphic shadow-card-graphic stats-card flex-column gap20 w100">

  <!-- Encabezado de la tarjeta -->
  <div class="flex-row center-between wrap gap12 w100">
    <div class="flex-row top-start gap12">
      <span class="stats-icon-box">
        <?= svg("chart-column-solid", "x18"); ?>
      </span>
      <div class="ui-field-header">
        <p class="m0 bold500 texto">Métricas de CTO y Rendimiento de Enlaces</p>
        <p class="m0 color-secondary">Promedio entre enlaces activos, visitas y tasa de clics por usuario.</p>
      </div>
    </div>

    <?php if (!empty($monthLabel)): ?>
      <span class="stats-pill-badge">
        <?= e($monthLabel); ?>
      </span>
    <?php endif; ?>
  </div>

  <!-- Fila de tarjetas de métricas -->
  <div class="flex-column gap15 w100">

    <!-- 1. Tasa de CTO del Mes con Gráfico Arc Meter -->
    <div class="stats-subcard flex-column gap14 w100">
      <div class="flex-row center-between">
        <div class="flex-row center-start gap8">
          <span class="flex-row center-center texto x18 flex-shrink-0"><?= svg("chart", "x18"); ?></span>
          <span class="texto bold500">CTO del Mes (Clics / Visitas)</span>
        </div>
      </div>

      <div class="flex-row-desk flex-column-mid bottom-center center-center-mid gap20 w100">
        <!-- Columna con el Gráfico vectorial Arc Meter -->
        <div class="w50 w-mid-70 w-sml-100">
          <?php _chart('arcMeter', [
            'color' => 'color1',
            'colorLabel' => 'texto',
            'axisLabel' => 'texto',
            'axisLabelSize' => 17,
            'transition' => 500,
            'barWidth' => 20,
            'width' => 100,
            'label' => 'Tasa de conversión',
            'value' => min(100.0, max(0.0, (float)$monthlyCtoRate)),
            'displayValue' => $monthlyCtoRate . '%',
          ]); ?>
        </div>

        <!-- Columna de métricas numéricas -->
        <div class="flex-column gap8 flex-1 w50 w-mid-100 w-sml-100">
          <p class="m0 texto">
            Promedio: <strong class="texto bold500"><?= e((string)$monthlyClicksPerVisit); ?></strong> clics por visita en el mes actual.
          </p>
          <div class="color-secondary">
            <strong class="texto bold500"><?= e((string)$monthlyClicks); ?></strong> clics registrados de un total de <strong class="texto bold500"><?= e((string)$monthlyViews); ?></strong> visitas este mes.
          </div>
        </div>
      </div>
    </div>

    <!-- 2. Relación Enlaces y Visitas / 3. Total de Clics Acumulados -->
    <div class="flex-row-desk flex-column-mid flex-column-sml center-center gap15 w100">
      <div class="stats-subcard flex-column gap8 w100">
        <span class="texto bold500">Promedio Enlaces / Visitas</span>
        <div class="flex-row center-start gap10 my5">
          <span class="x36 x-sml-28 bold500 texto stats-kpi-value"><?= e((string)$totalLinks); ?></span>
          <span class="color-secondary">enlaces activos</span>
        </div>
        <p class="m0 color-secondary">
          Promedio: <strong class="texto bold500"><?= e((string)$linksPerVisit); ?></strong> enlaces por cada visita recibida.
        </p>
        <div class="color-secondary pt8 stats-divider">
          <?= e((string)$contentLinks); ?> enlaces de contenido + <?= e((string)$rrssLinks); ?> redes sociales
        </div>
      </div>
  
      <div class="stats-subcard flex-column gap8 w100">
        <span class="texto bold500">Clics Acumulados Históricos</span>
        <div class="flex-row center-start gap10 my5">
          <span class="x36 x-sml-28 bold500 texto stats-kpi-value"><?= e((string)$allTimeClicks); ?></span>
          <span class="color-secondary">clics totales</span>
        </div>
        <p class="m0 color-secondary">
          CTO histórico global: <strong class="texto bold500"><?= e((string)$allTimeCtoRate); ?>%</strong> en <?= e((string)$allTimeViews); ?> visitas.
        </p>
        <div class="color-secondary pt8 stats-divider">
          Historial integral del perfil del creador
        </div>
      </div>
    </div>

  </div>

  <!-- Sección: Índice de distribución Enlaces vs Redes Sociales -->
  <div class="stats-subcard flex-column gap16 w100">
    
    <div class="flex-column gap12 w100">
      <div class="ui-field-header">
        <p class="m0 bold500 texto">Índice de Clics: Enlaces vs Redes Sociales</p>
        <p class="m0 color-secondary">
          Distribución de las interacciones hacia enlaces de contenido versus perfiles de redes sociales.
          <?php if (!$hasCurrentClicks && ($allTimeClicks > 0)): ?>
            <span>(Mostrando histórico acumulado por falta de clics en el mes actual)</span>
          <?php endif; ?>
        </p>
      </div>

      <div class="flex-row center-between wrap gap10 w100">
        <span class="flex-row center-start gap8">
          <span class="stats-dot-primary"></span>
          <span class="color-secondary">Enlaces: <strong class="texto bold500"><?= e((string)$barPctEnlaces); ?>%</strong></span>
        </span>
        <span class="flex-row center-start gap8">
          <span class="stats-dot-secondary"></span>
          <span class="color-secondary">RRSS: <strong class="texto bold500"><?= e((string)$barPctRrss); ?>%</strong></span>
        </span>
      </div>
    </div>

    <!-- Barra de proporción visual coherente con back1/color1 -->
    <div class="stats-dual-bar">
      <div class="stats-bar-primary" style="width: <?= min(100, max(0, (float)$barPctEnlaces)); ?>%;" title="Enlaces: <?= e((string)$barPctEnlaces); ?>%"></div>
      <div class="stats-bar-secondary" style="width: <?= min(100, max(0, (float)$barPctRrss)); ?>%;" title="RRSS: <?= e((string)$barPctRrss); ?>%"></div>
    </div>

    <!-- Desglose en 2 columnas -->
    <div class="flex-row-desk flex-column-mid flex-column-sml gap15 w100 pt4">

      <!-- Columna 1: Enlaces de Contenido -->
      <div class="stats-inner-card flex-column gap10 flex-1 min-w220">
        <div class="flex-row center-between gap8">
          <span class="flex-row center-start gap8 bold500 texto">
            <span class="stats-dot-primary"></span>
            Enlaces de Contenido
          </span>
          <span class="stats-pill-badge-solid">
            <?= e((string)$barPctEnlaces); ?>%
          </span>
        </div>

        <div class="flex-row center-start gap10 my2">
          <span class="x36 x-sml-28 bold500 texto stats-kpi-value"><?= e((string)$monthlyEnlaces); ?></span>
          <span class="color-secondary">clics este mes</span>
        </div>

        <div class="color-secondary">
          Histórico acumulado: <strong class="texto bold500"><?= e((string)$allTimeEnlaces); ?></strong> clics (<?= e((string)$pctEnlacesAllTime); ?>% del total general).
        </div>
      </div>

      <!-- Columna 2: Redes Sociales -->
      <div class="stats-inner-card flex-column gap10 flex-1 min-w220">
        <div class="flex-row center-between gap8">
          <span class="flex-row center-start gap8 bold500 texto">
            <span class="stats-dot-secondary"></span>
            Redes Sociales (RRSS)
          </span>
          <span class="stats-pill-badge">
            <?= e((string)$barPctRrss); ?>%
          </span>
        </div>

        <div class="flex-row center-start gap10 my2">
          <span class="x36 x-sml-28 bold500 texto stats-kpi-value"><?= e((string)$monthlyRrss); ?></span>
          <span class="color-secondary">clics este mes</span>
        </div>

        <div class="color-secondary">
          Histórico acumulado: <strong class="texto bold500"><?= e((string)$allTimeRrss); ?></strong> clics (<?= e((string)$pctRrssAllTime); ?>% del total general).
        </div>
      </div>

    </div>

    <!-- Conclusión inteligente del canal de mayor interés -->
    <div class="pt12 stats-divider w100">
      <p class="m0 color-secondary">
        <?php if ($predominantChannel === "rrss" || $predominantChannel === "rrss_history"): ?>
          Tus visitantes muestran mayor preferencia por conectar con tus <strong class="texto bold500">Redes Sociales</strong> (<?= e((string)$barPctRrss); ?>% de las interacciones).
        <?php elseif ($predominantChannel === "enlaces" || $predominantChannel === "enlaces_history"): ?>
          Tus visitantes interactúan con mayor frecuencia en tus <strong class="texto bold500">Enlaces de Contenido y Productos</strong> (<?= e((string)$barPctEnlaces); ?>% de las interacciones).
        <?php else: ?>
          La interacción de tus visitantes está <strong class="texto bold500">equilibrada</strong> entre tus Enlaces de Contenido y tus Redes Sociales.
        <?php endif; ?>
      </p>
    </div>

  </div>

</div>
