<?php
  /** @var mixed $card */
?>
<main class="color-text-card">
  <div class="p30 p-sml-20 pt30 pb30 pt-sml-20 w100">
    <?php _component("Menu.menuUser");?>
  </div>
  <div class="hem5 flex-column center-center gap10">
    
    <div class="flex-column center-center hpx120">
      <figure class="hpx100 wpx100 br100" style="background: transparent;">
        <img src="<?= $card["avatarSrc"] ?>" alt="Avatar de <?= $card["title"]?>" class="cover br100 image-protected flex-row center-center" fetchpriority="high">
      </figure>
    </div>
    
    <h1 class="x30 xp25 bold500 title-color"><?= $card["title"]?></h1>
    <div id="active-viewers-badge" class="flex-row center-center gap8 p5 pr12 pl12 br20 hidden" style="background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.25);">
      <span class="live-dot-pulse"></span>
      <span id="active-viewers-text" class="x12 bold600" style="color: #22c55e;">1 en línea</span>
    </div>
    <p class="p30 pb0 pt0 p-sml-0 w85 w-sml-90 text-c hpxm550 bold500"><?= $card["desc"]?></p>
    
  </div>
    <?php
      _part("User.rrss");
    ?>
</main>