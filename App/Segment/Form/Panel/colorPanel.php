<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $styleBack  = $card["backCard"]["style_back"] ?? $card["backCard"][1] ?? 'solid';
  $backPerfil = $card["backCard"]["back_perfil"] ?? $card["backCard"][0] ?? '#272727';
  $shadowVal        = $card["shadow"] ?? 'shadow-0';
  $isGlass          = (($card["style"] ?? 'buttonRegular') === 'buttonGlass');
  $hideButtonShadow = in_array($card["style"] ?? 'buttonRegular', ['buttonClay', 'buttonGlass'], true);
  $rawBtnTextColor  = strtolower(trim((string)($card["color"] ?? "#ffffff")));
  if ($rawBtnTextColor === "#000000" || $rawBtnTextColor === "#ffffff") {
    $glassTextColor = $rawBtnTextColor;
  } else {
    $hex = ltrim($rawBtnTextColor, "#");
    if (strlen($hex) === 3) {
      $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) === 6 && ctype_xdigit($hex)) {
      $luma = (0.299 * hexdec(substr($hex, 0, 2))) + (0.587 * hexdec(substr($hex, 2, 2))) + (0.114 * hexdec(substr($hex, 4, 2)));
      $glassTextColor = ($luma < 128) ? "#000000" : "#ffffff";
    } else {
      $glassTextColor = "#ffffff";
    }
  }
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post">
  <?= class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::csrfField() : '' ?>

  <div class="flex-column top-between gap20 w100">

    <div class="back-card-graphic shadow-card-graphic p20 br15 flex-column gap15 w100">
      <p class="bold500 texto">Color general</p>
      <!-- Color de fondo de la aplicación -->
      <div class="flex-row center-between panel-setting-row">
        <p class="texto">Color de fondo</p>

        <div class="back-card-graphic shadow-card-graphic hover-scale-soft color-trigger-pill">
          <label data-trigger-color="panel-select-color" class="flex-row center-start p10 gap10 pointer">
            <input type="color" id="panel-select-color" name="back_perfil" data-draft-field="back_perfil" value="<?= $backPerfil?>" class="color-picker box-color-picker"
            style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
            <p class="bold500 texto"><?= $backPerfil?></p>
          </label>
        </div>
      </div>

      <!-- Color de texto global -->
      <div class="flex-row center-between panel-setting-row">
        <p class="texto">Color de texto</p>

        <div class="back-card-graphic shadow-card-graphic hover-scale-soft color-trigger-pill">
          <label data-trigger-color="panel-select-color-text-app" class="flex-row center-start p10 gap10 pointer">
            <input type="color" id="panel-select-color-text-app" name="colorText" data-draft-field="colorText" value="<?= $card["colorText"] ?? "#383838"?>" class="color-picker box-color-picker"
            style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker box-color-picker">
            <p class="bold500 texto"><?= $card["colorText"] ?? "#383838"?></p>
          </label>
        </div>
      </div>
    </div>

    <div class="back-card-graphic shadow-card-graphic p20 br15 flex-column gap15 w100">
      <p class="bold500 texto">Color del titulo</p>
      <!-- Color del titulo -->
      <div class="flex-row center-between panel-setting-row">
        <p class="texto">Color de titulo</p>

        <div class="back-card-graphic shadow-card-graphic hover-scale-soft color-trigger-pill">
          <label data-trigger-color="panel-select-color-title" class="flex-row center-start p10 gap10 pointer">
            <input type="color" id="panel-select-color-title" name="titleColor" data-draft-field="titleColor" value="<?= $card["titleColor"] ?? $card["colorText"]?>" class="color-picker box-color-picker"
            style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
            <p class="bold500 texto"><?= $card["titleColor"] ?? $card["colorText"]?></p>
          </label>
        </div>
      </div>
    </div>
  
    <div class="back-card-graphic shadow-card-graphic p20 br15 flex-column gap15 w100">
      <p class="bold500 texto">Color de botones</p>
      <!-- Estilo de color de los botones -->
      <div id="color-button-back-row" class="flex-row center-between panel-setting-row <?= $isGlass ? 'hidden' : '' ?>" style="display: <?= $isGlass ? 'none !important' : 'flex' ?>;">
        <p class="texto">Color de fondo</p>

        <div class="back-card-graphic shadow-card-graphic hover-scale-soft color-trigger-pill">
          <label data-trigger-color="select-color-button" class="flex-row center-start p10 gap10 pointer">
            <input type="color" id="select-color-button" name="back" data-draft-field="back" value="<?= $card["back"] ?? "#d6d6d6"?>" class="color-picker box-color-picker"
            style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
            <p class="bold500 texto"><?= $card["back"] ?? "#d6d6d6"?></p>
          </label>
        </div>
      </div>

      <!-- Estilo de color del texto de los botones -->
      <div id="color-button-text-row" class="flex-row center-between panel-setting-row <?= $isGlass ? 'hidden' : '' ?>" style="display: <?= $isGlass ? 'none !important' : 'flex' ?>;">
        <p class="texto">Color de texto</p>

        <div class="back-card-graphic shadow-card-graphic hover-scale-soft color-trigger-pill">
          <label data-trigger-color="select-color-text" class="flex-row center-start p10 gap10 pointer">
            <input type="color" id="select-color-text" name="color" data-draft-field="color" value="<?= $card["color"] ?? "#494949"?>" class="color-picker box-color-picker"
            style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
            <p class="bold500 texto"><?= $card["color"] ?? "#494949"?></p>
          </label>
        </div>
      </div>

      <!-- Selector de color de texto exclusivo para Cristal (Blanco o Negro) -->
      <div id="color-glass-text-color-row" class="flex-row center-between panel-setting-row <?= $isGlass ? '' : 'hidden' ?>" style="display: <?= $isGlass ? 'flex' : 'none !important' ?>;">
        <p class="texto">Color de texto</p>

        <div class="flex-row center-end gap10">
          <div>
            <input type="radio" id="color-glass-text-white" name="glass_text_color_panel" value="#ffffff" class="hidden-radio glass-text-color-radio" <?= ($glassTextColor === '#ffffff') ? 'checked' : '' ?>>
            <label for="color-glass-text-white" class="flex-row center-center p10 pl15 pr15 gap8 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
              <span class="wpx22 hpx22 br50" style="background-color: #ffffff; border: 1px solid rgba(0, 0, 0, 0.2); display: inline-block; flex-shrink: 0;"></span>
              <p class="bold500 texto">Blanco</p>
            </label>
          </div>

          <div>
            <input type="radio" id="color-glass-text-black" name="glass_text_color_panel" value="#000000" class="hidden-radio glass-text-color-radio" <?= ($glassTextColor === '#000000') ? 'checked' : '' ?>>
            <label for="color-glass-text-black" class="flex-row center-center p10 pl15 pr15 gap8 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
              <span class="wpx22 hpx22 br50" style="background-color: #000000; border: 1px solid rgba(255, 255, 255, 0.25); display: inline-block; flex-shrink: 0;"></span>
              <p class="bold500 texto">Negro</p>
            </label>
          </div>
        </div>
      </div>

      <!-- Estilo de color de la sombra shadow-3 -->
      <div id="color-shadow3-color-row" class="flex-row center-between panel-setting-row <?= (!$hideButtonShadow && $shadowVal === 'shadow-3') ? '' : 'hidden' ?>" style="display: <?= (!$hideButtonShadow && $shadowVal === 'shadow-3') ? 'flex' : 'none !important' ?>;">
        <p class="texto">Color de sombra</p>

        <div class="back-card-graphic shadow-card-graphic hover-scale-soft color-trigger-pill">
          <label data-trigger-color="select-color-shadow3" class="flex-row center-start p10 gap10 pointer">
            <input type="color" id="select-color-shadow3" name="colorShadow3" data-draft-field="colorShadow3" value="<?= $card["colorShadow3"] ?? "#000000"?>" class="color-picker box-color-picker"
            style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
            <p class="bold500 texto"><?= $card["colorShadow3"] ?? "#000000"?></p>
          </label>
        </div>
      </div>
    </div>

  </div>
  
  <input type="submit" value="guardar" class="hidden">
</form>