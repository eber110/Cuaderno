<?php
  /** 
   * @var array $countries 
   * @var array $referrers 
   */
?>
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
