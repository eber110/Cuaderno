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

  const cells = Array.from(svg.querySelectorAll(".mono-matrix-cell"));
  const labels = Array.from(svg.querySelectorAll(".mono-matrix-row-label"));
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
  const cellDuration = Math.min(350, duration * 0.6);
  const staggerWindow = Math.max(0, duration - cellDuration);
  const staggerStep = totalCells > 1 ? staggerWindow / (totalCells - 1) : 0;

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);

    cells.forEach((cell, idx) => {
      const cellDelay = idx * staggerStep;
      const cellElapsed = Math.max(0, elapsed - cellDelay);
      const cellProgress = Math.max(0, Math.min(cellElapsed / cellDuration, 1));
      const ease = easeInOutCubic(cellProgress);

      const scale = isExit ? (1 - (ease * 0.4)) : (0.5 + (0.5 * ease));
      const opacity = isExit ? (1 - ease) : ease;

      cell.style.transform = `scale(${scale.toFixed(3)})`;
      cell.style.opacity = opacity.toFixed(3);
    });

    if (labels.length > 0) {
      const labelProgress = Math.max(0, Math.min(elapsed / (duration * 0.5), 1));
      const labelEase = easeInOutCubic(labelProgress);
      const labelOpacity = isExit ? (1 - labelEase) : labelEase;
      labels.forEach(lbl => {
        lbl.style.opacity = labelOpacity.toFixed(2);
      });
    }

    if (elapsed < duration) {
      rafId = requestAnimationFrame(step);
    } else {
      cells.forEach((cell) => {
        cell.style.transform = isExit ? "scale(0.5)" : "scale(1)";
        cell.style.opacity = isExit ? "0" : "1";
      });
      labels.forEach(lbl => {
        lbl.style.opacity = isExit ? "0" : "1";
      });
    }
  }

  rafId = requestAnimationFrame(step);

  return {
    cancel() {
      isCancelled = true;
      if (rafId) cancelAnimationFrame(rafId);
    }
  };
}

/**
 * Inicializa un gráfico Matrix Heatmap observado por MutationObserver o carga directa.
 * 
 * @param {SVGSVGElement} svg Elemento SVG del gráfico.
 */
export function initMatrixHeatmap(svg) {
  if (!svg || svg.dataset.matrixHeatmapInitialized === "true") return;
  svg.dataset.matrixHeatmapInitialized = "true";

  const duration = parseInt(svg.dataset.duration || "700", 10);

  // Ejecutar animación de entrada
  animateMatrixHeatmap(svg, { duration, isExit: false });
}
