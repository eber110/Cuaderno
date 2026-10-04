/**
 * 📊 horizontalBars.js
 * 
 * Librería independiente para barras horizontales en cápsula (Horizontal Bars).
 * Cero dependencias externas.
 * Crecimiento horizontal dinámico de 400ms con curva easeInOutCubic.
 * 
 * @module HorizontalBars
 */

import { easeInOutCubic } from "./arcMeter.js";

/**
 * Anima el ancho horizontal de una barra desde 0 hasta su ancho final.
 * 
 * @param {SVGRectElement} rect Elemento rect de la barra horizontal.
 * @param {Object} options Opciones de animación.
 * @param {number} options.targetW Ancho final objetivo.
 * @param {number} [options.duration=400] Duración en ms.
 * @returns {{ cancel: Function }}
 */
export function animateHorizontalBar(rect, { targetW, duration = 400 }) {
  if (!rect) return { cancel: () => {} };

  rect.setAttribute("width", "0");

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = Math.max(0, Math.min(elapsed / duration, 1));
    const ease = easeInOutCubic(progress);

    const currentW = Math.max(0, targetW * ease);
    rect.setAttribute("width", currentW.toFixed(2));

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      rect.setAttribute("width", targetW.toFixed(2));
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
 * Inicializa todos los gráficos de barras horizontales.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initHorizontalBars(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);
    const bars = svg.querySelectorAll(".mono-hbar-rect");

    bars.forEach((bar) => {
      const targetW = parseFloat(bar.getAttribute("data-target-w") || bar.getAttribute("width") || "50");
      animateHorizontalBar(bar, { targetW, duration });
    });
  });
}

if (typeof window !== "undefined") {
  window.initHorizontalBars = initHorizontalBars;
  window.animateHorizontalBar = animateHorizontalBar;
}
