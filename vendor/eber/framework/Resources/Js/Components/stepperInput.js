/**
 * Input Numérico Stepper Táctil (- 1 +) (.stepper-input).
 * Ofrece botones amplios (min 44px), repetición automática por pulsación prolongada
 * (Pointer Events), validación min/max/step y control por teclado.
 *
 * @function stepperInput
 * @description Transforma inputs `.stepper-input` en controles incrementales accesibles
 *              aptos para pantallas touch y escritorio.
 *
 * @example
 * <input type="number" name="cantidad" class="stepper-input" value="1" min="0" max="99" step="1">
 *
 * @css .ui-stepper, .ui-stepper-btn, .ui-stepper-field
 * @requires window.FmeUi (opcional, con respaldo integrado)
 * @returns {void}
 */
export function stepperInput() {
  const ui = window.FmeUi || null;
  const iconMinus = (ui && ui.ICONS && ui.ICONS.minus)
    ? ui.ICONS.minus
    : '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>';
  const iconPlus = (ui && ui.ICONS && ui.ICONS.plus)
    ? ui.ICONS.plus
    : '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';

  function getDecimals(num) {
    const str = String(num);
    if (str.indexOf('.') === -1) return 0;
    return str.split('.')[1].length;
  }

  function initSteppers() {
    document.querySelectorAll('.stepper-input:not([data-stepper-initialized])').forEach((input) => {
      input.dataset.stepperInitialized = 'true';

      const minAttr = input.getAttribute('min');
      const maxAttr = input.getAttribute('max');
      const hasMin = minAttr !== null && minAttr !== '' && Number.isFinite(parseFloat(minAttr));
      const hasMax = maxAttr !== null && maxAttr !== '' && Number.isFinite(parseFloat(maxAttr));
      const min = hasMin ? parseFloat(minAttr) : -Infinity;
      const max = hasMax ? parseFloat(maxAttr) : Infinity;
      const stepRaw = parseFloat(input.getAttribute('step') || '1');
      const step = Number.isFinite(stepRaw) && stepRaw > 0 ? stepRaw : 1;
      const decimals = Math.max(getDecimals(step), hasMin ? getDecimals(min) : 0);
      const isDisabled = input.disabled || input.readOnly;
      const uiTrigger = input.getAttribute('ui-trigger') || '';
      const uiTrack = input.getAttribute('ui-track') || '';

      input.style.display = 'none';

      const wrap = document.createElement('div');
      wrap.className = 'ui-stepper';
      wrap.setAttribute('role', 'group');
      if (ui) {
        ui.copyUiVars(input, wrap);
        if (uiTrigger) ui.applyClasses(wrap, uiTrigger);
      }

      const btnDec = document.createElement('button');
      btnDec.type = 'button';
      btnDec.className = 'ui-stepper-btn ui-stepper-dec';
      btnDec.innerHTML = iconMinus;
      btnDec.setAttribute('aria-label', 'Disminuir');
      if (uiTrack && ui) ui.applyClasses(btnDec, uiTrack);

      const field = document.createElement('input');
      field.type = 'number';
      field.className = 'ui-stepper-field';
      field.setAttribute('inputmode', decimals > 0 ? 'decimal' : 'numeric');
      if (hasMin) field.min = String(min);
      if (hasMax) field.max = String(max);
      field.step = String(step);
      field.disabled = isDisabled;
      if (input.getAttribute('aria-label')) {
        field.setAttribute('aria-label', input.getAttribute('aria-label'));
      }

      const btnInc = document.createElement('button');
      btnInc.type = 'button';
      btnInc.className = 'ui-stepper-btn ui-stepper-inc';
      btnInc.innerHTML = iconPlus;
      btnInc.setAttribute('aria-label', 'Incrementar');
      if (uiTrack && ui) ui.applyClasses(btnInc, uiTrack);

      wrap.appendChild(btnDec);
      wrap.appendChild(field);
      wrap.appendChild(btnInc);
      input.insertAdjacentElement('afterend', wrap);

      const clampValue = (val) => {
        let num = Number.isFinite(val) ? val : (hasMin ? min : 0);
        if (hasMin && num < min) num = min;
        if (hasMax && num > max) num = max;
        return decimals > 0 ? Number(num.toFixed(decimals)) : Math.round(num * 1e9) / 1e9;
      };

      const updateButtonsState = (val) => {
        btnDec.disabled = isDisabled || (hasMin && val <= min);
        btnInc.disabled = isDisabled || (hasMax && val >= max);
      };

      const setValue = (nextVal, emitChange = true) => {
        const clamped = clampValue(nextVal);
        const formatted = decimals > 0 ? clamped.toFixed(decimals) : String(clamped);
        field.value = formatted;
        input.value = formatted;
        updateButtonsState(clamped);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        if (emitChange) {
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      };

      const stepBy = (delta, emitChange = true) => {
        if (isDisabled) return;
        const current = parseFloat(field.value);
        const base = Number.isFinite(current) ? current : (hasMin ? min : 0);
        setValue(base + delta, emitChange);
      };

      const initVal = clampValue(parseFloat(input.value !== '' ? input.value : (hasMin ? min : 0)));
      field.value = decimals > 0 ? initVal.toFixed(decimals) : String(initVal);
      input.value = field.value;
      updateButtonsState(initVal);

      const bindHoldButton = (btn, delta) => {
        let delayTimer = null;
        let repeatTimer = null;
        let handledByPointer = false;

        const clearTimers = () => {
          if (delayTimer) { clearTimeout(delayTimer); delayTimer = null; }
          if (repeatTimer) { clearInterval(repeatTimer); repeatTimer = null; }
        };

        const stopHold = (emit) => {
          const hadTimers = !!(delayTimer || repeatTimer);
          clearTimers();
          if (emit && hadTimers) {
            input.dispatchEvent(new Event('change', { bubbles: true }));
          }
        };

        btn.addEventListener('pointerdown', (e) => {
          if (btn.disabled) return;
          if (e.pointerType === 'mouse' && e.button !== 0) return;
          e.preventDefault();
          handledByPointer = true;
          stepBy(delta, false);
          clearTimers();
          delayTimer = setTimeout(() => {
            repeatTimer = setInterval(() => {
              if (btn.disabled) {
                clearTimers();
                return;
              }
              stepBy(delta, false);
            }, 85);
          }, 360);
        });

        btn.addEventListener('pointerup', () => stopHold(true));
        btn.addEventListener('pointercancel', () => stopHold(false));
        btn.addEventListener('pointerleave', () => stopHold(false));

        // Accesibilidad por teclado (Enter / Space) sin duplicar el toque por puntero
        btn.addEventListener('click', (e) => {
          if (handledByPointer) {
            handledByPointer = false;
            return;
          }
          if (btn.disabled) return;
          stepBy(delta, true);
        });
      };

      bindHoldButton(btnDec, -step);
      bindHoldButton(btnInc, step);

      field.addEventListener('input', () => {
        const parsed = parseFloat(field.value);
        if (Number.isFinite(parsed)) {
          input.value = String(clampValue(parsed));
          updateButtonsState(clampValue(parsed));
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
      });

      field.addEventListener('blur', () => {
        const parsed = parseFloat(field.value);
        setValue(parsed, true);
      });

      field.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowUp') {
          e.preventDefault();
          stepBy(step, true);
        } else if (e.key === 'ArrowDown') {
          e.preventDefault();
          stepBy(-step, true);
        }
      });
    });
  }

  if (window.__fmeStepperBound) {
    initSteppers();
    return;
  }
  window.__fmeStepperBound = true;

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initSteppers);
  } else {
    initSteppers();
    document.addEventListener('previewUpdated', initSteppers);
  }
}
