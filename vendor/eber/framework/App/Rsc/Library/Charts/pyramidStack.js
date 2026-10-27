/**
 * 📊 pyramidStack.js
 * 
 * Librería independiente para gráficos de pila piramidal (Pyramid Stack - Hierarchy).
 * Cero dependencias externas.
 * Animación simétrica horizontal desde el centro hacia afuera con curva easeInOutCubic en 400ms.
 * 
 * @module PyramidStack
 */

import { easeInOutCubic } from "./arcMeter.js";

/**
 * Anima una cápsula de nivel de pirámide desde el centro hacia su ancho objetivo.
 * 
 * @param {SVGGElement} group Grupo g.mono-pyramid-tier.
 * @param {Object} options Opciones de animación.
 * @param {number} [options.duration=400] Duración en ms.
 * @param {boolean} [options.isExit=false] Si es animación de salida.
 * @returns {{ cancel: Function }}
 */
export function animatePyramidTier(group, { duration = 400, isExit = false } = {}) {
  if (!group) return { cancel: () => {} };

  const rect = group.querySelector(".mono-pyramid-rect");
  const text = group.querySelector(".mono-pyramid-text");
  if (!rect) return { cancel: () => {} };

  const targetW = parseFloat(rect.getAttribute("data-target-w") || rect.getAttribute("width") || "100");
  const targetX = parseFloat(rect.getAttribute("data-target-x") || rect.getAttribute("x") || "50");
  const cx = targetX + (targetW / 2);

  const startW = isExit ? targetW : 0;
  const endW = isExit ? 0 : targetW;
  const startX = isExit ? targetX : cx;
  const endX = isExit ? cx : targetX;

  rect.setAttribute("width", startW.toFixed(1));
  rect.setAttribute("x", startX.toFixed(1));
  if (text) text.style.opacity = isExit ? "1" : "0";

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = Math.max(0, Math.min(elapsed / duration, 1));
    const ease = easeInOutCubic(progress);

    const currentW = startW + ((endW - startW) * ease);
    const currentX = cx - (currentW / 2);

    rect.setAttribute("width", Math.max(0, currentW).toFixed(1));
    rect.setAttribute("x", currentX.toFixed(1));

    if (text) {
      text.style.opacity = isExit ? (1 - ease).toFixed(2) : ease.toFixed(2);
    }

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      rect.setAttribute("width", endW.toFixed(1));
      rect.setAttribute("x", endX.toFixed(1));
      if (text) text.style.opacity = isExit ? "0" : "1";
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
 * Inicializa todos los gráficos de Pyramid Stack.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initPyramidStack(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);
    const isExit = svg.classList.contains("is-exiting");
    const tiers = svg.querySelectorAll(".mono-pyramid-tier");

    tiers.forEach((tier) => {
      animatePyramidTier(tier, { duration, isExit });
    });
  });
}

if (typeof window !== "undefined") {
  window.initPyramidStack = initPyramidStack;
  window.animatePyramidTier = animatePyramidTier;
}
