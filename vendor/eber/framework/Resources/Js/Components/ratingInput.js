/**
 * Input de Calificación Interactiva con Estrellas / Corazones (.rating-input).
 * Soporta calificaciones enteras o medias (data-half="true"), arrastre táctil fluido
 * con Pointer Events, tokens CSS (--ui-accent, --ui-track-bg) y navegación por teclado.
 *
 * @function ratingInput
 * @description Transforma un input `.rating-input` en un selector visual de puntuación.
 *
 * @example
 * <input type="hidden" name="rating" class="rating-input" value="4" data-max="5" data-half="true" data-icon="star">
 *
 * @css .ui-rating, .ui-rating-item, .is-filled
 * @requires window.FmeUi (opcional, con respaldo integrado)
 * @returns {void}
 */
export function ratingInput() {
  const ui = window.FmeUi || null;
  const fallbackIcons = {
    star: '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 2.5l2.93 5.94 6.56.95-4.74 4.62 1.12 6.53L12 17.46l-5.87 3.08 1.12-6.53L2.51 9.39l6.56-.95L12 2.5z"/></svg>',
    heart: '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>'
  };

  function initRatings() {
    document.querySelectorAll('.rating-input:not([data-rating-initialized])').forEach((input) => {
      input.dataset.ratingInitialized = 'true';

      const max = Math.max(3, Math.min(10, parseInt(input.getAttribute('data-max') || '5', 10) || 5));
      const allowHalf = input.getAttribute('data-half') === 'true';
      const clearable = input.getAttribute('data-clearable') === 'true';
      const iconType = (input.getAttribute('data-icon') || 'star').toLowerCase() === 'heart' ? 'heart' : 'star';
      const isReadonly = input.disabled || input.readOnly || input.getAttribute('data-readonly') === 'true';
      const svgMarkup = (ui && ui.ICONS && ui.ICONS[iconType]) ? ui.ICONS[iconType] : fallbackIcons[iconType];
      const uiAccent = input.getAttribute('ui-accent') || '';
      const uiTrack = input.getAttribute('ui-track') || '';

      input.type = 'hidden';

      const container = document.createElement('div');
      container.className = 'ui-rating';
      container.setAttribute('role', 'slider');
      container.setAttribute('aria-valuemin', '0');
      container.setAttribute('aria-valuemax', String(max));
      container.setAttribute('tabindex', isReadonly ? '-1' : '0');
      if (isReadonly) container.setAttribute('aria-readonly', 'true');
      if (ui) ui.copyUiVars(input, container);

      const items = [];
      for (let i = 1; i <= max; i++) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ui-rating-item';
        btn.tabIndex = -1;
        btn.dataset.index = String(i);
        btn.setAttribute('aria-label', `${i} / ${max}`);
        if (uiTrack && ui) ui.applyClasses(btn, uiTrack);

        const wrapper = document.createElement('span');
        wrapper.style.position = 'relative';
        wrapper.style.display = 'inline-flex';
        wrapper.style.width = '24px';
        wrapper.style.height = '24px';
        wrapper.style.pointerEvents = 'none';

        const baseIcon = document.createElement('span');
        baseIcon.innerHTML = svgMarkup;
        baseIcon.style.display = 'inline-flex';

        const halfOverlay = document.createElement('span');
        halfOverlay.className = 'ui-rating-half';
        halfOverlay.innerHTML = svgMarkup;
        halfOverlay.style.position = 'absolute';
        halfOverlay.style.left = '0';
        halfOverlay.style.top = '0';
        halfOverlay.style.width = '50%';
        halfOverlay.style.height = '100%';
        halfOverlay.style.overflow = 'hidden';
        halfOverlay.style.display = 'none';
        halfOverlay.style.color = 'var(--ui-accent, #f59e0b)';
        if (uiAccent && ui) ui.applyClasses(halfOverlay, uiAccent);

        wrapper.appendChild(baseIcon);
        wrapper.appendChild(halfOverlay);
        btn.appendChild(wrapper);
        container.appendChild(btn);
        items.push({ btn, halfOverlay, index: i });
      }

      input.insertAdjacentElement('afterend', container);

      let currentValue = Math.max(0, Math.min(max, parseFloat(input.value || '0') || 0));
      let hoverValue = null;

      const applyVisual = (displayVal) => {
        items.forEach(({ btn, halfOverlay, index }) => {
          btn.classList.remove('is-filled', 'is-half');
          halfOverlay.style.display = 'none';
          if (displayVal >= index) {
            btn.classList.add('is-filled');
            if (uiAccent && ui) ui.applyClasses(btn, uiAccent);
          } else if (allowHalf && displayVal >= index - 0.5) {
            btn.classList.add('is-half');
            halfOverlay.style.display = 'inline-flex';
          }
        });
      };

      const commitValue = (val, emit = true) => {
        const step = allowHalf ? 0.5 : 1;
        const snapped = Math.max(0, Math.min(max, Math.round(val / step) * step));
        currentValue = snapped;
        input.value = String(snapped);
        container.setAttribute('aria-valuenow', String(snapped));
        applyVisual(currentValue);
        if (emit) {
          input.dispatchEvent(new Event('input', { bubbles: true }));
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      };

      const computeValueFromX = (clientX) => {
        for (let i = 0; i < items.length; i++) {
          const rect = items[i].btn.getBoundingClientRect();
          if (clientX <= rect.right || i === items.length - 1) {
            if (allowHalf && clientX < rect.left + rect.width / 2 && clientX >= rect.left - 8) {
              return Math.max(0.5, items[i].index - 0.5);
            }
            if (clientX < items[0].btn.getBoundingClientRect().left) {
              return 0;
            }
            return items[i].index;
          }
        }
        return currentValue;
      };

      commitValue(currentValue, false);

      if (!isReadonly) {
        let isDragging = false;
        let pointerStartVal = currentValue;

        container.addEventListener('pointerdown', (e) => {
          if (e.pointerType === 'mouse' && e.button !== 0) return;
          isDragging = true;
          pointerStartVal = currentValue;
          if (container.setPointerCapture) {
            try { container.setPointerCapture(e.pointerId); } catch (_) {}
          }
          const nextVal = computeValueFromX(e.clientX);
          hoverValue = nextVal;
          applyVisual(nextVal);
        });

        container.addEventListener('pointermove', (e) => {
          const nextVal = computeValueFromX(e.clientX);
          if (isDragging) {
            hoverValue = nextVal;
            applyVisual(nextVal);
          } else if (e.pointerType === 'mouse') {
            hoverValue = nextVal;
            applyVisual(nextVal);
          }
        });

        const endPointer = (e) => {
          if (!isDragging) return;
          isDragging = false;
          if (container.hasPointerCapture && container.hasPointerCapture(e.pointerId)) {
            try { container.releasePointerCapture(e.pointerId); } catch (_) {}
          }
          let finalVal = hoverValue !== null ? hoverValue : computeValueFromX(e.clientX);
          if (clearable && finalVal === pointerStartVal) {
            finalVal = 0;
          }
          hoverValue = null;
          commitValue(finalVal, true);
        };

        container.addEventListener('pointerup', endPointer);
        container.addEventListener('pointercancel', () => {
          isDragging = false;
          hoverValue = null;
          applyVisual(currentValue);
        });

        container.addEventListener('pointerleave', () => {
          if (!isDragging) {
            hoverValue = null;
            applyVisual(currentValue);
          }
        });

        container.addEventListener('keydown', (e) => {
          const step = allowHalf ? 0.5 : 1;
          if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
            e.preventDefault();
            commitValue(currentValue + step, true);
          } else if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
            e.preventDefault();
            commitValue(currentValue - step, true);
          } else if (e.key === 'Home') {
            e.preventDefault();
            commitValue(0, true);
          } else if (e.key === 'End') {
            e.preventDefault();
            commitValue(max, true);
          }
        });
      }
    });
  }

  if (window.__fmeRatingBound) {
    initRatings();
    return;
  }
  window.__fmeRatingBound = true;

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initRatings);
  } else {
    initRatings();
    document.addEventListener('previewUpdated', initRatings);
  }
}
