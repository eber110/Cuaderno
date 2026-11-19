/**
 * Selector de Fechas Interactivo (Fecha única, Rango de fechas, Hora opcional y Presets rápidos).
 * Soporta swipe horizontal táctil entre meses, selector rápido de mes, Bottom-Sheet en móvil,
 * tokens CSS (--ui-*) y modo oscuro nativo.
 *
 * @function datePicker
 * @returns {void}
 */
export function datePicker() {
  if (window.__fmeDatePickerBound) return;
  window.__fmeDatePickerBound = true;

  const ui = window.FmeUi || null;
  const tr = (k, p, f) => (ui ? ui.t(k, p, f) : (typeof window.t === 'function' ? window.t(k, p, f) : f));
  const icon = (name, fb) => (ui && ui.ICONS && ui.ICONS[name]) ? ui.ICONS[name] : fb;

  const monthsList = () => tr(
    'months',
    {},
    'Enero,Febrero,Marzo,Abril,Mayo,Junio,Julio,Agosto,Septiembre,Octubre,Noviembre,Diciembre'
  ).split(',');
  const weekdaysList = () => tr('weekdays_short', {}, 'Lu,Ma,Mi,Ju,Vi,Sá,Do').split(',');

  function pad2(n) {
    return String(n).padStart(2, '0');
  }

  function toIsoDate(d) {
    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
  }

  function parseIsoDate(str) {
    const m = String(str || '').trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!m) return null;
    return new Date(parseInt(m[1], 10), parseInt(m[2], 10) - 1, parseInt(m[3], 10));
  }

  function formatDisplay(isoStr, format, timeStr = '') {
    if (!isoStr) return '';
    const d = parseIsoDate(isoStr);
    if (!d) return isoStr;
    const yyyy = d.getFullYear();
    const mm = pad2(d.getMonth() + 1);
    const dd = pad2(d.getDate());
    let base = isoStr;
    if (format === 'DD/MM/YYYY') base = `${dd}/${mm}/${yyyy}`;
    else if (format === 'MM-DD') base = `${mm}-${dd}`;
    else base = `${yyyy}-${mm}-${dd}`;
    return timeStr ? `${base} ${timeStr}` : base;
  }

  let activePopover = null;
  let activeBackdrop = null;
  let activeInput = null;
  let activeDisplay = null;

  function closePopover() {
    if (!activePopover) return;
    const pop = activePopover;
    const bd = activeBackdrop;
    activePopover = null;
    activeBackdrop = null;
    activeInput = null;
    activeDisplay = null;
    if (ui && typeof ui.detachFloatingScroll === 'function') {
      ui.detachFloatingScroll(pop);
    }
    if (pop.parentNode) pop.parentNode.removeChild(pop);
    if (bd && bd.parentNode) bd.parentNode.removeChild(bd);
  }

  function openPopover(input, displayEl, options = {}) {
    const inlineHost = options.inlineHost || null;
    const isInline = !!inlineHost;

    if (!isInline && activePopover && activeInput === input) {
      closePopover();
      return;
    }
    if (!isInline) {
      closePopover();
      activeInput = input;
      activeDisplay = displayEl;
    }

    const mode = (input.getAttribute('data-mode') || 'single').toLowerCase();
    const format = input.getAttribute('data-format') || 'YYYY-MM-DD';
    const showTime = input.getAttribute('data-time') === 'true';
    const showPresets = input.getAttribute('data-presets') === 'true';
    const minStr = input.getAttribute('min') || '';
    const maxStr = input.getAttribute('max') || '';

    let startIso = '';
    let endIso = '';
    let timePart = '12:00';

    const rawVal = (input.value || '').trim();
    if (mode === 'range' && rawVal.includes(' - ')) {
      const parts = rawVal.split(' - ');
      startIso = parts[0].trim().slice(0, 10);
      endIso = parts[1].trim().slice(0, 10);
    } else if (rawVal) {
      startIso = rawVal.slice(0, 10);
      if (showTime && rawVal.includes(' ')) {
        timePart = rawVal.split(' ')[1].slice(0, 5) || '12:00';
      }
    }

    const baseDate = parseIsoDate(startIso) || new Date();
    let viewYear = baseDate.getFullYear();
    let viewMonth = baseDate.getMonth();
    let pickingEnd = false;
    let monthSelectView = false;

    const popover = document.createElement('div');
    popover.className = isInline ? 'ui-dp-popover ui-popover is-inline-preview' : 'ui-dp-popover ui-popover';
    if (ui) ui.copyUiVars(input, popover);

    const uiSurface = input.getAttribute('ui-surface') || '';
    if (uiSurface && ui) ui.applyClasses(popover, uiSurface);

    popover.addEventListener('click', (e) => e.stopPropagation());
    popover.addEventListener('pointerdown', (e) => e.stopPropagation());
    if (ui) popover.appendChild(ui.createSheetHandle());

    const commitValue = (shouldClose = true) => {
      let outVal = '';
      let dispVal = '';
      if (mode === 'range') {
        if (startIso && endIso) {
          if (startIso > endIso) [startIso, endIso] = [endIso, startIso];
          outVal = `${startIso} - ${endIso}`;
          dispVal = `${formatDisplay(startIso, format)} - ${formatDisplay(endIso, format)}`;
        } else if (startIso) {
          outVal = startIso;
          dispVal = formatDisplay(startIso, format);
        }
      } else if (startIso) {
        outVal = showTime ? `${startIso} ${timePart}` : startIso;
        dispVal = formatDisplay(startIso, format, showTime ? timePart : '');
      }

      input.value = outVal;
      displayEl.value = dispVal;
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      if (isInline && typeof renderBody === 'function') renderBody();
      if (shouldClose && !isInline) closePopover();
    };

    // Cabecera
    const header = document.createElement('div');
    header.className = 'ui-dp-header';

    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'ui-dp-nav-btn';
    prevBtn.innerHTML = icon('chevronLeft', '‹');

    const titleBtn = document.createElement('button');
    titleBtn.type = 'button';
    titleBtn.className = 'ui-dp-title-btn';

    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'ui-dp-nav-btn';
    nextBtn.innerHTML = icon('chevronRight', '›');

    header.appendChild(prevBtn);
    header.appendChild(titleBtn);
    header.appendChild(nextBtn);
    popover.appendChild(header);

    // Presets opcionales
    if (showPresets) {
      const presetsWrap = document.createElement('div');
      presetsWrap.className = 'ui-dp-presets';
      const today = new Date();
      const yesterday = new Date(today);
      yesterday.setDate(today.getDate() - 1);
      const last7 = new Date(today);
      last7.setDate(today.getDate() - 6);
      const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);

      const presetItems = [
        { label: tr('preset_today', {}, 'Hoy'), s: toIsoDate(today), e: toIsoDate(today) },
        { label: tr('preset_yesterday', {}, 'Ayer'), s: toIsoDate(yesterday), e: toIsoDate(yesterday) },
        ...(mode === 'range'
          ? [
              { label: tr('preset_last_7', {}, 'Últimos 7 días'), s: toIsoDate(last7), e: toIsoDate(today) },
              { label: tr('preset_this_month', {}, 'Este mes'), s: toIsoDate(monthStart), e: toIsoDate(today) }
            ]
          : [])
      ];

      presetItems.forEach((p) => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'ui-dp-chip';
        chip.textContent = p.label;
        chip.addEventListener('click', () => {
          startIso = p.s;
          endIso = mode === 'range' ? p.e : '';
          commitValue(true);
        });
        presetsWrap.appendChild(chip);
      });
      popover.appendChild(presetsWrap);
    }

    const bodyContainer = document.createElement('div');
    popover.appendChild(bodyContainer);

    // Hora opcional integrada
    if (showTime) {
      const timeRow = document.createElement('div');
      timeRow.className = 'ui-dp-time-row flex row-direction center-between gap10';
      timeRow.style.paddingTop = '6px';

      const timeLbl = document.createElement('span');
      timeLbl.style.fontSize = 'calc(var(--ui-font-size, 14px) * 0.85)';
      timeLbl.style.fontWeight = '600';
      timeLbl.textContent = tr('select_time', {}, 'Hora');

      const timeInput = document.createElement('input');
      timeInput.type = 'time';
      timeInput.value = timePart;
      timeInput.className = 'ui-hex-input';
      timeInput.style.maxWidth = '125px';
      timeInput.addEventListener('input', () => {
        timePart = timeInput.value || '12:00';
      });

      timeRow.appendChild(timeLbl);
      timeRow.appendChild(timeInput);
      popover.appendChild(timeRow);
    }

    // Footer con acciones claras
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
      startIso = '';
      endIso = '';
      commitValue(true);
    });

    const todayBtn = document.createElement('button');
    todayBtn.type = 'button';
    todayBtn.className = 'ui-btn ui-btn-primary';
    todayBtn.style.minHeight = '36px';
    todayBtn.style.padding = '6px 12px';
    todayBtn.textContent = (showTime || mode === 'range') ? tr('accept', {}, 'Aceptar') : tr('today', {}, 'Hoy');
    const customPrim = input.getAttribute('ui-btn-primary') || '';
    if (customPrim && ui) ui.applyClasses(todayBtn, customPrim);
    todayBtn.addEventListener('click', () => {
      if (showTime || mode === 'range') {
        if (!startIso) startIso = toIsoDate(new Date());
        commitValue(true);
      } else {
        startIso = toIsoDate(new Date());
        commitValue(true);
      }
    });

    footer.appendChild(clearBtn);
    footer.appendChild(todayBtn);
    popover.appendChild(footer);

    function renderBody() {
      const mNames = monthsList();
      titleBtn.textContent = monthSelectView ? `${viewYear}` : `${mNames[viewMonth] || ''} ${viewYear}`;
      bodyContainer.innerHTML = '';

      if (monthSelectView) {
        const mGrid = document.createElement('div');
        mGrid.className = 'ui-dp-months-grid';
        mNames.forEach((mLabel, mIdx) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = `ui-dp-day ${mIdx === viewMonth ? 'selected' : ''}`;
          btn.textContent = mLabel.slice(0, 3);
          btn.addEventListener('click', () => {
            viewMonth = mIdx;
            monthSelectView = false;
            renderBody();
          });
          mGrid.appendChild(btn);
        });
        bodyContainer.appendChild(mGrid);
        return;
      }

      const wdRow = document.createElement('div');
      wdRow.className = 'ui-dp-weekdays';
      weekdaysList().forEach((wd) => {
        const sp = document.createElement('span');
        sp.className = 'ui-dp-weekday';
        sp.textContent = wd.trim();
        wdRow.appendChild(sp);
      });
      bodyContainer.appendChild(wdRow);

      const daysGrid = document.createElement('div');
      daysGrid.className = 'ui-dp-days';

      const firstDay = new Date(viewYear, viewMonth, 1).getDay();
      const offset = firstDay === 0 ? 6 : firstDay - 1;
      const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
      const todayIso = toIsoDate(new Date());

      for (let i = 0; i < offset; i++) {
        const empty = document.createElement('div');
        empty.className = 'ui-dp-day empty';
        daysGrid.appendChild(empty);
      }

      for (let d = 1; d <= daysInMonth; d++) {
        const iso = `${viewYear}-${pad2(viewMonth + 1)}-${pad2(d)}`;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ui-dp-day';
        btn.textContent = String(d);

        if (iso === todayIso) btn.classList.add('today');
        if (iso === startIso || (mode === 'range' && iso === endIso)) {
          btn.classList.add('selected');
        } else if (mode === 'range' && startIso && endIso && iso > startIso && iso < endIso) {
          btn.classList.add('in-range');
        }

        if ((minStr && iso < minStr) || (maxStr && iso > maxStr)) {
          btn.disabled = true;
        }

        btn.addEventListener('click', () => {
          if (mode === 'range') {
            if (!pickingEnd || !startIso) {
              startIso = iso;
              endIso = '';
              pickingEnd = true;
              renderBody();
            } else {
              endIso = iso;
              if (startIso > endIso) [startIso, endIso] = [endIso, startIso];
              pickingEnd = false;
              renderBody();
              commitValue(!showTime);
            }
          } else {
            startIso = iso;
            renderBody();
            if (!showTime) commitValue(true);
          }
        });

        daysGrid.appendChild(btn);
      }

      bodyContainer.appendChild(daysGrid);
      if (popover.__floatingScrollInstance) {
        popover.__floatingScrollInstance.refresh();
      }
    }

    const goPrev = () => {
      if (monthSelectView) {
        viewYear--;
      } else {
        viewMonth--;
        if (viewMonth < 0) {
          viewMonth = 11;
          viewYear--;
        }
      }
      renderBody();
    };

    const goNext = () => {
      if (monthSelectView) {
        viewYear++;
      } else {
        viewMonth++;
        if (viewMonth > 11) {
          viewMonth = 0;
          viewYear++;
        }
      }
      renderBody();
    };

    prevBtn.addEventListener('click', goPrev);
    nextBtn.addEventListener('click', goNext);
    titleBtn.addEventListener('click', () => {
      monthSelectView = !monthSelectView;
      renderBody();
    });

    if (ui && typeof ui.bindSwipeHorizontal === 'function') {
      ui.bindSwipeHorizontal(bodyContainer, goPrev, goNext);
    }

    renderBody();
    if (isInline) {
      inlineHost.innerHTML = '';
      inlineHost.appendChild(popover);
      if (ui && typeof ui.attachFloatingScroll === 'function') {
        ui.attachFloatingScroll(popover, input);
      }
      return;
    }

    document.body.appendChild(popover);

    if (ui && typeof ui.positionFloating === 'function') {
      const pos = ui.positionFloating(popover, displayEl, {
        sourceEl: input,
        fallbackWidth: 304,
        fallbackHeight: 340,
        onBackdropClick: closePopover
      });
      activeBackdrop = pos.backdrop;
    } else {
      const rect = displayEl.getBoundingClientRect();
      popover.style.top = `${rect.bottom + 8}px`;
      popover.style.left = `${rect.left}px`;
    }

    activePopover = popover;
    if (ui && typeof ui.attachFloatingScroll === 'function') {
      ui.attachFloatingScroll(popover, input);
    }
  }

  function initDatePickers() {
    document.querySelectorAll('.date-picker:not([data-dp-initialized])').forEach((input) => {
      input.dataset.dpInitialized = 'true';
      if (input.type === 'date') input.type = 'text';
      input.style.display = 'none';

      const format = input.getAttribute('data-format') || 'YYYY-MM-DD';
      const showTime = input.getAttribute('data-time') === 'true';

      const wrap = document.createElement('div');
      wrap.className = 'ui-dp-trigger-wrap';
      if (ui) ui.copyUiVars(input, wrap);

      const displayInput = document.createElement('input');
      displayInput.type = 'text';
      displayInput.readOnly = true;
      displayInput.className = 'ui-dp-input-display';
      displayInput.placeholder = input.getAttribute('placeholder') || tr('select_date', {}, 'Seleccionar fecha');
      if (ui) ui.copyUiVars(input, displayInput);

      const uiTrigger = input.getAttribute('ui-trigger') || '';
      if (uiTrigger && ui) ui.applyClasses(displayInput, uiTrigger);

      if (input.value) {
        if (input.value.includes(' - ')) {
          const [s, e] = input.value.split(' - ');
          displayInput.value = `${formatDisplay(s, format)} - ${formatDisplay(e, format)}`;
        } else {
          displayInput.value = formatDisplay(input.value.slice(0, 10), format, showTime ? input.value.slice(11, 16) : '');
        }
      }

      const calBtn = document.createElement('button');
      calBtn.type = 'button';
      calBtn.className = 'ui-dp-trigger-btn';
      calBtn.setAttribute('aria-label', tr('select_date', {}, 'Seleccionar fecha'));
      calBtn.innerHTML = icon('calendar', '📅');

      input.insertAdjacentElement('beforebegin', wrap);
      wrap.appendChild(displayInput);
      wrap.appendChild(calBtn);

      const triggerOpen = (e) => {
        e.preventDefault();
        e.stopPropagation();
        openPopover(input, displayInput);
      };

      displayInput.addEventListener('click', triggerOpen);
      calBtn.addEventListener('click', triggerOpen);
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
    ui.observeDynamic(initDatePickers);
  } else {
    initDatePickers();
    document.addEventListener('previewUpdated', initDatePickers);
  }
}
