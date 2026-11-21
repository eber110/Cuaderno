/**
 * 📊 pyramidStack.js
 * 
 * Librería independiente para gráficos de pila piramidal (Pyramid Stack - Hierarchy).
 * Cero dependencias externas.
 * Animación simétrica horizontal desde el centro hacia afuera con curva easeInOutCubic en 400ms.
 * 
 * @module PyramidStack
 */

import { easeInOutCubic, getChartContainerWidth, observeChartResize } from "./arcMeter.js";

/**
 * Ajusta el viewBox y centra cada nivel de Pyramid Stack en `cx = containerW / 2`
 * para mantener escala 1:1 y preservar la altura exacta (`barWidth`/`barHeight`) en píxeles.
 *
 * @param {SVGElement|HTMLElement} target Elemento SVG o contenedor.
 * @param {number} [explicitWidth] Ancho explícito en píxeles.
 */
export function fitPyramidStackToContainer(target, explicitWidth = 0) {
  if (!target) return;
  const svg = target.matches && target.matches("svg") ? target : (target.querySelector ? target.querySelector("svg") : null);
  if (!svg) return;

  const containerW = explicitWidth > 0 ? Math.round(explicitWidth) : getChartContainerWidth(svg);
  if (containerW <= 0) return;

  const vbParts = (svg.getAttribute("viewBox") || "0 0 320 180").trim().split(/\s+/);
  const vbH = parseFloat(vbParts[3] || "180") || 180;
  svg.setAttribute("viewBox", `0 0 ${containerW} ${vbH}`);
  svg._lastFitWidth = containerW;

  const cx = containerW / 2;

  svg.querySelectorAll(".mono-pyramid-tier").forEach((tier) => {
    const rect = tier.querySelector(".mono-pyramid-rect");
    const text = tier.querySelector(".mono-pyramid-text");

    if (rect) {
      if (typeof rect._origTargetW !== "number") {
        rect._origTargetW = parseFloat(rect.getAttribute("data-target-w") || rect.getAttribute("width") || "100");
      }
      const targetW = Math.min(rect._origTargetW, Math.max(40, containerW - 16));
      const targetX = cx - targetW / 2;

      rect.setAttribute("data-target-w", targetW.toFixed(1));
      rect.setAttribute("data-target-x", targetX.toFixed(1));
      rect._pyramidTargetW = targetW;
      rect._pyramidCx = cx;

      if (!rect._isAnimating) {
        rect.setAttribute("width", targetW.toFixed(1));
        rect.setAttribute("x", targetX.toFixed(1));
      }
    }

    if (text) {
      text.setAttribute("x", cx.toFixed(1));
    }
  });
}

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

  if (rect._pyramidAnimCancel) {
    rect._pyramidAnimCancel();
  }

  const parsedDur = Number(duration);
  const safeDuration = Number.isFinite(parsedDur) && parsedDur >= 0 ? parsedDur : 400;

  const targetW = parseFloat(rect.getAttribute("data-target-w") || rect.getAttribute("width") || "100");
  const targetX = parseFloat(rect.getAttribute("data-target-x") || rect.getAttribute("x") || "50");
  if (!rect.hasAttribute("data-target-w")) {
    rect.setAttribute("data-target-w", targetW.toFixed(1));
  }
  if (!rect.hasAttribute("data-target-x")) {
    rect.setAttribute("data-target-x", targetX.toFixed(1));
  }
  const initialCx = targetX + (targetW / 2);

  if (!isExit) {
    rect.style.opacity = "";
  }
  rect._pyramidTargetW = targetW;
  rect._pyramidCx = initialCx;
  rect._isAnimating = true;

  const startW = isExit ? targetW : 0;
  const startX = isExit ? targetX : initialCx;

  rect.setAttribute("width", startW.toFixed(1));
  rect.setAttribute("x", startX.toFixed(1));
  if (text) text.style.opacity = isExit ? "1" : "0";

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = safeDuration > 0 ? Math.max(0, Math.min(elapsed / safeDuration, 1)) : 1;
    const ease = easeInOutCubic(progress);

    const liveTargetW = typeof rect._pyramidTargetW === "number" ? rect._pyramidTargetW : targetW;
    const liveCx = typeof rect._pyramidCx === "number" ? rect._pyramidCx : initialCx;
    const liveStartW = isExit ? liveTargetW : 0;
    const liveEndW = isExit ? 0 : liveTargetW;
    const liveEndX = isExit ? liveCx : (liveCx - liveTargetW / 2);

    const currentW = liveStartW + ((liveEndW - liveStartW) * ease);
    const currentX = liveCx - (currentW / 2);

    rect.setAttribute("width", Math.max(0, currentW).toFixed(1));
    rect.setAttribute("x", currentX.toFixed(1));

    if (text) {
      text.style.opacity = isExit ? (1 - ease).toFixed(2) : ease.toFixed(2);
    }

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      rect._isAnimating = false;
      rect.setAttribute("width", liveEndW.toFixed(1));
      rect.setAttribute("x", liveEndX.toFixed(1));
      if (text) text.style.opacity = isExit ? "0" : "1";
      rect._pyramidAnimCancel = null;
    }
  }

  const cancel = () => {
    isCancelled = true;
    rect._isAnimating = false;
    if (rafId) cancelAnimationFrame(rafId);
    rect._pyramidAnimCancel = null;
  };
  rect._pyramidAnimCancel = cancel;

  if (safeDuration === 0) {
    step(startTime);
    return { cancel };
  }

  rafId = requestAnimationFrame(step);
  return { cancel };
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

    observeChartResize(svg, fitPyramidStackToContainer);

    const rawDur = options.duration !== undefined
      ? options.duration
      : parseInt(svg.getAttribute("data-duration") ?? "400", 10);
    const duration = Number.isFinite(rawDur) && rawDur >= 0 ? rawDur : 400;
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
  window.fitPyramidStackToContainer = fitPyramidStackToContainer;
}
