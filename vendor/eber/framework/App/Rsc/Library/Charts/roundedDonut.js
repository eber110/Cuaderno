/**
 * 📊 roundedDonut.js
 * 
 * Librería independiente para gráficos de donut con extremos redondeados (Mono Rounded Donut - Soft Arc Caps).
 * Cero dependencias externas.
 * Animación sincronizada con curva easeInOutCubic en 400ms.
 * 
 * @module RoundedDonut
 */

import { easeInOutCubic } from "./arcMeter.js";

/**
 * Anima un segmento de arco de donut desde dashoffset L hasta 0 (entrada) o 0 hasta L (salida).
 * 
 * @param {SVGPathElement} path Elemento path del segmento.
 * @param {Object} options Opciones de animación.
 * @param {number} [options.duration=400] Duración en ms.
 * @param {boolean} [options.isExit=false] Si es animación de salida.
 * @returns {{ cancel: Function }}
 */
export function animateDonutSegment(path, { duration = 400, isExit = false } = {}) {
  if (!path) return { cancel: () => {} };

  const arcLen = parseFloat(path.getAttribute("data-length") || "100");
  const startOffset = isExit ? 0 : arcLen;
  const targetOffset = isExit ? arcLen : 0;

  path.style.strokeDasharray = `${arcLen} ${arcLen}`;
  path.style.strokeDashoffset = `${startOffset}`;

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = Math.max(0, Math.min(elapsed / duration, 1));
    const ease = easeInOutCubic(progress);

    const currentOffset = startOffset + ((targetOffset - startOffset) * ease);
    path.style.strokeDashoffset = currentOffset.toFixed(2);

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      path.style.strokeDashoffset = `${targetOffset}`;
    }
  }

  rafId = requestAnimationFrame(step);
  return {
    cancel: () => {
      isCancelled = true;
      if (rafId) cancelAnimationFrame(rafId);
    }
  };
}

/**
 * Inicializa todos los gráficos de Rounded Donut.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initRoundedDonut(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);
    const isExit = svg.classList.contains("is-exiting");
    const segments = svg.querySelectorAll(".mono-donut-segment");

    segments.forEach((seg) => {
      animateDonutSegment(seg, { duration, isExit });
    });
  });
}

if (typeof window !== "undefined") {
  window.initRoundedDonut = initRoundedDonut;
  window.animateDonutSegment = animateDonutSegment;
}
