/**
 * 📊 simpleBars.js
 * 
 * Librería independiente para barras verticales en cápsula (Simple Bars).
 * Cero dependencias externas.
 * Crecimiento vertical dinámico de 400ms con curva easeInOutCubic.
 * 
 * @module SimpleBars
 */

import { easeInOutCubic, getChartContainerWidth, observeChartResize } from "./arcMeter.js";

/**
 * Ajusta el viewBox y reposiciona columnas de Simple Bars para mantener escala 1:1 en píxeles
 * y preservar exactamente `barWidth` en cualquier tamaño de pantalla.
 *
 * @param {SVGElement|HTMLElement} target Elemento SVG o contenedor.
 * @param {number} [explicitWidth] Ancho explícito en píxeles.
 */
export function fitSimpleBarsToContainer(target, explicitWidth = 0) {
  if (!target) return;
  const svg = target.matches && target.matches("svg") ? target : (target.querySelector ? target.querySelector("svg") : null);
  if (!svg) return;

  const containerW = explicitWidth > 0 ? Math.round(explicitWidth) : getChartContainerWidth(svg);
  if (containerW <= 0) return;

  const vbParts = (svg.getAttribute("viewBox") || "0 0 320 180").trim().split(/\s+/);
  const vbH = parseFloat(vbParts[3] || "180") || 180;
  svg.setAttribute("viewBox", `0 0 ${containerW} ${vbH}`);
  svg._lastFitWidth = containerW;

  const showAxisY = svg.getAttribute("data-show-axis-y") !== "0";
  const plotX1 = showAxisY ? 44 : 16;
  const plotX2 = Math.max(plotX1 + 40, containerW - 16);
  const gridX1 = showAxisY ? 36 : 16;
  const gridX2 = showAxisY ? Math.max(gridX1 + 40, containerW - 14) : plotX2;
  const availableW = Math.max(20, plotX2 - plotX1);

  svg.querySelectorAll(".mono-svg-grid-line").forEach((line) => {
    line.setAttribute("x1", gridX1.toString());
    line.setAttribute("x2", gridX2.toString());
  });

  const items = svg.querySelectorAll(".mono-simple-bar-item");
  const count = items.length;
  if (count === 0) return;

  const slotWidth = availableW / count;
  const rawBarW = parseFloat(svg.getAttribute("data-bar-width") || "18");
  const barWidth = rawBarW > 0 ? rawBarW : 18;
  const rawRx = svg.getAttribute("data-rx");
  const rx = rawRx !== null ? parseFloat(rawRx) : barWidth / 2;

  items.forEach((item, idx) => {
    const cx = plotX1 + (idx + 0.5) * slotWidth;
    const barX = cx - barWidth / 2;

    const track = item.querySelector(".mono-simple-bar-track");
    if (track) {
      track.setAttribute("x", barX.toFixed(2));
      track.setAttribute("width", barWidth.toFixed(2));
      track.setAttribute("rx", rx.toFixed(2));
    }

    const bar = item.querySelector(".mono-simple-bar-rect");
    if (bar) {
      bar.setAttribute("x", barX.toFixed(2));
      bar.setAttribute("width", barWidth.toFixed(2));
      bar.setAttribute("rx", rx.toFixed(2));
    }

    const axisX = item.querySelector(".mono-svg-axis-text-x");
    if (axisX) {
      axisX.setAttribute("x", cx.toFixed(2));
      axisX.querySelectorAll("tspan").forEach((ts) => {
        ts.setAttribute("x", cx.toFixed(2));
      });
    }
  });
}

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

  if (rect._simpleBarCancel) {
    rect._simpleBarCancel();
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

    const currentH = Math.max(0, targetH * ease);
    const currentY = baseline - currentH;

    rect.setAttribute("height", currentH.toFixed(2));
    rect.setAttribute("y", currentY.toFixed(2));

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      rect.setAttribute("height", targetH.toFixed(2));
      rect.setAttribute("y", (baseline - targetH).toFixed(2));
      rect._simpleBarCancel = null;
    }
  }

  rafId = requestAnimationFrame(step);
  const cancel = () => {
    isCancelled = true;
    if (rafId) cancelAnimationFrame(rafId);
    rect._simpleBarCancel = null;
  };
  rect._simpleBarCancel = cancel;
  return { cancel };
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

    observeChartResize(svg, fitSimpleBarsToContainer);

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
  window.fitSimpleBarsToContainer = fitSimpleBarsToContainer;
}
