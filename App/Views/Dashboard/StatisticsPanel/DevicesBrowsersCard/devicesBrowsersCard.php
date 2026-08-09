<?php
  /** 
   * @var array $devices   Métricas del mes activo por tipo de dispositivo
   * @var array $browsers  Métricas del mes activo por navegador
   */

  $devices  = $devices ?? [];
  $browsers = $browsers ?? [];

  $deviceNamesMap = [
    'mobile'  => 'Móvil',
    'desktop' => 'Escritorio',
    'tablet'  => 'Tablet'
  ];

  $deviceLabels = [];
  $deviceSeries = [];
  foreach ($devices as $d) {
    $rawType = strtolower(trim($d['device_type'] ?? 'desktop'));
    $deviceLabels[] = $deviceNamesMap[$rawType] ?? ucfirst($rawType);
    $deviceSeries[] = (int)($d['total'] ?? 0);
  }

  $browserLabels = [];
  $browserTotals = [];
  foreach ($browsers as $b) {
    $browserLabels[] = $b['browser'] ?? 'Desconocido';
    $browserTotals[] = (int)($b['total'] ?? 0);
  }
?>

<div class="grid col-desk-2 col-mid-1 col-sml-1 gap15 w100 text-protected">
  
  <!-- 1. Dispositivos de Acceso con Gráfico Donut Directo -->
  <div class="flex-column gap15 p20 br20 back-card-graphic shadow-card-graphic hover-scale-soft">
    <div class="flex-row center-between wrap gap10">
      <div>
        <h4 class="bold700 x20 texto flex-row center-start gap8">
          <?= svg("user-solid", "x20") ?> Dispositivos de Acceso
        </h4>
        <p class="text-muted mt3">
          Distribución de las visitas recibidas en el mes activo según el tipo de dispositivo de tus usuarios.
        </p>
      </div>
      <span class="advice bold500">
        Período Mensual
      </span>
    </div>

    <?php if (!empty($devices)) : ?>
      <div class="chart-devices-donut w100 mt5" 
           style="min-height: 250px;"
           data-chart-labels='<?= json_encode($deviceLabels) ?>'
           data-chart-series='<?= json_encode($deviceSeries) ?>'>
      </div>
    <?php else : ?>
      <p class="text-muted">No hay datos de dispositivos registrados este mes.</p>
    <?php endif; ?>
  </div>

  <!-- 2. Navegadores & Apps Integradas (In-App) con Gráfico Horizontal Basic Bar Directo -->
  <div class="flex-column gap15 p20 br20 back-card-graphic shadow-card-graphic hover-scale-soft">
    <div class="flex-row center-between wrap gap10">
      <div>
        <h4 class="bold700 x20 texto flex-row center-start gap8">
          <?= svg("server-solid", "x20") ?> Navegadores & In-App
        </h4>
        <p class="text-muted mt3">
          Principales navegadores y aplicaciones integradas donde los usuarios abrieron tu cuaderno digital este mes.
        </p>
      </div>
      <span class="advice bold500">
        Período Mensual
      </span>
    </div>

    <?php if (!empty($browsers)) : ?>
      <div class="chart-browsers-bar w100 mt5" 
           style="min-height: 250px;"
           data-chart-labels='<?= json_encode($browserLabels) ?>'
           data-chart-totals='<?= json_encode($browserTotals) ?>'>
      </div>
    <?php else : ?>
      <p class="text-muted">No hay datos de navegadores registrados este mes.</p>
    <?php endif; ?>
  </div>

</div>
