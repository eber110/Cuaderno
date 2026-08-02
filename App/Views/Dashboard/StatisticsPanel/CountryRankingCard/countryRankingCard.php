<?php
  /** 
   * @var array $countries  Ranking de países del mes activo
   */

  $countries = $countries ?? [];

  $countryLabels = [];
  $countryTotals = [];
  $totalCountryVisits = 0;

  foreach ($countries as $c) {
    $cName = !empty($c['country_name']) ? $c['country_name'] : 'Desconocido';
    $cCode = !empty($c['country_code']) ? strtoupper($c['country_code']) : '';
    $label = $cCode ? "{$cName} ({$cCode})" : $cName;
    $val   = (int)($c['total'] ?? 0);

    $countryLabels[] = $label;
    $countryTotals[] = $val;
    $totalCountryVisits += $val;
  }
?>

<div class="flex-column gap15 p20 br20 border-item-panel back-body shadow-soft w100">
  
  <!-- Encabezado de la Card -->
  <div class="flex-row center-between wrap gap10">
    <div>
      <h4 class="bold700 x20 flex-row center-start gap8">
        <?= svg("globe", "x20") ?> Ranking de Países Visitantes
      </h4>
      <p class="text-muted mt3">
        Distribución geográfica de las visitas recibidas en el mes activo según el país de procedencia de la dirección IP.
      </p>
    </div>
    <span class="p5 pl10 pr10 br50 back-modal-item bold600 text-muted">
      Período Mensual
    </span>
  </div>

  <?php if (!empty($countries)) : ?>
    <div class="grid col-desk-12 gap20 w100 center-center mt5">
      
      <!-- Lado Izquierdo (7 columnas): Gráfico de Barras Horizontales ApexCharts -->
      <div class="col-span-desk-7 col-span-mid-7 col-span-sml-12 flex-column center-center w100">
        <div class="chart-countries-ranking w100" 
             style="min-height: 280px;"
             data-chart-labels='<?= json_encode($countryLabels) ?>'
             data-chart-totals='<?= json_encode($countryTotals) ?>'>
        </div>
      </div>

      <!-- Lado Derecho (5 columnas): Listado Resumen de Top Países -->
      <div class="col-span-desk-5 col-span-mid-5 col-span-sml-12 flex-column center-start gap10 w100">
        <h5 class="bold600 x16 textb flex-row center-start gap5 mb5">
          <?= svg("chart", "x16") ?> Resumen de Tráfico por País
        </h5>
        
        <div class="flex-column gap8 w100">
          <?php foreach ($countries as $index => $c) : 
            $cName  = !empty($c['country_name']) ? $c['country_name'] : 'Desconocido';
            $cCode  = !empty($c['country_code']) ? strtoupper($c['country_code']) : 'GLOBAL';
            $cVal   = (int)($c['total'] ?? 0);
            $pct    = ($totalCountryVisits > 0) ? round(($cVal / $totalCountryVisits) * 100, 1) : 0;
            $rankNum = $index + 1;
          ?>
            <div class="flex-row center-between p10 br12 border-item-panel back-modal-item w100">
              <div class="flex-row center-start gap10">
                <span class="p4 br50 back-primary textw bold700 x12 flex-row center-center min-wpx22 min-hpx22">
                  #<?= $rankNum ?>
                </span>
                <span class="bold600 textb x14"><?= e($cName) ?></span>
                <span class="p2 pl6 pr6 br50 back-body text-muted bold600 font-mono x11">
                  <?= e($cCode) ?>
                </span>
              </div>
              <div class="flex-row center-end gap8">
                <span class="bold700 text-primary x14"><?= number_format($cVal) ?> <span class="font-normal text-muted x12">visitas</span></span>
                <span class="p2 pl6 pr6 br50 back-body text-success bold600 x11"><?= $pct ?>%</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      </div>

    </div>
  <?php else : ?>
    <p class="text-muted">No hay visitas de países registradas este mes aún.</p>
  <?php endif; ?>

</div>
