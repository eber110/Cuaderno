<?php
  /** @var mixed $card */
?>
<main class="color-text-card">
  <div class="absolute top z-index-10 p30 p-sml-20 pt30 pb30 pt-sml-20 w100">
    <?php _component("Menu.menuUser");?>
  </div>

  <figure class="ar-square w100 faded-image">
    <img src="<?= $card["avatarSrc"] ?>" alt="Avatar de <?= $user ?? "Usuario"?>" class="cover image-protected" fetchpriority="high">
  </figure>

  <div class="flex-column center-center gap0 position-inset-hero p20 pt0 pb5 mmt20">
    <div class=" w100"></div>
    <div class="flex-column top-center gap10">

      <h1 class="x40 xp30 bold700 title-color"><?= $card["title"]?></h1>
      <p class="p20 p-sml-0 pb0 pt0 w90 w-sml-95 text-c bold500" style="overflow: hidden;"><?= $card["desc"]?></p>

    </div>
  </div>

  <?php
    _part("User.rrss");
  ?>
</main>