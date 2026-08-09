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
    ? "p5 pl15 pr15 br15 pointer back-save-panel textc bold500 border-item-panel-red pulse-once" 
    : "p5 pl15 pr15 br15 border-item-panel disabled-save-btn texto";
?>
<div id="active-viewers-badge" data-profile-user="<?= e($profile) ?>" class="active-viewers-badge flex-row center-center gap8 p5 pr12 pl12 |no-phone hidden" style="background: rgba(37, 146, 77, 0.12); border: 1px solid rgba(34, 197, 94, 0.25);">
  <span class="live-dot-pulse"></span>
  <span id="active-viewers-text" class="active-viewers-text x18 bold500" style="color: #22c55e;">1 en línea</span>
</div>
<nav class="flex-row center-between gap10 p20 sticky top z-index-20 back-body" style="border-bottom: solid 0.5px #f0f0f0;">
    <!-- Badge de Usuarios en Línea (Sección izquierda) -->
    <div class="wpx140 hpx32 no-phone">
      <div id="active-viewers-badge" data-profile-user="<?= e($profile) ?>" class="active-viewers-badge flex-row center-center gap8 p5 pr12 pl12 br20 |no-phone hidden" style="background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.25);">
        <span class="live-dot-pulse"></span>
        <span id="active-viewers-text" class="active-viewers-text x18 bold500" style="color: #22c55e;">1 en línea</span>
      </div>
    </div>

    <div class="no-desk no-tablet hamburger-toggle pointer border-none flex-row center-center" aria-label="Menú">
       <?= svg("bars", "x30");?>
    </div>
    <nav class="hamburger-menu from-left with-overlay animated hidden back-menu-sidebar shadow-card">
      <div class="hamburger-content flex-column wpx280 h-dvh p0 overflow-y-auto before-menu">
        <div class="flex-row center-end w100 p5 top3">
          <div class="closed-hamburger closed-modal-preview flex-row center-center back-transparent border-none" aria-label="Cerrar menú">
            <?= svg("xmark", "x20") ?>
          </div>
        </div>
        <div class="">
          <?php _part("Dashboard.SideMenu.sideMenuPhone")?>
        </div>
      </div>
    </nav>

    <!-- Botones de Acción (Sección derecha) -->
    <div class="flex-row center-end gap10 w100">
      <div id="save-btn-container" class="save-btn-wrapper hidden" data-has-custom="<?= $hasCustom ? 'true' : 'false' ?>">
        <a id="save-btn" href="<?= $saveUrl ?>" class="z-index-20 <?= $saveClass ?>" <?= $hasCustom ? "" : 'tabindex="-1" aria-disabled="true"' ?>>Guardar</a>
      </div>

      <div class="no-desk">
        <div class="modal-btn animated pointer |before-menu-overlay">
          <p class="p5 pl15 pr15 br15 back-card-graphic shadow-card-graphic hover-scale-soft texto">Vista previa</p>
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