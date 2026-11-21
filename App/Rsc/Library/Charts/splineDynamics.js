/**
 * 📊 splineDynamics.js
 * 
 * Librería independiente para curvas dinámicas Dual / Single (Spline Dynamics).
 * Cero dependencias externas.
 * Animación continua con curva easeInOutCubic en 400ms y conmutador Dual/Single.
 * 
 * @module SplineDynamics
 */

import { easeInOutCubic, getChartContainerWidth, observeChartResize, computeSplinePath, safeGetTotalLength } from "./arcMeter.js";

/**
 * Ajusta el viewBox, rejilla, curvas primaria/secundaria, nodos y etiquetas de Spline Dynamics
 * para mantener escala 1:1 en píxeles en cualquier dispositivo.
 *
 * @param {SVGElement|HTMLElement} target Elemento SVG o contenedor.
 * @param {number} [explicitWidth] Ancho explícito en píxeles.
 */
export function fitSplineDynamicsToContainer(target, explicitWidth = 0) {
  if (!target) return;
  const svg = target.matches && target.matches("svg") ? target : (target.querySelector ? target.querySelector("svg") : null);
  if (!svg) return;

  const containerW = explicitWidth > 0 ? Math.round(explicitWidth) : getChartContainerWidth(svg);
  if (containerW <= 0) return;

  const vbParts = (svg.getAttribute("viewBox") || "0 0 320 180").trim().split(/\s+/);
  const vbH = parseFloat(vbParts[3] || "180") || 180;
  svg.setAttribute("viewBox", `0 0 ${containerW} ${vbH}`);
  svg._lastFitWidth = containerW;

  const padL = parseFloat(svg.getAttribute("data-pad-l") || "36");
  const padR = parseFloat(svg.getAttribute("data-pad-r") || "18");
  const tension = parseFloat(svg.getAttribute("data-tension") || "0.25");
  const plotW = Math.max(40, containerW - padL - padR);

  svg.querySelectorAll(".mono-spline-dyn-grid line").forEach((line) => {
    line.setAttribute("x1", padL.toFixed(1));
    line.setAttribute("x2", (padL + plotW).toFixed(1));
  });

  const nodes = svg.querySelectorAll(".mono-spline-dyn-node");
  const xLabels = svg.querySelectorAll(".mono-spline-dyn-x-label");
  const count = nodes.length || xLabels.length;
  if (count === 0) return;

  const stepX = count > 1 ? plotW / (count - 1) : plotW;
  const primPoints = [];
  const secPoints = [];

  for (let i = 0; i < count; i++) {
    const px = padL + i * stepX;

    if (nodes[i]) {
      const dot = nodes[i].querySelector(".mono-spline-dyn-dot");
      const hit = nodes[i].querySelector(".mono-spline-dyn-hit");
      const pyPrim = parseFloat(nodes[i].getAttribute("data-py") || (dot ? dot.getAttribute("cy") : "90"));
      const pySec = parseFloat(nodes[i].getAttribute("data-sec-py") || String(pyPrim));

      primPoints.push({ x: px, y: pyPrim });
      secPoints.push({ x: px, y: pySec });

      if (dot) {
        dot.setAttribute("cx", px.toFixed(1));
        dot.style.transformOrigin = `${px.toFixed(1)}px ${pyPrim.toFixed(1)}px`;
      }
      if (hit) {
        hit.setAttribute("cx", px.toFixed(1));
      }
    } else if (xLabels[i]) {
      // Caso showPoints === false: recalcular igual la X de las etiquetas
    }

    if (xLabels[i]) {
      xLabels[i].setAttribute("x", px.toFixed(1));
    }
  }

  const primPath = svg.querySelector(".mono-spline-primary-path");
  if (primPath && primPoints.length > 0) {
    primPath.setAttribute("d", computeSplinePath(primPoints, tension));
    const fallbackLen = parseFloat(primPath.getAttribute("data-length") || "320");
    const len = safeGetTotalLength(primPath, fallbackLen > 0 ? fallbackLen : 320);
    primPath.setAttribute("data-length", len.toFixed(1));
    primPath._currentPathLen = len;
    if (!primPath._isAnimating) {
      primPath.style.strokeDasharray = "none";
      primPath.style.strokeDashoffset = "0";
    } else {
      primPath.style.strokeDasharray = `${len} ${len}`;
    }
  }

  const secPath = svg.querySelector(".mono-spline-secondary-path");
  if (secPath && secPoints.length > 0) {
    secPath.setAttribute("d", computeSplinePath(secPoints, tension));
  }
}

/**
 * Anima la curva spline primaria y sus nodos circulares.
 * 
 * @param {SVGElement} svg Elemento SVG del gráfico.
 * @param {Object} options Opciones de animación.
 * @param {number} [options.duration=400] Duración en ms.
 * @param {boolean} [options.isExit=false] Si es animación de salida.
 * @returns {{ cancel: Function }}
 */
export function animateSplineDynamics(svg, { duration = 400, isExit = false } = {}) {
  if (!svg) return { cancel: () => {} };

  if (svg._splineDynCancel) {
    svg._splineDynCancel();
  }

  const parsedDur = Number(duration);
  const safeDuration = Number.isFinite(parsedDur) && parsedDur >= 0 ? parsedDur : 400;

  const primPath = svg.querySelector(".mono-spline-primary-path");
  const dots = svg.querySelectorAll(".mono-spline-dyn-dot");

  const attrLen = primPath ? parseFloat(primPath.getAttribute("data-length") || "320") : 320;
  const initialLen = primPath ? safeGetTotalLength(primPath, attrLen > 0 ? attrLen : 320) : 320;
  if (primPath) {
    primPath._currentPathLen = initialLen;
    primPath._isAnimating = true;
    if (!isExit) {
      primPath.style.opacity = "";
    }
    primPath.style.strokeDasharray = `${initialLen} ${initialLen}`;
    primPath.style.strokeDashoffset = `${isExit ? 0 : initialLen}`;
  }

  dots.forEach((dot) => {
    dot.style.transformOrigin = `${dot.getAttribute("cx")}px ${dot.getAttribute("cy")}px`;
    dot.style.transform = isExit ? "scale(1)" : "scale(0)";
    dot.style.opacity = isExit ? "1" : "0";
  });

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = safeDuration > 0 ? Math.max(0, Math.min(elapsed / safeDuration, 1)) : 1;
    const ease = easeInOutCubic(progress);

    if (primPath) {
      const len = primPath._currentPathLen || initialLen;
      const startOffset = isExit ? 0 : len;
      const targetOffset = isExit ? len : 0;
      const currentOffset = startOffset + ((targetOffset - startOffset) * ease);
      primPath.style.strokeDasharray = `${len} ${len}`;
      primPath.style.strokeDashoffset = currentOffset.toFixed(1);
    }

    dots.forEach((dot) => {
      const scale = isExit ? (1 - ease) : ease;
      dot.style.transform = `scale(${Math.max(0, scale).toFixed(2)})`;
      dot.style.opacity = Math.max(0, Math.min(1, scale)).toFixed(2);
    });

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      if (primPath) {
        primPath._isAnimating = false;
        if (isExit) {
          const len = primPath._currentPathLen || initialLen;
          primPath.style.strokeDashoffset = `${len}`;
        } else {
          primPath.style.strokeDasharray = "none";
          primPath.style.strokeDashoffset = "0";
        }
      }
      dots.forEach((dot) => {
        dot.style.transform = isExit ? "scale(0)" : "";
        dot.style.opacity = isExit ? "0" : "1";
      });
      svg._splineDynCancel = null;
    }
  }

  const cancel = () => {
    isCancelled = true;
    if (primPath) primPath._isAnimating = false;
    if (rafId) cancelAnimationFrame(rafId);
    svg._splineDynCancel = null;
  };
  svg._splineDynCancel = cancel;

  if (safeDuration === 0) {
    step(startTime);
    return { cancel };
  }

  rafId = requestAnimationFrame(step);
  return { cancel };
}

/**
 * Conmuta el modo de visualización entre Dual (doble línea) y Single (línea única).
 * 
 * @param {SVGElement|HTMLElement} target Gráfico o contenedor.
 * @param {"dual"|"single"} mode Modo deseado.
 */
export function setSplineDynamicsMode(target, mode = "dual") {
  const container = target.closest ? (target.closest(".mono-chart-card") || target) : target;
  const secPath = container.querySelector(".mono-spline-secondary-path");
  if (!secPath) return;

  if (mode === "single") {
    secPath.style.transition = "opacity 0.25s ease";
    secPath.style.opacity = "0";
  } else {
    secPath.style.transition = "opacity 0.25s ease";
    secPath.style.opacity = "0.40";
  }
}

/**
 * Inicializa todos los gráficos de Spline Dynamics.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initSplineDynamics(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    observeChartResize(svg, fitSplineDynamicsToContainer);

    const rawDur = options.duration !== undefined
      ? options.duration
      : parseInt(svg.getAttribute("data-duration") ?? "400", 10);
    const duration = Number.isFinite(rawDur) && rawDur >= 0 ? rawDur : 400;
    const isExit = svg.classList.contains("is-exiting");

    animateSplineDynamics(svg, { duration, isExit });

    // Vincular selectores Dual / Single si existen en la tarjeta
    const card = svg.closest ? svg.closest(".flex-column") : null;
    if (card) {
      const modeButtons = card.querySelectorAll("[data-spline-mode]");
      modeButtons.forEach((btn) => {
        if (!btn.__splineBound) {
          btn.__splineBound = true;
          btn.addEventListener("click", () => {
            const mode = btn.getAttribute("data-spline-mode");
            modeButtons.forEach((b) => b.classList.remove("active"));
            btn.classList.add("active");
            setSplineDynamicsMode(svg, mode);
          });
        }
      });
    }
  });
}

if (typeof window !== "undefined") {
  window.initSplineDynamics = initSplineDynamics;
  window.animateSplineDynamics = animateSplineDynamics;
  window.setSplineDynamicsMode = setSplineDynamicsMode;
  window.fitSplineDynamicsToContainer = fitSplineDynamicsToContainer;
}
