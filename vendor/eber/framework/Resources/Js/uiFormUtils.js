/**
 * Utilidades compartidas para componentes UI de formulario (Tokens --ui-*, Touch, Posicionamiento e Iconos SVG).
 *
 * Se carga en prioridad dentro de jsConfig.json y expone window.FmeUi.
 *
 * @module uiFormUtils
 */

(function () {
  if (typeof window === 'undefined') return;

  const UI_CSS_VARS = [
    '--ui-trigger-bg',
    '--ui-trigger-text',
    '--ui-trigger-border',
    '--ui-surface-bg',
    '--ui-surface-text',
    '--ui-surface-muted',
    '--ui-surface-border',
    '--ui-surface-shadow',
    '--ui-btn-primary-bg',
    '--ui-btn-primary-text',
    '--ui-btn-secondary-bg',
    '--ui-btn-secondary-text',
    '--ui-accent',
    '--ui-accent-text',
    '--ui-track-bg',
    '--ui-thumb-bg',
    '--ui-radius',
    '--ui-touch-size',
    '--ui-font-size',
    '--ui-overlay-blur',
    '--ui-transition'
  ];

  const ICONS = {
    close: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    plus: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
    minus: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>',
    zoomIn: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>',
    zoomOut: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>',
    rotateLeft: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>',
    rotateRight: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>',
    calendar: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    clock: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    eye: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    eyeOff: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
    chevronLeft: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>',
    chevronRight: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>',
    chevronDown: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>',
    upload: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>',
    star: '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 2.5l2.93 5.94 6.56.95-4.74 4.62 1.12 6.53L12 17.46l-5.87 3.08 1.12-6.53L2.51 9.39l6.56-.95L12 2.5z"/></svg>',
    heart: '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
    check: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
  };

  function tr(key, params = {}, fallback = '') {
    if (typeof window.t === 'function') {
      return window.t(key, params, fallback);
    }
    let text = String(fallback || key);
    if (params && typeof params === 'object') {
      Object.keys(params).forEach((k) => {
        text = text.split(':' + k).join(String(params[k]));
      });
    }
    return text;
  }

  function sanitizeClassList(raw) {
    return String(raw || '')
      .split(/\s+/)
      .map((c) => c.trim())
      .filter((c) => c !== '' && /^[a-zA-Z0-9_:\[\].-]+$/.test(c));
  }

  function applyClasses(el, raw) {
    if (!el || !raw) return;
    sanitizeClassList(raw).forEach((cls) => el.classList.add(cls));
  }

  function resolveColorValue(raw) {
    const token = String(raw || '').replace(/^\./, '').trim();
    if (!token) return null;

    if (/[\x00-\x1F\x7F{};<>]|javascript:|expression\(|@import|url\(/i.test(token)) {
      return null;
    }

    const matchNum = token.match(/^(?:back|color|border)(\d+)(-hover)?$/i);
    if (matchNum) {
      const idx = parseInt(matchNum[1], 10);
      const base = idx <= 1 ? '--back-color' : `--back-color${idx}`;
      return matchNum[2] ? `var(${base}-hover, #bfbfbf)` : `var(${base}, #d3d3d3)`;
    }

    const semantic = {
      body: 'var(--body, #ffffff)',
      'body-back': 'var(--body, #ffffff)',
      texto: 'var(--text-primary, #111827)',
      textc: 'var(--text-secondary, #f8fafc)',
      textw: '#ffffff',
      textb: '#000000',
      'text-inactive': 'var(--text-inactive, #9ca3af)',
      inactive: 'var(--text-inactive, #9ca3af)',
      'back-success': 'var(--success, #00b900)',
      'color-success': 'var(--success, #00b900)',
      'back-caution': 'var(--caution, #ff8c00)',
      'color-caution': 'var(--caution, #ff8c00)',
      'back-danger': 'var(--danger, #ff0000)',
      'color-danger': 'var(--danger, #ff0000)'
    };

    const lower = token.toLowerCase();
    if (semantic[lower]) return semantic[lower];
    if (token.startsWith('--')) return `var(${token})`;
    if (/^(var\(|#|rgb\(|rgba\(|hsl\(|hsla\(|oklch\(|transparent$|currentcolor$)/i.test(token)) {
      return token;
    }
    return null;
  }

  /**
   * Copia variables CSS `--ui-*` heredadas o inline desde un input fuente hacia un portal/modal flotante.
   */
  function copyUiVars(sourceEl, targetEl) {
    if (!sourceEl || !targetEl || !targetEl.style) return;

    const wrapperHost = sourceEl.closest ? sourceEl.closest('[style*="--ui-"]') : null;
    const fieldCard = sourceEl.closest ? sourceEl.closest('.ui-field-card') : null;

    UI_CSS_VARS.forEach((varName) => {
      const hostVal = (wrapperHost && wrapperHost !== sourceEl) ? wrapperHost.style.getPropertyValue(varName) : '';
      const selfVal = sourceEl.style ? sourceEl.style.getPropertyValue(varName) : '';
      const val = (selfVal && selfVal.trim() !== '') ? selfVal.trim() : ((hostVal && hostVal.trim() !== '') ? hostVal.trim() : '');
      if (val !== '') {
        targetEl.style.setProperty(varName, val);
        if (fieldCard && fieldCard !== targetEl && (!fieldCard.style.getPropertyValue(varName) || fieldCard.style.getPropertyValue(varName).trim() === '')) {
          fieldCard.style.setProperty(varName, val);
        }
      }
    });

    const attrSrc = (attr) => sourceEl.getAttribute(attr) || (wrapperHost ? wrapperHost.getAttribute(attr) : '');
    const accentAttr = attrSrc('ui-accent');
    const trackAttr = attrSrc('ui-track');
    const radiusAttr = attrSrc('ui-radius');
    const touchAttr = attrSrc('ui-touch');
    const fontAttr = attrSrc('ui-font-size');

    if (accentAttr) {
      const resolved = resolveColorValue(accentAttr);
      if (resolved) targetEl.style.setProperty('--ui-accent', resolved);
    }
    if (trackAttr) {
      const resolved = resolveColorValue(trackAttr);
      if (resolved) targetEl.style.setProperty('--ui-track-bg', resolved);
    }
    if (radiusAttr && /^\d+$/.test(radiusAttr.trim())) {
      targetEl.style.setProperty('--ui-radius', `${radiusAttr.trim()}px`);
    }
    if (touchAttr && /^\d+$/.test(touchAttr.trim())) {
      targetEl.style.setProperty('--ui-touch-size', `${touchAttr.trim()}px`);
    }
    if (fontAttr && /^\d+$/.test(fontAttr.trim())) {
      targetEl.style.setProperty('--ui-font-size', `${fontAttr.trim()}px`);
    }
  }

  function isMobileTouchMode(sourceEl) {
    if (sourceEl && sourceEl.getAttribute('ui-mobile-sheet') === 'false') {
      return false;
    }
    if (document.body.classList.contains('is-touch-simulated') || (sourceEl && sourceEl.closest('.is-touch-simulated'))) {
      return true;
    }
    return window.innerWidth <= 576;
  }

  /**
   * Posiciona un popover flotante sin sumar window.scrollY en `position: fixed`,
   * o lo convierte en Bottom-Sheet en móviles/touch si corresponde.
   */
  function positionFloating(popover, trigger, options = {}) {
    if (!popover || !trigger) return { isSheet: false, backdrop: null };

    const sourceEl = options.sourceEl || trigger;
    const allowSheet = options.allowSheet !== false;
    const useSheet = allowSheet && isMobileTouchMode(sourceEl);
    let backdrop = null;

    if (useSheet) {
      popover.classList.add('is-mobile-sheet');
      popover.style.top = '';
      popover.style.left = '';
      popover.style.right = '';
      popover.style.bottom = '';
      popover.style.width = '';

      if (options.withBackdrop !== false) {
        backdrop = document.createElement('div');
        backdrop.className = 'ui-floating-backdrop';
        copyUiVars(sourceEl, backdrop);
        document.body.appendChild(backdrop);
        requestAnimationFrame(() => backdrop.classList.add('active'));
        if (typeof options.onBackdropClick === 'function') {
          backdrop.addEventListener('click', options.onBackdropClick);
        }
      }
      return { isSheet: true, backdrop };
    }

    popover.classList.remove('is-mobile-sheet');
    const rect = trigger.getBoundingClientRect();
    const popRect = popover.getBoundingClientRect();
    const popW = popRect.width || options.fallbackWidth || 272;
    const popH = popRect.height || options.fallbackHeight || 320;
    const gap = options.gap ?? 8;

    let top = rect.bottom + gap;
    let left = options.alignRight ? (rect.right - popW) : rect.left;

    if (left + popW > window.innerWidth - 10) {
      left = Math.max(10, window.innerWidth - popW - 10);
    }
    if (left < 10) left = 10;

    if (top + popH > window.innerHeight - 10 && rect.top - popH - gap >= 10) {
      top = rect.top - popH - gap;
    } else if (top + popH > window.innerHeight - 10) {
      top = Math.max(10, window.innerHeight - popH - 10);
    }

    popover.style.top = `${Math.round(top)}px`;
    popover.style.left = `${Math.round(left)}px`;

    if (options.matchTriggerWidth) {
      popover.style.width = `${Math.round(rect.width)}px`;
    }

    return { isSheet: false, backdrop: null };
  }

  /**
   * Arrastre continuo unificado (Mouse + Touch + Stylus) vía Pointer Events.
   */
  function bindDragValue(trackEl, onRatioChange, options = {}) {
    if (!trackEl || typeof onRatioChange !== 'function') return () => {};

    let dragging = false;

    const emitRatio = (clientX, clientY) => {
      const rect = trackEl.getBoundingClientRect();
      if (rect.width <= 0 || rect.height <= 0) return;
      const ratio = options.vertical
        ? (clientY - rect.top) / rect.height
        : (clientX - rect.left) / rect.width;
      const clamped = Math.max(0, Math.min(1, ratio));
      onRatioChange(clamped);
    };

    const onPointerDown = (e) => {
      if (e.button !== undefined && e.button !== 0 && e.pointerType === 'mouse') return;
      e.preventDefault();
      dragging = true;
      if (typeof trackEl.setPointerCapture === 'function' && e.pointerId !== undefined) {
        try { trackEl.setPointerCapture(e.pointerId); } catch (_) {}
      }
      emitRatio(e.clientX, e.clientY);
      if (typeof options.onStart === 'function') options.onStart();
    };

    const onPointerMove = (e) => {
      if (!dragging) return;
      emitRatio(e.clientX, e.clientY);
    };

    const onPointerUp = (e) => {
      if (!dragging) return;
      dragging = false;
      if (typeof trackEl.releasePointerCapture === 'function' && e.pointerId !== undefined) {
        try { trackEl.releasePointerCapture(e.pointerId); } catch (_) {}
      }
      if (typeof options.onEnd === 'function') options.onEnd();
    };

    trackEl.addEventListener('pointerdown', onPointerDown);
    trackEl.addEventListener('pointermove', onPointerMove);
    trackEl.addEventListener('pointerup', onPointerUp);
    trackEl.addEventListener('pointercancel', onPointerUp);

    return () => {
      trackEl.removeEventListener('pointerdown', onPointerDown);
      trackEl.removeEventListener('pointermove', onPointerMove);
      trackEl.removeEventListener('pointerup', onPointerUp);
      trackEl.removeEventListener('pointercancel', onPointerUp);
    };
  }

  /**
   * Swipe horizontal táctil (ej. cambiar de mes en calendario).
   */
  function bindSwipeHorizontal(el, onPrev, onNext, threshold = 48) {
    if (!el) return () => {};
    let startX = 0;
    let startY = 0;
    let tracking = false;

    const onTouchStart = (e) => {
      if (!e.touches || e.touches.length !== 1) return;
      tracking = true;
      startX = e.touches[0].clientX;
      startY = e.touches[0].clientY;
    };

    const onTouchEnd = (e) => {
      if (!tracking || !e.changedTouches || !e.changedTouches.length) return;
      tracking = false;
      const dx = e.changedTouches[0].clientX - startX;
      const dy = e.changedTouches[0].clientY - startY;
      if (Math.abs(dx) >= threshold && Math.abs(dx) > Math.abs(dy) * 1.4) {
        if (dx > 0 && typeof onPrev === 'function') onPrev();
        else if (dx < 0 && typeof onNext === 'function') onNext();
      }
    };

    el.addEventListener('touchstart', onTouchStart, { passive: true });
    el.addEventListener('touchend', onTouchEnd, { passive: true });

    return () => {
      el.removeEventListener('touchstart', onTouchStart);
      el.removeEventListener('touchend', onTouchEnd);
    };
  }

  /**
   * Observador unificado para inicializar componentes agregados por AJAX/Fetch o MutationObserver.
   */
  function observeDynamic(initFn) {
    if (typeof initFn !== 'function') return;

    const run = () => {
      try { initFn(); } catch (err) { console.warn('FmeUi init error:', err); }
    };

    run();

    ['previewUpdated', 'remoteContentUpdated', 'contentUpdated', 'functionalityReconnect'].forEach((evtName) => {
      document.addEventListener(evtName, run);
    });

    if (typeof MutationObserver !== 'undefined' && document.body) {
      let scheduled = false;
      const observer = new MutationObserver((mutations) => {
        if (scheduled) return;
        for (const m of mutations) {
          if (m.addedNodes && m.addedNodes.length > 0) {
            scheduled = true;
            requestAnimationFrame(() => {
              scheduled = false;
              run();
            });
            break;
          }
        }
      });
      observer.observe(document.body, { childList: true, subtree: true });
    }
  }

  function createSheetHandle() {
    const handle = document.createElement('div');
    handle.className = 'ui-sheet-handle';
    return handle;
  }

  window.FmeUi = {
    UI_CSS_VARS,
    ICONS,
    t: tr,
    sanitizeClassList,
    applyClasses,
    resolveColorValue,
    copyUiVars,
    isMobileTouchMode,
    positionFloating,
    bindDragValue,
    bindSwipeHorizontal,
    observeDynamic,
    createSheetHandle
  };
})();
