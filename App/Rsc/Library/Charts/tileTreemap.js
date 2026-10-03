/**
 * 📊 tileTreemap.js
 * 
 * Librería independiente para partición de mosaicos vectoriales (Tile Treemap).
 * Cero dependencias externas.
 * Fade in suave frame a frame de 400ms con curva easeInOutCubic (sin aparición tosca).
 * 
 * @module TileTreemap
 */

import { easeInOutCubic } from "./arcMeter.js";

/**
 * Anima la opacidad progresiva de un elemento con RAF continuo.
 * 
 * @param {SVGElement|HTMLElement} el Elemento a desvanecer.
 * @param {Object} [options={}] Opciones.
 * @param {number} [options.duration=400] Duración del fade en ms.
 * @returns {{ cancel: Function }}
 */
export function animateTileFade(el, { duration = 400 } = {}) {
  if (!el) return { cancel: () => {} };

  el.style.opacity = "0";

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = Math.max(0, Math.min(elapsed / duration, 1));
    const ease = easeInOutCubic(progress);

    el.style.opacity = Math.max(0, Math.min(1, ease)).toFixed(4);

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      el.style.opacity = "1";
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
 * Inicializa un gráfico de Tile Treemap.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initTileTreemap(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);
    const tileGroups = svg.querySelectorAll(".mono-treemap-tile-group");

    tileGroups.forEach((tile) => {
      animateTileFade(tile, { duration });
    });
  });
}

if (typeof window !== "undefined") {
  window.initTileTreemap = initTileTreemap;
  window.animateTileFade = animateTileFade;
}

