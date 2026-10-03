/**
 * 📊 stackedTones.js
 * 
 * Librería independiente para columnas multicapa apiladas en cápsula (Stacked Tones).
 * Cero dependencias externas.
 * Crecimiento vertical simultáneo de 400ms desde la línea base con curva easeInOutCubic.
 * 
 * @module StackedTones
 */

import { easeInOutCubic } from "./arcMeter.js";

/**
 * Anima la geometría de los rectángulos del clipPath desde la línea base hasta su altura final.
 * 
 * @param {SVGRectElement} rect Rectángulo del clipPath a animar.
 * @param {Object} options Opciones de animación.
 * @param {number} options.baseline Posición Y de la línea base (ej. 155).
 * @param {number} options.targetVal Altura final objetivo.
 * @param {number} [options.duration=400] Duración en ms.
 * @returns {{ cancel: Function }}
 */
export function animateClipRect(rect, { baseline = 155, targetVal, duration = 400 }) {
  if (!rect) return { cancel: () => {} };

  rect.setAttribute("height", "0");
  rect.setAttribute("y", baseline.toString());

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = Math.max(0, Math.min(elapsed / duration, 1));
    const ease = easeInOutCubic(progress);

    const currentVal = Math.max(0, targetVal * ease);
    const currentY = baseline - currentVal;

    rect.setAttribute("height", currentVal.toFixed(2));
    rect.setAttribute("y", currentY.toFixed(2));

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      const finalVal = Math.max(0, targetVal);
      rect.setAttribute("height", finalVal.toFixed(2));
      rect.setAttribute("y", (baseline - finalVal).toFixed(2));
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
 * Inicializa un gráfico de Stacked Tones.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initStackedTones(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);
    const clipRects = svg.querySelectorAll(".mono-stacked-clip-rect");

    clipRects.forEach((rect) => {
      const targetH = parseFloat(rect.getAttribute("data-target-h") || rect.getAttribute("height") || "100");
      animateClipRect(rect, { baseline: 155, targetVal: targetH, duration });
    });
  });
}

if (typeof window !== "undefined") {
  window.initStackedTones = initStackedTones;
  window.animateClipRect = animateClipRect;
}

