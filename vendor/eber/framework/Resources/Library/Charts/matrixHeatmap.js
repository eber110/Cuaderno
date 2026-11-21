/**
 * 📊 matrixHeatmap.js
 * 
 * Librería independiente para gráficos de mapa de calor matricial (Matrix Heatmap - Activity Grid).
 * Cero dependencias externas.
 * Animación escalonada (staggered) de celdas con escala y opacidad progresiva con easeInOutCubic.
 * 
 * @module MatrixHeatmap
 */

import { easeInOutCubic } from "./arcMeter.js";

/**
 * Anima la matriz de celdas de un gráfico Matrix Heatmap.
 * 
 * @param {SVGSVGElement} svg Elemento SVG del gráfico.
 * @param {Object} options Opciones de animación.
 * @param {number} [options.duration=600] Duración total en ms.
 * @param {boolean} [options.isExit=false] Si es animación de salida.
 * @returns {{ cancel: Function }}
 */
export function animateMatrixHeatmap(svg, { duration = 600, isExit = false } = {}) {
  if (!svg) return { cancel: () => {} };

  if (typeof svg._matrixAnimCancel === "function") {
    svg._matrixAnimCancel();
  }

  const parsedDur = Number(duration);
  const safeDuration = Number.isFinite(parsedDur) && parsedDur >= 0 ? parsedDur : 600;

  const cells = Array.from(svg.querySelectorAll(".mono-matrix-cell"));
  const labels = Array.from(svg.querySelectorAll(".mono-matrix-row-label, .mono-matrix-col-label"));
  if (cells.length === 0) return { cancel: () => {} };

  // Guardar coordenadas de centro para transform-origin relativo en SVG
  cells.forEach((cell) => {
    const x = parseFloat(cell.getAttribute("x") || "0");
    const y = parseFloat(cell.getAttribute("y") || "0");
    const w = parseFloat(cell.getAttribute("width") || "25");
    const h = parseFloat(cell.getAttribute("height") || "25");
    cell.style.transformOrigin = `${x + w / 2}px ${y + h / 2}px`;
  });

  const totalCells = cells.length;
  const cellDuration = Math.min(350, safeDuration * 0.6);
  const staggerWindow = Math.max(0, safeDuration - cellDuration);
  const staggerStep = totalCells > 1 ? staggerWindow / (totalCells - 1) : 0;

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);

    if (safeDuration > 0 && elapsed < safeDuration) {
      cells.forEach((cell, idx) => {
        const cellDelay = idx * staggerStep;
        const cellElapsed = Math.max(0, elapsed - cellDelay);
        const cellProgress = cellDuration > 0 ? Math.max(0, Math.min(cellElapsed / cellDuration, 1)) : 1;
        const ease = easeInOutCubic(cellProgress);

        const scale = isExit ? (1 - (ease * 0.4)) : (0.5 + (0.5 * ease));
        const opacity = isExit ? (1 - ease) : ease;

        cell.style.transform = `scale(${scale.toFixed(3)})`;
        cell.style.opacity = opacity.toFixed(3);
      });

      if (labels.length > 0) {
        const labelWindow = safeDuration * 0.5;
        const labelProgress = labelWindow > 0 ? Math.max(0, Math.min(elapsed / labelWindow, 1)) : 1;
        const labelEase = easeInOutCubic(labelProgress);
        const labelOpacity = isExit ? (1 - labelEase) : labelEase;
        labels.forEach((lbl) => {
          lbl.style.opacity = labelOpacity.toFixed(2);
        });
      }

      rafId = requestAnimationFrame(step);
    } else {
      cells.forEach((cell) => {
        if (isExit) {
          cell.style.transform = "scale(0.5)";
          cell.style.opacity = "0";
        } else {
          // Limpiar estilos inline al finalizar para no bloquear el :hover { transform: scale(1.10); } de CSS
          cell.style.transform = "";
          cell.style.opacity = "";
        }
      });
      labels.forEach((lbl) => {
        lbl.style.opacity = isExit ? "0" : "";
      });
      svg._matrixAnimCancel = null;
    }
  }

  const cancel = () => {
    isCancelled = true;
    if (rafId) cancelAnimationFrame(rafId);
    svg._matrixAnimCancel = null;
  };
  svg._matrixAnimCancel = cancel;

  if (safeDuration === 0) {
    step(startTime);
    return { cancel };
  }

  rafId = requestAnimationFrame(step);
  return { cancel };
}

/**
 * Inicializa un gráfico Matrix Heatmap observado por MutationObserver o carga directa.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initMatrixHeatmap(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    const rawDur = options.duration !== undefined
      ? options.duration
      : parseInt(svg.dataset.duration ?? "700", 10);
    const duration = Number.isFinite(rawDur) && rawDur >= 0 ? rawDur : 700;
    const isExit = options.isExit !== undefined ? Boolean(options.isExit) : svg.classList.contains("is-exiting");

    animateMatrixHeatmap(svg, { duration, isExit });
  });
}

if (typeof window !== "undefined") {
  window.initMatrixHeatmap = initMatrixHeatmap;
  window.animateMatrixHeatmap = animateMatrixHeatmap;
}
