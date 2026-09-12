/**
 * Componente scrollMemory.
 * 
 * Gestiona la persistencia y restauración automática de la posición de scroll
 * para contenedores internos (`.overflow-y-scroll` o elementos con `[data-scroll-memory]`).
 * 
 * Soluciona la pérdida de posición de scroll en:
 * 1. Perfil público (/:user): restaura el scroll al recargar (F5) o navegar y volver (BFCache / pageshow).
 * 2. Vista previa en Dashboard (.user-profile-preview): recuerda el scroll entre recargas y actualizaciones.
 * 3. Estabilización de scroll ante carga asíncrona de imágenes y videos.
 * 
 * @function scrollMemory
 * @returns {void}
 */
export function scrollMemory() {
  const isStorageAvailable = (() => {
    try {
      const testKey = '__sm_test__';
      sessionStorage.setItem(testKey, testKey);
      sessionStorage.removeItem(testKey);
      return true;
    } catch (e) {
      return false;
    }
  })();

  if (!isStorageAvailable) return;

  /**
   * Genera la clave de almacenamiento para un elemento específico.
   *
   * @param {HTMLElement} el Contenedor con scroll
   * @returns {string} Clave de sessionStorage
   */
  function getStorageKey(el) {
    const memoryId = el.getAttribute('data-scroll-memory') || 
      (el.closest('.user-profile-preview') ? 'user-preview' : 
      (el.closest('.preview-profile') || el.closest('.back-card') ? 'user-profile' : 'content'));
    return `cuaderno_scroll_${memoryId}_${window.location.pathname}`;
  }

  /**
   * Obtiene todos los elementos que deben recordar su scroll.
   *
   * @returns {HTMLElement[]} Lista de elementos
   */
  function getTrackedElements() {
    const elements = [];

    // 1. Elementos explícitos con data-scroll-memory
    document.querySelectorAll('[data-scroll-memory]').forEach((el) => {
      elements.push(el);
    });

    // 2. Contenedor de scroll del perfil público si no tiene atributo
    if (!elements.some(el => el.getAttribute('data-scroll-memory') === 'user-profile')) {
      const profileScroll = document.querySelector('.back-card-container .overflow-y-scroll, .preview-profile .overflow-y-scroll');
      if (profileScroll && !elements.includes(profileScroll)) {
        elements.push(profileScroll);
      }
    }

    // 3. Contenedor de scroll de la vista previa en el dashboard si no tiene atributo
    if (!elements.some(el => el.getAttribute('data-scroll-memory') === 'user-preview')) {
      const previewScroll = document.querySelector('.user-profile-preview .overflow-y-scroll');
      if (previewScroll && !elements.includes(previewScroll)) {
        elements.push(previewScroll);
      }
    }

    return elements;
  }

  // Mapa de timers para debounce por elemento
  const scrollDebounceMap = new WeakMap();
  // Elementos donde el usuario ya interactuó activamente
  const userInteractedSet = new WeakSet();
  // Bandera para ignorar eventos 'scroll' disparados por asignaciones programáticas
  let isProgrammaticScroll = false;

  // Desactivar la restauración nativa del navegador para evitar conflictos con scroll en contenedores internos
  if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
  }

  /**
   * Guarda de forma inmediata la posición de scroll de un elemento.
   *
   * @param {HTMLElement} el Contenedor con scroll
   */
  function saveScrollImmediate(el) {
    if (!el) return;
    const key = getStorageKey(el);
    const top = el.scrollTop;
    try {
      sessionStorage.setItem(key, String(top));
    } catch (e) {}
  }

  /**
   * Guarda de forma inmediata todos los contenedores rastreados.
   */
  function saveAllTracked() {
    const elements = getTrackedElements();
    elements.forEach(saveScrollImmediate);
  }

  /**
   * Restaura la posición de scroll de un elemento de forma instantánea sin recorrido visual.
   *
   * @param {HTMLElement} el Contenedor con scroll
   */
  function restoreScrollForElement(el) {
    if (!el) return;
    if (userInteractedSet.has(el)) return;

    const key = getStorageKey(el);
    const savedStr = sessionStorage.getItem(key);
    if (!savedStr) return;

    const targetTop = parseInt(savedStr, 10);
    if (isNaN(targetTop) || targetTop <= 0) return;

    // Si ya está en la posición objetivo (por el script anti-FOUC o asignación previa), no tocar nada
    if (Math.abs(el.scrollTop - targetTop) <= 2) return;

    // Asignación síncrona e instantánea asegurando scroll-behavior: auto !important
    el.style.setProperty('scroll-behavior', 'auto', 'important');
    isProgrammaticScroll = true;
    el.scrollTop = targetTop;
    setTimeout(() => {
      isProgrammaticScroll = false;
    }, 50);
  }

  /**
   * Restaura la posición de scroll de todos los elementos rastreados.
   */
  function restoreAllTracked() {
    const elements = getTrackedElements();
    elements.forEach(restoreScrollForElement);
  }

  /**
   * Vincula los escuchadores de eventos a los contenedores rastreados.
   */
  function bindScrollListeners() {
    const elements = getTrackedElements();

    elements.forEach((el) => {
      if (el.__scrollMemoryBound) return;
      el.__scrollMemoryBound = true;

      // Asegurar que el elemento tenga scroll-behavior: auto siempre
      el.style.setProperty('scroll-behavior', 'auto', 'important');

      // Detectar interacción manual real del usuario
      const onUserAction = () => {
        userInteractedSet.add(el);
      };
      el.addEventListener('wheel', onUserAction, { passive: true });
      el.addEventListener('touchstart', onUserAction, { passive: true });
      el.addEventListener('pointerdown', onUserAction, { passive: true });
      el.addEventListener('keydown', onUserAction, { passive: true });

      // Guardar con debounce en el evento scroll solo si es por interacción real
      el.addEventListener('scroll', () => {
        if (isProgrammaticScroll) return;

        const timer = scrollDebounceMap.get(el);
        if (timer) clearTimeout(timer);

        scrollDebounceMap.set(el, setTimeout(() => {
          saveScrollImmediate(el);
        }, 60));
      }, { passive: true });

      // Restaurar para este elemento de inmediato si no fue restaurado por el script inline
      restoreScrollForElement(el);
    });
  }

  // 1. Vincular al iniciar
  bindScrollListeners();

  // 2. Re-vincular cuando el contenido se actualiza en el cliente
  document.addEventListener('DOMContentLoaded', () => {
    bindScrollListeners();
  });

  window.addEventListener('load', () => {
    bindScrollListeners();
  });

  // 3. Soporte para BFCache (navegación atrás y adelante)
  window.addEventListener('pageshow', (e) => {
    bindScrollListeners();
    restoreAllTracked();
  });

  // 4. Actualizaciones de la vista previa en el dashboard (evento previewUpdated)
  document.addEventListener('previewUpdated', () => {
    bindScrollListeners();
  });

  // 5. Guardado síncrono al salir de la página o pulsar enlaces
  window.addEventListener('beforeunload', saveAllTracked);
  window.addEventListener('pagehide', saveAllTracked);

  document.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (link) {
      saveAllTracked();
    }
  });

  // API global para que autoSubmitForm y otros controladores sincronicen directamente
  window.__saveScrollMemory = saveAllTracked;
  window.__restoreScrollMemory = restoreAllTracked;
  window.__saveElementScroll = saveScrollImmediate;
  window.__restoreElementScroll = restoreScrollForElement;
}
