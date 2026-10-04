/**
 * 📊 tooltip.js
 * 
 * Controlador universal de tooltips flotantes para gráficos SVG (Eber Framework).
 * Cero dependencias externas.
 * Singleton en document.body con delegación de eventos y detección inteligente
 * de activación global y local ([data-tooltip="true|false"]).
 * 
 * @module ChartTooltip
 */

let tooltipEl = null;

/**
 * Obtiene o crea el elemento singleton del tooltip en el DOM.
 * 
 * @returns {HTMLElement}
 */
export function getTooltipElement() {
  if (tooltipEl && document.body.contains(tooltipEl)) {
    return tooltipEl;
  }

  tooltipEl = document.getElementById("mono-charts-floating-tooltip");
  if (!tooltipEl) {
    tooltipEl = document.createElement("div");
    tooltipEl.id = "mono-charts-floating-tooltip";
    tooltipEl.className = "mono-chart-tooltip";
    tooltipEl.setAttribute("role", "tooltip");
    tooltipEl.setAttribute("aria-hidden", "true");
    document.body.appendChild(tooltipEl);
  }

  return tooltipEl;
}

/**
 * Extrae la información contextual y construye el HTML para el tooltip
 * según el tipo de elemento interactivo del gráfico.
 * 
 * @param {Element} target Elemento interactivo del SVG.
 * @returns {string|null} HTML formateado o null si no hay datos.
 */
function buildTooltipContent(target) {
  if (!target) return null;

  // 1. Stacked Tones: Barra completa o segmento
  const stackedItem = target.closest(".mono-stacked-bar-item");
  if (stackedItem) {
    const label = stackedItem.getAttribute("data-label") || "";
    const total = stackedItem.getAttribute("data-total") || "";
    const white = stackedItem.getAttribute("data-white");
    const mid = stackedItem.getAttribute("data-mid");
    const dark = stackedItem.getAttribute("data-dark");

    let html = `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    html += `<div class="mono-tooltip-row"><span>Total:</span> <strong>${escapeHtml(total)}</strong></div>`;
    if (dark !== null && dark !== "") {
      html += `<div class="mono-tooltip-row mono-sub"><span>Nivel superior:</span> <span>${escapeHtml(dark)}</span></div>`;
    }
    if (mid !== null && mid !== "") {
      html += `<div class="mono-tooltip-row mono-sub"><span>Nivel medio:</span> <span>${escapeHtml(mid)}</span></div>`;
    }
    if (white !== null && white !== "") {
      html += `<div class="mono-tooltip-row mono-sub"><span>Nivel base:</span> <span>${escapeHtml(white)}</span></div>`;
    }
    return html;
  }

  // 2. Tile Treemap: Mosaico vectorial
  const tileGroup = target.closest(".mono-treemap-tile-group");
  if (tileGroup) {
    const label = tileGroup.getAttribute("data-label") || "";
    const pct = tileGroup.getAttribute("data-pct") || "";
    const tag = tileGroup.getAttribute("data-tag") || "";

    let html = `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    html += `<div class="mono-tooltip-row"><span>Porcentaje:</span> <strong>${escapeHtml(pct)}</strong></div>`;
    if (tag) {
      html += `<div class="mono-tooltip-tag">${escapeHtml(tag)}</div>`;
    }
    return html;
  }

  // 3. Arc Meter: Arco de progreso
  if (target.matches && target.matches(".mono-arc-meter-val")) {
    const label = target.getAttribute("data-label") || "";
    const val = target.getAttribute("data-val") || "";
    let html = "";
    if (label) html += `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    html += `<div class="mono-tooltip-row"><span>Valor:</span> <strong>${escapeHtml(val)}</strong></div>`;
    return html;
  }

  // 4. Hybrid Spline: Barra o Punto
  const splineBar = target.closest(".mono-spline-bar-rect");
  const splineDot = target.closest(".mono-spline-dot");
  if (splineBar || splineDot) {
    const el = splineBar || splineDot;
    const label = el.getAttribute("data-label") || "";
    const val = el.getAttribute("data-val") || "";
    let html = "";
    if (label) html += `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    html += `<div class="mono-tooltip-row"><span>Valor:</span> <strong>${escapeHtml(val)}</strong></div>`;
    return html;
  }

  // 5. Pill Pillars: Pilar primario/secundario o grupo de pareja
  const pillarPrimary = target.closest(".mono-pillar-primary");
  const pillarSecondary = target.closest(".mono-pillar-secondary");
  if (pillarPrimary || pillarSecondary) {
    const el = pillarPrimary || pillarSecondary;
    const label = el.getAttribute("data-label") || "";
    const val = el.getAttribute("data-val") || "";
    const series = el.getAttribute("data-series");
    const seriesName = series === "1" ? "Principal" : "Secundario";
    let html = "";
    if (label) html += `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    html += `<div class="mono-tooltip-row"><span>${seriesName}:</span> <strong>${escapeHtml(val)}</strong></div>`;
    return html;
  }

  const pillarGroup = target.closest(".mono-pillar-pair-group");
  if (pillarGroup) {
    const label = pillarGroup.getAttribute("data-label") || "";
    const v1 = pillarGroup.getAttribute("data-v1") || "";
    const v2 = pillarGroup.getAttribute("data-v2") || "";
    let html = `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    html += `<div class="mono-tooltip-row"><span>Principal:</span> <strong>${escapeHtml(v1)}</strong></div>`;
    html += `<div class="mono-tooltip-row"><span>Secundario:</span> <strong>${escapeHtml(v2)}</strong></div>`;
    return html;
  }

  // 6. Simple Bars: Barra vertical simple
  const simpleBar = target.closest(".mono-simple-bar-item");
  if (simpleBar) {
    const label = simpleBar.getAttribute("data-label") || "";
    const val = simpleBar.getAttribute("data-val") || "";
    let html = "";
    if (label) html += `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    html += `<div class="mono-tooltip-row"><span>Valor:</span> <strong>${escapeHtml(val)}</strong></div>`;
    return html;
  }

  // 7. Horizontal Bars: Barra horizontal
  const hbarItem = target.closest(".mono-hbar-item");
  if (hbarItem) {
    const label = hbarItem.getAttribute("data-label") || "";
    const val = hbarItem.getAttribute("data-val") || "";
    let html = "";
    if (label) html += `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    html += `<div class="mono-tooltip-row"><span>Valor:</span> <strong>${escapeHtml(val)}</strong></div>`;
    return html;
  }

  // 8. Bullet Target: Fila con objetivo y benchmark
  const bulletRow = target.closest(".mono-bullet-row");
  if (bulletRow) {
    const label = bulletRow.getAttribute("data-label") || "";
    const val = bulletRow.getAttribute("data-val") || "";
    const tgt = bulletRow.getAttribute("data-target") || "";
    const diff = bulletRow.getAttribute("data-diff") || "";

    let html = `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    html += `<div class="mono-tooltip-row"><span>Actual:</span> <strong>${escapeHtml(val)}</strong></div>`;
    html += `<div class="mono-tooltip-row mono-sub"><span>Objetivo:</span> <strong>${escapeHtml(tgt)}</strong></div>`;
    if (diff) {
      const isPositive = diff.startsWith("+");
      html += `<div class="mono-tooltip-row mono-sub"><span>Diferencia:</span> <span style="color: ${isPositive ? "#10b981" : "#f87171"}">${escapeHtml(diff)}</span></div>`;
    }
    return html;
  }

  // 9. Rounded Donut: Segmento de rosca suave
  const donutSeg = target.closest(".mono-donut-segment");
  if (donutSeg) {
    const label = donutSeg.getAttribute("data-label") || "";
    const val = donutSeg.getAttribute("data-val") || "";
    const pct = donutSeg.getAttribute("data-pct") || "";

    let html = "";
    if (label) html += `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    if (val && val !== pct) html += `<div class="mono-tooltip-row"><span>Valor:</span> <strong>${escapeHtml(val)}</strong></div>`;
    if (pct) html += `<div class="mono-tooltip-row"><span>Distribución:</span> <strong>${escapeHtml(pct)}</strong></div>`;
    return html;
  }

  // 10. Pyramid Stack: Nivel jerárquico de pirámide
  const pyramidTier = target.closest(".mono-pyramid-tier");
  if (pyramidTier) {
    const label = pyramidTier.getAttribute("data-label") || "";
    const tier = pyramidTier.getAttribute("data-tier") || "";
    const val = pyramidTier.getAttribute("data-val") || "";

    let html = "";
    if (label) html += `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    if (tier) html += `<div class="mono-tooltip-row"><span>Jerarquía:</span> <strong>${escapeHtml(tier)}</strong></div>`;
    if (val) html += `<div class="mono-tooltip-row mono-sub"><span>Métrica:</span> <span>${escapeHtml(val)}</span></div>`;
    return html;
  }

  // 11. Spline Dynamics: Nodo dinámico
  const dynNode = target.closest(".mono-spline-dyn-node");
  if (dynNode) {
    const label = dynNode.getAttribute("data-label") || "";
    const val = dynNode.getAttribute("data-val") || "";
    const ref = dynNode.getAttribute("data-ref") || "";

    let html = "";
    if (label) html += `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    if (val) html += `<div class="mono-tooltip-row"><span>Primario:</span> <strong>${escapeHtml(val)}</strong></div>`;
    if (ref) html += `<div class="mono-tooltip-row mono-sub"><span>Referencia:</span> <span>${escapeHtml(ref)}</span></div>`;
    return html;
  }

  // 12. Matrix Heatmap: Celda redondeada de actividad
  const matrixCell = target.closest(".mono-matrix-cell");
  if (matrixCell) {
    const label = matrixCell.getAttribute("data-label") || "";
    const val = matrixCell.getAttribute("data-val") || "";
    const density = matrixCell.getAttribute("data-density") || "";

    let html = "";
    if (label) html += `<div class="mono-tooltip-header"><strong>${escapeHtml(label)}</strong></div>`;
    if (val) html += `<div class="mono-tooltip-row"><span>Actividad:</span> <strong>${escapeHtml(val)}</strong></div>`;
    if (density && density !== val) html += `<div class="mono-tooltip-row mono-sub"><span>Densidad:</span> <span>${escapeHtml(density)}</span></div>`;
    return html;
  }

  return null;
}

/**
 * Escapa caracteres HTML para prevenir inyecciones.
 * 
 * @param {string} str Cadena a escapar.
 * @returns {string}
 */
function escapeHtml(str) {
  if (typeof str !== "string") return String(str);
  return str
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

let isTooltipInitialized = false;

/**
 * Inicializa el observador de eventos para tooltips de gráficos en el documento.
 */
export function initChartTooltips() {
  if (typeof document === "undefined") return;
  if (isTooltipInitialized) return;
  isTooltipInitialized = true;

  const tooltip = getTooltipElement();
  let currentTarget = null;

  // Delegación de eventos en el documento
  document.addEventListener("pointerover", (e) => {
    const target = e.target;
    if (!target || !target.closest) return;

    // Verificar si pertenece a un gráfico SVG
    const svg = target.closest("svg.mono-chart-svg");
    if (!svg) {
      if (currentTarget) {
        tooltip.classList.remove("visible");
        currentTarget = null;
      }
      return;
    }

    // Verificar si el gráfico tiene tooltips habilitados (data-tooltip !== "false")
    const tooltipSetting = svg.getAttribute("data-tooltip");
    if (tooltipSetting === "false") {
      tooltip.classList.remove("visible");
      currentTarget = null;
      return;
    }

    const content = buildTooltipContent(target);
    if (content) {
      currentTarget = target;
      tooltip.innerHTML = content;
      tooltip.classList.add("visible");
      updatePosition(e, tooltip);
    } else {
      tooltip.classList.remove("visible");
      currentTarget = null;
    }
  }, { passive: true });

  document.addEventListener("pointermove", (e) => {
    if (!currentTarget) return;
    updatePosition(e, tooltip);
  }, { passive: true });

  document.addEventListener("pointerout", (e) => {
    if (!currentTarget) return;
    const related = e.relatedTarget;
    if (!related || (currentTarget && !currentTarget.contains(related))) {
      tooltip.classList.remove("visible");
      currentTarget = null;
    }
  }, { passive: true });

  window.addEventListener("scroll", () => {
    if (currentTarget) {
      tooltip.classList.remove("visible");
      currentTarget = null;
    }
  }, { passive: true });
}

/**
 * Actualiza la posición física del tooltip en coordenadas de ventana (fixed).
 * 
 * @param {PointerEvent|MouseEvent} e Evento del cursor.
 * @param {HTMLElement} tooltip Elemento del tooltip.
 */
function updatePosition(e, tooltip) {
  const pad = 12;
  let posX = e.clientX;
  let posY = e.clientY - 12;

  const tooltipRect = tooltip.getBoundingClientRect();
  const width = tooltipRect.width || 120;
  const height = tooltipRect.height || 40;

  // Evitar desborde lateral izquierdo/derecho
  if (posX - width / 2 < pad) {
    posX = pad + width / 2;
  } else if (posX + width / 2 > window.innerWidth - pad) {
    posX = window.innerWidth - pad - width / 2;
  }

  // Evitar desborde superior (desplegar hacia abajo si está muy arriba)
  if (posY - height < pad) {
    tooltip.style.transform = "translate(-50%, 20px)";
  } else {
    tooltip.style.transform = "translate(-50%, -100%)";
  }

  tooltip.style.left = `${posX}px`;
  tooltip.style.top = `${posY}px`;
}
