/**
 * 🧭 floatingScroll.js
 *
 * Barra de scroll flotante (overlay) que no interactúa ni afecta el ancho (`width`) de la página.
 * Reproduce el funcionamiento nativo completo (tamaño proporcional del thumb, arrastre 1:1 sin lag,
 * salto al hacer clic en el carril, auto-ocultación si no hay contenido desplazable y actualización
 * reactiva ante cambios de DOM/tamaño).
 *
 * Compatible nativamente con las clases responsivas `no-desk`, `no-tablet`, `no-phone` (y `querys.js`)
 * así como con todas las clases de color compiladas por JIT (`color1`, `color2`, `color3`... `colorN`).
 *
 * @function floatingScroll
 * @description Inicializa la barra de scroll flotante en la ventana principal o en contenedores específicos.
 *
 * @param {number|string} [width=8] - Ancho de la barra en píxeles (ej. `8` o `"8px"`).
 * @param {string} [color='color2'] - Clase de color compilada (`'color1'`, `'color2'`, `'color3'`, etc.)
 *                                    o valor CSS directo (`'var(--back-color2)'`, `'#ff4400'`).
 * @param {string|string[]} [responsive=''] - Clases responsivas para ocultarla según dispositivo:
 *                                            `'no-desk'`, `'no-tablet'`, `'no-phone'` (combinables, ej. `'no-phone no-tablet'`).
 * @param {Object} [options={}] - Opciones adicionales:
 * @param {number} [options.hoverExpand=4] - Píxeles extra de ancho al hacer hover o arrastrar (default: `4`).
 * @param {number} [options.minThumbHeight=36] - Altura mínima del thumb en píxeles (default: `36`).
 * @param {number} [options.offsetRight=3] - Separación desde el borde derecho en píxeles (default: `3`).
 * @param {number} [options.offsetY=4] - Margen vertical superior/inferior del carril en píxeles (default: `4`).
 * @param {number} [options.opacity=0.65] - Opacidad base del thumb (sube a `1` en hover/drag).
 * @param {boolean} [options.autoHide=false] - Si es `true`, atenúa la barra tras inactividad de scroll.
 *
 * @example
 * // 1. En jsConfig.json (barra de 8px, color2, oculta en teléfonos y tablets)
 * "floatingScroll": [8, "color2", "no-phone no-tablet"]
 *
 * @example
 * // 2. En jsConfig.json con valores por defecto (8px, color2, visible en todos los dispositivos)
 * "floatingScroll": []
 *
 * @example
 * // 3. Instanciación programática en JavaScript
 * const bar = new FloatingScroll(10, "color5", "no-phone");
 *
 * @css .floating-scroll-track - Contenedor flotante lateral (fijo, sin afectar el width del layout)
 * @css .floating-scroll-thumb - Barra desplazable proporcional (usa `currentColor` de `color1..N`)
 * @css html.has-floating-scroll - Clase aplicada a `<html>` para ocultar la barra nativa sin reservar gutter
 *
 * @returns {FloatingScroll}
 */

const RESPONSIVE_CLASSES = [
  "no-desk",
  "no-tablet",
  "no-phone",
  "no-desk-css",
  "no-tablet-css",
  "no-phone-css",
  "no-dark-mode",
  "no-light-mode"
];

/**
 * Resuelve una clase de color del framework (`color1`, `color2`, `back1`, etc.)
 * a su variable CSS correspondiente en `_configTheme.css` como respaldo directo.
 *
 * @param {string} token Clase o valor de color.
 * @returns {{ className: string, cssColor: string }}
 */
function resolveColorToken(token) {
  if (!token || typeof token !== "string") {
    return { className: "color2", cssColor: "var(--back-color2, var(--text-primary, #888))" };
  }

  const trimmed = token.trim();

  // Coincidencia con .color1, .color2, ... .colorN o .back1, .back2, ... .backN
  const matchColor = trimmed.match(/^(?:color|back)(\d+)(?:-hover)?$/i);
  if (matchColor) {
    const idx = parseInt(matchColor[1], 10);
    const varName = idx === 1 ? "--back-color" : `--back-color${idx}`;
    return {
      className: `color${idx}`,
      cssColor: `var(${varName}, #d3d3d3)`
    };
  }

  // Clases semánticas de texto del framework
  const semanticMap = {
    texto: { className: "texto", cssColor: "var(--text-primary, #00124D)" },
    textc: { className: "textc", cssColor: "var(--text-secondary, #FFF9E5)" },
    textw: { className: "textw", cssColor: "#ffffff" },
    textb: { className: "textb", cssColor: "#000000" },
    "color-success": { className: "color-success", cssColor: "var(--success, #00b900)" },
    "color-caution": { className: "color-caution", cssColor: "var(--caution, #ff8c00)" },
    "color-danger": { className: "color-danger", cssColor: "var(--danger, #ff0000)" }
  };

  if (semanticMap[trimmed]) {
    return semanticMap[trimmed];
  }

  // Valor CSS directo (#hex, rgb, var(--...))
  if (
    trimmed.startsWith("#") ||
    trimmed.startsWith("rgb") ||
    trimmed.startsWith("hsl") ||
    trimmed.startsWith("oklch") ||
    trimmed.startsWith("var(")
  ) {
    return { className: "", cssColor: trimmed };
  }

  return { className: trimmed, cssColor: "currentColor" };
}

/**
 * Extrae y separa las clases de color y las clases responsivas (`no-desk`, `no-tablet`, `no-phone`)
 * incluso si el usuario las pasa juntas en un mismo string.
 *
 * @param {string} colorArg Segundo parámetro (`color`).
 * @param {string|string[]} responsiveArg Tercer parámetro (`responsive`).
 * @returns {{ colorInfo: { className: string, cssColor: string }, responsiveList: string[] }}
 */
function parseColorAndResponsive(colorArg, responsiveArg) {
  const tokens = [];

  if (typeof colorArg === "string" && colorArg.trim() !== "") {
    tokens.push(...colorArg.trim().split(/\s+/));
  }

  if (Array.isArray(responsiveArg)) {
    responsiveArg.forEach((item) => {
      if (typeof item === "string" && item.trim() !== "") {
        tokens.push(...item.trim().split(/\s+/));
      }
    });
  } else if (typeof responsiveArg === "string" && responsiveArg.trim() !== "") {
    tokens.push(...responsiveArg.trim().split(/\s+/));
  }

  const responsiveSet = new Set();
  let chosenColorToken = "";

  for (const t of tokens) {
    if (RESPONSIVE_CLASSES.includes(t)) {
      responsiveSet.add(t);
    } else if (!chosenColorToken) {
      chosenColorToken = t;
    }
  }

  return {
    colorInfo: resolveColorToken(chosenColorToken || "color2"),
    responsiveList: Array.from(responsiveSet)
  };
}

/**
 * Clase principal para la barra de scroll flotante de alto rendimiento.
 */
export class FloatingScroll {
  /**
   * @param {number|string} [width=8] Ancho de la barra en px.
   * @param {string} [color='color2'] Clase de color (`color1`, `color2`, `color3`, etc.).
   * @param {string|string[]} [responsive=''] Clases responsivas (`no-desk`, `no-tablet`, `no-phone`).
   * @param {Object} [options={}] Configuración adicional.
   */
  constructor(width = 8, color = "color2", responsive = "", options = {}) {
    // Si alguien pasa (color, width, responsive) invertido accidentalmente, normalizarlo con gracia
    if (typeof width === "string" && isNaN(parseFloat(width)) && typeof color === "number") {
      const temp = width;
      width = color;
      color = temp;
    }

    const parsedWidth = parseFloat(width);
    this.width = Number.isFinite(parsedWidth) && parsedWidth > 0 ? parsedWidth : 8;

    const { colorInfo, responsiveList } = parseColorAndResponsive(color, responsive);
    this.colorInfo = colorInfo;
    this.responsiveClasses = responsiveList;

    const rawOpts = options && typeof options === "object" ? options : {};
    this.options = {
      hoverExpand: rawOpts.hoverExpand !== undefined ? Number(rawOpts.hoverExpand) : 4,
      minThumbHeight: rawOpts.minThumbHeight !== undefined ? Number(rawOpts.minThumbHeight) : 36,
      offsetRight: rawOpts.offsetRight !== undefined ? Number(rawOpts.offsetRight) : 3,
      offsetY: rawOpts.offsetY !== undefined ? Number(rawOpts.offsetY) : 4,
      opacity: rawOpts.opacity !== undefined ? Number(rawOpts.opacity) : 0.65,
      autoHide: Boolean(rawOpts.autoHide),
      zIndex: rawOpts.zIndex !== undefined ? rawOpts.zIndex : 2147483647
    };

    // Si ya existe una instancia global previa en body, destruirla antes de crear la nueva
    if (window.__floatingScrollInstance && window.__floatingScrollInstance !== this) {
      window.__floatingScrollInstance.destroy();
    }
    window.__floatingScrollInstance = this;

    this.isDragging = false;
    this.isHovered = false;
    this.dragStartY = 0;
    this.dragStartScrollTop = 0;
    this.thumbHeight = 0;
    this.thumbTop = 0;
    this.maxScrollTop = 0;
    this.maxThumbTop = 0;
    this.rafId = null;
    this.hideTimer = null;
    this.isDestroyed = false;

    this._initDOM();
    this._bindEvents();
    this.refresh();
  }

  /**
   * Construye la estructura DOM flotante e inyecta la clase en `<html>` para ocultar
   * la barra de scroll nativa sin alterar el `width` del viewport.
   *
   * @private
   */
  _initDOM() {
    document.documentElement.classList.add("has-floating-scroll");

    this.track = document.createElement("div");
    this.track.id = "floating-scroll-track";
    this.track.setAttribute("role", "scrollbar");
    this.track.setAttribute("aria-controls", "body");
    this.track.setAttribute("aria-orientation", "vertical");

    this._applyClassesAndStyles();
    this._ensureThumbElement();

    document.body.appendChild(this.track);
  }

  /**
   * Garantiza que el elemento hijo `.floating-scroll-thumb` exista dentro del track
   * (incluso después de que `querys.js` vacíe y restaure `innerHTML` en cambios de breakpoint).
   *
   * @private
   */
  _ensureThumbElement() {
    if (!this.track) return;

    let thumb = this.track.querySelector(".floating-scroll-thumb");
    if (!thumb) {
      thumb = document.createElement("div");
      thumb.className = "floating-scroll-thumb";
      this.track.appendChild(thumb);
    }

    this.thumb = thumb;
    this._styleThumb();
  }

  /**
   * Aplica las clases de color (`color1`, `color2`, etc.), las clases responsivas
   * (`no-desk`, `no-tablet`, `no-phone`) y las variables de dimensión al track.
   *
   * @private
   */
  _applyClassesAndStyles() {
    if (!this.track) return;

    const classes = ["floating-scroll-track"];
    if (this.colorInfo.className) {
      classes.push(this.colorInfo.className);
    }
    for (const rc of this.responsiveClasses) {
      classes.push(rc);
    }

    this.track.className = classes.join(" ");

    const hitAreaWidth = Math.max(14, this.width + this.options.hoverExpand + this.options.offsetRight * 2);
    const s = this.track.style;
    s.position = "fixed";
    s.top = `${this.options.offsetY}px`;
    s.right = "0px";
    s.bottom = `${this.options.offsetY}px`;
    s.width = `${hitAreaWidth}px`;
    s.zIndex = String(this.options.zIndex);
    s.pointerEvents = "auto";
    s.userSelect = "none";
    s.webkitUserSelect = "none";
    s.background = "transparent";

    if (this.colorInfo.cssColor && this.colorInfo.cssColor !== "currentColor") {
      s.color = this.colorInfo.cssColor;
    } else {
      s.removeProperty("color");
    }
  }

  /**
   * Aplica los estilos visuales del thumb flotante.
   *
   * @private
   */
  _styleThumb() {
    if (!this.thumb) return;
    const activeWidth = (this.isHovered || this.isDragging)
      ? this.width + this.options.hoverExpand
      : this.width;

    const ts = this.thumb.style;
    ts.position = "absolute";
    ts.top = "0px";
    ts.right = `${this.options.offsetRight}px`;
    ts.width = `${activeWidth}px`;
    ts.height = `${Math.max(this.options.minThumbHeight, this.thumbHeight)}px`;
    ts.borderRadius = "999px";
    ts.backgroundColor = "currentColor";
    ts.opacity = (this.isHovered || this.isDragging) ? "1" : String(this.options.opacity);
    ts.transform = `translate3d(0, ${this.thumbTop}px, 0)`;
    ts.transition = this.isDragging
      ? "width 0.15s ease, opacity 0.15s ease"
      : "width 0.15s ease, opacity 0.2s ease";
    ts.cursor = this.isDragging ? "grabbing" : "grab";
    ts.willChange = "transform, width, opacity";
  }

  /**
   * Vincula los eventos de scroll, resize, arrastre 1:1, clic en el carril y reconexión responsiva.
   * Todos los eventos de puntero se delegan en `this.track` para ser inmunes al vaciado de `innerHTML` de `querys.js`.
   *
   * @private
   */
  _bindEvents() {
    // 1. Scroll pasivo sincronizado con requestAnimationFrame
    this._onScroll = () => {
      if (this.isDestroyed) return;
      if (this.options.autoHide && this.thumb && !this.isDragging && !this.isHovered) {
        this.thumb.style.opacity = String(this.options.opacity);
        clearTimeout(this.hideTimer);
        this.hideTimer = setTimeout(() => {
          if (!this.isDragging && !this.isHovered && this.thumb) {
            this.thumb.style.opacity = "0";
          }
        }, 1200);
      }
      this._scheduleUpdate();
    };
    window.addEventListener("scroll", this._onScroll, { passive: true });

    // 2. Resize de ventana y cambios de altura del DOM (ResizeObserver)
    this._onResize = () => {
      if (this.isDestroyed) return;
      this._scheduleUpdate();
    };
    window.addEventListener("resize", this._onResize, { passive: true });

    if (typeof ResizeObserver !== "undefined") {
      this._resizeObserver = new ResizeObserver(this._onResize);
      this._resizeObserver.observe(document.documentElement);
      if (document.body) {
        this._resizeObserver.observe(document.body);
      }
    }

    // 3. Hover sobre el carril/thumb para expandir suavemente el ancho de la barra
    this.track.addEventListener("mouseenter", () => {
      this.isHovered = true;
      clearTimeout(this.hideTimer);
      this._styleThumb();
    });

    this.track.addEventListener("mouseleave", () => {
      this.isHovered = false;
      if (!this.isDragging) {
        this._styleThumb();
      }
    });

    // 4. Inicio de arrastre (en el thumb) o salto directo (clic en el carril fuera del thumb)
    this._onPointerDown = (e) => {
      if (e.button !== undefined && e.button !== 0) return;
      this._ensureThumbElement();

      const clickedThumb = e.target.closest(".floating-scroll-thumb");

      if (!clickedThumb) {
        // Clic directo en el carril (track): saltar proporcionalmente a esa posición y permitir seguir arrastrando
        const trackRect = this.track.getBoundingClientRect();
        const clickY = e.clientY - trackRect.top;
        const targetThumbTop = Math.max(
          0,
          Math.min(this.maxThumbTop, clickY - this.thumbHeight * 0.5)
        );
        const ratio = this.maxThumbTop > 0 ? targetThumbTop / this.maxThumbTop : 0;
        window.scrollTo({
          top: ratio * this.maxScrollTop,
          behavior: "smooth"
        });
        return;
      }

      e.preventDefault();
      e.stopPropagation();

      this.isDragging = true;
      this.dragStartY = e.clientY;
      this.dragStartScrollTop = window.scrollY || document.documentElement.scrollTop || 0;

      // Desactivar scroll-behavior: smooth durante el drag para respuesta instantánea 1:1
      this._prevHtmlScrollBehavior = document.documentElement.style.scrollBehavior;
      this._prevBodyScrollBehavior = document.body.style.scrollBehavior;
      document.documentElement.style.scrollBehavior = "auto";
      document.body.style.scrollBehavior = "auto";
      document.body.classList.add("floating-scroll-dragging");

      if (this.track.setPointerCapture && e.pointerId !== undefined) {
        try {
          this.track.setPointerCapture(e.pointerId);
        } catch (_) {}
      }

      this._styleThumb();
    };

    this._onPointerMove = (e) => {
      if (!this.isDragging) return;
      e.preventDefault();

      const deltaY = e.clientY - this.dragStartY;
      const scrollRatio = this.maxThumbTop > 0 ? this.maxScrollTop / this.maxThumbTop : 0;
      const nextScrollTop = Math.max(
        0,
        Math.min(this.maxScrollTop, this.dragStartScrollTop + deltaY * scrollRatio)
      );

      window.scrollTo(0, nextScrollTop);
      this._updateMetricsAndPosition();
    };

    this._onPointerUp = (e) => {
      if (!this.isDragging) return;
      this.isDragging = false;

      document.documentElement.style.scrollBehavior = this._prevHtmlScrollBehavior || "";
      document.body.style.scrollBehavior = this._prevBodyScrollBehavior || "";
      document.body.classList.remove("floating-scroll-dragging");

      if (this.track && this.track.releasePointerCapture && e && e.pointerId !== undefined) {
        try {
          this.track.releasePointerCapture(e.pointerId);
        } catch (_) {}
      }

      this._styleThumb();
    };

    this.track.addEventListener("pointerdown", this._onPointerDown);
    window.addEventListener("pointermove", this._onPointerMove, { passive: false });
    window.addEventListener("pointerup", this._onPointerUp);
    window.addEventListener("pointercancel", this._onPointerUp);

    // 5. Observar si querys.js (no-desk, no-tablet, no-phone) restaura el innerHTML del track al cambiar de breakpoint
    if (typeof MutationObserver !== "undefined") {
      this._trackObserver = new MutationObserver(() => {
        if (this.isDestroyed) return;
        const isHiddenByResponsive =
          this.track.style.display === "none" ||
          this.track.getAttribute("aria-hidden") === "true";

        if (!isHiddenByResponsive && !this.track.querySelector(".floating-scroll-thumb")) {
          this._ensureThumbElement();
          this._updateMetricsAndPosition();
        } else if (!isHiddenByResponsive) {
          this.thumb = this.track.querySelector(".floating-scroll-thumb");
          this._updateMetricsAndPosition();
        }
      });
      this._trackObserver.observe(this.track, { childList: true, attributes: true, attributeFilter: ["style", "aria-hidden"] });
    }
  }

  /**
   * Agenda un único cálculo por cuadro de animación (`requestAnimationFrame`).
   *
   * @private
   */
  _scheduleUpdate() {
    if (this.rafId) return;
    this.rafId = requestAnimationFrame(() => {
      this.rafId = null;
      this._updateMetricsAndPosition();
    });
  }

  /**
   * Calcula las dimensiones proporcionales del documento y posiciona el thumb con aceleración GPU (`translate3d`).
   *
   * @private
   */
  _updateMetricsAndPosition() {
    if (this.isDestroyed || !this.track) return;

    // Si está oculto por una clase responsiva (no-phone, no-tablet, no-desk), no gastar recursos
    if (
      this.track.style.display === "none" ||
      this.track.getAttribute("aria-hidden") === "true" ||
      window.getComputedStyle(this.track).display === "none"
    ) {
      return;
    }

    this._ensureThumbElement();
    if (!this.thumb) return;

    const docEl = document.documentElement;
    const body = document.body;

    const viewportHeight = window.innerHeight || docEl.clientHeight;
    const scrollHeight = Math.max(
      docEl.scrollHeight,
      body ? body.scrollHeight : 0,
      docEl.offsetHeight,
      body ? body.offsetHeight : 0
    );

    this.maxScrollTop = Math.max(0, scrollHeight - viewportHeight);

    // Si la página no necesita scroll vertical, ocultar el thumb limpiamente
    if (this.maxScrollTop <= 1) {
      this.track.style.visibility = "hidden";
      this.track.style.pointerEvents = "none";
      return;
    }

    this.track.style.visibility = "visible";
    this.track.style.pointerEvents = "auto";

    const trackHeight = Math.max(1, viewportHeight - this.options.offsetY * 2);
    const proportionalHeight = (viewportHeight / scrollHeight) * trackHeight;
    this.thumbHeight = Math.max(
      this.options.minThumbHeight,
      Math.min(trackHeight, Math.round(proportionalHeight))
    );

    this.maxThumbTop = Math.max(0, trackHeight - this.thumbHeight);

    const currentScrollTop = Math.max(
      0,
      Math.min(this.maxScrollTop, window.scrollY || docEl.scrollTop || 0)
    );
    const scrollProgress = this.maxScrollTop > 0 ? currentScrollTop / this.maxScrollTop : 0;
    this.thumbTop = Math.round(scrollProgress * this.maxThumbTop);

    this.thumb.style.height = `${this.thumbHeight}px`;
    this.thumb.style.transform = `translate3d(0, ${this.thumbTop}px, 0)`;

    const progressPct = Math.round(scrollProgress * 100);
    this.track.setAttribute("aria-valuenow", String(progressPct));
  }

  /**
   * Cambia dinámicamente el ancho de la barra en píxeles.
   *
   * @param {number} width Nuevo ancho en px.
   * @returns {this}
   */
  setWidth(width) {
    const parsed = parseFloat(width);
    if (Number.isFinite(parsed) && parsed > 0) {
      this.width = parsed;
      this._applyClassesAndStyles();
      this._styleThumb();
    }
    return this;
  }

  /**
   * Cambia dinámicamente la clase de color (`color1`, `color2`, `color3`, etc.).
   *
   * @param {string} color Nueva clase de color o valor CSS.
   * @returns {this}
   */
  setColor(color) {
    this.colorInfo = resolveColorToken(color);
    this._applyClassesAndStyles();
    this._styleThumb();
    return this;
  }

  /**
   * Actualiza las clases responsivas (`no-desk`, `no-tablet`, `no-phone`).
   *
   * @param {string|string[]} responsive Clases responsivas separadas por espacio o array.
   * @returns {this}
   */
  setResponsive(responsive) {
    const { responsiveList } = parseColorAndResponsive("", responsive);
    this.responsiveClasses = responsiveList;
    this._applyClassesAndStyles();
    this.refresh();
    return this;
  }

  /**
   * Fuerza el recálculo inmediato de altura y posición de la barra.
   *
   * @returns {this}
   */
  refresh() {
    this._updateMetricsAndPosition();
    return this;
  }

  /**
   * Elimina la barra de scroll flotante, restaura la barra nativa y limpia todos los listeners.
   */
  destroy() {
    this.isDestroyed = true;
    clearTimeout(this.hideTimer);

    if (this.rafId) {
      cancelAnimationFrame(this.rafId);
      this.rafId = null;
    }

    window.removeEventListener("scroll", this._onScroll);
    window.removeEventListener("resize", this._onResize);
    window.removeEventListener("pointermove", this._onPointerMove);
    window.removeEventListener("pointerup", this._onPointerUp);
    window.removeEventListener("pointercancel", this._onPointerUp);

    if (this._resizeObserver) {
      this._resizeObserver.disconnect();
      this._resizeObserver = null;
    }

    if (this._trackObserver) {
      this._trackObserver.disconnect();
      this._trackObserver = null;
    }

    if (this.track && this.track.parentNode) {
      this.track.parentNode.removeChild(this.track);
    }

    document.documentElement.classList.remove("has-floating-scroll");
    document.body.classList.remove("floating-scroll-dragging");

    if (window.__floatingScrollInstance === this) {
      delete window.__floatingScrollInstance;
    }
  }
}

/**
 * Inicializa la barra de scroll flotante con los parámetros indicados.
 *
 * @param {number|string} [width=8] - Ancho de la barra en px (1er parámetro).
 * @param {string} [color='color2'] - Clase de color compilada `color1`, `color2`, `color3`... (2do parámetro).
 * @param {string|string[]} [responsive=''] - Clases `no-desk`, `no-tablet`, `no-phone` (3er parámetro).
 * @param {Object} [options={}] - Opciones adicionales (4to parámetro).
 * @returns {FloatingScroll}
 */
export function floatingScroll(width = 8, color = "color2", responsive = "", options = {}) {
  return new FloatingScroll(width, color, responsive, options);
}

// Exposición global en window
if (typeof window !== "undefined") {
  window.FloatingScroll = FloatingScroll;
  window.floatingScroll = floatingScroll;
}
