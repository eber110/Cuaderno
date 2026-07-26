<?php
  /** 
   * @var mixed $data 
   * @var mixed $card
   */
?>
<div class="flex-column center-center gap15 h100 back-modal-item">
  <p class="absolute top right pointer modal-close-button"><?= svg("xmark")?></p>

  <p class="bold600">Comparte este link</p>

  <a href="<?= $data["url"]?>" class="flex-column center-center gap5 wpx320 p30 br20 border-card-modal pointer |hover-scale-soft" style="background-color: oklch(from <?= $card["back"]?> calc(l * 0.20) calc(c + 0.07) h /60%); color: <?= $card["colorText"]?> !important;">
    <figure class="ar-square wpx200 br15">
      <img src="<?= $data["metaImg"] ?? '' ?>" alt="" class="cover">
    </figure>

    <p class="x16 bold500 text-c bold900 x22 cut-phrase"><?= $data["metaDesc"]?>...</p>
    <p class="x16" style="color: <?= $card["colorText"]?>;"><?= \Base\Module\TextModule::truncateRaw($data["url"], 1)?></p>
  </a>

  <?php if (!empty($data["share"]) && is_array($data["share"])) : ?>
    <div class="|border-card-modal w100 p0 flex-row center-center inline-carousel" data-loop="false" data-gap="10px">
      
      <div class="ic-prev border-card-modal back-body br100 ar-square wpx40 hpx40 flex-row center-center text pointer border-none absolute left" style="flex-shrink: 0; top: 50%; transform: translateY(-50%); z-index: 10; margin-left: 5px; transition: opacity 0.3s;">
        <?= svg("angle-l", "x30 opacity-slide-circle");?>
      </div>

      <div class="ic-track w100">
        <?php foreach ($data["share"] as $networkName => $shareUrl) : ?>
          <a href="<?= $shareUrl ?>" target="_blank" aria-label="<?= e($networkName) ?>" class="ic-item p3 br100 x30 back8 ar-square hpx50 wpx50 flex-row center-center textw pointer" style="flex-shrink: 0;">
            <?= svg($networkName, "x30"); ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="ic-next border-card-modal back-body br100 ar-square wpx40 hpx40 flex-row center-center text pointer border-none absolute right" style="flex-shrink: 0; top: 50%; transform: translateY(-50%); z-index: 10; margin-right: 5px; transition: opacity 0.3s;">
        <?= svg("angle-r", "x30 opacity-slide-circle");?>
      </div>
      
    </div>
  <?php endif; ?>

  <div class="hem13 back7 border-card-modal w100 br15">

  </div>

</div>