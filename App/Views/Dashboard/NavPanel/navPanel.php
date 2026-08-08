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
    ? "p5 pl15 pr15 br15 pointer back-save-panel textw bold500 border-item-panel-red pulse-once" 
    : "p5 pl15 pr15 br15 border-item-panel disabled-save-btn texto";
?>
<nav class="flex-row center-between gap10 p20 sticky top z-index-20 back-body" style="border-bottom: solid 0.5px #f0f0f0;">
    <!-- Badge de Usuarios en Línea (Sección izquierda) -->
    <div class="wpx116 hpx32 no-phone">
      <div id="active-viewers-badge" data-profile-user="<?= e($profile) ?>" class="flex-row center-center gap8 p5 pr12 pl12 br20 no-phone hidden" style="background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.25);">
        <span class="live-dot-pulse"></span>
        <span id="active-viewers-text" class="x18 bold500" style="color: #22c55e;">1 en línea</span>
      </div>
    </div>

    <button class="no-desk no-tablet hamburger-toggle hpx45 wpx45" aria-label="Menú"  style="z-index: 9999999999900000000;">
       <?= svg("bars");?>
     </button>
     <nav class="hamburger-menu from-left with-overlay animated hidden">
       <?php _part("Dashboard.sideMenu")?>
     </nav>

    <!-- Botones de Acción (Sección derecha) -->
    <div class="flex-row center-end gap10 w100">
      <div id="save-btn-container" class="before-menu save-btn-wrapper hidden" data-has-custom="<?= $hasCustom ? 'true' : 'false' ?>">
        <a id="save-btn" href="<?= $saveUrl ?>" class="<?= $saveClass ?>" <?= $hasCustom ? "" : 'tabindex="-1" aria-disabled="true"' ?>>Guardar</a>
      </div>

      <div class="no-desk">
        <div class="modal-btn animated pointer before-menu-overlay">
          <p class="p5 pl15 pr15 br15 back-card-graphic shadow-card-graphic hover-scale-soft">Vista previa</p>
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
    </div>
</nav>