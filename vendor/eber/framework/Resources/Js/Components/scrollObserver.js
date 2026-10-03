/**
 * 🔭 Módulo Universal de Intersection Observer (scrollObserver.js)
 * 
 * Gestiona de forma desacoplada y reactiva la detección de scroll en el viewport
 * para todos los selectores del DOM.
 * 
 * Funcionalidades:
 * 1. Monitoreo de contenedores '.observer' y sus elementos hijos (.slide-in-*, .fade-in, ob-*, dl-*, .text-animation).
 * 2. Monitoreo de elementos autónomos con clases 'ob-0' .. 'ob-100'.
 * 3. Integración transparente con textos animados (.text-animation) y animaciones clásicas del framework.
 * 4. API universal 'window.observe(selector, onEnter, options)' para callbacks personalizados en cualquier vista.
 * 
 * @module scrollObserver
 */

/**
 * Obtiene el mapa de observadores activos (función hoisted para evitar TDZ en bundling)
 * 
 * @returns {Map<HTMLElement, IntersectionObserver>}
 */
function getActiveObservers() {
  if (typeof window !== 'undefined') {
    if (!window._activeScrollObservers) {
      window._activeScrollObservers = new Map();
    }
    return window._activeScrollObservers;
  }
  return new Map();
}

/**
 * Obtiene la lista de umbrales finos (0.00 a 1.00 de 2% en 2%)
 * 
 * @returns {Array<number>}
 */
function getFineThresholds() {
  if (typeof window !== 'undefined' && window._fineThresholds) {
    return window._fineThresholds;
  }
  const thresholds = [];
  for (let i = 0; i <= 100; i += 2) {
    thresholds.push(i / 100);
  }
  if (typeof window !== 'undefined') {
    window._fineThresholds = thresholds;
  }
  return thresholds;
}

/**
 * Mapeo de clases CSS a efectos para compatibilidad con animaciones del framework
 * (Declarado como función hoisted para evitar cualquier ReferenceError en empaquetado minificado)
 * 
 * @returns {Object<string, string>}
 */
function getClassToEffectMap() {
  return {
    'fade-in': 'fadeIn',
    'fade-out': 'fadeOut',
    'slide-in-left': 'slideInLeft',
    'slide-in-right': 'slideInRight',
    'slide-in-top': 'slideInTop',
    'slide-in-bottom': 'slideInBottom',
    'slide-out-left': 'slideOutLeft',
    'slide-out-right': 'slideOutRight',
    'slide-out-top': 'slideOutTop',
    'slide-out-bottom': 'slideOutBottom',
    'zoom-in': 'zoomIn',
    'zoom-out': 'zoomOut',
    'scale-in': 'scaleIn',
    'scale-out': 'scaleOut',
    'bounce-in': 'bounceIn',
    'bounce-out': 'bounceOut',
    'spin': 'spin',
    'pulse': 'pulse',
    'pulse-once': 'pulseOnce'
  };
}

/**
 * Extrae el umbral numérico (0.0 a 1.0) desde las clases ob-N o atributos del elemento.
 * 
 * @param {HTMLElement} el Elemento DOM a inspeccionar.
 * @returns {number} Umbral entre 0.0 y 1.0 (por defecto 0.15).
 */
export function extractThreshold(el) {
  if (!el || !el.className) return 0.15;
  const match = el.className.match(/(?:^|\s)ob-(\d+)(?:\s|$)/);
  if (match) {
    const pct = parseInt(match[1], 10);
    return Math.min(1, Math.max(0, pct / 100));
  }
  if (el.dataset && el.dataset.obThreshold) {
    return parseFloat(el.dataset.obThreshold);
  }
  return 0.15;
}

/**
 * Extrae el retardo en segundos desde las clases dl-N (ej: dl-200 -> 0.2s).
 * 
 * @param {HTMLElement} el Elemento DOM a inspeccionar.
 * @returns {number|null} Retardo en segundos o null si no existe.
 */
export function extractDelay(el) {
  if (!el || !el.className) return null;
  const match = el.className.match(/(?:^|\s)dl-(\d+)(ms|s)?(?:\s|$)/);
  if (match) {
    const num = parseFloat(match[1]);
    const unit = match[2] || 'ms';
    return unit === 's' ? num : num / 1000;
  }
  if (el.dataset && el.dataset.animateDelay) {
    return parseFloat(el.dataset.animateDelay);
  }
  return null;
}

/**
 * Extrae la duración en segundos desde las clases dur-N (ej: dur-400 -> 0.4s).
 * 
 * @param {HTMLElement} el Elemento DOM a inspeccionar.
 * @returns {number|null} Duración en segundos o null si no existe.
 */
export function extractDuration(el) {
  if (!el || !el.className) return null;
  const match = el.className.match(/(?:^|\s)(?:dur|duration)-(\d+)(ms|s)?(?:\s|$)/);
  if (match) {
    const num = parseFloat(match[1]);
    const unit = match[2] || 'ms';
    return unit === 's' ? num : num / 1000;
  }
  if (el.dataset && el.dataset.animateDuration) {
    return parseFloat(el.dataset.animateDuration);
  }
  return null;
}

/**
 * Detecta el nombre del efecto de animación configurado en las clases o atributos del elemento.
 * 
 * @param {HTMLElement} el
 * @returns {string}
 */
export function extractEffect(el) {
  if (!el) return 'fadeIn';
  if (el.dataset && el.dataset.animate) {
    return el.dataset.animate;
  }
  if (el.classList) {
    const effectMap = getClassToEffectMap();
    for (const cls of Array.from(el.classList)) {
      if (effectMap[cls]) {
        return effectMap[cls];
      }
    }
  }
  return el.className && el.className.match(/(?:^|\s)ob-\d+/) ? 'fadeIn' : 'fadeIn';
}

/**
 * Ejecuta la acción correspondiente al entrar en pantalla:
 * - Si es .text-animation, activa el motor de textos (window.TextAnimator).
 * - Si tiene animaciones CSS/GSAP clásicas, llama a window.animate.
 * - Añade la clase .animated y marca data-ob-animated="true" para hacer visible el elemento.
 * 
 * @param {HTMLElement} el
 * @param {Object} [options={}]
 */
export function triggerElementAction(el, options = {}) {
  if (!el) return;
  if (el.dataset.obAnimated === 'true' && !options.infinite) return;
  el.dataset.obAnimated = 'true';
  el.classList.add('animated');

  // Disparar evento personalizado en el elemento
  el.dispatchEvent(new CustomEvent('inview', {
    bubbles: true,
    detail: { element: el, options }
  }));

  // 1. Textos animados (.text-animation)
  if (el.classList.contains('text-animation')) {
    const runText = () => {
      if (typeof window !== 'undefined' && window.TextAnimator?.playTextAnimation) {
        window.TextAnimator.playTextAnimation(el);
      }
    };

    if (typeof window !== 'undefined' && window.TextAnimator) {
      runText();
    } else {
      setTimeout(runText, 60);
    }
    return;
  }

  // 2. Animaciones clásicas CSS / GSAP del framework mediante window.animate
  const effect = options.effect || extractEffect(el);
  if (effect && typeof window !== 'undefined' && typeof window.animate === 'function') {
    window.animate(el, effect, {
      delay: options.delay !== undefined ? options.delay : extractDelay(el),
      duration: options.duration !== undefined ? options.duration : extractDuration(el),
      infinite: options.infinite || el.classList.contains('animate-infinite') || el.dataset.animateInfinite === 'true'
    });
  }
}

/**
 * Observa cualquier elemento o lista de elementos del DOM con IntersectionObserver universal.
 * 
 * @param {string|HTMLElement|NodeList|Array} target Selector CSS o elemento(s) a observar.
 * @param {Function} [onEnter=null] Callback ejecutado cuando el elemento entra al umbral (recibe: el, entry).
 * @param {Object} [options={}] Opciones de configuración.
 * @param {number|Array} [options.threshold] Umbral específico (ej: 0.25). Si se omite, se extrae de ob-* o 0.15.
 * @param {string} [options.rootMargin="0px"] Margen del viewport.
 * @param {HTMLElement|null} [options.root=null] Elemento raíz contenedor (null = viewport).
 * @param {boolean} [options.once=true] Si true, deja de observar tras el primer disparo.
 * @param {Function} [options.onLeave=null] Callback ejecutado cuando el elemento sale del viewport.
 * @returns {Map<HTMLElement, IntersectionObserver>} Mapa de observadores activos.
 */
export function observe(target, onEnter = null, options = {}) {
  const elements = resolveElements(target);
  const activeObservers = getActiveObservers();
  if (elements.length === 0) return activeObservers;

  if (typeof window === 'undefined' || !('IntersectionObserver' in window)) {
    elements.forEach(el => {
      if (typeof onEnter === 'function') onEnter(el, null);
      triggerElementAction(el, options);
    });
    return activeObservers;
  }

  const once = options.once !== false && !options.infinite;
  const rootMargin = options.rootMargin || '0px';
  const root = options.root || null;
  const fineThresholds = getFineThresholds();

  elements.forEach(el => {
    const threshold = options.threshold !== undefined 
      ? options.threshold 
      : extractThreshold(el);

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting && entry.intersectionRatio <= 0) return;

        const rect = entry.boundingClientRect;
        const vh = window.innerHeight || document.documentElement.clientHeight;
        const enteredDistance = vh - rect.top;
        const scrollRatio = rect.height > 0 ? (enteredDistance / rect.height) : 0;
        const currentRatio = Math.min(1, Math.max(entry.intersectionRatio || 0, scrollRatio));
        const requiredRatio = typeof threshold === 'number' ? threshold : (threshold[0] ?? 0.15);

        if (currentRatio >= requiredRatio) {
          el.dispatchEvent(new CustomEvent('inview', {
            bubbles: true,
            detail: { entry, ratio: currentRatio }
          }));

          if (typeof onEnter === 'function') {
            onEnter(el, entry);
          }

          triggerElementAction(el, options);

          if (once) {
            observer.unobserve(el);
            activeObservers.delete(el);
          }
        } else if (!entry.isIntersecting && typeof options.onLeave === 'function') {
          options.onLeave(el, entry);
        }
      });
    }, {
      root,
      rootMargin,
      threshold: fineThresholds
    });

    activeObservers.set(el, observer);
    observer.observe(el);
  });

  return activeObservers;
}

/**
 * Deja de observar un elemento o selector específico.
 * 
 * @param {string|HTMLElement|NodeList|Array} target Selector o elemento a desvincular.
 */
export function unobserve(target) {
  const elements = resolveElements(target);
  const activeObservers = getActiveObservers();
  elements.forEach(el => {
    const obs = activeObservers.get(el);
    if (obs) {
      obs.unobserve(el);
      activeObservers.delete(el);
    }
  });
}

/**
 * Resuelve selectores, NodeLists, Arrays o elementos individuales en un array plano de HTMLElements.
 * 
 * @param {any} target
 * @returns {Array<HTMLElement>}
 */
function resolveElements(target) {
  if (!target) return [];
  if (typeof target === 'string') {
    return Array.from(document.querySelectorAll(target));
  }
  if (target instanceof HTMLElement) {
    return [target];
  }
  if (target instanceof NodeList || Array.isArray(target)) {
    return Array.from(target).filter(item => item instanceof HTMLElement);
  }
  return [];
}

/**
 * Inicializa el escaneo y monitoreo automático reactivo para contenedores '.observer' y elementos 'ob-*'.
 * 
 * @function initScrollObserver
 */
export function initScrollObserver() {
  if (typeof window === 'undefined') return;

  if (!('IntersectionObserver' in window)) {
    document.querySelectorAll('.observer [class*="ob-"], [class*="ob-"], [data-observe]').forEach(el => {
      triggerElementAction(el);
    });
    return;
  }

  const fineThresholds = getFineThresholds();

  // 1. Manejo de Contenedores .observer (cada contenedor tiene su propia instancia aislada)
  const observerContainers = document.querySelectorAll('.observer');

  observerContainers.forEach(container => {
    if (container.dataset.obContainerBound === 'true') return;
    container.dataset.obContainerBound = 'true';

    const childSelector = '[class*="ob-"], .slide-in-bottom, .slide-in-top, .slide-in-left, .slide-in-right, .slide-out-bottom, .slide-out-top, .slide-out-left, .slide-out-right, .fade-in, .fade-out, .zoom-in, .zoom-out, .scale-in, .scale-out, .bounce-in, .bounce-out, .spin, .pulse, .pulse-once, .text-animation, [data-animate]';
    const allDescendants = Array.from(container.querySelectorAll(childSelector));
    const animChildren = allDescendants.filter(el => el.closest('.observer') === container);

    if (animChildren.length === 0) return;

    const items = animChildren.map(el => {
      return {
        element: el,
        threshold: extractThreshold(el),
        delay: extractDelay(el),
        duration: extractDuration(el),
        effect: extractEffect(el),
        infinite: el.classList.contains('animate-infinite') || el.dataset.animateInfinite === 'true',
        animated: false
      };
    });

    const containerObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting && entry.intersectionRatio <= 0) return;

        const rect = entry.boundingClientRect;
        const vh = window.innerHeight || document.documentElement.clientHeight;
        const enteredDistance = vh - rect.top;
        const scrollRatio = rect.height > 0 ? (enteredDistance / rect.height) : 0;
        const currentRatio = Math.min(1, Math.max(entry.intersectionRatio || 0, scrollRatio));

        let allDone = true;

        items.forEach(item => {
          if (item.animated && !item.infinite) return;

          if (currentRatio >= item.threshold) {
            item.animated = true;
            triggerElementAction(item.element, {
              delay: item.delay,
              duration: item.duration,
              infinite: item.infinite,
              effect: item.effect
            });
          } else {
            allDone = false;
          }
        });

        if (allDone && !items.some(it => it.infinite)) {
          containerObserver.unobserve(container);
        }
      });
    }, {
      root: null,
      threshold: fineThresholds,
      rootMargin: '0px 0px 0px 0px'
    });

    containerObserver.observe(container);
  });

  // 2. Manejo de Elementos Autónomos (fuera de cualquier contenedor .observer)
  const autonomousElements = document.querySelectorAll('[class*="ob-"], [data-observe]');
  autonomousElements.forEach(el => {
    if (el.closest('.observer')) return;
    if (el.dataset.obElementBound === 'true') return;
    el.dataset.obElementBound = 'true';

    const threshold = extractThreshold(el);
    const delay = extractDelay(el);
    const duration = extractDuration(el);
    const effect = extractEffect(el);
    const infinite = el.classList.contains('animate-infinite') || el.dataset.animateInfinite === 'true';

    const singleObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting && entry.intersectionRatio <= 0) return;

        const rect = entry.boundingClientRect;
        const vh = window.innerHeight || document.documentElement.clientHeight;
        const enteredDistance = vh - rect.top;
        const scrollRatio = rect.height > 0 ? (enteredDistance / rect.height) : 0;
        const currentRatio = Math.min(1, Math.max(entry.intersectionRatio || 0, scrollRatio));

        if (currentRatio >= threshold) {
          triggerElementAction(el, {
            delay,
            duration,
            infinite,
            effect
          });
          if (!infinite) {
            singleObserver.unobserve(el);
          }
        }
      });
    }, {
      root: null,
      threshold: fineThresholds
    });

    singleObserver.observe(el);
  });
}

/**
 * Función exportada estándar para el framework
 */
export function scrollObserver() {
  initScrollObserver();
}

// Auto-inicialización y exposición global segura
if (typeof window !== 'undefined') {
  window.ScrollObserver = {
    observe,
    unobserve,
    extractThreshold,
    extractDelay,
    extractDuration,
    extractEffect,
    triggerElementAction,
    initScrollObserver,
    scrollObserver
  };

  // Helper directo global
  window.observe = observe;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initScrollObserver());
  } else {
    setTimeout(() => initScrollObserver(), 0);
  }
}
