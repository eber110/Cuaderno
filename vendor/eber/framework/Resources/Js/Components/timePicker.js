/**
 * Selector de Hora Táctil (12h/24h, pasos configurables, Bottom-Sheet en móvil y tokens --ui-*).
 *
 * @function timePicker
 * @returns {void}
 */
export function timePicker() {
  if (window.__fmeTimePickerBound) return;
  window.__fmeTimePickerBound = true;

  const ui = window.FmeUi || null;
  const tr = (k, p, f) => (ui ? ui.t(k, p, f) : (typeof window.t === 'function' ? window.t(k, p, f) : f));
  const icon = (name, fb) => (ui && ui.ICONS && ui.ICONS[name]) ? ui.ICONS[name] : fb;
  const pad2 = (n) => String(n).padStart(2, '0');

  let activePopover = null;
  let activeBackdrop = null;
  let activeInput = null;

  function closePopover() {
    if (!activePopover) return;
    if (activePopover.parentNode) activePopover.parentNode.removeChild(activePopover);
    if (activeBackdrop && activeBackdrop.parentNode) activeBackdrop.parentNode.removeChild(activeBackdrop);
    activePopover = null;
    activeBackdrop = null;
    activeInput = null;
  }

  function openPopover(input, displayInput, options = {}) {
    const inlineHost = options.inlineHost || null;
    const isInline = !!inlineHost;

    if (!isInline && activePopover && activeInput === input) {
      closePopover();
      return;
    }
    if (!isInline) {
      closePopover();
      activeInput = input;
    }

    const is12h = (input.getAttribute('data-format') || '24h').toLowerCase() === '12h';
    const step = Math.max(1, Math.min(30, parseInt(input.getAttribute('data-step') || '15', 10) || 15));

    const parts = (input.value || '09:00').split(':');
    let selHour24 = Math.max(0, Math.min(23, parseInt(parts[0], 10) || 9));
    let selMin = Math.max(0, Math.min(59, parseInt(parts[1], 10) || 0));
    let selAmPm = selHour24 >= 12 ? 'PM' : 'AM';

    const popover = document.createElement('div');
    popover.className = isInline ? 'ui-dp-popover ui-popover is-inline-preview' : 'ui-dp-popover ui-popover';
    popover.style.width = '248px';
    if (ui) ui.copyUiVars(input, popover);
    const uiSurface = input.getAttribute('ui-surface') || '';
    if (uiSurface && ui) ui.applyClasses(popover, uiSurface);

    popover.addEventListener('click', (e) => e.stopPropagation());
    popover.addEventListener('pointerdown', (e) => e.stopPropagation());
    if (ui) popover.appendChild(ui.createSheetHandle());

    const colsWrap = document.createElement('div');
    colsWrap.className = `ui-tp-columns ${is12h ? 'has-ampm' : ''}`;

    const hrCol = document.createElement('div');
    hrCol.className = 'ui-tp-col';
    const minCol = document.createElement('div');
    minCol.className = 'ui-tp-col';
    const ampmCol = is12h ? document.createElement('div') : null;
    if (ampmCol) ampmCol.className = 'ui-tp-col';

    const syncButtons = () => {
      const displayHour12 = selHour24 % 12 === 0 ? 12 : selHour24 % 12;
      hrCol.querySelectorAll('.ui-tp-item').forEach((btn) => {
        const val = parseInt(btn.dataset.val, 10);
        btn.classList.toggle('active', is12h ? val === displayHour12 : val === selHour24);
      });
      minCol.querySelectorAll('.ui-tp-item').forEach((btn) => {
        btn.classList.toggle('active', parseInt(btn.dataset.val, 10) === selMin);
      });
      if (ampmCol) {
        ampmCol.querySelectorAll('.ui-tp-item').forEach((btn) => {
          btn.classList.toggle('active', btn.dataset.val === selAmPm);
        });
      }
    };

    const hours = is12h ? Array.from({ length: 12 }, (_, i) => i + 1) : Array.from({ length: 24 }, (_, i) => i);
    hours.forEach((h) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ui-tp-item';
      btn.dataset.val = String(h);
      btn.textContent = pad2(h);
      btn.addEventListener('click', () => {
        if (is12h) {
          selHour24 = selAmPm === 'PM' ? (h === 12 ? 12 : h + 12) : (h === 12 ? 0 : h);
        } else {
          selHour24 = h;
        }
        syncButtons();
      });
      hrCol.appendChild(btn);
    });

    for (let m = 0; m < 60; m += step) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ui-tp-item';
      btn.dataset.val = String(m);
      btn.textContent = pad2(m);
      btn.addEventListener('click', () => {
        selMin = m;
        syncButtons();
      });
      minCol.appendChild(btn);
    }

    colsWrap.appendChild(hrCol);
    colsWrap.appendChild(minCol);

    if (ampmCol) {
      ['AM', 'PM'].forEach((ap) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ui-tp-item';
        btn.dataset.val = ap;
        btn.textContent = ap;
        btn.addEventListener('click', () => {
          selAmPm = ap;
          if (ap === 'PM' && selHour24 < 12) selHour24 += 12;
          if (ap === 'AM' && selHour24 >= 12) selHour24 -= 12;
          syncButtons();
        });
        ampmCol.appendChild(btn);
      });
      colsWrap.appendChild(ampmCol);
    }

    popover.appendChild(colsWrap);

    const footer = document.createElement('div');
    footer.className = 'ui-dp-footer';

    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.className = 'ui-btn ui-btn-secondary';
    clearBtn.style.minHeight = '36px';
    clearBtn.style.padding = '6px 12px';
    clearBtn.textContent = tr('clear', {}, 'Limpiar');
    const customSec = input.getAttribute('ui-btn-secondary') || '';
    if (customSec && ui) ui.applyClasses(clearBtn, customSec);
    clearBtn.addEventListener('click', () => {
      input.value = '';
      displayInput.value = '';
      input.dispatchEvent(new Event('change', { bubbles: true }));
      if (!isInline) closePopover();
    });

    const acceptBtn = document.createElement('button');
    acceptBtn.type = 'button';
    acceptBtn.className = 'ui-btn ui-btn-primary';
    acceptBtn.style.minHeight = '36px';
    acceptBtn.style.padding = '6px 12px';
    acceptBtn.textContent = tr('accept', {}, 'Aceptar');
    const customPrim = input.getAttribute('ui-btn-primary') || '';
    if (customPrim && ui) ui.applyClasses(acceptBtn, customPrim);
    acceptBtn.addEventListener('click', () => {
      const isoTime = `${pad2(selHour24)}:${pad2(selMin)}`;
      const h12 = selHour24 % 12 === 0 ? 12 : selHour24 % 12;
      const disp = is12h ? `${pad2(h12)}:${pad2(selMin)} ${selAmPm}` : isoTime;
      input.value = isoTime;
      displayInput.value = disp;
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      if (!isInline) closePopover();
    });

    footer.appendChild(clearBtn);
    footer.appendChild(acceptBtn);
    popover.appendChild(footer);

    syncButtons();
    if (isInline) {
      inlineHost.innerHTML = '';
      inlineHost.appendChild(popover);
      return;
    }

    document.body.appendChild(popover);

    if (ui && typeof ui.positionFloating === 'function') {
      const pos = ui.positionFloating(popover, displayInput, {
        sourceEl: input,
        fallbackWidth: 248,
        fallbackHeight: 280,
        onBackdropClick: closePopover
      });
      activeBackdrop = pos.backdrop;
    } else {
      const rect = displayInput.getBoundingClientRect();
      popover.style.top = `${rect.bottom + 8}px`;
      popover.style.left = `${rect.left}px`;
    }

    activePopover = popover;
  }

  function initTimePickers() {
    document.querySelectorAll('.time-picker:not([data-tp-initialized])').forEach((input) => {
      input.dataset.tpInitialized = 'true';
      if (input.type === 'time') input.type = 'text';
      input.style.display = 'none';

      const wrap = document.createElement('div');
      wrap.className = 'ui-dp-trigger-wrap';
      if (ui) ui.copyUiVars(input, wrap);

      const displayInput = document.createElement('input');
      displayInput.type = 'text';
      displayInput.readOnly = true;
      displayInput.className = 'ui-dp-input-display';
      displayInput.placeholder = input.getAttribute('placeholder') || tr('select_time', {}, 'Seleccionar hora');
      displayInput.value = input.value || '';

      const uiTrigger = input.getAttribute('ui-trigger') || '';
      if (uiTrigger && ui) ui.applyClasses(displayInput, uiTrigger);

      const clockBtn = document.createElement('button');
      clockBtn.type = 'button';
      clockBtn.className = 'ui-dp-trigger-btn';
      clockBtn.setAttribute('aria-label', tr('select_time', {}, 'Seleccionar hora'));
      clockBtn.innerHTML = icon('clock', '🕒');

      input.insertAdjacentElement('beforebegin', wrap);
      wrap.appendChild(displayInput);
      wrap.appendChild(clockBtn);

      const triggerOpen = (e) => {
        e.preventDefault();
        e.stopPropagation();
        openPopover(input, displayInput);
      };
      displayInput.addEventListener('click', triggerOpen);
      clockBtn.addEventListener('click', triggerOpen);
      input.addEventListener('fme:renderInlinePreview', (ev) => {
        if (ev.detail?.host) openPopover(input, displayInput, { inlineHost: ev.detail.host });
      });
    });
  }

  document.addEventListener('click', (e) => {
    if (activePopover && !e.target.closest('.ui-dp-popover') && !e.target.closest('.ui-dp-trigger-wrap')) {
      closePopover();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && activePopover) closePopover();
  });

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initTimePickers);
  } else {
    initTimePickers();
    document.addEventListener('previewUpdated', initTimePickers);
  }
}
