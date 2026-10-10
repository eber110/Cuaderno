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
    ? "p5 pl15 pr15 pl-sml-12 pr-sml-12 br15 pointer back-card-graphic-red shadow-card-graphic hover-scale-soft bold500 textw border-none pulse-once" 
    : "p5 pl15 pr15 pl-sml-12 pr-sml-12 br15 back-card-graphic shadow-card-graphic border-none disabled-save-btn texto";
  $discardClass = $hasCustom
    ? "p5 pl15 pr15 pl-sml-10 pr-sml-10 br15 pointer back-card-graphic shadow-card-graphic hover-scale-soft bold500 texto border-none"
    : "p5 pl15 pr15 pl-sml-10 pr-sml-10 br15 back-card-graphic shadow-card-graphic border-none texto hidden";
?>
<div class="sticky top z-index-20">
  <div id="active-viewers-badge" data-profile-user="<?= e($profile) ?>" class="no-desk no-tablet active-viewers-badge flex-row center-center gap8 p5 pr12 pl12 back-live-view hidden">
    <span class="live-dot-pulse"></span>
    <span id="active-viewers-text" class="active-viewers-text x18 bold500 color-live-view">1 en línea</span>
  </div>

  <nav class="flex-row center-between gap10 p20 p-sml-20 back-body" style="border-bottom: solid 0.5px #f0f0f0;">
    <!-- Sección izquierda: Badge en desktop/tablet, Ver perfil en smartphone -->
    <div class="flex-row center-start flex-shrink-0">
      <!-- Badge de Usuarios en Línea (desktop y tablet) -->
      <div class="wpx200 hpx32 no-phone">
        <div id="active-viewers-badge" data-profile-user="<?= e($profile) ?>" class="active-viewers-badge flex-row center-center gap8 p5 pr12 pl12 br20 back-live-view shadow-card-graphic hidden">
          <span class="live-dot-pulse"></span>
          <span id="active-viewers-text" class="active-viewers-text x18 bold500 color-live-view">1 en línea</span>
        </div>
      </div>

      <!-- Enlace Ver perfil (smartphone) -->
      <div class="no-desk no-tablet">
        <a href="/<?= e($username) ?>" class="back-card-graphic shadow-card-graphic hover-scale-soft p5 pl12 pr12 br50 texto pointer flex-row center-center gap5 nowrap flex-shrink-0"><?= svg("arrow-l-l") ?> <span>Ver perfil</span></a>
      </div>
    </div>

    <!-- Botones de Acción (Sección derecha: Descartar, Guardar, Vista previa) -->
    <div class="flex-row center-end gap10 gap-sml-8 flex-shrink-0">
      <div id="save-btn-container" class="save-btn-wrapper hidden flex-row gap10 gap-sml-8 center-center flex-shrink-0" data-has-custom="<?= $hasCustom ? 'true' : 'false' ?>">
        <a id="discard-btn" href="<?= $discardUrl ?>" class="z-index-20 nowrap flex-shrink-0 <?= $discardClass ?> flex-row center-center" <?= $hasCustom ? "" : 'tabindex="-1" aria-disabled="true"' ?> title="Descartar cambios">
          <span class="no-phone">Descartar</span>
          <span class="no-desk no-tablet flex-row center-center"><?= svg("undo", "texto") ?></span>
        </a>
        <a id="save-btn" href="<?= $saveUrl ?>" class="z-index-20 nowrap flex-shrink-0 <?= $saveClass ?>" <?= $hasCustom ? "" : 'tabindex="-1" aria-disabled="true"' ?>>Guardar</a>
      </div>

      <div class="no-desk flex-shrink-0">
        <div class="modal-btn animated pointer">
          <p class="p5 pl15 pr15 br15 back-card-graphic shadow-card-graphic hover-scale-soft texto no-phone nowrap">Vista previa</p>
          <p class="p5 pl10 pr10 br15 back-card-graphic shadow-card-graphic hover-scale-soft texto no-tablet flex-row center-center"><?= svg("eye")?></p>
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