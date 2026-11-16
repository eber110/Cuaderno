/**
 * 📊 stackedTones.js
 * 
 * Librería independiente para columnas multicapa apiladas en cápsula (Stacked Tones).
 * Cero dependencias externas.
 * Crecimiento vertical simultáneo de 400ms desde la línea base con curva easeInOutCubic.
 * 
 * @module StackedTones
 */

import { easeInOutCubic, getChartContainerWidth, observeChartResize } from "./arcMeter.js";

/**
 * Ajusta el viewBox y reposiciona barras apiladas en Stacked Tones para conservar escala 1:1
 * y preservar `barWidth` exacto en píxeles en cualquier dispositivo.
 *
 * @param {SVGElement|HTMLElement} target Elemento SVG o contenedor.
 * @param {number} [explicitWidth] Ancho explícito en píxeles.
 */
export function fitStackedTonesToContainer(target, explicitWidth = 0) {
  if (!target) return;
  const svg = target.matches && target.matches("svg") ? target : (target.querySelector ? target.querySelector("svg") : null);
  if (!svg) return;

  const containerW = explicitWidth > 0 ? Math.round(explicitWidth) : getChartContainerWidth(svg);
  if (containerW <= 0) return;

  const vbParts = (svg.getAttribute("viewBox") || "0 0 320 190").trim().split(/\s+/);
  const vbH = parseFloat(vbParts[3] || "190") || 190;
  svg.setAttribute("viewBox", `0 0 ${containerW} ${vbH}`);
  svg._lastFitWidth = containerW;

  const plotX1 = 44;
  const plotX2 = Math.max(plotX1 + 40, containerW - 16);
  const gridX1 = 36;
  const gridX2 = Math.max(gridX1 + 40, containerW - 14);
  const availableW = Math.max(20, plotX2 - plotX1);

  svg.querySelectorAll(".mono-svg-grid-line").forEach((line) => {
    line.setAttribute("x1", gridX1.toString());
    line.setAttribute("x2", gridX2.toString());
  });

  const items = svg.querySelectorAll(".mono-stacked-bar-item");
  const clipRects = svg.querySelectorAll(".mono-stacked-clip-rect");
  const xLabels = svg.querySelectorAll(".mono-svg-axis-text-x");
  const count = items.length;
  if (count === 0) return;

  const slotWidth = availableW / count;
  const rawBarW = parseFloat(svg.getAttribute("data-bar-width") || "24");
  const barWidth = rawBarW > 0 ? rawBarW : 24;
  const rawRx = svg.getAttribute("data-rx");
  const rx = rawRx !== null ? parseFloat(rawRx) : barWidth / 2;

  items.forEach((item, idx) => {
    const cx = plotX1 + (idx + 0.5) * slotWidth;
    const qx = cx - barWidth / 2;

    if (clipRects[idx]) {
      clipRects[idx].setAttribute("x", qx.toFixed(2));
      clipRects[idx].setAttribute("width", barWidth.toFixed(2));
      clipRects[idx].setAttribute("rx", rx.toFixed(2));
      clipRects[idx].setAttribute("ry", rx.toFixed(2));
    }

    item.querySelectorAll(".mono-layer-top, .mono-layer-mid, .mono-layer-base").forEach((layer) => {
      layer.setAttribute("x", qx.toFixed(2));
      layer.setAttribute("width", barWidth.toFixed(2));
    });

    if (xLabels[idx]) {
      xLabels[idx].setAttribute("x", cx.toFixed(2));
      xLabels[idx].querySelectorAll("tspan").forEach((ts) => {
        ts.setAttribute("x", cx.toFixed(2));
      });
    }
  });
}

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

  if (rect._clipAnimCancel) {
    rect._clipAnimCancel();
  }

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
      rect._clipAnimCancel = null;
    }
  }

  rafId = requestAnimationFrame(step);
  const cancel = () => {
    isCancelled = true;
    if (rafId) cancelAnimationFrame(rafId);
    rect._clipAnimCancel = null;
  };
  rect._clipAnimCancel = cancel;
  return { cancel };
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

    observeChartResize(svg, fitStackedTonesToContainer);

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
  window.fitStackedTonesToContainer = fitStackedTonesToContainer;
}

