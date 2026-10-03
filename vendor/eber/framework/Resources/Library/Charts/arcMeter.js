/**
 * 📊 arcMeter.js
 * 
 * Librería independiente para gráficos de velocímetro / arco semicircular de 180° (Arc Meter).
 * Cero dependencias externas.
 * Animación continua de 400ms con curva easeInOutCubic desde 0% hasta el valor objetivo.
 * 
 * @module ArcMeter
 */

/**
 * Curva de aceleración Ease-In-Out Cúbica.
 * 
 * @param {number} t Progreso normalizado de 0 a 1.
 * @returns {number} Progreso amortiguado.
 */
export function easeInOutCubic(t) {
  const clamped = Math.max(0, Math.min(1, t));
  return clamped < 0.5 ? 4 * clamped * clamped * clamped : 1 - Math.pow(-2 * clamped + 2, 3) / 2;
}

/**
 * Anima el recorrido perimétrico del arco y su contador numérico central.
 * 
 * @param {SVGElement|HTMLElement} svgElement Elemento SVG del arco o contenedor.
 * @param {number} targetPct Porcentaje objetivo (0 a 100).
 * @param {Object} [options={}] Opciones de animación.
 * @param {number} [options.duration=400] Duración del recorrido en ms.
 * @param {boolean} [options.fromCurrent=false] Si parte del porcentaje actual en vez de cero.
 * @returns {{ cancel: Function }}
 */
export function animateArcMeterValue(svgElement, targetPct, { duration = 400, fromCurrent = false } = {}) {
  const svg = svgElement.matches && svgElement.matches("svg") ? svgElement : svgElement.querySelector("svg");
  if (!svg) return { cancel: () => {} };

  const meterVal = svg.querySelector(".mono-arc-meter-val");
  const numberText = svg.querySelector(".mono-arc-center-number");
  if (!meterVal) return { cancel: () => {} };

  if (meterVal._arcAnimRaf) {
    cancelAnimationFrame(meterVal._arcAnimRaf);
    meterVal._arcAnimRaf = null;
  }

  const perimeter = meterVal.getTotalLength ? meterVal.getTotalLength() : 251.33;
  meterVal.style.strokeDasharray = `${perimeter} ${perimeter}`;
  meterVal.setAttribute("stroke-dasharray", `${perimeter} ${perimeter}`);
  meterVal.style.transition = "none";

  const startPct = fromCurrent && typeof meterVal._currentPct === "number" ? meterVal._currentPct : 0;
  const startOffset = perimeter * (1 - startPct / 100);
  const endOffset = perimeter * (1 - targetPct / 100);

  meterVal.style.strokeDashoffset = `${startOffset}`;
  meterVal.setAttribute("stroke-dashoffset", `${startOffset}`);

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = Math.max(0, Math.min(elapsed / duration, 1));
    const ease = easeInOutCubic(progress);

    const currentOffset = Math.max(0, startOffset + (endOffset - startOffset) * ease);
    const currentPct = Math.round(startPct + (targetPct - startPct) * ease);

    meterVal.style.strokeDashoffset = `${currentOffset.toFixed(2)}`;
    meterVal.setAttribute("stroke-dashoffset", `${currentOffset.toFixed(2)}`);

    if (numberText) {
      numberText.textContent = `${currentPct}%`;
    }

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      meterVal.style.strokeDashoffset = `${endOffset.toFixed(2)}`;
      meterVal.setAttribute("stroke-dashoffset", `${endOffset.toFixed(2)}`);
      if (numberText) {
        numberText.textContent = `${targetPct}%`;
      }
      meterVal._currentPct = targetPct;
      meterVal._arcAnimRaf = null;
    }
  }

  rafId = requestAnimationFrame(step);
  meterVal._arcAnimRaf = rafId;

  return {
    cancel: () => {
      isCancelled = true;
      if (rafId) cancelAnimationFrame(rafId);
      meterVal._arcAnimRaf = null;
    }
  };
}

/**
 * Inicializa un gráfico de Arc Meter.
 * 
 * @param {string|SVGElement|HTMLElement} target Selector o elemento DOM.
 * @param {Object} [options={}] Opciones de inicialización.
 */
export function initArcMeter(target, options = {}) {
  const elements = typeof target === "string" ? document.querySelectorAll(target) : [target];

  elements.forEach((el) => {
    if (!el) return;
    const svg = el.matches && el.matches("svg") ? el : el.querySelector("svg");
    if (!svg) return;

    const rawTarget = svg.getAttribute("data-target-pct") || svg.getAttribute("data-value") || "90";
    const targetPct = Math.max(0, Math.min(100, parseFloat(rawTarget)));
    const duration = options.duration || parseInt(svg.getAttribute("data-duration") || "400", 10);

    animateArcMeterValue(svg, targetPct, { duration, fromCurrent: false });
  });
}

// Vinculación global al objeto window para máxima interoperabilidad
if (typeof window !== "undefined") {
  window.initArcMeter = initArcMeter;
  window.animateArcMeterValue = animateArcMeterValue;
}

