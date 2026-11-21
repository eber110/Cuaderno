/**
 * 📊 charts.js (Orquestador de Gráficos SVG del Eber Framework)
 * 
 * Punto de entrada único que exporta todos los módulos independientes de gráficos
 * e inicializa automáticamente los elementos [data-chart].
 * 
 * @module Charts
 */

import { easeInOutCubic, animateArcMeterValue, initArcMeter } from "./arcMeter.js";
import { initStackedTones, fitStackedTonesToContainer } from "./stackedTones.js";
import { initTileTreemap } from "./tileTreemap.js";
import { initHybridSpline, fitHybridSplineToContainer } from "./hybridSpline.js";
import { initPillPillars, fitPillPillarsToContainer } from "./pillPillars.js";
import { initSimpleBars, fitSimpleBarsToContainer } from "./simpleBars.js";
import { initHorizontalBars, fitHorizontalBarsToContainer } from "./horizontalBars.js";
import { initBulletTarget, fitBulletTargetToContainer } from "./bulletTarget.js";
import { initRoundedDonut, animateDonutSegment } from "./roundedDonut.js";
import { initPyramidStack, animatePyramidTier, fitPyramidStackToContainer } from "./pyramidStack.js";
import { initSplineDynamics, animateSplineDynamics, setSplineDynamicsMode, fitSplineDynamicsToContainer } from "./splineDynamics.js";
import { initMatrixHeatmap, animateMatrixHeatmap } from "./matrixHeatmap.js";
import { initChartTooltips } from "./tooltip.js";

export {
  easeInOutCubic,
  animateArcMeterValue,
  initArcMeter,
  initStackedTones,
  fitStackedTonesToContainer,
  initTileTreemap,
  initHybridSpline,
  fitHybridSplineToContainer,
  initPillPillars,
  fitPillPillarsToContainer,
  initSimpleBars,
  fitSimpleBarsToContainer,
  initHorizontalBars,
  fitHorizontalBarsToContainer,
  initBulletTarget,
  fitBulletTargetToContainer,
  initRoundedDonut,
  animateDonutSegment,
  initPyramidStack,
  animatePyramidTier,
  fitPyramidStackToContainer,
  initSplineDynamics,
  animateSplineDynamics,
  setSplineDynamicsMode,
  fitSplineDynamicsToContainer,
  initMatrixHeatmap,
  animateMatrixHeatmap,
  initChartTooltips
};

const CHART_HANDLERS = {
  "arc-meter": initArcMeter,
  "stacked-tones": initStackedTones,
  "tile-treemap": initTileTreemap,
  "hybrid-spline": initHybridSpline,
  "pill-pillars": initPillPillars,
  "simple-bars": initSimpleBars,
  "horizontal-bars": initHorizontalBars,
  "bullet-target": initBulletTarget,
  "rounded-donut": initRoundedDonut,
  "pyramid-stack": initPyramidStack,
  "spline-dynamics": initSplineDynamics,
  "matrix-heatmap": initMatrixHeatmap
};

const initializedCharts = typeof WeakSet !== "undefined" ? new WeakSet() : null;
const pendingHiddenCharts = new Set();
let observersAttached = false;
let visibilityObserver = null;
let visibilityFlushRaf = null;

/**
 * Determina si un elemento de gráfico es visible (no está dentro de un contenedor oculto con display: none).
 * En entornos sin layout real (SSR / JSDOM sin rects) retorna true para no bloquear inicializaciones.
 *
 * @param {Element} el Elemento del gráfico.
 * @returns {boolean}
 */
function isChartVisible(el) {
  if (!el || !el.isConnected) return false;
  if (
    typeof document !== "undefined" &&
    document.body &&
    typeof document.body.getClientRects === "function" &&
    document.body.getClientRects().length > 0 &&
    typeof el.getClientRects === "function"
  ) {
    return el.getClientRects().length > 0;
  }
  return true;
}

/**
 * Procesa los gráficos pendientes que pasaron a ser visibles y desconecta el observador cuando no quedan pendientes.
 */
function flushPendingHiddenCharts() {
  visibilityFlushRaf = null;
  if (pendingHiddenCharts.size === 0) {
    if (visibilityObserver) {
      visibilityObserver.disconnect();
      visibilityObserver = null;
    }
    return;
  }

  Array.from(pendingHiddenCharts).forEach((el) => {
    if (!el || !el.isConnected) {
      pendingHiddenCharts.delete(el);
      return;
    }
    if (isChartVisible(el)) {
      pendingHiddenCharts.delete(el);
      initChartElement(el, false);
    }
  });

  if (pendingHiddenCharts.size === 0 && visibilityObserver) {
    visibilityObserver.disconnect();
    visibilityObserver = null;
  }
}

/**
 * Activa un MutationObserver temporal sobre atributos de visibilidad (class, style, hidden)
 * para inicializar los gráficos pendientes exactamente cuando su contenedor se vuelva visible.
 */
function ensureVisibilityObserver() {
  if (typeof document === "undefined" || typeof MutationObserver === "undefined" || !document.body) return;
  if (visibilityObserver || pendingHiddenCharts.size === 0) return;

  visibilityObserver = new MutationObserver(() => {
    if (pendingHiddenCharts.size === 0) {
      visibilityObserver.disconnect();
      visibilityObserver = null;
      return;
    }
    if (visibilityFlushRaf !== null) return;
    if (typeof requestAnimationFrame === "function") {
      visibilityFlushRaf = requestAnimationFrame(flushPendingHiddenCharts);
    } else {
      flushPendingHiddenCharts();
    }
  });

  visibilityObserver.observe(document.body, {
    attributes: true,
    subtree: true,
    attributeFilter: ["class", "style", "hidden"]
  });
}

/**
 * Inicializa un nodo de gráfico individual si corresponde y no ha sido procesado.
 *
 * @param {Element} el Elemento con atributo `data-chart`.
 * @param {boolean} [force=false] Si fuerza la reinicialización.
 */
function initChartElement(el, force = false) {
  if (!el || !el.getAttribute) return;
  const type = el.getAttribute("data-chart");
  const handler = type ? CHART_HANDLERS[type] : null;
  if (!handler) return;
  if (!force && initializedCharts && initializedCharts.has(el)) return;

  if (!force && !isChartVisible(el)) {
    pendingHiddenCharts.add(el);
    ensureVisibilityObserver();
    return;
  }

  pendingHiddenCharts.delete(el);
  if (initializedCharts) {
    initializedCharts.add(el);
  }
  if (el.dataset) {
    el.dataset.chartInitialized = "true";
  }
  handler(el);
}

/**
 * Conecta MutationObserver y escucha `functionalityReconnect` (querie.js) para inicializar
 * gráficos agregados dinámicamente o restaurados al cambiar de dispositivo/media query.
 */
function ensureDynamicChartObservers() {
  if (typeof document === "undefined" || observersAttached) return;
  observersAttached = true;

  document.addEventListener("functionalityReconnect", (event) => {
    const newEl = event && event.detail ? event.detail.newElement : null;
    if (newEl) {
      initCharts(newEl, { force: true });
    }
  });

  if (typeof MutationObserver !== "undefined" && document.body) {
    const observer = new MutationObserver((mutations) => {
      for (const m of mutations) {
        if (m.type !== "childList" || !m.addedNodes || m.addedNodes.length === 0) continue;
        for (const node of m.addedNodes) {
          if (!node || node.nodeType !== 1) continue;
          if ((node.matches && node.matches("[data-chart]")) || (node.querySelector && node.querySelector("[data-chart]"))) {
            initCharts(node);
          }
        }
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }
}

/**
 * Inicializa todos los gráficos presentes en el documento o contenedor.
 * 
 * @param {Document|HTMLElement|SVGElement} [container=document] Contenedor raíz a escanear.
 * @param {Object} [options={}] Opciones de inicialización (`force`).
 */
export function initCharts(container = document, options = {}) {
  if (!container) return;
  const force = Boolean(options && options.force);

  if (container.matches && container.matches("[data-chart]")) {
    initChartElement(container, force);
  }

  if (container.querySelectorAll) {
    container.querySelectorAll("[data-chart]").forEach((el) => {
      initChartElement(el, force);
    });
  }

  initChartTooltips();
  ensureDynamicChartObservers();
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
    fitStackedTonesToContainer,
    initTileTreemap,
    initHybridSpline,
    fitHybridSplineToContainer,
    initPillPillars,
    fitPillPillarsToContainer,
    initSimpleBars,
    fitSimpleBarsToContainer,
    initHorizontalBars,
    fitHorizontalBarsToContainer,
    initBulletTarget,
    fitBulletTargetToContainer,
    initRoundedDonut,
    animateDonutSegment,
    initPyramidStack,
    animatePyramidTier,
    fitPyramidStackToContainer,
    initSplineDynamics,
    animateSplineDynamics,
    setSplineDynamicsMode,
    fitSplineDynamicsToContainer,
    initMatrixHeatmap,
    animateMatrixHeatmap,
    initChartTooltips,
    initCharts
  };
  window.initCharts = initCharts;
  window.initArcMeter = initArcMeter;
  window.animateArcMeterValue = animateArcMeterValue;
  window.initStackedTones = initStackedTones;
  window.initTileTreemap = initTileTreemap;
  window.initHybridSpline = initHybridSpline;
  window.initPillPillars = initPillPillars;
  window.initSimpleBars = initSimpleBars;
  window.initHorizontalBars = initHorizontalBars;
  window.initBulletTarget = initBulletTarget;
  window.initRoundedDonut = initRoundedDonut;
  window.initPyramidStack = initPyramidStack;
  window.initSplineDynamics = initSplineDynamics;
  window.setSplineDynamicsMode = setSplineDynamicsMode;
  window.initMatrixHeatmap = initMatrixHeatmap;
  window.animateMatrixHeatmap = animateMatrixHeatmap;
}

