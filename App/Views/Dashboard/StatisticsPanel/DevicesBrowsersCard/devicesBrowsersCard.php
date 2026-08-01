<?php
  /** 
   * @var array $devices 
   * @var array $browsers 
   */
?>
<div class="grid col-desk-2 col-mid-1 col-sml-1 gap15 w100">
  
  <!-- Dispositivos -->
  <div class="flex-column gap15 p20 br15 border-item-panel">
    <p class="bold600 x20">Dispositivos</p>
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
      <p class="text-muted">No hay datos registrados aún.</p>
    <?php endif; ?>
  </div>

  <!-- Navegadores -->
  <div class="flex-column gap15 p20 br15 border-item-panel">
    <p class="bold600 x20">Navegadores & In-App</p>
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
      <p class="text-muted">No hay datos registrados aún.</p>
    <?php endif; ?>
  </div>

</div>
