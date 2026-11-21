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
 * Obtiene de forma segura la longitud total de un path SVG, devolviendo un fallback
 * cuando el nodo está oculto, desconectado o el navegador retorna 0 / lanza error.
 *
 * @param {SVGGeometryElement|Element} pathEl Elemento SVG con getTotalLength().
 * @param {number} [fallback=0] Longitud de respaldo si no puede medirse.
 * @returns {number}
 */
export function safeGetTotalLength(pathEl, fallback = 0) {
  if (!pathEl || typeof pathEl.getTotalLength !== "function") return fallback;
  try {
    const len = pathEl.getTotalLength();
    return Number.isFinite(len) && len > 0 ? len : fallback;
  } catch (e) {
    return fallback;
  }
}

/**
 * Formatea el texto final del contador central de Arc Meter conservando decimales o displayValue cuando existen.
 *
 * @param {number} targetPct Porcentaje objetivo (0 a 100).
 * @param {string|null} [displayText=null] Valor textual explícito (ej. "40.5%").
 * @returns {string}
 */
function formatArcMeterFinalText(targetPct, displayText = null) {
  if (typeof displayText === "string" && displayText.trim() !== "") {
    return displayText.trim();
  }
  const rounded = Number.isInteger(targetPct) ? targetPct : Number(targetPct.toFixed(2));
  return `${rounded}%`;
}

/**
 * Obtiene el ancho real renderizado en píxeles de un elemento SVG o su contenedor padre.
 *
 * @param {SVGElement} svg Elemento SVG del gráfico.
 * @returns {number} Ancho en píxeles (0 si el elemento está oculto).
 */
export function getChartContainerWidth(svg) {
  if (!svg) return 0;
  const parent = svg.parentElement;
  const rectW = svg.getBoundingClientRect ? svg.getBoundingClientRect().width : 0;
  const rawW = svg.clientWidth || rectW || (parent ? parent.clientWidth : 0);
  return Math.round(rawW);
}

/**
 * Ejecuta una función de ajuste 1:1 en el render inicial y ante redimensionamientos reales,
 * evitando bucles de ResizeObserver y desconectando observadores de nodos removidos.
 *
 * @param {SVGElement} svg Elemento SVG del gráfico.
 * @param {Function} fitFn Función de ajuste que recibe `(svg, containerWidth)`.
 */
export function observeChartResize(svg, fitFn) {
  if (!svg || typeof fitFn !== "function") return;

  const runFit = () => {
    if (!svg.isConnected) {
      if (svg._chartResizeObserver) {
        svg._chartResizeObserver.disconnect();
        svg._chartResizeObserver = null;
      }
      return;
    }
    const w = getChartContainerWidth(svg);
    if (w <= 0) return;
    if (svg._lastFitWidth && Math.abs(w - svg._lastFitWidth) < 1) return;
    svg._lastFitWidth = w;
    fitFn(svg, w);
  };

  runFit();

  if (typeof ResizeObserver !== "undefined" && !svg._chartResizeObserver) {
    const target = svg.parentElement || svg;
    let rafPending = false;
    const ro = new ResizeObserver(() => {
      if (rafPending) return;
      rafPending = true;
      requestAnimationFrame(() => {
        rafPending = false;
        runFit();
      });
    });
    ro.observe(target);
    svg._chartResizeObserver = ro;
  }
}

/**
 * Calcula los comandos SVG de una curva Spline Bézier cúbica continua (Catmull-Rom).
 *
 * @param {Array<{x: number, y: number}>} points Puntos ordenados.
 * @param {number} [tension=0.25] Tensión de curvatura.
 * @returns {string} Comando path `M ... C ...`.
 */
export function computeSplinePath(points, tension = 0.25) {
  const count = points.length;
  if (count === 0) return "";
  if (count === 1) return `M ${points[0].x},${points[0].y}`;

  let d = `M ${points[0].x.toFixed(1)},${points[0].y.toFixed(1)}`;
  for (let i = 0; i < count - 1; i++) {
    const p0 = i > 0 ? points[i - 1] : points[i];
    const p1 = points[i];
    const p2 = points[i + 1];
    const p3 = i < count - 2 ? points[i + 2] : p2;

    const cp1x = p1.x + (p2.x - p0.x) * tension;
    const cp1y = p1.y + (p2.y - p0.y) * tension;
    const cp2x = p2.x - (p3.x - p1.x) * tension;
    const cp2y = p2.y - (p3.y - p1.y) * tension;

    d += ` C ${cp1x.toFixed(1)},${cp1y.toFixed(1)} ${cp2x.toFixed(1)},${cp2y.toFixed(1)} ${p2.x.toFixed(1)},${p2.y.toFixed(1)}`;
  }

  return d;
}

/**
 * Anima el recorrido perimétrico del arco y su contador numérico central.
 * 
 * @param {SVGElement|HTMLElement} svgElement Elemento SVG del arco o contenedor.
 * @param {number} targetPct Porcentaje objetivo (0 a 100).
 * @param {Object} [options={}] Opciones de animación.
 * @param {number} [options.duration=400] Duración del recorrido en ms.
 * @param {boolean} [options.fromCurrent=false] Si parte del porcentaje actual en vez de cero.
 * @param {string|null} [options.displayText=null] Texto final exacto a mostrar al terminar (ej. data-val).
 * @returns {{ cancel: Function }}
 */
export function animateArcMeterValue(svgElement, targetPct, { duration = 400, fromCurrent = false, displayText = null } = {}) {
  if (!svgElement) return { cancel: () => {} };
  const svg = svgElement.matches && svgElement.matches("svg") ? svgElement : svgElement.querySelector("svg");
  if (!svg) return { cancel: () => {} };

  const meterVal = svg.querySelector(".mono-arc-meter-val");
  const numberText = svg.querySelector(".mono-arc-center-number");
  if (!meterVal) return { cancel: () => {} };

  if (typeof meterVal._arcAnimCancel === "function") {
    meterVal._arcAnimCancel();
  }
  if (meterVal._arcAnimRaf) {
    cancelAnimationFrame(meterVal._arcAnimRaf);
    meterVal._arcAnimRaf = null;
  }

  const parsedTarget = Number(targetPct);
  const safeTargetPct = Number.isFinite(parsedTarget) ? Math.max(0, Math.min(100, parsedTarget)) : 0;
  const parsedDur = Number(duration);
  const safeDuration = Number.isFinite(parsedDur) && parsedDur >= 0 ? parsedDur : 400;

  const perimeter = safeGetTotalLength(meterVal, 282.74);
  meterVal.style.strokeDasharray = `${perimeter} ${perimeter}`;
  meterVal.setAttribute("stroke-dasharray", `${perimeter} ${perimeter}`);
  meterVal.style.transition = "none";

  const startPct = fromCurrent && typeof meterVal._currentPct === "number" ? meterVal._currentPct : 0;
  const startOffset = perimeter * (1 - startPct / 100);
  const endOffset = perimeter * (1 - safeTargetPct / 100);
  const finalText = formatArcMeterFinalText(safeTargetPct, displayText);

  meterVal.style.strokeDashoffset = `${startOffset.toFixed(2)}`;
  meterVal.setAttribute("stroke-dashoffset", `${startOffset.toFixed(2)}`);
  meterVal.style.opacity = startPct <= 0.15 ? "0" : "1";
  if (numberText) {
    numberText.textContent = `${Math.round(startPct)}%`;
  }

  let isCancelled = false;
  let rafId = null;
  const startTime = performance.now();

  function step(now) {
    if (isCancelled) return;
    const elapsed = Math.max(0, now - startTime);
    const progress = safeDuration > 0 ? Math.max(0, Math.min(elapsed / safeDuration, 1)) : 1;
    const ease = easeInOutCubic(progress);

    const currentOffset = Math.max(0, startOffset + (endOffset - startOffset) * ease);
    const rawPct = Math.max(0, Math.min(100, startPct + (safeTargetPct - startPct) * ease));
    const currentPct = Math.round(rawPct);

    meterVal.style.strokeDashoffset = `${currentOffset.toFixed(2)}`;
    meterVal.setAttribute("stroke-dashoffset", `${currentOffset.toFixed(2)}`);
    meterVal.style.opacity = rawPct <= 0.15 ? "0" : "1";
    meterVal._currentPct = rawPct;

    if (numberText) {
      numberText.textContent = `${currentPct}%`;
    }

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
      meterVal._arcAnimRaf = rafId;
    } else {
      meterVal.style.strokeDashoffset = `${endOffset.toFixed(2)}`;
      meterVal.setAttribute("stroke-dashoffset", `${endOffset.toFixed(2)}`);
      meterVal.style.opacity = safeTargetPct > 0 ? "1" : "0";
      if (numberText) {
        numberText.textContent = finalText;
      }
      meterVal._currentPct = safeTargetPct;
      meterVal._arcAnimRaf = null;
      meterVal._arcAnimCancel = null;
    }
  }

  const cancel = () => {
    isCancelled = true;
    if (rafId) cancelAnimationFrame(rafId);
    meterVal._arcAnimRaf = null;
    meterVal._arcAnimCancel = null;
  };
  meterVal._arcAnimCancel = cancel;

  if (safeDuration === 0) {
    step(startTime);
    return { cancel };
  }

  rafId = requestAnimationFrame(step);
  meterVal._arcAnimRaf = rafId;

  return { cancel };
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

    const rawTarget = svg.getAttribute("data-target-pct") ?? svg.getAttribute("data-value") ?? "90";
    const parsedTarget = parseFloat(rawTarget);
    const targetPct = Number.isFinite(parsedTarget) ? Math.max(0, Math.min(100, parsedTarget)) : 0;

    const rawDur = options.duration !== undefined
      ? options.duration
      : parseInt(svg.getAttribute("data-duration") ?? "400", 10);
    const duration = Number.isFinite(rawDur) && rawDur >= 0 ? rawDur : 400;

    const meterVal = svg.querySelector(".mono-arc-meter-val");
    const attrDisplay = meterVal ? meterVal.getAttribute("data-val") : null;
    const displayText = options.displayText !== undefined
      ? options.displayText
      : (attrDisplay && attrDisplay.trim() !== "" ? attrDisplay.trim() : null);

    animateArcMeterValue(svg, targetPct, { duration, fromCurrent: false, displayText });
  });
}

// Vinculación global al objeto window para máxima interoperabilidad
if (typeof window !== "undefined") {
  window.initArcMeter = initArcMeter;
  window.animateArcMeterValue = animateArcMeterValue;
}

