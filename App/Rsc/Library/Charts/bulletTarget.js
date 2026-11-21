/**
 * 📊 bulletTarget.js
 * 
 * Librería independiente para barras de comparación con marcador de benchmark (Bullet Target).
 * Cero dependencias externas.
 * Crecimiento horizontal dinámico de 400ms con curva easeInOutCubic.
 * 
 * @module BulletTarget
 */

import { easeInOutCubic, getChartContainerWidth, observeChartResize } from "./arcMeter.js";

/**
 * Ajusta el viewBox y recalcula pistas, barras, marcadores y etiquetas en Bullet Target
 * manteniendo escala 1:1 y preservando el grosor exacto de barra (`barWidth`/`barHeight`) y `yLabelPosition`.
 *
 * @param {SVGElement|HTMLElement} target Elemento SVG o contenedor.
 * @param {number} [explicitWidth] Ancho explícito en píxeles.
 */
export function fitBulletTargetToContainer(target, explicitWidth = 0) {
  if (!target) return;
  const svg = target.matches && target.matches("svg") ? target : (target.querySelector ? target.querySelector("svg") : null);
  if (!svg) return;

  const containerW = explicitWidth > 0 ? Math.round(explicitWidth) : getChartContainerWidth(svg);
  if (containerW <= 0) return;

  const vbParts = (svg.getAttribute("viewBox") || "0 0 320 180").trim().split(/\s+/);
  const vbH = parseFloat(vbParts[3] || "180") || 180;
  svg.setAttribute("viewBox", `0 0 ${containerW} ${vbH}`);
  svg._lastFitWidth = containerW;

  const padL = 14;
  const padR = 14;
  const netW = Math.max(60, containerW - padL - padR);

  const hasSplit = svg.getAttribute("data-has-split") === "1";
  const labelPct = parseFloat(svg.getAttribute("data-label-pct") || "50");
  const chartPct = parseFloat(svg.getAttribute("data-chart-pct") || "50");
  const rowAlign = svg.getAttribute("data-row-align") || "default";
  const textAlign = svg.getAttribute("data-text-align") || "start";
  const labelAnchor = textAlign === "center" ? "middle" : (textAlign === "end" ? "end" : "start");

  let trackX, trackW, labelX, valX, valAnchor;
  if (hasSplit) {
    const labelBoxW = (labelPct / 100) * netW;
    const chartBoxW = (chartPct / 100) * netW;
    const freeSpace = Math.max(0, netW - labelBoxW - chartBoxW);
    let labelBoxX = padL;
    if (rowAlign === "between") {
      labelBoxX = padL;
      trackX = padL + netW - chartBoxW;
    } else if (rowAlign === "around") {
      const gapAround = freeSpace / 2;
      labelBoxX = padL + gapAround / 2;
      trackX = labelBoxX + labelBoxW + gapAround;
    } else if (rowAlign === "center") {
      labelBoxX = padL + freeSpace / 2;
      trackX = labelBoxX + labelBoxW;
    } else if (rowAlign === "end") {
      labelBoxX = padL + freeSpace;
      trackX = labelBoxX + labelBoxW;
    } else {
      labelBoxX = padL;
      trackX = labelBoxX + labelBoxW;
    }
    trackW = Math.max(20, chartBoxW);
    const labelRightEdge = (labelPct + chartPct >= 98 && rowAlign !== "between")
      ? (labelBoxX + Math.max(0, labelBoxW - 8))
      : (labelBoxX + labelBoxW);
    labelX = labelAnchor === "middle"
      ? (labelBoxX + labelBoxW / 2)
      : (labelAnchor === "end" ? labelRightEdge : labelBoxX);
    valAnchor = "end";
    valX = trackX + trackW;
  } else {
    trackX = padL;
    trackW = netW;
    labelX = labelAnchor === "middle"
      ? (trackX + trackW / 2)
      : (labelAnchor === "end" ? (trackX + trackW) : trackX);
    valAnchor = labelAnchor === "end" ? "start" : "end";
    valX = labelAnchor === "end" ? trackX : (trackX + trackW);
  }

  svg.querySelectorAll(".mono-bullet-row").forEach((row) => {
    const labelEl = row.querySelector(".mono-bullet-label");
    if (labelEl) {
      labelEl.setAttribute("x", labelX.toFixed(1));
      labelEl.setAttribute("text-anchor", labelAnchor);
      labelEl.style.textAnchor = labelAnchor;
    }

    const valEl = row.querySelector(".mono-bullet-val");
    if (valEl) {
      valEl.setAttribute("x", valX.toFixed(1));
      valEl.setAttribute("text-anchor", valAnchor);
      valEl.style.textAnchor = valAnchor;
    }

    const track = row.querySelector(".mono-bullet-track");
    if (track) {
      track.setAttribute("x", trackX.toFixed(1));
      track.setAttribute("width", trackW.toFixed(1));
    }

    const ratioVal = parseFloat(row.getAttribute("data-ratio-val") || "0");
    const ratioTgt = parseFloat(row.getAttribute("data-ratio-tgt") || "0");
    const valW = Math.max(0, ratioVal * trackW);
    const tgtX = trackX + ratioTgt * trackW;

    const bar = row.querySelector(".mono-bullet-bar");
    if (bar) {
      bar.setAttribute("x", trackX.toFixed(1));
      bar.setAttribute("data-target-w", valW.toFixed(1));
      bar._bulletTargetW = valW;
      if (!bar._isAnimating) {
        bar.setAttribute("width", valW.toFixed(1));
      }
    }

    const marker = row.querySelector(".mono-bullet-marker");
    if (marker) {
      marker.setAttribute("x", (tgtX - 1.5).toFixed(1));
    }
  });
}

/**
 * Anima el ancho horizontal de una barra bullet desde 0 hasta su ancho final.
 * 
 * @param {SVGRectElement} rect Elemento rect de la barra.
 * @param {Object} options Opciones de animación.
 * @param {number} options.targetW Ancho final objetivo.
 * @param {number} [options.duration=400] Duración en ms.
 * @returns {{ cancel: Function }}
 */
export function animateBulletBar(rect, { targetW, duration = 400 }) {
  if (!rect) return { cancel: () => {} };

  if (rect._bulletAnimCancel) {
    rect._bulletAnimCancel();
  }

  const parsedDur = Number(duration);
  const safeDuration = Number.isFinite(parsedDur) && parsedDur >= 0 ? parsedDur : 400;
  const safeTarget = Number.isFinite(Number(targetW)) ? Math.max(0, Number(targetW)) : 0;

  rect.style.opacity = "";
  rect._bulletTargetW = safeTarget;
  rect._isAnimating = true;
  rect.setAttribute("width", "0");

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = safeDuration > 0 ? Math.max(0, Math.min(elapsed / safeDuration, 1)) : 1;
    const ease = easeInOutCubic(progress);
    const liveTargetW = typeof rect._bulletTargetW === "number" ? rect._bulletTargetW : safeTarget;

    const currentW = Math.max(0, liveTargetW * ease);
    rect.setAttribute("width", currentW.toFixed(2));

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      rect._isAnimating = false;
      rect.setAttribute("width", liveTargetW.toFixed(2));
      rect._bulletAnimCancel = null;
    }
  }

  const cancel = () => {
    isCancelled = true;
    rect._isAnimating = false;
    if (rafId) cancelAnimationFrame(rafId);
    rect._bulletAnimCancel = null;
  };
  rect._bulletAnimCancel = cancel;

  if (safeDuration === 0) {
    step(startTime);
    return { cancel };
  }

  rafId = requestAnimationFrame(step);
  return { cancel };
}

/**
 * Inicializa todos los gráficos de Bullet Target.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initBulletTarget(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    observeChartResize(svg, fitBulletTargetToContainer);

    const rawDur = options.duration !== undefined
      ? options.duration
      : parseInt(svg.getAttribute("data-duration") ?? "400", 10);
    const duration = Number.isFinite(rawDur) && rawDur >= 0 ? rawDur : 400;
    const bars = svg.querySelectorAll(".mono-bullet-bar");

    bars.forEach((bar) => {
      const targetW = parseFloat(bar.getAttribute("data-target-w") || bar.getAttribute("width") || "50");
      if (!bar.hasAttribute("data-target-w")) {
        bar.setAttribute("data-target-w", targetW.toFixed(1));
      }
      animateBulletBar(bar, { targetW, duration });
    });
  });
}

if (typeof window !== "undefined") {
  window.initBulletTarget = initBulletTarget;
  window.animateBulletBar = animateBulletBar;
  window.fitBulletTargetToContainer = fitBulletTargetToContainer;
}
