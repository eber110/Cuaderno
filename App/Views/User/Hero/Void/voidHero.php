<?php
  /** @var mixed $card */
?>
<main class="color-text-card">
  <div class="flex-column center-center gap0 p20 void-space pb5">
    <div class=" w100"></div>
    <div class="flex-column top-center gap10">

      <h1 class="x40 xp30 bold700 title-color"><?= $card["title"]?></h1>
      <p class="p20 p-sml-0 pb0 pt0 w90 w-sml-95 text-c bold500" style="overflow: hidden;"><?= $card["desc"]?></p>

    </div>
  </div>

  <?php
    _part("User.rrss", ["card" => $card]);
  ?>
</main>