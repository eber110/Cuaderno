<?php
  /** 
   * @var bool $isSample 
   * @var array $recentViews 
   */
  if ($isSample) return;
?>
<div class="flex-column gap15 p20 br15 back-card-graphic shadow-card-graphic hover-scale-soft w100 text-protected">
  <p class="bold600 x20 texto">Registros Recientes (SQLite Debug Log)</p>
  <?php if (!empty($recentViews)) : ?>
    <div class="flex-column gap10 w100">
      <?php foreach ($recentViews as $rv) : ?>
        <div class="flex-row center-between wrap p10 br10 border-item-panel gap5">
          <span class="bold600 texto"><?= e($rv['ip_address']) ?> (<?= e($rv['country_name']) ?>)</span>
          <span class="text-muted"><?= e($rv['device_type']) ?> | <?= e($rv['os']) ?> | <?= e($rv['browser']) ?></span>
          <span class="bold500 text-primary"><?= e($rv['created_at']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <p class="text-muted">No hay visitas registradas aún.</p>
  <?php endif; ?>
</div>
