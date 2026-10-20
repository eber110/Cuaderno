/**
 * 📊 horizontalBars.js
 * 
 * Librería independiente para barras horizontales en cápsula (Horizontal Bars).
 * Arquitectura híbrida HTML + CSS Flexbox/Grid con medidas pixel-perfect invariables
 * en cualquier contenedor (escritorio o móvil responsivo).
 * Crecimiento horizontal dinámico con curva easeInOutCubic.
 * 
 * @module HorizontalBars
 */

import { easeInOutCubic } from "./arcMeter.js";

/**
 * Función de compatibilidad para contenedores de barras horizontales.
 * Con la arquitectura híbrida HTML + CSS Flexbox/Grid, el navegador adapta el ancho
 * de forma nativa en 0ms conservando barWidth y axisLabelSize invariables en píxeles.
 * 
 * @param {HTMLElement|SVGElement} el Contenedor del gráfico.
 */
export function fitHorizontalBarsToContainer(el) {
  if (!el) return;
  // Si es un elemento HTML (.mono-hbar-container), el motor CSS Flexbox/Grid gestiona el layout de forma nativa.
  if (el.tagName && el.tagName.toLowerCase() !== "svg") return;
}

/**
 * Anima el ancho horizontal de una barra desde 0% hasta su porcentaje objetivo.
 * Soporta tanto elementos HTML (div.mono-hbar-rect en %) como SVG (rect.mono-hbar-rect).
 * 
 * @param {HTMLElement|SVGRectElement} bar Elemento de la barra horizontal.
 * @param {Object} options Opciones de animación.
 * @param {number} options.targetW Valor objetivo (porcentaje 0-100 en HTML o ancho en SVG).
 * @param {number} [options.duration=400] Duración en ms.
 * @returns {{ cancel: Function }}
 */
export function animateHorizontalBar(bar, { targetW, duration = 400 }) {
  if (!bar) return { cancel: () => {} };

  if (bar._hbarAnimCancel) {
    bar._hbarAnimCancel();
  }

  const isSvgRect = bar.tagName && bar.tagName.toLowerCase() === "rect";
  if (isSvgRect) {
    bar.setAttribute("width", "0");
  } else {
    bar.style.width = "0%";
  }

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = Math.max(0, Math.min(elapsed / duration, 1));
    const ease = easeInOutCubic(progress);

    const currentW = Math.max(0, targetW * ease);
    if (isSvgRect) {
      bar.setAttribute("width", currentW.toFixed(2));
    } else {
      bar.style.width = `${currentW.toFixed(2)}%`;
    }

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      if (isSvgRect) {
        bar.setAttribute("width", targetW.toFixed(2));
      } else {
        bar.style.width = `${targetW.toFixed(2)}%`;
      }
      bar._hbarAnimCancel = null;
    }
  }

  rafId = requestAnimationFrame(step);
  const cancelFn = () => {
    isCancelled = true;
    if (rafId) cancelAnimationFrame(rafId);
    bar._hbarAnimCancel = null;
  };
  bar._hbarAnimCancel = cancelFn;

  return { cancel: cancelFn };
}

/**
 * Inicializa todos los gráficos de barras horizontales.
 * 
 * @param {string|HTMLElement|SVGElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initHorizontalBars(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const chartEl = (el.matches && el.matches('[data-chart="horizontal-bars"], svg'))
      ? el
      : (el.querySelector ? el.querySelector('[data-chart="horizontal-bars"], svg') : null);
    if (!chartEl) return;

    const duration = options.duration || parseInt(chartEl.getAttribute("data-duration") || "400", 10);
    const bars = chartEl.querySelectorAll(".mono-hbar-rect");

    bars.forEach((bar) => {
      const targetW = parseFloat(bar.getAttribute("data-target-w") || "0");
      animateHorizontalBar(bar, { targetW, duration });
    });
  });
}

if (typeof window !== "undefined") {
  window.initHorizontalBars = initHorizontalBars;
  window.animateHorizontalBar = animateHorizontalBar;
  window.fitHorizontalBarsToContainer = fitHorizontalBarsToContainer;
}
