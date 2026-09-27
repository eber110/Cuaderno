<?php
  /** @var mixed $card */
?>
<main class="color-text-card pt80 pt-sml-70">
  <div class="hem5 flex-column center-center gap10">
    
    <div class="flex-column center-center hpx120">
      <figure class="hpx100 wpx100 br100" style="background: transparent;">
        <img src="<?= eUrl($card["avatarSrc"] ?? '') ?>" alt="Avatar de <?= e($card["title"] ?? '') ?>" class="cover br100 image-protected flex-row center-center" fetchpriority="high">
      </figure>
    </div>
    
    <h1 class="x30 xp25 bold500 title-color"><?= e($card["title"] ?? '') ?></h1>
    <p class="p30 pb0 pt0 p-sml-0 w85 w-sml-90 text-c hpxm550 bold500"><?= e($card["desc"] ?? '') ?></p>
    
  </div>
    <?php
      _part("User.rrss", ["card" => $card]);
    ?>
</main>