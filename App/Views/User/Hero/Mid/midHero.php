<?php
  /** @var mixed $card */
?>
<main class="color-text-card">
  <div class="absolute top z-index-10 p30 p-sml-20 pt30 pb30 pt-sml-20 w100">
    <?php _component("Menu.menuUser");?>
  </div>

  <figure class="absolute top back1 ar-square hpx480 hpx-sml-360 w100 faded-image">
    <img src="<?= DIR_SHOW_MEDIA."/Custom/{$card["avatar"]}"?>" alt="Avatar de <?= $user ?? "Usuario"?>" class="cover image-protected">
  </figure>

  <div class="flex-column bottom-center gap10 position-inset-hero hpx500 hpx-sml-400 p20 pt0 pb5">
    <h1 class="x40 xp30 bold700"><?= $card["title"]?></h1>
    <p class="p20 p-sml-0 pb0 pt0 w100 w-sml-100 text-c hpxm230 hpxm-sml-150 cut-phrase" style="overflow: hidden;"><?= $card["desc"]?></p>
  </div>

  <?php
    _part("User.rrss");
  ?>
</main>