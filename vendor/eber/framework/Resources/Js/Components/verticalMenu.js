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

  /**
   * Sincroniza el estado activo de un enlace remoto por data-remote en TODOS los vertical-menu de la página.
   * @param {string} remoteId - ID del contenido remoto (ej: "header-remote")
   */
  function syncActiveRemoteAcrossMenus(remoteId) {
    if (!remoteId) return;

    document.querySelectorAll('.vertical-menu').forEach(menu => {
      const activePrincipalClass = menu.getAttribute('active-principal') || 'active';
      const activeItemClass = menu.getAttribute('active-item') || 'active';

      const targetLink = menu.querySelector(`.vertical-menu-link[data-remote="${remoteId}"]`);
      if (!targetLink) return;

      const parentItem = targetLink.closest('.vertical-menu-item');
      const useAnimation = hasGsap && menu.classList.contains('animated') && menu.hasAttribute('data-menu-ready');

      // 1. Limpiar enlace activo previo en este menú
      menu.querySelectorAll('.vertical-menu-link').forEach(l => {
        l.classList.remove(activeItemClass);
        l.classList.remove('active');
      });

      // 2. Activar el enlace correspondiente
      targetLink.classList.add(activeItemClass);

      // 3. Cerrar otros acordeones que no contengan el enlace activo
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

      // 4. Limpiar cabeceras activas en este menú
      menu.querySelectorAll('.vertical-menu-header').forEach(el => {
        el.classList.remove(activePrincipalClass);
        el.classList.remove('active');
        if (el.firstElementChild) {
          el.firstElementChild.classList.remove(activePrincipalClass);
          el.firstElementChild.classList.remove('active');
        }
      });

      // 5. Si el enlace está dentro de un sub-menú, activar la cabecera y desplegarlo
      if (parentItem) {
        const parentHeader = parentItem.querySelector('.vertical-menu-header');
        if (parentHeader) {
          parentHeader.classList.add(activePrincipalClass);
          if (parentHeader.firstElementChild) {
            parentHeader.firstElementChild.classList.add(activePrincipalClass);
          }
        }

        const content = parentItem.querySelector('.vertical-menu-content');
        if (content) {
          if (!parentItem.classList.contains('open')) {
            if (useAnimation) {
              animateOpen(content, parentItem);
            } else {
              openWithoutAnimation(content, parentItem);
            }
          } else {
            content.classList.remove('hidden');
            content.style.height = 'auto';
          }
        }
      }
    });
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

      // Cerrar otros grupos si no está en modo 'multi'
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

      const remoteId = link.dataset.remote;

      if (remoteId) {
        // Sincronizar el estado activo en TODOS los menús verticales de la página
        syncActiveRemoteAcrossMenus(remoteId);

        // Guardar el estado en localStorage con clave única por URL de página
        const storageKey = `vertical_menu_active_${window.location.pathname}`;
        localStorage.setItem(storageKey, JSON.stringify({
          remote: remoteId
        }));
      } else {
        // Comportamiento local para enlaces sin data-remote
        const activeItemClass = menu.getAttribute('active-item') || 'active';
        const activePrincipalClass = menu.getAttribute('active-principal') || 'active';
        const parentItem = link.closest('.vertical-menu-item');

        menu.querySelectorAll('.vertical-menu-link').forEach(l => {
          l.classList.remove(activeItemClass);
          l.classList.remove('active');
        });
        link.classList.add(activeItemClass);

        if (parentItem) {
          const parentHeader = parentItem.querySelector('.vertical-menu-header');
          if (parentHeader) {
            parentHeader.classList.add(activePrincipalClass);
            if (parentHeader.firstElementChild) {
              parentHeader.firstElementChild.classList.add(activePrincipalClass);
            }
          }
        }
      }
    }
  });

  // Inicialización: auto-expandir y marcar activos en base al contenido guardado
  const storageKey = `vertical_menu_active_${window.location.pathname}`;
  const savedStateStr = localStorage.getItem(storageKey);
  let targetRemoteId = null;

  if (savedStateStr) {
    try {
      const savedState = JSON.parse(savedStateStr);
      if (savedState && savedState.remote && document.getElementById(savedState.remote)) {
        targetRemoteId = savedState.remote;
      }
    } catch (e) {
      console.error('Error al restaurar estado del menú vertical:', e);
    }
  }

  // Fallback si no hay estado en localStorage: buscar link con .active o primer link con data-remote
  if (!targetRemoteId) {
    const activeLink = document.querySelector('.vertical-menu-link.active[data-remote]');
    if (activeLink && activeLink.dataset.remote) {
      targetRemoteId = activeLink.dataset.remote;
    } else {
      const firstRemoteLink = document.querySelector('.vertical-menu-link[data-remote]');
      if (firstRemoteLink && firstRemoteLink.dataset.remote) {
        targetRemoteId = firstRemoteLink.dataset.remote;
      }
    }
  }

  if (targetRemoteId) {
    // 1. Sincronizar todos los menús al remote objetivo
    syncActiveRemoteAcrossMenus(targetRemoteId);

    // 2. Mostrar el panel correspondiente en .remote-container
    const targetPanel = document.getElementById(targetRemoteId);
    if (targetPanel) {
      const container = targetPanel.closest('.remote-container');
      if (container) {
        container.querySelectorAll('.remote-content').forEach(content => {
          if (content === targetPanel) {
            content.classList.remove('hidden');
            content.classList.add('active');
          } else {
            content.classList.remove('active');
            content.classList.add('hidden');
          }
        });
      }
    }
  }

  // Marcar los menús y el contenedor como listos
  document.querySelectorAll('.vertical-menu').forEach(menu => {
    menu.setAttribute('data-menu-ready', 'true');
  });

  const container = document.querySelector('.remote-container');
  if (container) {
    container.setAttribute('data-container-ready', 'true');
  }
}
