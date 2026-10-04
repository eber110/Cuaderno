/**
 * 📊 simpleBars.js
 * 
 * Librería independiente para barras verticales en cápsula (Simple Bars).
 * Cero dependencias externas.
 * Crecimiento vertical dinámico de 400ms con curva easeInOutCubic.
 * 
 * @module SimpleBars
 */

import { easeInOutCubic } from "./arcMeter.js";

/**
 * Anima la geometría de una barra vertical desde la línea base hasta su altura final.
 * 
 * @param {SVGRectElement} rect Elemento rect de la barra a animar.
 * @param {Object} options Opciones de animación.
 * @param {number} [options.baseline=155] Posición Y de la línea base.
 * @param {number} options.targetH Altura final objetivo.
 * @param {number} [options.duration=400] Duración en ms.
 * @returns {{ cancel: Function }}
 */
export function animateSimpleBar(rect, { baseline = 155, targetH, duration = 400 }) {
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

    const currentH = Math.max(0, targetH * ease);
    const currentY = baseline - currentH;

    rect.setAttribute("height", currentH.toFixed(2));
    rect.setAttribute("y", currentY.toFixed(2));

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      rect.setAttribute("height", targetH.toFixed(2));
      rect.setAttribute("y", (baseline - targetH).toFixed(2));
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
 * Inicializa todos los gráficos de barras simples verticales.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initSimpleBars(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);
    const bars = svg.querySelectorAll(".mono-simple-bar-rect");

    bars.forEach((bar) => {
      const targetH = parseFloat(bar.getAttribute("data-target-h") || bar.getAttribute("height") || "50");
      animateSimpleBar(bar, { baseline: 155, targetH, duration });
    });
  });
}

if (typeof window !== "undefined") {
  window.initSimpleBars = initSimpleBars;
  window.animateSimpleBar = animateSimpleBar;
}
