/**
 * Menú hamburguesa para navegación móvil.
 * 
 * @function hamburgerMenu
 * @description Implementa un menú de navegación móvil que se activa mediante
 *              un botón hamburguesa animado. Incluye animaciones GSAP opcionales,
 *              cierre con overlay y bloqueo de scroll del body.
 * 
 * @example
 * // HTML - Estructura básica
 * <button class="hamburger-toggle" aria-label="Menú">
 *   <span class="hamburger-line"></span>
 *   <span class="hamburger-line"></span>
 *   <span class="hamburger-line"></span>
 * </button>
 * <nav class="hamburger-menu hidden">
 *   <a href="#">Inicio</a>
 *   <a href="#">Servicios</a>
 *   <a href="#">Contacto</a>
 * </nav>
 * 
 * @example
 * // HTML - Con overlay y animación
 * <button class="hamburger-toggle" aria-label="Menú">
 *   <span class="hamburger-line"></span>
 *   <span class="hamburger-line"></span>
 *   <span class="hamburger-line"></span>
 * </button>
 * <nav class="hamburger-menu animated with-overlay hidden">
 *   <div class="hamburger-content">
 *     <a href="#">Enlace 1</a>
 *     <a href="#">Enlace 2</a>
 *   </div>
 * </nav>
 * 
 * @css .hamburger-toggle - Botón activador del menú hamburguesa
 * @css .hamburger-toggle.active - Estado activo (menú abierto)
 * @css .hamburger-line - Líneas del ícono hamburguesa
 * @css .hamburger-menu - Contenedor del menú de navegación
 * @css .hamburger-menu.with-overlay - Añade overlay de fondo
 * @css .hamburger-menu.from-left - Menú desliza desde la izquierda
 * @css .hamburger-menu.from-right - Menú desliza desde la derecha (por defecto)
 * @css .hidden - Clase para ocultar el menú
 * @css .hamburger-menu.animated - Habilita animaciones GSAP de deslizamiento
 * 
 * @requires gsap - GreenSock Animation Platform (opcional)
 * @returns {void}
 */
export function hamburgerMenu() {
  const toggleButtons = document.querySelectorAll('.hamburger-toggle');

  if (!toggleButtons.length) return;

  // Verificar si GSAP está disponible
  const hasGsap = typeof gsap !== 'undefined';

  // Configuración de animación
  const animConfig = {
    duration: 0.35,
    ease: 'power2.out',
    stagger: 0.05
  };

  /**
   * Crea el overlay de fondo si el menú tiene la clase with-overlay.
   * @param {HTMLElement} menu - Menú que requiere overlay
   * @returns {HTMLElement|null} - Elemento overlay creado o null
   */
  function createOverlay(menu) {
    if (!menu.classList.contains('with-overlay')) return null;

    let overlay = menu.querySelector('.hamburger-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'hamburger-overlay';
      overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: -1;
        opacity: 0;
        transition: opacity 0.3s ease;
      `;
      menu.insertBefore(overlay, menu.firstChild);
    }
    return overlay;
  }

  /**
   * Bloquea o desbloquea el scroll del body.
   * @param {boolean} lock - true para bloquear, false para desbloquear
   */
  function toggleBodyScroll(lock) {
    document.body.style.overflow = lock ? 'hidden' : '';
  }

  /**
   * Anima la apertura del menú usando GSAP.
   * @param {HTMLElement} menu - Menú a animar
   * @param {HTMLElement} button - Botón activador
   */
  function animateMenuOpen(menu, button) {
    const isFromLeft = menu.classList.contains('from-left');
    const overlay = createOverlay(menu);
    const content = menu.querySelector('.hamburger-content') || menu;

    menu.classList.remove('hidden');
    button.classList.add('active');
    toggleBodyScroll(true);

    if (!hasGsap || !menu.classList.contains('animated')) {
      menu.style.transform = 'translateX(0)';
      if (overlay) overlay.style.opacity = '1';
      return;
    }

    // Animación GSAP
    const translateFrom = isFromLeft ? '-100%' : '100%';

    gsap.set(menu, {
      x: translateFrom,
      visibility: 'visible'
    });

    gsap.to(menu, {
      x: 0,
      duration: animConfig.duration,
      ease: animConfig.ease
    });

    if (overlay) {
      gsap.to(overlay, {
        opacity: 1,
        duration: animConfig.duration
      });
    }
  }

  /**
   * Anima el cierre del menú usando GSAP.
   * @param {HTMLElement} menu - Menú a animar
   * @param {HTMLElement} button - Botón activador
   */
  function animateMenuClose(menu, button) {
    const isFromLeft = menu.classList.contains('from-left');
    const overlay = menu.querySelector('.hamburger-overlay');

    button.classList.remove('active');

    if (!hasGsap || !menu.classList.contains('animated')) {
      menu.classList.add('hidden');
      menu.style.transform = '';
      if (overlay) overlay.style.opacity = '0';
      toggleBodyScroll(false);
      return;
    }

    const translateTo = isFromLeft ? '-100%' : '100%';

    if (overlay) {
      gsap.to(overlay, {
        opacity: 0,
        duration: animConfig.duration * 0.7
      });
    }

    gsap.to(menu, {
      x: translateTo,
      duration: animConfig.duration * 0.8,
      ease: 'power2.in',
      onComplete: () => {
        menu.classList.add('hidden');
        gsap.set(menu, { clearProps: 'x,visibility' });
        toggleBodyScroll(false);
      }
    });
  }

  /**
   * Alterna el estado del menú (abrir/cerrar).
   * @param {HTMLElement} button - Botón activador
   * @param {HTMLElement} menu - Menú a alternar
   */
  function toggleMenu(button, menu) {
    const isHidden = menu.classList.contains('hidden');

    if (isHidden) {
      animateMenuOpen(menu, button);
    } else {
      animateMenuClose(menu, button);
    }
  }

  /**
   * Cierra todos los menús hamburguesa abiertos.
   */
  function closeAllMenus() {
    document.querySelectorAll('.hamburger-menu:not(.hidden)').forEach(menu => {
      const button = menu.previousElementSibling;
      if (button && button.classList.contains('hamburger-toggle')) {
        animateMenuClose(menu, button);
      }
    });
  }

  // Event listeners para cada botón hamburguesa
  toggleButtons.forEach(button => {
    const menu = button.nextElementSibling;

    if (!menu || !menu.classList.contains('hamburger-menu')) {
      console.warn('hamburgerMenu: No se encontró .hamburger-menu después de .hamburger-toggle:', button);
      return;
    }

    // Click en el botón hamburguesa
    button.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleMenu(button, menu);
    });

    // Click en el overlay para cerrar
    const overlay = createOverlay(menu);
    if (overlay) {
      overlay.addEventListener('click', () => {
        animateMenuClose(menu, button);
      });
    }

    // Click en enlaces del menú para cerrar automáticamente
    menu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        animateMenuClose(menu, button);
      });
    });

    // Click en botón de cerrar (.closed-hamburger) para cerrar el menú
    menu.querySelectorAll('.closed-hamburger').forEach(closeBtn => {
      closeBtn.addEventListener('click', () => {
        animateMenuClose(menu, button);
      });
    });
  });

  // Cerrar menús al presionar Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeAllMenus();
    }
  });

  // Cerrar menús al hacer resize si la pantalla pasa a desktop
  const desktopQuery = window.matchMedia('(min-width: 993px)');

  function handleDesktopChange(e) {
    if (e.matches) {
      closeAllMenus();
    }
  }

  // Cerrar inmediatamente si ya está en desktop
  if (desktopQuery.matches) {
    closeAllMenus();
  }

  // Escuchar cambios de media query
  desktopQuery.addEventListener('change', handleDesktopChange);
}
