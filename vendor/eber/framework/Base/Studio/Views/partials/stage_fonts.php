<?php
if (!defined('ROOT_PATH') || !class_exists(\Base\Studio\StudioKernel::class) || !\Base\Studio\StudioKernel::isDevEnvironment()) {
  http_response_code(404);
  exit;
}
?>
<style>
  #fontSandboxView {
    display: none;
    flex: 1;
    min-height: 0;
    flex-direction: column;
    overflow: hidden;
    background-color: var(--body, #ffffff);
    color: var(--text-primary, #0f172a);
    font-family: var(--font, system-ui, sans-serif);
  }
  .fs-weight-btn-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 5px;
  }
  .fs-weight-btn {
    background: var(--ts-bg);
    border: 1px solid var(--ts-border);
    color: #cbd5e1;
    border-radius: 6px;
    padding: 5px 4px;
    font-size: 0.7rem;
    font-family: monospace;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.12s ease;
    text-align: center;
  }
  .fs-weight-btn:hover:not(:disabled) {
    border-color: var(--ts-primary);
    color: #fff;
  }
  .fs-weight-btn.active {
    background: rgba(56, 189, 248, 0.2);
    border-color: var(--ts-primary);
    color: #fff;
    font-weight: 700;
  }
  .fs-weight-btn.is-unsupported,
  .fs-weight-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
    text-decoration: line-through;
  }
  .fs-type-pill {
    font-size: 0.58rem;
    font-family: monospace;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 4px;
    flex-shrink: 0;
    text-transform: uppercase;
  }
  .fs-type-pill.is-var {
    background: rgba(56, 189, 248, 0.18);
    color: #7dd3fc;
    border: 1px solid rgba(56, 189, 248, 0.35);
  }
  .fs-type-pill.is-static {
    background: rgba(16, 185, 129, 0.16);
    color: #6ee7b7;
    border: 1px solid rgba(16, 185, 129, 0.35);
  }
  .fs-type-pill.is-disk {
    background: rgba(245, 158, 11, 0.18);
    color: #fde68a;
    border: 1px solid rgba(245, 158, 11, 0.38);
  }
  .fs-type-pill.is-default {
    background: rgba(148, 163, 184, 0.16);
    color: #cbd5e1;
    border: 1px solid rgba(148, 163, 184, 0.32);
  }

  /* Tarjetas en Galería (estilo Gráficos) */
  #fsGalleryContainer.cs-gallery-grid {
    grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
  }
  .fs-font-card {
    border-radius: 14px;
    padding: 16px;
    border: 1px solid rgba(128, 128, 128, 0.24);
    display: flex;
    flex-direction: column;
    gap: 12px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.2s ease;
    cursor: pointer;
    min-width: 0;
    position: relative;
  }
  .fs-font-card:hover {
    border-color: rgba(56, 189, 248, 0.65);
  }
  .fs-font-card.active {
    border-color: var(--ts-primary);
    box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.35);
  }
  .fs-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 0.8rem;
    font-weight: 700;
  }
  .fs-card-title {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .fs-invoke-chip {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.68rem;
    padding: 2px 7px;
    border-radius: 6px;
    background: rgba(56, 189, 248, 0.12);
    border: 1px solid rgba(56, 189, 248, 0.3);
    color: inherit;
    font-weight: 600;
    flex-shrink: 0;
    cursor: pointer;
    transition: background 0.12s ease, border-color 0.12s ease;
  }
  .fs-invoke-chip:hover {
    background: rgba(56, 189, 248, 0.24);
    border-color: var(--ts-primary);
  }
  .fs-card-specimen {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
    min-height: 112px;
    padding: 8px 2px;
    overflow: hidden;
    font-synthesis: none;
  }
  .fs-specimen-glyphs {
    font-size: 1.75rem;
    line-height: 1.15;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .fs-specimen-sample {
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    word-break: break-word;
  }
  .fs-card-foot {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-top: 8px;
    border-top: 1px dashed rgba(128, 128, 128, 0.25);
  }
  .fs-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 6px;
    font-size: 0.7rem;
    opacity: 0.85;
  }
  .fs-weights-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
  }
  .fs-weight-pill {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.64rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 5px;
    border: 1px solid rgba(128, 128, 128, 0.28);
    transition: all 0.12s ease;
  }
  .fs-weight-pill.is-supported {
    background: rgba(56, 189, 248, 0.12);
    border-color: rgba(56, 189, 248, 0.35);
    cursor: pointer;
  }
  .fs-weight-pill.is-supported:hover {
    background: rgba(56, 189, 248, 0.28);
    border-color: var(--ts-primary);
  }
  .fs-weight-pill.is-active {
    background: var(--ts-primary);
    border-color: var(--ts-primary);
    color: #020617 !important;
  }
  .fs-weight-pill.is-unsupported {
    opacity: 0.26;
  }

  /* Vista Enfocada */
  .fs-focused-card {
    width: 100%;
    border-radius: 16px;
    padding: 22px;
    border: 1px solid rgba(128, 128, 128, 0.25);
    transition: max-width 0.25s ease, background-color 0.2s ease;
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.14);
    display: flex;
    flex-direction: column;
    gap: 18px;
  }
  .fs-focused-section-title {
    font-size: 0.74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    opacity: 0.75;
    margin-bottom: 8px;
  }
  .fs-waterfall-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .fs-waterfall-row {
    display: grid;
    grid-template-columns: 150px 1fr auto;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid rgba(128, 128, 128, 0.22);
    cursor: pointer;
    transition: border-color 0.12s ease, background-color 0.12s ease;
  }
  .fs-waterfall-row:hover {
    border-color: rgba(56, 189, 248, 0.6);
  }
  .fs-waterfall-row.is-active {
    border-color: var(--ts-primary);
    background: rgba(56, 189, 248, 0.08);
  }
  .fs-waterfall-weight {
    display: flex;
    flex-direction: column;
    gap: 3px;
    font-size: 0.73rem;
  }
  .fs-waterfall-sample {
    font-synthesis: none;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
  }
  .fs-charset-box {
    padding: 14px;
    border-radius: 10px;
    border: 1px solid rgba(128, 128, 128, 0.22);
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 1.05rem;
    line-height: 1.5;
    word-break: break-word;
    font-synthesis: none;
  }
  .fs-files-table-wrap {
    border: 1px solid rgba(128, 128, 128, 0.25);
    border-radius: 10px;
    overflow: hidden;
  }
  .fs-files-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.75rem;
  }
  .fs-files-table th,
  .fs-files-table td {
    padding: 8px 10px;
    text-align: left;
    border-bottom: 1px solid rgba(128, 128, 128, 0.18);
  }
  .fs-files-table th {
    font-weight: 700;
    opacity: 0.8;
    background: rgba(128, 128, 128, 0.08);
  }
  .fs-files-table tr:last-child td {
    border-bottom: none;
  }
  @media (max-width: 720px) {
    .fs-waterfall-row {
      grid-template-columns: 1fr;
      gap: 6px;
    }
  }
</style>

<!-- Laboratorio Visual de Fuentes (Font Studio) -->
<div id="fontSandboxView">
  <div class="cs-toolbar">
    <div class="cs-toolbar-group">
      <div class="ts-mode-segmented">
        <button type="button" class="ts-mode-btn active" id="btnFsViewGallery" onclick="setFontSandboxViewMode('gallery')">
          🔲 <span>Galería (<span id="fsGalleryCount">0</span>)</span>
        </button>
        <button type="button" class="ts-mode-btn" id="btnFsViewFocused" onclick="setFontSandboxViewMode('focused')">
          🎯 <span>Vista Enfocada (1)</span>
        </button>
      </div>

      <div id="fsFocusedWidthWrap" style="display:none; align-items:center; gap:5px;">
        <span style="font-size:0.74rem; color:var(--ts-muted);">Ancho tarjeta:</span>
        <select id="fsFocusedWidthSelect" class="cs-toolbar-select" onchange="setFontFocusedWidth(this.value)">
          <option value="100%">100% (Ancho completo)</option>
          <option value="760px">760px (Lectura amplia)</option>
          <option value="560px">560px (Columna media)</option>
          <option value="380px">380px (Tarjeta móvil)</option>
        </select>
      </div>
    </div>

    <div class="cs-toolbar-group">
      <select id="fsSurfaceSelect" class="cs-toolbar-select" onchange="setFontStageSurface(this.value)" title="Fondo simulado de las tarjetas">
        <option value="var(--body)">Fondo: --body</option>
        <option value="var(--back-color)">Fondo: back1</option>
        <option value="var(--back-color2)">Fondo: back2</option>
        <option value="var(--back-color3)">Fondo: back3</option>
        <option value="#0c0c0e">Fondo: #0C0C0E</option>
        <option value="#ffffff">Fondo: #FFFFFF</option>
      </select>

      <select id="fsSamplePresetSelect" class="cs-toolbar-select" onchange="setFontSamplePreset(this.value)" title="Texto de prueba rápido">
        <option value="standard">Texto: Pangrama ES</option>
        <option value="specimen">Texto: Specimen Aa–09</option>
        <option value="headline">Texto: Titular Editorial</option>
        <option value="kpi">Texto: Cifras / KPI</option>
      </select>

      <button type="button" class="ts-btn ts-btn-secondary" style="padding:5px 10px; font-size:0.76rem;" onclick="refreshFontsCatalog(true)" title="Re-escanear font-project.css y App/Rsc/Fonts/">
        🔄 <span>Actualizar</span>
      </button>
    </div>
  </div>

  <div class="cs-sandbox-stage" id="fontSandboxStage">
    <div id="fsGalleryContainer" class="cs-gallery-grid">
      <!-- Tarjetas de fuentes disponibles generadas por JS -->
    </div>
    <div id="fsFocusedContainer" class="cs-focused-wrap" style="display:none;">
      <!-- Inspector detallado y cascada de pesos generada por JS -->
    </div>
  </div>

  <!-- Dock Inferior de Código Copy & Paste -->
  <div class="cs-code-dock" id="fsCodeDock">
    <div class="cs-code-header">
      <div class="cs-code-tabs">
        <button type="button" class="cs-code-tab active" id="fsCodeTab_class" onclick="setFontCodeFormat('class')">Clase CSS</button>
        <button type="button" class="cs-code-tab" id="fsCodeTab_html" onclick="setFontCodeFormat('html')">HTML</button>
        <button type="button" class="cs-code-tab" id="fsCodeTab_css" onclick="setFontCodeFormat('css')">CSS font-family</button>
        <button type="button" class="cs-code-tab" id="fsCodeTab_var" onclick="setFontCodeFormat('var')">--font (Tema)</button>
      </div>

      <div class="cs-code-options">
        <label title="Agrega el peso activo cuando es distinto de 400 (ej. roboto bold600)">
          <input type="checkbox" id="fsOptIncludeWeight" checked onchange="updateFontCodeOutput()"> Incluir peso
        </label>
        <label id="fsOptDirectWeightWrap" title="Usa la clase individual de font-project.css (ej. roboto600) en lugar de combinar clase base + bold600">
          <input type="checkbox" id="fsOptDirectWeightClass" onchange="updateFontCodeOutput()"> Clase directa por peso
        </label>
        <label title="Incluye clases JIT de itálica, mayúsculas o números tabulares si están marcadas">
          <input type="checkbox" id="fsOptIncludeTransforms" checked onchange="updateFontCodeOutput()"> Transformaciones
        </label>
      </div>

      <div style="display:flex; align-items:center; gap:6px;">
        <button type="button" class="ts-icon-btn" id="btnToggleFontCodeDock" onclick="toggleFontCodeDock()" title="Colapsar / Expandir visor de código">▾</button>
        <button type="button" class="ts-btn ts-btn-primary" style="padding:5px 11px; font-size:0.76rem;" onclick="copyFontCode()">
          📋 <span>Copiar Código</span>
        </button>
      </div>
    </div>
    <div class="cs-code-body">
      <pre class="cs-code-pre"><code id="fontCodeOutput"></code></pre>
    </div>
  </div>
</div>

<script>
  (function() {
    const ALL_WEIGHTS = [
      { w: 100, label: 'Thin' },
      { w: 200, label: 'ExtraLight' },
      { w: 300, label: 'Light' },
      { w: 400, label: 'Regular' },
      { w: 500, label: 'Medium' },
      { w: 600, label: 'SemiBold' },
      { w: 700, label: 'Bold' },
      { w: 800, label: 'ExtraBold' },
      { w: 900, label: 'Black' }
    ];

    const SAMPLE_PRESETS = {
      standard: 'El veloz murciélago hindú comía feliz cardillo y kiwi',
      specimen: 'Aa Bb Cc Dd Ee Ff Gg 0123456789',
      headline: 'Diseño tipográfico claro y legible para tu interfaz',
      kpi: '$ 128,450.90 · +24.8% · 2026'
    };

    window.fontsCatalog = normalizeFontsCatalog(typeof initialFontsCatalog !== 'undefined' ? initialFontsCatalog : {});
    window.activeFontId = '';
    window.fontSandboxViewMode = 'gallery';
    window.fontStageSurface = 'var(--body)';
    window.fontFocusedWidth = '100%';
    window.fontCodeFormat = 'class';
    window.fontSelectedWeight = 400;
    window.fontSampleSize = 26;
    window.fontLineHeight = 1.35;
    window.fontLetterSpacing = 0;
    window.fontItalic = false;
    window.fontUpper = false;
    window.fontTabular = false;
    window.fontSampleText = SAMPLE_PRESETS.standard;
    window.fontSearchQuery = '';
    let fontRefreshInFlight = false;

    function normalizeFontsCatalog(raw) {
      const cat = (raw && typeof raw === 'object') ? raw : {};
      const families = Array.isArray(cat.families) ? cat.families : [];
      return {
        totalFonts: Number(cat.totalFonts || families.length || 0),
        projectFontsCount: Number(cat.projectFontsCount || 0),
        unregisteredCount: Number(cat.unregisteredCount || 0),
        orphanCssCount: Number(cat.orphanCssCount || 0),
        orphans: Array.isArray(cat.orphans) ? cat.orphans : [],
        fontsDir: String(cat.fontsDir || 'App/Rsc/Fonts'),
        families: families,
        previewFontFacesCss: String(cat.previewFontFacesCss || '')
      };
    }

    function getWeightLabel(w) {
      const found = ALL_WEIGHTS.find(item => item.w === Number(w));
      return found ? found.label : String(w);
    }

    function formatBytesKb(bytes) {
      const b = Number(bytes || 0);
      if (b <= 0) return 'Nativo';
      if (b < 1024) return b + ' B';
      return (b / 1024).toFixed(1) + ' KB';
    }

    function getFilteredFontFamilies() {
      const list = window.fontsCatalog.families || [];
      const q = (window.fontSearchQuery || '').trim().toLowerCase();
      if (!q) return list;

      return list.filter((fam) => {
        const name = (fam.name || '').toLowerCase();
        const family = (fam.fontFamily || '').toLowerCase();
        const baseCls = (fam.baseClass || '').toLowerCase();
        const wClasses = Object.values(fam.weightClasses || {}).join(' ').toLowerCase();
        return name.includes(q) || family.includes(q) || baseCls.includes(q) || wClasses.includes(q);
      });
    }

    function getActiveFontObj() {
      const list = window.fontsCatalog.families || [];
      if (!list.length) return null;
      return list.find(f => f.id === window.activeFontId) || list[0];
    }

    function resolveNearestSupportedWeight(font, desiredWeight) {
      const weights = Array.isArray(font?.weights) && font.weights.length ? font.weights.map(Number) : [400];
      const desired = Number(desiredWeight) || 400;
      if (weights.includes(desired)) return desired;
      if (weights.includes(400)) return 400;
      let best = weights[0];
      let bestDiff = Math.abs(best - desired);
      for (const w of weights) {
        const diff = Math.abs(w - desired);
        if (diff < bestDiff) {
          best = w;
          bestDiff = diff;
        }
      }
      return best;
    }

    function getFontTypePill(fam) {
      if (fam.category === 'default') return { text: 'BASE', cls: 'is-default' };
      if (fam.category === 'unregistered') return { text: 'DISK', cls: 'is-disk' };
      if (fam.isVariable) return { text: 'VAR', cls: 'is-var' };
      return { text: 'EST', cls: 'is-static' };
    }

    window.buildFontInvocationClass = function(font, weight, useDirectClass, includeWeight, includeTransforms) {
      if (!font) return '';
      const w = Number(weight) || 400;
      const parts = [];

      if (font.category === 'unregistered') {
        return `/* Sin registrar en font-project.css (ejecuta: frame font:install) */`;
      }

      if (font.category === 'default') {
        parts.push(font.baseClass || 'font');
        if (includeWeight && w !== 400) {
          parts.push('bold' + w);
        }
      } else {
        const wClasses = font.weightClasses || {};
        const directCls = wClasses[String(w)] || wClasses[w] || '';
        if (useDirectClass && directCls) {
          parts.push(directCls);
        } else {
          const baseCls = font.baseClass || 'customFont';
          parts.push(baseCls);
          if (includeWeight && w !== 400) {
            parts.push('bold' + w);
          }
        }
      }

      if (includeTransforms) {
        if (window.fontItalic) parts.push('ita');
        if (window.fontUpper) parts.push('upper');
        if (window.fontTabular) parts.push('tabular-nums');
      }

      return parts.join(' ').trim();
    };

    window.initFontStudio = function() {
      const list = window.fontsCatalog.families || [];
      if (!window.activeFontId && list.length) {
        window.activeFontId = list[0].id;
      }
      const active = getActiveFontObj();
      if (active) {
        window.fontSelectedWeight = resolveNearestSupportedWeight(active, window.fontSelectedWeight);
      }
      renderFontSidebar();
      renderFontStage();
      updateFontCodeOutput();
    };

    window.refreshFontsCatalog = async function(showNotification = false) {
      if (fontRefreshInFlight) return;
      fontRefreshInFlight = true;
      try {
        const res = await fetch('/_frame/theme-studio/fonts', {
          method: 'GET',
          headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (!res.ok || !data || !data.success) {
          throw new Error(data?.message || 'Error al cargar el catálogo de fuentes');
        }

        if (data.catalog) {
          window.fontsCatalog = normalizeFontsCatalog(data.catalog);
          let facesStyle = document.getElementById('ts-fonts-live-faces');
          if (!facesStyle) {
            facesStyle = document.createElement('style');
            facesStyle.id = 'ts-fonts-live-faces';
            document.head.appendChild(facesStyle);
          }
          facesStyle.textContent = window.fontsCatalog.previewFontFacesCss || '';
        }

        if (Array.isArray(data.fonts)) {
          const fontSel = document.getElementById('fontSelect');
          if (fontSel) {
            const prevVal = fontSel.value;
            fontSel.innerHTML = data.fonts.map(f =>
              `<option value="${escapeHtml(f.value)}">${escapeHtml(f.name)}</option>`
            ).join('') + '<option value="custom">✏️ Personalizada (Escribir abajo)</option>';
            const hasPrev = Array.from(fontSel.options).some(o => o.value === prevVal);
            fontSel.value = hasPrev ? prevVal : 'custom';
          }
        }

        const list = window.fontsCatalog.families || [];
        if (!list.some(f => f.id === window.activeFontId)) {
          window.activeFontId = list.length ? list[0].id : '';
        }
        const active = getActiveFontObj();
        if (active) {
          window.fontSelectedWeight = resolveNearestSupportedWeight(active, window.fontSelectedWeight);
        }

        renderFontSidebar();
        renderFontStage();
        updateFontCodeOutput();

        if (showNotification && typeof showToast === 'function') {
          showToast(`🔠 Fuentes actualizadas (${window.fontsCatalog.totalFonts})`);
        }
      } catch (err) {
        if (showNotification && typeof showToast === 'function') {
          showToast('❌ ' + (err?.message || 'No se pudieron actualizar las fuentes'), 'error');
        }
      } finally {
        fontRefreshInFlight = false;
      }
    };

    window.onFontSearchChange = function(val) {
      window.fontSearchQuery = String(val || '');
      renderFontSidebarGrid();
      renderFontStage();
    };

    window.selectActiveFont = function(fontId, switchToFocused = false) {
      const list = window.fontsCatalog.families || [];
      const target = list.find(f => f.id === fontId);
      if (!target) return;

      window.activeFontId = target.id;
      window.fontSelectedWeight = resolveNearestSupportedWeight(target, window.fontSelectedWeight);

      if (switchToFocused) {
        setFontSandboxViewMode('focused');
      } else {
        renderFontSidebar();
        renderFontStage();
        updateFontCodeOutput();
        if (typeof saveWorkspaceState === 'function') saveWorkspaceState();
      }
    };

    window.selectFontWeight = function(weight, fontId = null) {
      if (fontId && fontId !== window.activeFontId) {
        window.activeFontId = fontId;
      }
      const active = getActiveFontObj();
      if (!active) return;
      const w = Number(weight);
      const supported = (active.weights || []).map(Number);
      if (!supported.includes(w)) return;

      window.fontSelectedWeight = w;
      renderFontSidebar();
      renderFontStage();
      updateFontCodeOutput();
      if (typeof saveWorkspaceState === 'function') saveWorkspaceState();
    };

    window.onFontSampleTextInput = function(val) {
      window.fontSampleText = String(val || '');
      renderFontStage();
    };

    window.setFontSamplePreset = function(presetKey) {
      const txt = SAMPLE_PRESETS[presetKey] || SAMPLE_PRESETS.standard;
      window.fontSampleText = txt;
      const input = document.getElementById('fsSampleTextInput');
      if (input) input.value = txt;
      renderFontStage();
    };

    window.onFontMeasureChange = function(prop, rawVal) {
      const num = parseFloat(rawVal);
      if (!Number.isFinite(num)) return;

      if (prop === 'size') {
        const clamped = Math.max(14, Math.min(72, Math.round(num)));
        window.fontSampleSize = clamped;
        const r = document.getElementById('fsSampleSizeRange');
        const n = document.getElementById('fsSampleSizeNum');
        if (r) r.value = clamped;
        if (n) n.value = clamped;
      } else if (prop === 'lineHeight') {
        const clamped = Math.max(1.0, Math.min(2.2, num));
        window.fontLineHeight = clamped;
        const r = document.getElementById('fsLineHeightRange');
        const n = document.getElementById('fsLineHeightNum');
        if (r) r.value = clamped;
        if (n) n.value = clamped;
      } else if (prop === 'letterSpacing') {
        const clamped = Math.max(-2, Math.min(8, num));
        window.fontLetterSpacing = clamped;
        const r = document.getElementById('fsLetterSpacingRange');
        const n = document.getElementById('fsLetterSpacingNum');
        if (r) r.value = clamped;
        if (n) n.value = clamped;
      }
      renderFontStage();
    };

    window.onFontToggleOption = function() {
      window.fontItalic = !!document.getElementById('fsOptItalic')?.checked;
      window.fontUpper = !!document.getElementById('fsOptUpper')?.checked;
      window.fontTabular = !!document.getElementById('fsOptTabular')?.checked;
      renderFontStage();
      updateFontCodeOutput();
    };

    window.setFontSandboxViewMode = function(mode) {
      window.fontSandboxViewMode = (mode === 'focused') ? 'focused' : 'gallery';
      const btnGal = document.getElementById('btnFsViewGallery');
      const btnFoc = document.getElementById('btnFsViewFocused');
      const galEl = document.getElementById('fsGalleryContainer');
      const focEl = document.getElementById('fsFocusedContainer');
      const widthWrap = document.getElementById('fsFocusedWidthWrap');

      if (btnGal) btnGal.classList.toggle('active', window.fontSandboxViewMode === 'gallery');
      if (btnFoc) btnFoc.classList.toggle('active', window.fontSandboxViewMode === 'focused');
      if (galEl) galEl.style.display = window.fontSandboxViewMode === 'gallery' ? 'grid' : 'none';
      if (focEl) focEl.style.display = window.fontSandboxViewMode === 'focused' ? 'flex' : 'none';
      if (widthWrap) widthWrap.style.display = window.fontSandboxViewMode === 'focused' ? 'flex' : 'none';

      renderFontSidebar();
      renderFontStage();
      updateFontCodeOutput();
      if (typeof syncStudioFloatingScrolls === 'function') {
        requestAnimationFrame(syncStudioFloatingScrolls);
      }
      if (typeof saveWorkspaceState === 'function') saveWorkspaceState();
    };

    window.setFontStageSurface = function(surfaceVal) {
      window.fontStageSurface = surfaceVal || 'var(--body)';
      injectLiveThemeIntoFontSandbox();
      if (typeof saveWorkspaceState === 'function') saveWorkspaceState();
    };

    window.setFontFocusedWidth = function(widthVal) {
      window.fontFocusedWidth = widthVal || '100%';
      const card = document.querySelector('#fsFocusedContainer .fs-focused-card');
      if (card) {
        card.style.maxWidth = window.fontFocusedWidth;
      }
      if (typeof saveWorkspaceState === 'function') saveWorkspaceState();
    };

    window.setFontCodeFormat = function(fmt) {
      const valid = ['class', 'html', 'css', 'var'];
      window.fontCodeFormat = valid.includes(fmt) ? fmt : 'class';
      valid.forEach((f) => {
        const el = document.getElementById('fsCodeTab_' + f);
        if (el) el.classList.toggle('active', f === window.fontCodeFormat);
      });
      updateFontCodeOutput();
      if (typeof saveWorkspaceState === 'function') saveWorkspaceState();
    };

    window.toggleFontCodeDock = function() {
      const dock = document.getElementById('fsCodeDock');
      const btn = document.getElementById('btnToggleFontCodeDock');
      if (!dock) return;
      const collapsed = dock.classList.toggle('collapsed');
      if (btn) btn.textContent = collapsed ? '▸' : '▾';
      if (typeof saveWorkspaceState === 'function') saveWorkspaceState();
    };

    window.applySelectedFontAsGlobal = function() {
      const active = getActiveFontObj();
      if (!active) return;

      if (active.category === 'unregistered') {
        if (typeof showToast === 'function') {
          showToast('⚠️ Instala primero la fuente con "frame font:install" para registrarla en el sitio.', 'warning');
        }
        return;
      }

      const fontVal = active.cssValue || "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif";
      const customInput = document.getElementById('fontCustom');
      const fontSel = document.getElementById('fontSelect');

      if (customInput) customInput.value = fontVal;
      if (fontSel) {
        const match = Array.from(fontSel.options).find(o => o.value === fontVal);
        fontSel.value = match ? match.value : 'custom';
      }
      if (typeof updateLayoutProperty === 'function') {
        updateLayoutProperty('font', fontVal);
      }
      if (typeof showToast === 'function') {
        showToast(`✨ "${active.name}" aplicada como fuente global (--font)`);
      }
    };

    window.copyFontInlineSnippet = function(text, event) {
      if (event) event.stopPropagation();
      const str = String(text || '').trim();
      if (!str) return;
      navigator.clipboard.writeText(str).then(() => {
        if (typeof showToast === 'function') {
          showToast(`📋 Copiado: ${str}`);
        }
      });
    };

    window.copyFontCode = function() {
      const codeEl = document.getElementById('fontCodeOutput');
      const text = codeEl ? codeEl.textContent : '';
      if (!text) return;
      navigator.clipboard.writeText(text).then(() => {
        if (typeof showToast === 'function') {
          showToast('📋 Código de tipografía copiado al portapapeles');
        }
      });
    };

    window.injectLiveThemeIntoFontSandbox = function() {
      const view = document.getElementById('fontSandboxView');
      if (!view || typeof themeConfig === 'undefined') return;

      const isDark = (typeof currentPreviewMode !== 'undefined' && currentPreviewMode === 'dark');
      if (isDark) {
        view.setAttribute('data-theme', 'dark');
      } else {
        view.removeAttribute('data-theme');
      }

      const lightColors = themeConfig.light.colors || [];
      const darkColors = (themeConfig.dark.colors && themeConfig.dark.colors.length)
        ? themeConfig.dark.colors
        : lightColors.map(c => invertToDarkHex(c));

      const activeBody = isDark ? themeConfig.dark.body : themeConfig.light.body;
      const activeTextPrimary = isDark ? themeConfig.dark.text.primary : themeConfig.light.text.primary;
      const activeTextSecondary = isDark ? themeConfig.dark.text.secondary : themeConfig.light.text.secondary;
      const activeTextInactive = isDark ? themeConfig.dark.text.inactive : themeConfig.light.text.inactive;
      const activeColors = isDark ? darkColors : lightColors;

      let css = `#fontSandboxView {\n`;
      css += `  --font: ${themeConfig.layout.font};\n`;
      css += `  --font-size: ${themeConfig.layout.fontSize};\n`;
      css += `  --body: ${activeBody};\n`;
      css += `  --text-primary: ${activeTextPrimary};\n`;
      css += `  --text-secondary: ${activeTextSecondary};\n`;
      css += `  --text-inactive: ${activeTextInactive};\n`;
      activeColors.forEach((hex, i) => {
        const vName = i === 0 ? '--back-color' : `--back-color${i + 1}`;
        css += `  ${vName}: ${hex};\n`;
        if (i === 0) css += `  --back-color1: ${hex};\n`;
      });
      css += `}\n`;

      let cardTextColor = 'var(--text-primary)';
      if (window.fontStageSurface === '#0c0c0e') {
        cardTextColor = '#e2e8f0';
      } else if (window.fontStageSurface === '#ffffff') {
        cardTextColor = '#0f172a';
      } else if (window.fontStageSurface.startsWith('var(--back-color') && typeof resolveColorTokenPreviewHex === 'function' && typeof getRelativeLuminance === 'function') {
        const resolved = resolveColorTokenPreviewHex(window.fontStageSurface);
        cardTextColor = getRelativeLuminance(resolved) < 0.35 ? '#f8fafc' : '#0f172a';
      }

      css += `#fontSandboxView .fs-font-card, #fontSandboxView .fs-focused-card {\n`;
      css += `  background-color: ${window.fontStageSurface};\n`;
      css += `  color: ${cardTextColor};\n`;
      css += `}\n`;

      let styleEl = document.getElementById('ts-fonts-live-theme-vars');
      if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'ts-fonts-live-theme-vars';
        document.head.appendChild(styleEl);
      }
      styleEl.textContent = css;
    };

    function renderFontSidebar() {
      renderFontSidebarGrid();
      renderFontWeightButtons();
      renderFontSummaryBox();
    }

    function renderFontSidebarGrid() {
      const grid = document.getElementById('fsFontTypeGrid');
      const countBadge = document.getElementById('fsSidebarCountBadge');
      const emptyBanner = document.getElementById('fsProjectEmptyBanner');
      const orphanNotice = document.getElementById('fsOrphanNotice');
      const barBadge = document.getElementById('fsActiveFontBadge');
      const galCount = document.getElementById('fsGalleryCount');

      const all = window.fontsCatalog.families || [];
      const filtered = getFilteredFontFamilies();
      const active = getActiveFontObj();

      if (countBadge) countBadge.textContent = String(all.length);
      if (galCount) galCount.textContent = String(filtered.length);
      if (barBadge && active) {
        const inv = active.category === 'unregistered' ? 'sin registrar' : '.' + (active.baseClass || 'font');
        barBadge.textContent = inv;
      }

      if (emptyBanner) {
        emptyBanner.style.display = window.fontsCatalog.projectFontsCount === 0 ? 'block' : 'none';
      }

      if (orphanNotice) {
        if (window.fontsCatalog.orphanCssCount > 0) {
          orphanNotice.style.display = 'block';
          orphanNotice.innerHTML = `⚠️ Se ignoraron <strong>${window.fontsCatalog.orphanCssCount}</strong> bloque(s) huérfano(s) en <code>font-project.css</code> porque su archivo no existe en disco. Usa <code>frame font:reset</code> para limpiarlos.`;
        } else {
          orphanNotice.style.display = 'none';
        }
      }

      if (!grid) return;
      if (!filtered.length) {
        grid.innerHTML = `<div style="grid-column:1 / -1; font-size:0.74rem; color:var(--ts-muted); text-align:center; padding:12px;">Sin coincidencias para "${escapeHtml(window.fontSearchQuery)}".</div>`;
        return;
      }

      grid.innerHTML = filtered.map((fam) => {
        const isActive = active && fam.id === active.id;
        const pill = getFontTypePill(fam);
        const invokeLabel = fam.category === 'unregistered'
          ? 'sin clase'
          : '.' + (fam.baseClass || 'font');

        return `
          <button type="button" class="cs-type-btn${isActive ? ' active' : ''}" onclick="selectActiveFont('${escapeHtml(fam.id)}')" title="${escapeHtml(fam.name)} (${escapeHtml(fam.weightRangeLabel)})">
            <div class="cs-type-btn-text" style="flex:1;">
              <span class="cs-type-btn-name">${escapeHtml(fam.name)}</span>
              <span class="cs-type-btn-method">${escapeHtml(invokeLabel)} · ${escapeHtml(fam.weightRangeLabel)}</span>
            </div>
            <span class="fs-type-pill ${pill.cls}">${pill.text}</span>
          </button>
        `;
      }).join('');
    }

    function renderFontWeightButtons() {
      const grid = document.getElementById('fsWeightGrid');
      const rangeBadge = document.getElementById('fsWeightRangeBadge');
      const activeLabel = document.getElementById('fsActiveWeightLabel');
      const active = getActiveFontObj();
      if (!grid || !active) return;

      const supported = (active.weights || []).map(Number);
      if (rangeBadge) rangeBadge.textContent = active.weightRangeLabel || '400';
      if (activeLabel) activeLabel.textContent = `${window.fontSelectedWeight} · ${getWeightLabel(window.fontSelectedWeight)}`;

      grid.innerHTML = ALL_WEIGHTS.map((item) => {
        const isSup = supported.includes(item.w);
        const isAct = isSup && Number(window.fontSelectedWeight) === item.w;
        const cls = [
          'fs-weight-btn',
          isAct ? 'active' : '',
          !isSup ? 'is-unsupported' : ''
        ].filter(Boolean).join(' ');

        const title = isSup
          ? `${item.w} ${item.label} (Disponible)`
          : `${item.w} ${item.label} (No instalado en ${active.name})`;

        return `<button type="button" class="${cls}" ${isSup ? '' : 'disabled'} title="${escapeHtml(title)}" onclick="selectFontWeight(${item.w})">${item.w}</button>`;
      }).join('');
    }

    function renderFontSummaryBox() {
      const box = document.getElementById('fsSelectedSummaryBox');
      const active = getActiveFontObj();
      if (!box || !active) return;

      const files = Array.isArray(active.files) ? active.files : [];
      const invokeRegular = active.category === 'unregistered'
        ? 'Requiere <code>frame font:install</code>'
        : `<code>${escapeHtml(active.baseClass)}</code>`;

      const invokeMulti = (active.category !== 'unregistered' && active.weights && active.weights.some(w => w !== 400))
        ? `<div style="margin-top:4px;">Ejemplo otro peso: <code>${escapeHtml(active.baseClass)} bold${escapeHtml(String(active.weights.find(w => w !== 400)))}</code></div>`
        : '';

      const filesHtml = files.length
        ? `<ul style="margin:6px 0 0 16px; padding:0; font-size:0.71rem;">` + files.map(f =>
            `<li><code>${escapeHtml(f.filename)}</code> · ${escapeHtml(f.format)} (${formatBytesKb(f.sizeBytes)}) · peso <strong>${escapeHtml(f.weight)}</strong>${f.preloaded ? ' ⚡' : ''}</li>`
          ).join('') + `</ul>`
        : `<div style="margin-top:4px; font-size:0.71rem; opacity:0.85;">Fuente nativa del sistema operativo del usuario (0 KB de descarga).</div>`;

      box.innerHTML = `
        <div><strong>Familia:</strong> <code>${escapeHtml(active.fontFamily)}</code></div>
        <div><strong>Invocación base:</strong> ${invokeRegular}</div>
        ${invokeMulti}
        <div><strong>Pesos soportados:</strong> ${escapeHtml(active.weightRangeLabel)}</div>
        ${filesHtml}
      `;
    }

    window.renderFontStage = function() {
      injectLiveThemeIntoFontSandbox();
      if (window.fontSandboxViewMode === 'focused') {
        renderFontFocused();
      } else {
        renderFontGallery();
      }
      updateFontCodeOutput();
    };

    function getSampleTransformStyle() {
      const style = window.fontItalic ? 'italic' : 'normal';
      const transform = window.fontUpper ? 'uppercase' : 'none';
      const variant = window.fontTabular ? 'tabular-nums' : 'normal';
      return `font-style:${style}; text-transform:${transform}; font-variant-numeric:${variant};`;
    }

    function renderFontGallery() {
      const container = document.getElementById('fsGalleryContainer');
      if (!container) return;

      const list = getFilteredFontFamilies();
      const active = getActiveFontObj();

      if (!list.length) {
        container.innerHTML = `<div style="grid-column:1 / -1; text-align:center; padding:36px; opacity:0.75;">No se encontraron fuentes con ese filtro.</div>`;
        return;
      }

      const sampleTxt = (window.fontSampleText || '').trim() || SAMPLE_PRESETS.standard;
      const transformCss = getSampleTransformStyle();

      container.innerHTML = list.map((fam) => {
        const isSelected = active && fam.id === active.id;
        const pill = getFontTypePill(fam);
        const cardWeight = isSelected
          ? window.fontSelectedWeight
          : resolveNearestSupportedWeight(fam, window.fontSelectedWeight);

        const invokeClass = fam.category === 'unregistered'
          ? 'sin registrar'
          : window.buildFontInvocationClass(fam, cardWeight, false, true, false);

        const copyVal = fam.category === 'unregistered' ? fam.fontFamily : invokeClass;

        const typeText = fam.category === 'default'
          ? 'Navegador (0 KB)'
          : (fam.isVariable
              ? `Variable ${fam.weightRangeLabel}`
              : `Estática (${fam.weights.length} ${fam.weights.length === 1 ? 'peso' : 'pesos'})`);

        const formats = Array.from(new Set((fam.files || []).map(f => f.format))).join(', ');
        const sizeLabel = fam.totalSizeBytes > 0 ? `${formats} · ${formatBytesKb(fam.totalSizeBytes)}` : 'Sistema';
        const preloadBadge = fam.preloaded ? ' · ⚡ Preload' : '';

        const supportedSet = new Set((fam.weights || []).map(Number));
        const stripHtml = ALL_WEIGHTS.map((item) => {
          const isSup = supportedSet.has(item.w);
          const isAct = isSelected && Number(window.fontSelectedWeight) === item.w;
          const pCls = [
            'fs-weight-pill',
            isSup ? 'is-supported' : 'is-unsupported',
            isAct ? 'is-active' : ''
          ].filter(Boolean).join(' ');

          const clickAttr = isSup
            ? `onclick="event.stopPropagation(); selectFontWeight(${item.w}, '${escapeHtml(fam.id)}')"`
            : '';
          const titleAttr = isSup
            ? `Probar peso ${item.w} (${item.label})`
            : `Peso ${item.w} no instalado`;

          return `<span class="${pCls}" title="${escapeHtml(titleAttr)}" ${clickAttr}>${item.w}</span>`;
        }).join('');

        return `
          <div class="fs-font-card${isSelected ? ' active' : ''}" onclick="selectActiveFont('${escapeHtml(fam.id)}')" ondblclick="selectActiveFont('${escapeHtml(fam.id)}', true)">
            <div class="fs-card-head">
              <div class="fs-card-title">
                <span class="fs-type-pill ${pill.cls}">${pill.text}</span>
                <span title="${escapeHtml(fam.name)}">${escapeHtml(fam.name)}</span>
              </div>
              <button type="button" class="fs-invoke-chip" title="Clic para copiar clase de invocación" onclick="copyFontInlineSnippet('${escapeHtml(copyVal)}', event)">
                ${fam.category === 'unregistered' ? 'sin registrar' : '.' + escapeHtml(invokeClass)}
              </button>
            </div>

            <div class="fs-card-specimen" style="font-family:${escapeHtml(fam.previewCssValue)}; font-weight:${cardWeight}; ${transformCss}">
              <div class="fs-specimen-glyphs">Aa Bb Cc 123</div>
              <div class="fs-specimen-sample" style="font-size:${Math.min(34, window.fontSampleSize)}px; line-height:${window.fontLineHeight}; letter-spacing:${window.fontLetterSpacing}px;">
                ${escapeHtml(sampleTxt)}
              </div>
            </div>

            <div class="fs-card-foot">
              <div class="fs-meta-row">
                <span>${escapeHtml(typeText)}</span>
                <span>${escapeHtml(sizeLabel + preloadBadge)}</span>
              </div>
              <div class="fs-weights-strip">
                ${stripHtml}
              </div>
            </div>
          </div>
        `;
      }).join('');
    }

    function renderFontFocused() {
      const container = document.getElementById('fsFocusedContainer');
      const active = getActiveFontObj();
      if (!container || !active) return;

      const pill = getFontTypePill(active);
      const sampleTxt = (window.fontSampleText || '').trim() || SAMPLE_PRESETS.standard;
      const transformCss = getSampleTransformStyle();
      const useDirect = !!document.getElementById('fsOptDirectWeightClass')?.checked;
      const includeTransforms = !!document.getElementById('fsOptIncludeTransforms')?.checked;

      const rowsHtml = (active.weights || [400]).map((w) => {
        const wNum = Number(w);
        const isAct = Number(window.fontSelectedWeight) === wNum;
        const invokeCls = window.buildFontInvocationClass(active, wNum, useDirect, true, includeTransforms);
        const canCopy = active.category !== 'unregistered';

        return `
          <div class="fs-waterfall-row${isAct ? ' is-active' : ''}" onclick="selectFontWeight(${wNum})">
            <div class="fs-waterfall-weight">
              <strong>${wNum} · ${escapeHtml(getWeightLabel(wNum))}</strong>
              <span style="font-family:monospace; font-size:0.68rem; opacity:0.85;">${canCopy ? escapeHtml(invokeCls) : 'Sin clase CSS'}</span>
            </div>
            <div class="fs-waterfall-sample" style="font-family:${escapeHtml(active.previewCssValue)}; font-weight:${wNum}; font-size:${window.fontSampleSize}px; line-height:${window.fontLineHeight}; letter-spacing:${window.fontLetterSpacing}px; ${transformCss}">
              ${escapeHtml(sampleTxt)}
            </div>
            <div>
              ${canCopy ? `<button type="button" class="fs-invoke-chip" onclick="copyFontInlineSnippet('${escapeHtml(invokeCls)}', event)" title="Copiar clase">📋</button>` : ''}
            </div>
          </div>
        `;
      }).join('');

      const files = active.files || [];
      const filesTableHtml = files.length
        ? `
          <div>
            <div class="fs-focused-section-title">Archivos en Disco (${files.length})</div>
            <div class="fs-files-table-wrap">
              <table class="fs-files-table">
                <thead>
                  <tr>
                    <th>Archivo</th>
                    <th>Formato</th>
                    <th>Peso CSS</th>
                    <th>Estilo</th>
                    <th>Tamaño</th>
                    <th>Preload</th>
                  </tr>
                </thead>
                <tbody>
                  ${files.map(f => `
                    <tr>
                      <td><code>${escapeHtml(f.url)}</code></td>
                      <td>${escapeHtml(f.format)}</td>
                      <td><code>${escapeHtml(f.weight)}</code></td>
                      <td>${escapeHtml(f.style)}</td>
                      <td>${escapeHtml(formatBytesKb(f.sizeBytes))}</td>
                      <td>${f.preloaded ? '⚡ Activo' : '—'}</td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          </div>
        `
        : '';

      container.innerHTML = `
        <div class="fs-focused-card" style="max-width:${escapeHtml(window.fontFocusedWidth)};">
          <div class="fs-card-head" style="font-size:1rem;">
            <div class="fs-card-title">
              <span class="fs-type-pill ${pill.cls}">${pill.text}</span>
              <span>${escapeHtml(active.name)}</span>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
              <span style="font-family:monospace; font-size:0.74rem; opacity:0.75;">Pesos: ${escapeHtml(active.weightRangeLabel)}</span>
              ${active.category !== 'unregistered'
                ? `<button type="button" class="fs-invoke-chip" onclick="copyFontInlineSnippet('${escapeHtml(active.baseClass)}', event)">.${escapeHtml(active.baseClass)}</button>`
                : ''}
            </div>
          </div>

          <div>
            <div class="fs-focused-section-title">Cascada de Pesos Soportados (Waterfall)</div>
            <div class="fs-waterfall-list">
              ${rowsHtml}
            </div>
          </div>

          <div>
            <div class="fs-focused-section-title">Mapa de Caracteres</div>
            <div class="fs-charset-box" style="font-family:${escapeHtml(active.previewCssValue)}; font-weight:${window.fontSelectedWeight}; ${transformCss}">
              <div>ABCDEFGHIJKLMNOPQRSTUVWXYZ</div>
              <div>abcdefghijklmnopqrstuvwxyz</div>
              <div>0123456789 · á é í ó ú ü ñ Á É Í Ó Ú Ü Ñ</div>
              <div>! ? @ # $ % &amp; / ( ) [ ] { } = + - * : ; , .</div>
            </div>
          </div>

          ${filesTableHtml}
        </div>
      `;
    }

    window.updateFontCodeOutput = function() {
      const outEl = document.getElementById('fontCodeOutput');
      const directChk = document.getElementById('fsOptDirectWeightClass');
      const directWrap = document.getElementById('fsOptDirectWeightWrap');
      const includeWeightChk = document.getElementById('fsOptIncludeWeight');
      const includeTransChk = document.getElementById('fsOptIncludeTransforms');
      const active = getActiveFontObj();

      if (!outEl || !active) return;

      const w = Number(window.fontSelectedWeight) || 400;
      const wClasses = active.weightClasses || {};
      const hasDirectForWeight = !!(wClasses[String(w)] || wClasses[w]);

      if (directChk && directWrap) {
        directChk.disabled = !hasDirectForWeight;
        if (!hasDirectForWeight) directChk.checked = false;
        directWrap.style.opacity = hasDirectForWeight ? '1' : '0.45';
      }

      const includeWeight = includeWeightChk ? includeWeightChk.checked : true;
      const useDirect = directChk ? (directChk.checked && hasDirectForWeight) : false;
      const includeTransforms = includeTransChk ? includeTransChk.checked : true;

      const clsString = window.buildFontInvocationClass(active, w, useDirect, includeWeight, includeTransforms);
      const sampleTxt = ((window.fontSampleText || '').trim() || 'Texto de ejemplo').slice(0, 60);

      let code = '';
      if (window.fontCodeFormat === 'class') {
        code = clsString;
      } else if (window.fontCodeFormat === 'html') {
        if (active.category === 'unregistered') {
          code = `<!-- Registra la fuente primero con: frame font:install -->\n<p style="font-family: ${active.cssValue}; font-weight: ${w};">${sampleTxt}</p>`;
        } else {
          code = `<p class="${clsString}">${sampleTxt}</p>`;
        }
      } else if (window.fontCodeFormat === 'css') {
        const lines = [`font-family: ${active.cssValue};`];
        if (includeWeight) lines.push(`font-weight: ${w};`);
        if (includeTransforms && window.fontItalic) lines.push(`font-style: italic;`);
        if (includeTransforms && window.fontUpper) lines.push(`text-transform: uppercase;`);
        if (includeTransforms && window.fontTabular) lines.push(`font-variant-numeric: tabular-nums;`);
        code = lines.join('\n');
      } else if (window.fontCodeFormat === 'var') {
        code = `--font: ${active.cssValue};`;
      }

      outEl.textContent = code;
    };
  })();
</script>
