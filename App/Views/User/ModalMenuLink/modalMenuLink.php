<?php
  /** 
   * @var mixed $data 
   * @var mixed $card
   */
?>
<div class="flex-column center-center gap15 h100 back-modal-item">
  <p class="absolute top right pointer modal-close-button"><?= svg("xmark")?></p>

  <p>Comparte estos links</p>

  <a href="<?= $data["url"]?>" class="flex-column center-center gap10 wpx320 p30 br20 border-card-modal pointer |hover-scale-soft" style="background-color: oklch(from <?= $card["back"]?> calc(l * 0.20) calc(c + 0.07) h /60%)">
    <figure class="ar-square wpx200 br15">
      <img src="<?= $data["imgSrc"] ?? '' ?>" alt="" class="cover">
    </figure>

    <p class="x16 bold500 text-c bold900 x22" style="color: <?= $card["colorText"]?> !important;"><?= \Base\Module\TextModule::truncate($card["desc"], 5)?>...</p>
    <p class="x16" style="color: <?= $card["colorText"]?>;"><?= \Base\Module\TextModule::truncateRaw($data["url"], 1)?></p>
  </a>

  <?php if (!empty($data["share"]) && is_array($data["share"])) : ?>
    <div class="border-card-modal w100 br15 p10 flex-row center-between gap10 inline-carousel" data-loop="false" data-gap="10px">
      
      <button class="ic-prev back8 br100 ar-square wpx30 hpx30 flex-row center-center textw pointer border-none" style="flex-shrink: 0; font-size: 14px;">
        &#10094;
      </button>

      <div class="ic-track w100 flex-row gap15">
        <?php foreach ($data["share"] as $networkName => $shareUrl) : ?>
          <a href="<?= $shareUrl ?>" target="_blank" aria-label="<?= e($networkName) ?>" class="ic-item p3 br100 x30 back8 ar-square hpx45 wpx45 flex-row center-center textw pointer" style="flex-shrink: 0;">
            <?= svg($networkName, "x20"); ?>
          </a>
        <?php endforeach; ?>
      </div>

      <button class="ic-next back8 br100 ar-square wpx30 hpx30 flex-row center-center textw pointer border-none" style="flex-shrink: 0; font-size: 14px;">
        &#10095;
      </button>
      
    </div>
  <?php endif; ?>

  <div class="hem13 back7 border-card-modal w100 br15">

  </div>

</div>