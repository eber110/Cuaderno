/**
 * 📊 pillPillars.js
 * 
 * Librería independiente para columnas emparejadas en cápsula (Pill Pillars).
 * Cero dependencias externas.
 * Crecimiento vertical simultáneo en 400ms desde la línea base con curva easeInOutCubic.
 * 
 * @module PillPillars
 */

import { easeInOutCubic } from "./arcMeter.js";

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

    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);
    const baseline = 155;
    const pillars = svg.querySelectorAll(".mono-pillar-primary, .mono-pillar-secondary");

    pillars.forEach((pillar) => {
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
          requestAnimationFrame(step);
        } else {
          const finalH = Math.max(0, targetH);
          pillar.setAttribute("height", finalH.toFixed(2));
          pillar.setAttribute("y", (baseline - finalH).toFixed(2));
        }
      }
      requestAnimationFrame(step);
    });
  });
}

if (typeof window !== "undefined") {
  window.initPillPillars = initPillPillars;
}

