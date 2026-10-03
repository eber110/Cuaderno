/**
 * 📊 charts.js (Orquestador de Gráficos SVG del Eber Framework)
 * 
 * Punto de entrada único que exporta todos los módulos independientes de gráficos
 * e inicializa automáticamente los elementos [data-chart].
 * 
 * @module Charts
 */

import { easeInOutCubic, animateArcMeterValue, initArcMeter } from "./arcMeter.js";
import { initStackedTones } from "./stackedTones.js";
import { initTileTreemap } from "./tileTreemap.js";
import { initHybridSpline } from "./hybridSpline.js";
import { initPillPillars } from "./pillPillars.js";

export {
  easeInOutCubic,
  animateArcMeterValue,
  initArcMeter,
  initStackedTones,
  initTileTreemap,
  initHybridSpline,
  initPillPillars
};

/**
 * Inicializa todos los gráficos presentes en el documento o contenedor.
 * 
 * @param {Document|HTMLElement} [container=document] Contenedor raíz a escanear.
 */
export function initCharts(container = document) {
  if (!container || !container.querySelectorAll) return;

  // 1. Arc Meter
  container.querySelectorAll('[data-chart="arc-meter"]').forEach((el) => {
    initArcMeter(el);
  });

  // 2. Stacked Tones
  container.querySelectorAll('[data-chart="stacked-tones"]').forEach((el) => {
    initStackedTones(el);
  });

  // 3. Tile Treemap
  container.querySelectorAll('[data-chart="tile-treemap"]').forEach((el) => {
    initTileTreemap(el);
  });

  // 4. Hybrid Spline
  container.querySelectorAll('[data-chart="hybrid-spline"]').forEach((el) => {
    initHybridSpline(el);
  });

  // 5. Pill Pillars
  container.querySelectorAll('[data-chart="pill-pillars"]').forEach((el) => {
    initPillPillars(el);
  });
}

// Inicialización automática al cargar el DOM
if (typeof document !== "undefined") {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => initCharts());
  } else {
    // Si ya cargó el DOM, inicializar de inmediato
    setTimeout(() => initCharts(), 0);
  }
}

// Vinculación global al objeto window
if (typeof window !== "undefined") {
  window.Charts = {
    easeInOutCubic,
    animateArcMeterValue,
    initArcMeter,
    initStackedTones,
    initTileTreemap,
    initHybridSpline,
    initPillPillars,
    initCharts
  };
  window.initCharts = initCharts;
  window.animateArcMeterValue = animateArcMeterValue;
}

