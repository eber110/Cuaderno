<?php
  /** 
   * @var array $summary                Métricas del mes actual (total_views, unique_views, total_clicks, ctr)
   * @var array $allTimeSummary         Métricas históricas acumuladas (total_views, total_clicks)
   * @var array $viewsByDayOfMonth      Desglose de visitas por día del mes actual
   * @var array $viewsByWeekOfMonth     Desglose de visitas por semana del mes actual
   */

  $chartDates = [];
  $chartViews = [];
  foreach ($viewsByDayOfMonth as $d) {
    $chartDates[] = $d['date_str'] ?? ($d['day_num'] ?? '');
    $chartViews[] = (int)($d['total'] ?? 0);
  }
?>
<link rel="stylesheet" href="/App/Rsc/Library/ApexCharts/apexcharts.css">
<script src="/App/Rsc/Library/ApexCharts/apexcharts.min.js" defer></script>

<div class="grid col-desk-4 col-mid-2 col-sml-2 gap15 w100">

  <!-- 1. Card Total Visitas (Del Mes) con Modal de Desglose Integrado -->
  <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft modal-btn darken pointer |hover-scale-soft relative">
    <div class="flex-row center-between w100">
      <span class="text-muted bold500">Total Visitas (Mes)</span>
      <span class="p2 pl6 pr6 br50 back-modal-item text-primary bold600 flex-row center-center gap3">
        <?= svg("eye", "x14") ?> Ver
      </span>
    </div>
    <span class="x22 bold700 text-primary"><?= number_format($summary['total_views'] ?? 0) ?></span>
    <span class="text-muted">Clic para ver desglose y gráfico</span>
  </div>

  <!-- MODAL DE DESGLOSE DE VISITAS CON GRÁFICO MIXTO / COMBO APEXCHARTS -->
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
            <?= svg("chart", "x20") ?> Desglose de Visitas y Gráfico
          </h3>
          <p class="text-muted">
            Estadísticas detalladas por día, semana y gráfico interactivo Combo (Línea + Barras).
          </p>
        </div>
  
        <!-- Indicadores Resumen (Histórico vs Mes Actual) -->
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

        <!-- SECCIÓN DE GRÁFICO MIXTO / COMBO (APEXCHARTS) -->
        <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft">
          <div class="flex-row center-between">
            <h4 class="bold600 x20 flex-row center-start gap5 textb">
              <?= svg("chart", "x16") ?> Gráfico Mixto (Barras + Tendencia)
            </h4>
            <span class="p2 pl8 pr8 br50 back-primary textw bold600">ApexCharts</span>
          </div>
          <!-- Contenedor desacoplado con clase CSS e inyección de datos mediante data-attributes -->
          <div class="chart-summary-combo w100" 
               style="min-height: 270px;"
               data-chart-dates='<?= json_encode($chartDates) ?>'
               data-chart-views='<?= json_encode($chartViews) ?>'>
          </div>
        </div>
  
        <!-- SECCIÓN B: Desglose por Semana del Mes -->
        <div class="flex-column gap10 w100 mt5" id="chart-data-weeks-month">
          <div class="flex-row center-between">
            <h4 class="bold600 x20 flex-row center-start gap5 textb">
              <?= svg("clock", "x16") ?> Visitas por Semana (Mes Actual)
            </h4>
            <span class="text-muted font-mono"><?= count($viewsByWeekOfMonth) ?> semanas</span>
          </div>
  
          <?php if (!empty($viewsByWeekOfMonth)) : ?>
            <div class="grid col-desk-2 col-sml-1 gap10 w100">
              <?php foreach ($viewsByWeekOfMonth as $w) : 
                $weekLabel = $w['week_label'] ?? ('Semana ' . ($w['week_num'] ?? ''));
                $weekViews = (int)($w['total'] ?? 0);
              ?>
                <div class="flex-row center-between p12 br10 border-item-panel back-body chart-item-week" 
                     data-week="<?= e($weekLabel) ?>" 
                     data-views="<?= $weekViews ?>">
                  <span class="bold600 textb"><?= e($weekLabel) ?></span>
                  <span class="bold700 text-primary"><?= number_format($weekViews) ?> <span class="font-normal text-muted">visitas</span></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else : ?>
            <p class="text-muted p10 border-item-panel br10 text-c">No se registran visitas semanales en este mes aún.</p>
          <?php endif; ?>
        </div>
  
      </div>
    </div>
  </div>

  <!-- 2. Card Visitas Únicas (Del Mes) -->
  <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft">
    <span class="text-muted bold500">Visitas Únicas (Mes)</span>
    <span class="x22 bold700"><?= number_format($summary['unique_views'] ?? 0) ?></span>
  </div>

  <!-- 3. Card Total Clics (Del Mes) -->
  <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft">
    <span class="text-muted bold500">Total Clics (Mes)</span>
    <span class="x22 bold700"><?= number_format($summary['total_clicks'] ?? 0) ?></span>
  </div>

  <!-- 4. Card CTR Global (Del Mes) -->
  <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft">
    <span class="text-muted bold500">CTR Global (Mes)</span>
    <span class="x22 bold700 text-success"><?= number_format($summary['ctr'] ?? 0, 2) ?>%</span>
  </div>

</div>
