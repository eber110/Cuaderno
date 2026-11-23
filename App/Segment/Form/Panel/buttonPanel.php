<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $buttonStyle     = $card["style"] ?? 'buttonRegular';
  $isClay          = ($buttonStyle === 'buttonClay');
  $isGlass         = ($buttonStyle === 'buttonGlass');
  $isCustomSurface = ($isClay || $isGlass);
  $borders         = is_array($card["borders"] ?? null) 
    ? $card["borders"] 
    : (is_string($card["borders"] ?? null) ? (str_contains($card["borders"], ",") ? explode(",", $card["borders"]) : [$card["borders"], $card["borders"]]) : ["br0", "br0"]);
  $borderVal          = $borders[0] ?? 'br0';
  $effectiveBorderVal = ($isCustomSurface && $borderVal === 'br0') ? 'br10' : $borderVal;
  $shadowVal          = $card["shadow"] ?? 'shadow-0';
  $availableFonts     = \App\Models\DesignModels::getAvailableFonts();
  $selectedButtonFont = \App\Models\DesignModels::resolveFont((string)($card["font_button"] ?? ""));
  $fontSearchable     = count($availableFonts) > 6 ? "true" : "false";
  $rawBtnTextColor    = strtolower(trim((string)($card["color"] ?? "#ffffff")));
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
    
    <!-- Estilo general de los botones -->
    <div class="flex-column top-start gap10 w100">
      <p class="bold500 texto">Estilo de botones</p>

      <div class="flex-row center-start flex-wrap gap12 w100">
        <input type="radio" id="button-regular" name="style" class="hidden-radio" value="buttonRegular" <?php if ($buttonStyle == "buttonRegular" || $buttonStyle == "Regular") echo "checked";?>>
        <label for="button-regular">
          <div class="back-card-graphic shadow-card-graphic hover-scale-soft p5 br20 flex-column center-center gap5 pointer">
            <div class="button-style-background flex-row center-center">
              <div class="<?= $borderVal?> button-fill-style"></div>
            </div>
            <p class="bold500 texto">Solido</p>
          </div>
        </label>

        <input type="radio" id="button-clay" name="style" class="hidden-radio" value="buttonClay" <?php if ($isClay) echo "checked";?>>
        <label for="button-clay">
          <div class="back-card-graphic shadow-card-graphic hover-scale-soft p5 br20 flex-column center-center gap5 pointer">
            <div class="button-style-background button-clay-preview-bg flex-row center-center">
              <div class="<?= ($effectiveBorderVal === 'br0') ? 'br10' : $effectiveBorderVal ?> button-clay-style"></div>
            </div>
            <p class="bold500 texto">Arcilla</p>
          </div>
        </label>

        <input type="radio" id="button-glass" name="style" class="hidden-radio" value="buttonGlass" <?php if ($isGlass) echo "checked";?>>
        <label for="button-glass">
          <div class="back-card-graphic shadow-card-graphic hover-scale-soft p5 br20 flex-column center-center gap5 pointer">
            <div class="button-style-background button-glass-preview-bg flex-row center-center">
              <div class="<?= ($effectiveBorderVal === 'br0') ? 'br10' : $effectiveBorderVal ?> button-glass-style"></div>
            </div>
            <p class="bold500 texto">Cristal</p>
          </div>
        </label>
      </div>
    </div>

    <!-- Curvatura de las esquinas -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="bold500 texto">Redondeo de esquinas</p>

      <div class="flex-row center-end gap12">
      
        <div id="border-square-option" class="<?= $isCustomSurface ? 'hidden' : '' ?>" style="display: <?= $isCustomSurface ? 'none !important' : 'block' ?>;">
          <input type="radio" id="square" name="borders" value="br0,br0" class="hidden-radio" <?php if ($effectiveBorderVal == "br0") echo "checked";?>>
          <label for="square" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="bold500 texto wpx25 hpx25 flex-row center-center">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="round1" name="borders" value="br10,br5" class="hidden-radio" <?php if ($effectiveBorderVal == "br10" || $effectiveBorderVal == "br5") echo "checked";?>>
          <label for="round1" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="bold500 texto wpx25 hpx25 flex-row center-center">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V8C4 5.79086 5.79086 4 8 4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="round2" name="borders" value="br20,br12" class="hidden-radio" <?php if ($effectiveBorderVal == "br20") echo "checked";?>>
          <label for="round2" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="bold500 texto wpx25 hpx25 flex-row center-center">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V12C4 7.58172 7.58172 4 12 4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="round3" name="borders" value="br50,br50" class="hidden-radio" <?php if ($effectiveBorderVal == "br50") echo "checked";?>>
          <label for="round3" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="bold500 texto wpx25 hpx25 flex-row center-center">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V12C4 7.58172 7.58172 4 12 4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </p>
          </label>
        </div>

      </div>

    </div>

    <!-- Estilos de sombreado de los botones -->
    <div id="button-shadow-row" class="flex-row center-between flex-column-sml top-start-sml gap10 w100 <?= $isCustomSurface ? 'hidden' : '' ?>" style="display: <?= $isCustomSurface ? 'none !important' : 'flex' ?>;">
      <p class="bold500 texto">Estilo de sombra</p>

      <div class="flex-row center-end gap12">

        <div class="">
          <input type="radio" id="shadow0" name="shadow" value="shadow-0" class="hidden-radio" <?php if ($shadowVal == "shadow-0") echo "checked";?>>
          <label for="shadow0" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="bold500 texto wpx25 hpx25 flex-row center-center">
              0
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="shadow1" name="shadow" value="shadow-1" class="hidden-radio" <?php if ($shadowVal == "shadow-1") echo "checked";?>>
          <label for="shadow1" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="bold500 texto wpx25 hpx25 flex-row center-center">
              1
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="shadow2" name="shadow" value="shadow-2" class="hidden-radio" <?php if ($shadowVal == "shadow-2") echo "checked";?>>
          <label for="shadow2" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="bold500 texto wpx25 hpx25 flex-row center-center">
              2
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="shadow3" name="shadow" value="shadow-3" class="hidden-radio" <?php if ($shadowVal == "shadow-3") echo "checked";?>>
          <label for="shadow3" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="bold500 texto wpx25 hpx25 flex-row center-center">
              3
            </p>
          </label>
        </div>

      </div>

    </div>

    <!-- Estilo de color de los botones -->
    <div id="button-back-color-row" class="flex-row center-between flex-column-sml top-start-sml gap10 w100 <?= $isGlass ? 'hidden' : '' ?>" style="display: <?= $isGlass ? 'none !important' : 'flex' ?>;">
      <p class="bold500 texto">Color de fondo</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft color-trigger-pill">
        <label data-trigger-color="button-select-color" for="button-select-color" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="button-select-color" name="back" value="<?= $card["back"] ?? "#d6d6d6"?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="bold500 texto"><?= $card["back"] ?? "#d6d6d6"?></p>
        </label>
      </div>
    </div>

    <!-- Estilo de color del texto de los botones -->
    <div id="button-text-color-row" class="flex-row center-between flex-column-sml top-start-sml gap10 w100 <?= $isGlass ? 'hidden' : '' ?>" style="display: <?= $isGlass ? 'none !important' : 'flex' ?>;">
      <p class="bold500 texto">Color de texto</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft color-trigger-pill">
        <label data-trigger-color="button-select-color-text" for="button-select-color-text" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="button-select-color-text" name="color" value="<?= $card["color"] ?? "#494949"?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="bold500 texto"><?= $card["color"] ?? "#494949"?></p>
        </label>
      </div>
    </div>

    <!-- Selector de color de texto exclusivo para Cristal (Blanco o Negro) -->
    <div id="button-glass-text-color-row" class="flex-row center-between flex-column-sml top-start-sml gap10 w100 <?= $isGlass ? '' : 'hidden' ?>" style="display: <?= $isGlass ? 'flex' : 'none !important' ?>;">
      <p class="bold500 texto">Color de texto</p>

      <div class="flex-row center-end gap10">
        <div>
          <input type="radio" id="glass-text-white" name="glass_text_color" value="#ffffff" class="hidden-radio glass-text-color-radio" <?= ($glassTextColor === '#ffffff') ? 'checked' : '' ?>>
          <label for="glass-text-white" class="flex-row center-center p10 pl15 pr15 gap8 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <span class="wpx22 hpx22 br50" style="background-color: #ffffff; border: 1px solid rgba(0, 0, 0, 0.2); display: inline-block; flex-shrink: 0;"></span>
            <p class="bold500 texto">Blanco</p>
          </label>
        </div>

        <div>
          <input type="radio" id="glass-text-black" name="glass_text_color" value="#000000" class="hidden-radio glass-text-color-radio" <?= ($glassTextColor === '#000000') ? 'checked' : '' ?>>
          <label for="glass-text-black" class="flex-row center-center p10 pl15 pr15 gap8 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <span class="wpx22 hpx22 br50" style="background-color: #000000; border: 1px solid rgba(255, 255, 255, 0.25); display: inline-block; flex-shrink: 0;"></span>
            <p class="bold500 texto">Negro</p>
          </label>
        </div>
      </div>
    </div>

    <!-- Estilo de color de la sombra shadow-3 -->
    <div id="shadow3-color-row" class="flex-row center-between flex-column-sml top-start-sml gap10 w100 <?= (!$isCustomSurface && $shadowVal === 'shadow-3') ? '' : 'hidden' ?>" style="display: <?= (!$isCustomSurface && $shadowVal === 'shadow-3') ? 'flex' : 'none !important' ?>;">
      <p class="bold500 texto">Color de sombra</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft color-trigger-pill">
        <label data-trigger-color="button-select-color-shadow3" for="button-select-color-shadow3" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="button-select-color-shadow3" name="colorShadow3" value="<?= $card["colorShadow3"] ?? "#000000"?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="bold500 texto"><?= $card["colorShadow3"] ?? "#000000"?></p>
        </label>
      </div>
    </div>

    <!-- Fuente de botones y contenidos -->
    <div id="button-font-row" class="flex-row center-between flex-column-sml top-start-sml gap10 w100 <?= $isCustomSurface ? 'hidden' : '' ?>" style="display: <?= $isCustomSurface ? 'none !important' : 'flex' ?>;">
      <p class="bold500 texto">Fuente de botones</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft br15 p5 w60 w-sml-100">
        <div class="dropdown-select ui-input-component animated font-selector-dropdown"
             id="select-font-button"
             data-name="font_button"
             data-searchable="<?= $fontSearchable ?>"
             data-placeholder="Elige una fuente"
             data-max-height="260"
             style="--ui-trigger-bg: #ffffff; --ui-trigger-text: var(--text-primary, #111827); --ui-surface-bg: #ffffff; --ui-surface-text: var(--text-primary, #111827); --ui-surface-muted: var(--back-color, #d3d3d3); --ui-surface-shadow: 0 10px 30px rgba(0, 0, 0, 0.14); --ui-placeholder-color: var(--text-primary, #111827); --ui-btn-primary-text: #0F172A; --ui-btn-primary-border: transparent; --ui-accent-text: var(--text-secondary, #f8fafc); --ui-thumb-bg: var(--text-secondary, #f8fafc); --ui-border-width: 0px; --ui-focus-ring: var(--body, #ffffff); --ui-focus-outline: none; --ui-font-size: 18px; --ui-overlay-blur: 0px;">
          <input type="hidden" name="font_button" value="<?= e($selectedButtonFont['key']) ?>">
          <div class="dropdown-selected" style="font-family: <?= e($selectedButtonFont['cssFamily']) ?>; font-weight: <?= (int)$selectedButtonFont['weight'] ?>;"><?= e($selectedButtonFont['label']) ?></div>
          <div class="dropdown-options hidden">
            <?php if ($fontSearchable === "true") : ?>
              <input type="text" class="dropdown-search" placeholder="Buscar fuente...">
            <?php endif; ?>
            <?php foreach ($availableFonts as $fKey => $fMeta) : ?>
              <div class="dropdown-option<?= ($selectedButtonFont['key'] === $fKey) ? ' selected' : '' ?>"
                   data-value="<?= e($fKey) ?>"
                   data-font-family="<?= e($fMeta['cssFamily']) ?>"
                   data-font-weight="<?= (int)$fMeta['weight'] ?>"
                   style="font-family: <?= e($fMeta['cssFamily']) ?>; font-weight: <?= (int)$fMeta['weight'] ?>;">
                <?= e($fMeta['label']) ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Habilita la animación hover en los botones -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100 back-card-graphic panel-toggle-card">
      <p class="bold500 texto">Activar hover</p>

      <input type="checkbox" name="hover" id="" value="true" data-option="true,false" active="<?= (!empty($card["hover"])) ? '1' : '2' ?>" class="checkbox-switch">
    </div>

  </div>

  <input type="submit" value="guardar" class="hidden">
</form>