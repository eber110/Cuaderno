<?php
  /** @var mixed $card */
?>
<?php if ($card["rrss"]) :?>
  <div class="flex-row center-center wrap gap10 gap-sml-8 p30 pt8 pb0 x30 xp20">
    
    <?php foreach ($card["rrss"] as $value) :?>

      <a href="<?= $value[1]?>" target="_blank" aria-label="<?= $value[0]?>" class="color-text-card hover-lift-ns">
        <?= svg($value[0])?>
      </a>
      
    <?php endforeach?>
  
  </div>
<?php endif;?>