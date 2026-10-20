<?php
  use Base\Module\GraphicsModule;

  /**
   * Vista de Actividad Semanal Heatmap (Dashboard.StatisticsPanel.Heatmap.heatmap)
   *
   * Muestra la matriz de densidad de visitas por días y franjas horarias:
   * - Plan Premium: Matriz 5x7 con GraphicsModule::matrixHeatmap y recomendación inteligente de horarios de publicación.
   * - Plan Free: Teaser desenfocado con candado de suscripción.
   *
   * @var array $heatmap   Array con 'matrix' (días x horas) y 'recommendation'
   * @var bool  $isPremium Booleano que indica si el creador cuenta con suscripción activa
   */

  $heatmap        = is_array($heatmap ?? null) ? $heatmap : (is_array($stats["heatmap"] ?? null) ? $stats["heatmap"] : []);
  $matrix         = is_array($heatmap["matrix"] ?? null) ? $heatmap["matrix"] : [];
  $recommendation = (string)($heatmap["recommendation"] ?? "");
  $isPremium      = (bool)($isPremium ?? false);

  // Agrupación de las 24 horas en las 5 franjas canónicas de GraphicsModule::matrixHeatmap
  $timeSlots = [
    ['label' => '00-06h', 'name' => 'Madrugada (00:00 - 06:00)', 'hours' => [0, 1, 2, 3, 4, 5]],
    ['label' => '06-12h', 'name' => 'Mañana (06:00 - 12:00)',    'hours' => [6, 7, 8, 9, 10, 11]],
    ['label' => '12-15h', 'name' => 'Mediodía (12:00 - 15:00)',  'hours' => [12, 13, 14]],
    ['label' => '15-19h', 'name' => 'Tarde (15:00 - 19:00)',     'hours' => [15, 16, 17, 18]],
    ['label' => '19-24h', 'name' => 'Noche (19:00 - 24:00)',     'hours' => [19, 20, 21, 22, 23]],
  ];
  $daysCols = [
    1 => 'Lun',
    2 => 'Mar',
    3 => 'Mié',
    4 => 'Jue',
    5 => 'Vie',
    6 => 'Sáb',
    0 => 'Dom'
  ];

  // Calcular suma por bloque y determinar intensidad normalizada
  $slotMax = 0;
  $heatmapTotals = [];
  foreach ($timeSlots as $slot) {
    $rowCells = [];
    foreach ($daysCols as $dNum => $dShort) {
      $sum = 0;
      foreach ($slot['hours'] as $h) {
        $sum += (int)($matrix[$dNum][$h] ?? 0);
      }
      if ($sum > $slotMax) {
        $slotMax = $sum;
      }
      $rowCells[$dNum] = $sum;
    }
    $heatmapTotals[] = $rowCells;
  }

  $matrixHeatmapData = [];
  foreach ($heatmapTotals as $rIdx => $rowCells) {
    $row = [];
    $slotInfo = $timeSlots[$rIdx];
    foreach ($daysCols as $dNum => $dShort) {
      $v = $rowCells[$dNum];
      $intensity = $slotMax > 0 ? round($v / $slotMax, 2) : 0.0;
      $row[] = [
        'intensity' => $intensity,
        'val'       => "{$v} " . ($v === 1 ? 'visita' : 'visitas'),
        'label'     => "{$dShort} • {$slotInfo['name']}"
      ];
    }
    $matrixHeatmapData[] = $row;
  }
?>

<div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
  <div class="flex-row center-between wrap gap10 w100">
    <div class="flex-row center-start gap10">
      <span class="flex-row center-center color-secondary x20">
        <?= svg("calendar", "x20"); ?>
      </span>
      <div class="flex-column gap2">
        <p class="m0 bold600 texto">Actividad Semanal (Heatmap)</p>
        <p class="m0 x13 color-secondary">Distribución de audiencia por días y franjas horarias para optimizar tus lanzamientos.</p>
      </div>
    </div>

    <?php if (!$isPremium): ?>
      <span class="premium-lock-badge">
        <?= svg("lock", "x10"); ?> Premium
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isPremium): ?>
    <!-- Vista Desbloqueada (Premium): Recomendación + Matriz 5x7 -->
    <?php if (!empty($recommendation)): ?>
      <div class="p15 br15 back-card-graphic shadow-card-graphic flex-row center-start gap12">
        <span class="color-warning x24 flex-shrink-0"><?= svg("lightbulb", "x24"); ?></span>
        <div class="flex-column gap2">
          <span class="bold700 texto">Recomendación inteligente</span>
          <p class="m0 color-secondary leading-normal"><?= e($recommendation); ?></p>
        </div>
      </div>
    <?php endif; ?>

    <div class="w100 flex-column center pt10">
      <div class="w100 flex-column gap5">
        <div class="flex-row center-end gap10 pr15">
          <span>Días: <strong>Lun — Dom</strong></span>
          <span>•</span>
          <span>Horas: <strong>00:00 — 24:00</strong></span>
        </div>

        <div class="w100 flex-row center-center">
          <?= GraphicsModule::matrixHeatmap([
            'rows'   => array_column($timeSlots, 'label'),
            'cols'   => array_values($daysCols),
            'matrix' => $matrixHeatmapData,
            'showAxisX' => true
          ]); ?>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- Vista Bloqueada (Free Teaser) con GraphicsModule -->
    <div class="premium-blur-container w100">
      <div class="premium-blur-content flex-column gap10 w100 pt5">
        <div class="p12 br12 back-card-graphic shadow-card-graphic">
          <p class="m0">Recomendación: Tu mayor pico de audiencia ocurre los Miércoles en la tarde.</p>
        </div>
        <div class="w100">
          <?= GraphicsModule::matrixHeatmap([
            'rows'  => ['00-06h', '06-12h', '12-15h', '15-19h', '19-24h'],
            'cols'  => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            'color' => 'texto'
          ]); ?>
        </div>
      </div>

      <div class="premium-lock-overlay">
        <span class="color-warning x28 mb6"><?= svg("lock", "x28"); ?></span>
        <h4 class="m0 x16 bold700 texto mb4">Encuentra tu Mejor Horario para Publicar</h4>
        <p class="m0 color-secondary x13 max-wpx450 mb15">El Heatmap semanal te revela con exactitud los días y franjas horarias con mayor afluencia de tus seguidores.</p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x12"); ?>
          Desbloquear Heatmap Semanal
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
