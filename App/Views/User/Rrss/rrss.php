<?php
  /** @var mixed $card */
?>
<?php if ($card["rrss"]) :?>
  <div class="flex-row center-center wrap gap10 gap-sml-8 p30 pt8 pb0 x30 xp20">
    
    <?php foreach ($card["rrss"] as $value) :?>
      <?php $hasUrl = !empty($value[1]); ?>
      <a href="<?= e($value[1] ?? '#') ?>" target="_blank" aria-label="<?= e($value[0]) ?>" class="color-text-card hover-lift-ns track-link-click" data-user="<?= e($card['profile'] ?? '') ?>" data-link-id="rrss_<?= e($value[0]) ?>" style="<?= $hasUrl ? '' : 'display: none;' ?>">
        <?= svg($value[0])?>
      </a>
    <?php endforeach?>
  
  </div>
<?php endif;?>