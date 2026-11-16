/**
 * 📊 hybridSpline.js
 * 
 * Librería independiente para barras translúcidas con curva Spline continua (Hybrid Spline).
 * Cero dependencias externas.
 * Crecimiento de barras + trazado de curva en 400ms simultáneo con curva easeInOutCubic.
 * 
 * @module HybridSpline
 */

import { easeInOutCubic, getChartContainerWidth, observeChartResize, computeSplinePath } from "./arcMeter.js";

/**
 * Ajusta el viewBox, barras translúcidas, curva Bézier y etiquetas de Hybrid Spline
 * manteniendo escala 1:1 y preservando `barWidth` exacto en píxeles.
 *
 * @param {SVGElement|HTMLElement} target Elemento SVG o contenedor.
 * @param {number} [explicitWidth] Ancho explícito en píxeles.
 */
export function fitHybridSplineToContainer(target, explicitWidth = 0) {
  if (!target) return;
  const svg = target.matches && target.matches("svg") ? target : (target.querySelector ? target.querySelector("svg") : null);
  if (!svg) return;

  const containerW = explicitWidth > 0 ? Math.round(explicitWidth) : getChartContainerWidth(svg);
  if (containerW <= 0) return;

  const vbParts = (svg.getAttribute("viewBox") || "0 0 320 180").trim().split(/\s+/);
  const vbH = parseFloat(vbParts[3] || "180") || 180;
  svg.setAttribute("viewBox", `0 0 ${containerW} ${vbH}`);
  svg._lastFitWidth = containerW;

  const plotX1 = 44;
  const plotX2 = Math.max(plotX1 + 40, containerW - 40);
  const gridX2 = Math.max(plotX1 + 40, containerW - 16);
  const availableW = Math.max(20, plotX2 - plotX1);

  svg.querySelectorAll(".mono-svg-grid-line").forEach((line) => {
    line.setAttribute("x1", "28");
    line.setAttribute("x2", gridX2.toString());
  });

  const bars = svg.querySelectorAll(".mono-spline-bar-rect");
  const dots = svg.querySelectorAll(".mono-spline-dot");
  const xLabels = svg.querySelectorAll(".mono-svg-axis-text-x");
  const count = Math.max(bars.length, dots.length, xLabels.length);
  if (count === 0) return;

  const step = count > 1 ? availableW / (count - 1) : 0;
  const rawBarW = parseFloat(svg.getAttribute("data-bar-width") || "14");
  const barWidth = rawBarW > 0 ? rawBarW : 14;
  const rawRx = svg.getAttribute("data-rx");
  const rx = rawRx !== null ? parseFloat(rawRx) : barWidth / 2;
  const baselineY = parseFloat(svg.getAttribute("data-baseline-y") || "155");
  const tension = parseFloat(svg.getAttribute("data-tension") || "0.25");

  const points = [];
  for (let idx = 0; idx < count; idx++) {
    const x = count > 1 ? plotX1 + idx * step : plotX1 + availableW / 2;
    let y = baselineY;
    if (dots[idx]) {
      y = parseFloat(dots[idx].getAttribute("cy") || String(baselineY));
      dots[idx].setAttribute("cx", x.toFixed(2));
    } else if (bars[idx]) {
      y = parseFloat(bars[idx].getAttribute("data-target-y") || String(baselineY));
    }

    points.push({ x, y });

    if (bars[idx]) {
      bars[idx].setAttribute("x", (x - barWidth / 2).toFixed(2));
      bars[idx].setAttribute("width", barWidth.toFixed(2));
      bars[idx].setAttribute("rx", rx.toFixed(2));
    }

    if (xLabels[idx]) {
      xLabels[idx].setAttribute("x", x.toFixed(2));
      xLabels[idx].querySelectorAll("tspan").forEach((ts) => {
        ts.setAttribute("x", x.toFixed(2));
      });
    }
  }

  const pathD = computeSplinePath(points, tension);
  const splinePath = svg.querySelector(".mono-spline-path");
  if (splinePath && pathD) {
    splinePath.setAttribute("d", pathD);
    if (splinePath.getTotalLength) {
      const len = splinePath.getTotalLength();
      splinePath._currentPathLen = len;
      if (!splinePath._isAnimating) {
        splinePath.style.strokeDasharray = "none";
        splinePath.style.strokeDashoffset = "0";
      } else {
        splinePath.style.strokeDasharray = `${len} ${len}`;
      }
    }
  }

  const area = svg.querySelector(".mono-spline-area");
  if (area && pathD && points.length > 0) {
    const areaD = `${pathD} L ${points[points.length - 1].x.toFixed(1)},${baselineY} L ${points[0].x.toFixed(1)},${baselineY} Z`;
    area.setAttribute("d", areaD);
  }
}

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

    observeChartResize(svg, fitHybridSplineToContainer);

    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);
    const baseline = parseFloat(svg.getAttribute("data-baseline-y") || "155");

    // 1. Barras de fondo
    const bars = svg.querySelectorAll(".mono-spline-bar-rect");
    bars.forEach((bar) => {
      if (bar._splineBarRaf) {
        cancelAnimationFrame(bar._splineBarRaf);
      }
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
          bar._splineBarRaf = requestAnimationFrame(stepBar);
        } else {
          const finalH = Math.max(0, targetH);
          bar.setAttribute("height", finalH.toFixed(2));
          bar.setAttribute("y", (baseline - finalH).toFixed(2));
          bar._splineBarRaf = null;
        }
      }
      bar._splineBarRaf = requestAnimationFrame(stepBar);
    });

    // 2. Curva Spline continua (stroke-dashoffset)
    const splinePath = svg.querySelector(".mono-spline-path");
    if (splinePath) {
      if (splinePath._splinePathRaf) {
        cancelAnimationFrame(splinePath._splinePathRaf);
      }
      const initialLen = splinePath.getTotalLength ? splinePath.getTotalLength() : 350;
      splinePath._currentPathLen = initialLen;
      splinePath._isAnimating = true;
      splinePath.style.strokeDasharray = `${initialLen} ${initialLen}`;
      splinePath.style.strokeDashoffset = `${initialLen}`;

      const startTime = performance.now();
      function stepSpline(now) {
        const elapsed = Math.max(0, now - startTime);
        const progress = Math.max(0, Math.min(elapsed / duration, 1));
        const ease = easeInOutCubic(progress);
        const len = splinePath._currentPathLen || initialLen;
        const currentOffset = Math.max(0, len * (1 - ease));
        splinePath.style.strokeDasharray = `${len} ${len}`;
        splinePath.style.strokeDashoffset = `${currentOffset.toFixed(2)}`;

        if (progress < 1) {
          splinePath._splinePathRaf = requestAnimationFrame(stepSpline);
        } else {
          splinePath._isAnimating = false;
          splinePath.style.strokeDasharray = "none";
          splinePath.style.strokeDashoffset = "0";
          splinePath._splinePathRaf = null;
        }
      }
      splinePath._splinePathRaf = requestAnimationFrame(stepSpline);
    }

    // 3. Área degradada y puntos
    const area = svg.querySelector(".mono-spline-area");
    if (area) {
      if (area._areaRaf) {
        cancelAnimationFrame(area._areaRaf);
      }
      area.style.opacity = "0";
      const startTime = performance.now();
      function stepArea(now) {
        const elapsed = Math.max(0, now - startTime);
        const progress = Math.max(0, Math.min(elapsed / duration, 1));
        area.style.opacity = easeInOutCubic(progress).toFixed(4);
        if (progress < 1) {
          area._areaRaf = requestAnimationFrame(stepArea);
        } else {
          area.style.opacity = "1";
          area._areaRaf = null;
        }
      }
      area._areaRaf = requestAnimationFrame(stepArea);
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
  window.fitHybridSplineToContainer = fitHybridSplineToContainer;
}

