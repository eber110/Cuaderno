/**
 * Input de Etiquetas / Chips Interactivos (.tags-input).
 * Convierte un input de texto en un campo multi-etiqueta con píldoras táctiles,
 * atajos de teclado (Enter, coma, Backspace) y límite configurable.
 *
 * @function tagsInput
 * @description Mejora inputs `.tags-input` sincronizando valores separados por delimitador
 *              en el input original para envío estándar de formularios.
 *
 * @example
 * <input type="text" name="tags" class="tags-input" value="PHP,JS" data-max-tags="8" data-separator=",">
 *
 * @css .ui-tags-box, .ui-tag-chip, .ui-tag-remove, .ui-tags-entry
 * @requires window.FmeUi (opcional, con respaldo integrado)
 * @returns {void}
 */
export function tagsInput() {
  const ui = window.FmeUi || null;
  const tr = (k, p, f) => (ui ? ui.t(k, p, f) : (typeof window.t === 'function' ? window.t(k, p, f) : f));
  const iconClose = (ui && ui.ICONS && ui.ICONS.close)
    ? ui.ICONS.close
    : '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

  function initTagsInputs() {
    document.querySelectorAll('.tags-input:not([data-tags-initialized])').forEach((input) => {
      input.dataset.tagsInitialized = 'true';

      const separator = input.getAttribute('data-separator') || ',';
      const maxTagsRaw = parseInt(input.getAttribute('data-max-tags') || '10', 10);
      const maxTags = Number.isFinite(maxTagsRaw) && maxTagsRaw > 0 ? maxTagsRaw : 0;
      const isDisabled = input.disabled || input.readOnly;
      const placeholder = input.getAttribute('placeholder') || tr('add_tag', {}, 'Añadir etiqueta...');
      const uiTrigger = input.getAttribute('ui-trigger') || '';
      const uiAccent = input.getAttribute('ui-accent') || '';

      input.style.display = 'none';

      const box = document.createElement('div');
      box.className = 'ui-tags-box';
      if (ui) {
        ui.copyUiVars(input, box);
        if (uiTrigger) ui.applyClasses(box, uiTrigger);
      }

      const entry = document.createElement('input');
      entry.type = 'text';
      entry.className = 'ui-tags-entry';
      entry.placeholder = placeholder;
      entry.disabled = isDisabled;
      entry.setAttribute('autocomplete', 'off');
      entry.setAttribute('aria-label', placeholder);

      box.appendChild(entry);
      input.insertAdjacentElement('afterend', box);

      let tags = [];

      const parseInitial = (raw) => {
        const list = [];
        String(raw || '').split(separator).forEach((part) => {
          const clean = part.trim();
          if (!clean) return;
          if (!list.some((t) => t.toLowerCase() === clean.toLowerCase())) {
            if (maxTags === 0 || list.length < maxTags) {
              list.push(clean);
            }
          }
        });
        return list;
      };

      const syncInput = (emit = true) => {
        input.value = tags.join(separator);
        if (emit) {
          input.dispatchEvent(new Event('input', { bubbles: true }));
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      };

      const renderChips = () => {
        box.querySelectorAll('.ui-tag-chip').forEach((chip) => chip.remove());

        tags.forEach((tag, index) => {
          const chip = document.createElement('span');
          chip.className = 'ui-tag-chip';
          if (uiAccent && ui) ui.applyClasses(chip, uiAccent);

          const labelSpan = document.createElement('span');
          labelSpan.textContent = tag;
          chip.appendChild(labelSpan);

          if (!isDisabled) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'ui-tag-remove';
            removeBtn.style.minWidth = '24px';
            removeBtn.style.minHeight = '24px';
            removeBtn.innerHTML = iconClose;
            removeBtn.setAttribute('aria-label', `${tr('remove_item', {}, 'Eliminar')} ${tag}`);
            removeBtn.addEventListener('click', (e) => {
              e.stopPropagation();
              tags.splice(index, 1);
              renderChips();
              syncInput(true);
              entry.focus();
            });
            chip.appendChild(removeBtn);
          }

          box.insertBefore(chip, entry);
        });

        const atLimit = maxTags > 0 && tags.length >= maxTags;
        entry.style.display = (isDisabled || atLimit) ? 'none' : '';
        if (ui && typeof ui.attachFloatingScroll === 'function') {
          ui.attachFloatingScroll(box, input);
        }
      };

      const addTagFromRaw = (rawText) => {
        if (isDisabled) return;
        const candidates = String(rawText || '').split(separator);
        let added = false;

        for (const cand of candidates) {
          const clean = cand.trim();
          if (!clean) continue;
          if (tags.some((t) => t.toLowerCase() === clean.toLowerCase())) continue;
          if (maxTags > 0 && tags.length >= maxTags) {
            if (typeof window.toast === 'function') {
              window.toast(tr('max_tags_error', { max: maxTags }, `Máximo ${maxTags} etiqueta(s).`), 'warning');
            }
            break;
          }
          tags.push(clean);
          added = true;
        }

        if (added) {
          renderChips();
          syncInput(true);
        }
      };

      tags = parseInitial(input.value);
      renderChips();
      syncInput(false);

      box.addEventListener('click', () => {
        if (!isDisabled && entry.style.display !== 'none') {
          entry.focus();
        }
      });

      entry.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === separator) {
          if (entry.value.trim() !== '') {
            e.preventDefault();
            addTagFromRaw(entry.value);
            entry.value = '';
          } else if (e.key === separator) {
            e.preventDefault();
          }
        } else if (e.key === 'Backspace' && entry.value === '' && tags.length > 0) {
          tags.pop();
          renderChips();
          syncInput(true);
        }
      });

      entry.addEventListener('input', () => {
        if (separator && entry.value.includes(separator)) {
          addTagFromRaw(entry.value);
          entry.value = '';
        }
      });

      entry.addEventListener('paste', (e) => {
        const pasted = (e.clipboardData || window.clipboardData)?.getData('text') || '';
        if (pasted && pasted.includes(separator)) {
          e.preventDefault();
          addTagFromRaw(pasted);
          entry.value = '';
        }
      });

      entry.addEventListener('blur', () => {
        if (entry.value.trim() !== '') {
          addTagFromRaw(entry.value);
          entry.value = '';
        }
      });
    });
  }

  if (window.__fmeTagsBound) {
    initTagsInputs();
    return;
  }
  window.__fmeTagsBound = true;

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initTagsInputs);
  } else {
    initTagsInputs();
    document.addEventListener('previewUpdated', initTagsInputs);
  }
}
