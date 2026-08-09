<?php
  /** 
   * @var array $countries  Ranking de países del mes activo
   */

  $countries = $countries ?? [];

  $countryLabels = [];
  $countryTotals = [];

  foreach ($countries as $c) {
    $cName = !empty($c['country_name']) ? $c['country_name'] : 'Desconocido';
    $cCode = !empty($c['country_code']) ? strtoupper($c['country_code']) : '';
    $label = $cCode ? "{$cName} ({$cCode})" : $cName;
    $val   = (int)($c['total'] ?? 0);

    $countryLabels[] = $label;
    $countryTotals[] = $val;
  }
?>

<div class="flex-column gap15 p20 br20 back-card-graphic shadow-card-graphic hover-scale-soft w100 text-protected">
  
  <!-- Encabezado de la Card -->
  <div class="flex-row center-between wrap gap10">
    <div>
      <h4 class="bold700 x20 texto flex-row center-start gap8">
        <?= svg("globe", "x20") ?> Ranking de Países Visitantes
      </h4>
      <p class="text-muted mt3">
        Distribución geográfica de las visitas recibidas en el mes activo según el país de procedencia de la dirección IP.
      </p>
    </div>
    <span class="advice bold500">
      Período Mensual
    </span>
  </div>

  <?php if (!empty($countries)) : ?>
    <!-- Gráfico de Barras Horizontales ApexCharts a 100% de Ancho -->
    <div class="w100 mt5">
      <div class="chart-countries-ranking w100" 
           style="min-height: 250px;"
           data-chart-labels='<?= json_encode($countryLabels) ?>'
           data-chart-totals='<?= json_encode($countryTotals) ?>'>
      </div>
    </div>
  <?php else : ?>
    <p class="text-muted">No hay visitas de países registradas este mes aún.</p>
  <?php endif; ?>

</div>
