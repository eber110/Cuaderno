<?php 
  /** 
   * @var mixed $card
   * @var mixed $hasCustom
   * @var mixed $uri
   */
  $hasCustom = $hasCustom ?? false;
  $profile = $card["profile"] ?? "";
  $saveUrl = $uri["saveDesign"] ?? ("/panel/" . $profile . "/guardar");
  $saveClass = $hasCustom 
    ? "p5 pl15 pr15 br15 pointer back-save-panel textw bold500 border-item-panel pulse-once" 
    : "p5 pl15 pr15 br15 border-item-panel disabled-save-btn texto";
?>
<nav class="flex-row center-end gap10 p20 sticky top z-index-20 back-body" style="border-bottom: solid 0.5px #f0f0f0;">
    <div id="save-btn-container" class="save-btn-wrapper">
      <a id="save-btn" href="<?= $saveUrl ?>" class="<?= $saveClass ?>" <?= $hasCustom ? "" : 'tabindex="-1" aria-disabled="true"' ?>>Guardar</a>
    </div>

    <div class="no-desk">
      <div class="modal-btn animated pointer before-menu-overlay">
        <p class="p5 pl15 pr15 br15 border-item-panel">Vista previa</p>
      </div>
      <div class="hidden">
        <div class="flex-column center-center w100">
          <div class="absolute m20 top right fadeIn pointer modal-close-button closed-modal-preview br50 p0 hpx30 wpx30 flex-column center-center">
            <?= svg("xmark")?>
          </div>
          <?php _component("UserPreview.userPreview", ["data" => $card])?>
        </div>
      </div>
    </div>
</nav>