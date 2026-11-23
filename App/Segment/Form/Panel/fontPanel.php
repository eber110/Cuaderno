<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $availableFonts     = \App\Models\DesignModels::getAvailableFonts();
  $selectedTitleFont  = \App\Models\DesignModels::resolveFont((string)($card["font_title"] ?? ""));
  $selectedTextFont   = \App\Models\DesignModels::resolveFont((string)($card["font_text"] ?? ""));
  $selectedButtonFont = \App\Models\DesignModels::resolveFont((string)($card["font_button"] ?? ""));
  $isClay             = (($card["style"] ?? "buttonRegular") === "buttonClay");
  $isUnifiedFont      = $isClay
    ? ($selectedTitleFont["key"] === $selectedTextFont["key"])
    : ($selectedTitleFont["key"] === $selectedTextFont["key"] && $selectedTextFont["key"] === $selectedButtonFont["key"]);
  $selectedGlobalFont = $isUnifiedFont ? $selectedTitleFont : null;
  $fontSearchable     = count($availableFonts) > 6 ? "true" : "false";
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post">
  <?= class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::csrfField() : '' ?>

  <div class="flex-column top-between gap20 w100">

    <p class="bold500 texto">Fuente global</p>
    <!-- Selector maestro que actualiza todas las fuentes del perfil -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="texto">Fuente global</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft br15 p5 w60 w-sml-100">
        <div class="dropdown-select ui-input-component animated font-selector-dropdown font-global-dropdown"
             id="panel-select-font-global"
             data-name="font_global"
             data-font-global="true"
             data-searchable="<?= $fontSearchable ?>"
             data-placeholder="Personalizada"
             data-max-height="260"
             style="--ui-trigger-bg: #ffffff; --ui-trigger-text: var(--text-primary, #111827); --ui-surface-bg: #ffffff; --ui-surface-text: var(--text-primary, #111827); --ui-surface-muted: var(--back-color, #d3d3d3); --ui-surface-shadow: 0 10px 30px rgba(0, 0, 0, 0.14); --ui-placeholder-color: var(--text-primary, #111827); --ui-btn-primary-text: #0F172A; --ui-btn-primary-border: transparent; --ui-accent-text: var(--text-secondary, #f8fafc); --ui-thumb-bg: var(--text-secondary, #f8fafc); --ui-border-width: 0px; --ui-focus-ring: var(--body, #ffffff); --ui-focus-outline: none; --ui-font-size: 18px; --ui-overlay-blur: 0px;">
          <input type="hidden" name="font_global" value="<?= $selectedGlobalFont ? e($selectedGlobalFont['key']) : '' ?>">
          <?php if ($selectedGlobalFont) : ?>
            <div class="dropdown-selected" style="font-family: <?= e($selectedGlobalFont['cssFamily']) ?>; font-weight: <?= (int)$selectedGlobalFont['weight'] ?>;"><?= e($selectedGlobalFont['label']) ?></div>
          <?php else : ?>
            <div class="dropdown-selected is-placeholder">Personalizada</div>
          <?php endif; ?>
          <div class="dropdown-options hidden">
            <?php if ($fontSearchable === "true") : ?>
              <input type="text" class="dropdown-search" placeholder="Buscar fuente...">
            <?php endif; ?>
            <?php foreach ($availableFonts as $fKey => $fMeta) : ?>
              <div class="dropdown-option<?= ($selectedGlobalFont && $selectedGlobalFont['key'] === $fKey) ? ' selected' : '' ?>"
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

    <p class="bold500 mt20 texto">Fuente de cabecera</p>
    <!-- Fuente de título -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="texto">Fuente de título</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft br15 p5 w60 w-sml-100">
        <div class="dropdown-select ui-input-component animated font-selector-dropdown"
             id="panel-select-font-title"
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

    <!-- Fuente de texto -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="texto">Fuente de texto</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft br15 p5 w60 w-sml-100">
        <div class="dropdown-select ui-input-component animated font-selector-dropdown"
             id="panel-select-font-text"
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

    <div id="panel-font-button-wrapper" class="flex-column gap20 w100 <?= $isClay ? 'hidden' : '' ?>" style="display: <?= $isClay ? 'none !important' : 'flex' ?>;">
      <p class="bold500 mt20 texto">Fuente de botones</p>
      <!-- Fuente de botones y contenidos -->
      <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
        <p class="texto">Fuente de botones</p>

        <div class="back-card-graphic shadow-card-graphic hover-scale-soft br15 p5 w60 w-sml-100">
          <div class="dropdown-select ui-input-component animated font-selector-dropdown"
               id="panel-select-font-button"
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
    </div>

  </div>

  <input type="submit" value="guardar" class="hidden">
</form>
