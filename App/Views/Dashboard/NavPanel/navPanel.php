<?php 
  /** 
   * @var mixed $card
   * @var mixed $hasCustom
   * @var mixed $uri
   */
  $hasCustom = $hasCustom ?? false;
  $username = $session["username"] ?? ($card["profile"] ?? "");
  $profile = $card["profile"] ?? "";
  $saveUrl = $uri["saveDesign"] ?? ("/panel/" . $profile . "/guardar");
  $discardUrl = $uri["discardDesign"] ?? ("/panel/" . $profile . "/descartar");
  $saveClass = $hasCustom 
    ? "p5 pl15 pr15 br15 pointer back-card-graphic-red shadow-card-graphic hover-scale-soft bold500 textw border-none pulse-once" 
    : "p5 pl15 pr15 br15 back-card-graphic shadow-card-graphic border-none disabled-save-btn texto";
  $discardClass = $hasCustom
    ? "p5 pl15 pr15 br15 pointer back-card-graphic shadow-card-graphic hover-scale-soft bold500 texto border-none"
    : "p5 pl15 pr15 br15 back-card-graphic shadow-card-graphic border-none texto hidden";
?>
<div class="sticky top z-index-20">
  <div id="active-viewers-badge" data-profile-user="<?= e($profile) ?>" class="no-desk no-tablet active-viewers-badge flex-row center-center gap8 p5 pr12 pl12 back-live-view hidden">
    <span class="live-dot-pulse"></span>
    <span id="active-viewers-text" class="active-viewers-text x18 bold500 color-live-view">1 en línea</span>
  </div>

  <nav class="flex-row center-between gap10 p20 |sticky |top |z-index-20 back-body" style="border-bottom: solid 0.5px #f0f0f0;">
      <!-- Badge de Usuarios en Línea (Sección izquierda) -->
      <div class="wpx200 hpx32 no-phone">
        <div id="active-viewers-badge" data-profile-user="<?= e($profile) ?>" class="active-viewers-badge flex-row center-center gap8 p5 pr12 pl12 br20 back-live-view shadow-card-graphic hidden">
          <span class="live-dot-pulse"></span>
          <span id="active-viewers-text" class="active-viewers-text x18 bold500 color-live-view">1 en línea</span>
        </div>
      </div>
  
      <div class="no-desk no-tablet flex-row center-start gap10 w100">
        <a href="/<?= e($username) ?>" class="back-card-graphic shadow-card-graphic hover-scale-soft p5 pl15 pr15 br50 texto pointer flex-row center-center gap5"><?= svg("arrow-l-l") ?> Ver perfil</a>
        <!-- <div class="modal-btn animated pointer back-card-graphic shadow-card-graphic hover-scale-soft p5 pl12 pr12 br50 texto flex-row center-center gap5" aria-label="Cerrar sesión">
          <?= svg("out") ?>
        </div> -->
        <div class="hidden">
          <div class="w100 flex-column center-center h-dvh">
            <div class="flex-column gap20 wpx520 w-sml-100 back-card-graphic p20 br15">
              <p class="x24 bold500 texto">¿Desea cerrar sesión?</p>

              <div class="flex-row center-between gap10">
                <a href="/salir" class="btn-card-graphic shadow-card-graphic hover-scale-soft text-c texto w100 bold500">Salir</a>
                <p class="btn-card-graphic-red shadow-card-graphic hover-scale-soft text-c textc w100 pointer bold500 modal-close-button">Cancelar</p>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Botones de Acción (Sección derecha) -->
      <div class="flex-row center-end gap10 w100">
        <div id="save-btn-container" class="save-btn-wrapper hidden flex-row gap10 center-center" data-has-custom="<?= $hasCustom ? 'true' : 'false' ?>">
          <a id="discard-btn" href="<?= $discardUrl ?>" class="z-index-20 <?= $discardClass ?>" <?= $hasCustom ? "" : 'tabindex="-1" aria-disabled="true"' ?>>Descartar</a>
          <a id="save-btn" href="<?= $saveUrl ?>" class="z-index-20 <?= $saveClass ?>" <?= $hasCustom ? "" : 'tabindex="-1" aria-disabled="true"' ?>>Guardar</a>
        </div>
  
        <div class="no-desk">
          <div class="modal-btn animated pointer |before-menu-overlay">
            <p class="p5 pl15 pr15 br15 back-card-graphic shadow-card-graphic hover-scale-soft texto no-phone">Vista previa</p>
            <p class="p5 pl15 pr15 br15 back-card-graphic shadow-card-graphic hover-scale-soft texto no-tablet"><?= svg("eye")?></p>
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
</div>

<div class="bottom-nav-bar no-desk no-tablet hpx80 back-body fixed bottom w100 z-index-20">
  <?php _part("Dashboard.SideMenu.sideMenuPhone"); ?>
</div>