<?php
  /** 
   * @var mixed $stats 
   * @var mixed $card 
   */
  $summary     = $stats["summary"] ?? ['total_views' => 0, 'unique_views' => 0, 'total_clicks' => 0, 'ctr' => 0];
  $devices     = $stats["devices"] ?? [];
  $browsers    = $stats["browsers"] ?? [];
  $countries   = $stats["countries"] ?? [];
  $referrers   = $stats["referrers"] ?? [];
  $recentViews = $stats["recentViews"] ?? [];
  $userProfile = $card["profile"] ?? "user";
?>

<div class="flex-column gap20 w100 textb">

  <!-- Encabezado con Botón de Simulación para Depuración -->
  <div class="flex-row center-between wrap gap10 p20 br15 border-item-panel back-body">
    <div>
      <h3 class="bold700 x20 flex-row center-start gap10"><?= svg("chart", "x24") ?> Estadísticas y Analíticas</h3>
      <p class="text-muted x14">Monitoreo en tiempo real almacenado en SQLite</p>
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

</div>
