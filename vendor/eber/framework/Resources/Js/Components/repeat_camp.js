/**
 * Campos de formulario repetibles multi-instancia.
 * Soporta múltiples grupos en la misma página (`.repeat-group` o `#create-repeat`),
 * botón de eliminar fila, límite por `data-max` / parámetro, tokens CSS (--ui-*) y áreas táctiles de 40px+.
 *
 * @function repeat_camp
 * @param {number|string} [limit=10] - Límite máximo por defecto si no se define `data-max`
 * @returns {void}
 */
export function repeat_camp(limit = 10) {
  const ui = window.FmeUi || null;
  const tr = (k, p, f) => (ui ? ui.t(k, p, f) : (typeof window.t === 'function' ? window.t(k, p, f) : f));
  const icon = (name, fb) => (ui && ui.ICONS && ui.ICONS[name]) ? ui.ICONS[name] : fb;
  const defaultLimit = typeof limit === 'string' ? (parseInt(limit, 10) || 10) : (limit || 10);

  function initRepeatGroups() {
    const legacyRoots = Array.from(document.querySelectorAll('#create-repeat'));
    const modernRoots = Array.from(document.querySelectorAll('.repeat-group'));
    const allRoots = Array.from(new Set([...legacyRoots, ...modernRoots]));

    allRoots.forEach((rootEl) => {
      if (rootEl.dataset.repeatInitialized === 'true') return;
      const firstInput = rootEl.querySelector('.repeat-camp');
      if (!firstInput) return;

      rootEl.dataset.repeatInitialized = 'true';
      const maxLimit = parseInt(firstInput.getAttribute('data-max') || '', 10) || defaultLimit;
      const customAddLabel = firstInput.getAttribute('data-add-label') || '';
      const addVariant = (firstInput.getAttribute('data-add-variant') || '').toLowerCase();
      const uiRemoveCls = firstInput.getAttribute('ui-btn-remove') || '';

      const container = document.createElement('div');
      container.className = 'repeat-container';
      if (ui) {
        ui.copyUiVars(rootEl, container);
        ui.copyUiVars(firstInput, container);
      }

      const uiSurface = firstInput.getAttribute('ui-surface') || '';
      if (uiSurface && ui) ui.applyClasses(container, uiSurface);

      const addBtn = document.createElement('button');
      addBtn.type = 'button';
      const uiSecBtn = firstInput.getAttribute('ui-btn-secondary') || '';
      const uiAdd = firstInput.getAttribute('ui-btn-primary') || firstInput.getAttribute('ui-btn-add') || '';
      let useSecondaryAdd = uiSecBtn && !uiAdd;
      if (addVariant === 'secondary') useSecondaryAdd = true;
      else if (addVariant === 'primary') useSecondaryAdd = false;

      addBtn.className = useSecondaryAdd
        ? 'repeat-btn-add ui-btn ui-btn-secondary'
        : 'repeat-btn-add ui-btn ui-btn-primary';
      if (useSecondaryAdd && uiSecBtn && ui) ui.applyClasses(addBtn, uiSecBtn);
      else if (!useSecondaryAdd && uiAdd && ui) ui.applyClasses(addBtn, uiAdd);

      const rowsWrap = document.createElement('div');
      rowsWrap.className = 'repeat-rows-wrap';
      if (ui) {
        ui.copyUiVars(rootEl, rowsWrap);
        ui.copyUiVars(firstInput, rowsWrap);
      }

      const originalId = firstInput.getAttribute('id') || '';
      const templateInput = firstInput.cloneNode(true);
      templateInput.removeAttribute('id');
      templateInput.classList.remove('repeat-camp');
      templateInput.classList.add('repeat-camp-input');
      const uiTrigger = firstInput.getAttribute('ui-trigger') || '';
      if (uiTrigger && ui) ui.applyClasses(templateInput, uiTrigger);
      if (!templateInput.value) templateInput.value = '';

      firstInput.remove();

      const updateCounterText = () => {
        const currentCount = rowsWrap.querySelectorAll('.repeat-row').length;
        const remaining = Math.max(0, maxLimit - currentCount);
        addBtn.disabled = remaining === 0;
        addBtn.textContent = customAddLabel
          ? `${customAddLabel} (${remaining})`
          : tr('add_option', { remaining }, `+ Agrega otra opción (${remaining})`);
      };

      const createRow = (isFirst = false, initialVal = '') => {
        const row = document.createElement('div');
        row.className = 'repeat-row';

        const inp = templateInput.cloneNode(true);
        const idx = rowsWrap.querySelectorAll('.repeat-row').length + 1;
        if (!isFirst) {
          inp.removeAttribute('required');
          inp.value = '';
          inp.placeholder = tr('option_n', { n: idx }, `Opción ${idx}`);
        } else {
          if (originalId) inp.id = originalId;
          if (initialVal) inp.value = initialVal;
        }

        row.appendChild(inp);

        if (!isFirst) {
          const remBtn = document.createElement('button');
          remBtn.type = 'button';
          remBtn.className = 'repeat-btn-remove';
          if (uiRemoveCls && ui) ui.applyClasses(remBtn, uiRemoveCls);
          remBtn.setAttribute('aria-label', tr('remove_item', {}, 'Eliminar'));
          remBtn.innerHTML = icon('close', '✕');
          remBtn.addEventListener('click', () => {
            row.remove();
            updateCounterText();
            if (rowsWrap.__floatingScrollInstance) rowsWrap.__floatingScrollInstance.refresh();
            rowsWrap.dispatchEvent(new Event('change', { bubbles: true }));
          });
          row.appendChild(remBtn);
        }

        return row;
      };

      rowsWrap.appendChild(createRow(true, templateInput.value));
      container.appendChild(addBtn);
      container.appendChild(rowsWrap);
      rootEl.appendChild(container);
      updateCounterText();

      if (ui && typeof ui.attachFloatingScroll === 'function') {
        ui.attachFloatingScroll(rowsWrap, container);
      }

      addBtn.addEventListener('click', () => {
        const currentCount = rowsWrap.querySelectorAll('.repeat-row').length;
        if (currentCount >= maxLimit) return;
        const newRow = createRow(false);
        rowsWrap.appendChild(newRow);
        updateCounterText();
        if (ui && typeof ui.attachFloatingScroll === 'function') {
          ui.attachFloatingScroll(rowsWrap, container);
        }
        const focusInp = newRow.querySelector('input');
        if (focusInp) focusInp.focus();
      });
    });
  }

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initRepeatGroups);
  } else {
    initRepeatGroups();
    document.addEventListener('previewUpdated', initRepeatGroups);
  }
}
