<?php
  /** 
   * @var mixed $stats 
   * @var mixed $card 
   */
  $summary        = $stats["summary"] ?? ['total_views' => 0, 'unique_views' => 0, 'total_clicks' => 0, 'ctr' => 0];
  $devices        = $stats["devices"] ?? [];
  $browsers       = $stats["browsers"] ?? [];
  $countries      = $stats["countries"] ?? [];
  $referrers      = $stats["referrers"] ?? [];
  $topDays        = $stats["topDays"] ?? [];
  $topHours       = $stats["topHours"] ?? [];
  $recommendation = $stats["recommendation"] ?? ['bestDay' => 'Lunes', 'bestHour' => '18:00 - 19:00', 'bestDayTotal' => 0, 'bestHourTotal' => 0];
  $socialStats    = $stats["socialStats"] ?? [];
  $recentViews    = $stats["recentViews"] ?? [];
  $userProfile    = $card["profile"] ?? "user";

  // Si no hay días u horas calculadas, habilitar datos de muestra (Sample Mode)
  $isSample = empty($topDays) && empty($topHours);

  if ($isSample) {
    $summary = ['total_views' => 150, 'unique_views' => 98, 'total_clicks' => 42, 'ctr' => 28.0];
    $topDays = [
      ["day_num" => "1", "day_name" => "Lunes",     "total" => 48],
      ["day_num" => "3", "day_name" => "Miércoles", "total" => 36],
      ["day_num" => "5", "day_name" => "Viernes",   "total" => 29],
      ["day_num" => "6", "day_name" => "Sábado",    "total" => 22],
      ["day_num" => "0", "day_name" => "Domingo",   "total" => 15]
    ];
    $topHours = [
      ["hour_num" => "18", "label" => "18:00 - 19:00", "total" => 34],
      ["hour_num" => "20", "label" => "20:00 - 21:00", "total" => 28],
      ["hour_num" => "14", "label" => "14:00 - 15:00", "total" => 21],
      ["hour_num" => "12", "label" => "12:00 - 13:00", "total" => 16],
      ["hour_num" => "09", "label" => "09:00 - 10:00", "total" => 11]
    ];
    $recommendation = [
      'bestDay'       => 'Lunes',
      'bestHour'      => '18:00 - 19:00',
      'bestDayTotal'  => 48,
      'bestHourTotal' => 34
    ];
    if (empty($socialStats)) {
      $socialStats = [
        ['key' => 'instagram', 'name' => 'Instagram',       'icon' => 'instagram', 'total' => 74, 'bestDay' => 'Miércoles', 'bestHour' => '20:00 - 21:00'],
        ['key' => 'tiktok',    'name' => 'TikTok',          'icon' => 'tiktok',    'total' => 41, 'bestDay' => 'Viernes',   'bestHour' => '22:00 - 23:00'],
        ['key' => 'facebook',  'name' => 'Facebook',        'icon' => 'facebook',  'total' => 28, 'bestDay' => 'Domingo',   'bestHour' => '15:00 - 16:00'],
        ['key' => 'direct',    'name' => 'Tráfico Directo', 'icon' => 'globe',     'total' => 35, 'bestDay' => 'Lunes',     'bestHour' => '12:00 - 13:00']
      ];
    }
    if (empty($devices)) {
      $devices = [
        ['device_type' => 'mobile',  'total' => 105],
        ['device_type' => 'desktop', 'total' => 38],
        ['device_type' => 'tablet',  'total' => 7]
      ];
    }
    if (empty($browsers)) {
      $browsers = [
        ['browser' => 'Instagram App', 'total' => 62],
        ['browser' => 'Chrome',        'total' => 45],
        ['browser' => 'Safari',        'total' => 28],
        ['browser' => 'TikTok App',    'total' => 15]
      ];
    }
    if (empty($countries)) {
      $countries = [
        ['country_name' => 'Chile',  'country_code' => 'CL', 'total' => 95],
        ['country_name' => 'México', 'country_code' => 'MX', 'total' => 32],
        ['country_name' => 'España', 'country_code' => 'ES', 'total' => 23]
      ];
    }
    if (empty($referrers)) {
      $referrers = [
        ['referrer' => 'https://instagram.com', 'total' => 74],
        ['referrer' => 'https://tiktok.com',    'total' => 41],
        ['referrer' => 'Tráfico Directo',       'total' => 35]
      ];
    }
  }
?>

<div class="flex-column gap20 w100 textb">

  <!-- Encabezado con Botón de Simulación para Depuración -->
  <div class="flex-row center-between wrap gap10 p20 br15 border-item-panel back-body">
    <div>
      <h3 class="bold700 x20 flex-row center-start gap10">
        <?= svg("chart", "x24") ?> Estadísticas y Analíticas
        <?php if ($isSample) : ?>
          <span class="x11 p5 pl10 pr10 br50 back-modal-item text-muted bold600">Vista de Muestra</span>
        <?php endif; ?>
      </h3>
      <p class="text-muted x14">
        <?= $isSample ? 'Ejemplo demostrativo de cómo se visualizarán las métricas de tu perfil' : 'Monitoreo en tiempo real almacenado en SQLite' ?>
      </p>
    </div>

    <!-- Botón para simular visita/clic de prueba -->
    <form action="/panel/<?= e($userProfile) ?>/simular-datos" method="post">
      <button type="submit" class="p10 pl15 pr15 br20 textw back-primary pointer flex-row center-center gap5 bold500 x14 border-none shadow-1">
        <?= svg("add") ?> Simular Visita / Clic
      </button>
    </form>
  </div>

  <!-- Tarjetas de Métricas Generales (Métricas Clave) -->
  <div class="grid col-desk-4 col-mid-2 col-sml-2 gap15 w100">
    <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft">
      <span class="text-muted bold500 x13">Total Visitas</span>
      <span class="x24 bold700"><?= number_format($summary['total_views'] ?? 0) ?></span>
    </div>
    <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft">
      <span class="text-muted bold500 x13">Visitas Únicas</span>
      <span class="x24 bold700"><?= number_format($summary['unique_views'] ?? 0) ?></span>
    </div>
    <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft">
      <span class="text-muted bold500 x13">Total Clics</span>
      <span class="x24 bold700"><?= number_format($summary['total_clicks'] ?? 0) ?></span>
    </div>
    <div class="flex-column center-center p15 br15 border-item-panel text-c gap5 shadow-soft">
      <span class="text-muted bold500 x13">CTR Global</span>
      <span class="x24 bold700 text-success"><?= number_format($summary['ctr'] ?? 0, 2) ?>%</span>
    </div>
  </div>

  <!-- Card Destacada de Recomendación de Horario -->
  <div class="flex-column gap15 p20 br20 border-item-panel back-body shadow-soft w100" style="border-left: 5px solid var(--primary-color, #dc2626);">
    <div class="flex-row center-between wrap gap10">
      <div class="flex-row center-start gap10">
        <div class="br100 p10 back-modal-item flex-row center-center text-primary" style="flex-shrink: 0;">
          <?= svg("clock", "x24") ?>
        </div>
        <div>
          <h4 class="bold700 x18">💡 Recomendación de Horario para Publicar</h4>
          <p class="text-muted x13">Optimizado según la actividad de tus visitantes</p>
        </div>
      </div>
      <span class="x12 p6 pl12 pr12 br50 back-primary textw bold700 flex-row center-center gap5 shadow-soft">
        ⚡️ Horario Sugerido
      </span>
    </div>

    <div class="grid col-desk-3 col-mid-3 col-sml-1 gap15 w100 mt5">
      <!-- Mejor Día -->
      <div class="flex-column p15 br12 border-item-panel gap5 back-modal-item">
        <span class="text-muted bold500 x12">Mejor Día de la Semana</span>
        <span class="x20 bold700 text-primary"><?= e($recommendation['bestDay'] ?? 'Lunes') ?></span>
        <span class="x12 text-muted"><?= e($recommendation['bestDayTotal'] ?? 0) ?> visitas ese día</span>
      </div>

      <!-- Mejor Horario -->
      <div class="flex-column p15 br12 border-item-panel gap5 back-modal-item">
        <span class="text-muted bold500 x12">Franja Horaria Pico</span>
        <span class="x20 bold700 text-primary"><?= e($recommendation['bestHour'] ?? '18:00 - 19:00') ?></span>
        <span class="x12 text-muted"><?= e($recommendation['bestHourTotal'] ?? 0) ?> visitas en esa franja</span>
      </div>

      <!-- Consejo de Impacto -->
      <div class="flex-column p15 br12 border-item-panel gap5 back-modal-item">
        <span class="text-muted bold500 x12">Consejo de Impacto</span>
        <p class="x13 bold500 textb leading-normal">
          Tus seguidores están más activos los <strong class="text-primary"><?= e($recommendation['bestDay'] ?? 'Lunes') ?></strong> entre las <strong class="text-primary"><?= e($recommendation['bestHour'] ?? '18:00 - 19:00') ?> hrs</strong>.
        </p>
      </div>
    </div>
  </div>

  <!-- Sección de Rendimiento por Red Social (Visitas, Mejor Día y Hora por Red) -->
  <div class="flex-column gap15 p20 br20 border-item-panel back-body shadow-soft w100">
    <div class="flex-row center-between wrap gap10">
      <div>
        <h4 class="bold700 x18 flex-row center-start gap10">
          <?= svg("share", "x22") ?> Rendimiento y Horarios por Red Social
        </h4>
        <p class="text-muted x13">Desglose de visitas, día pico y hora óptima según la fuente de tráfico (Referrer)</p>
      </div>
      <span class="x12 p5 pl10 pr10 br50 back-modal-item bold600 text-muted">
        Calculado por Referrer
      </span>
    </div>

    <?php if (!empty($socialStats)) : ?>
      <div class="grid col-desk-4 col-mid-2 col-sml-1 gap15 w100 mt5">
        <?php foreach ($socialStats as $sNet) : ?>
          <div class="flex-column gap10 p15 br15 border-item-panel back-modal-item shadow-soft">
            
            <!-- Encabezado de la Red Social -->
            <div class="flex-row center-between wrap gap5">
              <div class="flex-row center-start gap8">
                <span class="p8 br100 back-body flex-row center-center text-primary shadow-soft">
                  <?= svg($sNet['icon'], "x20") ?>
                </span>
                <span class="bold700 x15"><?= e($sNet['name']) ?></span>
              </div>
              <span class="bold700 x15 text-primary"><?= number_format($sNet['total']) ?> <span class="x11 bold500 text-muted">visitas</span></span>
            </div>

            <div class="w100 border-top mt2 mb2"></div>

            <!-- Mejor Día y Hora de esta Red -->
            <div class="flex-column gap6 x13">
              <div class="flex-row center-between">
                <span class="text-muted bold500 flex-row center-start gap5"><?= svg("calendar", "x14") ?> Día Pico:</span>
                <span class="bold700 textb"><?= e($sNet['bestDay']) ?></span>
              </div>
              <div class="flex-row center-between">
                <span class="text-muted bold500 flex-row center-start gap5"><?= svg("clock", "x14") ?> Hora Pico:</span>
                <span class="bold700 text-primary"><?= e($sNet['bestHour']) ?></span>
              </div>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php else : ?>
      <p class="text-muted x14">No hay datos por red social registrados aún.</p>
    <?php endif; ?>
  </div>

  <!-- Días y Horarios con Mayor Tráfico General -->
  <div class="grid col-desk-2 col-mid-1 col-sml-1 gap15 w100">
    
    <!-- Días de la Semana Más Visitados -->
    <div class="flex-column gap15 p20 br15 border-item-panel">
      <div class="flex-row center-between wrap gap5">
        <p class="bold600 x16 flex-row center-start gap5"><?= svg("calendar", "x18") ?> Días Más Visitados</p>
        <?php if (!empty($topDays)) : ?>
          <span class="x12 p5 pl10 pr10 br50 back-primary textw bold600">
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
              <div class="flex-row center-between x14">
                <span class="bold600 flex-row center-start gap5">
                  <?= e($d['day_name']) ?>
                  <?php if ($isPeak) : ?>
                    <span class="x11 p2 pl8 pr8 br50 back-primary textw bold700">Pico</span>
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
        <p class="text-muted x14">No hay datos registrados aún.</p>
      <?php endif; ?>
    </div>

    <!-- Horarios de Mayor Tráfico -->
    <div class="flex-column gap15 p20 br15 border-item-panel">
      <div class="flex-row center-between wrap gap5">
        <p class="bold600 x16 flex-row center-start gap5"><?= svg("clock", "x18") ?> Horarios Más Concurridos</p>
        <?php if (!empty($topHours)) : ?>
          <span class="x12 p5 pl10 pr10 br50 back-primary textw bold600">
            Hora Pico: <?= e($topHours[0]['label']) ?>
          </span>
        <?php endif; ?>
      </div>

      <?php if (!empty($topHours)) : 
        $maxHourTotal = max(array_column($topHours, 'total')) ?: 1;
      ?>
        <div class="flex-column gap10 w100">
          <?php foreach ($topHours as $index => $h) : 
            $percent = round(($h['total'] / $maxHourTotal) * 100);
            $isPeak = ($index === 0);
          ?>
            <div class="flex-column gap5 p10 br10 border-item-panel <?= $isPeak ? 'back-modal-item' : '' ?>">
              <div class="flex-row center-between x14">
                <span class="bold600 flex-row center-start gap5">
                  <?= e($h['label']) ?>
                  <?php if ($isPeak) : ?>
                    <span class="x11 p2 pl8 pr8 br50 back-primary textw bold700">Pico</span>
                  <?php endif; ?>
                </span>
                <span class="bold700 text-muted"><?= e($h['total']) ?> visitas</span>
              </div>
              <div class="w100 br10 overflow-hidden hpx6 back-body">
                <div class="h100 br10 <?= $isPeak ? 'back-primary' : 'back-muted' ?>" style="width: <?= $percent ?>%;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <p class="text-muted x14">No hay datos registrados aún.</p>
      <?php endif; ?>
    </div>

  </div>

  <!-- Desglose por Dispositivos y Navegadores (In-App Browsers) -->
  <div class="grid col-desk-2 col-mid-1 col-sml-1 gap15 w100">
    
    <!-- Dispositivos -->
    <div class="flex-column gap15 p20 br15 border-item-panel">
      <p class="bold600 x16">Dispositivos</p>
      <?php if (!empty($devices)) : ?>
        <div class="flex-column gap10 w100">
          <?php foreach ($devices as $d) : 
            $devName = ucfirst($d['device_type'] ?? 'desktop');
            $devIcon = ($devName === 'Mobile') ? 'user-solid' : (($devName === 'Tablet') ? 'user' : 'server-solid');
          ?>
            <div class="flex-row center-between p10 br10 border-item-panel">
              <span class="bold500 flex-row center-start gap5"><?= svg($devIcon, "x16") ?> <?= e($devName) ?></span>
              <span class="bold700 text-muted"><?= e($d['total']) ?> visitas</span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <p class="text-muted x14">No hay datos registrados aún.</p>
      <?php endif; ?>
    </div>

    <!-- Navegadores -->
    <div class="flex-column gap15 p20 br15 border-item-panel">
      <p class="bold600 x16">Navegadores & In-App</p>
      <?php if (!empty($browsers)) : ?>
        <div class="flex-column gap10 w100">
          <?php foreach ($browsers as $b) : ?>
            <div class="flex-row center-between p10 br10 border-item-panel">
              <span class="bold500"><?= e($b['browser'] ?? 'Desconocido') ?></span>
              <span class="bold700 text-muted"><?= e($b['total']) ?> visitas</span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <p class="text-muted x14">No hay datos registrados aún.</p>
      <?php endif; ?>
    </div>

  </div>

  <!-- Desglose por Países y Fuentes de Tráfico -->
  <div class="grid col-desk-2 col-mid-1 col-sml-1 gap15 w100">
    
    <!-- Países -->
    <div class="flex-column gap15 p20 br15 border-item-panel">
      <p class="bold600 x16">Ubicación Geográfica</p>
      <?php if (!empty($countries)) : ?>
        <div class="flex-column gap10 w100">
          <?php foreach ($countries as $c) : ?>
            <div class="flex-row center-between p10 br10 border-item-panel">
              <span class="bold500"><?= e($c['country_name'] ?? 'Desconocido') ?> (<?= e($c['country_code'] ?? 'N/A') ?>)</span>
              <span class="bold700 text-muted"><?= e($c['total']) ?> visitas</span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <p class="text-muted x14">No hay datos registrados aún.</p>
      <?php endif; ?>
    </div>

    <!-- Referrers -->
    <div class="flex-column gap15 p20 br15 border-item-panel">
      <p class="bold600 x16">Orígenes de Tráfico (Referrer)</p>
      <?php if (!empty($referrers)) : ?>
        <div class="flex-column gap10 w100">
          <?php foreach ($referrers as $r) : 
            $refText = empty($r['referrer']) ? 'Tráfico Directo / Desconocido' : $r['referrer'];
          ?>
            <div class="flex-row center-between p10 br10 border-item-panel">
              <span class="bold500 cut-phrase wpx250"><?= e($refText) ?></span>
              <span class="bold700 text-muted"><?= e($r['total']) ?> visitas</span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <p class="text-muted x14">No hay datos registrados aún.</p>
      <?php endif; ?>
    </div>

  </div>

  <!-- Registros Recientes en SQLite (Tabla de Depuración) -->
  <?php if (!$isSample) : ?>
    <div class="flex-column gap15 p20 br15 border-item-panel w100">
      <p class="bold600 x16">Registros Recientes (SQLite Debug Log)</p>
      <?php if (!empty($recentViews)) : ?>
        <div class="flex-column gap10 w100">
          <?php foreach ($recentViews as $rv) : ?>
            <div class="flex-row center-between wrap p10 br10 border-item-panel gap5 x13">
              <span class="bold600"><?= e($rv['ip_address']) ?> (<?= e($rv['country_name']) ?>)</span>
              <span class="text-muted"><?= e($rv['device_type']) ?> | <?= e($rv['os']) ?> | <?= e($rv['browser']) ?></span>
              <span class="bold500 text-primary"><?= e($rv['created_at']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <p class="text-muted x14">No hay visitas registradas aún.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>
