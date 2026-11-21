/**
 * 📊 roundedDonut.js
 * 
 * Librería independiente para gráficos de donut con extremos redondeados (Mono Rounded Donut - Soft Arc Caps).
 * Cero dependencias externas.
 * Animación sincronizada con curva easeInOutCubic en 400ms.
 * 
 * @module RoundedDonut
 */

import { easeInOutCubic, safeGetTotalLength } from "./arcMeter.js";

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

  if (typeof path._donutAnimCancel === "function") {
    path._donutAnimCancel();
  }

  const parsedDur = Number(duration);
  const safeDuration = Number.isFinite(parsedDur) && parsedDur >= 0 ? parsedDur : 400;

  const parsedLen = parseFloat(path.getAttribute("data-length") || "0");
  const arcLen = parsedLen > 0 ? parsedLen : safeGetTotalLength(path, 100);
  const startOffset = isExit ? 0 : arcLen;
  const targetOffset = isExit ? arcLen : 0;

  if (!isExit) {
    path.style.opacity = "";
  }
  path.style.strokeDasharray = `${arcLen} ${arcLen}`;
  path.style.strokeDashoffset = `${startOffset}`;

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = safeDuration > 0 ? Math.max(0, Math.min(elapsed / safeDuration, 1)) : 1;
    const ease = easeInOutCubic(progress);

    const currentOffset = startOffset + ((targetOffset - startOffset) * ease);
    path.style.strokeDashoffset = currentOffset.toFixed(2);

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      path.style.strokeDashoffset = `${targetOffset}`;
      path._donutAnimCancel = null;
    }
  }

  const cancel = () => {
    isCancelled = true;
    if (rafId) cancelAnimationFrame(rafId);
    path._donutAnimCancel = null;
  };
  path._donutAnimCancel = cancel;

  if (safeDuration === 0) {
    step(startTime);
    return { cancel };
  }

  rafId = requestAnimationFrame(step);
  return { cancel };
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

    const rawDur = options.duration !== undefined
      ? options.duration
      : parseInt(svg.getAttribute("data-duration") ?? "400", 10);
    const duration = Number.isFinite(rawDur) && rawDur >= 0 ? rawDur : 400;
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
