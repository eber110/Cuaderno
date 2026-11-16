/**
 * 📊 pillPillars.js
 * 
 * Librería independiente para columnas emparejadas en cápsula (Pill Pillars).
 * Cero dependencias externas.
 * Crecimiento vertical simultáneo en 400ms desde la línea base con curva easeInOutCubic.
 * 
 * @module PillPillars
 */

import { easeInOutCubic, getChartContainerWidth, observeChartResize } from "./arcMeter.js";

/**
 * Ajusta el viewBox y reposiciona parejas de Pill Pillars manteniendo escala 1:1
 * y grosor `barWidth` / `pillarWidth` exacto en píxeles en cualquier dispositivo.
 *
 * @param {SVGElement|HTMLElement} target Elemento SVG o contenedor.
 * @param {number} [explicitWidth] Ancho explícito en píxeles.
 */
export function fitPillPillarsToContainer(target, explicitWidth = 0) {
  if (!target) return;
  const svg = target.matches && target.matches("svg") ? target : (target.querySelector ? target.querySelector("svg") : null);
  if (!svg) return;

  const containerW = explicitWidth > 0 ? Math.round(explicitWidth) : getChartContainerWidth(svg);
  if (containerW <= 0) return;

  const vbParts = (svg.getAttribute("viewBox") || "0 0 320 180").trim().split(/\s+/);
  const vbH = parseFloat(vbParts[3] || "180") || 180;
  svg.setAttribute("viewBox", `0 0 ${containerW} ${vbH}`);
  svg._lastFitWidth = containerW;

  const plotX1 = 36;
  const plotX2 = Math.max(plotX1 + 40, containerW - 16);
  const gridX1 = 28;
  const gridX2 = Math.max(gridX1 + 40, containerW - 14);
  const availableW = Math.max(20, plotX2 - plotX1);

  svg.querySelectorAll(".mono-svg-grid-line").forEach((line) => {
    line.setAttribute("x1", gridX1.toString());
    line.setAttribute("x2", gridX2.toString());
  });

  const groups = svg.querySelectorAll(".mono-pillar-pair-group");
  const count = groups.length;
  if (count === 0) return;

  const slotWidth = availableW / count;
  const rawW = parseFloat(svg.getAttribute("data-bar-width") || "12");
  const pillarWidth = rawW > 0 ? rawW : 12;
  const rawGap = svg.getAttribute("data-pillar-gap");
  const pillarGap = rawGap !== null ? parseFloat(rawGap) : Math.min(6, Math.max(2, pillarWidth * 0.25));
  const rawRx = svg.getAttribute("data-rx");
  const rx = rawRx !== null ? parseFloat(rawRx) : pillarWidth / 2;

  groups.forEach((group, idx) => {
    const cx = plotX1 + (idx + 0.5) * slotWidth;
    const x1 = cx - pillarWidth - pillarGap / 2;
    const x2 = cx + pillarGap / 2;

    const p1 = group.querySelector(".mono-pillar-primary");
    if (p1) {
      p1.setAttribute("x", x1.toFixed(2));
      p1.setAttribute("width", pillarWidth.toFixed(2));
      p1.setAttribute("rx", rx.toFixed(2));
    }

    const p2 = group.querySelector(".mono-pillar-secondary");
    if (p2) {
      p2.setAttribute("x", x2.toFixed(2));
      p2.setAttribute("width", pillarWidth.toFixed(2));
      p2.setAttribute("rx", rx.toFixed(2));
    }

    const axisX = group.querySelector(".mono-svg-axis-text-x");
    if (axisX) {
      axisX.setAttribute("x", cx.toFixed(2));
      axisX.querySelectorAll("tspan").forEach((ts) => {
        ts.setAttribute("x", cx.toFixed(2));
      });
    }
  });
}

/**
 * Inicializa un gráfico de Pill Pillars.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initPillPillars(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    observeChartResize(svg, fitPillPillarsToContainer);

    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);
    const baseline = 155;
    const pillars = svg.querySelectorAll(".mono-pillar-primary, .mono-pillar-secondary");

    pillars.forEach((pillar) => {
      if (pillar._pillarRaf) {
        cancelAnimationFrame(pillar._pillarRaf);
      }
      const targetH = parseFloat(pillar.getAttribute("data-target-h") || pillar.getAttribute("height") || "80");
      pillar.setAttribute("height", "0");
      pillar.setAttribute("y", baseline.toString());

      const startTime = performance.now();
      function step(now) {
        const elapsed = Math.max(0, now - startTime);
        const progress = Math.max(0, Math.min(elapsed / duration, 1));
        const ease = easeInOutCubic(progress);
        const currentH = Math.max(0, targetH * ease);

        pillar.setAttribute("height", currentH.toFixed(2));
        pillar.setAttribute("y", (baseline - currentH).toFixed(2));

        if (progress < 1) {
          pillar._pillarRaf = requestAnimationFrame(step);
        } else {
          const finalH = Math.max(0, targetH);
          pillar.setAttribute("height", finalH.toFixed(2));
          pillar.setAttribute("y", (baseline - finalH).toFixed(2));
          pillar._pillarRaf = null;
        }
      }
      pillar._pillarRaf = requestAnimationFrame(step);
    });
  });
}

if (typeof window !== "undefined") {
  window.initPillPillars = initPillPillars;
  window.fitPillPillarsToContainer = fitPillPillarsToContainer;
}

