/**
 * Componente Vertical Menu - Módulo de menús de navegación colapsables para sidebar.
 * Soporta transiciones animadas con GSAP si el contenedor tiene la clase 'animated'.
 * Permite definir clases personalizadas para los estados activos mediante atributos
 * active-principal y active-item en el contenedor principal.
 * Guarda y restaura de forma automática el estado del menú y el contenido activo
 * en localStorage ante recargas de página o navegaciones.
 *
 * @function verticalMenu
 * @description Activa menús verticales con acordeones que expanden sub-ítems
 *              y administran clases de estado activo tanto para cabeceras
 *              como para sub-enlaces.
 *
 * @example
 * <div class="vertical-menu animated w100" active-principal="bg-active-header" active-item="bg-active-link">
 *   <div class="vertical-menu-item flex-column w100">
 *     <div class="vertical-menu-header w100 pointer">
 *       <p>Diseño</p>
 *       <p class="arrow-icon">v</p>
 *     </div>
 *     <div class="vertical-menu-content w100 hidden">
 *       <p class="vertical-menu-link active" data-remote="c1">Cabecera</p>
 *       <p class="vertical-menu-link" data-remote="c2">Fondo</p>
 *     </div>
 *   </div>
 *   <p class="vertical-menu-link">Contenido</p>
 * </div>
 *
 * @css .vertical-menu          - Contenedor principal
 * @css .vertical-menu.animated - Activa transiciones GSAP
 * @css .vertical-menu-item     - Grupo contenedor de cabecera y contenido
 * @css .vertical-menu-header   - Botón cabecera para expandir/colapsar
 * @css .vertical-menu-content  - Contenedor de sub-enlaces (colapsable)
 * @css .vertical-menu-link     - Enlace clickeable (sub-item o item raíz)
 * @css .active                 - Estado activo (default, si no se definen atributos custom)
 * @css .open                   - Estado de grupo abierto/desplegado
 * @css .hidden                 - Estado oculto
 */
export function verticalMenu() {
  const hasGsap = typeof gsap !== 'undefined';

  const animConfig = {
    duration: 0.3,
    ease: 'power2.out',
    easeClose: 'power2.in'
  };

  function animateOpen(content, item) {
    item.classList.add('open');
    content.classList.remove('hidden');
    const height = content.scrollHeight;

    gsap.fromTo(content,
      { height: 0, opacity: 0 },
      {
        height: height,
        opacity: 1,
        duration: animConfig.duration,
        ease: animConfig.ease,
        onComplete: () => {
          content.style.height = 'auto';
        }
      }
    );
  }

  function animateClose(content, item) {
    gsap.to(content, {
      height: 0,
      opacity: 0,
      duration: animConfig.duration * 0.7,
      ease: animConfig.easeClose,
      onComplete: () => {
        item.classList.remove('open');
        content.classList.add('hidden');
        gsap.set(content, { clearProps: 'height,opacity' });
      }
    });
  }

  function openWithoutAnimation(content, item) {
    item.classList.add('open');
    content.classList.remove('hidden');
    content.style.maxHeight = content.scrollHeight + 'px';
  }

  function closeWithoutAnimation(content, item) {
    item.classList.remove('open');
    content.style.maxHeight = null;
    content.classList.add('hidden');
  }

  // Delegación de click
  document.addEventListener('click', (e) => {
    // 1. Click en cabecera del sub-menú (.vertical-menu-header)
    const header = e.target.closest('.vertical-menu-header');
    if (header) {
      const item = header.closest('.vertical-menu-item');
      const menu = header.closest('.vertical-menu');
      if (!item || !menu) return;

      const content = item.querySelector('.vertical-menu-content');
      if (!content) return;

      const isOpen = item.classList.contains('open');
      const useAnimation = hasGsap && menu.classList.contains('animated');

      // Cerrar otros grupos si no está en modo 'multi' (por defecto colapsa los demás)
      if (!menu.classList.contains('multi')) {
        menu.querySelectorAll('.vertical-menu-item.open').forEach(openItem => {
          if (openItem !== item) {
            const openContent = openItem.querySelector('.vertical-menu-content');
            if (openContent) {
              if (useAnimation) {
                animateClose(openContent, openItem);
              } else {
                closeWithoutAnimation(openContent, openItem);
              }
            }
          }
        });
      }

      // Alternar grupo clickeado
      if (isOpen) {
        if (useAnimation) {
          animateClose(content, item);
        } else {
          closeWithoutAnimation(content, item);
        }
      } else {
        if (useAnimation) {
          animateOpen(content, item);
        } else {
          openWithoutAnimation(content, item);
        }
        
        // Al abrir el desplegable, seleccionar el primer link automáticamente SOLO si no hay ya un link activo dentro
        const activeItemClass = menu.getAttribute('active-item') || 'active';
        const hasActiveLink = content.querySelector(`.vertical-menu-link.${activeItemClass}`) !== null;
        
        if (!hasActiveLink) {
          const firstLink = content.querySelector('.vertical-menu-link');
          if (firstLink) {
            firstLink.click();
          }
        }
      }
      return;
    }

    // 2. Click en enlace (.vertical-menu-link)
    const link = e.target.closest('.vertical-menu-link');
    if (link) {
      const menu = link.closest('.vertical-menu');
      if (!menu) return;

      const useAnimation = hasGsap && menu.classList.contains('animated');
      const parentItem = link.closest('.vertical-menu-item');

      // Cerrar otros sub-menús si se hace click fuera de ellos o en un elemento raíz
      if (!menu.classList.contains('multi')) {
        menu.querySelectorAll('.vertical-menu-item.open').forEach(openItem => {
          if (openItem !== parentItem) {
            const openContent = openItem.querySelector('.vertical-menu-content');
            if (openContent) {
              if (useAnimation) {
                animateClose(openContent, openItem);
              } else {
                closeWithoutAnimation(openContent, openItem);
              }
            }
          }
        });
      }

      // Obtener clases personalizadas de active (fallback a 'active')
      const activePrincipalClass = menu.getAttribute('active-principal') || 'active';
      const activeItemClass = menu.getAttribute('active-item') || 'active';

      // Desactivar todos los enlaces del menú (limpiar todas las posibles clases active configuradas)
      menu.querySelectorAll('.vertical-menu-link').forEach(l => {
        l.classList.remove(activeItemClass);
        if (activeItemClass !== 'active') {
          l.classList.remove('active');
        }
      });

      // Activar el enlace presionado
      link.classList.add(activeItemClass);

      // Desactivar cabeceras activas y sus hijos internos directos
      menu.querySelectorAll('.vertical-menu-header').forEach(el => {
        el.classList.remove(activePrincipalClass);
        if (activePrincipalClass !== 'active') {
          el.classList.remove('active');
        }
        if (el.firstElementChild) {
          el.firstElementChild.classList.remove(activePrincipalClass);
          if (activePrincipalClass !== 'active') {
            el.firstElementChild.classList.remove('active');
          }
        }
      });

      // Si el enlace está dentro de un sub-menú, activar la cabecera correspondiente
      if (parentItem) {
        const parentHeader = parentItem.querySelector('.vertical-menu-header');
        if (parentHeader) {
          parentHeader.classList.add(activePrincipalClass);
          // Si la cabecera tiene un contenedor de diseño interno (como un div estilizado), activarlo también
          if (parentHeader.firstElementChild) {
            parentHeader.firstElementChild.classList.add(activePrincipalClass);
          }
        }
      }

      // Guardar el estado en localStorage (filtrado por url de página y ID de menú para evitar colisiones)
      const menuId = menu.id || 'default';
      const storageKey = `vertical_menu_active_${window.location.pathname}_${menuId}`;
      const linkIndex = Array.from(menu.querySelectorAll('.vertical-menu-link')).indexOf(link);
      localStorage.setItem(storageKey, JSON.stringify({
        remote: link.dataset.remote || '',
        index: linkIndex
      }));
    }
  });

  // Inicialización: auto-expandir y marcar activos en base al contenido
  document.querySelectorAll('.vertical-menu').forEach(menu => {
    const activePrincipalClass = menu.getAttribute('active-principal') || 'active';
    const activeItemClass = menu.getAttribute('active-item') || 'active';

    // Intentar restaurar el estado guardado desde localStorage
    const menuId = menu.id || 'default';
    const storageKey = `vertical_menu_active_${window.location.pathname}_${menuId}`;
    const savedStateStr = localStorage.getItem(storageKey);
    let activeLink = null;

    if (savedStateStr) {
      try {
        const savedState = JSON.parse(savedStateStr);
        const links = menu.querySelectorAll('.vertical-menu-link');
        
        if (savedState.remote) {
          activeLink = menu.querySelector(`.vertical-menu-link[data-remote="${savedState.remote}"]`);
        }
        
        if (!activeLink && savedState.index >= 0 && savedState.index < links.length) {
          activeLink = links[savedState.index];
        }
      } catch (e) {
        console.error('Error al restaurar estado del menú vertical:', e);
      }
    }

    // Si hay un link guardado en la caché del navegador, forzar que sea el activo
    if (activeLink) {
      menu.querySelectorAll('.vertical-menu-link').forEach(l => {
        l.classList.remove(activeItemClass);
        l.classList.remove('active');
      });
      activeLink.classList.add(activeItemClass);
    }

    // 1. Mapear cualquier enlace que tenga la clase por defecto 'active' en el HTML a la clase personalizada
    if (activeItemClass !== 'active') {
      menu.querySelectorAll('.vertical-menu-link.active').forEach(link => {
        link.classList.add(activeItemClass);
        link.classList.remove('active');
      });
    }

    // 2. Mapear cualquier cabecera con clase 'active' a la clase personalizada principal
    if (activePrincipalClass !== 'active') {
      menu.querySelectorAll('.vertical-menu-header.active').forEach(el => {
        el.classList.add(activePrincipalClass);
        el.classList.remove('active');
        if (el.firstElementChild) {
          el.firstElementChild.classList.add(activePrincipalClass);
          el.firstElementChild.classList.remove('active');
        }
      });
    }

    // 3. Auto-expandir grupos que contengan enlaces activos
    menu.querySelectorAll('.vertical-menu-item').forEach(item => {
      const content = item.querySelector('.vertical-menu-content');
      if (!content) return;

      // Buscar si el sub-menú contiene un enlace activo
      const hasActiveLink = content.querySelector(`.vertical-menu-link.${activeItemClass}`) !== null;
      const shouldOpen = item.classList.contains('open') || hasActiveLink;

      if (shouldOpen) {
        item.classList.add('open');
        content.classList.remove('hidden');
        content.style.height = 'auto';

        if (hasActiveLink) {
          const header = item.querySelector('.vertical-menu-header');
          if (header) {
            header.classList.add(activePrincipalClass);
            if (header.firstElementChild) {
              header.firstElementChild.classList.add(activePrincipalClass);
            }
          }
        }
      } else {
        content.classList.add('hidden');
        item.classList.remove('open');
      }
    });

    // 4. Marcar el menú y el contenedor remoto como listos para evitar FOUC
    menu.setAttribute('data-menu-ready', 'true');
    const container = document.querySelector('.remote-container');
    if (container) {
      container.setAttribute('data-container-ready', 'true');
    }
  });
}
