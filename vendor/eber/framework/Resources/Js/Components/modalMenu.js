/**
 * Sistema de menús dropdown con soporte para hover y click.
 * Incluye animaciones GSAP para apertura y cierre.
 * 
 * @function modalMenu
 * @description Maneja menús contextuales que se posicionan automáticamente
 *              según el espacio disponible y el elemento activador.
 *              Soporta animaciones GSAP de apertura/cierre.
 * 
 * @example
 * // HTML - Activación por click (por defecto)
 * <button class="open-modal-menu left">Abrir</button>
 * <div class="content-modal-menu hidden">Contenido...</div>
 * 
 * @example
 * // HTML - Activación por hover
 * <button class="open-modal-menu on-mouse">Hover</button>
 * <div class="content-modal-menu hidden">Contenido...</div>
 * 
 * @css .open-modal-menu - Botón activador del menú
 * @css .open-modal-menu.active - Estado activo del botón (para animación hamburguesa)
 * @css .content-modal-menu - Contenedor del menú
 * @css .hidden - Clase para ocultar el menú
 * @css .left/.right/.bottom - Modificadores de posición
 * @css .on-mouse - Activa el modo hover en lugar de click
 * 
 * @requires gsap - GreenSock Animation Platform
 * @returns {void}
 */
export function modalMenu() {
  // Verificar si existen elementos antes de configurar event listeners
  if (!document.querySelector('.open-modal-menu')) return;

  // Verificar si GSAP está disponible
  const hasGsap = typeof gsap !== 'undefined';

  // Configuración de animación
  const animConfig = {
    duration: 0.15,
    ease: 'power2.out',
    openFromY: -15,
    openFromOpacity: 0,
    closeToY: -10,
    closeToOpacity: 0
  };

  /**
   * Anima la apertura del menú usando GSAP.
   * Solo anima si el botón tiene la clase 'animated'.
   * @param {HTMLElement} menu - Menú a animar
   * @param {HTMLElement} button - Botón activador
   * @returns {boolean} - true si se animó, false si no
   */
  function animateMenuOpen(menu, button) {
    // Solo animar si tiene clase 'animated'
    if (!hasGsap || !button.classList.contains('animated')) {
      button.classList.add('active');
      return false;
    }

    // Agregar clase active al botón para animación hamburguesa
    button.classList.add('active');

    gsap.fromTo(menu,
      {
        opacity: animConfig.openFromOpacity,
        y: animConfig.openFromY,
        scale: 0.95
      },
      {
        opacity: 1,
        y: 0,
        scale: 1,
        duration: animConfig.duration,
        ease: animConfig.ease
      }
    );
    return true;
  }

  /**
   * Anima el cierre del menú usando GSAP.
   * Solo anima si el botón tiene la clase 'animated'.
   * @param {HTMLElement} menu - Menú a animar
   * @param {HTMLElement|null} button - Botón activador (opcional)
   * @param {Function|null} onComplete - Callback al completar la animación
   */
  function animateMenuClose(menu, button = null, onComplete = null) {
    // Si no tiene clase 'animated' o no hay GSAP, cerrar sin animación
    const shouldAnimate = hasGsap && button && button.classList.contains('animated');

    if (!shouldAnimate) {
      menu.classList.add('hidden');
      if (button) button.classList.remove('active');
      if (onComplete) onComplete();
      return;
    }

    // Remover clase active del botón
    button.classList.remove('active');

    gsap.to(menu, {
      opacity: animConfig.closeToOpacity,
      y: animConfig.closeToY,
      scale: 0.95,
      duration: animConfig.duration * 0.7,
      ease: 'power2.in',
      onComplete: () => {
        menu.classList.add('hidden');
        // Resetear estilos para la próxima apertura
        gsap.set(menu, { clearProps: 'opacity,y,scale' });
        if (onComplete) onComplete();
      }
    });
  }

  /**
   * Posiciona y muestra el menú relativo al botón activador.
   * @param {HTMLElement} openButton - Botón que activa el menú
   * @param {HTMLElement} contentMenuModal - Contenedor del menú
   */
  function showMenu(openButton, contentMenuModal) {
    const classLocationLeft = openButton.classList.contains('left');
    const classLocationRight = openButton.classList.contains('right');
    const classLocationBottom = openButton.classList.contains('bottom');
    const location = openButton.getBoundingClientRect();

    contentMenuModal.style.position = "fixed";
    contentMenuModal.style.zIndex = "900";
    contentMenuModal.style.top = 'auto';
    contentMenuModal.style.bottom = 'auto';
    contentMenuModal.classList.add("p0");

    if (classLocationBottom) {
      const buttonTop = Math.floor(location.top);
      contentMenuModal.style.bottom = `${window.innerHeight - buttonTop}px`;
    } else {
      const buttonBottom = Math.floor(location.bottom);
      contentMenuModal.style.top = `${buttonBottom}px`;
    }

    contentMenuModal.style.left = 'auto';
    contentMenuModal.style.right = 'auto';
    contentMenuModal.style.textAlign = 'initial';

    const windowWidth = window.innerWidth;
    const buttonRight = Math.floor(windowWidth - location.right);
    const buttonLeft = Math.floor(location.left);

    if (classLocationLeft) {
      contentMenuModal.style.textAlign = 'left';
      contentMenuModal.style.left = `${buttonLeft}px`;
    } else if (classLocationRight) {
      contentMenuModal.style.textAlign = 'right';
      contentMenuModal.style.right = `${buttonRight}px`;
    } else {
      contentMenuModal.style.textAlign = 'left';
      contentMenuModal.style.left = `${buttonLeft}px`;
    }

    contentMenuModal.classList.remove('hidden');

    // Animar apertura
    animateMenuOpen(contentMenuModal, openButton);
  }

  /**
   * Cierra todos los menús abiertos excepto el especificado.
   * @param {HTMLElement|null} exceptMenu - Menú a mantener abierto (opcional)
   * @param {boolean} animate - Si debe animar el cierre (default: true)
   */
  function closeAllMenusExcept(exceptMenu = null, animate = true) {
    document.querySelectorAll('.content-modal-menu:not(.hidden)').forEach(menu => {
      if (menu !== exceptMenu) {
        const button = menu.previousElementSibling;
        if (animate && hasGsap) {
          animateMenuClose(menu, button);
        } else {
          menu.classList.add('hidden');
          if (button) button.classList.remove('active');
        }
      }
    });
  }

  // ========== SOPORTE PARA HOVER (clase 'on-mouse') ==========
  let hoverCloseTimeout = null;

  function cancelHoverTimeout() {
    if (hoverCloseTimeout) {
      clearTimeout(hoverCloseTimeout);
      hoverCloseTimeout = null;
    }
  }

  function scheduleMenuClose(menuModal) {
    cancelHoverTimeout();
    hoverCloseTimeout = setTimeout(() => {
      const button = menuModal.previousElementSibling;
      animateMenuClose(menuModal, button);
    }, 150);
  }

  // Mouseenter en el botón activador
  document.addEventListener("mouseenter", (e) => {
    if (!e.target || !e.target.closest) return;
    const openButton = e.target.closest('.open-modal-menu.on-mouse');
    if (openButton) {
      cancelHoverTimeout();
      const contentMenuModal = openButton.nextElementSibling;
      if (!contentMenuModal || !contentMenuModal.classList.contains('content-modal-menu')) {
        return;
      }
      closeAllMenusExcept(contentMenuModal);
      showMenu(openButton, contentMenuModal);
      return;
    }

    // Mouseenter en el menú
    const menuModal = e.target.closest('.content-modal-menu');
    if (menuModal) {
      const prevButton = menuModal.previousElementSibling;
      if (prevButton && prevButton.classList.contains('on-mouse')) {
        cancelHoverTimeout();
      }
    }
  }, true);

  // Mouseleave del botón activador
  document.addEventListener("mouseleave", (e) => {
    if (!e.target || !e.target.closest) return;
    const openButton = e.target.closest('.open-modal-menu.on-mouse');
    if (openButton) {
      const contentMenuModal = openButton.nextElementSibling;
      if (!contentMenuModal || !contentMenuModal.classList.contains('content-modal-menu')) {
        return;
      }
      const relatedTarget = e.relatedTarget;
      if (relatedTarget && (contentMenuModal.contains(relatedTarget) || contentMenuModal === relatedTarget)) {
        return;
      }
      scheduleMenuClose(contentMenuModal);
      return;
    }

    // Mouseleave del menú
    const menuModal = e.target.closest('.content-modal-menu');
    if (menuModal) {
      const prevButton = menuModal.previousElementSibling;
      if (!prevButton || !prevButton.classList.contains('on-mouse')) return;
      const relatedTarget = e.relatedTarget;
      if (relatedTarget && (prevButton.contains(relatedTarget) || prevButton === relatedTarget)) {
        return;
      }
      scheduleMenuClose(menuModal);
    }
  }, true);

  // ========== SOPORTE PARA CLICK (sin clase 'on-mouse') ==========
  document.addEventListener("click", (e) => {
    const event = e.target;
    const openButton = event.closest('.open-modal-menu');

    if (openButton) {
      e.preventDefault();
      if (openButton.classList.contains('on-mouse')) {
        return;
      }
      e.stopPropagation();
      const contentMenuModal = openButton.nextElementSibling;

      if (!contentMenuModal || !contentMenuModal.classList.contains('content-modal-menu')) {
        console.warn("Elemento '.content-modal-menu' no encontrado:", openButton);
        closeAllMenusExcept();
        return;
      }

      const isCurrentlyHidden = contentMenuModal.classList.contains('hidden');
      closeAllMenusExcept(contentMenuModal);

      if (isCurrentlyHidden) {
        showMenu(openButton, contentMenuModal);
      } else {
        // Animar cierre del menú
        animateMenuClose(contentMenuModal, openButton);
      }
    } else {
      if (!event.closest('.content-modal-menu:not(.hidden)')) {
        closeAllMenusExcept(null, true);
      }
    }
  });

  // Cerrar menús al hacer scroll o resize (sin animación para mejor rendimiento)
  window.addEventListener('scroll', (e) => {
    if (e.target && (e.target.classList?.contains('content-modal-menu') || (e.target.closest && e.target.closest('.content-modal-menu')))) {
      return;
    }
    closeAllMenusExcept(null, false);
  }, { passive: true });

  window.addEventListener('resize', () => {
    closeAllMenusExcept(null, false);
  }, { passive: true });
}
