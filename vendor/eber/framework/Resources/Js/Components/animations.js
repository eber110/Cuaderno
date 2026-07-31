/**
 * 🎬 Sistema de Animación Híbrido (CSS / GSAP)
 * Provee compatibilidad total y fluida entre clases de animación CSS y GSAP.
 * 
 * @module animations
 * @description Registra efectos GSAP equivalentes a las clases de CSS y expone
 *              un helper global 'window.animate()' con fallback transparente.
 *              Además inicializa el bindeo automático al hacer scroll.
 */

// Verificar disponibilidad de GSAP
const hasGsap = typeof gsap !== 'undefined';
const hasScrollTrigger = typeof ScrollTrigger !== 'undefined';

/**
 * Mapeo de nombres en camelCase (GSAP) a kebab-case (CSS)
 */
const effectToClassMap = {
  fadeIn: 'fade-in',
  fadeOut: 'fade-out',
  slideInLeft: 'slide-in-left',
  slideInRight: 'slide-in-right',
  slideInTop: 'slide-in-top',
  slideInBottom: 'slide-in-bottom',
  slideOutLeft: 'slide-out-left',
  slideOutRight: 'slide-out-right',
  slideOutTop: 'slide-out-top',
  slideOutBottom: 'slide-out-bottom',
  zoomIn: 'zoom-in',
  zoomOut: 'zoom-out',
  scaleIn: 'scale-in',
  scaleOut: 'scale-out',
  bounceIn: 'bounce-in',
  bounceOut: 'bounce-out',
  spin: 'spin',
  pulse: 'pulse'
};

/**
 * Registra efectos personalizados en GSAP para máxima compatibilidad
 */
function registerGsapEffects() {
  if (!hasGsap) return;

  const defaultDuration = 0.4;
  const defaultEase = 'power2.out';
  const elasticEase = 'back.out(1.5)';
  const inEase = 'power2.in';

  // 1. Fade
  gsap.registerEffect({
    name: 'fadeIn',
    effect: (targets, config) => gsap.fromTo(targets, { opacity: 0 }, { opacity: 1, ...config }),
    defaults: { duration: 0.6, ease: defaultEase }
  });

  gsap.registerEffect({
    name: 'fadeOut',
    effect: (targets, config) => gsap.to(targets, { opacity: 0, ...config }),
    defaults: { duration: defaultDuration, ease: inEase }
  });

  // 2. Slide In
  gsap.registerEffect({
    name: 'slideInLeft',
    effect: (targets, config) => gsap.fromTo(targets, { x: '-100%', opacity: 0 }, { x: 0, opacity: 1, ...config }),
    defaults: { duration: defaultDuration, ease: defaultEase }
  });

  gsap.registerEffect({
    name: 'slideInRight',
    effect: (targets, config) => gsap.fromTo(targets, { x: '100%', opacity: 0 }, { x: 0, opacity: 1, ...config }),
    defaults: { duration: defaultDuration, ease: defaultEase }
  });

  gsap.registerEffect({
    name: 'slideInTop',
    effect: (targets, config) => gsap.fromTo(targets, { y: '-100%', opacity: 0 }, { y: 0, opacity: 1, ...config }),
    defaults: { duration: defaultDuration, ease: defaultEase }
  });

  gsap.registerEffect({
    name: 'slideInBottom',
    effect: (targets, config) => gsap.fromTo(targets, { y: '100%', opacity: 0 }, { y: 0, opacity: 1, ...config }),
    defaults: { duration: defaultDuration, ease: defaultEase }
  });

  // 3. Slide Out
  gsap.registerEffect({
    name: 'slideOutLeft',
    effect: (targets, config) => gsap.to(targets, { x: '-100%', opacity: 0, ...config }),
    defaults: { duration: defaultDuration, ease: inEase }
  });

  gsap.registerEffect({
    name: 'slideOutRight',
    effect: (targets, config) => gsap.to(targets, { x: '100%', opacity: 0, ...config }),
    defaults: { duration: defaultDuration, ease: inEase }
  });

  gsap.registerEffect({
    name: 'slideOutTop',
    effect: (targets, config) => gsap.to(targets, { y: '-100%', opacity: 0, ...config }),
    defaults: { duration: defaultDuration, ease: inEase }
  });

  gsap.registerEffect({
    name: 'slideOutBottom',
    effect: (targets, config) => gsap.to(targets, { y: '100%', opacity: 0, ...config }),
    defaults: { duration: defaultDuration, ease: inEase }
  });

  // 4. Zoom / Scale
  gsap.registerEffect({
    name: 'zoomIn',
    effect: (targets, config) => gsap.fromTo(targets, { scale: 0.85, opacity: 0 }, { scale: 1, opacity: 1, ...config }),
    defaults: { duration: defaultDuration, ease: defaultEase }
  });

  gsap.registerEffect({
    name: 'zoomOut',
    effect: (targets, config) => gsap.to(targets, { scale: 0.85, opacity: 0, ...config }),
    defaults: { duration: defaultDuration, ease: inEase }
  });

  gsap.registerEffect({
    name: 'scaleIn',
    effect: (targets, config) => gsap.fromTo(targets, { scale: 0.85, opacity: 0 }, { scale: 1, opacity: 1, ...config }),
    defaults: { duration: defaultDuration, ease: defaultEase }
  });

  gsap.registerEffect({
    name: 'scaleOut',
    effect: (targets, config) => gsap.to(targets, { scale: 0.85, opacity: 0, ...config }),
    defaults: { duration: defaultDuration, ease: inEase }
  });

  // 5. Bounce
  gsap.registerEffect({
    name: 'bounceIn',
    effect: (targets, config) => gsap.fromTo(targets, { scale: 0.3, opacity: 0 }, { scale: 1, opacity: 1, ...config }),
    defaults: { duration: 0.55, ease: elasticEase }
  });

  gsap.registerEffect({
    name: 'bounceOut',
    effect: (targets, config) => gsap.to(targets, { scale: 0.3, opacity: 0, ...config }),
    defaults: { duration: defaultDuration, ease: inEase }
  });

  // 6. Spin
  gsap.registerEffect({
    name: 'spin',
    effect: (targets, config) => {
      const infinite = config.repeat === -1 || config.infinite;
      const duration = config.duration || 1.5;
      return gsap.to(targets, {
        rotation: 360,
        ease: 'none',
        duration: duration,
        repeat: infinite ? -1 : 0,
        ...config
      });
    },
    defaults: { infinite: false }
  });

  // 7. Pulse
  gsap.registerEffect({
    name: 'pulse',
    effect: (targets, config) => gsap.to(targets, {
      scale: 1.05,
      duration: 0.8,
      yoyo: true,
      repeat: -1,
      ease: 'power1.inOut',
      ...config
    })
  });
}

/**
 * Helper global para animar elementos con soporte de fallback CSS.
 * Expueto como `window.animate(element, effect, options)`.
 * 
 * @param {HTMLElement|string} element - Elemento o selector a animar
 * @param {string} effectName - Nombre de la animación (ej. 'fadeIn', 'slideInLeft')
 * @param {Object} [options={}] - Parámetros de configuración adicionales
 */
export function animate(element, effectName, options = {}) {
  const el = typeof element === 'string' ? document.querySelector(element) : element;
  if (!el) {
    console.warn(`[animate] Elemento no encontrado:`, element);
    return;
  }

  // 1. ANIMACIÓN MEDIANTE GSAP (Si está disponible)
  if (hasGsap && gsap.effects[effectName]) {
    // Si se especifica cleanup, lo hacemos al completar
    const originalComplete = options.onComplete;
    options.onComplete = () => {
      if (options.clearProps) {
        gsap.set(el, { clearProps: options.clearProps });
      }
      if (originalComplete) originalComplete();
    };

    gsap.effects[effectName](el, options);
    return;
  }

  // 2. FALLBACK MEDIANTE CSS TRANSITIONS/KEYFRAMES
  const cssClass = effectToClassMap[effectName] || effectName;
  
  // Detener animaciones previas
  el.classList.remove('animated', ...Object.values(effectToClassMap));
  
  // Forzar reflow para reiniciar la animación en el navegador
  void el.offsetWidth;

  // Aplicar configuraciones personalizadas mediante variables CSS locales
  if (options.duration) {
    const ms = typeof options.duration === 'number' ? `${options.duration * 1000}ms` : options.duration;
    el.style.setProperty('--animate-duration', ms);
  }
  if (options.delay) {
    const ms = typeof options.delay === 'number' ? `${options.delay * 1000}ms` : options.delay;
    el.style.setProperty('--animate-delay', ms);
  }
  if (options.ease) {
    el.style.setProperty('--animate-ease', options.ease);
  }

  // Escuchar fin de animación para onComplete y limpiezas
  const handleAnimationEnd = (e) => {
    if (e.target !== el) return;
    el.removeEventListener('animationend', handleAnimationEnd);
    el.removeEventListener('webkitAnimationEnd', handleAnimationEnd);

    // Limpieza si se solicita
    if (options.clearProps) {
      el.style.removeProperty('--animate-duration');
      el.style.removeProperty('--animate-delay');
      el.style.removeProperty('--animate-ease');
      el.classList.remove('animated', cssClass);
    }

    if (typeof options.onComplete === 'function') {
      options.onComplete();
    }
  };

  el.addEventListener('animationend', handleAnimationEnd);
  el.addEventListener('webkitAnimationEnd', handleAnimationEnd);

  // Aplicar clases para disparar animación CSS
  el.classList.add('animated', cssClass);
}

// Exponer la función al contexto global
window.animate = animate;

/**
 * Vincula de forma inteligente las animaciones de interacción con el mouse (hover, active) usando GSAP
 * si está disponible, permitiendo una personalización transparente con variables CSS.
 */
export function initGsapHoverAnimations() {
  if (!hasGsap) return;

  const hoverSelectors = [
    '.hover-scale',
    '.hover-scale-soft',
    '.hover-shrink',
    '.hover-lift',
    '.hover-lift-ns',
    '.hover-lift-no-shadow',
    '.hover-slide-right',
    '.hover-tilt',
    '.hover-spin',
    '.hover-glow',
    '.hover-press',
    '.hover-arrow'
  ];

  const elements = document.querySelectorAll(hoverSelectors.join(', '));

  elements.forEach(el => {
    if (el.dataset.gsapHoverBound) return;
    el.dataset.gsapHoverBound = "true";

    // Registrar eventos
    el.addEventListener('mouseenter', () => {
      const style = getComputedStyle(el);
      const durationIn = parseFloat(style.getPropertyValue('--hover-duration-in')) / 1000 || 0.25;

      if (el.classList.contains('hover-scale')) {
        const scale = parseFloat(style.getPropertyValue('--hover-scale')) || 1.05;
        gsap.to(el, { scale: scale, duration: durationIn, ease: 'back.out(1.5)', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-scale-soft')) {
        const scale = parseFloat(style.getPropertyValue('--hover-scale-soft')) || 1.005;
        const shadow = style.getPropertyValue('--hover-shadow-soft').trim() || '0 6px 12px rgba(0, 0, 0, 0.12)';
        const duration = parseFloat(style.getPropertyValue('--hover-duration-soft')) / 1000 || 0.35;
        gsap.to(el, { scale: scale, boxShadow: shadow, duration: duration, ease: 'power2.out', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-shrink')) {
        const scaleShrink = parseFloat(style.getPropertyValue('--hover-scale-shrink')) || 0.95;
        gsap.to(el, { scale: scaleShrink, duration: durationIn, ease: 'power2.out', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-lift')) {
        const yVal = style.getPropertyValue('--hover-y').trim() || '-6px';
        const shadow = style.getPropertyValue('--hover-shadow').trim() || '0 10px 20px rgba(0, 0, 0, 0.15)';
        gsap.to(el, { y: yVal, boxShadow: shadow, duration: durationIn, ease: 'power2.out', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-lift-ns') || el.classList.contains('hover-lift-no-shadow')) {
        const yVal = style.getPropertyValue('--hover-y').trim() || '-6px';
        gsap.to(el, { y: yVal, duration: durationIn, ease: 'power2.out', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-slide-right')) {
        const xVal = style.getPropertyValue('--hover-x').trim() || '6px';
        gsap.to(el, { x: xVal, duration: durationIn, ease: 'power2.out', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-tilt')) {
        const rotateVal = style.getPropertyValue('--hover-rotate-tilt').trim() || '-3deg';
        const scale = parseFloat(style.getPropertyValue('--hover-scale')) || 1.05;
        gsap.to(el, { rotation: rotateVal, scale: scale, duration: durationIn, ease: 'back.out(1.5)', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-spin')) {
        gsap.to(el, { rotation: 360, duration: 0.6, ease: 'power2.out', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-glow')) {
        const glowColor = style.getPropertyValue('--hover-glow-color').trim() || 'rgba(255, 255, 255, 0.4)';
        gsap.to(el, { filter: `drop-shadow(0px 0px 8px ${glowColor})`, duration: durationIn, ease: 'power2.out', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-arrow')) {
        const arrow = el.querySelector('svg, i');
        if (arrow) {
          gsap.to(arrow, { x: 5, duration: durationIn, ease: 'back.out(1.5)', overwrite: 'auto' });
        }
      }
    });

    el.addEventListener('mouseleave', () => {
      const style = getComputedStyle(el);
      const durationOut = parseFloat(style.getPropertyValue('--hover-duration-out')) / 1000 || 0.35;

      if (el.classList.contains('hover-scale') || el.classList.contains('hover-shrink') || el.classList.contains('hover-tilt')) {
        gsap.to(el, { scale: 1, rotation: 0, duration: durationOut, ease: 'power2.out', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-scale-soft')) {
        const duration = parseFloat(style.getPropertyValue('--hover-duration-soft')) / 1000 || 0.35;
        gsap.to(el, { 
          scale: 1, 
          boxShadow: '0px 0px 0px rgba(0,0,0,0)', 
          duration: duration, 
          ease: 'power2.out', 
          overwrite: 'auto',
          clearProps: 'boxShadow,scale'
        });
      }

      if (el.classList.contains('hover-lift')) {
        gsap.to(el, { 
          y: 0, 
          boxShadow: '0px 0px 0px rgba(0,0,0,0)', 
          duration: durationOut, 
          ease: 'power2.out', 
          overwrite: 'auto',
          clearProps: 'boxShadow,y'
        });
      }

      if (el.classList.contains('hover-lift-ns') || el.classList.contains('hover-lift-no-shadow')) {
        gsap.to(el, { y: 0, duration: durationOut, ease: 'power2.out', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-slide-right')) {
        gsap.to(el, { x: 0, duration: durationOut, ease: 'power2.out', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-spin')) {
        gsap.to(el, { rotation: 0, duration: 0.6, ease: 'power2.out', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-glow')) {
        gsap.to(el, { filter: 'drop-shadow(0px 0px 0px rgba(0,0,0,0))', duration: durationOut, ease: 'power2.out', overwrite: 'auto' });
      }

      if (el.classList.contains('hover-arrow')) {
        const arrow = el.querySelector('svg, i');
        if (arrow) {
          gsap.to(arrow, { x: 0, duration: durationOut, ease: 'power2.out', overwrite: 'auto' });
        }
      }
    });

    if (el.classList.contains('hover-press')) {
      el.addEventListener('mousedown', () => {
        const style = getComputedStyle(el);
        const config = { scale: 0.97, y: 2, duration: 0.1, ease: 'power2.out', overwrite: 'auto' };
        if (el.classList.contains('hover-lift')) {
          config.boxShadow = style.getPropertyValue('--hover-shadow-active').trim() || '0 4px 8px rgba(0, 0, 0, 0.1)';
        }
        gsap.to(el, config);
      });

      const handleRelease = () => {
        const style = getComputedStyle(el);
        const durationOut = parseFloat(style.getPropertyValue('--hover-duration-out')) / 1000 || 0.35;
        const hasLift = el.classList.contains('hover-lift') || el.classList.contains('hover-lift-ns') || el.classList.contains('hover-lift-no-shadow');
        const yVal = hasLift ? (style.getPropertyValue('--hover-y').trim() || '-6px') : 0;
        const scaleVal = el.classList.contains('hover-scale') ? (parseFloat(style.getPropertyValue('--hover-scale')) || 1.05) : 1;
        const shadowVal = el.classList.contains('hover-lift') ? (style.getPropertyValue('--hover-shadow').trim() || '0 10px 20px rgba(0, 0, 0, 0.15)') : 'none';

        gsap.to(el, {
          scale: scaleVal,
          y: yVal,
          boxShadow: shadowVal,
          duration: durationOut,
          ease: 'power2.out',
          overwrite: 'auto'
        });
      };

      el.addEventListener('mouseup', handleRelease);
      el.addEventListener('mouseleave', handleRelease);
    }
  });

}

/**
 * Inicializador automático del sistema de animaciones.
 * Escucha elementos con atributos 'data-animate' y los vincula.
 */
export function animations() {
  initAnimations();
}

export function initAnimations() {
  // Registrar efectos
  registerGsapEffects();

  // Activar compatibilidad GSAP para interacciones de mouse si GSAP está presente
  if (hasGsap) {
    document.documentElement.classList.add('gsap-loaded');
    initGsapHoverAnimations();
  }

  // Bindeo de elementos con scroll trigger
  const animScrollElements = document.querySelectorAll('[data-animate]');

  animScrollElements.forEach(el => {
    const effect = el.dataset.animate || 'fadeIn';
    const triggerMode = el.dataset.animateTrigger || 'scroll'; // 'scroll', 'load'
    const duration = parseFloat(el.dataset.animateDuration) || null;
    const delay = parseFloat(el.dataset.animateDelay) || null;
    const ease = el.dataset.animateEase || null;
    const infinite = el.classList.contains('animate-infinite') || el.dataset.animateInfinite === 'true';

    const options = {
      duration,
      delay,
      ease,
      infinite
    };

    if (triggerMode === 'load') {
      // Animar al cargar la página
      animate(el, effect, options);
    } else if (triggerMode === 'scroll') {
      // Animar al entrar al viewport (utilizando ScrollTrigger si está cargado)
      if (hasGsap && hasScrollTrigger) {
        // Opacidad inicial cero para evitar flash
        gsap.set(el, { opacity: 0 });

        ScrollTrigger.create({
          trigger: el,
          start: 'top 85%',
          onEnter: () => {
            gsap.set(el, { opacity: 1 }); // Restaurar antes de la animación
            animate(el, effect, options);
          },
          once: !infinite
        });
      } else {
        // Fallback básico con IntersectionObserver
        if ('IntersectionObserver' in window) {
          el.style.opacity = '0';
          
          const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                el.style.opacity = '';
                animate(el, effect, options);
                if (!infinite) observer.unobserve(el);
              }
            });
          }, { threshold: 0.15 });

          observer.observe(el);
        } else {
          // Fallback final: animar inmediatamente
          animate(el, effect, options);
        }
      }
    }
  });
}

// Auto-inicializar cuando el DOM esté listo si no se importa como módulo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAnimations);
} else {
  initAnimations();
}
