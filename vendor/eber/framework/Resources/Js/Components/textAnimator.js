/**
 * ✍️ textAnimator.js
 * 
 * Librería universal de animaciones para texto y números en Vanilla JS (ES Modules).
 * Cero dependencias externas. Componente nativo del framework Eber.
 * 
 * Convención de clases (sin IDs):
 *   class="text-animation text-scramble"
 *   class="text-animation text-typewriter"
 *   class="text-animation text-fade-blur"
 *   class="text-animation text-slide-up"
 *   class="text-animation text-split-chars"
 *   class="text-animation text-counter"
 *   class="text-animation text-loop-pulse" (o text-pulse)
 *   class="text-animation text-loop-shimmer" (o text-shimmer)
 *   class="text-animation text-loop-breathing" (o text-breathing)
 * 
 * Todo elemento se descubre e inicializa automáticamente mediante querySelectorAll('.text-animation').
 * 
 * @module textAnimator
 */

/**
 * Glifos para el efecto Scramble / Decoder
 * 
 * @returns {string}
 */
function getScrambleGlyphs() {
  return "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
}

/**
 * Funciones de Easing matemáticas
 */
export const EASING = {
  easeOutExpo: (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t)),
  easeOutCubic: (t) => 1 - Math.pow(1 - t, 3),
  easeInOutQuad: (t) => (t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2),
  linear: (t) => t
};

/**
 * Formatea un número agregando separadores de miles y decimales
 * 
 * @param {number} value Valor numérico.
 * @param {number} decimals Cantidad de decimales.
 * @returns {string} Número formateado.
 */
export function formatNumber(value, decimals = 0) {
  const parts = Number(value).toFixed(decimals).split(".");
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  return parts.join(".");
}

/**
 * Anima un contador numérico con suavidad matemática (LERP / Easing).
 * 
 * @param {HTMLElement} el Elemento DOM objetivo.
 * @param {Object} options Opciones de configuración.
 * @returns {Object} Controlador con método cancel().
 */
export function animateCounter(el, options = {}) {
  if (!el) return null;

  if (el._counterController && typeof el._counterController.cancel === "function") {
    el._counterController.cancel();
  }

  const start = options.start !== undefined ? Number(options.start) : 0;
  const end = options.end !== undefined ? Number(options.end) : 100;
  const duration = options.duration || 1200;
  const decimals = options.decimals !== undefined ? options.decimals : 0;
  const prefix = options.prefix || "";
  const suffix = options.suffix || "";
  const easeFn = options.easing || EASING.easeOutExpo;
  const onUpdate = options.onUpdate || null;
  const onComplete = options.onComplete || null;

  let startTime = null;
  let rafId = null;
  let isCancelled = false;

  function step(timestamp) {
    if (isCancelled) return;
    if (!startTime) startTime = timestamp;

    const elapsed = timestamp - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const easedProgress = easeFn(progress);

    const currentVal = start + (end - start) * easedProgress;
    const formatted = `${prefix}${formatNumber(currentVal, decimals)}${suffix}`;

    el.textContent = formatted;

    if (onUpdate) {
      onUpdate(formatted, currentVal, progress);
    }

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      el.textContent = `${prefix}${formatNumber(end, decimals)}${suffix}`;
      el._counterController = null;
      if (onComplete) onComplete();
    }
  }

  rafId = requestAnimationFrame(step);

  const controller = {
    cancel: () => {
      isCancelled = true;
      if (rafId) cancelAnimationFrame(rafId);
      el._counterController = null;
    }
  };

  el._counterController = controller;
  return controller;
}

/**
 * Anima un elemento de texto según su efecto (scramble, typewriter, fade-blur, slide-up, split-chars).
 * 
 * @param {HTMLElement} el Elemento DOM objetivo.
 * @param {string} effect Tipo de efecto.
 * @param {Object} options Opciones adicionales.
 * @returns {Promise<void>}
 */
export function animateText(el, effect = "fade-blur", options = {}) {
  if (!el) return Promise.resolve();

  // Cancelar intervalos activos previos en este elemento
  if (el._textAnimInterval) {
    clearInterval(el._textAnimInterval);
    el._textAnimInterval = null;
  }

  // Eliminar cursor previo de typewriter si existe
  const next = el.nextElementSibling;
  if (next && next.classList.contains("anim-typewriter-cursor")) {
    next.remove();
  }

  // Limpiar clases de animación y salida previas
  el.classList.remove(
    "is-exiting",
    "text-fade-blur",
    "text-slide-up",
    "anim-enter-fade-blur",
    "anim-enter-slide-up",
    "anim-exit-fade-blur",
    "anim-exit-slide-down",
    "anim-exit-collapse",
    "anim-scramble-active"
  );

  const text = options.text !== undefined 
    ? options.text 
    : (el.dataset.originalText || el.textContent.trim());

  if (!el.dataset.originalText && text) {
    el.dataset.originalText = text;
  }

  const glyphs = getScrambleGlyphs();

  return new Promise((resolve) => {
    switch (effect) {
      case "scramble":
      case "decoder": {
        const duration = options.duration || 1100;
        const totalFrames = Math.max(18, Math.floor(duration / 30));
        let frame = 0;

        // Fijar dimensiones exactas antes de empezar para evitar cualquier salto o jitter
        const rect = el.getBoundingClientRect();
        const originalMinWidth = el.style.minWidth;
        const originalMinHeight = el.style.minHeight;

        if (rect.width > 0) {
          el.style.minWidth = `${Math.ceil(rect.width)}px`;
        }
        if (rect.height > 0) {
          el.style.minHeight = `${Math.ceil(rect.height)}px`;
        }

        el.classList.add("anim-scramble-active");

        el._textAnimInterval = setInterval(() => {
          frame++;
          const progress = frame / totalFrames;
          const revealedCharsCount = Math.floor(progress * text.length);

          let output = "";
          for (let i = 0; i < text.length; i++) {
            const char = text[i];
            if (char === " " || char === "•" || char === "-" || char === ":" || char === "%") {
              output += char;
            } else if (i < revealedCharsCount) {
              output += char;
            } else {
              const randomGlyph = glyphs[Math.floor(Math.random() * glyphs.length)];
              output += randomGlyph;
            }
          }

          el.textContent = output;

          if (frame >= totalFrames) {
            clearInterval(el._textAnimInterval);
            el._textAnimInterval = null;
            el.textContent = text;
            el.classList.remove("anim-scramble-active");
            el.style.minWidth = originalMinWidth || "";
            el.style.minHeight = originalMinHeight || "";
            resolve();
          }
        }, 30);
        break;
      }

      case "typewriter": {
        const speed = options.speed || 40;
        const showCursor = options.showCursor !== false;
        el.textContent = "";

        let cursor = null;
        if (showCursor) {
          cursor = document.createElement("span");
          cursor.className = "anim-typewriter-cursor";
          el.parentNode.insertBefore(cursor, el.nextSibling);
        }

        let charIndex = 0;
        el._textAnimInterval = setInterval(() => {
          if (charIndex < text.length) {
            el.textContent += text[charIndex];
            charIndex++;
          } else {
            clearInterval(el._textAnimInterval);
            el._textAnimInterval = null;
            if (cursor && options.keepCursor !== true) {
              setTimeout(() => cursor.remove(), 1200);
            }
            resolve();
          }
        }, speed);
        break;
      }

      case "split-chars": {
        el.innerHTML = "";
        const charDelay = options.delay || 30;
        const fragment = document.createDocumentFragment();

        [...text].forEach((char, index) => {
          const span = document.createElement("span");
          span.className = "anim-char-item";
          span.textContent = char === " " ? "\u00A0" : char;
          span.style.animationDelay = `${index * charDelay}ms`;
          fragment.appendChild(span);
        });

        el.appendChild(fragment);
        setTimeout(resolve, text.length * charDelay + 500);
        break;
      }

      case "slide-up": {
        el.textContent = text;
        void el.offsetWidth; // Forzar reflow
        el.classList.add("text-slide-up");
        setTimeout(resolve, 700);
        break;
      }

      case "fade-blur":
      default: {
        el.textContent = text;
        void el.offsetWidth; // Forzar reflow
        el.classList.add("text-fade-blur");
        setTimeout(resolve, 800);
        break;
      }
    }
  });
}

/**
 * Anima la salida de un elemento individual
 * 
 * @param {HTMLElement} el Elemento DOM.
 * @param {Object} options Opciones de salida.
 * @returns {Promise<void>}
 */
export function animateExit(el, options = {}) {
  if (!el) return Promise.resolve();

  // Caso especial: Contadores numéricos que bajan a 0
  if (el.classList.contains("text-counter") || el.dataset.animCounter !== undefined) {
    const rawVal = parseFloat(el.textContent.replace(/[^0-9.-]/g, "")) || 0;
    const prefix = el.dataset.prefix || "";
    const suffix = el.dataset.suffix || "";
    const decimals = parseInt(el.dataset.decimals || "0", 10);
    const duration = options.duration || 650;

    return new Promise((resolve) => {
      animateCounter(el, {
        start: rawVal,
        end: 0,
        duration,
        prefix,
        suffix,
        decimals,
        onComplete: () => {
          el.classList.add("is-exiting");
          setTimeout(resolve, 350);
        }
      });
    });
  }

  // Textos estándar
  return new Promise((resolve) => {
    el.classList.remove("text-fade-blur", "text-slide-up");
    void el.offsetWidth;

    if (el.classList.contains("text-typewriter") || el.classList.contains("text-slide-up")) {
      el.classList.add("text-exit-slide-down");
    } else if (el.classList.contains("text-split-chars")) {
      el.classList.add("text-exit-collapse");
    } else {
      el.classList.add("is-exiting");
    }

    setTimeout(resolve, options.duration || 500);
  });
}

/**
 * Anima un elemento individual con .text-animation según sus clases y atributos.
 * 
 * @param {HTMLElement} el
 */
export function playTextAnimation(el) {
  if (!el) return;

  el.classList.remove(
    "is-exiting",
    "text-exit-fade-blur",
    "text-exit-slide-down",
    "text-exit-collapse"
  );

  // 1. Contadores Numéricos
  if (el.classList.contains("text-counter")) {
    if (!el.dataset.target) {
      const textVal = el.textContent.trim();
      const numericVal = parseFloat(textVal.replace(/[^0-9.-]/g, "")) || 0;
      el.dataset.target = numericVal.toString();
      if (textVal.endsWith("%") && !el.dataset.suffix) el.dataset.suffix = "%";
      if (textVal.startsWith("$") && !el.dataset.prefix) el.dataset.prefix = "$";
    }

    const target = parseFloat(el.dataset.target || "100");
    const duration = parseInt(el.dataset.duration || "1200", 10);
    const decimals = parseInt(el.dataset.decimals || "0", 10);
    const prefix = el.dataset.prefix || "";
    const suffix = el.dataset.suffix || "";

    animateCounter(el, {
      start: 0,
      end: target,
      duration,
      decimals,
      prefix,
      suffix
    });
    return;
  }

  // 2. Efecto Scramble / Decoder
  if (el.classList.contains("text-scramble")) {
    animateText(el, "scramble");
    return;
  }

  // 3. Efecto Typewriter
  if (el.classList.contains("text-typewriter")) {
    animateText(el, "typewriter");
    return;
  }

  // 4. Efecto Split Chars
  if (el.classList.contains("text-split-chars")) {
    animateText(el, "split-chars");
    return;
  }

  // 5. Efecto Slide Up
  if (el.classList.contains("text-slide-up")) {
    animateText(el, "slide-up");
    return;
  }

  // 6. Efecto Fade Blur (o default si tiene .text-fade-blur)
  if (el.classList.contains("text-fade-blur")) {
    animateText(el, "fade-blur");
    return;
  }
}

/**
 * Descubre y ejecuta todas las animaciones de texto y números dentro de un contenedor mediante querySelectorAll.
 * Omite los elementos que están configurados para scroll (ob-*) salvo que se indique lo contrario.
 * 
 * @param {HTMLElement|Document} container Contenedor base de búsqueda.
 * @param {boolean} [includeObserved=false] Si true, también anima los elementos marcados para scroll.
 */
export function playAllTextAnimations(container = document, includeObserved = false) {
  const elements = container.querySelectorAll(".text-animation");

  elements.forEach((el) => {
    // Si tiene ob-* o está dentro de .observer y no se fuerza includeObserved, esperar a que el ScrollObserver lo active
    if (!includeObserved && (el.className.match(/(?:^|\s)ob-\d+/) || el.closest(".observer"))) {
      return;
    }

    playTextAnimation(el);
  });
}

/**
 * Ejecuta la animación de salida en todos los textos y contadores encontrados mediante querySelectorAll.
 * 
 * @param {HTMLElement|Document} container Contenedor base de búsqueda.
 */
export function playAllTextExits(container = document) {
  const elements = container.querySelectorAll(".text-animation");
  elements.forEach((el) => {
    animateExit(el);
  });
}

/**
 * Alterna o activa/desactiva los bucles continuos (pulse, shimmer, breathing).
 * 
 * @param {HTMLElement|Document} container Contenedor base de búsqueda.
 * @param {boolean|null} forceState Estado forzado true/false o null para toggle.
 */
export function toggleAllLoops(container = document, forceState = null) {
  const elements = container.querySelectorAll(".text-animation");

  elements.forEach((el) => {
    const isPulse = el.classList.contains("text-loop-pulse") || el.classList.contains("text-pulse");
    const isShimmer = el.classList.contains("text-loop-shimmer") || el.classList.contains("text-shimmer");
    const isBreathing = el.classList.contains("text-loop-breathing") || el.classList.contains("text-breathing");

    if (!isPulse && !isShimmer && !isBreathing) return;

    if (forceState === false || (forceState === null && !el.dataset.loopPaused)) {
      el.style.animationPlayState = "paused";
      el.dataset.loopPaused = "true";
    } else {
      el.style.animationPlayState = "running";
      delete el.dataset.loopPaused;
    }
  });
}

/**
 * Función principal para inicio automático con el framework Eber
 */
export function textAnimator() {
  playAllTextAnimations(document, false);
}

// Exposición global en window para accesibilidad universal
if (typeof window !== "undefined") {
  window.TextAnimator = {
    animateCounter,
    animateText,
    animateExit,
    playTextAnimation,
    playAllTextAnimations,
    playAllTextExits,
    toggleAllLoops,
    textAnimator,
    formatNumber,
    EASING
  };

  // Helpers directos globales
  window.animateText = animateText;
  window.animateCounter = animateCounter;
  window.playTextAnimation = playTextAnimation;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => textAnimator());
  } else {
    setTimeout(() => textAnimator(), 0);
  }
}
