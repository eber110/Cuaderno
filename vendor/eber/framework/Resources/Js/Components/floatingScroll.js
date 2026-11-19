/**
 * 🧭 floatingScroll.js
 *
 * Barra de scroll flotante (overlay) que no interactúa ni afecta el ancho (`width`) de la página
 * ni de contenedores HTML internos (popovers, modales, selects, listas y textareas).
 * Reproduce el funcionamiento nativo completo (tamaño proporcional del thumb, arrastre 1:1 sin lag,
 * salto al hacer clic en el carril, auto-ocultación si no hay contenido desplazable y actualización
 * reactiva ante cambios de DOM/tamaño).
 *
 * Compatible nativamente con las clases responsivas `no-desk`, `no-tablet`, `no-phone` (y `querie.js`)
 * así como con todas las clases de color compiladas por JIT (`color1`, `color2`, `color3`... `colorN`).
 *
 * @function floatingScroll
 * @description Inicializa la barra de scroll flotante en la ventana principal o en contenedores específicos.
 *
 * @param {number|string} [width=8] - Ancho de la barra en px (ej. `8` o `"8px"`).
 * @param {string} [color='color2'] - Clase de color (`'color1'`, `'color2'`, ...) o valor CSS directo.
 * @param {string|string[]} [responsive=''] - Clases responsivas (`'no-desk'`, `'no-tablet'`, `'no-phone'`).
 * @param {Object} [options={}] - Opciones adicionales:
 * @param {HTMLElement|null} [options.container=null] - Elemento HTML contenedor para modo local.
 * @param {boolean} [options.hidden=false] - Oculta visualmente el track/thumb manteniendo oculta la scrollbar nativa.
 * @param {number} [options.hoverExpand=4] - Píxeles extra de ancho al hacer hover o arrastrar.
 * @param {number} [options.minThumbHeight=36] - Altura mínima del thumb en píxeles.
 * @param {number} [options.offsetRight=3] - Separación desde el borde derecho en píxeles.
 * @param {number} [options.offsetY=4] - Margen vertical superior/inferior del carril en píxeles.
 * @param {number} [options.opacity=0.65] - Opacidad base del thumb.
 * @param {boolean} [options.autoHide=false] - Atenúa la barra tras inactividad de scroll.
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

  const matchColor = trimmed.match(/^(?:color|back)(\d+)(?:-hover)?$/i);
  if (matchColor) {
    const idx = parseInt(matchColor[1], 10);
    const varName = idx === 1 ? "--back-color" : `--back-color${idx}`;
    return {
      className: `color${idx}`,
      cssColor: `var(${varName}, #d3d3d3)`
    };
  }

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

  if (
    trimmed.startsWith("#") ||
    trimmed.startsWith("rgb") ||
    trimmed.startsWith("hsl") ||
    trimmed.startsWith("oklch") ||
    trimmed.startsWith("oklab") ||
    trimmed.startsWith("color-mix(") ||
    trimmed.startsWith("var(")
  ) {
    return { className: "", cssColor: trimmed };
  }

  return { className: trimmed, cssColor: "currentColor" };
}

function isResponsiveOrThemeClass(token) {
  return RESPONSIVE_CLASSES.includes(token) || /^no-[a-z0-9_-]+(-css)?$/.test(token);
}

/**
 * Extrae y separa las clases de color y las clases responsivas.
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
    if (isResponsiveOrThemeClass(t)) {
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
   * @param {number|string|HTMLElement|Object} [width=8] Ancho en px, o contenedor/opciones en uso abreviado.
   * @param {string|Object} [color='color2'] Clase de color o valor CSS.
   * @param {string|string[]} [responsive=''] Clases responsivas.
   * @param {Object} [options={}] Configuración adicional (`container`, `hidden`, etc.).
   */
  constructor(width = 8, color = "color2", responsive = "", options = {}) {
    if (typeof HTMLElement !== "undefined" && width instanceof HTMLElement) {
      const containerOpts = (typeof color === "object" && color !== null) ? color : {};
      options = Object.assign({}, containerOpts, { container: width });
      width = options.width ?? 6;
      color = options.color ?? "color2";
      responsive = options.responsive ?? "";
    } else if (typeof width === "object" && width !== null) {
      options = width;
      width = options.width ?? 8;
      color = options.color ?? "color2";
      responsive = options.responsive ?? "";
    } else if (typeof width === "string" && isNaN(parseFloat(width)) && typeof color === "number") {
      const temp = width;
      width = color;
      color = temp;
    }

    const rawOpts = options && typeof options === "object" ? options : {};
    this.container = (typeof HTMLElement !== "undefined" && rawOpts.container instanceof HTMLElement)
      ? rawOpts.container
      : null;
    this.isContainerMode = !!this.container;

    const parsedWidth = parseFloat(width);
    this.width = Number.isFinite(parsedWidth) && parsedWidth > 0 ? parsedWidth : (this.isContainerMode ? 6 : 8);

    const { colorInfo, responsiveList } = parseColorAndResponsive(color, responsive);
    this.colorInfo = colorInfo;
    this.responsiveClasses = responsiveList;

    this.doc = this.container
      ? (this.container.ownerDocument || (typeof document !== "undefined" ? document : null))
      : (rawOpts.targetDocument || (rawOpts.targetWindow ? rawOpts.targetWindow.document : (typeof document !== "undefined" ? document : null)));
    this.win = rawOpts.targetWindow || (this.doc && this.doc.defaultView ? this.doc.defaultView : (typeof window !== "undefined" ? window : null));

    this.options = {
      hoverExpand: rawOpts.hoverExpand !== undefined ? Number(rawOpts.hoverExpand) : (this.isContainerMode ? 2 : 4),
      minThumbHeight: rawOpts.minThumbHeight !== undefined ? Number(rawOpts.minThumbHeight) : (this.isContainerMode ? 24 : 36),
      offsetRight: rawOpts.offsetRight !== undefined ? Number(rawOpts.offsetRight) : (this.isContainerMode ? 2 : 3),
      offsetY: rawOpts.offsetY !== undefined ? Number(rawOpts.offsetY) : 4,
      opacity: rawOpts.opacity !== undefined ? Number(rawOpts.opacity) : 0.65,
      autoHide: Boolean(rawOpts.autoHide),
      zIndex: rawOpts.zIndex !== undefined ? rawOpts.zIndex : (this.isContainerMode ? 1000600 : 2147483647)
    };

    this.hidden = Boolean(rawOpts.hidden);

    if (this.isContainerMode) {
      if (this.container.__floatingScrollInstance && this.container.__floatingScrollInstance !== this) {
        this.container.__floatingScrollInstance.destroy();
      }
      this.container.__floatingScrollInstance = this;
    } else {
      if (this.win && this.win.__floatingScrollInstance && this.win.__floatingScrollInstance !== this) {
        this.win.__floatingScrollInstance.destroy();
      }
      if (this.win) {
        this.win.__floatingScrollInstance = this;
      }
    }

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
   * Adjunta o actualiza una instancia de FloatingScroll sobre un contenedor HTML.
   *
   * @param {HTMLElement} containerEl Contenedor con scroll vertical.
   * @param {Object} [options={}] Opciones (`width`, `color`, `responsive`, `hidden`, `hoverExpand`, etc.).
   * @returns {FloatingScroll|null}
   */
  static attach(containerEl, options = {}) {
    if (!containerEl || !(containerEl instanceof HTMLElement)) {
      return null;
    }
    const existing = containerEl.__floatingScrollInstance;
    if (existing && !existing.isDestroyed) {
      if (options.width !== undefined) existing.setWidth(options.width);
      if (options.color !== undefined) existing.setColor(options.color);
      if (options.responsive !== undefined) existing.setResponsive(options.responsive);
      if (options.hidden !== undefined) existing.setHidden(options.hidden);
      if (options.zIndex !== undefined) {
        existing.options.zIndex = options.zIndex;
        if (existing.track) existing.track.style.zIndex = String(options.zIndex);
      }
      existing.refresh();
      return existing;
    }

    const width = options.width ?? 6;
    const color = options.color ?? "color2";
    const responsive = options.responsive ?? "";
    return new FloatingScroll(width, color, responsive, Object.assign({}, options, { container: containerEl }));
  }

  /**
   * Desvincula y destruye la instancia de FloatingScroll asociada a un contenedor HTML.
   *
   * @param {HTMLElement} containerEl Contenedor objetivo.
   * @returns {void}
   */
  static detach(containerEl) {
    if (containerEl && containerEl.__floatingScrollInstance) {
      containerEl.__floatingScrollInstance.destroy();
    }
  }

  /**
   * Asegura las reglas CSS base para ocultar barras nativas sin reservar espacio.
   *
   * @private
   */
  _ensureBaseStyles() {
    if (!this.doc || !this.doc.documentElement) return;
    if (this.doc.getElementById("floating-scroll-base-styles")) return;

    const style = this.doc.createElement("style");
    style.id = "floating-scroll-base-styles";
    style.textContent = `
      html.has-floating-scroll,
      html.has-floating-scroll body,
      .has-floating-scroll-box {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
        scrollbar-gutter: auto !important;
      }
      html.has-floating-scroll::-webkit-scrollbar,
      html.has-floating-scroll body::-webkit-scrollbar,
      .has-floating-scroll-box::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        background: transparent !important;
      }
      .floating-scroll-track {
        position: fixed;
        right: 0;
        z-index: 2147483647;
        background: transparent;
        user-select: none;
        -webkit-user-select: none;
        touch-action: none;
      }
      .floating-scroll-track.is-container-scroll {
        right: auto;
        bottom: auto;
      }
      .floating-scroll-thumb {
        background-color: currentColor;
        border-radius: 999px;
        touch-action: none;
      }
    `;
    (this.doc.head || this.doc.documentElement).appendChild(style);
  }

  /**
   * Construye la estructura DOM flotante e inyecta la clase correspondiente para ocultar
   * la barra nativa sin alterar el `width` del viewport o contenedor.
   *
   * @private
   */
  _initDOM() {
    if (!this.doc || !this.doc.documentElement) return;
    this._ensureBaseStyles();

    if (this.isContainerMode) {
      this.container.classList.add("has-floating-scroll-box");
    } else {
      this.doc.documentElement.classList.add("has-floating-scroll");
    }

    this.track = this.doc.createElement("div");
    if (!this.isContainerMode) {
      this.track.id = "floating-scroll-track";
      this.track.setAttribute("aria-controls", "body");
    }
    this.track.setAttribute("role", "scrollbar");
    this.track.setAttribute("aria-orientation", "vertical");

    this._applyClassesAndStyles();
    this._ensureThumbElement();

    if (this.doc.body) {
      this.doc.body.appendChild(this.track);
    } else {
      this.doc.documentElement.appendChild(this.track);
    }
  }

  /**
   * Garantiza que el elemento hijo `.floating-scroll-thumb` exista dentro del track.
   *
   * @private
   */
  _ensureThumbElement() {
    if (!this.track) return;

    let thumb = this.track.querySelector(".floating-scroll-thumb");
    if (!thumb) {
      thumb = this.doc.createElement("div");
      thumb.className = "floating-scroll-thumb";
      this.track.appendChild(thumb);
    }

    this.thumb = thumb;
    this._styleThumb();
  }

  /**
   * Aplica las clases de color, responsivas y dimensiones base al track.
   *
   * @private
   */
  _applyClassesAndStyles() {
    if (!this.track) return;

    const classes = ["floating-scroll-track"];
    if (this.isContainerMode) {
      classes.push("is-container-scroll");
    }
    if (this.colorInfo.className) {
      classes.push(this.colorInfo.className);
    }
    for (const rc of this.responsiveClasses) {
      classes.push(rc);
    }

    this.track.className = classes.join(" ");

    const hitAreaWidth = Math.max(12, this.width + this.options.hoverExpand + this.options.offsetRight * 2);
    const s = this.track.style;
    s.position = "fixed";
    s.width = `${hitAreaWidth}px`;
    s.zIndex = String(this.options.zIndex);
    s.pointerEvents = "auto";
    s.userSelect = "none";
    s.webkitUserSelect = "none";
    s.background = "transparent";

    if (this.isContainerMode) {
      s.right = "auto";
      s.bottom = "auto";
    } else {
      s.top = `${this.options.offsetY}px`;
      s.right = "0px";
      s.bottom = `${this.options.offsetY}px`;
      s.left = "auto";
      s.height = "auto";
    }

    if (this.hidden) {
      s.display = "none";
    } else {
      s.removeProperty("display");
    }

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
   * Vincula los eventos de scroll, resize, mutación de contenido y arrastre 1:1.
   *
   * @private
   */
  _bindEvents() {
    if (!this.win || !this.doc) return;

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

    this._onResize = () => {
      if (this.isDestroyed) return;
      this._scheduleUpdate();
    };

    if (this.isContainerMode) {
      this.container.addEventListener("scroll", this._onScroll, { passive: true });
      if (this.container.tagName === "TEXTAREA") {
        this._onTextareaInput = () => this._scheduleUpdate();
        this.container.addEventListener("input", this._onTextareaInput, { passive: true });
      }
      this._onParentScroll = () => {
        if (!this.isDestroyed) this._scheduleUpdate();
      };
      this.win.addEventListener("scroll", this._onParentScroll, { passive: true, capture: true });
    } else {
      this.win.addEventListener("scroll", this._onScroll, { passive: true });
    }

    this.win.addEventListener("resize", this._onResize, { passive: true });

    if (typeof ResizeObserver !== "undefined") {
      this._resizeObserver = new ResizeObserver(this._onResize);
      if (this.isContainerMode) {
        this._resizeObserver.observe(this.container);
        Array.from(this.container.children).forEach((child) => {
          this._resizeObserver.observe(child);
        });
      } else {
        this._resizeObserver.observe(this.doc.documentElement);
        if (this.doc.body) {
          this._resizeObserver.observe(this.doc.body);
        }
      }
    }

    if (this.isContainerMode && typeof MutationObserver !== "undefined" && this.container.tagName !== "TEXTAREA") {
      this._containerMutationObserver = new MutationObserver(() => {
        if (this.isDestroyed) return;
        if (this._resizeObserver) {
          Array.from(this.container.children).forEach((child) => {
            try { this._resizeObserver.observe(child); } catch (_) {}
          });
        }
        this._scheduleUpdate();
      });
      this._containerMutationObserver.observe(this.container, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ["style", "class", "hidden"]
      });
    }

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

    this._onPointerDown = (e) => {
      if (e.button !== undefined && e.button !== 0) return;
      this._ensureThumbElement();

      const clickedThumb = e.target.closest(".floating-scroll-thumb");

      if (!clickedThumb) {
        const trackRect = this.track.getBoundingClientRect();
        const clickY = e.clientY - trackRect.top;
        const targetThumbTop = Math.max(
          0,
          Math.min(this.maxThumbTop, clickY - this.thumbHeight * 0.5)
        );
        const ratio = this.maxThumbTop > 0 ? targetThumbTop / this.maxThumbTop : 0;
        if (this.isContainerMode) {
          if (typeof this.container.scrollTo === "function") {
            this.container.scrollTo({ top: ratio * this.maxScrollTop, behavior: "smooth" });
          } else {
            this.container.scrollTop = ratio * this.maxScrollTop;
          }
        } else {
          this.win.scrollTo({ top: ratio * this.maxScrollTop, behavior: "smooth" });
        }
        return;
      }

      e.preventDefault();
      e.stopPropagation();

      this.isDragging = true;
      this.dragStartY = e.clientY;
      this.dragStartScrollTop = this.isContainerMode
        ? (this.container.scrollTop || 0)
        : (this.win.scrollY || this.doc.documentElement.scrollTop || 0);

      if (this.isContainerMode) {
        this._prevContainerScrollBehavior = this.container.style.scrollBehavior;
        this.container.style.scrollBehavior = "auto";
      } else {
        this._prevHtmlScrollBehavior = this.doc.documentElement.style.scrollBehavior;
        this._prevBodyScrollBehavior = this.doc.body ? this.doc.body.style.scrollBehavior : "";
        this.doc.documentElement.style.scrollBehavior = "auto";
        if (this.doc.body) {
          this.doc.body.style.scrollBehavior = "auto";
          this.doc.body.classList.add("floating-scroll-dragging");
        }
      }

      if (this.track.setPointerCapture && e.pointerId !== undefined) {
        try {
          this.track.setPointerCapture(e.pointerId);
        } catch (_) {}
      }

      if (typeof window !== "undefined" && window !== this.win) {
        window.addEventListener("pointermove", this._onPointerMove, { passive: false });
        window.addEventListener("pointerup", this._onPointerUp);
        window.addEventListener("pointercancel", this._onPointerUp);
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

      if (this.isContainerMode) {
        this.container.scrollTop = nextScrollTop;
      } else {
        this.win.scrollTo(0, nextScrollTop);
      }
      this._updateMetricsAndPosition();
    };

    this._onPointerUp = (e) => {
      if (!this.isDragging) return;
      this.isDragging = false;

      if (this.isContainerMode) {
        this.container.style.scrollBehavior = this._prevContainerScrollBehavior || "";
      } else {
        this.doc.documentElement.style.scrollBehavior = this._prevHtmlScrollBehavior || "";
        if (this.doc.body) {
          this.doc.body.style.scrollBehavior = this._prevBodyScrollBehavior || "";
          this.doc.body.classList.remove("floating-scroll-dragging");
        }
      }

      if (this.track && this.track.releasePointerCapture && e && e.pointerId !== undefined) {
        try {
          this.track.releasePointerCapture(e.pointerId);
        } catch (_) {}
      }

      if (typeof window !== "undefined" && window !== this.win) {
        window.removeEventListener("pointermove", this._onPointerMove);
        window.removeEventListener("pointerup", this._onPointerUp);
        window.removeEventListener("pointercancel", this._onPointerUp);
      }

      this._styleThumb();
    };

    this.track.addEventListener("pointerdown", this._onPointerDown);
    this.win.addEventListener("pointermove", this._onPointerMove, { passive: false });
    this.win.addEventListener("pointerup", this._onPointerUp);
    this.win.addEventListener("pointercancel", this._onPointerUp);

    if (typeof MutationObserver !== "undefined") {
      this._trackObserver = new MutationObserver(() => {
        if (this.isDestroyed) return;
        const isHiddenByResponsive =
          this.hidden ||
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
    const reqAnim = (this.win && this.win.requestAnimationFrame) ? this.win.requestAnimationFrame : requestAnimationFrame;
    this.rafId = reqAnim(() => {
      this.rafId = null;
      this._updateMetricsAndPosition();
    });
  }

  /**
   * Calcula las dimensiones proporcionales y posiciona el track/thumb con aceleración GPU.
   *
   * @private
   */
  _updateMetricsAndPosition() {
    if (this.isDestroyed || !this.track || !this.win || !this.doc) return;

    if (this.isContainerMode && !this.container.isConnected) {
      this.destroy();
      return;
    }

    if (
      this.hidden ||
      this.track.style.display === "none" ||
      this.track.getAttribute("aria-hidden") === "true" ||
      (this.win.getComputedStyle && this.win.getComputedStyle(this.track).display === "none")
    ) {
      return;
    }

    this._ensureThumbElement();
    if (!this.thumb) return;

    let viewportHeight = 0;
    let scrollHeight = 0;
    let currentScrollTop = 0;

    if (this.isContainerMode) {
      const rect = this.container.getBoundingClientRect();
      if (rect.width <= 0 || rect.height <= 0) {
        this.track.style.visibility = "hidden";
        this.track.style.pointerEvents = "none";
        return;
      }

      const cs = this.win.getComputedStyle ? this.win.getComputedStyle(this.container) : null;
      if (cs && (cs.display === "none" || cs.visibility === "hidden")) {
        this.track.style.visibility = "hidden";
        this.track.style.pointerEvents = "none";
        return;
      }

      const hitAreaWidth = Math.max(12, this.width + this.options.hoverExpand + this.options.offsetRight * 2);
      const safeOffsetY = Math.min(this.options.offsetY, Math.floor(rect.height / 6));
      const trackBoxHeight = Math.max(1, Math.round(rect.height - safeOffsetY * 2));

      this.track.style.top = `${Math.round(rect.top + safeOffsetY)}px`;
      this.track.style.left = `${Math.round(rect.right - hitAreaWidth)}px`;
      this.track.style.height = `${trackBoxHeight}px`;

      viewportHeight = this.container.clientHeight || rect.height;
      scrollHeight = this.container.scrollHeight;
      this.maxScrollTop = Math.max(0, scrollHeight - viewportHeight);

      if (this.maxScrollTop <= 1) {
        this.track.style.visibility = "hidden";
        this.track.style.pointerEvents = "none";
        return;
      }

      this.track.style.visibility = "visible";
      this.track.style.pointerEvents = "auto";

      const proportionalHeight = (viewportHeight / Math.max(1, scrollHeight)) * trackBoxHeight;
      this.thumbHeight = Math.max(
        Math.min(this.options.minThumbHeight, trackBoxHeight),
        Math.min(trackBoxHeight, Math.round(proportionalHeight))
      );
      this.maxThumbTop = Math.max(0, trackBoxHeight - this.thumbHeight);
      currentScrollTop = Math.max(0, Math.min(this.maxScrollTop, this.container.scrollTop || 0));
    } else {
      const docEl = this.doc.documentElement;
      const body = this.doc.body;

      viewportHeight = this.win.innerHeight || docEl.clientHeight;
      scrollHeight = Math.max(
        docEl.scrollHeight,
        body ? body.scrollHeight : 0,
        docEl.offsetHeight,
        body ? body.offsetHeight : 0
      );

      this.maxScrollTop = Math.max(0, scrollHeight - viewportHeight);

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
      currentScrollTop = Math.max(0, Math.min(this.maxScrollTop, this.win.scrollY || docEl.scrollTop || 0));
    }

    const scrollProgress = this.maxScrollTop > 0 ? currentScrollTop / this.maxScrollTop : 0;
    this.thumbTop = Math.round(scrollProgress * this.maxThumbTop);

    this.thumb.style.height = `${this.thumbHeight}px`;
    this.thumb.style.transform = `translate3d(0, ${this.thumbTop}px, 0)`;

    const progressPct = Math.round(scrollProgress * 100);
    this.track.setAttribute("aria-valuenow", String(progressPct));
  }

  /**
   * Oculta o muestra visualmente el carril flotante manteniendo oculta la barra nativa del navegador.
   *
   * @param {boolean} hidden True para ocultar visualmente la barra flotante.
   * @returns {this}
   */
  setHidden(hidden) {
    this.hidden = Boolean(hidden);
    if (this.track) {
      if (this.hidden) {
        this.track.style.display = "none";
      } else {
        this.track.style.removeProperty("display");
      }
    }
    if (!this.hidden) {
      this.refresh();
    }
    return this;
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
   * Cambia dinámicamente la clase o color CSS (`color1`, `color2`, `#hex`, `var(--...)`).
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
   * Elimina la barra de scroll flotante, restaura la barra nativa (si aplica) y limpia todos los listeners.
   */
  destroy() {
    if (this.isDestroyed) return;
    this.isDestroyed = true;
    clearTimeout(this.hideTimer);

    if (this.rafId) {
      const cancelAnim = (this.win && this.win.cancelAnimationFrame) ? this.win.cancelAnimationFrame : cancelAnimationFrame;
      cancelAnim(this.rafId);
      this.rafId = null;
    }

    if (this.isContainerMode && this.container) {
      this.container.removeEventListener("scroll", this._onScroll);
      if (this._onTextareaInput) {
        this.container.removeEventListener("input", this._onTextareaInput);
      }
      this.container.classList.remove("has-floating-scroll-box");
      if (this.container.__floatingScrollInstance === this) {
        delete this.container.__floatingScrollInstance;
      }
    }

    if (this.win) {
      if (this.isContainerMode && this._onParentScroll) {
        this.win.removeEventListener("scroll", this._onParentScroll, { capture: true });
      } else {
        this.win.removeEventListener("scroll", this._onScroll);
      }
      this.win.removeEventListener("resize", this._onResize);
      this.win.removeEventListener("pointermove", this._onPointerMove);
      this.win.removeEventListener("pointerup", this._onPointerUp);
      this.win.removeEventListener("pointercancel", this._onPointerUp);
    }

    if (typeof window !== "undefined" && window !== this.win) {
      window.removeEventListener("pointermove", this._onPointerMove);
      window.removeEventListener("pointerup", this._onPointerUp);
      window.removeEventListener("pointercancel", this._onPointerUp);
    }

    if (this._resizeObserver) {
      this._resizeObserver.disconnect();
      this._resizeObserver = null;
    }

    if (this._containerMutationObserver) {
      this._containerMutationObserver.disconnect();
      this._containerMutationObserver = null;
    }

    if (this._trackObserver) {
      this._trackObserver.disconnect();
      this._trackObserver = null;
    }

    if (this.track && this.track.parentNode) {
      this.track.parentNode.removeChild(this.track);
    }

    if (!this.isContainerMode) {
      if (this.doc && this.doc.documentElement) {
        this.doc.documentElement.classList.remove("has-floating-scroll");
      }
      if (this.doc && this.doc.body) {
        this.doc.body.classList.remove("floating-scroll-dragging");
      }
      if (this.win && this.win.__floatingScrollInstance === this) {
        delete this.win.__floatingScrollInstance;
      }
    }
  }
}

/**
 * Inicializa la barra de scroll flotante con los parámetros indicados.
 *
 * @param {number|string|HTMLElement|Object} [width=8] - Ancho de la barra en px o contenedor HTML.
 * @param {string|Object} [color='color2'] - Clase de color `color1`, `color2`, ... o configuración.
 * @param {string|string[]} [responsive=''] - Clases `no-desk`, `no-tablet`, `no-phone`.
 * @param {Object} [options={}] - Opciones adicionales.
 * @returns {FloatingScroll}
 */
export function floatingScroll(width = 8, color = "color2", responsive = "", options = {}) {
  return new FloatingScroll(width, color, responsive, options);
}

if (typeof window !== "undefined") {
  window.FloatingScroll = FloatingScroll;
  window.floatingScroll = floatingScroll;
}
