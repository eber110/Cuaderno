/**
 * Menú hamburguesa para navegación móvil.
 * 
 * @function hamburgerMenu
 * @description Implementa un menú de navegación móvil que se activa mediante
 *              cualquier elemento con la clase .hamburger-toggle (button, div, span, p, a, etc.).
 *              El activador no requiere ser un elemento <button> específico; funciona
 *              con cualquier etiqueta HTML mediante clases CSS y atributos opcionales
 *              (data-target, aria-controls, o por proximidad DOM).
 *              Incluye animaciones GSAP opcionales, cierre con overlay, bloqueo de scroll
 *              del body y soporte completo de accesibilidad (teclado / roles ARIA).
 * 
 * @example
 * // HTML - Activador como botón
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
 * // HTML - Activador como div, span o párrafo (con data-target opcional)
 * <div class="hamburger-toggle" data-target="#miMenu" aria-label="Abrir Menú">
 *   <span class="hamburger-line"></span>
 *   <span class="hamburger-line"></span>
 *   <span class="hamburger-line"></span>
 * </div>
 * <nav id="miMenu" class="hamburger-menu animated with-overlay hidden">
 *   <div class="hamburger-content">
 *     <span class="closed-hamburger" aria-label="Cerrar">✕</span>
 *     <a href="#">Enlace 1</a>
 *     <a href="#">Enlace 2</a>
 *   </div>
 * </nav>
 * 
 * @css .hamburger-toggle - Elemento activador del menú hamburguesa (cualquier etiqueta)
 * @css .hamburger-toggle.active - Estado activo (menú abierto)
 * @css .hamburger-line - Líneas del ícono hamburguesa
 * @css .hamburger-menu - Contenedor del menú de navegación
 * @css .hamburger-menu.with-overlay - Añade overlay de fondo
 * @css .hamburger-menu.from-left - Menú desliza desde la izquierda
 * @css .hamburger-menu.from-right - Menú desliza desde la derecha (por defecto)
 * @css .hidden - Clase para ocultar el menú
 * @css .hamburger-menu.animated - Habilita animaciones GSAP de deslizamiento
 * @css .closed-hamburger - Elemento interno para cerrar el menú (cualquier etiqueta)
 * 
 * @requires gsap - GreenSock Animation Platform (opcional)
 * @returns {void}
 */
export function hamburgerMenu() {
  const toggles = document.querySelectorAll('.hamburger-toggle');

  if (!toggles.length) return;

  // Verificar si GSAP está disponible
  const hasGsap = typeof gsap !== 'undefined';

  // Configuración de animación
  const animConfig = {
    duration: 0.35,
    ease: 'power2.out',
    stagger: 0.05
  };

  /**
   * Encuentra el menú correspondiente a un elemento activador.
   * Admite resolución por:
   * 1. Atributo data-target o data-menu (ej: data-target="#miMenu" o data-target=".hamburger-menu")
   * 2. Atributo aria-controls (ej: aria-controls="miMenuId")
   * 3. Atributo href (ej: href="#miMenuId")
   * 4. Hermano siguiente directo (.nextElementSibling)
   * 5. Hermano siguiente del contenedor padre
   * 6. Menú dentro del contenedor/header más cercano
   * 7. Fallback: primer .hamburger-menu en el DOM
   * 
   * @param {HTMLElement} toggle - Elemento activador
   * @returns {HTMLElement|null} - Elemento menú encontrado o null
   */
  function findMenuForToggle(toggle) {
    // 1. data-target o data-menu
    const targetSelector = toggle.getAttribute('data-target') || toggle.getAttribute('data-menu');
    if (targetSelector) {
      const targetEl = document.querySelector(targetSelector);
      if (targetEl) return targetEl;
    }

    // 2. aria-controls
    const ariaControls = toggle.getAttribute('aria-controls');
    if (ariaControls) {
      const controlledEl = document.getElementById(ariaControls);
      if (controlledEl) return controlledEl;
    }

    // 3. href (para enlaces <a>)
    const href = toggle.getAttribute('href');
    if (href && href.startsWith('#') && href.length > 1) {
      const hrefEl = document.querySelector(href);
      if (hrefEl) return hrefEl;
    }

    // 4. Hermano siguiente directo con clase .hamburger-menu
    if (toggle.nextElementSibling && toggle.nextElementSibling.classList.contains('hamburger-menu')) {
      return toggle.nextElementSibling;
    }

    // 5. Hermano siguiente del padre
    if (toggle.parentElement && toggle.parentElement.nextElementSibling && toggle.parentElement.nextElementSibling.classList.contains('hamburger-menu')) {
      return toggle.parentElement.nextElementSibling;
    }

    // 6. Menú dentro del contenedor común más cercano
    const container = toggle.closest('header, nav, .header, .nav, .navbar, .nav-panel, [class*="nav"], body');
    if (container) {
      const menuInContainer = container.querySelector('.hamburger-menu');
      if (menuInContainer) return menuInContainer;
    }

    // 7. Fallback global
    return document.querySelector('.hamburger-menu');
  }

  /**
   * Obtiene todos los activadores asociados a un menú específico.
   * @param {HTMLElement} menu - Menú de navegación
   * @returns {HTMLElement[]} - Lista de elementos activadores
   */
  function getTogglesForMenu(menu) {
    const menuId = menu.id ? `#${menu.id}` : null;
    const result = [];

    toggles.forEach(toggle => {
      const target = toggle.getAttribute('data-target') || toggle.getAttribute('data-menu');
      const aria = toggle.getAttribute('aria-controls');
      const href = toggle.getAttribute('href');

      if (menuId && (target === menuId || aria === menu.id || href === menuId)) {
        result.push(toggle);
      } else if (findMenuForToggle(toggle) === menu) {
        result.push(toggle);
      }
    });

    return result.length ? result : Array.from(toggles);
  }

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
   * Anima la apertura del menú usando GSAP o transiciones CSS.
   * @param {HTMLElement} menu - Menú a animar
   * @param {HTMLElement|null} toggle - Elemento activador que disparó la acción
   */
  function animateMenuOpen(menu, toggle = null) {
    const isFromLeft = menu.classList.contains('from-left');
    const overlay = createOverlay(menu);

    // Marcar menú como en proceso de apertura y deshabilitar pointer-events temporalmente
    menu.dataset.menuOpening = 'true';
    menu.style.pointerEvents = 'none';

    menu.classList.remove('hidden');

    // Sincronizar estado visual y accesibilidad en los activadores
    const relatedToggles = toggle ? [toggle] : getTogglesForMenu(menu);
    relatedToggles.forEach(t => {
      t.classList.add('active');
      t.setAttribute('aria-expanded', 'true');
    });

    toggleBodyScroll(true);

    const enablePointerEvents = () => {
      menu.dataset.menuOpening = 'false';
      menu.style.pointerEvents = 'auto';
    };

    if (!hasGsap || !menu.classList.contains('animated')) {
      menu.style.transform = 'translateX(0)';
      if (overlay) overlay.style.opacity = '1';
      setTimeout(enablePointerEvents, 350);
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
      ease: animConfig.ease,
      onComplete: enablePointerEvents
    });

    if (overlay) {
      gsap.to(overlay, {
        opacity: 1,
        duration: animConfig.duration
      });
    }
  }

  /**
   * Anima el cierre del menú usando GSAP o transiciones CSS.
   * @param {HTMLElement} menu - Menú a animar
   * @param {HTMLElement|null} toggle - Elemento activador
   */
  function animateMenuClose(menu, toggle = null) {
    const isFromLeft = menu.classList.contains('from-left');
    const overlay = menu.querySelector('.hamburger-overlay');

    // Marcar menú como no disponible inmediatamente al cerrar
    menu.dataset.menuOpening = 'true';
    menu.style.pointerEvents = 'none';

    // Sincronizar estado visual y accesibilidad en los activadores
    const relatedToggles = toggle ? [toggle] : getTogglesForMenu(menu);
    relatedToggles.forEach(t => {
      t.classList.remove('active');
      t.setAttribute('aria-expanded', 'false');
    });

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
   * @param {HTMLElement} toggle - Elemento activador
   * @param {HTMLElement} menu - Menú a alternar
   */
  function toggleMenu(toggle, menu) {
    const isHidden = menu.classList.contains('hidden');

    if (isHidden) {
      animateMenuOpen(menu, toggle);
    } else {
      animateMenuClose(menu, toggle);
    }
  }

  /**
   * Cierra todos los menús hamburguesa abiertos.
   */
  function closeAllMenus() {
    document.querySelectorAll('.hamburger-menu:not(.hidden)').forEach(menu => {
      animateMenuClose(menu);
    });

    // Asegurar que todos los activadores queden inactivos
    toggles.forEach(toggle => {
      toggle.classList.remove('active');
      toggle.setAttribute('aria-expanded', 'false');
    });
  }

  // Configuración de cada activador (.hamburger-toggle)
  toggles.forEach(toggle => {
    const menu = findMenuForToggle(toggle);

    if (!menu) {
      console.warn('hamburgerMenu: No se encontró .hamburger-menu para el activador:', toggle);
      return;
    }

    // Configurar accesibilidad si no es un <button> nativo
    if (toggle.tagName !== 'BUTTON' && toggle.tagName !== 'A') {
      if (!toggle.hasAttribute('role')) {
        toggle.setAttribute('role', 'button');
      }
      if (!toggle.hasAttribute('tabindex')) {
        toggle.setAttribute('tabindex', '0');
      }
    }

    // Atributo aria-expanded inicial y desactivación de pointerEvents si está oculto
    const isInitialHidden = menu.classList.contains('hidden');
    toggle.setAttribute('aria-expanded', isInitialHidden ? 'false' : 'true');
    if (isInitialHidden) {
      menu.style.pointerEvents = 'none';
    }

    // Manejador unificado para click y touchstart
    const toggleHandler = (e) => {
      e.stopPropagation();
      
      // Evitar que el clic sintético se dispare después del touchstart cancelando el evento táctil
      if (e.type === 'touchstart') {
        if (e.cancelable) {
          e.preventDefault();
        }
        toggle._touchHandled = true;
      } else if (e.type === 'click' && toggle._touchHandled) {
        toggle._touchHandled = false;
        return;
      }
      
      toggleMenu(toggle, menu);
    };

    // Registrar ambos eventos
    toggle.addEventListener('click', toggleHandler);
    toggle.addEventListener('touchstart', toggleHandler, { passive: false });

    // Accesibilidad por teclado (Enter / Espacio) para cualquier tipo de elemento
    toggle.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        e.stopPropagation();
        toggleMenu(toggle, menu);
      }
    });

    // Click en el overlay para cerrar
    const overlay = createOverlay(menu);
    if (overlay && !overlay._hasHamburgerListener) {
      overlay._hasHamburgerListener = true;
      overlay.addEventListener('click', () => {
        animateMenuClose(menu, toggle);
      });
    }

    // Click en enlaces del menú para cerrar automáticamente
    menu.querySelectorAll('a').forEach(link => {
      if (!link._hasHamburgerListener) {
        link._hasHamburgerListener = true;
        link.addEventListener('click', (e) => {
          // Si el menú se está abriendo o pointer-events está desactivado, ignorar y prevenir navegación accidental
          if (menu.dataset.menuOpening === 'true' || menu.style.pointerEvents === 'none') {
            e.preventDefault();
            e.stopPropagation();
            return;
          }
          animateMenuClose(menu, toggle);
        });
      }
    });

    // Click en cualquier elemento de cerrar (.closed-hamburger) dentro del menú
    menu.querySelectorAll('.closed-hamburger').forEach(closeEl => {
      // Accesibilidad si no es un botón nativo
      if (closeEl.tagName !== 'BUTTON' && closeEl.tagName !== 'A') {
        if (!closeEl.hasAttribute('role')) {
          closeEl.setAttribute('role', 'button');
        }
        if (!closeEl.hasAttribute('tabindex')) {
          closeEl.setAttribute('tabindex', '0');
        }
      }

      if (!closeEl._hasHamburgerListener) {
        closeEl._hasHamburgerListener = true;
        
        const closeHandler = (e) => {
          e.stopPropagation();
          if (e.type === 'touchstart') {
            closeEl._touchHandled = true;
          } else if (e.type === 'click' && closeEl._touchHandled) {
            closeEl._touchHandled = false;
            return;
          }
          animateMenuClose(menu, toggle);
        };

        closeEl.addEventListener('click', closeHandler);
        closeEl.addEventListener('touchstart', closeHandler, { passive: true });

        closeEl.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            e.stopPropagation();
            animateMenuClose(menu, toggle);
          }
        });
      }
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
