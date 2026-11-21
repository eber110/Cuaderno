/**
 * Select mejorado con búsqueda, navegación por teclado, tokens CSS (`--ui-*`)
 * y adaptación Bottom-Sheet en móviles/pantallas táctiles.
 *
 * @function dropdown
 * @description Mejora contenedores `.dropdown-select` con búsqueda en tiempo real, teclado y sincronización automática con `<input type="hidden">`.
 *
 * @example
 * // HTML - Dropdown con campo hidden sincronizado y altura máxima configurable
 * <div class="dropdown-select" data-name="pais" data-max-height="240" data-required="true">
 *   <div class="dropdown-selected">Selecciona un país</div>
 *   <div class="dropdown-options">
 *     <div class="dropdown-option" data-value="ar">Argentina</div>
 *     <div class="dropdown-option" data-value="es">España</div>
 *     <div class="dropdown-option" data-value="mx">México</div>
 *   </div>
 * </div>
 *
 * @css .dropdown-select - Contenedor principal del selector desplegable
 * @css .dropdown-selected - Disparador que muestra la opción seleccionada
 * @css .dropdown-options - Lista de opciones disponibles
 * @css .dropdown-option - Cada ítem seleccionable (usa `data-value`)
 * @attribute data-name - Nombre del `<input type="hidden">` generado automáticamente (opcional)
 * @attribute data-max-height - Altura máxima del panel en píxeles (opcional)
 * @attribute data-empty-text - Texto mostrado cuando la búsqueda no arroja coincidencias (opcional)
 *
 * @returns {void}
 */
export function dropdown() {
  if (window.__fmeDropdownBound) return;
  window.__fmeDropdownBound = true;

  const ui = window.FmeUi || null;
  const tr = (k, p, f) => (ui ? ui.t(k, p, f) : (typeof window.t === 'function' ? window.t(k, p, f) : f));
  const hasGsap = typeof gsap !== 'undefined';

  let activeDropdown = null;
  let activeOptionsEl = null;
  let activeBackdrop = null;
  let activeOriginalParent = null;
  let activePlaceholderNode = null;

  function initDropdownElements() {
    document.querySelectorAll('.dropdown-select:not([data-dropdown-initialized])').forEach((el) => {
      el.dataset.dropdownInitialized = 'true';
      el.style.overflow = 'visible';
      el.style.position = 'relative';

      const selectedEl = el.querySelector('.dropdown-selected');
      const optionsEl = el.querySelector('.dropdown-options');
      if (!selectedEl || !optionsEl) return;

      const uiTrigger = el.getAttribute('ui-trigger') || '';
      const uiSurface = el.getAttribute('ui-surface') || '';
      if (ui) {
        ui.copyUiVars(el, selectedEl);
        if (uiTrigger) ui.applyClasses(selectedEl, uiTrigger);
        if (uiSurface) ui.applyClasses(optionsEl, uiSurface);
      }

      const maxHeightAttr = parseInt(el.getAttribute('data-max-height') || '', 10);
      if (Number.isFinite(maxHeightAttr) && maxHeightAttr >= 80) {
        optionsEl.style.maxHeight = `${maxHeightAttr}px`;
      }

      // Asegurar icono chevron si no existe ya
      if (!selectedEl.querySelector('.dropdown-chevron') && ui && ui.ICONS && ui.ICONS.chevronDown) {
        const chev = document.createElement('span');
        chev.className = 'dropdown-chevron';
        chev.style.display = 'inline-flex';
        chev.style.opacity = '0.7';
        chev.innerHTML = ui.ICONS.chevronDown;
        selectedEl.appendChild(chev);
      }

      // Asegurar input hidden asociado cuando tiene data-name
      const dataName = el.getAttribute('data-name');
      let hiddenInput = el.querySelector('input[type="hidden"]');
      if (dataName && !hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = dataName;
        el.appendChild(hiddenInput);
      }
      if (hiddenInput && (el.getAttribute('data-required') === 'true' || el.hasAttribute('required'))) {
        hiddenInput.dataset.uiRequired = 'true';
      }

      // Sincronizar opción pre-seleccionada o estado placeholder
      const preSelected = el.querySelector('.dropdown-option.selected');
      if (preSelected) {
        setSelectedDisplay(selectedEl, preSelected.textContent.trim(), preSelected.dataset.value || '');
        if (hiddenInput) hiddenInput.value = preSelected.dataset.value || '';
      } else {
        selectedEl.classList.add('is-placeholder');
      }

      el.addEventListener('fme:renderInlinePreview', (ev) => {
        const host = ev.detail?.host;
        if (!host) return;
        const sourceOpts = el.querySelector('.dropdown-options') || (activeDropdown === el ? activeOptionsEl : null);
        if (!sourceOpts) return;

        const clone = sourceOpts.cloneNode(true);
        clone.classList.remove('hidden', 'is-mobile-sheet');
        clone.classList.add('is-inline-preview');
        clone.style.top = '';
        clone.style.left = '';
        clone.style.width = '';
        if (Number.isFinite(maxHeightAttr) && maxHeightAttr >= 80) {
          clone.style.maxHeight = `${maxHeightAttr}px`;
        }
        if (ui) {
          ui.copyUiVars(el, clone);
          if (!clone.querySelector('.ui-sheet-handle')) {
            clone.prepend(ui.createSheetHandle());
          }
        }
        const cloneOpts = clone.querySelectorAll('.dropdown-option');
        if (cloneOpts.length && !clone.querySelector('.dropdown-option.selected')) {
          cloneOpts[0].classList.add('selected');
        }
        clone.addEventListener('click', (clickEv) => {
          const opt = clickEv.target.closest('.dropdown-option');
          if (!opt) return;
          clickEv.stopPropagation();
          clone.querySelectorAll('.dropdown-option').forEach((o) => o.classList.remove('selected', 'is-focused'));
          opt.classList.add('selected');
          const val = opt.dataset.value ?? '';
          const txt = opt.textContent.trim();
          setSelectedDisplay(selectedEl, txt, val);
          if (hiddenInput) {
            hiddenInput.value = val;
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });
        host.innerHTML = '';
        host.appendChild(clone);
        if (ui && typeof ui.attachFloatingScroll === 'function') {
          ui.attachFloatingScroll(clone, el);
        }
      });
    });
  }

  function setSelectedDisplay(selectedEl, label, value) {
    const chev = selectedEl.querySelector('.dropdown-chevron');
    selectedEl.textContent = label;
    selectedEl.dataset.value = value;
    selectedEl.classList.toggle('is-placeholder', !value);
    if (chev) selectedEl.appendChild(chev);
  }

  function closeActiveDropdown() {
    if (!activeOptionsEl) return;
    const opts = activeOptionsEl;
    const bd = activeBackdrop;
    const origParent = activeOriginalParent;
    const ph = activePlaceholderNode;
    const dd = activeDropdown;

    activeDropdown = null;
    activeOptionsEl = null;
    activeBackdrop = null;
    activeOriginalParent = null;
    activePlaceholderNode = null;

    if (ui && typeof ui.detachFloatingScroll === 'function') {
      ui.detachFloatingScroll(opts);
    }

    const finalize = () => {
      opts.classList.add('hidden');
      opts.classList.remove('is-mobile-sheet');
      opts.style.top = '';
      opts.style.left = '';
      opts.style.width = '';
      if (origParent) {
        if (ph && ph.parentNode === origParent) {
          origParent.replaceChild(opts, ph);
        } else {
          origParent.appendChild(opts);
        }
      }
      if (bd && bd.parentNode) bd.parentNode.removeChild(bd);
    };

    const useAnim = hasGsap && dd && dd.classList.contains('animated');
    if (useAnim) {
      gsap.to(opts, {
        opacity: 0,
        y: -8,
        duration: 0.16,
        ease: 'power2.in',
        onComplete: () => {
          gsap.set(opts, { clearProps: 'opacity,y,scale' });
          finalize();
        }
      });
    } else {
      finalize();
    }
  }

  function openDropdown(dropdownEl, selectedEl, optionsEl) {
    if (activeOptionsEl === optionsEl) {
      closeActiveDropdown();
      return;
    }

    closeActiveDropdown();

    activeDropdown = dropdownEl;
    activeOptionsEl = optionsEl;
    activeOriginalParent = optionsEl.parentNode;
    activePlaceholderNode = document.createComment('dropdown-options-anchor');
    activeOriginalParent.replaceChild(activePlaceholderNode, optionsEl);

    if (ui) ui.copyUiVars(dropdownEl, optionsEl);

    // Insertar handle táctil para modo bottom-sheet si no existe aún
    if (ui && !optionsEl.querySelector('.ui-sheet-handle')) {
      optionsEl.prepend(ui.createSheetHandle());
    }

    optionsEl.classList.remove('hidden');
    document.body.appendChild(optionsEl);

    if (ui && typeof ui.positionFloating === 'function') {
      const pos = ui.positionFloating(optionsEl, selectedEl, {
        sourceEl: dropdownEl,
        matchTriggerWidth: true,
        fallbackWidth: selectedEl.getBoundingClientRect().width || 240,
        fallbackHeight: 260,
        onBackdropClick: closeActiveDropdown
      });
      activeBackdrop = pos.backdrop;
    } else {
      const rect = selectedEl.getBoundingClientRect();
      optionsEl.style.top = `${rect.bottom + 4}px`;
      optionsEl.style.left = `${rect.left}px`;
      optionsEl.style.width = `${rect.width}px`;
    }

    const useAnim = hasGsap && dropdownEl.classList.contains('animated');
    if (useAnim) {
      gsap.fromTo(optionsEl, { opacity: 0, y: -8 }, { opacity: 1, y: 0, duration: 0.2, ease: 'power2.out' });
    }

    if (ui && typeof ui.attachFloatingScroll === 'function') {
      ui.attachFloatingScroll(optionsEl, dropdownEl);
    }

    const search = optionsEl.querySelector('.dropdown-search');
    if (search) {
      search.value = '';
      optionsEl.querySelectorAll('.dropdown-option').forEach((o) => { o.style.display = ''; });
      const emptyEl = optionsEl.querySelector('.dropdown-empty');
      if (emptyEl) emptyEl.remove();
      if (optionsEl.__floatingScrollInstance) optionsEl.__floatingScrollInstance.refresh();
      setTimeout(() => search.focus({ preventScroll: true }), 20);
    }
  }

  function selectOption(dropdownEl, optionEl) {
    const selectedDisplay = dropdownEl.querySelector('.dropdown-selected');
    const hiddenInput = dropdownEl.querySelector('input[type="hidden"]');
    const val = optionEl.dataset.value ?? '';
    const txt = optionEl.textContent.trim();

    if (selectedDisplay) {
      setSelectedDisplay(selectedDisplay, txt, val);
    }
    if (hiddenInput) {
      hiddenInput.value = val;
      hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const sourceList = activeOptionsEl || dropdownEl;
    sourceList.querySelectorAll('.dropdown-option').forEach((opt) => opt.classList.remove('selected', 'is-focused'));
    optionEl.classList.add('selected');

    dropdownEl.dispatchEvent(new CustomEvent('dropdownChange', { bubbles: true, detail: { value: val, label: txt } }));
    closeActiveDropdown();
  }

  document.addEventListener('click', (e) => {
    if (!e.target || !e.target.closest) return;

    const selectedEl = e.target.closest('.dropdown-selected');
    if (selectedEl) {
      const dd = selectedEl.closest('.dropdown-select');
      const opts = dd?.querySelector('.dropdown-options') || (activeDropdown === dd ? activeOptionsEl : null);
      if (dd && opts) {
        e.stopPropagation();
        openDropdown(dd, selectedEl, opts);
      }
      return;
    }

    const optionEl = e.target.closest('.dropdown-option');
    if (optionEl && activeDropdown) {
      e.stopPropagation();
      selectOption(activeDropdown, optionEl);
      return;
    }

    if (e.target.closest('.dropdown-options')) {
      return;
    }

    if (activeOptionsEl) {
      closeActiveDropdown();
    }
  });

  document.addEventListener('input', (e) => {
    const search = e.target.closest('.dropdown-search');
    if (!search) return;

    const optsRoot = search.closest('.dropdown-options');
    if (!optsRoot) return;

    const query = search.value.trim().toLowerCase();
    let visibleCount = 0;

    optsRoot.querySelectorAll('.dropdown-option').forEach((option) => {
      const text = option.textContent.toLowerCase();
      const match = text.includes(query);
      option.style.display = match ? '' : 'none';
      option.classList.remove('is-focused');
      if (match) visibleCount++;
    });

    let emptyEl = optsRoot.querySelector('.dropdown-empty');
    if (visibleCount === 0) {
      if (!emptyEl) {
        emptyEl = document.createElement('div');
        emptyEl.className = 'dropdown-empty';
        const customEmpty = activeDropdown ? activeDropdown.getAttribute('data-empty-text') : '';
        emptyEl.textContent = customEmpty || tr('no_results', {}, 'Sin resultados');
        optsRoot.appendChild(emptyEl);
      }
    } else if (emptyEl) {
      emptyEl.remove();
    }

    if (optsRoot.__floatingScrollInstance) {
      optsRoot.__floatingScrollInstance.refresh();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (!activeOptionsEl || !activeDropdown) return;

    if (e.key === 'Escape') {
      e.preventDefault();
      closeActiveDropdown();
      return;
    }

    const visibleOptions = Array.from(activeOptionsEl.querySelectorAll('.dropdown-option')).filter(
      (o) => o.style.display !== 'none'
    );
    if (!visibleOptions.length) return;

    let idx = visibleOptions.findIndex((o) => o.classList.contains('is-focused'));

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (idx >= 0) visibleOptions[idx].classList.remove('is-focused');
      idx = (idx + 1) % visibleOptions.length;
      visibleOptions[idx].classList.add('is-focused');
      visibleOptions[idx].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (idx >= 0) visibleOptions[idx].classList.remove('is-focused');
      idx = (idx - 1 + visibleOptions.length) % visibleOptions.length;
      visibleOptions[idx].classList.add('is-focused');
      visibleOptions[idx].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter' && idx >= 0) {
      e.preventDefault();
      selectOption(activeDropdown, visibleOptions[idx]);
    }
  });

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initDropdownElements);
  } else {
    initDropdownElements();
    document.addEventListener('previewUpdated', initDropdownElements);
  }
}
