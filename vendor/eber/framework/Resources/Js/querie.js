/**
 * querie.js
 * Módulo central para controlar la visibilidad reactiva y seguridad del contenido
 * según las media queries configuradas en el proyecto (ThemeModule / Theme Studio)
 * y el modo de tema (oscuro/claro).
 *
 * Breakpoints predeterminados (cuando el proyecto no define personalizados):
 * - phone:  (max-width: 576px)
 * - tablet: (min-width: 577px) and (max-width: 992px)
 * - desk:   (min-width: 993px)
 */

const DEFAULT_BREAKPOINTS = {
  phone: "(max-width: 576px)",
  tablet: "(min-width: 577px) and (max-width: 992px)",
  desk: "(min-width: 993px)"
};

let activeController = null;

/**
 * Resuelve el mapa de breakpoints `{ slug: mediaQueryString }` desde opciones,
 * `window.fmeMediaQueries` o los valores por defecto.
 *
 * @param {Object|Array|null} [customBreakpoints=null]
 * @returns {Record<string, string>}
 */
function resolveBreakpoints(customBreakpoints = null) {
  const map = {};

  if (Array.isArray(customBreakpoints) && customBreakpoints.length > 0) {
    customBreakpoints.forEach(item => {
      if (item && item.slug && item.query) {
        map[String(item.slug).trim()] = String(item.query).trim();
      }
    });
    if (Object.keys(map).length > 0) return map;
  }

  if (customBreakpoints && typeof customBreakpoints === "object" && !Array.isArray(customBreakpoints)) {
    Object.entries(customBreakpoints).forEach(([key, query]) => {
      if (typeof query === "string" && query.trim() !== "") {
        const normalizedKey = key === "desktop" && !customBreakpoints.desk ? "desk" : key;
        map[normalizedKey] = query.trim();
      }
    });
    if (Object.keys(map).length > 0) return map;
  }

  if (typeof window !== "undefined" && Array.isArray(window.fmeMediaQueries) && window.fmeMediaQueries.length > 0) {
    window.fmeMediaQueries.forEach(item => {
      if (item && item.slug && item.query) {
        map[String(item.slug).trim()] = String(item.query).trim();
      }
    });
    if (Object.keys(map).length > 0) return map;
  }

  return { ...DEFAULT_BREAKPOINTS };
}

/**
 * Configura la visibilidad responsive de elementos basado en media queries y temas.
 *
 * @param {Object} [options={}] - Opciones de configuración opcional
 * @param {Object|Array} [options.breakpoints] - Puntos de quiebre personalizados
 * @param {Array<string>} [options.preserveFunctionality] - Selectores de elementos cuya funcionalidad debe preservarse
 * @param {boolean} [options.observeDynamicElements=true] - Si true, observa nuevos elementos añadidos al DOM
 * @returns {Object} Objeto con método `init()`
 */
export function setupResponsiveVisibility(options = {}) {
  const preserveFunctionality = Array.isArray(options.preserveFunctionality) ? options.preserveFunctionality : [];
  const observeDynamicElements = options.observeDynamicElements !== false;

  let breakpoints = resolveBreakpoints(options.breakpoints || null);
  let mediaQueries = {};

  // Almacenar el contenido original por clase responsiva (.no-{slug}) y de tema
  const responsiveMaps = new Map();
  const originalContent = {
    noDarkMode: new Map(),
    noLightMode: new Map()
  };

  // Almacenar referencias a elementos con funcionalidad a preservar
  const functionalElements = new Map();

  // MutationObservers y estado
  let mutationObserver = null;
  let themeObserver = null;
  let isProcessing = false;
  let listenersAttached = false;

  // Referencia única para agregar y remover listeners limpiamente
  const mediaChangeHandler = () => {
    requestAnimationFrame(updateVisibility);
  };

  const buildMediaQueries = () => {
    mediaQueries = {};
    Object.entries(breakpoints).forEach(([slug, query]) => {
      mediaQueries[slug] = window.matchMedia(query);
      if (!responsiveMaps.has(slug)) {
        responsiveMaps.set(slug, new Map());
      }
    });
  };

  buildMediaQueries();

  const getDynamicSelector = () => {
    const selectors = Object.keys(mediaQueries).map(slug => `.no-${slug}`);
    selectors.push(".no-dark-mode", ".no-light-mode", ".no-dark-light", ".no-drak-light");
    return selectors.join(", ");
  };

  const hasResponsiveClass = (node) => {
    if (!node || !node.classList) return false;
    for (const slug of Object.keys(mediaQueries)) {
      if (node.classList.contains(`no-${slug}`)) return true;
    }
    return (
      node.classList.contains("no-dark-mode") ||
      node.classList.contains("no-light-mode") ||
      node.classList.contains("no-dark-light") ||
      node.classList.contains("no-drak-light")
    );
  };

  /**
   * Guarda referencias a elementos funcionales dentro de un contenedor.
   *
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
            funcEl.id = "func-" + Math.random().toString(36).substring(2, 11);
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
   * Guarda el contenido original de los elementos si aún no se ha guardado.
   */
  const saveOriginalContent = () => {
    Object.keys(mediaQueries).forEach(slug => {
      const map = responsiveMaps.get(slug);
      document.querySelectorAll(`.no-${slug}`).forEach(element => {
        if (!map.has(element)) {
          map.set(element, element.innerHTML);
          saveFunctionalElements(element);
          element.setAttribute("data-responsive-processed", "true");
        }
      });
    });

    document.querySelectorAll(".no-dark-mode").forEach(element => {
      if (!originalContent.noDarkMode.has(element)) {
        originalContent.noDarkMode.set(element, element.innerHTML);
        saveFunctionalElements(element);
        element.setAttribute("data-responsive-processed", "true");
      }
    });

    document.querySelectorAll(".no-light-mode, .no-dark-light, .no-drak-light").forEach(element => {
      if (!originalContent.noLightMode.has(element)) {
        originalContent.noLightMode.set(element, element.innerHTML);
        saveFunctionalElements(element);
        element.setAttribute("data-responsive-processed", "true");
      }
    });
  };

  /**
   * Determina si un elemento debe estar oculto por alguna media query o tema activo.
   *
   * @param {HTMLElement} element - Elemento a validar
   * @returns {boolean}
   */
  const shouldBeHidden = (element) => {
    const darkActive = document.documentElement.getAttribute("data-theme") === "dark";

    for (const [slug, mql] of Object.entries(mediaQueries)) {
      if (element.classList.contains(`no-${slug}`) && mql.matches) {
        return true;
      }
    }

    if (element.classList.contains("no-dark-mode") && darkActive) {
      return true;
    }

    if (
      (element.classList.contains("no-light-mode") ||
        element.classList.contains("no-dark-light") ||
        element.classList.contains("no-drak-light")) &&
      !darkActive
    ) {
      return true;
    }

    return false;
  };

  /**
   * Restaura el contenido y reconecta la funcionalidad.
   *
   * @param {HTMLElement} element - Elemento a restaurar
   * @param {string} originalHtml - HTML original del elemento
   */
  const restoreContentAndFunctionality = (element, originalHtml) => {
    element.innerHTML = originalHtml;

    if (functionalElements.has(element)) {
      const elementFuncs = functionalElements.get(element);

      elementFuncs.forEach((info, id) => {
        const newElement = element.querySelector("#" + id);

        if (newElement) {
          if (info.data) {
            Object.keys(info.data).forEach(key => {
              newElement.dataset[key] = info.data[key];
            });
          }

          const reconnectEvent = new CustomEvent("functionalityReconnect", {
            detail: {
              originalElement: info.element,
              newElement: newElement,
              data: info.data
            }
          });

          document.dispatchEvent(reconnectEvent);

          if (typeof window.reconnectFunctionality === "function") {
            window.reconnectFunctionality(info.element, newElement, info.data);
          }
        }
      });
    }
  };

  /**
   * Oculta un elemento, elimina físicamente su contenido y añade aria-hidden.
   *
   * @param {HTMLElement} element - Elemento a ocultar
   */
  const hideElement = (element) => {
    if (
      element.dataset.responsiveHidden === "1" &&
      element.style.display === "none" &&
      element.innerHTML === ""
    ) {
      return;
    }
    element.style.setProperty("display", "none", "important");
    element.innerHTML = "";
    element.setAttribute("aria-hidden", "true");
    element.dataset.responsiveHidden = "1";
  };

  /**
   * Muestra un elemento y restaura su contenido original.
   *
   * @param {HTMLElement} element - Elemento a mostrar
   * @param {string} originalHtml - HTML original
   */
  const showElement = (element, originalHtml) => {
    if (
      element.dataset.responsiveHidden === "1" ||
      element.style.display === "none" ||
      element.getAttribute("aria-hidden") === "true" ||
      (element.innerHTML === "" && originalHtml !== "")
    ) {
      element.style.display = "";
      element.removeAttribute("aria-hidden");
      delete element.dataset.responsiveHidden;
      restoreContentAndFunctionality(element, originalHtml);
    }
  };

  /**
   * Actualiza la visibilidad y el contenido de los elementos de forma compuesta.
   */
  const updateVisibility = () => {
    if (isProcessing) return;
    isProcessing = true;

    try {
      saveOriginalContent();

      const processed = new Set();

      const processEntry = (element, html) => {
        if (processed.has(element) || !element.isConnected) return;
        processed.add(element);

        if (shouldBeHidden(element)) {
          hideElement(element);
        } else {
          showElement(element, html);
        }
      };

      responsiveMaps.forEach((map) => {
        map.forEach((html, element) => processEntry(element, html));
      });

      originalContent.noDarkMode.forEach((html, element) => processEntry(element, html));
      originalContent.noLightMode.forEach((html, element) => processEntry(element, html));
    } finally {
      isProcessing = false;
    }
  };

  /**
   * MutationObserver para detectar dinámicamente nuevos elementos agregados al DOM.
   */
  const setupMutationObserver = () => {
    if (!observeDynamicElements || mutationObserver || !document.body) return;

    mutationObserver = new MutationObserver((mutations) => {
      if (isProcessing) return;
      let hasNewResponsiveElements = false;
      const selector = getDynamicSelector();

      for (const mutation of mutations) {
        if (mutation.type === "childList") {
          for (const node of mutation.addedNodes) {
            if (node.nodeType === Node.ELEMENT_NODE) {
              if (hasResponsiveClass(node)) {
                hasNewResponsiveElements = true;
                break;
              }
              if (node.querySelector && node.querySelector(selector)) {
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
   * MutationObserver para detectar cambios en el tema (modo oscuro / claro).
   */
  const setupThemeObserver = () => {
    if (themeObserver) return;

    themeObserver = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        if (mutation.type === "attributes" && mutation.attributeName === "data-theme") {
          requestAnimationFrame(updateVisibility);
          break;
        }
      }
    });

    themeObserver.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ["data-theme"]
    });
  };

  /**
   * Agrega listeners a las media queries.
   */
  const addMediaListeners = () => {
    if (listenersAttached) return;
    Object.values(mediaQueries).forEach(mql => {
      if (mql.addEventListener) {
        mql.addEventListener("change", mediaChangeHandler);
      } else if (mql.addListener) {
        mql.addListener(mediaChangeHandler);
      }
    });
    listenersAttached = true;
  };

  /**
   * Elimina los listeners de las media queries.
   */
  const removeMediaListeners = () => {
    if (!listenersAttached) return;
    Object.values(mediaQueries).forEach(mql => {
      if (mql.removeEventListener) {
        mql.removeEventListener("change", mediaChangeHandler);
      } else if (mql.removeListener) {
        mql.removeListener(mediaChangeHandler);
      }
    });
    listenersAttached = false;
  };

  /**
   * Recarga las definiciones de media queries en caliente (útil para Theme Studio).
   *
   * @param {Object|Array} newBreakpoints - Nuevos breakpoints del proyecto
   */
  const refreshMediaQueries = (newBreakpoints = null) => {
    removeMediaListeners();
    breakpoints = resolveBreakpoints(newBreakpoints);
    buildMediaQueries();
    addMediaListeners();
    updateVisibility();
  };

  /**
   * Destruye completamente la funcionalidad y limpia referencias.
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

    responsiveMaps.forEach(map => map.clear());
    responsiveMaps.clear();
    originalContent.noDarkMode.clear();
    originalContent.noLightMode.clear();
    functionalElements.clear();

    if (activeController && activeController._destroyRef === destroy) {
      activeController = null;
    }
    if (typeof window !== "undefined") {
      window.responsiveVisibilityInitialized = false;
      if (window.__fmeQuerieController && window.__fmeQuerieController._destroyRef === destroy) {
        delete window.__fmeQuerieController;
      }
    }
  };

  /**
   * Inicializa el gestor reactivo.
   */
  const init = () => {
    updateVisibility();
    addMediaListeners();
    setupMutationObserver();
    setupThemeObserver();

    const instance = {
      update: updateVisibility,
      refreshMediaQueries,
      destroy,
      _destroyRef: destroy
    };

    activeController = instance;
    if (typeof window !== "undefined") {
      window.__fmeQuerieController = instance;
    }

    return instance;
  };

  return { init };
}

/**
 * Inicializa automáticamente la visibilidad responsive y de tema (Singleton).
 * Evita registros duplicados si ya se autoejecutó en prioridad antes de loader.js.
 *
 * @param {Object} [options={}] - Opciones de configuración
 * @returns {Object} Controlador activo con métodos `update`, `refreshMediaQueries` y `destroy`
 */
export function initResponsiveVisibility(options = {}) {
  if (activeController) {
    activeController.update();
    return activeController;
  }

  let controller = null;
  let initialized = false;

  const onDOMLoaded = () => {
    if (initialized) return;
    initialized = true;
    if (activeController) {
      controller = activeController;
      controller.update();
      return;
    }
    controller = setupResponsiveVisibility(options).init();
  };

  if (document.readyState === "complete" || document.readyState === "interactive") {
    onDOMLoaded();
  } else {
    document.addEventListener("DOMContentLoaded", onDOMLoaded, { once: true });
  }

  return {
    update: () => {
      if (controller) controller.update();
      else if (activeController) activeController.update();
    },
    refreshMediaQueries: (newBreakpoints = null) => {
      if (controller) controller.refreshMediaQueries(newBreakpoints);
      else if (activeController) activeController.refreshMediaQueries(newBreakpoints);
    },
    destroy: () => {
      if (controller) {
        controller.destroy();
        controller = null;
      }
      document.removeEventListener("DOMContentLoaded", onDOMLoaded);
    }
  };
}

/**
 * Registra una función callback para ejecutarse cuando el viewport coincide
 * con una media query configurada (ej. 'phone', 'tablet', 'desk' o personalizada).
 *
 * @param {string} slug - Nombre de la media query ('phone', 'tablet', 'desk', etc.)
 * @param {Function} callback - Función a ejecutar cuando la media query esté activa
 * @returns {Function} Función para des-registrar el listener
 */
export function onMediaQuery(slug, callback) {
  if (typeof callback !== "function" || typeof window === "undefined") {
    return () => {};
  }

  const breakpoints = resolveBreakpoints(null);
  const normalizedSlug = slug === "desktop" && !breakpoints.desktop && breakpoints.desk ? "desk" : slug;
  const query = breakpoints[normalizedSlug];

  if (!query) {
    console.warn(`querie.js: Media query "${slug}" no está definida.`);
    return () => {};
  }

  const mql = window.matchMedia(query);

  if (mql.matches) {
    try {
      callback(mql);
    } catch (error) {
      console.error(`Error ejecutando callback inicial para "${slug}":`, error);
    }
  }

  const listener = (event) => {
    if (event.matches) {
      try {
        callback(event);
      } catch (error) {
        console.error(`Error ejecutando callback en cambio para "${slug}":`, error);
      }
    }
  };

  if (mql.addEventListener) {
    mql.addEventListener("change", listener);
  } else if (mql.addListener) {
    mql.addListener(listener);
  }

  return () => {
    if (mql.removeEventListener) {
      mql.removeEventListener("change", listener);
    } else if (mql.removeListener) {
      mql.removeListener(listener);
    }
  };
}

/**
 * Sistema de reconexión generalizado para funcionalidades en elementos responsive.
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

    document.addEventListener("functionalityReconnect", (event) => {
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
  onMediaQuery,
  refreshMediaQueries: (newBreakpoints = null) => {
    if (activeController && typeof activeController.refreshMediaQueries === "function") {
      activeController.refreshMediaQueries(newBreakpoints);
    }
  },
  update: () => {
    if (activeController && typeof activeController.update === "function") {
      activeController.update();
    }
  },
  reconnectCountdown,
  reconnector: functionalityReconnector
};

// Autoejecución global previniendo doble inicialización
if (typeof window !== "undefined") {
  window.responsiveVisibility = responsiveVisibility;
  window.onMediaQuery = onMediaQuery;

  if (!window.responsiveVisibilityInitialized) {
    window.responsiveVisibilityInitialized = true;
    initResponsiveVisibility();
  }
}
