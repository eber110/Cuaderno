/**
 * querys.js (responsive-visibility.js)
 * Módulo para controlar la visibilidad y seguridad del contenido
 * según el tamaño de la pantalla y el modo de tema (oscuro/claro).
 * 
 * Breakpoints predeterminados:
 * - phone: (max-width: 576px)
 * - tablet: (min-width: 577px) and (max-width: 992px)
 * - desktop: (min-width: 993px)
 */

/**
 * Configura la visibilidad responsive de elementos basado en media queries y temas
 * @param {Object} options - Opciones de configuración opcional
 * @param {Object} options.breakpoints - Puntos de quiebre personalizados {phone, tablet, desktop}
 * @param {Array} options.preserveFunctionality - Selectores de elementos cuya funcionalidad debe preservarse
 * @param {boolean} options.observeDynamicElements - Si true, observa nuevos elementos añadidos al DOM (default: true)
 * @returns {Object} - Métodos para controlar la funcionalidad
 */
export function setupResponsiveVisibility(options = {}) {
  // Definir los breakpoints para cada dispositivo
  const defaultBreakpoints = {
    phone: "(max-width: 576px)",
    tablet: "(min-width: 577px) and (max-width: 992px)",
    desktop: "(min-width: 993px)"
  };

  const breakpoints = options.breakpoints || defaultBreakpoints;
  const preserveFunctionality = options.preserveFunctionality || [];
  const observeDynamicElements = options.observeDynamicElements !== false;

  const mediaQueries = {
    phone: window.matchMedia(breakpoints.phone),
    tablet: window.matchMedia(breakpoints.tablet),
    desktop: window.matchMedia(breakpoints.desktop)
  };

  // Almacenar el contenido original de los elementos
  const originalContent = {
    noPhone: new Map(),
    noTablet: new Map(),
    noDesk: new Map(),
    noDarkMode: new Map(),
    noLightMode: new Map()
  };

  // Almacenar referencias a elementos con funcionalidad a preservar
  const functionalElements = new Map();

  // MutationObservers
  let mutationObserver = null;
  let themeObserver = null;

  // Flag para evitar procesamiento múltiple
  let isProcessing = false;

  /**
   * Guarda referencias a elementos funcionales dentro de un contenedor
   * @param {HTMLElement} element - Elemento contenedor
   */
  const saveFunctionalElements = (element) => {
    preserveFunctionality.forEach(selector => {
      const functionalEls = element.querySelectorAll(selector);
      if (functionalEls.length > 0) {
        if (!functionalElements.has(element)) {
          functionalElements.set(element, new Map());
        }
        const elementMap = functionalElements.get(element);

        functionalEls.forEach(funcEl => {
          if (!funcEl.id) {
            funcEl.id = 'func-' + Math.random().toString(36).substr(2, 9);
          }
          elementMap.set(funcEl.id, {
            selector,
            element: funcEl,
            data: funcEl.dataset ? { ...funcEl.dataset } : {}
          });
        });
      }
    });
  };

  /**
   * Guarda el contenido original de los elementos si aún no se ha guardado
   */
  const saveOriginalContent = () => {
    // Guardar elementos con la clase no-phone
    document.querySelectorAll('.no-phone').forEach(element => {
      if (!originalContent.noPhone.has(element)) {
        originalContent.noPhone.set(element, element.innerHTML);
        saveFunctionalElements(element);
        element.setAttribute('data-responsive-processed', 'true');
      }
    });

    // Guardar elementos con la clase no-tablet
    document.querySelectorAll('.no-tablet').forEach(element => {
      if (!originalContent.noTablet.has(element)) {
        originalContent.noTablet.set(element, element.innerHTML);
        saveFunctionalElements(element);
        element.setAttribute('data-responsive-processed', 'true');
      }
    });

    // Guardar elementos con la clase no-desk
    document.querySelectorAll('.no-desk').forEach(element => {
      if (!originalContent.noDesk.has(element)) {
        originalContent.noDesk.set(element, element.innerHTML);
        saveFunctionalElements(element);
        element.setAttribute('data-responsive-processed', 'true');
      }
    });

    // Guardar elementos con la clase no-dark-mode
    document.querySelectorAll('.no-dark-mode').forEach(element => {
      if (!originalContent.noDarkMode.has(element)) {
        originalContent.noDarkMode.set(element, element.innerHTML);
        saveFunctionalElements(element);
        element.setAttribute('data-responsive-processed', 'true');
      }
    });

    // Guardar elementos con la clase no-light-mode / no-dark-light / no-drak-light (typos de soporte)
    document.querySelectorAll('.no-light-mode, .no-dark-light, .no-drak-light').forEach(element => {
      if (!originalContent.noLightMode.has(element)) {
        originalContent.noLightMode.set(element, element.innerHTML);
        saveFunctionalElements(element);
        element.setAttribute('data-responsive-processed', 'true');
      }
    });
  };

  /**
   * Determina si un elemento debe estar oculto por otra condición activa
   * @param {HTMLElement} element - Elemento a validar
   * @returns {boolean}
   */
  const shouldBeHiddenByOtherReason = (element) => {
    const darkActive = document.documentElement.getAttribute('data-theme') === 'dark';

    if (element.classList.contains('no-phone') && mediaQueries.phone.matches) {
      return true;
    }
    if (element.classList.contains('no-tablet') && mediaQueries.tablet.matches) {
      return true;
    }
    if (element.classList.contains('no-desk') && mediaQueries.desktop.matches) {
      return true;
    }
    if (element.classList.contains('no-dark-mode') && darkActive) {
      return true;
    }
    if ((element.classList.contains('no-light-mode') || element.classList.contains('no-dark-light') || element.classList.contains('no-drak-light')) && !darkActive) {
      return true;
    }

    return false;
  };

  /**
   * Restaura el contenido y reconecta la funcionalidad
   * @param {HTMLElement} element - Elemento a restaurar
   * @param {string} originalHtml - HTML original del elemento
   */
  const restoreContentAndFunctionality = (element, originalHtml) => {
    element.innerHTML = originalHtml;

    if (functionalElements.has(element)) {
      const elementFuncs = functionalElements.get(element);

      elementFuncs.forEach((info, id) => {
        const newElement = element.querySelector('#' + id);

        if (newElement) {
          if (info.data) {
            Object.keys(info.data).forEach(key => {
              newElement.dataset[key] = info.data[key];
            });
          }

          const reconnectEvent = new CustomEvent('functionalityReconnect', {
            detail: {
              originalElement: info.element,
              newElement: newElement,
              data: info.data
            }
          });

          document.dispatchEvent(reconnectEvent);

          if (typeof window.reconnectFunctionality === 'function') {
            window.reconnectFunctionality(info.element, newElement, info.data);
          }
        }
      });
    }
  };

  /**
   * Oculta un elemento, elimina físicamente su contenido para mayor seguridad y añade aria-hidden
   * @param {HTMLElement} element - Elemento a ocultar
   */
  const hideElement = (element) => {
    element.style.setProperty('display', 'none', 'important');
    element.innerHTML = '';
    element.setAttribute('aria-hidden', 'true');
  };

  /**
   * Muestra un elemento y restaura su contenido original
   * @param {HTMLElement} element - Elemento a mostrar
   * @param {string} originalHtml - HTML original
   */
  const showElement = (element, originalHtml) => {
    if (element.style.display === 'none' || element.innerHTML === '') {
      element.style.display = '';
      element.removeAttribute('aria-hidden');
      restoreContentAndFunctionality(element, originalHtml);
    }
  };

  /**
   * Actualiza la visibilidad y el contenido de los elementos de forma compuesta
   */
  const updateVisibility = () => {
    if (isProcessing) return;
    isProcessing = true;

    // Guardar el contenido original antes de cualquier modificación
    saveOriginalContent();

    const darkActive = document.documentElement.getAttribute('data-theme') === 'dark';

    // 1. Procesar elementos no-phone
    originalContent.noPhone.forEach((html, element) => {
      if (mediaQueries.phone.matches) {
        hideElement(element);
      } else if (!shouldBeHiddenByOtherReason(element)) {
        showElement(element, html);
      } else {
        hideElement(element);
      }
    });

    // 2. Procesar elementos no-tablet
    originalContent.noTablet.forEach((html, element) => {
      if (mediaQueries.tablet.matches) {
        hideElement(element);
      } else if (!shouldBeHiddenByOtherReason(element)) {
        showElement(element, html);
      } else {
        hideElement(element);
      }
    });

    // 3. Procesar elementos no-desk
    originalContent.noDesk.forEach((html, element) => {
      if (mediaQueries.desktop.matches) {
        hideElement(element);
      } else if (!shouldBeHiddenByOtherReason(element)) {
        showElement(element, html);
      } else {
        hideElement(element);
      }
    });

    // 4. Procesar elementos no-dark-mode
    originalContent.noDarkMode.forEach((html, element) => {
      if (darkActive) {
        hideElement(element);
      } else if (!shouldBeHiddenByOtherReason(element)) {
        showElement(element, html);
      } else {
        hideElement(element);
      }
    });

    // 5. Procesar elementos no-light-mode / no-dark-light / no-drak-light
    originalContent.noLightMode.forEach((html, element) => {
      if (!darkActive) {
        hideElement(element);
      } else if (!shouldBeHiddenByOtherReason(element)) {
        showElement(element, html);
      } else {
        hideElement(element);
      }
    });

    isProcessing = false;
  };

  /**
   * MutationObserver para detectar dinámicamente nuevos elementos agregados al DOM
   */
  const setupMutationObserver = () => {
    if (!observeDynamicElements || mutationObserver) return;

    mutationObserver = new MutationObserver((mutations) => {
      let hasNewResponsiveElements = false;

      for (const mutation of mutations) {
        if (mutation.type === 'childList') {
          for (const node of mutation.addedNodes) {
            if (node.nodeType === Node.ELEMENT_NODE) {
              if (node.classList && (
                node.classList.contains('no-phone') ||
                node.classList.contains('no-tablet') ||
                node.classList.contains('no-desk') ||
                node.classList.contains('no-dark-mode') ||
                node.classList.contains('no-light-mode') ||
                node.classList.contains('no-dark-light') ||
                node.classList.contains('no-drak-light')
              )) {
                hasNewResponsiveElements = true;
                break;
              }

              if (node.querySelector && node.querySelector('.no-phone, .no-tablet, .no-desk, .no-dark-mode, .no-light-mode, .no-dark-light, .no-drak-light')) {
                hasNewResponsiveElements = true;
                break;
              }
            }
          }
        }
        if (hasNewResponsiveElements) break;
      }

      if (hasNewResponsiveElements) {
        requestAnimationFrame(updateVisibility);
      }
    });

    mutationObserver.observe(document.body, {
      childList: true,
      subtree: true
    });
  };

  /**
   * MutationObserver para detectar cambios en el tema (modo oscuro / claro)
   */
  const setupThemeObserver = () => {
    if (themeObserver) return;

    themeObserver = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
          requestAnimationFrame(updateVisibility);
        }
      }
    });

    themeObserver.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['data-theme']
    });
  };

  /**
   * Agrega listeners a las media queries
   */
  const addMediaListeners = () => {
    const handler = () => requestAnimationFrame(updateVisibility);

    if (mediaQueries.phone.addEventListener) {
      mediaQueries.phone.addEventListener('change', handler);
      mediaQueries.tablet.addEventListener('change', handler);
      mediaQueries.desktop.addEventListener('change', handler);
    } else {
      mediaQueries.phone.addListener(handler);
      mediaQueries.tablet.addListener(handler);
      mediaQueries.desktop.addListener(handler);
    }
  };

  /**
   * Elimina los listeners de las media queries
   */
  const removeMediaListeners = () => {
    const handler = () => requestAnimationFrame(updateVisibility);

    if (mediaQueries.phone.removeEventListener) {
      mediaQueries.phone.removeEventListener('change', handler);
      mediaQueries.tablet.removeEventListener('change', handler);
      mediaQueries.desktop.removeEventListener('change', handler);
    } else {
      mediaQueries.phone.removeListener(handler);
      mediaQueries.tablet.removeListener(handler);
      mediaQueries.desktop.removeListener(handler);
    }
  };

  /**
   * Destruye completamente la funcionalidad y limpia referencias
   */
  const destroy = () => {
    removeMediaListeners();

    if (mutationObserver) {
      mutationObserver.disconnect();
      mutationObserver = null;
    }

    if (themeObserver) {
      themeObserver.disconnect();
      themeObserver = null;
    }

    originalContent.noPhone.clear();
    originalContent.noTablet.clear();
    originalContent.noDesk.clear();
    originalContent.noDarkMode.clear();
    originalContent.noLightMode.clear();
    functionalElements.clear();
  };

  /**
   * Inicializa el gestor reactivo
   */
  const init = () => {
    updateVisibility();
    addMediaListeners();
    setupMutationObserver();
    setupThemeObserver();

    return {
      update: updateVisibility,
      destroy
    };
  };

  return { init };
}

/**
 * Inicializa automáticamente la visibilidad responsive y de tema cuando el DOM está listo
 * @param {Object} options - Opciones de configuración
 * @returns {Object} - Método para destruir la funcionalidad
 */
export function initResponsiveVisibility(options = {}) {
  let controller = null;

  const onDOMLoaded = () => {
    controller = setupResponsiveVisibility(options).init();
  };

  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(onDOMLoaded, 0);
  } else {
    document.addEventListener('DOMContentLoaded', onDOMLoaded);
  }

  return {
    destroy: () => {
      if (controller) {
        controller.destroy();
      }
      document.removeEventListener('DOMContentLoaded', onDOMLoaded);
    }
  };
}

/**
 * Sistema de reconexión generalizado para funcionalidades en elementos responsive
 */
export const functionalityReconnector = {
  handlers: new Map(),
  _listenerConfigured: false,

  register(selector, handler, options = {}) {
    if (!this.handlers.has(selector)) {
      this.handlers.set(selector, []);
    }

    this.handlers.get(selector).push({ handler, options });
    this._ensureGlobalListener();

    return this;
  },

  unregister(selector, handler = null) {
    if (!this.handlers.has(selector)) return this;

    if (handler) {
      const handlers = this.handlers.get(selector);
      const filteredHandlers = handlers.filter(h => h.handler !== handler);
      this.handlers.set(selector, filteredHandlers);
    } else {
      this.handlers.delete(selector);
    }

    return this;
  },

  executeHandlers(originalElement, newElement, data) {
    this.handlers.forEach((handlers, selector) => {
      if (originalElement.matches(selector) || newElement.matches(selector)) {
        handlers.forEach(({ handler, options }) => {
          try {
            handler(newElement, originalElement, data);
          } catch (error) {
            console.error(`Error executing reconnect handler for "${selector}":`, error);
          }

          if (options.once) {
            this.unregister(selector, handler);
          }
        });
      }
    });
  },

  _ensureGlobalListener() {
    if (this._listenerConfigured) return;

    document.addEventListener('functionalityReconnect', (event) => {
      const { originalElement, newElement, data } = event.detail;
      this.executeHandlers(originalElement, newElement, data);
    });

    this._listenerConfigured = true;
  },

  initAndReconnect(selector, initFunction) {
    document.querySelectorAll(selector).forEach(initFunction);
    this.register(selector, initFunction);

    return {
      destroy: () => this.unregister(selector, initFunction)
    };
  }
};

export function reconnectCountdown(countdownSelector, updateFunction) {
  return functionalityReconnector.register(countdownSelector, updateFunction);
}

export const responsiveVisibility = {
  setup: setupResponsiveVisibility,
  init: initResponsiveVisibility,
  reconnectCountdown,
  reconnector: functionalityReconnector
};

// Autoejecución global previniendo doble inicialización
if (typeof window !== 'undefined' && !window.responsiveVisibilityInitialized) {
  window.responsiveVisibilityInitialized = true;
  initResponsiveVisibility();
}

// Exportaciones
export default setupResponsiveVisibility;
