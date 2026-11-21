<?php
if (!defined('ROOT_PATH') || !class_exists(\Base\Studio\StudioKernel::class) || !\Base\Studio\StudioKernel::isDevEnvironment()) {
  http_response_code(404);
  exit;
}
?>
<style>
  .jsm-card {
    background: var(--ts-bg);
    border: 1px solid var(--ts-border);
    border-radius: 10px;
    padding: 13px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 10px;
    transition: border-color 0.15s ease, transform 0.15s ease;
  }
  .jsm-card.is-active {
    border-color: rgba(56, 189, 248, 0.45);
  }
  .jsm-card:hover {
    border-color: rgba(56, 189, 248, 0.65);
  }
  .jsm-desc {
    font-size: 0.77rem;
    color: var(--ts-muted);
    line-height: 1.35;
    min-height: 3.9em;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .jsm-card-footer {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: auto;
  }
  .jsm-docs-btn {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    background: rgba(56, 189, 248, 0.08);
    border: 1px solid rgba(56, 189, 248, 0.25);
    color: var(--ts-primary);
    border-radius: 7px;
    padding: 6px 10px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
  }
  .jsm-docs-btn:hover {
    background: rgba(56, 189, 248, 0.16);
    border-color: var(--ts-primary);
  }
  .jsm-docs-count {
    font-size: 0.68rem;
    background: rgba(15, 23, 42, 0.7);
    color: var(--ts-text);
    padding: 1px 7px;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.22);
  }

  /* Modal de Documentación de Módulos JS */
  .jsm-modal-box {
    width: min(920px, 94vw);
    max-width: 920px;
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    padding: 0;
    overflow: hidden;
    background: #0f172a;
    border: 1px solid var(--ts-border);
    border-radius: 14px;
    box-shadow: 0 24px 56px rgba(0, 0, 0, 0.65);
  }
  .jsm-modal-header {
    padding: 18px 22px 14px;
    border-bottom: 1px solid var(--ts-border);
    background: var(--ts-surface);
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex-shrink: 0;
  }
  .jsm-modal-header-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
  }
  .jsm-modal-close {
    background: rgba(148, 163, 184, 0.12);
    border: 1px solid var(--ts-border);
    color: var(--ts-text);
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .jsm-modal-close:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.45);
    color: #fca5a5;
  }
  .jsm-modal-body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    scrollbar-width: none;
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 20px;
  }
  .jsm-modal-body::-webkit-scrollbar {
    display: none;
  }
  .jsm-section-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 10px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .jsm-code-card {
    background: #020617;
    border: 1px solid var(--ts-border);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 12px;
  }
  .jsm-code-card:last-child {
    margin-bottom: 0;
  }
  .jsm-code-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 12px;
    background: rgba(30, 41, 59, 0.85);
    border-bottom: 1px solid var(--ts-border);
  }
  .jsm-code-title {
    font-size: 0.78rem;
    font-weight: 600;
    color: #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .jsm-lang-badge {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 2px 6px;
    border-radius: 5px;
    background: rgba(56, 189, 248, 0.14);
    color: var(--ts-primary);
  }
  .jsm-copy-btn {
    background: rgba(56, 189, 248, 0.12);
    border: 1px solid rgba(56, 189, 248, 0.3);
    color: var(--ts-primary);
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 0.72rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease;
  }
  .jsm-copy-btn:hover {
    background: rgba(56, 189, 248, 0.24);
  }
  .jsm-code-pre {
    margin: 0;
    padding: 13px 14px;
    overflow-x: auto;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 0.78rem;
    line-height: 1.55;
    color: #e2e8f0;
    tab-size: 2;
    white-space: pre;
  }
  .hl-comment { color: #64748b; font-style: italic; }
  .hl-tag { color: #38bdf8; }
  .hl-attr { color: #a5f3fc; }
  .hl-str { color: #86efac; }
  .hl-kw { color: #c084fc; font-weight: 600; }
  .hl-num { color: #fbbf24; }

  .jsm-table-wrap {
    border: 1px solid var(--ts-border);
    border-radius: 10px;
    overflow: hidden;
    background: rgba(2, 6, 23, 0.55);
    margin-bottom: 12px;
  }
  .jsm-table-wrap:last-child {
    margin-bottom: 0;
  }
  .jsm-table-subtitle {
    padding: 8px 12px;
    font-size: 0.77rem;
    font-weight: 700;
    color: #cbd5e1;
    background: rgba(30, 41, 59, 0.65);
    border-bottom: 1px solid var(--ts-border);
  }
  .jsm-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.77rem;
  }
  .jsm-table th {
    text-align: left;
    padding: 8px 12px;
    color: var(--ts-muted);
    font-weight: 600;
    border-bottom: 1px solid var(--ts-border);
    background: rgba(15, 23, 42, 0.8);
  }
  .jsm-table td {
    padding: 8px 12px;
    color: #e2e8f0;
    border-bottom: 1px solid rgba(51, 65, 85, 0.55);
    vertical-align: top;
    line-height: 1.4;
  }
  .jsm-table tr:last-child td {
    border-bottom: none;
  }
  .jsm-mono-pill {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.73rem;
    background: rgba(56, 189, 248, 0.12);
    color: #7dd3fc;
    padding: 2px 6px;
    border-radius: 5px;
    white-space: nowrap;
  }
  .jsm-opt-badge {
    display: inline-block;
    font-size: 0.68rem;
    padding: 1px 6px;
    border-radius: 999px;
    background: rgba(245, 158, 11, 0.14);
    color: #fbbf24;
  }
  .jsm-req-badge {
    display: inline-block;
    font-size: 0.68rem;
    padding: 1px 6px;
    border-radius: 999px;
    background: rgba(56, 189, 248, 0.14);
    color: var(--ts-primary);
  }
</style>

<!-- STAGE: GESTOR VISUAL DE MÓDULOS JS Y LIBRERÍAS -->
<div id="jsModulesSandboxView" style="display:none; flex-direction:column; flex:1; width:100%; align-self:stretch; min-height:0; overflow-y:auto; padding:22px; gap:18px; background:var(--ts-bg); border-radius:10px; border:1px solid var(--ts-border);">
  <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; background:var(--ts-surface); border:1px solid var(--ts-border); border-radius:12px; padding:16px 20px;">
    <div>
      <h2 style="font-size:1.1rem; font-weight:700; color:#fff; margin-bottom:4px;">📦 Módulos JavaScript y Librerías de Producción</h2>
      <p style="font-size:0.82rem; color:var(--ts-muted);">Activa únicamente lo que tu aplicación necesita. Las librerías desactivadas no se incluirán en el paquete de producción.</p>
    </div>
    <div style="display:flex; gap:8px; align-items:center;">
      <button type="button" class="ts-btn ts-btn-secondary" onclick="detectJsUsage(true)">🔍 Re-escanear proyecto</button>
      <button type="button" class="ts-btn ts-btn-success" onclick="saveJsModulesConfig()">💾 Guardar y Compilar</button>
    </div>
  </div>

  <!-- Librerías en App/Rsc/Library -->
  <div style="background:var(--ts-surface); border:1px solid var(--ts-border); border-radius:12px; padding:16px 20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
      <h3 style="font-size:0.95rem; font-weight:700; color:#fff;">📚 Librerías Externas (<code style="color:var(--ts-primary);">loadLibraryJsConfiguration.php</code>)</h3>
      <span id="jsLibsBadge" style="font-size:0.78rem; color:var(--ts-muted);"></span>
    </div>
    <div id="jsLibrariesGrid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:12px;"></div>
  </div>

  <!-- Componentes JS -->
  <div style="background:var(--ts-surface); border:1px solid var(--ts-border); border-radius:12px; padding:16px 20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
      <h3 style="font-size:0.95rem; font-weight:700; color:#fff;">🧩 Componentes Vanilla ES (<code style="color:var(--ts-primary);">jsConfig.json</code>)</h3>
      <span id="jsComponentsBadge" style="font-size:0.78rem; color:var(--ts-muted);"></span>
    </div>
    <div id="jsComponentsGrid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(310px, 1fr)); gap:12px;"></div>
  </div>
</div>

<!-- MODAL DE DOCUMENTACIÓN Y EJEMPLOS FORMATEADOS -->
<div id="modalJsModuleDocs" class="ts-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="jsmModalTitle" onclick="if (event.target === this) closeJsModuleDocsModal();">
  <div class="jsm-modal-box">
    <div id="jsmModalHeader" class="jsm-modal-header"></div>
    <div id="jsmModalBody" class="jsm-modal-body"></div>
  </div>
</div>

<script>
  let __jsCatalogState = null;
  let __jsUsageState = null;
  let __jsDocsCodeBlocks = {};

  function formatBytesKb(bytes) {
    const b = Number(bytes || 0);
    if (b < 1024) return b + ' B';
    return (b / 1024).toFixed(2) + ' KB';
  }

  function escapeHtmlStudio(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function highlightCodeStudio(rawCode, lang) {
    const escaped = escapeHtmlStudio(rawCode);
    const kind = String(lang || 'js').toLowerCase();

    if (kind === 'html') {
      return escaped.replace(/(&lt;!--[\s\S]*?--&gt;)|(&lt;\/?[a-zA-Z0-9:-]+(?:\s+[\s\S]*?)?\/?&gt;)/g, (match, comment, tag) => {
        if (comment) {
          return `<span class="hl-comment">${comment}</span>`;
        }
        return tag.replace(/^(&lt;\/?[a-zA-Z0-9:-]+)([\s\S]*?)(\/?&gt;)$/, (_, open, attrs, close) => {
          const coloredAttrs = attrs.replace(/(\s+)([a-zA-Z0-9_:-]+)(\s*=\s*)(&quot;[^&]*?&quot;|'[^']*?')/g, '$1<span class="hl-attr">$2</span>$3<span class="hl-str">$4</span>');
          return `<span class="hl-tag">${open}</span>${coloredAttrs}<span class="hl-tag">${close}</span>`;
        });
      });
    }

    if (kind === 'json') {
      return escaped.replace(/(&quot;(?:\\.|[^&])*?&quot;)(\s*:)?|\b(true|false|null)\b|(-?\d+(?:\.\d+)?)/g, (match, str, colon, boolVal, numVal) => {
        if (str) {
          return colon
            ? `<span class="hl-attr">${str}</span>${colon}`
            : `<span class="hl-str">${str}</span>`;
        }
        if (boolVal) return `<span class="hl-kw">${boolVal}</span>`;
        if (numVal) return `<span class="hl-num">${numVal}</span>`;
        return match;
      });
    }

    return escaped.replace(/(\/\/[^\n]*|\/\*[\s\S]*?\*\/)|(&quot;(?:\\.|[^&])*?&quot;|'(?:\\.|[^'])*'|`(?:\\.|[^`])*`)|\b(const|let|var|function|async|await|return|if|else|for|of|in|new|true|false|null|undefined|document|window)\b|\b(\d+(?:\.\d+)?)\b/g, (match, comment, str, kw, num) => {
      if (comment) return `<span class="hl-comment">${comment}</span>`;
      if (str) return `<span class="hl-str">${str}</span>`;
      if (kw) return `<span class="hl-kw">${kw}</span>`;
      if (num) return `<span class="hl-num">${num}</span>`;
      return match;
    });
  }

  function copyJsModuleCode(blockId) {
    const rawText = __jsDocsCodeBlocks[blockId];
    if (typeof rawText !== 'string') return;

    const onCopied = () => {
      if (typeof showToast === 'function') {
        showToast('📋 Código copiado al portapapeles');
      }
    };

    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
      navigator.clipboard.writeText(rawText).then(onCopied).catch(() => fallbackCopyText(rawText, onCopied));
      return;
    }
    fallbackCopyText(rawText, onCopied);
  }

  function fallbackCopyText(text, callback) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      if (typeof callback === 'function') callback();
    } finally {
      document.body.removeChild(ta);
    }
  }

  function openJsModuleDocsModal(idx) {
    if (!__jsCatalogState || !__jsCatalogState.modules || !__jsCatalogState.modules[idx]) return;
    const mod = __jsCatalogState.modules[idx];
    const docs = mod.docs || {};
    const overlay = document.getElementById('modalJsModuleDocs');
    const headerEl = document.getElementById('jsmModalHeader');
    const bodyEl = document.getElementById('jsmModalBody');
    if (!overlay || !headerEl || !bodyEl) return;

    __jsDocsCodeBlocks = {};

    const isCore = mod.category === 'core';
    const statusLabel = mod.active
      ? `Activo (${mod.mode})`
      : (mod.mode === 'excluded' ? 'Excluido' : 'Inactivo');
    const statusStyle = mod.active
      ? 'background:rgba(34,197,94,0.16); color:#4ade80;'
      : 'background:rgba(148,163,184,0.15); color:var(--ts-muted);';

    const requiresBadges = (docs.requires || mod.requires || []).map(req => (
      `<span style="font-size:0.7rem; background:rgba(192,132,252,0.16); color:#d8b4fe; padding:2px 8px; border-radius:999px;">Requiere: ${escapeHtmlStudio(req)}</span>`
    )).join('');

    const fullDesc = docs.fullDescription || mod.shortDescription || mod.description || 'Módulo JavaScript del framework.';

    headerEl.innerHTML = `
      <div class="jsm-modal-header-top">
        <div>
          <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <h3 id="jsmModalTitle" style="margin:0; font-size:1.12rem; font-weight:700; color:#fff;">${escapeHtmlStudio(mod.module)}</h3>
            <span style="font-size:0.72rem; font-family:monospace; color:var(--ts-muted);">${escapeHtmlStudio(mod.file)} · ${formatBytesKb(mod.sizeBytes)}</span>
          </div>
          <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
            <span style="font-size:0.7rem; background:rgba(56,189,248,0.14); color:var(--ts-primary); padding:2px 8px; border-radius:999px;">${isCore ? 'Core' : escapeHtmlStudio(mod.source)}</span>
            <span style="font-size:0.7rem; ${statusStyle} padding:2px 8px; border-radius:999px;">${escapeHtmlStudio(statusLabel)}</span>
            ${requiresBadges}
          </div>
        </div>
        <button type="button" class="jsm-modal-close" aria-label="Cerrar documentación" onclick="closeJsModuleDocsModal()">✕</button>
      </div>
      <p style="margin:0; font-size:0.83rem; color:#cbd5e1; line-height:1.5; white-space:pre-line;">${escapeHtmlStudio(fullDesc)}</p>
    `;

    const examples = Array.isArray(docs.examples) ? docs.examples : [];
    const renderCodeCard = (blockId, title, lang, rawCode) => {
      __jsDocsCodeBlocks[blockId] = String(rawCode ?? '');
      const highlighted = highlightCodeStudio(rawCode, lang);
      return `
        <div class="jsm-code-card">
          <div class="jsm-code-head">
            <div class="jsm-code-title">
              <span class="jsm-lang-badge">${escapeHtmlStudio(lang)}</span>
              <span>${escapeHtmlStudio(title)}</span>
            </div>
            <button type="button" class="jsm-copy-btn" onclick="copyJsModuleCode('${escapeHtmlStudio(blockId)}')">📋 Copiar</button>
          </div>
          <pre class="jsm-code-pre"><code>${highlighted}</code></pre>
        </div>
      `;
    };

    let examplesHtml = examples.map((ex, exIdx) => (
      renderCodeCard(`ex_${idx}_${exIdx}`, ex.title || `Ejemplo ${exIdx + 1}`, ex.lang || 'html', ex.code || '')
    )).join('');

    if (docs.jsConfigSnippet) {
      examplesHtml += renderCodeCard(`cfg_${idx}`, 'Configuración en jsConfig.json', 'json', docs.jsConfigSnippet);
    }

    if (!examplesHtml) {
      examplesHtml = '<div style="font-size:0.82rem; color:var(--ts-muted);">Este módulo no tiene ejemplos registrados en su cabecera JSDoc.</div>';
    }

    const params = Array.isArray(docs.params) ? docs.params : [];
    const attrs = Array.isArray(docs.attributes) ? docs.attributes : [];
    const cssClasses = Array.isArray(docs.cssClasses) ? docs.cssClasses : [];

    let propsHtml = '';

    if (params.length > 0) {
      propsHtml += `
        <div class="jsm-table-wrap">
          <div class="jsm-table-subtitle">⚙️ Parámetros opcionales (jsConfig.json / inicialización)</div>
          <table class="jsm-table">
            <thead>
              <tr>
                <th>Parámetro</th>
                <th>Tipo</th>
                <th>Defecto</th>
                <th>Requisito</th>
                <th>Descripción</th>
              </tr>
            </thead>
            <tbody>
              ${params.map(p => `
                <tr>
                  <td><span class="jsm-mono-pill">${escapeHtmlStudio(p.name)}</span></td>
                  <td><code style="color:#c4b5fd;">${escapeHtmlStudio(p.type || 'any')}</code></td>
                  <td>${p.default ? `<code style="color:#fde68a;">${escapeHtmlStudio(p.default)}</code>` : '<span style="color:var(--ts-muted);">—</span>'}</td>
                  <td>${p.optional ? '<span class="jsm-opt-badge">Opcional</span>' : '<span class="jsm-req-badge">Requerido</span>'}</td>
                  <td>${escapeHtmlStudio(p.description || '—')}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    }

    if (attrs.length > 0) {
      propsHtml += `
        <div class="jsm-table-wrap">
          <div class="jsm-table-subtitle">🏷️ Atributos HTML (@attribute / data-*)</div>
          <table class="jsm-table">
            <thead>
              <tr>
                <th>Atributo</th>
                <th>Uso</th>
                <th>Descripción</th>
              </tr>
            </thead>
            <tbody>
              ${attrs.map(a => `
                <tr>
                  <td><span class="jsm-mono-pill">${escapeHtmlStudio(a.name)}</span></td>
                  <td>${a.optional ? '<span class="jsm-opt-badge">Opcional</span>' : '<span class="jsm-req-badge">Configurable</span>'}</td>
                  <td>${escapeHtmlStudio(a.description || 'Atributo HTML soportado por el componente.')}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    }

    if (cssClasses.length > 0) {
      propsHtml += `
        <div class="jsm-table-wrap">
          <div class="jsm-table-subtitle">🎨 Clases y Variables CSS (@css)</div>
          <table class="jsm-table">
            <thead>
              <tr>
                <th>Selector / Token</th>
                <th>Descripción</th>
              </tr>
            </thead>
            <tbody>
              ${cssClasses.map(c => `
                <tr>
                  <td><span class="jsm-mono-pill">${escapeHtmlStudio(c.selector)}</span></td>
                  <td>${escapeHtmlStudio(c.description || '—')}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    }

    if (!propsHtml) {
      propsHtml = `
        <div style="background:rgba(30,41,59,0.5); border:1px dashed var(--ts-border); border-radius:10px; padding:14px 16px; font-size:0.82rem; color:var(--ts-muted); line-height:1.5;">
          Este módulo no requiere parámetros opcionales en <code style="color:var(--ts-primary);">jsConfig.json</code>; se activa automáticamente mediante su selector HTML/CSS o al incluirlo en la configuración.
        </div>
      `;
    }

    bodyEl.innerHTML = `
      <section>
        <h4 class="jsm-section-title">🧩 Cómo aplicar en la vista</h4>
        ${examplesHtml}
      </section>
      <section>
        <h4 class="jsm-section-title">📋 Propiedades, Atributos y Clases Opcionales</h4>
        ${propsHtml}
      </section>
    `;

    bodyEl.scrollTop = 0;
    overlay.classList.add('open');

    if (typeof window.FloatingScroll !== 'undefined' && typeof window.FloatingScroll.attach === 'function') {
      const fs = window.FloatingScroll.attach(bodyEl, {
        width: 7,
        color: 'var(--ts-primary, #38bdf8)',
        hoverExpand: 3,
        minThumbHeight: 36,
        offsetRight: 4,
        offsetY: 6,
        opacity: 0.6,
        zIndex: 100005
      });
      if (fs && typeof fs.refresh === 'function') {
        requestAnimationFrame(() => fs.refresh());
      }
    }
  }

  function closeJsModuleDocsModal() {
    const overlay = document.getElementById('modalJsModuleDocs');
    const bodyEl = document.getElementById('jsmModalBody');
    if (bodyEl && typeof window.FloatingScroll !== 'undefined' && typeof window.FloatingScroll.detach === 'function') {
      window.FloatingScroll.detach(bodyEl);
    }
    if (overlay) {
      overlay.classList.remove('open');
    }
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const overlay = document.getElementById('modalJsModuleDocs');
      if (overlay && overlay.classList.contains('open')) {
        closeJsModuleDocsModal();
      }
    }
  });

  async function loadJsModulesCatalog(forceRefresh = false) {
    if (__jsCatalogState && !forceRefresh) {
      renderJsModulesStage();
      return;
    }
    try {
      const res = await fetch('/_frame/studio/js-catalog');
      const data = await res.json();
      if (!data.success) {
        showToast('❌ ' + (data.message || 'No se pudo cargar el catálogo JS'));
        return;
      }
      __jsCatalogState = data.catalog;
      __jsUsageState = data.usage;
      const priInput = document.getElementById('jsModPriorityInput');
      const excInput = document.getElementById('jsModExcludeInput');
      if (priInput) priInput.value = (__jsCatalogState.jsConfig.priority || []).join(', ');
      if (excInput) excInput.value = (__jsCatalogState.jsConfig.exclude || []).join(', ');
      renderJsModulesStage();
    } catch (e) {
      showToast('❌ Error de red cargando módulos JS');
    }
  }

  async function detectJsUsage(notify = true) {
    try {
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const res = await fetch('/_frame/studio/js-detect', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
        },
        body: JSON.stringify({})
      });
      const data = await res.json();
      if (data.success) {
        __jsUsageState = data.usage;
        renderJsModulesStage();
        if (notify) {
          showToast(`🔍 Escaneados ${data.usage.scannedFiles} archivo(s). Ahorro potencial: ${formatBytesKb(data.usage.estimatedSavingsBytes)}`);
        }
      }
    } catch (e) {
      showToast('❌ Error al escanear el proyecto');
    }
  }

  function applyRecommendedJsModules() {
    if (!__jsCatalogState || !__jsUsageState) {
      loadJsModulesCatalog(true);
      return;
    }
    const compUsage = __jsUsageState.components || {};
    const libUsage = __jsUsageState.libraries || {};

    (__jsCatalogState.modules || []).forEach(mod => {
      if (mod.category === 'core') {
        mod.active = true;
        mod.mode = 'priority';
        return;
      }
      const u = compUsage[mod.module];
      const used = u ? !!u.used : false;
      mod.active = used;
      if (used && (mod.mode === 'inactive' || mod.mode === 'excluded')) {
        mod.mode = 'defer';
      } else if (!used) {
        mod.mode = 'inactive';
      }
    });

    (__jsCatalogState.libraries || []).forEach(lib => {
      const u = libUsage[lib.name];
      if (u) {
        lib.active = !!u.used;
      }
    });

    renderJsModulesStage();
    showToast('⚡ Recomendación aplicada. Revisa y presiona Guardar para minificar.');
  }

  function toggleJsLib(idx, checked) {
    if (__jsCatalogState && __jsCatalogState.libraries[idx]) {
      __jsCatalogState.libraries[idx].active = !!checked;
      renderJsModulesSummary();
    }
  }

  function toggleJsModActive(idx, checked) {
    if (!__jsCatalogState || !__jsCatalogState.modules[idx]) return;
    const mod = __jsCatalogState.modules[idx];
    mod.active = !!checked;
    if (checked) {
      if (mod.mode === 'inactive' || mod.mode === 'excluded') {
        mod.mode = mod.category === 'core' ? 'priority' : 'defer';
      }
    } else {
      mod.mode = 'inactive';
    }
    renderJsModulesStage();
  }

  function changeJsModMode(idx, mode) {
    if (!__jsCatalogState || !__jsCatalogState.modules[idx]) return;
    const mod = __jsCatalogState.modules[idx];
    mod.mode = mode;
    mod.active = (mode === 'defer' || mode === 'async' || mode === 'priority');
    renderJsModulesSummary();
  }

  function changeJsModArgs(idx, rawArgs) {
    if (!__jsCatalogState || !__jsCatalogState.modules[idx]) return;
    const parsed = String(rawArgs || '').split(',').map(s => s.trim()).filter(Boolean);
    __jsCatalogState.modules[idx].args = parsed;
  }

  function renderJsModulesSummary() {
    if (!__jsCatalogState) return;
    const mods = __jsCatalogState.modules || [];
    const activeMods = mods.filter(m => m.active);
    const activeBytes = activeMods.reduce((acc, m) => acc + Number(m.sizeBytes || 0), 0);
    const totalBytes = mods.reduce((acc, m) => acc + Number(m.sizeBytes || 0), 0);
    const savings = __jsUsageState ? Number(__jsUsageState.estimatedSavingsBytes || 0) : 0;

    const badge = document.getElementById('jsModulesCountBadge');
    if (badge) badge.textContent = `${activeMods.length}/${mods.length} activos`;

    const box = document.getElementById('jsModulesSummaryBox');
    if (box) {
      box.innerHTML = `
        <div><strong>Módulos activos:</strong> ${activeMods.length} de ${mods.length}</div>
        <div><strong>Fuente activa:</strong> ${formatBytesKb(activeBytes)} / ${formatBytesKb(totalBytes)}</div>
        <div><strong>Bundle actual (js.min.js):</strong> ${formatBytesKb(__jsCatalogState.stats?.minifiedBytes || 0)}</div>
        <div><strong>Ahorro sugerido:</strong> <span style="color:var(--ts-success);">${formatBytesKb(savings)}</span></div>
      `;
    }
  }

  function renderJsModulesStage() {
    if (!__jsCatalogState) return;
    renderJsModulesSummary();

    const libsGrid = document.getElementById('jsLibrariesGrid');
    const libs = __jsCatalogState.libraries || [];
    const libUsage = __jsUsageState?.libraries || {};
    if (libsGrid) {
      libsGrid.innerHTML = libs.map((lib, idx) => {
        const u = libUsage[lib.name] || { used: false, matches: 0 };
        const badgeHtml = u.used
          ? `<span style="font-size:0.7rem; background:rgba(34,197,94,0.16); color:#4ade80; padding:2px 7px; border-radius:999px;">Detectada (${u.matches})</span>`
          : `<span style="font-size:0.7rem; background:rgba(148,163,184,0.15); color:var(--ts-muted); padding:2px 7px; border-radius:999px;">Sin uso detectado</span>`;
        return `
          <div style="background:var(--ts-bg); border:1px solid ${lib.active ? 'var(--ts-primary)' : 'var(--ts-border)'}; border-radius:10px; padding:12px; display:flex; flex-direction:column; gap:8px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
              <strong style="color:#fff; font-size:0.92rem;">${escapeHtmlStudio(lib.name)}</strong>
              <label style="display:flex; align-items:center; gap:6px; font-size:0.8rem; cursor:pointer;">
                <input type="checkbox" ${lib.active ? 'checked' : ''} onchange="toggleJsLib(${idx}, this.checked)">
                <span>${lib.active ? 'Activa' : 'Inactiva'}</span>
              </label>
            </div>
            <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
              ${badgeHtml}
              <span style="font-size:0.72rem; color:var(--ts-muted);">${lib.filesCount} archivos · ${formatBytesKb(lib.sizeBytes)}</span>
            </div>
          </div>
        `;
      }).join('');
    }
    const libsBadge = document.getElementById('jsLibsBadge');
    if (libsBadge) libsBadge.textContent = `${libs.filter(l => l.active).length}/${libs.length} activas`;

    const search = (document.getElementById('jsModSearchInput')?.value || '').toLowerCase().trim();
    const filter = document.getElementById('jsModFilterSelect')?.value || 'all';
    const compUsage = __jsUsageState?.components || {};
    const modsGrid = document.getElementById('jsComponentsGrid');
    const compBadge = document.getElementById('jsComponentsBadge');

    if (modsGrid) {
      let visibleCount = 0;
      const cards = (__jsCatalogState.modules || []).map((mod, idx) => {
        const u = compUsage[mod.module] || { used: false, reason: '' };
        const shortDesc = mod.shortDescription || mod.description || '';
        if (search && !mod.module.toLowerCase().includes(search) && !mod.file.toLowerCase().includes(search) && !shortDesc.toLowerCase().includes(search)) {
          return '';
        }
        if (filter === 'active' && !mod.active) return '';
        if (filter === 'used' && !u.used) return '';
        if (filter === 'unused' && u.used) return '';
        visibleCount++;

        const isCore = mod.category === 'core';
        const docs = mod.docs || {};
        const exCount = Array.isArray(docs.examples) ? docs.examples.length : 0;
        const propsCount = (Array.isArray(docs.params) ? docs.params.length : 0)
          + (Array.isArray(docs.attributes) ? docs.attributes.length : 0)
          + (Array.isArray(docs.cssClasses) ? docs.cssClasses.length : 0);
        const docsBadge = (exCount > 0 || propsCount > 0)
          ? `<span class="jsm-docs-count">${exCount} ej · ${propsCount} props</span>`
          : '';

        const usagePill = u.used
          ? `<span style="font-size:0.68rem; background:rgba(34,197,94,0.16); color:#4ade80; padding:2px 6px; border-radius:999px;">En uso</span>`
          : `<span style="font-size:0.68rem; background:rgba(245,158,11,0.15); color:#fbbf24; padding:2px 6px; border-radius:999px;">Sin uso</span>`;
        const sourcePill = `<span style="font-size:0.68rem; background:rgba(56,189,248,0.12); color:var(--ts-primary); padding:2px 6px; border-radius:999px;">${isCore ? 'Core' : escapeHtmlStudio(mod.source)}</span>`;

        return `
          <div class="jsm-card ${mod.active ? 'is-active' : ''}">
            <div style="display:flex; flex-direction:column; gap:8px;">
              <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                <div>
                  <div style="font-weight:700; color:#fff; font-size:0.9rem;">${escapeHtmlStudio(mod.module)}</div>
                  <div style="font-size:0.72rem; color:var(--ts-muted); font-family:monospace;">${escapeHtmlStudio(mod.file)} · ${formatBytesKb(mod.sizeBytes)}</div>
                </div>
                <input type="checkbox" ${mod.active ? 'checked' : ''} ${isCore ? 'disabled title="Requerido por el núcleo"' : ''} onchange="toggleJsModActive(${idx}, this.checked)">
              </div>
              <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                ${sourcePill}
                ${usagePill}
              </div>
              <div class="jsm-desc" title="${escapeHtmlStudio(shortDesc)}">
                ${escapeHtmlStudio(shortDesc || u.reason || 'Módulo JavaScript del proyecto')}
              </div>
            </div>
            <div class="jsm-card-footer">
              ${isCore ? '' : `
              <div style="display:grid; grid-template-columns:100px 1fr; gap:6px; align-items:center;">
                <select class="ts-input-text" style="padding:4px 6px; font-size:0.74rem;" onchange="changeJsModMode(${idx}, this.value)">
                  <option value="defer" ${mod.mode === 'defer' ? 'selected' : ''}>defer</option>
                  <option value="async" ${mod.mode === 'async' ? 'selected' : ''}>async</option>
                  <option value="inactive" ${mod.mode === 'inactive' ? 'selected' : ''}>inactivo</option>
                </select>
                <input type="text" class="ts-input-text" style="padding:4px 8px; font-size:0.74rem; font-family:monospace;" placeholder="args opcionales (coma)" value="${escapeHtmlStudio((mod.args || []).join(', '))}" oninput="changeJsModArgs(${idx}, this.value)">
              </div>`}
              <button type="button" class="jsm-docs-btn" onclick="openJsModuleDocsModal(${idx})">
                <span>📖 Ejemplos y Props</span>
                ${docsBadge}
              </button>
            </div>
          </div>
        `;
      }).join('');
      modsGrid.innerHTML = cards || '<div style="color:var(--ts-muted); font-size:0.85rem;">No hay módulos que coincidan con el filtro.</div>';
      if (compBadge) compBadge.textContent = `${visibleCount} mostrados`;
    }
    if (typeof syncStudioFloatingScrolls === 'function') {
      requestAnimationFrame(syncStudioFloatingScrolls);
    }
  }

  async function saveJsModulesConfig() {
    if (!__jsCatalogState) return;
    const priRaw = document.getElementById('jsModPriorityInput')?.value || '';
    const excRaw = document.getElementById('jsModExcludeInput')?.value || '';

    const priority = priRaw.split(',').map(s => s.trim()).filter(Boolean);
    const exclude = excRaw.split(',').map(s => s.trim()).filter(Boolean);
    const defer = {};
    const asyncMods = {};

    (__jsCatalogState.modules || []).forEach(mod => {
      if (!mod.active || mod.category === 'core') return;
      if (mod.mode === 'async') {
        asyncMods[mod.module] = mod.args || [];
      } else {
        defer[mod.module] = mod.args || [];
      }
    });

    const libraries = (__jsCatalogState.libraries || []).filter(l => l.active).map(l => l.name);

    try {
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const res = await fetch('/_frame/studio/js-save', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
        },
        body: JSON.stringify({ priority, exclude, defer, async: asyncMods, libraries })
      });
      const data = await res.json();
      if (data.success) {
        showToast(`✅ ${data.message} (${formatBytesKb(data.minifiedBytes || 0)})`);
        loadJsModulesCatalog(true);
      } else {
        showToast('❌ ' + (data.message || 'No se pudo guardar'));
      }
    } catch (e) {
      showToast('❌ Error de red al guardar módulos JS');
    }
  }
</script>
