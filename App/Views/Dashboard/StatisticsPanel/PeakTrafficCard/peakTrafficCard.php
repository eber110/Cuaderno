<?php
  /** 
   * @var array $topDays 
   * @var array $topHours 
   */
?>
<div class="grid col-desk-2 col-mid-1 col-sml-1 gap15 w100">
  
  <!-- Días de la Semana Más Visitados -->
  <div class="flex-column gap15 p20 br15 border-item-panel back-body shadow-soft">
    <div class="flex-row center-between wrap gap5">
      <p class="bold600 x20 flex-row center-start gap5"><?= svg("calendar", "x18") ?> Días Más Visitados</p>
      <?php if (!empty($topDays)) : ?>
        <span class="p5 pl10 pr10 br50 back-primary textw bold600">
          Día Pico: <?= e($topDays[0]['day_name']) ?>
        </span>
      <?php endif; ?>
    </div>

    <?php if (!empty($topDays)) : 
      $maxDayTotal = max(array_column($topDays, 'total')) ?: 1;
    ?>
      <div class="flex-column gap10 w100">
        <?php foreach ($topDays as $index => $d) : 
          $percent = round(($d['total'] / $maxDayTotal) * 100);
          $isPeak = ($index === 0);
        ?>
          <div class="flex-column gap5 p10 br10 border-item-panel <?= $isPeak ? 'back-modal-item' : '' ?>">
            <div class="flex-row center-between">
              <span class="bold600 flex-row center-start gap5">
                <?= e($d['day_name']) ?>
                <?php if ($isPeak) : ?>
                  <span class="p2 pl8 pr8 br50 back-primary textw bold700">Pico</span>
                <?php endif; ?>
              </span>
              <span class="bold700 text-muted"><?= e($d['total']) ?> visitas</span>
            </div>
            <div class="w100 br10 overflow-hidden hpx6 back-body">
              <div class="h100 br10 <?= $isPeak ? 'back-primary' : 'back-muted' ?>" style="width: <?= $percent ?>%;"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else : ?>
      <p class="text-muted">No hay datos registrados aún.</p>
    <?php endif; ?>
  </div>

  <!-- Horarios Más Concurridos (Gráfico de Línea Directo con Data Labels) -->
  <div class="flex-column gap15 p20 br15 border-item-panel back-body shadow-soft">
    <div class="flex-row center-between wrap gap5">
      <p class="bold600 x20 flex-row center-start gap5"><?= svg("clock", "x18") ?> Horarios Más Concurridos</p>
      <?php if (!empty($topHours)) : ?>
        <span class="p5 pl10 pr10 br50 back-primary textw bold600">
          Hora Pico: <?= e($topHours[0]['label']) ?>
        </span>
      <?php endif; ?>
    </div>

    <?php if (!empty($topHours)) : 
      // Ordenar cronológicamente por hora para el gráfico de línea
      $hoursSorted = $topHours;
      usort($hoursSorted, function($a, $b) {
        return (int)($a['hour_num'] ?? 0) <=> (int)($b['hour_num'] ?? 0);
      });

      $chartHourLabels = [];
      $chartHourTotals = [];
      foreach ($hoursSorted as $h) {
        $chartHourLabels[] = $h['label'] ?? '';
        $chartHourTotals[] = (int)($h['total'] ?? 0);
      }
    ?>
      <div class="chart-peak-hours-line w100 mt5"
           data-chart-hours='<?= json_encode($chartHourLabels) ?>'
           data-chart-totals='<?= json_encode($chartHourTotals) ?>'>
      </div>
    <?php else : ?>
      <p class="text-muted">No hay datos registrados aún.</p>
    <?php endif; ?>
  </div>

</div>
