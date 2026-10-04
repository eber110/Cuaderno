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
import { initSimpleBars } from "./simpleBars.js";
import { initHorizontalBars } from "./horizontalBars.js";
import { initBulletTarget } from "./bulletTarget.js";
import { initRoundedDonut, animateDonutSegment } from "./roundedDonut.js";
import { initChartTooltips } from "./tooltip.js";

export {
  easeInOutCubic,
  animateArcMeterValue,
  initArcMeter,
  initStackedTones,
  initTileTreemap,
  initHybridSpline,
  initPillPillars,
  initSimpleBars,
  initHorizontalBars,
  initBulletTarget,
  initRoundedDonut,
  animateDonutSegment,
  initChartTooltips
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

  // 6. Simple Bars (Barras verticales)
  container.querySelectorAll('[data-chart="simple-bars"]').forEach((el) => {
    initSimpleBars(el);
  });

  // 7. Horizontal Bars (Barras horizontales)
  container.querySelectorAll('[data-chart="horizontal-bars"]').forEach((el) => {
    initHorizontalBars(el);
  });

  // 8. Bullet Target (Barras de comparación con benchmark)
  container.querySelectorAll('[data-chart="bullet-target"]').forEach((el) => {
    initBulletTarget(el);
  });

  // 9. Rounded Donut (Rosca con Soft Arc Caps)
  container.querySelectorAll('[data-chart="rounded-donut"]').forEach((el) => {
    initRoundedDonut(el);
  });

  // 10. Tooltips interactivos
  initChartTooltips();
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
    initSimpleBars,
    initHorizontalBars,
    initBulletTarget,
    initRoundedDonut,
    animateDonutSegment,
    initChartTooltips,
    initCharts
  };
  window.initCharts = initCharts;
  window.animateArcMeterValue = animateArcMeterValue;
  window.initRoundedDonut = initRoundedDonut;
}

