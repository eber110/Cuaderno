<?php
  /** 
   * @var array $topDays 
   * @var array $topHours 
   */
?>
<div class="grid col-desk-2 col-mid-1 col-sml-1 gap15 w100">
  
  <!-- Días Más Visitados (Gráfico de Columnas Directo con Data Labels) -->
  <div class="flex-column gap15 p20 br15 border-item-panel back-body shadow-soft">
    <div class="flex-column gap5">
      <div class="flex-row center-between wrap gap5">
        <p class="bold600 x20 flex-row center-start gap5"><?= svg("calendar", "x18") ?> Días Más Visitados</p>
        <?php if (!empty($topDays)) : 
          $peakDay = $topDays[0];
          foreach ($topDays as $td) {
            if ((int)($td['total'] ?? 0) > (int)($peakDay['total'] ?? 0)) {
              $peakDay = $td;
            }
          }
        ?>
          <span class="p5 pl10 pr10 br50 back-primary textw bold600">
            Día Pico: <?= e($peakDay['day_name']) ?> (<?= number_format($peakDay['total']) ?> visitas)
          </span>
        <?php endif; ?>
      </div>
      <p class="text-muted">
        Distribución total de visitas recibidas según el día de la semana (Lunes a Domingo), identificando el día pico de mayor afluencia de usuarios.
      </p>
    </div>

    <?php if (!empty($topDays)) : 
      // Ordenar los 7 días de la semana cronológicamente de Lunes a Domingo
      $daysChronological = [
        "1" => ["day_name" => "Lunes",     "total" => 0],
        "2" => ["day_name" => "Martes",    "total" => 0],
        "3" => ["day_name" => "Miércoles", "total" => 0],
        "4" => ["day_name" => "Jueves",    "total" => 0],
        "5" => ["day_name" => "Viernes",   "total" => 0],
        "6" => ["day_name" => "Sábado",    "total" => 0],
        "0" => ["day_name" => "Domingo",   "total" => 0],
      ];

      foreach ($topDays as $d) {
        $num = (string)($d['day_num'] ?? '');
        if (isset($daysChronological[$num])) {
          $daysChronological[$num]['total'] = (int)$d['total'];
        }
      }

      $chartDayNames  = [];
      $chartDayTotals = [];
      foreach ($daysChronological as $dc) {
        $chartDayNames[]  = $dc['day_name'];
        $chartDayTotals[] = $dc['total'];
      }
    ?>
      <div class="chart-peak-days-column w100 mt5"
           data-chart-days='<?= json_encode($chartDayNames) ?>'
           data-chart-totals='<?= json_encode($chartDayTotals) ?>'>
      </div>
    <?php else : ?>
      <p class="text-muted">No hay datos registrados aún.</p>
    <?php endif; ?>
  </div>

  <!-- Horarios Más Concurridos (Gráfico de Línea Directo con Data Labels) -->
  <div class="flex-column gap15 p20 br15 border-item-panel back-body shadow-soft">
    <div class="flex-column gap5">
      <div class="flex-row center-between wrap gap5">
        <p class="bold600 x20 flex-row center-start gap5"><?= svg("clock", "x18") ?> Horarios Más Concurridos</p>
        <?php if (!empty($topHours)) : ?>
          <span class="p5 pl10 pr10 br50 back-primary textw bold600">
            Hora Pico: <?= e($topHours[0]['label']) ?>
          </span>
        <?php endif; ?>
      </div>
      <p class="text-muted">
        Curva de tendencia de tráfico por franjas horarias del día (ordenadas cronológicamente), destacando los picos de mayor concurrencia de visitas.
      </p>
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
