/**
 * Dropzone Universal de Archivos (.file-dropzone).
 * Transforma `<input type="file">` en un área táctil/arrastrable para documentos o múltiples archivos,
 * con validación de tamaño/cantidad, lista con insignia de extensión y botones táctiles de borrado.
 *
 * @function fileDropzone
 * @description Mejora inputs `.file-dropzone` preservando el input nativo sincronizado vía DataTransfer.
 *
 * @example
 * <input type="file" name="docs[]" multiple accept=".pdf,.zip,image/*" class="file-dropzone" data-max-files="5" data-max-size="10">
 *
 * @css .ui-dropzone-wrap, .ui-dropzone-area, .ui-dropzone-list, .ui-dropzone-item, .ui-dropzone-badge, .ui-dropzone-remove
 * @requires window.FmeUi (opcional, con respaldo integrado)
 * @returns {void}
 */
export function fileDropzone() {
  const ui = window.FmeUi || null;
  const tr = (k, p, f) => (ui ? ui.t(k, p, f) : (typeof window.t === 'function' ? window.t(k, p, f) : f));
  const icon = (name, fb) => (ui && ui.ICONS && ui.ICONS[name]) ? ui.ICONS[name] : fb;

  function formatBytes(bytes) {
    if (!Number.isFinite(bytes) || bytes <= 0) return '0 KB';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
  }

  function fileMatchesAccept(file, acceptStr) {
    if (!acceptStr || !acceptStr.trim()) return true;
    const rules = acceptStr.split(',').map((r) => r.trim().toLowerCase()).filter(Boolean);
    if (!rules.length) return true;
    const fileName = (file.name || '').toLowerCase();
    const fileType = (file.type || '').toLowerCase();
    return rules.some((rule) => {
      if (rule === '*/*') return true;
      if (rule.startsWith('.')) return fileName.endsWith(rule);
      if (rule.endsWith('/*')) return fileType.startsWith(rule.slice(0, -1));
      return fileType === rule;
    });
  }

  function syncNativeFiles(input) {
    input._isUpdating = true;
    try {
      const dt = new DataTransfer();
      (input._selectedFiles || []).forEach((f) => dt.items.add(f));
      input.files = dt.files;
    } catch (_) {
      // Algunos entornos sintéticos sin DataTransfer completo
    }
    input._isUpdating = false;
  }

  function initDropzones() {
    document.querySelectorAll('.file-dropzone:not([data-dropzone-initialized])').forEach((input) => {
      input.dataset.dropzoneInitialized = 'true';
      input._selectedFiles = [];
      input._isUpdating = false;

      const isMultiple = input.hasAttribute('multiple');
      const maxFiles = isMultiple ? (parseInt(input.getAttribute('data-max-files') || '5', 10) || 5) : 1;
      const maxSizeMb = parseFloat(input.getAttribute('data-max-size') || '10') || 10;
      const maxBytes = maxSizeMb > 0 ? maxSizeMb * 1024 * 1024 : 0;
      const acceptAttr = input.getAttribute('accept') || '';
      const hintText = input.getAttribute('placeholder') || tr('dropzone_hint', {}, 'Arrastra archivos aquí o toca para seleccionar');
      const uiTrigger = input.getAttribute('ui-trigger') || '';
      const uiSurface = input.getAttribute('ui-surface') || '';
      const uiAccent = input.getAttribute('ui-accent') || '';

      input.style.display = 'none';

      const wrap = document.createElement('div');
      wrap.className = 'ui-dropzone-wrap';
      if (ui) ui.copyUiVars(input, wrap);

      const area = document.createElement('div');
      area.className = 'ui-dropzone-area';
      area.setAttribute('role', 'button');
      area.setAttribute('tabindex', '0');
      area.setAttribute('aria-label', hintText);
      if (uiTrigger && ui) ui.applyClasses(area, uiTrigger);

      const iconHolder = document.createElement('div');
      iconHolder.innerHTML = icon(
        'upload',
        '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>'
      );
      iconHolder.style.color = 'var(--ui-accent, #3b82f6)';

      const titleEl = document.createElement('div');
      titleEl.style.fontWeight = '600';
      titleEl.style.fontSize = '14px';
      titleEl.textContent = hintText;

      const metaEl = document.createElement('div');
      metaEl.style.fontSize = '12px';
      metaEl.style.color = 'var(--ui-surface-muted, #64748b)';
      const metaParts = [];
      if (isMultiple) metaParts.push(tr('files_many', { count: maxFiles }, `Máx. ${maxFiles} archivos`));
      if (maxSizeMb > 0) metaParts.push(`${maxSizeMb} MB`);
      if (acceptAttr) metaParts.push(acceptAttr);
      metaEl.textContent = metaParts.join(' · ');

      area.appendChild(iconHolder);
      area.appendChild(titleEl);
      if (metaEl.textContent) area.appendChild(metaEl);

      const listEl = document.createElement('div');
      listEl.className = 'ui-dropzone-list';

      wrap.appendChild(area);
      wrap.appendChild(listEl);
      input.insertAdjacentElement('afterend', wrap);

      const renderList = () => {
        listEl.innerHTML = '';
        input._selectedFiles.forEach((file, index) => {
          const item = document.createElement('div');
          item.className = 'ui-dropzone-item';
          if (uiSurface && ui) ui.applyClasses(item, uiSurface);

          const left = document.createElement('div');
          left.style.display = 'flex';
          left.style.alignItems = 'center';
          left.style.gap = '10px';
          left.style.minWidth = '0';
          left.style.flex = '1';

          const extMatch = (file.name || '').match(/\.([a-zA-Z0-9]{1,6})$/);
          const badge = document.createElement('span');
          badge.className = 'ui-dropzone-badge';
          if (uiAccent && ui) ui.applyClasses(badge, uiAccent);
          badge.textContent = extMatch ? extMatch[1].toUpperCase() : 'FILE';

          const info = document.createElement('div');
          info.style.minWidth = '0';
          info.style.flex = '1';

          const nameSpan = document.createElement('div');
          nameSpan.style.fontSize = '13px';
          nameSpan.style.fontWeight = '600';
          nameSpan.style.whiteSpace = 'nowrap';
          nameSpan.style.overflow = 'hidden';
          nameSpan.style.textOverflow = 'ellipsis';
          nameSpan.textContent = file.name;

          const sizeSpan = document.createElement('div');
          sizeSpan.style.fontSize = '11px';
          sizeSpan.style.color = 'var(--ui-surface-muted, #64748b)';
          sizeSpan.textContent = formatBytes(file.size);

          info.appendChild(nameSpan);
          info.appendChild(sizeSpan);
          left.appendChild(badge);
          left.appendChild(info);

          const removeBtn = document.createElement('button');
          removeBtn.type = 'button';
          removeBtn.className = 'ui-dropzone-remove';
          removeBtn.style.minWidth = '40px';
          removeBtn.style.minHeight = '40px';
          removeBtn.innerHTML = icon(
            'close',
            '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
          );
          removeBtn.setAttribute('aria-label', `${tr('remove_item', {}, 'Eliminar')} ${file.name}`);
          removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            input._selectedFiles.splice(index, 1);
            syncNativeFiles(input);
            renderList();
            input.dispatchEvent(new Event('change', { bubbles: true }));
          });

          item.appendChild(left);
          item.appendChild(removeBtn);
          listEl.appendChild(item);
        });
      };

      const ingestFiles = (files) => {
        const list = Array.from(files || []);
        if (!list.length) return;

        if (!isMultiple) {
          const single = list[0];
          if (!fileMatchesAccept(single, acceptAttr)) return;
          if (maxBytes > 0 && single.size > maxBytes) {
            if (typeof window.toast === 'function') {
              window.toast(tr('max_size_error', { max: maxSizeMb }, `El archivo supera el límite de ${maxSizeMb} MB.`), 'warning');
            }
            return;
          }
          input._selectedFiles = [single];
        } else {
          for (const file of list) {
            if (!fileMatchesAccept(file, acceptAttr)) continue;
            if (maxFiles > 0 && input._selectedFiles.length >= maxFiles) {
              if (typeof window.toast === 'function') {
                window.toast(tr('max_files_error', { max: maxFiles }, `Máximo ${maxFiles} archivo(s) permitido(s).`), 'warning');
              }
              break;
            }
            if (maxBytes > 0 && file.size > maxBytes) {
              if (typeof window.toast === 'function') {
                window.toast(tr('max_size_error', { max: maxSizeMb }, `El archivo supera el límite de ${maxSizeMb} MB.`), 'warning');
              }
              continue;
            }
            const dup = input._selectedFiles.some(
              (f) => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified
            );
            if (!dup) input._selectedFiles.push(file);
          }
        }

        syncNativeFiles(input);
        renderList();
        input.dispatchEvent(new Event('change', { bubbles: true }));
      };

      area.addEventListener('click', () => {
        if (!input.disabled) input.click();
      });

      area.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          if (!input.disabled) input.click();
        }
      });

      ['dragenter', 'dragover'].forEach((evt) => {
        area.addEventListener(evt, (e) => {
          e.preventDefault();
          area.classList.add('is-dragover');
        });
      });

      ['dragleave', 'dragend'].forEach((evt) => {
        area.addEventListener(evt, () => {
          area.classList.remove('is-dragover');
        });
      });

      area.addEventListener('drop', (e) => {
        e.preventDefault();
        area.classList.remove('is-dragover');
        if (e.dataTransfer && e.dataTransfer.files) {
          ingestFiles(e.dataTransfer.files);
        }
      });

      input.addEventListener('change', () => {
        if (input._isUpdating) return;
        ingestFiles(input.files);
      });

      input.addEventListener('fme:renderInlinePreview', (ev) => {
        const host = ev.detail?.host;
        if (!host) return;
        const demoList = document.createElement('div');
        demoList.className = 'ui-dropzone-list is-inline-preview';
        demoList.style.marginTop = '0';
        demoList.style.maxWidth = '360px';
        if (ui) ui.copyUiVars(input, demoList);

        const samples = [
          { ext: 'PDF', name: 'contrato-servicio.pdf', size: '1.42 MB' },
          { ext: 'PNG', name: 'comprobante-pago.png', size: '420.5 KB' }
        ];
        samples.forEach((smp) => {
          const item = document.createElement('div');
          item.className = 'ui-dropzone-item';
          if (uiSurface && ui) ui.applyClasses(item, uiSurface);

          const left = document.createElement('div');
          left.style.display = 'flex';
          left.style.alignItems = 'center';
          left.style.gap = '10px';
          left.style.minWidth = '0';
          left.style.flex = '1';

          const badge = document.createElement('span');
          badge.className = 'ui-dropzone-badge';
          if (uiAccent && ui) ui.applyClasses(badge, uiAccent);
          badge.textContent = smp.ext;

          const info = document.createElement('div');
          info.style.minWidth = '0';
          info.style.flex = '1';
          info.innerHTML = `<div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${smp.name}</div><div style="font-size:11px;color:var(--ui-surface-muted, #64748b);">${smp.size}</div>`;

          left.appendChild(badge);
          left.appendChild(info);

          const remBtn = document.createElement('button');
          remBtn.type = 'button';
          remBtn.className = 'ui-dropzone-remove';
          remBtn.style.minWidth = '40px';
          remBtn.style.minHeight = '40px';
          remBtn.innerHTML = icon('close', '✕');
          remBtn.setAttribute('aria-label', `${tr('remove_item', {}, 'Eliminar')} ${smp.name}`);

          item.appendChild(left);
          item.appendChild(remBtn);
          demoList.appendChild(item);
        });

        host.innerHTML = '';
        host.appendChild(demoList);
      });
    });
  }

  if (window.__fmeDropzoneBound) {
    initDropzones();
    return;
  }
  window.__fmeDropzoneBound = true;

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initDropzones);
  } else {
    initDropzones();
    document.addEventListener('previewUpdated', initDropzones);
  }
}
