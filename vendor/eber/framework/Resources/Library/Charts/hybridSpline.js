/**
 * 📊 hybridSpline.js
 * 
 * Librería independiente para barras translúcidas con curva Spline continua (Hybrid Spline).
 * Cero dependencias externas.
 * Crecimiento de barras + trazado de curva en 400ms simultáneo con curva easeInOutCubic.
 * 
 * @module HybridSpline
 */

import { easeInOutCubic } from "./arcMeter.js";

/**
 * Inicializa un gráfico de Hybrid Spline.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initHybridSpline(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);
    const baseline = 155;

    // 1. Barras de fondo
    const bars = svg.querySelectorAll(".mono-spline-bar-rect");
    bars.forEach((bar) => {
      const targetH = parseFloat(bar.getAttribute("data-target-h") || bar.getAttribute("height") || "60");
      bar.setAttribute("height", "0");
      bar.setAttribute("y", baseline.toString());

      const startTime = performance.now();
      function stepBar(now) {
        const elapsed = Math.max(0, now - startTime);
        const progress = Math.max(0, Math.min(elapsed / duration, 1));
        const ease = easeInOutCubic(progress);
        const currentH = Math.max(0, targetH * ease);
        bar.setAttribute("height", currentH.toFixed(2));
        bar.setAttribute("y", (baseline - currentH).toFixed(2));

        if (progress < 1) {
          requestAnimationFrame(stepBar);
        } else {
          const finalH = Math.max(0, targetH);
          bar.setAttribute("height", finalH.toFixed(2));
          bar.setAttribute("y", (baseline - finalH).toFixed(2));
        }
      }
      requestAnimationFrame(stepBar);
    });

    // 2. Curva Spline continua (stroke-dashoffset)
    const splinePath = svg.querySelector(".mono-spline-path");
    if (splinePath) {
      const pathLength = splinePath.getTotalLength ? splinePath.getTotalLength() : 350;
      splinePath.style.strokeDasharray = `${pathLength} ${pathLength}`;
      splinePath.style.strokeDashoffset = `${pathLength}`;

      const startTime = performance.now();
      function stepSpline(now) {
        const elapsed = Math.max(0, now - startTime);
        const progress = Math.max(0, Math.min(elapsed / duration, 1));
        const ease = easeInOutCubic(progress);
        const currentOffset = Math.max(0, pathLength * (1 - ease));
        splinePath.style.strokeDashoffset = `${currentOffset.toFixed(2)}`;

        if (progress < 1) {
          requestAnimationFrame(stepSpline);
        } else {
          splinePath.style.strokeDashoffset = "0";
        }
      }
      requestAnimationFrame(stepSpline);
    }

    // 3. Área degradada y puntos
    const area = svg.querySelector(".mono-spline-area");
    if (area) {
      area.style.opacity = "0";
      const startTime = performance.now();
      function stepArea(now) {
        const elapsed = Math.max(0, now - startTime);
        const progress = Math.max(0, Math.min(elapsed / duration, 1));
        area.style.opacity = easeInOutCubic(progress).toFixed(4);
        if (progress < 1) requestAnimationFrame(stepArea);
        else area.style.opacity = "1";
      }
      requestAnimationFrame(stepArea);
    }

    // 4. Puntos (Vértices): Aparecen al final de la animación con un fade suave en su posición exacta
    const dots = svg.querySelectorAll(".mono-spline-dot");
    const fadeDelay = options.dotFadeDelay !== undefined ? options.dotFadeDelay : duration;
    const fadeDuration = options.dotFadeDuration || Math.min(200, Math.max(120, Math.round(duration * 0.4)));

    dots.forEach((dot) => {
      if (dot._fadeRaf) {
        cancelAnimationFrame(dot._fadeRaf);
      }
      dot.style.opacity = "0";
      dot.style.transform = ""; // Sin transform para garantizar que permanezcan en su coordenada exacta (cx, cy)

      const startTime = performance.now();
      function stepDot(now) {
        const elapsed = Math.max(0, now - startTime);
        if (elapsed < fadeDelay) {
          dot.style.opacity = "0";
          dot._fadeRaf = requestAnimationFrame(stepDot);
          return;
        }

        const fadeElapsed = Math.max(0, elapsed - fadeDelay);
        const progress = Math.max(0, Math.min(fadeElapsed / fadeDuration, 1));
        const ease = easeInOutCubic(progress);
        dot.style.opacity = ease.toFixed(4);

        if (progress < 1) {
          dot._fadeRaf = requestAnimationFrame(stepDot);
        } else {
          dot.style.opacity = "1";
          dot._fadeRaf = null;
        }
      }
      dot._fadeRaf = requestAnimationFrame(stepDot);
    });
  });
}

if (typeof window !== "undefined") {
  window.initHybridSpline = initHybridSpline;
}

