<?php
  /** 
   * @var array $summary                Métricas del mes actual (total_views, unique_views, total_clicks, ctr)
   * @var array $allTimeSummary         Métricas históricas acumuladas (total_views, unique_views, total_clicks)
   * @var array $viewsByDayOfMonth      Desglose de visitas, visitas únicas y clics por día del mes actual
   * @var array $viewsByWeekOfMonth     Desglose de visitas y visitas únicas por semana del mes actual
   * @var array $topLinks               Top enlaces del widget de contenido con mayor cantidad de clics
   * @var int   $totalRrssClicks        Total de clics en redes sociales del mes actual
   * @var array $rrssLinks              Ranking de clics en redes sociales
   * @var array $recommendation         Recomendación inteligente de día y hora pico
   * @var array $socialStats            Métricas por red social
   */

  $topLinks         = $topLinks ?? [];
  $totalRrssClicks  = $totalRrssClicks ?? 0;
  $rrssLinks        = $rrssLinks ?? [];
  $recommendation   = $recommendation ?? ['bestDay' => 'Sábado', 'bestHour' => '14:00 - 15:00'];
  $socialStats      = $socialStats ?? [];

  $topSocial = !empty($socialStats) ? $socialStats[0] : ['name' => 'Instagram', 'bestDay' => 'Sábado', 'bestHour' => '14:00 - 15:00'];

  $chartDates       = [];
  $chartViews       = [];
  $chartClicks      = [];
  $chartUniqueViews = [];
  foreach ($viewsByDayOfMonth as $d) {
    $chartDates[]       = $d['date_str'] ?? ($d['day_num'] ?? '');
    $chartViews[]       = (int)($d['total_views'] ?? $d['total'] ?? 0);
    $chartClicks[]      = (int)($d['total_clicks'] ?? 0);
    $chartUniqueViews[] = (int)($d['unique_views'] ?? 0);
  }

  $weekLabels  = [];
  $weekViews   = [];
  $weekUniques = [];
  foreach ($viewsByWeekOfMonth as $w) {
    $weekLabels[]  = $w['week_label'] ?? ('Semana ' . ($w['week_num'] ?? ''));
    $weekViews[]   = (int)($w['total'] ?? 0);
    $weekUniques[] = (int)($w['unique_total'] ?? 0);
  }

  $linkLabels = [];
  $linkClicks = [];
  foreach ($topLinks as $l) {
    $linkLabels[] = $l['link_name'] ?? 'Enlace';
    $linkClicks[] = (int)($l['total'] ?? 0);
  }

  $rrssLabels = [];
  $rrssClicks = [];
  foreach ($rrssLinks as $r) {
    $rrssLabels[] = $r['link_name'] ?? 'Red Social';
    $rrssClicks[] = (int)($r['total'] ?? 0);
  }

  $ctrValue = (float)($summary['ctr'] ?? 0);
?>
<link rel="stylesheet" href="/App/Rsc/Library/ApexCharts/apexcharts.css">
<script src="/App/Rsc/Library/ApexCharts/apexcharts.min.js" defer></script>

<div class="flex-column gap15 w100">

  <!-- GRID DE 4 TARJETAS PRINCIPALES -->
  <div class="grid col-desk-4 col-mid-2 col-sml-2 gap15 w100">

    <!-- 1. Card Total Visitas (Del Mes) -->
    <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft modal-btn darken pointer |hover-scale-soft relative">
      <div class="flex-row center-center w100">
        <span class="text-muted bold500">Total Visitas (Mes)</span>
      </div>
      <span class="x22 bold700 text-primary"><?= number_format($summary['total_views'] ?? 0) ?></span>
      <span class="flex-row center-center gap5 back-modal-item pl10 pr10 p5 br50"><?= svg("eye", "x20") ?> Ver desglose y gráfico</span>
    </div>

    <!-- MODAL DE DESGLOSE DE TOTAL VISITAS -->
    <div class="hidden">
      <div class="flex-column center-center w100 p20 h-dvh">
        <div class="wpx600 w-sml-100">
          <div id="close-modal-btn" class="modal-close-button pointer absolute top right p2 m5 closed-modal-preview br50 z-index-20">
            <?= svg("xmark", "x20") ?>
          </div>
        </div>
        <div class="wpx600 w-sml-100 overflow-y-scroll back-modal-item br-desk-15 br-mid-15 br-sml-0 p25 text-menu-modal relative shadow-1 flex-column gap20">
          
          <div class="flex-column gap5">
            <h3 class="bold700 x20 flex-row center-start gap10 textb">
              <?= svg("chart", "x20") ?> Desglose de Visitas y Gráficos
            </h3>
            <p class="text-muted">
              Monitoreo detallado de tráfico diario y semanal para medir el rendimiento de tu cuaderno digital.
            </p>
          </div>
    
          <div class="grid col-desk-2 col-sml-2 gap12 w100">
            <div class="flex-column p15 br12 border-item-panel back-body gap3">
              <span class="text-muted bold500">Total Histórico Completo</span>
              <span class="x22 bold700 text-primary"><?= number_format($allTimeSummary['total_views'] ?? 0) ?></span>
              <span class="text-muted">Todas las visitas acumuladas</span>
            </div>
            <div class="flex-column p15 br12 border-item-panel back-body gap3">
              <span class="text-muted bold500">Visitas Mes Actual</span>
              <span class="x22 bold700 textb"><?= number_format($summary['total_views'] ?? 0) ?></span>
              <span class="text-muted">Período en curso</span>
            </div>
          </div>
    
          <div class="w100 border-top"></div>

          <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft">
            <div class="flex-row center-between">
              <h4 class="bold600 x20 flex-row center-start gap5 textb">
                <?= svg("chart", "x16") ?> Gráfico Mixto (Visitas vs Clics)
              </h4>
              <span class="p2 pl8 pr8 br50 back-primary textw bold600">ApexCharts</span>
            </div>
            <p class="text-muted">
              Compara la cantidad total de visitas por día (barras) con la cantidad de clics en los enlaces de tu widget (línea) para evaluar la conversión diaria.
            </p>
            <div class="chart-summary-combo w100" 
                 style="min-height: 270px;"
                 data-chart-dates='<?= json_encode($chartDates) ?>'
                 data-chart-views='<?= json_encode($chartViews) ?>'
                 data-chart-clicks='<?= json_encode($chartClicks) ?>'>
            </div>
          </div>
    
          <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft">
            <div class="flex-row center-between">
              <h4 class="bold600 x20 flex-row center-start gap5 textb">
                <?= svg("clock", "x16") ?> Visitas por Semana (Mes Actual)
              </h4>
              <span class="text-muted font-mono"><?= count($viewsByWeekOfMonth) ?> semanas</span>
            </div>
            <p class="text-muted">
              Mide el volumen total de impresiones y visitas acumuladas en cada semana del mes activo para identificar períodos de mayor actividad.
            </p>
            <div class="chart-summary-weeks w100" 
                 style="min-height: 220px;"
                 data-chart-weeks='<?= json_encode($weekLabels) ?>'
                 data-chart-week-views='<?= json_encode($weekViews) ?>'>
            </div>
          </div>
    
        </div>
      </div>
    </div>

    <!-- 2. Card Visitas Únicas (Del Mes) -->
    <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft modal-btn darken pointer |hover-scale-soft relative">
      <div class="flex-row center-center w100">
        <span class="text-muted bold500">Visitas Únicas (Mes)</span>
      </div>
      <span class="x22 bold700 text-primary"><?= number_format($summary['unique_views'] ?? 0) ?></span>
      <span class="flex-row center-center gap5 back-modal-item pl10 pr10 p5 br50"><?= svg("eye", "x20") ?> Ver desglose y gráfico</span>
    </div>

    <!-- MODAL DE DESGLOSE DE VISITAS ÚNICAS -->
    <div class="hidden">
      <div class="flex-column center-center w100 p20 h-dvh">
        <div class="wpx600 w-sml-100">
          <div id="close-modal-btn" class="modal-close-button pointer absolute top right p2 m5 closed-modal-preview br50 z-index-20">
            <?= svg("xmark", "x20") ?>
          </div>
        </div>
        <div class="wpx600 w-sml-100 overflow-y-scroll back-modal-item br-desk-15 br-mid-15 br-sml-0 p25 text-menu-modal relative shadow-1 flex-column gap20">
          
          <div class="flex-column gap5">
            <h3 class="bold700 x20 flex-row center-start gap10 textb">
              <?= svg("chart", "x20") ?> Desglose de Visitas Únicas
            </h3>
            <p class="text-muted">
              Monitoreo exclusivo de usuarios únicos (IPs distintas) para medir el alcance real sin contabilizar visitas repetidas.
            </p>
          </div>
    
          <div class="grid col-desk-2 col-sml-2 gap12 w100">
            <div class="flex-column p15 br12 border-item-panel back-body gap3">
              <span class="text-muted bold500">Únicas Históricas</span>
              <span class="x22 bold700 text-primary"><?= number_format($allTimeSummary['unique_views'] ?? 0) ?></span>
              <span class="text-muted">Total acumulado de IPs únicas</span>
            </div>
            <div class="flex-column p15 br12 border-item-panel back-body gap3">
              <span class="text-muted bold500">Únicas Mes Actual</span>
              <span class="x22 bold700 textb"><?= number_format($summary['unique_views'] ?? 0) ?></span>
              <span class="text-muted">Usuarios únicos este mes</span>
            </div>
          </div>
    
          <div class="w100 border-top"></div>

          <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft">
            <div class="flex-row center-between">
              <h4 class="bold600 x20 flex-row center-start gap5 textb">
                <?= svg("chart", "x16") ?> Visitas Únicas por Día (Mes Actual)
              </h4>
              <span class="p2 pl8 pr8 br50 back-primary textw bold600">ApexCharts</span>
            </div>
            <p class="text-muted">
              Mide la cantidad de dispositivos únicos distintos que ingresaron a tu perfil cada día, descartando accesos múltiples del mismo usuario.
            </p>
            <div class="chart-summary-uniques-combo w100" 
                 style="min-height: 270px;"
                 data-chart-dates='<?= json_encode($chartDates) ?>'
                 data-chart-uniques='<?= json_encode($chartUniqueViews) ?>'>
            </div>
          </div>

          <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft">
            <div class="flex-row center-between">
              <h4 class="bold600 x20 flex-row center-start gap5 textb">
                <?= svg("clock", "x16") ?> Visitas Únicas por Semana
              </h4>
              <span class="text-muted font-mono"><?= count($viewsByWeekOfMonth) ?> semanas</span>
            </div>
            <p class="text-muted">
              Muestra el alcance semanal deduplicado, permitiéndote saber cuántas personas distintas atrajo tu contenido cada semana.
            </p>
            <div class="chart-summary-uniques-weeks w100" 
                 style="min-height: 230px;"
                 data-chart-weeks='<?= json_encode($weekLabels) ?>'
                 data-chart-week-uniques='<?= json_encode($weekUniques) ?>'>
            </div>
          </div>
    
        </div>
      </div>
    </div>

    <!-- 3. Card Total Clics (Del Mes - Enlaces del Widget) -->
    <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft modal-btn darken pointer |hover-scale-soft relative">
      <div class="flex-row center-center w100">
        <span class="text-muted bold500">Total Clics (Mes)</span>
      </div>
      <span class="x22 bold700 text-primary"><?= number_format($summary['total_clicks'] ?? 0) ?></span>
      <span class="flex-row center-center gap5 back-modal-item pl10 pr10 p5 br50"><?= svg("eye", "x20") ?> Ver desglose y gráfico</span>
    </div>

    <!-- MODAL DE DESGLOSE DE CLICS EN ENLACES DEL WIDGET -->
    <div class="hidden">
      <div class="flex-column center-center w100 p20 h-dvh">
        <div class="wpx600 w-sml-100">
          <div id="close-modal-btn" class="modal-close-button pointer absolute top right p2 m5 closed-modal-preview br50 z-index-20">
            <?= svg("xmark", "x20") ?>
          </div>
        </div>
        <div class="wpx600 w-sml-100 overflow-y-scroll back-modal-item br-desk-15 br-mid-15 br-sml-0 p25 text-menu-modal relative shadow-1 flex-column gap20">
          
          <div class="flex-column gap5">
            <h3 class="bold700 x20 flex-row center-start gap10 textb">
              <?= svg("chart", "x20") ?> Desglose de Clics en Enlaces del Widget
            </h3>
            <p class="text-muted">
              Análisis detallado de interacción en los enlaces principales de tu widget (las redes sociales se miden por separado).
            </p>
          </div>
    
          <div class="grid col-desk-2 col-sml-2 gap12 w100">
            <div class="flex-column p15 br12 border-item-panel back-body gap3">
              <span class="text-muted bold500">Clics Históricos</span>
              <span class="x22 bold700 text-primary"><?= number_format($allTimeSummary['total_clicks'] ?? 0) ?></span>
              <span class="text-muted">Total acumulado en enlaces</span>
            </div>
            <div class="flex-column p15 br12 border-item-panel back-body gap3">
              <span class="text-muted bold500">Clics Mes Actual</span>
              <span class="x22 bold700 textb"><?= number_format($summary['total_clicks'] ?? 0) ?></span>
              <span class="text-muted">Interacciones en enlaces</span>
            </div>
          </div>
    
          <div class="w100 border-top"></div>

          <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft">
            <div class="flex-row center-between">
              <h4 class="bold600 x20 flex-row center-start gap5 textb">
                <?= svg("chart", "x16") ?> Clics por Día (Mes Actual)
              </h4>
              <span class="p2 pl8 pr8 br50 back-primary textw bold600">ApexCharts</span>
            </div>
            <p class="text-muted">
              Muestra la cantidad total de clics registrados exclusivamente en los enlaces de tu widget día a día durante el mes activo.
            </p>
            <div class="chart-summary-clicks-daily w100" 
                 style="min-height: 270px;"
                 data-chart-dates='<?= json_encode($chartDates) ?>'
                 data-chart-clicks='<?= json_encode($chartClicks) ?>'>
            </div>
          </div>

          <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft">
            <div class="flex-row center-between">
              <h4 class="bold600 x20 flex-row center-start gap5 textb">
                <?= svg("link", "x16") ?> Top Enlaces más Clicados
              </h4>
              <span class="text-muted font-mono"><?= count($topLinks) ?> enlaces</span>
            </div>
            <p class="text-muted">
              Ranking de los enlaces y botones de tu widget con mayor cantidad de interacción acumulada.
            </p>
            <div class="chart-summary-top-links w100" 
                 style="min-height: 230px;"
                 data-chart-links='<?= json_encode($linkLabels) ?>'
                 data-chart-link-clicks='<?= json_encode($linkClicks) ?>'>
            </div>
          </div>
    
        </div>
      </div>
    </div>

    <!-- 4. NUEVA CARD: Clics a mis RRSS (Mes) -->
    <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft modal-btn darken pointer |hover-scale-soft relative">
      <div class="flex-row center-center w100">
        <span class="text-muted bold500">Clics a mis RRSS (Mes)</span>
      </div>
      <span class="x22 bold700 text-primary"><?= number_format($totalRrssClicks) ?></span>
      <span class="flex-row center-center gap5 back-modal-item pl10 pr10 p5 br50"><?= svg("eye", "x20") ?> Ver desglose y gráfico</span>
    </div>

    <!-- MODAL DE DESGLOSE DE CLICS A REDES SOCIALES -->
    <div class="hidden">
      <div class="flex-column center-center w100 p20 h-dvh">
        <div class="wpx600 w-sml-100">
          <div id="close-modal-btn" class="modal-close-button pointer absolute top right p2 m5 closed-modal-preview br50 z-index-20">
            <?= svg("xmark", "x20") ?>
          </div>
        </div>
        <div class="wpx600 w-sml-100 overflow-y-scroll back-modal-item br-desk-15 br-mid-15 br-sml-0 p25 text-menu-modal relative shadow-1 flex-column gap20">
          
          <div class="flex-column gap5">
            <h3 class="bold700 x20 flex-row center-start gap10 textb">
              <?= svg("share-from", "x20") ?> Desglose de Clics a tus Redes Sociales
            </h3>
            <p class="text-muted">
              Medición detallada de interacciones en tus perfiles y canales de redes sociales vinculados a tu cuaderno digital.
            </p>
          </div>
    
          <div class="grid col-desk-2 col-sml-2 gap12 w100">
            <div class="flex-column p15 br12 border-item-panel back-body gap3">
              <span class="text-muted bold500">Total Clics RRSS</span>
              <span class="x22 bold700 text-primary"><?= number_format($totalRrssClicks) ?></span>
              <span class="text-muted">Interacciones este mes</span>
            </div>
            <div class="flex-column p15 br12 border-item-panel back-body gap3">
              <span class="text-muted bold500">Redes Activas</span>
              <span class="x22 bold700 textb"><?= count($rrssLinks) ?></span>
              <span class="text-muted">Perfiles vinculados</span>
            </div>
          </div>
    
          <div class="w100 border-top"></div>

          <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft">
            <div class="flex-row center-between">
              <h4 class="bold600 x20 flex-row center-start gap5 textb">
                <?= svg("chart", "x16") ?> Clics por Red Social (Ranking)
              </h4>
              <span class="p2 pl8 pr8 br50 back-primary textw bold600">ApexCharts</span>
            </div>
            <p class="text-muted">
              Gráfico de barras que compara la cantidad de clics que recibió cada una de tus redes sociales (GitHub, LinkedIn, Instagram, X/Twitter, etc.).
            </p>
            <div class="chart-summary-rrss-links w100" 
                 style="min-height: 230px;"
                 data-chart-links='<?= json_encode($rrssLabels) ?>'
                 data-chart-link-clicks='<?= json_encode($rrssClicks) ?>'>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>

  <!-- TARJETA DESTACADA GRANDE DE CTR GLOBAL (COLUMNA IZQ: GAUGE | COLUMNA DER: TEXTO, BOTÓN Y MODAL) -->
  <div class="grid col-desk-12 gap20 p20 br20 border-item-panel back-body shadow-soft w100 center-center">
    
    <!-- Columna Izquierda: Gráfico Gauge / Medidor Radial ApexCharts -->
    <div class="col-span-desk-5 col-span-mid-5 col-span-sml-12 flex-column center-center w100 h100 relative">
      <div class="chart-ctr-gauge w100 flex-row center-center" data-ctr-value="<?= $ctrValue ?>"></div>
    </div>

    <!-- Columna Derecha: Título, Porcentaje, Descripción y Botón Modal de Optimización -->
    <div class="col-span-desk-7 col-span-mid-7 col-span-sml-12 flex-column center-start gap12 w100">
      <div class="flex-row center-between wrap w100 gap10">
        <h4 class="bold700 x20 textb flex-row center-start gap8">
          <?= svg("chart", "x20") ?> CTR Global (Tasa de Conversión)
        </h4>
        <span class="p5 pl12 pr12 br50 back-modal-item text-success bold700 x18">
          <?= number_format($ctrValue, 2) ?>%
        </span>
      </div>

      <p class="text-muted line-height-1-5">
        El CTR (Click-Through Rate) mide el porcentaje de visitantes que hicieron clic en al menos uno de tus enlaces respecto al total de visitas recibidas. Un CTR más alto indica mayor efectividad y atractivo en tu llamado a la acción.
      </p>

      <div class="modal-btn pointer darken mt5">
        <span class="flex-row center-center gap8 back-modal-item textb pl15 pr15 p10 br50 bold500 shadow-soft |hover-scale-soft">
          <?= svg("lightbulb", "x18") ?> Ver cómo optimizarlo
        </span>
      </div>

      <!-- MODAL DE OPTIMIZACIÓN Y ESTRATEGIA DE CTR -->
      <div class="hidden">
        <div class="flex-column center-center w100 p20 h-dvh">
          <div class="wpx600 w-sml-100">
            <div id="close-modal-btn" class="modal-close-button pointer absolute top right p2 m5 closed-modal-preview br50 z-index-20">
              <?= svg("xmark", "x20") ?>
            </div>
          </div>
          <div class="wpx600 w-sml-100 overflow-y-scroll back-modal-item br-desk-15 br-mid-15 br-sml-0 p25 text-menu-modal relative shadow-1 flex-column gap20">
            
            <!-- Encabezado del Modal -->
            <div class="flex-column gap5">
              <h3 class="bold700 x20 flex-row center-start gap10 textb">
                <?= svg("chart", "x20") ?> ¿Qué es el CTR y cómo mejorarlo?
              </h3>
              <p class="text-muted">
                Explicación detallada de la Tasa de Clics (Click-Through Rate) y estrategia recomendada para maximizar la conversión de tus visitas.
              </p>
            </div>

            <!-- Indicadores de Diagnóstico del CTR -->
            <div class="grid col-desk-2 col-sml-2 gap12 w100">
              <div class="flex-column p15 br12 border-item-panel back-body gap3">
                <span class="text-muted bold500">Tu CTR Actual</span>
                <span class="x22 bold700 text-success"><?= number_format($ctrValue, 2) ?>%</span>
                <span class="text-muted">Porcentaje de conversión</span>
              </div>
              <div class="flex-column p15 br12 border-item-panel back-body gap3">
                <span class="text-muted bold500">Diagnóstico</span>
                <?php 
                  $diagText  = ($ctrValue >= 50) ? 'Excelente' : (($ctrValue >= 25) ? 'Bueno' : 'Mejorable');
                  $diagColor = ($ctrValue >= 50) ? 'text-success' : (($ctrValue >= 25) ? 'text-primary' : 'text-danger');
                ?>
                <span class="x22 bold700 <?= $diagColor ?>"><?= $diagText ?></span>
                <span class="text-muted">Basado en visitas vs clics</span>
              </div>
            </div>

            <div class="w100 border-top"></div>

            <!-- CONSEJO ESTRATÉGICO PERSONALIZADO (DÍA Y RED SOCIAL RECOMENDADA) -->
            <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft">
              <div class="flex-row center-between">
                <h4 class="bold600 x20 flex-row center-start gap8 textb">
                  <?= svg("lightbulb", "x16") ?> Recomendación de Estrategia Pico
                </h4>
                <span class="p2 pl8 pr8 br50 back-primary textw bold600">Recomendación</span>
              </div>
              <p class="text-muted">
                Según tus datos estadísticos, la mayor tasa de interacción la obtienes desde 
                <strong class="textb"><?= e($topSocial['name'] ?? 'Instagram') ?></strong>.
              </p>
              <div class="p15 br12 border-item-panel back-modal-item flex-column gap8">
                <span class="bold600 text-primary flex-row center-start gap5">
                  <?= svg("clock", "x14") ?> Ejemplo de Publicación Sugerida:
                </span>
                <p class="textb">
                  Publica o promociona tu enlace en <strong class="text-primary"><?= e($topSocial['name'] ?? 'Instagram') ?></strong> los días 
                  <strong class="textb"><?= e($recommendation['bestDay'] ?? 'Sábado') ?></strong> cerca de las 
                  <strong class="textb"><?= e($recommendation['bestHour'] ?? '14:00 - 15:00') ?></strong>.
                  En este horario tu audiencia muestra mayor propensión a hacer clic.
                </p>
              </div>
            </div>

            <!-- CONSEJOS PRÁCTICOS Y AVANZADOS PARA POTENCIAR EL CTR -->
            <div class="flex-column gap12 w100 p15 br15 border-item-panel back-body shadow-soft">
              <h4 class="bold600 x20 flex-row center-start gap8 textb">
                <?= svg("check", "x16") ?> 5 Estrategias Clave para Potenciar tu CTR
              </h4>
              <div class="flex-column gap12">
                <div class="flex-row center-start gap10">
                  <span class="p4 br50 back-primary textw bold700 x12 flex-row center-center min-wpx24 min-hpx24">1</span>
                  <span class="textb"><strong>Llamados a la acción directos y claros:</strong> Usa verbos de acción en tus botones como <em>"Escríbeme por WhatsApp"</em>, <em>"Ver mi portafolio"</em> o <em>"Descargar plantilla gratis"</em> en lugar de títulos genéricos.</span>
                </div>
                <div class="flex-row center-start gap10">
                  <span class="p4 br50 back-primary textw bold700 x12 flex-row center-center min-wpx24 min-hpx24">2</span>
                  <span class="textb"><strong>Jerarquía visual y posición estratégica:</strong> Ubica tus 2 o 3 enlaces principales en la parte superior de tu tarjeta para captar la atención en los primeros 3 segundos de lectura.</span>
                </div>
                <div class="flex-row center-start gap10">
                  <span class="p4 br50 back-primary textw bold700 x12 flex-row center-center min-wpx24 min-hpx24">3</span>
                  <span class="textb"><strong>Imágenes y miniaturas atractivas:</strong> Agrega íconos o portadas visuales representativas a cada enlace para incentivar el clic impulsivo.</span>
                </div>
                <div class="flex-row center-start gap10">
                  <span class="p4 br50 back-primary textw bold700 x12 flex-row center-center min-wpx24 min-hpx24">4</span>
                  <span class="textb"><strong>Promoción activa en horario pico:</strong> Difunde tu enlace en historias o publicaciones de tu red social principal coincidiendo con el día y hora pico recomendados arriba.</span>
                </div>
                <div class="flex-row center-start gap10">
                  <span class="p4 br50 back-primary textw bold700 x12 flex-row center-center min-wpx24 min-hpx24">5</span>
                  <span class="textb"><strong>Pruebas A/B y renovación continua:</strong> Modifica periódicamente los textos de tus botones y evalúa cuál genera más clics para mantener enganchada a tu audiencia.</span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

  </div>

</div>
