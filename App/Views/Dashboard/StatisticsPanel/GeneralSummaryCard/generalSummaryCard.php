<?php
  /** 
   * @var array $summary                Métricas del mes actual (total_views, unique_views, total_clicks, ctr)
   * @var array $allTimeSummary         Métricas históricas acumuladas (total_views, unique_views, total_clicks)
   * @var array $viewsByDayOfMonth      Desglose de visitas y clics por día del mes actual
   * @var array $viewsByWeekOfMonth     Desglose de visitas y visitas únicas por semana del mes actual
   */

  $chartDates  = [];
  $chartViews  = [];
  $chartClicks = [];
  foreach ($viewsByDayOfMonth as $d) {
    $chartDates[]  = $d['date_str'] ?? ($d['day_num'] ?? '');
    $chartViews[]  = (int)($d['total_views'] ?? $d['total'] ?? 0);
    $chartClicks[] = (int)($d['total_clicks'] ?? 0);
  }

  $weekLabels  = [];
  $weekViews   = [];
  $weekUniques = [];
  foreach ($viewsByWeekOfMonth as $w) {
    $weekLabels[]  = $w['week_label'] ?? ('Semana ' . ($w['week_num'] ?? ''));
    $weekViews[]   = (int)($w['total'] ?? 0);
    $weekUniques[] = (int)($w['unique_total'] ?? 0);
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

  <!-- MODAL DE DESGLOSE DE TOTAL VISITAS CON GRÁFICOS APEXCHARTS -->
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
            Estadísticas detalladas por día, semana y gráfico interactivo Combo (Visitas vs Clics).
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

        <!-- SECCIÓN A: GRÁFICO MIXTO / COMBO (VISITAS VS CLICS) -->
        <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft">
          <div class="flex-row center-between">
            <h4 class="bold600 x20 flex-row center-start gap5 textb">
              <?= svg("chart", "x16") ?> Gráfico Mixto (Visitas vs Clics)
            </h4>
            <span class="p2 pl8 pr8 br50 back-primary textw bold600">ApexCharts</span>
          </div>
          <div class="chart-summary-combo w100" 
               style="min-height: 270px;"
               data-chart-dates='<?= json_encode($chartDates) ?>'
               data-chart-views='<?= json_encode($chartViews) ?>'
               data-chart-clicks='<?= json_encode($chartClicks) ?>'>
          </div>
        </div>
  
        <!-- SECCIÓN B: GRÁFICO HORIZONTAL DE VISITAS POR SEMANA (MES ACTUAL) -->
        <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft" id="chart-data-weeks-month">
          <div class="flex-row center-between">
            <h4 class="bold600 x20 flex-row center-start gap5 textb">
              <?= svg("clock", "x16") ?> Visitas por Semana (Mes Actual)
            </h4>
            <span class="text-muted font-mono"><?= count($viewsByWeekOfMonth) ?> semanas</span>
          </div>
  
          <!-- Contenedor para el gráfico de barras horizontales multicolor -->
          <div class="chart-summary-weeks w100" 
               style="min-height: 220px;"
               data-chart-weeks='<?= json_encode($weekLabels) ?>'
               data-chart-week-views='<?= json_encode($weekViews) ?>'>
          </div>
        </div>
  
      </div>
    </div>
  </div>

  <!-- 2. Card Visitas Únicas (Del Mes) con Modal de Desglose Integrado -->
  <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft modal-btn darken pointer |hover-scale-soft relative">
    <div class="flex-row center-between w100">
      <span class="text-muted bold500">Visitas Únicas (Mes)</span>
      <span class="p2 pl6 pr6 br50 back-modal-item text-primary bold600 flex-row center-center gap3">
        <?= svg("eye", "x14") ?> Ver
      </span>
    </div>
    <span class="x22 bold700 text-primary"><?= number_format($summary['unique_views'] ?? 0) ?></span>
    <span class="text-muted">Clic para ver desglose por semana</span>
  </div>

  <!-- MODAL DE DESGLOSE DE VISITAS ÚNICAS (GRÁFICO HORIZONTAL DYNAMIC LOADED) -->
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
            <?= svg("chart", "x20") ?> Desglose de Visitas Únicas
          </h3>
          <p class="text-muted">
            Análisis de usuarios únicos (IPs distintas) registrados semana a semana en el mes activo.
          </p>
        </div>
  
        <!-- Indicadores Resumen (Histórico vs Mes Actual) -->
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

        <!-- SECCIÓN: GRÁFICO HORIZONTAL DYNAMIC LOADED DE VISITAS ÚNICAS POR SEMANA -->
        <div class="flex-column gap10 w100 p15 br15 border-item-panel back-body shadow-soft">
          <div class="flex-row center-between">
            <h4 class="bold600 x20 flex-row center-start gap5 textb">
              <?= svg("chart", "x16") ?> Visitas Únicas por Semana
            </h4>
            <span class="p2 pl8 pr8 br50 back-primary textw bold600">ApexCharts</span>
          </div>
          <!-- Contenedor desacoplado con barras horizontales multicolor -->
          <div class="chart-summary-uniques-weeks w100" 
               style="min-height: 230px;"
               data-chart-weeks='<?= json_encode($weekLabels) ?>'
               data-chart-week-uniques='<?= json_encode($weekUniques) ?>'>
          </div>
        </div>
  
      </div>
    </div>
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
