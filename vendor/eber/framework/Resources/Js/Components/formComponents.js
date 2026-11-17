/**
 * Componentes de Formulario HTML (Color Picker + Checkbox Switch).
 * Refactorizado con soporte táctil completo (Pointer Events + Bottom-Sheet móvil),
 * corrección de bugs de posicionamiento/HSL, tokens CSS (--ui-*) y modo oscuro [data-theme="dark"].
 *
 * @function formComponents
 * @returns {void}
 */

export function colorPicker() {
  if (window.__fmeColorPickerBound) return;
  window.__fmeColorPickerBound = true;

  const ui = window.FmeUi || null;
  const tr = (k, p, f) => (ui ? ui.t(k, p, f) : (typeof window.t === 'function' ? window.t(k, p, f) : f));

  const defaultPresets = [
    '#FF4B4B', '#FF8E53', '#FFD369', '#4E9F3D', '#17B978',
    '#3F72AF', '#3D84B8', '#3F51B5', '#8A39E1', '#D1345B',
    '#111111', '#555555', '#888888', '#CCCCCC', '#FFFFFF'
  ];

  function hslToHex(h, s, l) {
    l /= 100;
    const a = (s * Math.min(l, 1 - l)) / 100;
    const f = (n) => {
      const k = (n + h / 30) % 12;
      const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
      return Math.round(255 * color).toString(16).padStart(2, '0');
    };
    return `#${f(0)}${f(8)}${f(4)}`.toUpperCase();
  }

  function hexToHsl(hex) {
    const clean = hex.replace('#', '').slice(0, 6);
    const r = parseInt(clean.slice(0, 2), 16) / 255;
    const g = parseInt(clean.slice(2, 4), 16) / 255;
    const b = parseInt(clean.slice(4, 6), 16) / 255;
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    let h = 0;
    let s = 0;
    const l = (max + min) / 2;

    if (max !== min) {
      const d = max - min;
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      switch (max) {
        case r: h = (g - b) / d + (g < b ? 6 : 0); break;
        case g: h = (b - r) / d + 2; break;
        case b: h = (r - g) / d + 4; break;
      }
      h /= 6;
    }
    return { h: Math.round(h * 360), s: Math.round(s * 100), l: Math.round(l * 100) };
  }

  function styleColorPickers() {
    document.querySelectorAll('input[type="color"].color-picker:not([data-cp-styled])').forEach((input) => {
      input.dataset.cpStyled = 'true';
      const styleClasses = input.getAttribute('style-color') || input.getAttribute('ui-trigger') || input.dataset.styleColor || '';
      if (styleClasses) {
        styleClasses.split(/\s+/).filter(Boolean).forEach((cls) => input.classList.add(cls));
      }
      if (/^#[0-9a-f]{3,8}$/i.test(input.value || '')) {
        input.style.backgroundColor = input.value;
      }
      input.addEventListener('fme:renderInlinePreview', (ev) => {
        if (ev.detail?.host) {
          openCustomColorPicker(input, input, { inlineHost: ev.detail.host });
        }
      });
    });
  }

  function updateLivePreviewColor(name, hex, inputElement = null) {
    if (!name || !hex) return;

    const liveTargetSel = inputElement?.getAttribute('data-live-target');
    if (liveTargetSel) {
      const prop = inputElement.getAttribute('data-live-prop') || 'backgroundColor';
      document.querySelectorAll(liveTargetSel).forEach((el) => {
        el.style[prop] = hex;
      });
    }

    const preview = document.querySelector('.user-profile-preview');
    if (!preview) return;

    if (name === 'back_perfil') {
      preview.querySelectorAll('.back-card, .back-card-container').forEach((el) => {
        el.style.backgroundColor = hex;
      });
    } else if (name === 'colorText') {
      preview.querySelectorAll('.desc-hero-regular, .desc-hero-big, .desc-hero-mini, p, .color-text').forEach((el) => {
        if (!el.closest('.theme-button')) el.style.color = hex;
      });
    } else if (name === 'titleColor') {
      preview.querySelectorAll('.title-hero-regular, .title-hero-big, .title-hero-mini, h1, h2, h3').forEach((el) => {
        el.style.color = hex;
      });
    } else if (name === 'back') {
      preview.querySelectorAll('.theme-button').forEach((el) => {
        el.style.backgroundColor = hex;
      });
    } else if (name === 'color') {
      preview.querySelectorAll('.theme-button').forEach((el) => {
        el.style.color = hex;
      });
    }
  }

  let activePopover = null;
  let activeBackdrop = null;
  let activeInput = null;

  function closeActivePopover(confirm = false) {
    if (!activePopover) return;
    const inputRef = activeInput;
    const popoverRef = activePopover;
    const backdropRef = activeBackdrop;

    activePopover = null;
    activeInput = null;
    activeBackdrop = null;

    if (popoverRef && popoverRef.parentNode) popoverRef.parentNode.removeChild(popoverRef);
    if (backdropRef && backdropRef.parentNode) backdropRef.parentNode.removeChild(backdropRef);

    if (confirm && inputRef) {
      const currentInput = inputRef.id ? (document.getElementById(inputRef.id) || inputRef) : inputRef;
      const labelText = currentInput.closest('label')?.querySelector('p, span');
      if (labelText && currentInput.value) labelText.textContent = currentInput.value;
      currentInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  function createColorTrack(initialPct, gradientCss, onRatio) {
    const track = document.createElement('div');
    track.className = 'ui-color-track';
    track.style.background = gradientCss;

    const thumb = document.createElement('div');
    thumb.className = 'ui-color-track-thumb';
    thumb.style.left = `${Math.max(0, Math.min(100, initialPct))}%`;
    track.appendChild(thumb);

    const setRatio = (r) => {
      thumb.style.left = `${Math.round(r * 1000) / 10}%`;
      onRatio(r);
    };

    if (ui && typeof ui.bindDragValue === 'function') {
      ui.bindDragValue(track, setRatio);
    } else {
      track.addEventListener('pointerdown', (e) => {
        const rect = track.getBoundingClientRect();
        const r = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
        setRatio(r);
      });
    }

    return { track, thumb };
  }

  function openCustomColorPicker(input, trigger, options = {}) {
    const inlineHost = options.inlineHost || null;
    const isInline = !!inlineHost;

    if (!isInline && activePopover && activeInput === input) {
      closeActivePopover(true);
      return;
    }

    if (!isInline) {
      closeActivePopover(true);
      activeInput = input;
    }
    const inputId = input.id;
    const showAlpha = input.getAttribute('data-alpha') === 'true';
    const customSwatchesRaw = input.getAttribute('data-swatches') || '';
    const palette = customSwatchesRaw
      ? customSwatchesRaw.split(',').map((c) => c.trim()).filter((c) => /^#[0-9a-f]{3,8}$/i.test(c))
      : defaultPresets;

    function getLiveInput() {
      if (inputId) {
        const live = document.getElementById(inputId);
        if (live) return live;
      }
      return input;
    }

    const popover = document.createElement('div');
    popover.className = isInline
      ? 'custom-color-picker-popover ui-popover is-inline-preview'
      : 'custom-color-picker-popover ui-popover';
    if (ui) ui.copyUiVars(input, popover);

    const boxClasses = input.getAttribute('ui-surface') || input.getAttribute('style-box') || input.dataset.styleBox || '';
    if (boxClasses) {
      if (ui) ui.applyClasses(popover, boxClasses);
      else boxClasses.split(/\s+/).filter(Boolean).forEach((cls) => popover.classList.add(cls));
    }

    popover.addEventListener('click', (e) => e.stopPropagation());
    popover.addEventListener('pointerdown', (e) => e.stopPropagation());
    if (ui) popover.appendChild(ui.createSheetHandle());

    let currentValue = (input.value || '#3B82F6').toUpperCase();
    if (!/^#[0-9A-F]{6}$/i.test(currentValue)) currentValue = '#3B82F6';
    const hslState = hexToHsl(currentValue);
    let alphaVal = 100;

    const grid = document.createElement('div');
    grid.className = 'ui-color-grid';

    const syncUiState = (hex, emitLive = true) => {
      const liveInput = getLiveInput();
      const baseHex = hex.slice(0, 7).toUpperCase();
      liveInput.value = baseHex;
      liveInput.style.backgroundColor = baseHex;
      if (showAlpha) liveInput.dataset.alpha = String(alphaVal);

      const displayColor = showAlpha && alphaVal < 100
        ? `color-mix(in srgb, ${baseHex} ${alphaVal}%, transparent)`
        : baseHex;

      hexInput.value = baseHex;
      preview.style.background = displayColor;

      const labelText = liveInput.closest('label')?.querySelector('p, span');
      if (labelText) labelText.textContent = baseHex;

      satTrackObj.track.style.background = `linear-gradient(to right, ${hslToHex(hslState.h, 0, hslState.l)}, ${hslToHex(hslState.h, 100, hslState.l)})`;
      lightTrackObj.track.style.background = `linear-gradient(to right, ${hslToHex(hslState.h, hslState.s, 0)}, ${hslToHex(hslState.h, hslState.s, 50)}, ${hslToHex(hslState.h, hslState.s, 100)})`;
      if (alphaTrackObj) {
        alphaTrackObj.track.style.background = `linear-gradient(to right, transparent, ${baseHex})`;
      }

      updateLivePreviewColor(liveInput.name, baseHex, liveInput);
      if (emitLive) liveInput.dispatchEvent(new Event('input', { bubbles: true }));
    };

    (palette.length ? palette : defaultPresets).forEach((color) => {
      const swatch = document.createElement('button');
      swatch.type = 'button';
      swatch.className = 'custom-picker-swatch';
      swatch.style.backgroundColor = color;
      swatch.setAttribute('aria-label', color);
      if (color.toUpperCase() === currentValue) swatch.classList.add('active');

      swatch.addEventListener('click', () => {
        popover.querySelectorAll('.custom-picker-swatch').forEach((s) => s.classList.remove('active'));
        swatch.classList.add('active');
        const parsed = hexToHsl(color);
        hslState.h = parsed.h;
        hslState.s = parsed.s;
        hslState.l = parsed.l;
        hueTrackObj.thumb.style.left = `${(hslState.h / 360) * 100}%`;
        satTrackObj.thumb.style.left = `${hslState.s}%`;
        lightTrackObj.thumb.style.left = `${hslState.l}%`;
        syncUiState(color, true);
      });
      grid.appendChild(swatch);
    });
    popover.appendChild(grid);

    const slidersContainer = document.createElement('div');
    slidersContainer.className = 'ui-color-slider-group';

    const hueLabel = document.createElement('div');
    hueLabel.className = 'ui-color-label';
    hueLabel.textContent = tr('hue', {}, 'Tono');
    slidersContainer.appendChild(hueLabel);

    const hueTrackObj = createColorTrack(
      (hslState.h / 360) * 100,
      'linear-gradient(to right, #ff0000, #ffff00, #00ff00, #00ffff, #0000ff, #ff00ff, #ff0000)',
      (r) => {
        hslState.h = Math.round(r * 360);
        popover.querySelectorAll('.custom-picker-swatch').forEach((s) => s.classList.remove('active'));
        syncUiState(hslToHex(hslState.h, hslState.s, hslState.l), true);
      }
    );
    slidersContainer.appendChild(hueTrackObj.track);

    const satLabel = document.createElement('div');
    satLabel.className = 'ui-color-label';
    satLabel.textContent = tr('saturation', {}, 'Saturación');
    slidersContainer.appendChild(satLabel);

    const satTrackObj = createColorTrack(
      hslState.s,
      `linear-gradient(to right, ${hslToHex(hslState.h, 0, hslState.l)}, ${hslToHex(hslState.h, 100, hslState.l)})`,
      (r) => {
        hslState.s = Math.round(r * 100);
        popover.querySelectorAll('.custom-picker-swatch').forEach((s) => s.classList.remove('active'));
        syncUiState(hslToHex(hslState.h, hslState.s, hslState.l), true);
      }
    );
    slidersContainer.appendChild(satTrackObj.track);

    const lightLabel = document.createElement('div');
    lightLabel.className = 'ui-color-label';
    lightLabel.textContent = tr('lightness', {}, 'Brillo');
    slidersContainer.appendChild(lightLabel);

    const lightTrackObj = createColorTrack(
      hslState.l,
      `linear-gradient(to right, ${hslToHex(hslState.h, hslState.s, 0)}, ${hslToHex(hslState.h, hslState.s, 50)}, ${hslToHex(hslState.h, hslState.s, 100)})`,
      (r) => {
        hslState.l = Math.round(5 + r * 90);
        popover.querySelectorAll('.custom-picker-swatch').forEach((s) => s.classList.remove('active'));
        syncUiState(hslToHex(hslState.h, hslState.s, hslState.l), true);
      }
    );
    slidersContainer.appendChild(lightTrackObj.track);

    let alphaTrackObj = null;
    if (showAlpha) {
      const alphaLabel = document.createElement('div');
      alphaLabel.className = 'ui-color-label';
      alphaLabel.textContent = tr('opacity', {}, 'Opacidad');
      slidersContainer.appendChild(alphaLabel);

      const alphaChecker = document.createElement('div');
      alphaChecker.className = 'ui-color-alpha-checker';
      alphaChecker.style.borderRadius = '999px';
      alphaTrackObj = createColorTrack(
        alphaVal,
        `linear-gradient(to right, transparent, ${currentValue})`,
        (r) => {
          alphaVal = Math.round(r * 100);
          syncUiState(hslToHex(hslState.h, hslState.s, hslState.l), true);
        }
      );
      alphaChecker.appendChild(alphaTrackObj.track);
      slidersContainer.appendChild(alphaChecker);
    }

    popover.appendChild(slidersContainer);

    const footer = document.createElement('div');
    footer.className = 'ui-color-footer';

    const preview = document.createElement('div');
    preview.className = 'ui-color-preview-dot';
    preview.style.backgroundColor = currentValue;

    const hexInput = document.createElement('input');
    hexInput.type = 'text';
    hexInput.className = 'ui-hex-input';
    hexInput.value = currentValue;
    hexInput.maxLength = 7;

    hexInput.addEventListener('input', () => {
      let hex = hexInput.value.trim();
      if (!hex.startsWith('#')) hex = '#' + hex;
      if (/^#[0-9A-F]{6}$/i.test(hex)) {
        const parsed = hexToHsl(hex);
        hslState.h = parsed.h;
        hslState.s = parsed.s;
        hslState.l = parsed.l;
        hueTrackObj.thumb.style.left = `${(hslState.h / 360) * 100}%`;
        satTrackObj.thumb.style.left = `${hslState.s}%`;
        lightTrackObj.thumb.style.left = `${hslState.l}%`;
        syncUiState(hex.toUpperCase(), true);
      }
    });

    const btnClasses = ['ui-btn', 'ui-btn-primary'];
    const customBtnCls = input.getAttribute('ui-btn-primary') || input.getAttribute('ui-btn-save') || '';
    if (customBtnCls) btnClasses.push(...customBtnCls.split(/\s+/).filter(Boolean));

    const acceptBtn = document.createElement('button');
    acceptBtn.type = 'button';
    acceptBtn.className = btnClasses.join(' ');
    acceptBtn.style.minHeight = '38px';
    acceptBtn.style.padding = '6px 14px';
    acceptBtn.textContent = tr('accept', {}, 'Aceptar');
    acceptBtn.addEventListener('click', () => {
      if (isInline) {
        getLiveInput().dispatchEvent(new Event('change', { bubbles: true }));
        return;
      }
      closeActivePopover(true);
    });

    footer.appendChild(preview);
    footer.appendChild(hexInput);
    footer.appendChild(acceptBtn);
    popover.appendChild(footer);

    if (isInline) {
      inlineHost.innerHTML = '';
      inlineHost.appendChild(popover);
      return;
    }

    document.body.appendChild(popover);

    if (ui && typeof ui.positionFloating === 'function') {
      const pos = ui.positionFloating(popover, trigger, {
        sourceEl: input,
        fallbackWidth: 272,
        fallbackHeight: 350,
        onBackdropClick: () => closeActivePopover(true)
      });
      activeBackdrop = pos.backdrop;
    } else {
      const rect = trigger.getBoundingClientRect();
      let top = rect.bottom + 8;
      let left = rect.left;
      if (left + 272 > window.innerWidth) left = Math.max(10, window.innerWidth - 282);
      if (top + 350 > window.innerHeight && rect.top - 350 > 10) top = rect.top - 350;
      popover.style.top = `${top}px`;
      popover.style.left = `${left}px`;
    }

    activePopover = popover;
  }

  document.addEventListener('click', (e) => {
    if (e.target.closest('.custom-color-picker-popover')) return;

    const trigger = e.target.closest('label, [data-trigger-color], input[type="color"].color-picker');
    if (!trigger) {
      if (activePopover) closeActivePopover(true);
      return;
    }

    let colorInput = null;
    if (trigger.tagName === 'INPUT' && trigger.type === 'color') {
      colorInput = trigger;
    } else if (trigger.tagName === 'LABEL') {
      const forId = trigger.getAttribute('for');
      colorInput = forId ? document.getElementById(forId) : trigger.querySelector('input[type="color"]');
    } else {
      colorInput = document.getElementById(trigger.getAttribute('data-trigger-color'));
    }

    if (colorInput && colorInput.classList.contains('color-picker')) {
      e.preventDefault();
      openCustomColorPicker(colorInput, trigger);
    } else if (activePopover) {
      closeActivePopover(true);
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && activePopover) closeActivePopover(false);
  });

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(styleColorPickers);
  } else {
    styleColorPickers();
    document.addEventListener('previewUpdated', styleColorPickers);
  }
}

export function switchInput() {
  if (window.__fmeSwitchInputBound) return;
  window.__fmeSwitchInputBound = true;

  const ui = window.FmeUi || null;

  function initCheckboxSwitches() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"].checkbox-switch:not([data-switch-initialized])');
    checkboxes.forEach((input) => {
      input.setAttribute('data-switch-initialized', 'true');
      input.style.display = 'none';

      const optionsAttr = input.getAttribute('data-option') || 'true,false';
      const options = optionsAttr.split(',').map((opt) => opt.trim());
      const valActive = options[0] || 'true';
      const valInactive = options[1] || 'false';
      const active = input.getAttribute('active') || '1';

      input.checked = active === '1';
      input.value = valActive;

      let hiddenInput = null;
      if (input.name) {
        const existingHidden = input.previousElementSibling;
        if (
          existingHidden &&
          existingHidden.tagName === 'INPUT' &&
          existingHidden.type === 'hidden' &&
          existingHidden.name === input.name
        ) {
          hiddenInput = existingHidden;
        } else {
          hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = input.name;
          input.parentNode.insertBefore(hiddenInput, input);
        }
        hiddenInput.value = valInactive;
        hiddenInput.disabled = input.checked;
      }

      const sizeAttr = (input.getAttribute('ui-size') || 'md').toLowerCase();
      const sizeClass = sizeAttr === 'sm' ? 'switch-sm' : (sizeAttr === 'lg' ? 'switch-lg' : '');

      const wrapper = document.createElement('label');
      wrapper.className = `checkbox-switch-container ${sizeClass}`.trim();
      wrapper.setAttribute('role', 'switch');
      wrapper.setAttribute('aria-checked', input.checked ? 'true' : 'false');
      wrapper.setAttribute('tabindex', '0');
      if (ui) ui.copyUiVars(input, wrapper);

      const track = document.createElement('span');
      track.className = 'checkbox-switch-track';
      const thumb = document.createElement('span');
      thumb.className = 'checkbox-switch-thumb';
      track.appendChild(thumb);

      const labelOn = input.getAttribute('data-label-on') || '';
      const labelOff = input.getAttribute('data-label-off') || '';
      let stateLabel = null;
      if (labelOn || labelOff) {
        stateLabel = document.createElement('span');
        stateLabel.className = 'checkbox-switch-label';
        stateLabel.textContent = input.checked ? (labelOn || labelOff) : (labelOff || labelOn);
      }

      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(input);
      wrapper.appendChild(track);
      if (stateLabel) wrapper.appendChild(stateLabel);

      const syncState = () => {
        input.setAttribute('active', input.checked ? '1' : '2');
        wrapper.setAttribute('aria-checked', input.checked ? 'true' : 'false');
        if (hiddenInput) hiddenInput.disabled = input.checked;
        if (stateLabel) stateLabel.textContent = input.checked ? (labelOn || labelOff) : (labelOff || labelOn);
      };

      input.addEventListener('change', syncState);

      wrapper.addEventListener('keydown', (e) => {
        if (e.key === ' ' || e.key === 'Enter') {
          e.preventDefault();
          input.checked = !input.checked;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });

      let swipeStartX = null;
      wrapper.addEventListener('touchstart', (e) => {
        if (e.touches && e.touches.length === 1) swipeStartX = e.touches[0].clientX;
      }, { passive: true });
      wrapper.addEventListener('touchend', (e) => {
        if (swipeStartX === null || !e.changedTouches || !e.changedTouches.length) return;
        const dx = e.changedTouches[0].clientX - swipeStartX;
        swipeStartX = null;
        if (Math.abs(dx) > 16) {
          const wantChecked = dx > 0;
          if (input.checked !== wantChecked) {
            input.checked = wantChecked;
            input.dispatchEvent(new Event('change', { bubbles: true }));
          }
        }
      }, { passive: true });
    });
  }

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initCheckboxSwitches);
  } else {
    initCheckboxSwitches();
    document.addEventListener('previewUpdated', initCheckboxSwitches);
  }
}

export function formComponents() {
  colorPicker();
  switchInput();
}
