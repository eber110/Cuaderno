<?php
if (!defined('ROOT_PATH') || !class_exists(\Base\Studio\StudioKernel::class) || !\Base\Studio\StudioKernel::isDevEnvironment()) {
  http_response_code(404);
  exit;
}
?>
<!-- TAB: FUENTES TIPOGRÁFICAS (FONT STUDIO) -->
<div id="tab-fonts" class="ts-tab-content">
  <!-- Bloque 1: Fuentes Disponibles -->
  <div class="ts-section">
    <div class="ts-section-title">
      <span>1. Fuentes Disponibles</span>
      <span id="fsSidebarCountBadge" style="font-size:0.72rem; color:var(--ts-primary); font-weight:700;">0</span>
    </div>

    <div class="ts-field" style="margin-bottom:8px;">
      <input type="text" id="fsSearchInput" class="ts-input-text" placeholder="🔍 Filtrar por familia o clase..." oninput="onFontSearchChange(this.value)">
    </div>

    <div id="fsFontTypeGrid" class="cs-chart-type-grid">
      <!-- Renderizado dinámico vía JS -->
    </div>

    <div id="fsProjectEmptyBanner" style="display:none; margin-top:10px; padding:10px; border-radius:8px; border:1px dashed rgba(56, 189, 248, 0.38); background:rgba(15, 23, 42, 0.55); font-size:0.74rem; color:var(--ts-muted); line-height:1.45;">
      No hay fuentes personalizadas instaladas en este proyecto. Instala una fuente con <code style="color:var(--ts-primary);">frame font:install</code> y pulsa <strong>Actualizar</strong>.
    </div>

    <div id="fsOrphanNotice" style="display:none; margin-top:8px; padding:8px 10px; border-radius:8px; border:1px solid rgba(245, 158, 11, 0.35); background:rgba(245, 158, 11, 0.08); font-size:0.72rem; color:#fde68a; line-height:1.4;">
      <!-- Aviso dinámico de bloques huérfanos descartados -->
    </div>
  </div>

  <!-- Bloque 2: Probador en Vivo (Specimen) -->
  <div class="ts-section">
    <div class="ts-section-title">
      <span>2. Probador en Vivo</span>
      <span id="fsWeightRangeBadge" class="cs-local-badge is-global">400</span>
    </div>

    <div class="cs-measure-row">
      <div class="cs-measure-top">
        <span>Peso Activo (<code>font-weight</code>)</span>
        <span id="fsActiveWeightLabel" style="font-size:0.72rem; color:var(--ts-primary); font-family:monospace;">400 · Regular</span>
      </div>
      <div id="fsWeightGrid" class="fs-weight-btn-grid">
        <!-- Botones 100..900 generados vía JS -->
      </div>
    </div>

    <div class="ts-field" style="margin-bottom:10px;">
      <label for="fsSampleTextInput">Texto de Prueba</label>
      <input type="text" id="fsSampleTextInput" class="ts-input-text" value="El veloz murciélago hindú comía feliz cardillo y kiwi" oninput="onFontSampleTextInput(this.value)">
    </div>

    <div class="cs-measure-row">
      <div class="cs-measure-top">
        <span>Tamaño de Muestra (<code>font-size</code>)</span>
        <span style="font-size:0.7rem; color:var(--ts-muted);">px</span>
      </div>
      <div class="cs-measure-controls">
        <input type="range" id="fsSampleSizeRange" min="14" max="72" step="1" value="26" class="ts-range-input" oninput="onFontMeasureChange('size', this.value)">
        <input type="number" id="fsSampleSizeNum" min="14" max="72" value="26" class="cs-num-input" oninput="onFontMeasureChange('size', this.value)">
      </div>
    </div>

    <div class="cs-measure-row">
      <div class="cs-measure-top">
        <span>Interlineado (<code>line-height</code>)</span>
        <span style="font-size:0.7rem; color:var(--ts-muted);">ratio</span>
      </div>
      <div class="cs-measure-controls">
        <input type="range" id="fsLineHeightRange" min="1.0" max="2.2" step="0.05" value="1.35" class="ts-range-input" oninput="onFontMeasureChange('lineHeight', this.value)">
        <input type="number" id="fsLineHeightNum" min="1.0" max="2.2" step="0.05" value="1.35" class="cs-num-input" oninput="onFontMeasureChange('lineHeight', this.value)">
      </div>
    </div>

    <div class="cs-measure-row">
      <div class="cs-measure-top">
        <span>Espaciado (<code>letter-spacing</code>)</span>
        <span style="font-size:0.7rem; color:var(--ts-muted);">px</span>
      </div>
      <div class="cs-measure-controls">
        <input type="range" id="fsLetterSpacingRange" min="-2" max="8" step="0.5" value="0" class="ts-range-input" oninput="onFontMeasureChange('letterSpacing', this.value)">
        <input type="number" id="fsLetterSpacingNum" min="-2" max="8" step="0.5" value="0" class="cs-num-input" oninput="onFontMeasureChange('letterSpacing', this.value)">
      </div>
    </div>

    <div class="cs-check-grid">
      <label class="cs-check-item"><input type="checkbox" id="fsOptItalic" onchange="onFontToggleOption()"> Itálica</label>
      <label class="cs-check-item"><input type="checkbox" id="fsOptUpper" onchange="onFontToggleOption()"> MAYÚSCULAS</label>
      <label class="cs-check-item"><input type="checkbox" id="fsOptTabular" onchange="onFontToggleOption()"> Números tabulares</label>
    </div>
  </div>

  <!-- Bloque 3: Invocación y Acciones -->
  <div class="ts-section">
    <div class="ts-section-title">
      <span>3. Invocación y Archivos</span>
    </div>

    <div id="fsSelectedSummaryBox" style="background:rgba(15, 23, 42, 0.55); border:1px solid var(--ts-border); border-radius:8px; padding:10px; font-size:0.76rem; color:var(--ts-muted); line-height:1.5; margin-bottom:10px;">
      Selecciona una fuente para ver su detalle técnico.
    </div>

    <button type="button" class="ts-btn ts-btn-primary" style="width:100%; justify-content:center; padding:8px 10px;" onclick="applySelectedFontAsGlobal()">
      ✨ Usar como fuente global (--font)
    </button>
  </div>
</div>
