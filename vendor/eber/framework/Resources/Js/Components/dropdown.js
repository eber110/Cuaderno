/**
 * Select mejorado con búsqueda.
 * Incluye animaciones GSAP opcionales con clase 'animated'.
 * 
 * @function dropdown
 * @description Reemplaza selects nativos con una versión estilizable
 *              que incluye búsqueda y mejor UX.
 * 
 * @example
 * // HTML - Sin animación
 * <div class="dropdown-select" data-name="pais">
 *   <div class="dropdown-selected">Seleccionar...</div>
 *   <div class="dropdown-options hidden">...</div>
 * </div>
 * 
 * @example
 * // HTML - Con animación GSAP
 * <div class="dropdown-select animated" data-name="pais">
 *   <div class="dropdown-selected">Seleccionar...</div>
 *   <div class="dropdown-options hidden">...</div>
 * </div>
 * 
 * @css .dropdown-select - Contenedor principal
 * @css .dropdown-select.animated - Activa animaciones GSAP
 * @css .dropdown-selected - Muestra la opción seleccionada
 * @css .dropdown-options - Lista de opciones
 * @css .dropdown-option - Cada opción
 * @css .dropdown-search - Campo de búsqueda
 * 
 * @requires gsap - GreenSock Animation Platform (opcional)
 * @returns {void}
 */
export function dropdown() {
  // Verificar si existen elementos antes de configurar event listeners
  const dropdowns = document.querySelectorAll('.dropdown-select');
  if (!dropdowns.length) return;

  // Verificar si GSAP está disponible
  const hasGsap = typeof gsap !== 'undefined';

  // Configuración de animación
  const animConfig = {
    duration: 0.25,
    ease: 'power2.out',
    easeClose: 'power2.in'
  };

  /**
   * Anima la apertura del dropdown.
   * @param {HTMLElement} options - Contenedor de opciones
   */
  function animateOpen(options) {
    gsap.fromTo(options,
      { opacity: 0, y: -10, scale: 0.95 },
      {
        opacity: 1,
        y: 0,
        scale: 1,
        duration: animConfig.duration,
        ease: animConfig.ease
      }
    );
  }

  /**
   * Anima el cierre del dropdown.
   * @param {HTMLElement} options - Contenedor de opciones
   * @param {Function|null} onComplete - Callback al completar
   */
  function animateClose(options, onComplete = null) {
    gsap.to(options, {
      opacity: 0,
      y: -10,
      scale: 0.95,
      duration: animConfig.duration * 0.7,
      ease: animConfig.easeClose,
      onComplete: () => {
        options.classList.add('hidden');
        gsap.set(options, { clearProps: 'opacity,y,scale' });
        if (onComplete) onComplete();
      }
    });
  }

  /**
   * Cierra dropdown sin animación.
   * @param {HTMLElement} options - Contenedor de opciones
   */
  function closeWithoutAnimation(options) {
    options.classList.add('hidden');
  }

  // Aplicar overflow:visible automáticamente para evitar que las opciones sean recortadas
  dropdowns.forEach(dropdown => {
    dropdown.style.overflow = 'visible';
    dropdown.style.position = 'relative';
  });

  document.addEventListener('click', (e) => {
    if (!e.target || !e.target.closest) return;
    const selectedEl = e.target.closest('.dropdown-selected');

    if (selectedEl) {
      const dropdown = selectedEl.closest('.dropdown-select');
      const optionsEl = dropdown?.querySelector('.dropdown-options');
      const useAnimation = hasGsap && dropdown?.classList.contains('animated');

      if (optionsEl) {
        const isHidden = optionsEl.classList.contains('hidden');

        // Cerrar otros dropdowns
        document.querySelectorAll('.dropdown-options:not(.hidden)').forEach(opt => {
          if (opt !== optionsEl) {
            const parentDropdown = opt.closest('.dropdown-select');
            if (hasGsap && parentDropdown?.classList.contains('animated')) {
              animateClose(opt);
            } else {
              closeWithoutAnimation(opt);
            }
          }
        });

        // Toggle del dropdown actual
        if (isHidden) {
          optionsEl.classList.remove('hidden');
          if (useAnimation) {
            animateOpen(optionsEl);
          }
          // Focus en búsqueda si existe
          const search = optionsEl.querySelector('.dropdown-search');
          if (search) search.focus();
        } else {
          if (useAnimation) {
            animateClose(optionsEl);
          } else {
            closeWithoutAnimation(optionsEl);
          }
        }
      }
      return;
    }

    // Seleccionar opción
    const optionEl = e.target.closest('.dropdown-option');
    if (optionEl) {
      const dropdown = optionEl.closest('.dropdown-select');
      const selectedDisplay = dropdown?.querySelector('.dropdown-selected');
      const hiddenInput = dropdown?.querySelector('input[type="hidden"]');
      const optionsEl = dropdown?.querySelector('.dropdown-options');
      const useAnimation = hasGsap && dropdown?.classList.contains('animated');

      if (selectedDisplay) {
        selectedDisplay.textContent = optionEl.textContent;
        selectedDisplay.dataset.value = optionEl.dataset.value;
      }

      if (hiddenInput) {
        hiddenInput.value = optionEl.dataset.value;
        // Disparar evento change
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
      }

      dropdown?.querySelectorAll('.dropdown-option').forEach(opt => {
        opt.classList.remove('selected');
      });
      optionEl.classList.add('selected');

      if (optionsEl) {
        if (useAnimation) {
          animateClose(optionsEl);
        } else {
          closeWithoutAnimation(optionsEl);
        }
      }
      return;
    }

    // Cerrar dropdowns al hacer click fuera
    if (!e.target.closest('.dropdown-select')) {
      document.querySelectorAll('.dropdown-options:not(.hidden)').forEach(opt => {
        const parentDropdown = opt.closest('.dropdown-select');
        if (hasGsap && parentDropdown?.classList.contains('animated')) {
          animateClose(opt);
        } else {
          closeWithoutAnimation(opt);
        }
      });
    }
  });

  // Búsqueda
  document.addEventListener('input', (e) => {
    const search = e.target.closest('.dropdown-search');
    if (!search) return;

    const dropdown = search.closest('.dropdown-select');
    const query = search.value.toLowerCase();

    dropdown?.querySelectorAll('.dropdown-option').forEach(option => {
      const text = option.textContent.toLowerCase();
      option.style.display = text.includes(query) ? '' : 'none';
    });
  });
}
