/**
 * 📊 horizontalBars.js
 * 
 * Librería independiente para barras horizontales en cápsula (Horizontal Bars).
 * Cero dependencias externas.
 * Crecimiento horizontal dinámico de 400ms con curva easeInOutCubic.
 * Soporte responsivo de escala 1.0 permanente (grosor de barra y tamaño de fuente invariables respecto al ancho del contenedor).
 * 
 * @module HorizontalBars
 */

import { easeInOutCubic } from "./arcMeter.js";

/**
 * Ajusta dinámicamente las coordenadas del SVG para coincidir 1:1 con el ancho real de su contenedor.
 * Garantiza que la escala interna del SVG sea exactamente 1.0, preservando invariable el grosor
 * de las barras (barWidth en píxeles) y el tamaño de las etiquetas (axisLabelSize en píxeles).
 * 
 * @param {SVGSVGElement} svg Elemento SVG del gráfico.
 */
export function fitHorizontalBarsToContainer(svg) {
  if (!svg) return;

  const parent = svg.parentElement;
  if (!parent) return;

  const containerW = Math.round(parent.getBoundingClientRect().width);
  if (containerW < 50) return; // Contenedor colapsado o invisible (ej. tab oculta)

  const currentVb = svg.viewBox && svg.viewBox.baseVal;
  const currentW = currentVb && currentVb.width > 0 ? currentVb.width : 320;
  const vbH = currentVb && currentVb.height > 0 ? currentVb.height : 180;

  // Si ya coincide con el ancho del contenedor y ya se ajustó una vez, evitar trabajo innecesario
  if (Math.abs(containerW - currentW) <= 1 && svg._hasFitOnce) return;
  svg._hasFitOnce = true;

  const padLeft = parseFloat(svg.getAttribute("data-pad-left") || "14");
  const padRight = parseFloat(svg.getAttribute("data-pad-right") || "16");
  const gap = parseFloat(svg.getAttribute("data-gap") || "12");
  const labelColW = parseFloat(svg.getAttribute("data-label-col-w") || "0");
  const maxVal = parseFloat(svg.getAttribute("data-max-val") || "10");
  const textAnchor = svg.getAttribute("data-text-anchor") || "start";
  const showAxisY = svg.getAttribute("data-show-axis-y") !== "0";

  // Actualizar viewBox al ancho real del contenedor para forzar scale = 1.0 permanente
  svg.setAttribute("viewBox", `0 0 ${containerW} ${vbH}`);

  const netW = Math.max(50, containerW - padLeft - padRight);

  let plotX1, availableW;
  if (!showAxisY || labelColW <= 0) {
    plotX1 = padLeft;
    availableW = netW;
  } else {
    plotX1 = padLeft + labelColW + gap;
    availableW = Math.max(40, netW - (labelColW + gap));
  }

  let textX;
  if (textAnchor === "start") {
    textX = padLeft;
  } else if (textAnchor === "middle") {
    textX = padLeft + (labelColW / 2.0);
  } else {
    textX = padLeft + labelColW;
  }

  // 1. Actualizar pistas y barras dinámicas
  const items = svg.querySelectorAll(".mono-hbar-item");
  items.forEach((item) => {
    const track = item.querySelector(".mono-hbar-track");
    const bar = item.querySelector(".mono-hbar-rect");
    const labelText = item.querySelector(".mono-svg-axis-text-y");

    if (track) {
      track.setAttribute("x", plotX1.toFixed(2));
      track.setAttribute("width", availableW.toFixed(2));
    }

    if (bar) {
      const itVal = parseFloat(bar.getAttribute("data-num-val") || item.getAttribute("data-num-val") || "0");
      let ratio = parseFloat(bar.getAttribute("data-ratio") || "0");
      if (isNaN(ratio) || ratio <= 0) {
        ratio = maxVal > 0 ? Math.min(itVal, maxVal) / maxVal : 0;
      }
      const targetW = Math.max(0, ratio * availableW);

      bar.setAttribute("x", plotX1.toFixed(2));
      bar.setAttribute("data-target-w", targetW.toFixed(2));
      bar.setAttribute("width", targetW.toFixed(2));
    }

    if (labelText) {
      labelText.setAttribute("x", textX.toFixed(2));
      labelText.querySelectorAll("tspan").forEach((ts) => {
        ts.setAttribute("x", textX.toFixed(2));
      });
    }
  });

  // 2. Actualizar líneas de rejilla vertical y etiquetas del eje X
  const gridLines = svg.querySelectorAll(".mono-svg-grid-line");
  const axisXTexts = svg.querySelectorAll(".mono-svg-axis-text-x");

  gridLines.forEach((line, idx) => {
    const rawRatio = line.getAttribute("data-ratio");
    const ratio = rawRatio !== null ? parseFloat(rawRatio) : (gridLines.length > 1 ? idx / (gridLines.length - 1) : 0);
    const tickX = plotX1 + (ratio * availableW);
    line.setAttribute("x1", tickX.toFixed(2));
    line.setAttribute("x2", tickX.toFixed(2));
  });

  axisXTexts.forEach((text, idx) => {
    const rawRatio = text.getAttribute("data-ratio");
    const ratio = rawRatio !== null ? parseFloat(rawRatio) : (axisXTexts.length > 1 ? idx / (axisXTexts.length - 1) : 0);
    const tickX = plotX1 + (ratio * availableW);
    text.setAttribute("x", tickX.toFixed(2));
  });
}

/**
 * Anima el ancho horizontal de una barra desde 0 hasta su ancho final.
 * 
 * @param {SVGRectElement} rect Elemento rect de la barra horizontal.
 * @param {Object} options Opciones de animación.
 * @param {number} options.targetW Ancho final objetivo.
 * @param {number} [options.duration=400] Duración en ms.
 * @returns {{ cancel: Function }}
 */
export function animateHorizontalBar(rect, { targetW, duration = 400 }) {
  if (!rect) return { cancel: () => {} };

  rect.setAttribute("width", "0");

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = Math.max(0, Math.min(elapsed / duration, 1));
    const ease = easeInOutCubic(progress);

    const currentW = Math.max(0, targetW * ease);
    rect.setAttribute("width", currentW.toFixed(2));

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      rect.setAttribute("width", targetW.toFixed(2));
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
 * Inicializa todos los gráficos de barras horizontales con ajuste responsivo a escala 1.0.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initHorizontalBars(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    // 1. Ajustar el SVG al ancho exacto de su contenedor (escala 1.0 para conservar medidas reales)
    fitHorizontalBarsToContainer(svg);

    // 2. Conectar ResizeObserver para adaptarse fluidamente a redimensionamientos
    if (typeof ResizeObserver !== "undefined" && svg.parentElement && !svg._hasHBarResizeObserver) {
      svg._hasHBarResizeObserver = true;
      const ro = new ResizeObserver(() => {
        fitHorizontalBarsToContainer(svg);
      });
      ro.observe(svg.parentElement);
      svg._hBarRo = ro;
    }

    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);
    const bars = svg.querySelectorAll(".mono-hbar-rect");

    bars.forEach((bar) => {
      const targetW = parseFloat(bar.getAttribute("data-target-w") || bar.getAttribute("width") || "50");
      animateHorizontalBar(bar, { targetW, duration });
    });
  });
}

if (typeof window !== "undefined") {
  window.initHorizontalBars = initHorizontalBars;
  window.animateHorizontalBar = animateHorizontalBar;
  window.fitHorizontalBarsToContainer = fitHorizontalBarsToContainer;
}
