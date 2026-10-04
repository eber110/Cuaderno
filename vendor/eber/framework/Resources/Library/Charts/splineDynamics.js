/**
 * 📊 splineDynamics.js
 * 
 * Librería independiente para curvas dinámicas Dual / Single (Spline Dynamics).
 * Cero dependencias externas.
 * Animación continua con curva easeInOutCubic en 400ms y conmutador Dual/Single.
 * 
 * @module SplineDynamics
 */

import { easeInOutCubic } from "./arcMeter.js";

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

  const primPath = svg.querySelector(".mono-spline-primary-path");
  const secPath = svg.querySelector(".mono-spline-secondary-path");
  const dots = svg.querySelectorAll(".mono-spline-dyn-dot");

  const pathLen = primPath ? parseFloat(primPath.getAttribute("data-length") || "320") : 320;
  const startOffset = isExit ? 0 : pathLen;
  const targetOffset = isExit ? pathLen : 0;

  if (primPath) {
    primPath.style.strokeDasharray = `${pathLen} ${pathLen}`;
    primPath.style.strokeDashoffset = `${startOffset}`;
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
    const progress = Math.max(0, Math.min(elapsed / duration, 1));
    const ease = easeInOutCubic(progress);

    if (primPath) {
      const currentOffset = startOffset + ((targetOffset - startOffset) * ease);
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
      if (primPath) primPath.style.strokeDashoffset = `${targetOffset}`;
      dots.forEach((dot) => {
        dot.style.transform = isExit ? "scale(0)" : "scale(1)";
        dot.style.opacity = isExit ? "0" : "1";
      });
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

    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);
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
}
