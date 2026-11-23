<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $isVideoEnabled = \App\Models\DesignModels::isVideoEnabled();
  $headerVal = $card["header"] ?? 'regularHero';
  $avatarVal = $card["avatar"] ?? 'no-user.webp';
  $avatarSrc = $card["avatarSrc"] ?? '';
  $voidSpaceVal = $card["voidHero"]["space"] ?? ($card["void_space"] ?? 450);
  $availableFonts = \App\Models\DesignModels::getAvailableFonts();
  $selectedTitleFont = \App\Models\DesignModels::resolveFont((string)($card["font_title"] ?? ""));
  $selectedTextFont  = \App\Models\DesignModels::resolveFont((string)($card["font_text"] ?? ""));
  $fontSearchable    = count($availableFonts) > 6 ? "true" : "false";
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post" enctype="multipart/form-data">
  <?= class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::csrfField() : '' ?>

  <div class="flex-column top-between gap20">
    
    <!-- Estilo de las cabeceras disponibles -->
    <div class="flex-column top-start gap10 w100">
      <p class="bold500 texto">Estilo de cabecera</p>

      <div class="flex-row center-start flex-wrap gap12 w100">

        <!-- Opción Vacío (voidHero) -->
        <div class="flex-row center-end gap10">
          <input type="radio" id="voidHeader" name="header" class="hidden-radio" value="voidHero" <?php if ($headerVal == "voidHero") echo "checked";?>>
          <label for="voidHeader">
            <div class="back-card-graphic shadow-card-graphic hover-scale-soft p15 br20 flex-column center-center gap5 pointer">
              <div class="hpx70 wpx70 flex-column center-center gap10">
                <div class="hpx50 wpx50 br15 flex-column center-center">
                  <span class="opacity-60"><?= svg("ban", "x50")?></span>
                </div>
              </div>
              <p class="bold500 texto">Vacío</p>
            </div>
          </label>
        </div>

        <!-- Opción Regular (regularHero) -->
        <div class="flex-row center-end gap10">
          <input type="radio" id="regularHeader" name="header" class="hidden-radio" value="regularHero" <?php if ($headerVal == "regularHero") echo "checked";?>>
          <label for="regularHeader">
            <div class="back-card-graphic shadow-card-graphic hover-scale-soft p15 br20 flex-column center-center gap5 pointer">
              <div class="hpx70 wpx70 flex-column center-center gap10">
                <figure class="hpx50 br50">
                  <img src="<?= $avatarSrc ?>" alt="" class="cover ar-square">
                </figure>  
              </div>
              <p class="bold500 texto">Regular</p>
            </div>
          </label>
        </div>
  
        <!-- Opción Hero (midHero) -->
        <div class="flex-row center-end gap10">
          <input type="radio" id="midHeader" name="header" class="hidden-radio" value="midHero" <?php if ($headerVal == "midHero") echo "checked";?>>
          <label for="midHeader">
            <div class="back-card-graphic shadow-card-graphic hover-scale-soft p15 br20 flex-column center-center gap5 pointer">
              <div class="hpx70 wpx70 flex-column center-center gap10">
                <figure class="brtl10 brtr10 hpx70 wpx70 faded-image">
                  <img src="<?= $avatarSrc ?>" alt="" class="cover">
                </figure>  
              </div>
              <p class="bold500 texto">Hero</p>
            </div>
          </label>
        </div>

      </div>
    </div>

    <!-- Separaciones superiores de voidHero (solo visible cuando header == voidHero) -->
    <div id="void-space-container" class="flex-column top-start gap10 w100 <?= ($headerVal === 'voidHero') ? '' : 'hidden' ?>" style="display: <?= ($headerVal === 'voidHero') ? 'flex' : 'none !important' ?>;">
      <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
        <p class="bold500 texto">Separación superior</p>

        <div class="flex-row center-end gap10">
          
          <!-- Separación 1: 20% (Alto) -->
          <div class="flex-row center-end gap10">
            <input type="radio" id="voidSpace20" name="void_space" class="hidden-radio" value="20" <?php if ($voidSpaceVal == 20 || $voidSpaceVal == 130) echo "checked";?>>
            <label for="voidSpace20">
              <div class="back-card-graphic shadow-card-graphic hover-scale-soft p10 br15 pointer flex-column center-center gap2 wpx70">
                <span class="bold500 texto">20%</span>
                <span class="text-muted">Alto</span>
              </div>
            </label>
          </div>

          <!-- Separación 2: 45% (Medio) -->
          <div class="flex-row center-end gap10">
            <input type="radio" id="voidSpace45" name="void_space" class="hidden-radio" value="45" <?php if ($voidSpaceVal == 45 || $voidSpaceVal == 250) echo "checked";?>>
            <label for="voidSpace45">
              <div class="back-card-graphic shadow-card-graphic hover-scale-soft p10 br15 pointer flex-column center-center gap2 wpx70">
                <span class="bold500 texto">45%</span>
                <span class="text-muted">Medio</span>
              </div>
            </label>
          </div>

          <!-- Separación 3: 70% (Bajo) -->
          <div class="flex-row center-end gap10">
            <input type="radio" id="voidSpace70" name="void_space" class="hidden-radio" value="70" <?php if ($voidSpaceVal == 70 || $voidSpaceVal == 450 || empty($voidSpaceVal) || !in_array($voidSpaceVal, [20, 45, 70, 130, 250, 450])) echo "checked";?>>
            <label for="voidSpace70">
              <div class="back-card-graphic shadow-card-graphic hover-scale-soft p10 br15 pointer flex-column center-center gap2 wpx70">
                <span class="bold500 texto">70%</span>
                <span class="text-muted">Bajo</span>
              </div>
            </label>
          </div>

        </div>
      </div>
    </div>

    <!-- Imagen de perfil -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="bold500 texto">Imagen de perfil</p>

      <div class="br15 p5 x18 back-card-graphic shadow-card-graphic hover-scale-soft">
        <?= uiInput('cropImage', [
          'triggerBg' => 'textw',
          'placeholderSize' => 18,
          'placeholderColor' => 'texto',
          'borderWidth' => 0,
          'radius' => 15,
          'fontSize' => 18,
          'boxPadding' => 15,
          'trackBg' => 'textw',
          'surfaceBg' => 'body',
          'btnPrimaryText' => 'textc',
          'btnPrimaryBg' => 'back4',
          'btnSecondaryBg' => 'back9',
          'btnSecondaryText' => 'textw',
          'thumbBg' => 'back4',
          'accentText' => 'back4',
          'accentColor' => 'back5',
          'name' => 'avatar',
          'autoSubmit' => true,
        ]) ?>
      </div>

    </div>

    <?php if ($isVideoEnabled) : ?>
      <!-- Bloque para futura cabecera con video o avatar animado (desactivado por variable de entorno) -->
    <?php endif; ?>

    <!-- Titulo del perfil -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="bold500 texto">Titulo</p>

      <div class="back-card-graphic panel-input-box w70 w-sml-100">
         <?= uiInput('textLimiter', [
          'triggerText' => 'texto',
          'fontSize' => 18,
          'placeholderSize' => 18,
          'secondaryFontSize' => 14,
          'value' => $card["title"],
          'rows' => 1,
          'autoSubmit' => true,
          'name' => 'title',
          'placeholder' => 'Titulo',
          'triggerBg' => 'textw',
          'secondaryText' => 'text-inactive',
          'placeholderColor' => 'text-inactive',
          'borderWidth' => 0,
        ]) ?>
      </div>
    </div>

    <!-- Fuente de título -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="bold500 texto">Fuente de título</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft br15 p5 w70 w-sml-100">
        <div class="dropdown-select ui-input-component animated font-selector-dropdown"
             id="select-font-title"
             data-name="font_title"
             data-searchable="<?= $fontSearchable ?>"
             data-placeholder="Elige una fuente"
             data-max-height="260"
             style="--ui-trigger-bg: #ffffff; --ui-trigger-text: var(--text-primary, #111827); --ui-surface-bg: #ffffff; --ui-surface-text: var(--text-primary, #111827); --ui-surface-muted: var(--back-color, #d3d3d3); --ui-surface-shadow: 0 10px 30px rgba(0, 0, 0, 0.14); --ui-placeholder-color: var(--text-primary, #111827); --ui-btn-primary-text: #0F172A; --ui-btn-primary-border: transparent; --ui-accent-text: var(--text-secondary, #f8fafc); --ui-thumb-bg: var(--text-secondary, #f8fafc); --ui-border-width: 0px; --ui-focus-ring: var(--body, #ffffff); --ui-focus-outline: none; --ui-font-size: 18px; --ui-overlay-blur: 0px;">
          <input type="hidden" name="font_title" value="<?= e($selectedTitleFont['key']) ?>">
          <div class="dropdown-selected" style="font-family: <?= e($selectedTitleFont['cssFamily']) ?>; font-weight: <?= (int)$selectedTitleFont['weight'] ?>;"><?= e($selectedTitleFont['label']) ?></div>
          <div class="dropdown-options hidden">
            <?php if ($fontSearchable === "true") : ?>
              <input type="text" class="dropdown-search" placeholder="Buscar fuente...">
            <?php endif; ?>
            <?php foreach ($availableFonts as $fKey => $fMeta) : ?>
              <div class="dropdown-option<?= ($selectedTitleFont['key'] === $fKey) ? ' selected' : '' ?>"
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

    <!-- Descripción del perfil -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="bold500 texto">Bio</p>

      <div class="back-card-graphic panel-input-box w70 w-sml-100">
        <?= uiInput('textLimiter', [
          'triggerText' => 'texto',
          'fontSize' => 18,
          'placeholderSize' => 18,
          'secondaryFontSize' => 14,
          'value' => $card["desc"],
          'rows' => 5,
          'autoSubmit' => true,
          'triggerBg' => 'textw',
          'secondaryText' => 'text-inactive',
          'placeholderColor' => 'text-inactive',
          'name' => 'desc',
          'placeholder' => 'Descripción',
          'borderWidth' => 0,
        ]) ?>
      </div>
    </div>

    <!-- Fuente de texto -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="bold500 texto">Fuente de texto</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft br15 p5 w70 w-sml-100">
        <div class="dropdown-select ui-input-component animated font-selector-dropdown"
             id="select-font-text"
             data-name="font_text"
             data-searchable="<?= $fontSearchable ?>"
             data-placeholder="Elige una fuente"
             data-max-height="260"
             style="--ui-trigger-bg: #ffffff; --ui-trigger-text: var(--text-primary, #111827); --ui-surface-bg: #ffffff; --ui-surface-text: var(--text-primary, #111827); --ui-surface-muted: var(--back-color, #d3d3d3); --ui-surface-shadow: 0 10px 30px rgba(0, 0, 0, 0.14); --ui-placeholder-color: var(--text-primary, #111827); --ui-btn-primary-text: #0F172A; --ui-btn-primary-border: transparent; --ui-accent-text: var(--text-secondary, #f8fafc); --ui-thumb-bg: var(--text-secondary, #f8fafc); --ui-border-width: 0px; --ui-focus-ring: var(--body, #ffffff); --ui-focus-outline: none; --ui-font-size: 18px; --ui-overlay-blur: 0px;">
          <input type="hidden" name="font_text" value="<?= e($selectedTextFont['key']) ?>">
          <div class="dropdown-selected" style="font-family: <?= e($selectedTextFont['cssFamily']) ?>; font-weight: <?= (int)$selectedTextFont['weight'] ?>;"><?= e($selectedTextFont['label']) ?></div>
          <div class="dropdown-options hidden">
            <?php if ($fontSearchable === "true") : ?>
              <input type="text" class="dropdown-search" placeholder="Buscar fuente...">
            <?php endif; ?>
            <?php foreach ($availableFonts as $fKey => $fMeta) : ?>
              <div class="dropdown-option<?= ($selectedTextFont['key'] === $fKey) ? ' selected' : '' ?>"
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

    <!-- Estilo de color del texto de los botones -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="bold500 texto">Color de titulo</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft color-trigger-pill">
        <label data-trigger-color="select-color-title" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color-title" name="titleColor" value="<?= $card["titleColor"] ?? "#383838"?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="bold500 texto"><?= $card["titleColor"] ?? "#383838"?></p>
        </label>
      </div>
    </div>

  </div>  

  <input type="submit" value="guardar" class="hidden">
</form>