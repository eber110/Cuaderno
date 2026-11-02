<?php
  /** @var mixed $card */
  $voidSpace = (int)($card["voidHero"]["space"] ?? ($card["void_space"] ?? 70));
  if ($voidSpace === 130) $voidSpace = 20;
  elseif ($voidSpace === 250) $voidSpace = 45;
  elseif ($voidSpace === 450) $voidSpace = 70;
?>
<main class="color-text-card">
  <div data-profile-bind-style="padding-top:void_space:%" class="flex-column center-center gap0 p20 void-space pb5" style="padding-top: var(--preview-void-space, <?= $voidSpace ?>%) !important;">
    <div class=" w100"></div>
    <div class="flex-column top-center gap10">

      <h1 data-profile-bind="title" class="x40 xp30 bold700 title-color"><?= e($card["title"] ?? '') ?></h1>
      <p data-profile-bind="desc" class="p20 p-sml-0 pb0 pt0 w90 w-sml-95 text-c bold500" style="overflow: hidden;"><?= e($card["desc"] ?? '') ?></p>

    </div>
  </div>

  <?php
    _part("User.rrss", ["card" => $card]);
  ?>
</main>
