/**
 * 📊 svgChartAnimator.js
 * 
 * Motor de gráficos SVG minimalistas y monocromáticos nativos (Vanilla JS ES Modules).
 * Cero dependencias externas (sin Chart.js ni D3).
 * Soporta geometría matemática de arcos, barras apiladas con máscaras de cápsula,
 * treemaps particionados, curvas spline cúbicas continuas y columnas conmutables Col/Row.
 * Incluye animaciones fluidas orgánicas (curvas ease-out suaves sin cortes bruscos),
 * tooltips flotantes glassmorphic, copiado de SVG al portapapeles y
 * ciclo coordinado de animaciones de entrada y salida (.is-exiting).
 * 
 * @module svgChartAnimator
 */

/**
 * Funciones de cálculo matemático para curvas Spline Bézier continuas (Catmull-Rom a Cúbica)
 * 
 * @param {Array<{x: number, y: number}>} points Puntos de anclaje (vértices).
 * @param {number} tension Tensión de la curvatura (típico: 0.2 a 0.35).
 * @returns {string} Comando SVG d="M ... C ...".
 */
export function calculateSplinePath(points, tension = 0.25) {
  if (!points || points.length === 0) return "";
  if (points.length === 1) return `M ${points[0].x},${points[0].y}`;

  let d = `M ${points[0].x.toFixed(1)},${points[0].y.toFixed(1)}`;

  for (let i = 0; i < points.length - 1; i++) {
    const p0 = i > 0 ? points[i - 1] : points[i];
    const p1 = points[i];
    const p2 = points[i + 1];
    const p3 = i < points.length - 2 ? points[i + 2] : p2;

    const cp1x = p1.x + (p2.x - p0.x) * tension;
    const cp1y = p1.y + (p2.y - p0.y) * tension;
    const cp2x = p2.x - (p3.x - p1.x) * tension;
    const cp2y = p2.y - (p3.y - p1.y) * tension;

    d += ` C ${cp1x.toFixed(1)},${cp1y.toFixed(1)} ${cp2x.toFixed(1)},${cp2y.toFixed(1)} ${p2.x.toFixed(1)},${p2.y.toFixed(1)}`;
  }

  return d;
}

/**
 * Muestra el tooltip flotante dentro del contenedor relativo de la tarjeta.
 * 
 * @param {HTMLElement} card Tarjeta contenedora.
 * @param {string} text Contenido en texto o HTML seguro.
 * @param {number} clientX Coordenada X del cursor en ventana.
 * @param {number} clientY Coordenada Y del cursor en ventana.
 */
export function showChartTooltip(card, text, clientX, clientY) {
  let tooltip = card.querySelector(".mono-chart-tooltip");
  if (!tooltip) {
    tooltip = document.createElement("div");
    tooltip.className = "mono-chart-tooltip";
    card.appendChild(tooltip);
  }

  const cardRect = card.getBoundingClientRect();
  const relX = clientX - cardRect.left;
  const relY = clientY - cardRect.top;

  tooltip.innerHTML = text;
  tooltip.style.left = `${relX}px`;
  tooltip.style.top = `${relY}px`;
  tooltip.classList.add("visible");
}

/**
 * Oculta el tooltip flotante de la tarjeta.
 * 
 * @param {HTMLElement} card Tarjeta contenedora.
 */
export function hideChartTooltip(card) {
  const tooltip = card.querySelector(".mono-chart-tooltip");
  if (tooltip) {
    tooltip.classList.remove("visible");
  }
}

/**
 * Copia el código SVG o HTML del gráfico al portapapeles con feedback visual.
 * 
 * @param {HTMLElement} buttonElement Botón que disparó la copia.
 */
export async function copyChartSvg(buttonElement) {
  const card = buttonElement.closest(".mono-chart-card");
  if (!card) return;

  const viewport = card.querySelector(".mono-chart-viewport");
  if (!viewport) return;

  const svg = viewport.querySelector("svg");
  let contentToCopy = "";

  if (svg) {
    const clone = svg.cloneNode(true);
    clone.removeAttribute("style");
    clone.setAttribute("xmlns", "http://www.w3.org/2000/svg");
    contentToCopy = clone.outerHTML;
  } else {
    const treemap = viewport.querySelector(".mono-treemap-grid");
    if (treemap) {
      contentToCopy = treemap.outerHTML;
    }
  }

  if (!contentToCopy) return;

  try {
    await navigator.clipboard.writeText(contentToCopy);
    buttonElement.classList.add("copied");
    const originalTitle = buttonElement.getAttribute("title");
    buttonElement.setAttribute("title", "¡SVG Copiado!");

    setTimeout(() => {
      buttonElement.classList.remove("copied");
      if (originalTitle) buttonElement.setAttribute("title", originalTitle);
    }, 1800);
  } catch (err) {
    console.error("Error al copiar SVG al portapapeles:", err);
  }
}

/**
 * Función de curvatura Ease-In-Out Cúbica simétrica
 * Otorga aceleración inicial suave, velocidad máxima en el punto medio y frenado orgánico.
 * 
 * @param {number} t Progreso lineal de 0 a 1.
 * @returns {number} Progreso con curva ease-in-out.
 */
export function easeInOutCubic(t) {
  const clamped = Math.max(0, Math.min(1, t));
  return clamped < 0.5 ? 4 * clamped * clamped * clamped : 1 - Math.pow(-2 * clamped + 2, 3) / 2;
}

/**
 * Helper de conteo numérico local con curva ease-in-out (fallback seguro si TextAnimator aún no está disponible)
 * 
 * @param {HTMLElement} el Elemento DOM objetivo.
 * @param {number} start Valor inicial.
 * @param {number} end Valor objetivo.
 * @param {number} [duration=400] Duración en milisegundos.
 * @param {string} [suffix=""] Sufijo (ej: "%").
 */
function animateLocalCounter(el, start, end, duration = 400, suffix = "") {
  if (!el) return;
  if (el._localCounterRaf) {
    cancelAnimationFrame(el._localCounterRaf);
  }
  const startTime = performance.now();
  function step(now) {
    const elapsed = Math.max(0, now - startTime);
    const progress = Math.max(0, Math.min(elapsed / duration, 1));
    const ease = easeInOutCubic(progress);
    const current = Math.round(start + (end - start) * ease);
    el.textContent = `${current}${suffix}`;
    if (progress < 1) {
      el._localCounterRaf = requestAnimationFrame(step);
    } else {
      el.textContent = `${end}${suffix}`;
      el._localCounterRaf = null;
    }
  }
  el._localCounterRaf = requestAnimationFrame(step);
}

/**
 * Anima la geometría de un rectángulo SVG desde el punto cero (línea base) hasta su valor real.
 * 
 * @param {SVGRectElement} rect Elemento SVG rect a animar.
 * @param {Object} options Opciones de animación.
 * @param {'height'|'width'} [options.dimension='height'] Dimensión que crece.
 * @param {number} options.baseline Posición de la línea base (Y para altura, X para anchura).
 * @param {number} options.targetVal Valor final de la dimensión (altura o anchura).
 * @param {number} [options.delay=0] Retardo antes de iniciar en ms.
 * @param {number} [options.duration=400] Duración en ms.
 * @returns {{ cancel: Function }}
 */
function animateSvgRectGeometry(rect, { dimension = "height", baseline, targetVal, delay = 0, duration = 400 }) {
  if (!rect) return { cancel: () => {} };

  if (rect._geoAnim) {
    clearTimeout(rect._geoAnim.timer);
    cancelAnimationFrame(rect._geoAnim.raf);
    rect._geoAnim = null;
  }

  // Establecer estado inicial en cero absoluto en la línea base
  if (dimension === "height") {
    rect.setAttribute("height", "0");
    rect.setAttribute("y", baseline.toString());
  } else {
    rect.setAttribute("width", "0");
    rect.setAttribute("x", baseline.toString());
  }

  let rafId = null;
  let isCancelled = false;

  const timerId = setTimeout(() => {
    if (isCancelled) return;
    const startTime = performance.now();

    function step(now) {
      if (isCancelled) return;
      const elapsed = Math.max(0, now - startTime);
      const progress = Math.max(0, Math.min(elapsed / duration, 1));
      const ease = easeInOutCubic(progress);
      const currentVal = Math.max(0, targetVal * ease);

      if (dimension === "height") {
        const currentY = baseline - currentVal;
        rect.setAttribute("height", currentVal.toFixed(1));
        rect.setAttribute("y", currentY.toFixed(1));
      } else {
        rect.setAttribute("width", currentVal.toFixed(1));
      }

      if (progress < 1) {
        rafId = requestAnimationFrame(step);
      } else {
        const finalVal = Math.max(0, targetVal);
        if (dimension === "height") {
          rect.setAttribute("height", finalVal.toFixed(1));
          rect.setAttribute("y", (baseline - finalVal).toFixed(1));
        } else {
          rect.setAttribute("width", finalVal.toFixed(1));
        }
        rect._geoAnim = null;
      }
    }

    rafId = requestAnimationFrame(step);
  }, delay);

  const controller = {
    cancel: () => {
      isCancelled = true;
      clearTimeout(timerId);
      if (rafId) cancelAnimationFrame(rafId);
      rect._geoAnim = null;
    }
  };

  rect._geoAnim = controller;
  return controller;
}

/**
 * Anima el desvanecimiento (fade in/out) de un mosaico o elemento con interpolación
 * continua frame a frame por RAF y curva easeInOutCubic.
 * Garantiza un fade progresivo, aterciopelado y uniforme sin saltos bruscos.
 * 
 * @param {HTMLElement} tile Elemento del mosaico.
 * @param {Object} options Opciones de animación.
 * @param {number} [options.delay=0] Retardo en ms antes de iniciar.
 * @param {number} [options.duration=400] Duración del fade en ms.
 * @param {number} [options.fromOpacity=0] Opacidad inicial.
 * @param {number} [options.toOpacity=1] Opacidad final.
 * @returns {{ cancel: Function }}
 */
function animateTileFade(tile, { delay = 0, duration = 400, fromOpacity = 0, toOpacity = 1 } = {}) {
  if (!tile) return { cancel: () => {} };

  if (tile._fadeAnim) {
    clearTimeout(tile._fadeAnim.timer);
    cancelAnimationFrame(tile._fadeAnim.raf);
    tile._fadeAnim = null;
  }

  tile.style.transition = "filter 0.25s ease, transform 0.2s ease";
  tile.style.opacity = fromOpacity.toString();

  let rafId = null;
  let isCancelled = false;

  const timerId = setTimeout(() => {
    if (isCancelled) return;
    const startTime = performance.now();

    function step(now) {
      if (isCancelled) return;
      const elapsed = Math.max(0, now - startTime);
      const progress = Math.max(0, Math.min(elapsed / duration, 1));
      const ease = easeInOutCubic(progress);

      const currentOpacity = Math.max(0, Math.min(1, fromOpacity + (toOpacity - fromOpacity) * ease));
      tile.style.opacity = currentOpacity.toFixed(4);

      if (progress < 1) {
        rafId = requestAnimationFrame(step);
      } else {
        tile.style.opacity = Math.max(0, Math.min(1, toOpacity)).toString();
        tile._fadeAnim = null;
      }
    }

    rafId = requestAnimationFrame(step);
  }, delay);

  const controller = {
    cancel: () => {
      isCancelled = true;
      clearTimeout(timerId);
      if (rafId) cancelAnimationFrame(rafId);
      tile._fadeAnim = null;
    }
  };

  tile._fadeAnim = controller;
  return controller;
}

/**
 * Resuelve los elementos DOM de un gráfico Arc Meter (soporta .mono-chart-card y svg[data-chart="arc-meter"]).
 *
 * @param {HTMLElement|SVGElement} container Tarjeta contenedora o elemento SVG del arco.
 * @returns {{ svg: SVGElement|null, meterVal: SVGElement|null, numberText: SVGElement|null, headlineNum: HTMLElement|null, centerLabel: SVGElement|null, isCard: boolean }}
 */
function resolveArcMeterElements(container) {
  if (!container) {
    return { svg: null, meterVal: null, numberText: null, headlineNum: null, centerLabel: null, isCard: false };
  }
  const isCard = !!(container.classList && container.classList.contains("mono-chart-card"));
  const svg = container.matches && container.matches('svg[data-chart="arc-meter"]')
    ? container
    : container.querySelector('svg[data-chart="arc-meter"], svg');
  const scope = svg || container;
  const meterVal = scope.querySelector(".mono-arc-meter-val");
  const numberText = scope.querySelector(".mono-arc-center-number");
  const headlineNum = isCard ? container.querySelector(".mono-chart-value-big") : null;
  const centerLabel = scope.querySelector(".mono-arc-center-label");

  return { svg, meterVal, numberText, headlineNum, centerLabel, isCard };
}

/**
 * Formatea el texto final del contador central de Arc Meter conservando decimales o displayValue cuando existen.
 *
 * @param {number} targetPct Porcentaje objetivo (0 a 100).
 * @param {string|null} displayText Valor textual explícito (ej. "25%" o "150%").
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
 * Anima el valor del Arc Meter mediante interpolación frame a frame con RAF y curva easeInOutCubic.
 * Garantiza un recorrido visual continuo y suave desde 0% (o desde el valor previo) hasta el porcentaje objetivo.
 * 
 * @param {HTMLElement|SVGElement} card Elemento contenedor o SVG de Arc Meter.
 * @param {number} targetPct Porcentaje objetivo (0 a 100).
 * @param {Object} [options={}] Opciones de animación.
 * @param {number} [options.duration=400] Duración del recorrido en ms.
 * @param {boolean} [options.fromCurrent=false] Si parte del porcentaje actual en vez de cero.
 * @param {number} [options.delay=0] Retardo antes de arrancar en ms.
 * @param {string|null} [options.displayText=null] Texto final exacto a mostrar al terminar (ej. data-val).
 */
export function animateArcMeterValue(card, targetPct, { duration = 400, fromCurrent = false, delay = 0, displayText = null } = {}) {
  const { svg, meterVal, numberText, headlineNum, centerLabel, isCard } = resolveArcMeterElements(card);

  if (!meterVal) return;

  // Cancelar animaciones activas previas en este arco
  if (meterVal._arcAnimRaf) {
    cancelAnimationFrame(meterVal._arcAnimRaf);
    meterVal._arcAnimRaf = null;
  }
  if (card._arcMeterTimeout) {
    clearTimeout(card._arcMeterTimeout);
    card._arcMeterTimeout = null;
  }

  // Longitud perimétrica real del arco semicircular (usa getTotalLength para evitar desajustes de radio en SVG)
  const rawLen = meterVal.getTotalLength ? meterVal.getTotalLength() : 0;
  const perimeter = rawLen > 0 ? rawLen : 282.74;
  meterVal.style.strokeDasharray = `${perimeter} ${perimeter}`;
  meterVal.setAttribute("stroke-dasharray", `${perimeter} ${perimeter}`);
  meterVal.style.transition = "none";

  const startPct = fromCurrent && typeof meterVal._currentPct === "number" ? meterVal._currentPct : 0;

  // Estado inicial en caso de comenzar desde cero
  if (!fromCurrent) {
    meterVal._currentPct = 0;
    meterVal.style.strokeDashoffset = perimeter.toFixed(2);
    meterVal.setAttribute("stroke-dashoffset", perimeter.toFixed(2));
    meterVal.style.opacity = "0";
    if (numberText) numberText.textContent = "0%";
    if (headlineNum) headlineNum.textContent = "0%";
  }

  // En tarjetas sandbox antiguas sin data-target-pct se mantiene la etiqueta contextual dinámica;
  // en gráficos generados por GraphicsModule se respeta siempre la etiqueta provista desde PHP.
  const isNativeGraphicsArc = !!(svg && svg.hasAttribute("data-target-pct"));
  if (centerLabel && isCard && !isNativeGraphicsArc) {
    if (targetPct <= 30) centerLabel.textContent = "Optimal Load";
    else if (targetPct <= 75) centerLabel.textContent = "Moderate Load";
    else centerLabel.textContent = "High Capacity";
  }

  const finalFormatted = isNativeGraphicsArc
    ? formatArcMeterFinalText(targetPct, displayText)
    : `${Math.round(targetPct)}%`;

  // Lanzar bucle de animación con RAF
  const launchAnim = () => {
    const startTime = performance.now();

    function step(now) {
      const elapsed = Math.max(0, now - startTime);
      const progress = Math.max(0, Math.min(elapsed / duration, 1));
      const ease = easeInOutCubic(progress);

      const currentPct = Math.max(0, Math.min(100, startPct + (targetPct - startPct) * ease));
      const currentOffset = Math.max(0, perimeter * (1 - currentPct / 100));

      meterVal.style.strokeDashoffset = currentOffset.toFixed(2);
      meterVal.setAttribute("stroke-dashoffset", currentOffset.toFixed(2));

      // Control de opacidad para ocultar el punto redondo de stroke-linecap en 0%
      if (currentPct <= 0.15) {
        meterVal.style.opacity = "0";
      } else {
        meterVal.style.opacity = "1";
      }

      // Sincronización progresiva de lecturas numéricas en cada frame
      const formatted = `${Math.round(currentPct)}%`;
      if (numberText) numberText.textContent = formatted;
      if (headlineNum) headlineNum.textContent = formatted;

      meterVal._currentPct = currentPct;

      if (progress < 1) {
        meterVal._arcAnimRaf = requestAnimationFrame(step);
      } else {
        meterVal._arcAnimRaf = null;
        meterVal._currentPct = targetPct;
        const finalOffset = perimeter * (1 - targetPct / 100);
        meterVal.style.strokeDashoffset = finalOffset.toFixed(2);
        meterVal.setAttribute("stroke-dashoffset", finalOffset.toFixed(2));
        meterVal.style.opacity = targetPct > 0 ? "1" : "0";
        if (numberText) numberText.textContent = finalFormatted;
        if (headlineNum) headlineNum.textContent = finalFormatted;
      }
    }

    meterVal._arcAnimRaf = requestAnimationFrame(step);
  };

  if (delay > 0) {
    card._arcMeterTimeout = setTimeout(() => {
      card._arcMeterTimeout = null;
      launchAnim();
    }, delay);
  } else {
    launchAnim();
  }
}

/**
 * 1. Inicializa y anima el Arc Meter Gauge (Speedometer de 180°)
 * Comienza su animación en cero absoluto (arco vacío y 0%) y realiza un recorrido
 * visible y continuo desde 0 hasta el porcentaje correspondiente (con curva ease-in-out).
 * Permite además alternar interactivamente porcentajes dinámicos (20%, 70%, 100%).
 * 
 * @param {HTMLElement|SVGElement} card Elemento contenedor o SVG del arco.
 * @param {boolean} [playEntry=true] Si debe ejecutar la animación de entrada.
 */
export function initArcMeter(card, playEntry = true) {
  const { svg, meterVal, numberText, headlineNum, isCard } = resolveArcMeterElements(card);

  if (!meterVal) return;

  const rawLen = meterVal.getTotalLength ? meterVal.getTotalLength() : 0;
  const perimeter = rawLen > 0 ? rawLen : 282.74;
  meterVal.style.strokeDasharray = `${perimeter} ${perimeter}`;
  meterVal.setAttribute("stroke-dasharray", `${perimeter} ${perimeter}`);
  meterVal.style.transition = "none";

  // Configurar botones pills de porcentaje dinámico si existen
  const pillBtns = card.querySelectorAll(".mono-arc-val-pills button, [data-arc-target]");
  pillBtns.forEach((pill) => {
    if (!pill._hasArcPillListener) {
      pill._hasArcPillListener = true;
      pill.addEventListener("click", () => {
        const newTarget = parseFloat(pill.dataset.arcTarget || "20");
        pillBtns.forEach((p) => p.classList.toggle("active", p === pill));
        meterVal.dataset.value = newTarget.toString();
        if (svg && svg.hasAttribute("data-target-pct")) {
          svg.setAttribute("data-target-pct", newTarget.toString());
        }
        if (headlineNum) headlineNum.dataset.target = newTarget.toString();
        card.classList.remove("is-exiting");
        animateArcMeterValue(card, newTarget, { duration: 400, fromCurrent: true, delay: 0 });
      });
    }
  });

  const isNativeGraphicsArc = !!(svg && svg.hasAttribute("data-target-pct"));
  const rawTarget = isNativeGraphicsArc
    ? svg.getAttribute("data-target-pct")
    : (meterVal.dataset.value ?? "20");
  const parsedTarget = parseFloat(rawTarget);
  const targetPct = Number.isFinite(parsedTarget) ? Math.max(0, Math.min(100, parsedTarget)) : 0;

  const rawDuration = svg ? parseInt(svg.getAttribute("data-duration") || "400", 10) : 400;
  const duration = Number.isFinite(rawDuration) && rawDuration > 0 ? rawDuration : 400;
  const displayVal = isNativeGraphicsArc ? (meterVal.getAttribute("data-val") || "").trim() : "";
  const finalFormatted = isNativeGraphicsArc
    ? formatArcMeterFinalText(targetPct, displayVal)
    : `${Math.round(targetPct)}%`;

  if (playEntry) {
    card.classList.remove("is-exiting");
    animateArcMeterValue(card, targetPct, {
      duration,
      fromCurrent: false,
      delay: 0,
      displayText: isNativeGraphicsArc ? finalFormatted : null
    });
  } else {
    if (meterVal._arcAnimRaf) cancelAnimationFrame(meterVal._arcAnimRaf);
    if (card._arcMeterTimeout) clearTimeout(card._arcMeterTimeout);
    const finalOffset = perimeter * (1 - targetPct / 100);
    meterVal.style.strokeDashoffset = finalOffset.toFixed(2);
    meterVal.setAttribute("stroke-dashoffset", finalOffset.toFixed(2));
    meterVal.style.opacity = targetPct > 0 ? "1" : "0";
    meterVal._currentPct = targetPct;
    if (numberText) numberText.textContent = finalFormatted;
    if (headlineNum) headlineNum.textContent = finalFormatted;
  }

  // En tarjetas sandbox sin GraphicsModule se conserva el tooltip local legacy;
  // en gráficos GraphicsModule el tooltip delegado proviene de Charts/tooltip.js.
  if (isCard && !isNativeGraphicsArc) {
    meterVal.onmouseenter = (e) => {
      const curVal = Math.round(typeof meterVal._currentPct === "number" ? meterVal._currentPct : targetPct);
      showChartTooltip(card, `Carga del sistema: <strong>${curVal}%</strong>`, e.clientX, e.clientY);
    };
    meterVal.onmousemove = (e) => {
      const curVal = Math.round(typeof meterVal._currentPct === "number" ? meterVal._currentPct : targetPct);
      showChartTooltip(card, `Carga del sistema: <strong>${curVal}%</strong>`, e.clientX, e.clientY);
    };
    meterVal.onmouseleave = () => hideChartTooltip(card);
  }
}

/**
 * 2. Inicializa el gráfico Stacked Tones (Barras apiladas monocromáticas)
 * Las columnas crecen progresivamente desde el punto 0 (línea base) hasta su altura final
 * con curva ease-in-out cúbica y cadencia rítmica.
 * 
 * @param {HTMLElement} card Elemento de la tarjeta.
 * @param {boolean} [playEntry=true] Si debe ejecutar la animación de entrada.
 */
export function initStackedTones(card, playEntry = true) {
  const barGroups = card.querySelectorAll(".mono-stacked-bar-item");

  barGroups.forEach((group, index) => {
    const label = group.dataset.label || `Q${index + 1}`;
    const total = group.dataset.total || "100";
    const white = group.dataset.white || "40";
    const mid = group.dataset.mid || "35";
    const dark = group.dataset.dark || "25";

    // Encontrar el rectángulo de máscara clipPath de la cápsula
    const clipRect = card.querySelector(`#stacked-clip-${index} rect`) || card.querySelectorAll(".mono-stacked-clip-rect")[index];
    if (!clipRect) return;

    const baseline = 155;
    const targetH = parseFloat(clipRect.dataset.targetH || clipRect.getAttribute("height") || "100");
    clipRect.dataset.targetH = targetH.toString();
    clipRect.dataset.targetY = (baseline - targetH).toString();

    if (playEntry) {
      card.classList.remove("is-exiting");
      group.style.opacity = "1";

      animateSvgRectGeometry(clipRect, {
        dimension: "height",
        baseline,
        targetVal: targetH,
        delay: 0,
        duration: 400
      });
    } else {
      clipRect.setAttribute("height", targetH.toFixed(1));
      clipRect.setAttribute("y", (baseline - targetH).toFixed(1));
      group.style.opacity = "1";
    }

    group.onmouseenter = (e) => {
      const tooltipHtml = `
        <div style="font-weight:700;margin-bottom:4px;border-bottom:1px solid rgba(255,255,255,0.15);padding-bottom:2px;">${label} • ${total} unidades</div>
        <div style="display:flex;gap:8px;font-size:11px;">
          <span style="color:#ffffff;">● Base: ${white}</span>
          <span style="color:#a1a1aa;">● Medio: ${mid}</span>
          <span style="color:#71717a;">● Cima: ${dark}</span>
        </div>
      `;
      showChartTooltip(card, tooltipHtml, e.clientX, e.clientY);
    };

    group.onmousemove = (e) => {
      const tooltipHtml = `
        <div style="font-weight:700;margin-bottom:4px;border-bottom:1px solid rgba(255,255,255,0.15);padding-bottom:2px;">${label} • ${total} unidades</div>
        <div style="display:flex;gap:8px;font-size:11px;">
          <span style="color:#ffffff;">● Base: ${white}</span>
          <span style="color:#a1a1aa;">● Medio: ${mid}</span>
          <span style="color:#71717a;">● Cima: ${dark}</span>
        </div>
      `;
      showChartTooltip(card, tooltipHtml, e.clientX, e.clientY);
    };

    group.onmouseleave = () => hideChartTooltip(card);
  });
}

/**
 * 3. Inicializa el gráfico Tile Treemap (Mosaicos particionados)
 * Los mosaicos aparecen al mismo tiempo con un fade in
 * continuo de 400ms impulsado por RAF y curva easeInOutCubic (sin cortes ni saltos bruscos).
 * 
 * @param {HTMLElement} card Elemento de la tarjeta.
 * @param {boolean} [playEntry=true] Si debe ejecutar la animación de entrada.
 */
export function initTileTreemap(card, playEntry = true) {
  const tiles = card.querySelectorAll(".mono-treemap-tile");

  // Limpiar temporizadores previos de la tarjeta si existían
  if (card._treemapTimeouts && Array.isArray(card._treemapTimeouts)) {
    card._treemapTimeouts.forEach((t) => clearTimeout(t));
  }
  card._treemapTimeouts = [];

  tiles.forEach((tile, index) => {
    const name = tile.dataset.name || "Item";
    const pct = tile.dataset.pct || "25%";
    const info = tile.dataset.info || "Asignación de recursos";

    if (tile._fadeAnim) {
      tile._fadeAnim.cancel();
      tile._fadeAnim = null;
    }

    if (playEntry) {
      card.classList.remove("is-exiting");

      // Todas las cajas aparecen simultáneamente en 400ms con fade in suave
      animateTileFade(tile, { delay: 0, duration: 400, fromOpacity: 0, toOpacity: 1 });
    } else {
      tile.style.opacity = "1";
    }

    tile.onmouseenter = (e) => {
      showChartTooltip(card, `<strong>${name}</strong> (${pct})<br><span style="color:#a1a1aa;font-size:11px;">${info}</span>`, e.clientX, e.clientY);
    };
    tile.onmousemove = (e) => {
      showChartTooltip(card, `<strong>${name}</strong> (${pct})<br><span style="color:#a1a1aa;font-size:11px;">${info}</span>`, e.clientX, e.clientY);
    };
    tile.onmouseleave = () => hideChartTooltip(card);
  });
}

/**
 * 4. Inicializa el gráfico Hybrid Spline + Bar (Barras con curva suave de spline Bézier y toggle)
 * Las barras crecen desde el punto 0 (línea base) hasta su altura con curva ease-in-out.
 * 
 * @param {HTMLElement} card Elemento de la tarjeta.
 * @param {boolean} [playEntry=true] Si debe ejecutar la animación de entrada.
 */
export function initHybridSpline(card, playEntry = true) {
  const splinePath = card.querySelector(".mono-spline-path");
  const splinePoints = card.querySelectorAll(".mono-spline-point");
  const bars = card.querySelectorAll(".mono-hybrid-bar");
  const toggleBtn = card.querySelector(".mono-chart-switch-btn");

  const points = [];
  const baseline = 160;

  bars.forEach((bar, index) => {
    const x = parseFloat(bar.getAttribute("x") || "0") + parseFloat(bar.getAttribute("width") || "32") / 2;
    const origY = parseFloat(bar.dataset.targetY || bar.getAttribute("y") || "0");
    const origH = parseFloat(bar.dataset.targetH || bar.getAttribute("height") || "0");
    bar.dataset.targetY = origY.toString();
    bar.dataset.targetH = origH.toString();

    points.push({ x, y: origY });

    if (playEntry) {
      card.classList.remove("is-exiting");
      bar.style.opacity = "1";
      animateSvgRectGeometry(bar, {
        dimension: "height",
        baseline,
        targetVal: origH,
        delay: 0,
        duration: 400
      });
    } else {
      bar.setAttribute("height", origH.toFixed(1));
      bar.setAttribute("y", origY.toFixed(1));
      bar.style.opacity = "1";
    }

    const month = bar.dataset.month || `Mes ${index + 1}`;
    const value = bar.dataset.val || "0";

    bar.onmouseenter = (e) => {
      showChartTooltip(card, `<strong>${month}</strong>: ${value} unidades`, e.clientX, e.clientY);
    };
    bar.onmousemove = (e) => {
      showChartTooltip(card, `<strong>${month}</strong>: ${value} unidades`, e.clientX, e.clientY);
    };
    bar.onmouseleave = () => hideChartTooltip(card);
  });

  // Trazado de Spline Bézier
  let pathLength = 500;
  if (splinePath && points.length > 0) {
    const d = calculateSplinePath(points, 0.28);
    splinePath.setAttribute("d", d);
    pathLength = splinePath.getTotalLength ? splinePath.getTotalLength() : 500;
    splinePath.style.strokeDasharray = `${pathLength}`;

    if (playEntry) {
      splinePath.style.transition = "none";
      splinePath.style.strokeDashoffset = `${pathLength}`;
      splinePath.style.opacity = "1";
      void splinePath.getBoundingClientRect();

      setTimeout(() => {
        splinePath.style.transition = "stroke-dashoffset 400ms cubic-bezier(0.42, 0, 0.58, 1), opacity 200ms ease-in-out";
        splinePath.style.strokeDashoffset = "0";
      }, 0);
    } else {
      splinePath.style.strokeDashoffset = "0";
      splinePath.style.opacity = "1";
    }
  }

  // Puntos circulares en las cúspides
  splinePoints.forEach((dot, index) => {
    const month = dot.dataset.month || `Mes ${index + 1}`;
    const value = dot.dataset.val || "0";

    dot.style.transformBox = "fill-box";
    dot.style.transformOrigin = "center center";

    if (playEntry) {
      dot.style.transition = "none";
      dot.style.opacity = "0";
      dot.style.transform = "scale(0)";
      void dot.getBoundingClientRect();

      setTimeout(() => {
        dot.style.transition = "transform 400ms cubic-bezier(0.42, 0, 0.58, 1), opacity 400ms ease-in-out";
        dot.style.opacity = "1";
        dot.style.transform = "scale(1)";
      }, 0);
    } else {
      dot.style.opacity = "1";
      dot.style.transform = "scale(1)";
    }

    dot.onmouseenter = (e) => {
      showChartTooltip(card, `Pico <strong>${month}</strong>: <strong>${value}k</strong>`, e.clientX, e.clientY);
    };
    dot.onmousemove = (e) => {
      showChartTooltip(card, `Pico <strong>${month}</strong>: <strong>${value}k</strong>`, e.clientX, e.clientY);
    };
    dot.onmouseleave = () => hideChartTooltip(card);
  });

  // Conmutador interactivo Spline On / Off
  if (toggleBtn && !toggleBtn._hasSplineListener) {
    toggleBtn._hasSplineListener = true;
    toggleBtn.addEventListener("click", () => {
      const isActive = toggleBtn.classList.contains("active");
      if (isActive) {
        toggleBtn.classList.remove("active");
        toggleBtn.textContent = "Spline Off";
        if (splinePath) {
          splinePath.style.transition = "opacity 400ms ease-in-out";
          splinePath.style.opacity = "0";
        }
        splinePoints.forEach((dot) => {
          dot.style.transition = "transform 400ms cubic-bezier(0.42, 0, 0.58, 1), opacity 400ms ease-in-out";
          dot.style.opacity = "0";
          dot.style.transform = "scale(0)";
        });
      } else {
        toggleBtn.classList.add("active");
        toggleBtn.textContent = "Spline On";
        if (splinePath) {
          splinePath.style.transition = "stroke-dashoffset 400ms cubic-bezier(0.42, 0, 0.58, 1), opacity 400ms ease-in-out";
          splinePath.style.opacity = "1";
          splinePath.style.strokeDashoffset = "0";
        }
        splinePoints.forEach((dot) => {
          dot.style.transition = "transform 400ms cubic-bezier(0.42, 0, 0.58, 1), opacity 400ms ease-in-out";
          dot.style.opacity = "1";
          dot.style.transform = "scale(1)";
        });
      }
    });
  }
}

/**
 * 5. Inicializa el gráfico Rounded Pill Pillars (Columnas en cápsula con conmutador Col / Row)
 * Los pilares crecen desde el punto 0 hasta su valor real en ambas orientaciones con ease-in-out.
 * 
 * @param {HTMLElement} card Elemento de la tarjeta.
 * @param {boolean} [playEntry=true] Si debe ejecutar la animación de entrada.
 */
export function initPillPillars(card, playEntry = true) {
  const colGroup = card.querySelector(".pillar-vertical-group");
  const rowGroup = card.querySelector(".pillar-horizontal-group");
  const segmentedOpts = card.querySelectorAll(".mono-segmented-opt");

  function animatePillars(groupEl, mode = "col") {
    if (!groupEl) return;
    const bars = groupEl.querySelectorAll(".mono-pillar-primary, .mono-pillar-secondary");

    if (mode === "col") {
      const baseline = 155;
      bars.forEach((bar) => {
        const targetH = parseFloat(bar.dataset.targetH || bar.getAttribute("height") || "0");
        const targetY = parseFloat(bar.dataset.targetY || bar.getAttribute("y") || "0");
        bar.dataset.targetH = targetH.toString();
        bar.dataset.targetY = targetY.toString();

        bar.style.opacity = "1";

        animateSvgRectGeometry(bar, {
          dimension: "height",
          baseline,
          targetVal: targetH,
          delay: 0,
          duration: 400
        });
      });
    } else {
      const baseline = 75;
      bars.forEach((bar) => {
        const targetW = parseFloat(bar.dataset.targetW || bar.getAttribute("width") || "0");
        bar.dataset.targetW = targetW.toString();

        bar.style.opacity = "1";

        animateSvgRectGeometry(bar, {
          dimension: "width",
          baseline,
          targetVal: targetW,
          delay: 0,
          duration: 400
        });
      });
    }
  }

  function setOrientation(mode) {
    segmentedOpts.forEach((opt) => {
      if (opt.dataset.mode === mode) {
        opt.classList.add("active");
      } else {
        opt.classList.remove("active");
      }
    });

    if (mode === "col") {
      if (colGroup) {
        colGroup.style.display = "";
        colGroup.style.opacity = "1";
      }
      if (rowGroup) {
        rowGroup.style.display = "none";
        rowGroup.style.opacity = "0";
      }
      animatePillars(colGroup, "col");
    } else {
      if (colGroup) {
        colGroup.style.display = "none";
        colGroup.style.opacity = "0";
      }
      if (rowGroup) {
        rowGroup.style.display = "";
        rowGroup.style.opacity = "1";
      }
      animatePillars(rowGroup, "row");
    }
  }

  // Asignar eventos al conmutador Col / Row
  segmentedOpts.forEach((opt) => {
    if (!opt._hasOrientationListener) {
      opt._hasOrientationListener = true;
      opt.addEventListener("click", () => {
        const mode = opt.dataset.mode || "col";
        setOrientation(mode);
      });
    }
  });

  // Animación de entrada inicial para columnas
  if (playEntry) {
    card.classList.remove("is-exiting");
    animatePillars(colGroup, "col");
  }

  // Interacción de tooltips para ambos modos
  const allBars = card.querySelectorAll(".mono-pillar-primary, .mono-pillar-secondary");
  allBars.forEach((bar) => {
    const label = bar.dataset.label || "Serie";
    const val = bar.dataset.val || "0";

    bar.onmouseenter = (e) => {
      showChartTooltip(card, `<strong>${label}</strong>: ${val} pts`, e.clientX, e.clientY);
    };
    bar.onmousemove = (e) => {
      showChartTooltip(card, `<strong>${label}</strong>: ${val} pts`, e.clientX, e.clientY);
    };
    bar.onmouseleave = () => hideChartTooltip(card);
  });
}

let svgChartTooltipsPromise = null;

/**
 * Inicializa el controlador delegado de tooltips flotantes para gráficos de GraphicsModule.
 */
function ensureChartsTooltips() {
  if (typeof document === "undefined") return;
  if (typeof window.Charts?.initChartTooltips === "function") {
    window.Charts.initChartTooltips();
    return;
  }
  if (svgChartTooltipsPromise) return;

  const tooltipScript = document.querySelector('script[src*="/Library/Charts/tooltip.js"]');
  if (tooltipScript && tooltipScript.src) {
    svgChartTooltipsPromise = import(tooltipScript.src)
      .then((mod) => {
        if (mod && typeof mod.initChartTooltips === "function") {
          mod.initChartTooltips();
        }
      })
      .catch(() => {});
  }
}

/**
 * Mapa de inicializadores por atributo [data-chart] para gráficos nativos de GraphicsModule.
 * Todos los gráficos nativos delegan en sus inicializadores canónicos de la librería Charts.
 */
const NATIVE_CHART_INITIALIZERS = {
  "arc-meter": (el) => {
    if (typeof window.initArcMeter !== "function") return false;
    window.initArcMeter(el);
    return true;
  },
  "hybrid-spline": (el) => {
    if (typeof window.initHybridSpline !== "function") return false;
    window.initHybridSpline(el);
    return true;
  },
  "horizontal-bars": (el) => {
    if (typeof window.initHorizontalBars !== "function") return false;
    window.initHorizontalBars(el);
    return true;
  },
  "stacked-tones": (el) => {
    if (typeof window.initStackedTones !== "function") return false;
    window.initStackedTones(el);
    return true;
  },
  "tile-treemap": (el) => {
    if (typeof window.initTileTreemap !== "function") return false;
    window.initTileTreemap(el);
    return true;
  },
  "pill-pillars": (el) => {
    if (typeof window.initPillPillars !== "function") return false;
    window.initPillPillars(el);
    return true;
  },
  "simple-bars": (el) => {
    if (typeof window.initSimpleBars !== "function") return false;
    window.initSimpleBars(el);
    return true;
  },
  "bullet-target": (el) => {
    if (typeof window.initBulletTarget !== "function") return false;
    window.initBulletTarget(el);
    return true;
  },
  "rounded-donut": (el) => {
    if (typeof window.initRoundedDonut !== "function") return false;
    window.initRoundedDonut(el);
    return true;
  },
  "pyramid-stack": (el) => {
    if (typeof window.initPyramidStack !== "function") return false;
    window.initPyramidStack(el);
    return true;
  },
  "spline-dynamics": (el) => {
    if (typeof window.initSplineDynamics !== "function") return false;
    window.initSplineDynamics(el);
    return true;
  },
  "matrix-heatmap": (el) => {
    if (typeof window.initMatrixHeatmap !== "function") return false;
    window.initMatrixHeatmap(el);
    return true;
  }
};

/**
 * Inicializa los gráficos nativos de GraphicsModule ([data-chart]) visibles dentro de un ámbito.
 * Delega en `window.initCharts` (librería Charts) como fuente única de verdad cuando está disponible.
 *
 * @param {Document|HTMLElement} context Ámbito de búsqueda.
 * @param {boolean} playEntry Si debe reproducir animaciones de entrada.
 */
function initNativeGraphicsCharts(context, playEntry) {
  if (!context || !context.querySelectorAll) return;

  if (typeof window.initCharts === "function") {
    window.initCharts(context);
    return;
  }

  const charts = context.querySelectorAll("[data-chart]");
  if (charts.length === 0) return;

  ensureChartsTooltips();

  charts.forEach((chartEl) => {
    if (chartEl.closest(".mono-chart-card")) return;
    if (chartEl.closest(".hidden")) return;
    if (chartEl.dataset.chartInitialized === "true") return;

    const chartType = chartEl.getAttribute("data-chart") || "";
    const handler = NATIVE_CHART_INITIALIZERS[chartType];
    if (!handler) return;

    const initialized = handler(chartEl, playEntry);
    if (initialized) {
      chartEl.dataset.chartInitialized = "true";
    }
  });
}

/**
 * Inicializa todos los gráficos SVG dentro de un contenedor o documento.
 * 
 * @param {Document|HTMLElement} [context=document] Ámbito de búsqueda.
 * @param {boolean} [playEntry=true] Si debe reproducir animaciones de entrada.
 */
export function initAllSvgCharts(context = document, playEntry = true) {
  const cards = context.querySelectorAll(".mono-chart-card");

  cards.forEach((card) => {
    const type = card.dataset.chartType;

    // Configurar botón de copiado de SVG
    const copyBtn = card.querySelector(".mono-chart-copy-btn");
    if (copyBtn && !copyBtn._hasCopyListener) {
      copyBtn._hasCopyListener = true;
      copyBtn.addEventListener("click", () => copyChartSvg(copyBtn));
    }

    switch (type) {
      case "arc-meter":
        initArcMeter(card, playEntry);
        break;
      case "stacked-tones":
        initStackedTones(card, playEntry);
        break;
      case "tile-treemap":
        initTileTreemap(card, playEntry);
        break;
      case "hybrid-spline":
        initHybridSpline(card, playEntry);
        break;
      case "pill-pillars":
        initPillPillars(card, playEntry);
        break;
      default:
        if (card.querySelector(".mono-arc-meter-val")) initArcMeter(card, playEntry);
        else if (card.querySelector(".mono-stacked-bar-item")) initStackedTones(card, playEntry);
        else if (card.querySelector(".mono-treemap-grid")) initTileTreemap(card, playEntry);
        else if (card.querySelector(".mono-spline-path")) initHybridSpline(card, playEntry);
        else if (card.querySelector(".mono-pillar-primary")) initPillPillars(card, playEntry);
        break;
    }
  });

  initNativeGraphicsCharts(context, playEntry);
}

/**
 * Ejecuta la animación coordinada de salida para todos los gráficos del documento.
 * 
 * @param {Document|HTMLElement} [context=document] Ámbito de búsqueda.
 */
export function playAllSvgChartExits(context = document) {
  const cards = context.querySelectorAll(".mono-chart-card");

  cards.forEach((card) => {
    card.classList.add("is-exiting");

    // Limpiar temporizadores de entrada pendientes si los hay
    if (card._arcMeterTimeout) {
      clearTimeout(card._arcMeterTimeout);
      card._arcMeterTimeout = null;
    }
    if (card._treemapTimeouts && Array.isArray(card._treemapTimeouts)) {
      card._treemapTimeouts.forEach((t) => clearTimeout(t));
      card._treemapTimeouts = [];
    }

    // Retraer Arc Meter hacia 0% mediante interpolación frame a frame con RAF
    const meterVal = card.querySelector(".mono-arc-meter-val");
    if (meterVal) {
      if (meterVal._arcAnimRaf) {
        cancelAnimationFrame(meterVal._arcAnimRaf);
        meterVal._arcAnimRaf = null;
      }
      const perimeter = meterVal.getTotalLength ? meterVal.getTotalLength() : 251.33;
      const startPct = typeof meterVal._currentPct === "number" ? meterVal._currentPct : parseFloat(meterVal.dataset.value || "20");
      const startTime = performance.now();
      const exitDuration = 400;

      function exitStep(now) {
        const elapsed = Math.max(0, now - startTime);
        const progress = Math.max(0, Math.min(elapsed / exitDuration, 1));
        const ease = easeInOutCubic(progress);
        const currentPct = Math.max(0, startPct * (1 - ease));
        const currentOffset = Math.max(0, perimeter * (1 - currentPct / 100));

        meterVal.style.strokeDashoffset = currentOffset.toFixed(2);
        meterVal.setAttribute("stroke-dashoffset", currentOffset.toFixed(2));
        if (currentPct <= 0.15) {
          meterVal.style.opacity = "0";
        }

        const formatted = `${Math.round(currentPct)}%`;
        const numText = card.querySelector(".mono-arc-center-number");
        const headNum = card.querySelector(".mono-chart-value-big");
        if (numText) numText.textContent = formatted;
        if (headNum) headNum.textContent = formatted;

        meterVal._currentPct = currentPct;

        if (progress < 1) {
          meterVal._arcAnimRaf = requestAnimationFrame(exitStep);
        } else {
          meterVal._arcAnimRaf = null;
          meterVal.style.opacity = "0";
          meterVal.style.strokeDashoffset = perimeter.toFixed(2);
          meterVal.setAttribute("stroke-dashoffset", perimeter.toFixed(2));
          meterVal._currentPct = 0;
          if (numText) numText.textContent = "0%";
          if (headNum) headNum.textContent = "0%";
        }
      }

      meterVal._arcAnimRaf = requestAnimationFrame(exitStep);
    } else {
      // Retraer números de texto asociados hacia 0 en las demás tarjetas
      const counterNumbers = card.querySelectorAll(".text-counter, .mono-chart-value-big");
      counterNumbers.forEach((num) => {
        const currentVal = parseFloat(num.textContent.replace(/[^0-9.]/g, "") || "0");
        if (window.TextAnimator && typeof window.TextAnimator.animateCounter === "function") {
          window.TextAnimator.animateCounter(num, {
            start: currentVal,
            end: 0,
            suffix: num.dataset.suffix || "%",
            duration: 400,
            decimals: parseInt(num.dataset.decimals || "0", 10)
          });
        } else {
          animateLocalCounter(num, currentVal, 0, 400, num.dataset.suffix || "%");
        }
      });
    }

    // Retraer rectángulos de barras hacia su línea base 0
    const clipRects = card.querySelectorAll(".mono-stacked-clip-rect");
    clipRects.forEach((r) => {
      animateSvgRectGeometry(r, {
        dimension: "height",
        baseline: 155,
        targetVal: 0,
        delay: 0,
        duration: 400
      });
    });

    const hybridBars = card.querySelectorAll(".mono-hybrid-bar");
    hybridBars.forEach((b) => {
      animateSvgRectGeometry(b, {
        dimension: "height",
        baseline: 160,
        targetVal: 0,
        delay: 0,
        duration: 400
      });
    });

    const pillars = card.querySelectorAll(".mono-pillar-primary, .mono-pillar-secondary");
    pillars.forEach((p) => {
      const isCol = !card.querySelector(".pillar-horizontal-group") || card.querySelector(".pillar-horizontal-group").style.display === "none";
      if (isCol) {
        animateSvgRectGeometry(p, {
          dimension: "height",
          baseline: 155,
          targetVal: 0,
          delay: 0,
          duration: 400
        });
      } else {
        animateSvgRectGeometry(p, {
          dimension: "width",
          baseline: 75,
          targetVal: 0,
          delay: 0,
          duration: 400
        });
      }
    });

    // Retraer mosaicos de Tile Treemap con fade out suave
    const treemapTiles = card.querySelectorAll(".mono-treemap-tile");
    treemapTiles.forEach((tile) => {
      const currentOp = parseFloat(tile.style.opacity || "1");
      animateTileFade(tile, { delay: 0, duration: 400, fromOpacity: currentOp, toOpacity: 0 });
    });
  });
}

/**
 * Función principal para inicio automático con el framework Eber
 */
export function svgChartAnimator() {
  initAllSvgCharts(document, true);
}

// Exposición global en window para accesibilidad universal
if (typeof window !== "undefined") {
  window.SvgChartAnimator = {
    initAllSvgCharts,
    playAllSvgChartExits,
    initArcMeter,
    animateArcMeterValue,
    initStackedTones,
    initTileTreemap,
    initHybridSpline,
    initPillPillars,
    copyChartSvg,
    showChartTooltip,
    hideChartTooltip,
    calculateSplinePath,
    svgChartAnimator
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => svgChartAnimator());
  } else {
    // Si el DOM ya cargó, iniciar de forma inmediata
    setTimeout(() => svgChartAnimator(), 50);
  }
}
