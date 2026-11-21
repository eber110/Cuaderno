/**
 * Slider Táctil Simple y de Doble Rango (`.range-slider`).
 * Soporta Pointer Events, burbuja de valor y tokens CSS (`--ui-accent`, `--ui-track-bg`, `--ui-thumb-bg`).
 *
 * @function rangeSlider
 * @description Reemplaza inputs `.range-slider` por deslizadores táctiles de valor único o rango doble (`data-mode="range"`).
 *
 * @example
 * // HTML - Slider de doble rango para filtro de precios
 * <input type="text"
 *        name="precio"
 *        class="range-slider"
 *        min="0"
 *        max="1000"
 *        step="10"
 *        value="150,700"
 *        data-mode="range"
 *        data-suffix=" USD">
 *
 * @css .range-slider - Clase requerida en el `<input>` para inicializar el slider
 * @attribute data-mode - Usa `'range'` para habilitar dos controles deslizantes (opcional)
 * @attribute data-suffix - Sufijo mostrado en la burbuja del valor (opcional)
 * @attribute data-tooltip - Oculta la burbuja de valor cuando vale `'false'` (opcional)
 *
 * @returns {void}
 */
export function rangeSlider() {
  const ui = window.FmeUi || null;

  function initRangeSliders() {
    document.querySelectorAll('.range-slider:not([data-rs-initialized])').forEach((input) => {
      input.dataset.rsInitialized = 'true';
      input.style.display = 'none';

      const isDual = (input.getAttribute('data-mode') || '').toLowerCase() === 'range';
      const min = parseFloat(input.getAttribute('min') ?? '0') || 0;
      const max = parseFloat(input.getAttribute('max') ?? '100') || 100;
      const step = parseFloat(input.getAttribute('step') ?? '1') || 1;
      const suffix = input.getAttribute('data-suffix') || '';
      const showTooltip = input.getAttribute('data-tooltip') !== 'false';

      const snapVal = (v) => {
        const clamped = Math.max(min, Math.min(max, v));
        const steps = Math.round((clamped - min) / step);
        return Math.max(min, Math.min(max, Number((min + steps * step).toFixed(4))));
      };

      let lowVal = min;
      let highVal = max;

      if (isDual) {
        const parts = String(input.value || `${min},${max}`).split(',').map((s) => parseFloat(s.trim()));
        lowVal = snapVal(Number.isFinite(parts[0]) ? parts[0] : min);
        highVal = snapVal(Number.isFinite(parts[1]) ? parts[1] : max);
        if (lowVal > highVal) [lowVal, highVal] = [highVal, lowVal];
      } else {
        highVal = snapVal(parseFloat(input.value || String((min + max) / 2)) || min);
      }

      const wrap = document.createElement('div');
      wrap.className = 'ui-range-wrap';
      if (ui) ui.copyUiVars(input, wrap);

      const track = document.createElement('div');
      track.className = 'ui-range-track';

      const fill = document.createElement('div');
      fill.className = 'ui-range-fill';
      track.appendChild(fill);

      const createThumb = () => {
        const th = document.createElement('div');
        th.className = 'ui-range-thumb';
        th.setAttribute('tabindex', '0');
        let tip = null;
        if (showTooltip) {
          tip = document.createElement('div');
          tip.className = 'ui-range-tooltip';
          th.appendChild(tip);
        }
        return { th, tip };
      };

      const thumbHigh = createThumb();
      const thumbLow = isDual ? createThumb() : null;

      if (thumbLow) track.appendChild(thumbLow.th);
      track.appendChild(thumbHigh.th);

      const valsRow = document.createElement('div');
      valsRow.className = 'ui-range-values ui-slider-limits';
      const minSpan = document.createElement('span');
      const maxSpan = document.createElement('span');
      valsRow.appendChild(minSpan);
      valsRow.appendChild(maxSpan);

      wrap.appendChild(track);
      wrap.appendChild(valsRow);
      input.insertAdjacentElement('beforebegin', wrap);

      const toPct = (v) => (max === min ? 0 : ((v - min) / (max - min)) * 100);

      const render = (emitInput = false) => {
        const highPct = toPct(highVal);
        if (isDual && thumbLow) {
          const lowPct = toPct(lowVal);
          fill.style.left = `${lowPct}%`;
          fill.style.width = `${Math.max(0, highPct - lowPct)}%`;
          thumbLow.th.style.left = `${lowPct}%`;
          thumbHigh.th.style.left = `${highPct}%`;
          if (thumbLow.tip) thumbLow.tip.textContent = `${lowVal}${suffix}`;
          if (thumbHigh.tip) thumbHigh.tip.textContent = `${highVal}${suffix}`;
          minSpan.textContent = `${lowVal}${suffix}`;
          maxSpan.textContent = `${highVal}${suffix}`;
          input.value = `${lowVal},${highVal}`;
        } else {
          fill.style.left = '0%';
          fill.style.width = `${highPct}%`;
          thumbHigh.th.style.left = `${highPct}%`;
          if (thumbHigh.tip) thumbHigh.tip.textContent = `${highVal}${suffix}`;
          minSpan.textContent = `${min}${suffix}`;
          maxSpan.textContent = `${highVal}${suffix}`;
          input.value = String(highVal);
        }
        if (emitInput) {
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
      };

      let activeHandle = 'high';
      const handleRatio = (r) => {
        const val = snapVal(min + r * (max - min));
        if (isDual) {
          if (activeHandle === 'low') {
            lowVal = Math.min(val, highVal);
          } else {
            highVal = Math.max(val, lowVal);
          }
        } else {
          highVal = val;
        }
        render(true);
      };

      const onPointerDown = (e) => {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        e.preventDefault();
        const rect = track.getBoundingClientRect();
        const ratio = Math.max(0, Math.min(1, (e.clientX - rect.left) / (rect.width || 1)));
        const clickVal = min + ratio * (max - min);
        if (isDual) {
          activeHandle = Math.abs(clickVal - lowVal) <= Math.abs(clickVal - highVal) ? 'low' : 'high';
        } else {
          activeHandle = 'high';
        }
        if (typeof track.setPointerCapture === 'function' && e.pointerId !== undefined) {
          try { track.setPointerCapture(e.pointerId); } catch (_) {}
        }
        handleRatio(ratio);
      };

      const onPointerMove = (e) => {
        if (e.buttons === 0 && e.pointerType === 'mouse') return;
        if (!track.hasPointerCapture || !track.hasPointerCapture(e.pointerId)) return;
        const rect = track.getBoundingClientRect();
        const ratio = Math.max(0, Math.min(1, (e.clientX - rect.left) / (rect.width || 1)));
        handleRatio(ratio);
      };

      const onPointerUp = (e) => {
        if (track.hasPointerCapture && track.hasPointerCapture(e.pointerId)) {
          try { track.releasePointerCapture(e.pointerId); } catch (_) {}
        }
        input.dispatchEvent(new Event('change', { bubbles: true }));
      };

      track.addEventListener('pointerdown', onPointerDown);
      track.addEventListener('pointermove', onPointerMove);
      track.addEventListener('pointerup', onPointerUp);
      track.addEventListener('pointercancel', onPointerUp);

      render(false);
    });
  }

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initRangeSliders);
  } else {
    initRangeSliders();
    document.addEventListener('previewUpdated', initRangeSliders);
  }
}
