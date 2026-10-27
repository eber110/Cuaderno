/**
 * 🌌 backgroundMotionText.js
 *
 * Clase y módulo para generar fondos dinámicos en patrón (texto, símbolos o iconos SVG)
 * renderizados mediante un único Tile Canvas offscreen + Pattern de hardware (O(1) por frame).
 * Ejecución 100% local en el cliente con consumo mínimo de CPU/GPU y cero impacto en el servidor.
 *
 * @function backgroundMotionText
 * @description Inicializa un fondo dinámico en patrón configurable globalmente (desde jsConfig.json)
 *              o por contenedor HTML (mediante la clase `.background-motion-text`).
 *
 * @param {string} [content] - Imagen SVG o texto del patrón (nombre de icono en App/Rsc/Ico/,
 *                             markup `<svg>...</svg>`, ruta `.svg` o texto plano como `'•'`).
 * @param {boolean} [rotate=false] - Si es `true`, el SVG o texto rota continuamente (360°).
 * @param {boolean} [moveBg=false] - Si es `true`, el patrón se mueve en diagonal desde la
 *                                   esquina inferior derecha hacia la esquina superior izquierda.
 * @param {Object} [options={}] - Opciones adicionales de personalización:
 * @param {string|HTMLElement} [options.target=document.body] - Contenedor destino.
 * @param {number} [options.gap] - Separación entre elementos en px (default: 28 en puntos, 36 en iconos/texto).
 * @param {number} [options.size] - Tamaño del icono o fuente en px (default: 6 en puntos, 16 en iconos/texto).
 * @param {number} [options.opacity=0.15] - Opacidad de cada elemento del patrón (0 a 1).
 * @param {string} [options.color] - Color CSS del elemento (por defecto usa `--bmt-color` o `currentColor`).
 * @param {number} [options.rotateSpeed=35] - Velocidad de rotación en grados/segundo.
 * @param {number} [options.moveSpeed=18] - Velocidad del desplazamiento diagonal en px/segundo.
 * @param {number} [options.angle=0] - Ángulo inicial en grados cuando `rotate` es `false`.
 * @param {string} [options.type='auto'] - Forzar tipo de contenido: `'auto' | 'text' | 'svg' | 'icon' | 'image'`.
 * @param {number} [options.zIndex] - z-index personalizado para el lienzo del fondo.
 *
 * @example
 * // 1. En jsConfig.json (global en body)
 * "backgroundMotionText": ["•", false, true]
 * // o con icono SVG rotando y moviéndose + opciones:
 * "backgroundMotionText": ["asterisk", true, true, { "gap": 40, "size": 18, "opacity": 0.12 }]
 *
 * @example
 * // 2. En HTML sobre cualquier contenedor
 * <section class="background-motion-text back8 color2 p40"
 *          data-bmt-content="asterisk"
 *          data-bmt-rotate="true"
 *          data-bmt-move="true"
 *          data-bmt-gap="36"
 *          data-bmt-size="16"
 *          data-bmt-opacity="0.15">
 *   <h2>Contenido sobre fondo dinámico</h2>
 * </section>
 *
 * @example
 * // 3. Instanciación directa en JavaScript
 * const bg = new BackgroundMotionText("•", true, true, { gap: 28, size: 8 });
 *
 * @css .background-motion-text - Contenedor con patrón de fondo dinámico
 * @css .bmt-canvas - Lienzo Canvas generado automáticamente
 * @css --bmt-color - Variable CSS opcional para el color del patrón
 * @css --bmt-opacity - Variable CSS opcional para la opacidad (ej. 0.15)
 * @css --bmt-gap - Variable CSS opcional para la separación en px
 * @css --bmt-size - Variable CSS opcional para el tamaño en px
 * @css --bmt-rotate-speed - Variable CSS opcional para velocidad de giro (deg/s)
 * @css --bmt-move-speed - Variable CSS opcional para velocidad diagonal (px/s)
 *
 * @returns {BackgroundMotionText|BackgroundMotionText[]|null}
 */

const ROUTE_ICON_BASE = "/App/Rsc/Ico/";
const DOT_CHARS = new Set(["•", "·", ".", "∙", "⋅", "●", "○"]);

/**
 * Convierte un valor booleano desde atributos HTML, strings o valores nativos.
 *
 * @param {*} val Valor a evaluar.
 * @param {boolean} fallback Valor por defecto si es undefined o vacío.
 * @returns {boolean}
 */
function parseBool(val, fallback = false) {
  if (typeof val === "boolean") return val;
  if (val === undefined || val === null || val === "") return fallback;
  if (typeof val === "number") return val !== 0;
  const norm = String(val).trim().toLowerCase();
  if (norm === "true" || norm === "1" || norm === "yes" || norm === "si") return true;
  if (norm === "false" || norm === "0" || norm === "no") return false;
  return fallback;
}

/**
 * Convierte un número flotante seguro con valor por defecto.
 *
 * @param {*} val Valor a convertir.
 * @param {number} fallback Valor por defecto.
 * @returns {number}
 */
function parseNum(val, fallback) {
  if (val === undefined || val === null || val === "") return fallback;
  const n = parseFloat(val);
  return Number.isFinite(n) ? n : fallback;
}

/**
 * Clase optimizada para renderizar patrones dinámicos de fondo en un único Canvas
 * utilizando pre-rasterizado en Sprite + Tile Pattern de hardware.
 */
export class BackgroundMotionText {
  /** Caché estático de archivos SVG descargados para evitar peticiones repetidas */
  static _svgCache = new Map();

  /**
   * Crea una instancia de BackgroundMotionText.
   * Soporta:
   * - `new BackgroundMotionText(content, rotate, moveBg, options)`
   * - `new BackgroundMotionText(containerElement, content, rotate, moveBg, options)`
   *
   * @param {string|HTMLElement} contentOrEl Texto/SVG del patrón o elemento contenedor.
   * @param {boolean|string} [rotate=false] Rotación continua del elemento (`true` / `false`).
   * @param {boolean} [moveBg=false] Movimiento diagonal inferior-derecha a superior-izquierda (`true` / `false`).
   * @param {Object|boolean} [options={}] Opciones adicionales de configuración.
   * @param {Object} [maybeOptions={}] Opciones cuando el primer argumento es un HTMLElement.
   */
  constructor(contentOrEl = "•", rotate = false, moveBg = false, options = {}, maybeOptions = {}) {
    let targetEl = null;
    let rawContent = contentOrEl;
    let rawRotate = rotate;
    let rawMoveBg = moveBg;
    let rawOptions = options && typeof options === "object" ? options : {};

    // Permitir pasar un HTMLElement como primer argumento
    if (typeof HTMLElement !== "undefined" && contentOrEl instanceof HTMLElement) {
      targetEl = contentOrEl;
      rawContent = typeof rotate === "string" ? rotate : undefined;
      rawRotate = typeof rotate === "boolean" ? rotate : moveBg;
      rawMoveBg = typeof rotate === "boolean" ? moveBg : options;
      rawOptions = (typeof rotate === "boolean" ? options : maybeOptions) || {};
    } else if (rawOptions.target) {
      if (typeof rawOptions.target === "string") {
        targetEl = document.querySelector(rawOptions.target);
      } else if (typeof HTMLElement !== "undefined" && rawOptions.target instanceof HTMLElement) {
        targetEl = rawOptions.target;
      }
    }

    this.container = targetEl || document.body;
    if (!this.container) return;

    // Si ya existía una instancia previa en este contenedor, destruirla limpiamente
    if (this.container.__bmt_instance && this.container.__bmt_instance !== this) {
      this.container.__bmt_instance.destroy();
    }

    this.isBody = this.container === document.body || this.container === document.documentElement;

    // Extraer configuración combinando argumentos, atributos data-* y variables CSS
    const ds = this.container.dataset || {};
    const computed = window.getComputedStyle(this.container);

    const cssGap = computed.getPropertyValue("--bmt-gap").trim();
    const cssSize = computed.getPropertyValue("--bmt-size").trim();
    const cssOpacity = computed.getPropertyValue("--bmt-opacity").trim();
    const cssColor = computed.getPropertyValue("--bmt-color").trim();
    const cssRotateSpeed = computed.getPropertyValue("--bmt-rotate-speed").trim();
    const cssMoveSpeed = computed.getPropertyValue("--bmt-move-speed").trim();
    const cssAngle = computed.getPropertyValue("--bmt-angle").trim();

    this.content = String(
      rawContent !== undefined && rawContent !== null && rawContent !== ""
        ? rawContent
        : (ds.bmtContent ?? ds.content ?? ds.bmt ?? "•")
    );

    this.rotate = parseBool(
      rawRotate !== undefined ? rawRotate : (ds.bmtRotate ?? ds.rotate),
      false
    );

    this.moveBg = parseBool(
      rawMoveBg !== undefined ? rawMoveBg : (ds.bmtMove ?? ds.move),
      false
    );

    const isDot = DOT_CHARS.has(this.content.trim());
    const defaultSize = isDot ? 6 : 16;
    const defaultGap = isDot ? 28 : 36;

    this.options = {
      type: rawOptions.type || ds.bmtType || ds.type || "auto",
      size: Math.max(2, parseNum(rawOptions.size ?? ds.bmtSize ?? ds.size ?? cssSize, defaultSize)),
      gap: Math.max(4, parseNum(rawOptions.gap ?? ds.bmtGap ?? ds.gap ?? cssGap, defaultGap)),
      opacity: Math.min(1, Math.max(0.01, parseNum(rawOptions.opacity ?? ds.bmtOpacity ?? ds.opacity ?? cssOpacity, 0.15))),
      color: rawOptions.color || ds.bmtColor || ds.color || cssColor || "",
      rotateSpeed: parseNum(rawOptions.rotateSpeed ?? ds.bmtRotateSpeed ?? ds.rotateSpeed ?? cssRotateSpeed, 35),
      moveSpeed: parseNum(rawOptions.moveSpeed ?? ds.bmtMoveSpeed ?? ds.moveSpeed ?? cssMoveSpeed, 18),
      angle: parseNum(rawOptions.angle ?? ds.bmtAngle ?? ds.angle ?? cssAngle, 0),
      fontFamily: rawOptions.fontFamily || ds.bmtFont || "",
      fontWeight: rawOptions.fontWeight || ds.bmtFontWeight || "600",
      zIndex: rawOptions.zIndex ?? (ds.bmtZIndex !== undefined ? parseInt(ds.bmtZIndex, 10) : null)
    };

    // Estado interno de renderizado y animación
    this.dpr = Math.min(window.devicePixelRatio || 1, 2);
    this.viewWidth = 0;
    this.viewHeight = 0;
    this.tileWidth = 32;
    this.tileHeight = 32;
    this.angle = this.options.angle;
    this.offsetX = 0;
    this.offsetY = 0;
    this.lastTime = 0;
    this.rafId = null;
    this.isPaused = false;
    this.isVisible = true;
    this.isDestroyed = false;
    this.pattern = null;
    this.patternMatrix = typeof DOMMatrix !== "undefined" ? new DOMMatrix() : null;
    this.loadedImage = null;
    this.resolvedKind = "text"; // 'text' | 'image'

    // Canvases offscreen para Sprite (elemento único) y Tile (celda repetible)
    this.spriteCanvas = document.createElement("canvas");
    this.spriteCtx = this.spriteCanvas.getContext("2d");
    this.tileCanvas = document.createElement("canvas");
    this.tileCtx = this.tileCanvas.getContext("2d");

    this.container.__bmt_instance = this;
    this.container.setAttribute("data-bmt-ready", "true");

    this._initDOM();
    this._initObservers();
    this._loadAndRender();
  }

  /**
   * Crea e inyecta el elemento `<canvas>` de fondo asegurando el contexto de apilamiento.
   *
   * @private
   */
  _initDOM() {
    this.canvas = document.createElement("canvas");
    this.canvas.className = "bmt-canvas";
    this.canvas.setAttribute("aria-hidden", "true");

    const s = this.canvas.style;
    s.pointerEvents = "none";
    s.userSelect = "none";
    s.webkitUserSelect = "none";
    s.display = "block";
    s.inset = "0";

    if (this.isBody) {
      s.position = "fixed";
      s.width = "100vw";
      s.height = "100vh";
      s.zIndex = String(this.options.zIndex !== null ? this.options.zIndex : 0);
    } else {
      const comp = window.getComputedStyle(this.container);
      if (comp.position === "static") {
        this.container.style.position = "relative";
      }
      if (!this.container.style.isolation) {
        this.container.style.isolation = "isolate";
      }
      s.position = "absolute";
      s.width = "100%";
      s.height = "100%";
      s.zIndex = String(this.options.zIndex !== null ? this.options.zIndex : -1);
    }

    if (this.container.firstChild) {
      this.container.insertBefore(this.canvas, this.container.firstChild);
    } else {
      this.container.appendChild(this.canvas);
    }

    this.ctx = this.canvas.getContext("2d", { alpha: true });
    this._updateCanvasSize();
  }

  /**
   * Configura observadores de tamaño, visibilidad en viewport, pestaña activa y cambio de tema claro/oscuro.
   *
   * @private
   */
  _initObservers() {
    // 1. ResizeObserver para adaptar dimensiones sin recalcular en cada frame
    this._onResize = () => {
      if (this.isDestroyed) return;
      this.dpr = Math.min(window.devicePixelRatio || 1, 2);
      this._updateCanvasSize();
      this._rebuildSpriteAndTile();
      this._drawFrame();
    };

    if (typeof ResizeObserver !== "undefined" && !this.isBody) {
      this._resizeObserver = new ResizeObserver(this._onResize);
      this._resizeObserver.observe(this.container);
    } else {
      window.addEventListener("resize", this._onResize, { passive: true });
    }

    // 2. IntersectionObserver para pausar automáticamente cuando el contenedor no está en pantalla
    if (typeof IntersectionObserver !== "undefined" && !this.isBody) {
      this._intersectionObserver = new IntersectionObserver(
        (entries) => {
          for (const entry of entries) {
            this.isVisible = entry.isIntersecting;
          }
          this._syncAnimationState();
        },
        { threshold: 0 }
      );
      this._intersectionObserver.observe(this.container);
    }

    // 3. Visibilidad de pestaña del navegador (ahorro total de recursos en background)
    this._onVisibilityChange = () => {
      this._syncAnimationState();
    };
    document.addEventListener("visibilitychange", this._onVisibilityChange);

    // 4. Sincronización automática con cambio de tema (claro / oscuro en <html data-theme="...">)
    if (typeof MutationObserver !== "undefined") {
      this._themeObserver = new MutationObserver(() => {
        if (this.isDestroyed) return;
        this._loadAndRender();
      });
      this._themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["data-theme", "class"]
      });
    }
  }

  /**
   * Actualiza las dimensiones físicas y lógicas del lienzo principal.
   *
   * @private
   */
  _updateCanvasSize() {
    if (!this.canvas) return;
    const width = this.isBody ? window.innerWidth : this.container.clientWidth;
    const height = this.isBody ? window.innerHeight : this.container.clientHeight;

    this.viewWidth = Math.max(1, width);
    this.viewHeight = Math.max(1, height);

    const targetW = Math.round(this.viewWidth * this.dpr);
    const targetH = Math.round(this.viewHeight * this.dpr);

    if (this.canvas.width !== targetW || this.canvas.height !== targetH) {
      this.canvas.width = targetW;
      this.canvas.height = targetH;
    }
  }

  /**
   * Resuelve el color efectivo del elemento a partir de las opciones, variables CSS o el tema activo.
   *
   * @private
   * @returns {string} Color CSS resuelto.
   */
  _getResolvedColor() {
    const comp = window.getComputedStyle(this.container);
    const rawColor = this.options.color || comp.getPropertyValue("--bmt-color").trim();

    if (rawColor && !rawColor.includes("var(") && rawColor !== "currentColor") {
      return rawColor;
    }

    if (rawColor && rawColor.includes("var(")) {
      const temp = document.createElement("span");
      temp.style.display = "none";
      temp.style.color = rawColor;
      this.container.appendChild(temp);
      const resolved = window.getComputedStyle(temp).color;
      temp.remove();
      if (resolved) return resolved;
    }

    return comp.color || "#8899aa";
  }

  /**
   *Resuelve la fuente tipográfica efectiva para el modo texto.
   *
   * @private
   * @returns {string}
   */
  _getResolvedFontFamily() {
    const comp = window.getComputedStyle(this.container);
    return (
      this.options.fontFamily ||
      comp.getPropertyValue("--bmt-font").trim() ||
      comp.fontFamily ||
      "system-ui, -apple-system, sans-serif"
    );
  }

  /**
   * Determina el tipo de contenido ('svg-inline', 'svg-url', 'image-url', 'icon-candidate', 'text').
   *
   * @private
   * @returns {{ mode: string, value: string }}
   */
  _classifyContent() {
    const raw = (this.content || "•").trim();
    const forced = (this.options.type || "auto").toLowerCase();

    if (forced === "text") {
      return { mode: "text", value: raw };
    }

    if (raw.startsWith("<svg") || (raw.startsWith("<?xml") && raw.includes("<svg"))) {
      return { mode: "svg-inline", value: raw };
    }

    if (/^(?:ico|svg|icon):/i.test(raw)) {
      const iconName = raw.replace(/^(?:ico|svg|icon):/i, "").trim();
      const url = iconName.startsWith("/") || iconName.startsWith("http")
        ? iconName
        : `${ROUTE_ICON_BASE}${iconName.replace(/\.svg$/i, "")}.svg`;
      return { mode: "svg-url", value: url };
    }

    if (/\.svg(?:\?.*)?$/i.test(raw)) {
      const url = raw.startsWith("/") || raw.startsWith("http") || raw.startsWith("./")
        ? raw
        : `${ROUTE_ICON_BASE}${raw}`;
      return { mode: "svg-url", value: url };
    }

    if (/\.(?:png|webp|jpe?g|gif|avif)(?:\?.*)?$/i.test(raw) || /^data:image\//i.test(raw)) {
      return { mode: "image-url", value: raw };
    }

    if (forced === "svg" || forced === "icon") {
      const clean = raw.replace(/\.svg$/i, "");
      return { mode: "svg-url", value: `${ROUTE_ICON_BASE}${clean}.svg` };
    }

    if (forced === "image") {
      return { mode: "image-url", value: raw };
    }

    // Auto-detección: si parece un identificador de icono de App/Rsc/Ico/ (ej. 'asterisk', 'heart-fill', 'Custom/logo')
    if (
      raw.length >= 2 &&
      !/\s/.test(raw) &&
      /^(?:[a-z0-9]+(?:-[a-z0-9]+)*|(?:Custom|Separator)\/[a-zA-Z0-9_-]+)$/.test(raw)
    ) {
      return {
        mode: "icon-candidate",
        value: `${ROUTE_ICON_BASE}${raw}.svg`,
        fallbackText: raw
      };
    }

    return { mode: "text", value: raw };
  }

  /**
   * Descarga o recupera de caché el contenido de un archivo SVG.
   *
   * @private
   * @param {string} url Ruta del archivo SVG.
   * @returns {Promise<string|null>}
   */
  async _fetchSvgText(url) {
    if (BackgroundMotionText._svgCache.has(url)) {
      return BackgroundMotionText._svgCache.get(url);
    }

    try {
      const res = await fetch(url);
      if (!res.ok) {
        BackgroundMotionText._svgCache.set(url, null);
        return null;
      }
      const text = await res.text();
      if (!text || !text.includes("<svg")) {
        BackgroundMotionText._svgCache.set(url, null);
        return null;
      }
      BackgroundMotionText._svgCache.set(url, text);
      return text;
    } catch (_) {
      BackgroundMotionText._svgCache.set(url, null);
      return null;
    }
  }

  /**
   * Prepara un string SVG inyectando dimensiones explícitas y el color resuelto,
   * y lo convierte en un objeto `HTMLImageElement` listo para dibujar en Canvas.
   *
   * @private
   * @param {string} svgText Markup SVG original.
   * @param {string} color Color CSS resuelto.
   * @returns {Promise<HTMLImageElement|null>}
   */
  _svgToImage(svgText, color) {
    return new Promise((resolve) => {
      try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(svgText, "image/svg+xml");
        const svgEl = doc.querySelector("svg");

        if (!svgEl) {
          resolve(null);
          return;
        }

        const pixelSize = Math.max(32, Math.round(this.options.size * this.dpr * 2));
        if (!svgEl.getAttribute("xmlns")) {
          svgEl.setAttribute("xmlns", "http://www.w3.org/2000/svg");
        }
        svgEl.setAttribute("width", String(pixelSize));
        svgEl.setAttribute("height", String(pixelSize));
        svgEl.setAttribute("fill", color);
        svgEl.style.color = color;

        // Aplicar color a los nodos internos que usen currentColor o carezcan de fill explícito
        svgEl.querySelectorAll("path, circle, rect, polygon, polyline, ellipse, g").forEach((node) => {
          const fillAttr = node.getAttribute("fill");
          if (!fillAttr || fillAttr === "currentColor") {
            node.setAttribute("fill", color);
          }
          const strokeAttr = node.getAttribute("stroke");
          if (strokeAttr === "currentColor") {
            node.setAttribute("stroke", color);
          }
        });

        const serialized = new XMLSerializer().serializeToString(svgEl);
        const blob = new Blob([serialized], { type: "image/svg+xml;charset=utf-8" });
        const blobUrl = URL.createObjectURL(blob);

        const img = new Image();
        img.onload = () => {
          URL.revokeObjectURL(blobUrl);
          resolve(img);
        };
        img.onerror = () => {
          URL.revokeObjectURL(blobUrl);
          resolve(null);
        };
        img.src = blobUrl;
      } catch (_) {
        resolve(null);
      }
    });
  }

  /**
   * Carga una imagen externa por URL.
   *
   * @private
   * @param {string} url URL de la imagen.
   * @returns {Promise<HTMLImageElement|null>}
   */
  _loadUrlImage(url) {
    return new Promise((resolve) => {
      const img = new Image();
      img.crossOrigin = "anonymous";
      img.onload = () => resolve(img);
      img.onerror = () => resolve(null);
      img.src = url;
    });
  }

  /**
   * Resuelve el recurso (SVG, imagen o texto), construye el Sprite y el Tile, e inicia el ciclo de pintado.
   *
   * @private
   */
  async _loadAndRender() {
    if (this.isDestroyed) return;

    const classification = this._classifyContent();
    const color = this._getResolvedColor();

    this.loadedImage = null;
    this.resolvedKind = "text";

    if (classification.mode === "svg-inline") {
      const img = await this._svgToImage(classification.value, color);
      if (this.isDestroyed) return;
      if (img) {
        this.loadedImage = img;
        this.resolvedKind = "image";
      }
    } else if (classification.mode === "svg-url") {
      const svgText = await this._fetchSvgText(classification.value);
      if (this.isDestroyed) return;
      if (svgText) {
        const img = await this._svgToImage(svgText, color);
        if (this.isDestroyed) return;
        if (img) {
          this.loadedImage = img;
          this.resolvedKind = "image";
        }
      }
    } else if (classification.mode === "icon-candidate") {
      const svgText = await this._fetchSvgText(classification.value);
      if (this.isDestroyed) return;
      if (svgText) {
        const img = await this._svgToImage(svgText, color);
        if (this.isDestroyed) return;
        if (img) {
          this.loadedImage = img;
          this.resolvedKind = "image";
        }
      }
    } else if (classification.mode === "image-url") {
      const img = await this._loadUrlImage(classification.value);
      if (this.isDestroyed) return;
      if (img) {
        this.loadedImage = img;
        this.resolvedKind = "image";
      }
    }

    this._rebuildSpriteAndTile();
    this._drawFrame();
    this._syncAnimationState();
  }

  /**
   * Pre-rasteriza el elemento individual (texto o SVG) en `this.spriteCanvas`
   * y genera la celda base en `this.tileCanvas`.
   *
   * @private
   */
  _rebuildSpriteAndTile() {
    const dpr = this.dpr;
    const size = this.options.size;
    const gap = this.options.gap;
    const color = this._getResolvedColor();

    let itemW = size;
    let itemH = size;

    if (this.resolvedKind === "text") {
      const font = `${this.options.fontWeight} ${Math.round(size * dpr)}px ${this._getResolvedFontFamily()}`;
      this.spriteCtx.font = font;
      const metrics = this.spriteCtx.measureText(this.content);
      const measuredW = Math.ceil((metrics.width || size * dpr) / dpr);
      itemW = Math.max(size, measuredW + 2);
      itemH = Math.ceil(size * 1.25);
    }

    this.itemWidth = itemW;
    this.itemHeight = itemH;

    // Dimensiones del Sprite en píxeles físicos (dpr)
    const spritePhysW = Math.max(4, Math.ceil(itemW * dpr));
    const spritePhysH = Math.max(4, Math.ceil(itemH * dpr));
    this.spriteCanvas.width = spritePhysW;
    this.spriteCanvas.height = spritePhysH;

    const sCtx = this.spriteCtx;
    sCtx.clearRect(0, 0, spritePhysW, spritePhysH);
    sCtx.save();
    sCtx.globalAlpha = this.options.opacity;

    if (this.resolvedKind === "image" && this.loadedImage) {
      // Mantener relación de aspecto del SVG/imagen centrado dentro del sprite
      const natW = this.loadedImage.naturalWidth || size;
      const natH = this.loadedImage.naturalHeight || size;
      const scale = Math.min(spritePhysW / natW, spritePhysH / natH);
      const drawW = natW * scale;
      const drawH = natH * scale;
      const dx = (spritePhysW - drawW) * 0.5;
      const dy = (spritePhysH - drawH) * 0.5;
      sCtx.drawImage(this.loadedImage, dx, dy, drawW, drawH);
    } else {
      const trimmed = this.content.trim();
      if (DOT_CHARS.has(trimmed)) {
        // Para puntos ('•', '.'), dibujar un círculo geométrico perfecto con antialiasing nativo
        sCtx.fillStyle = color;
        sCtx.beginPath();
        const radius = Math.max(1, (size * dpr) * 0.35);
        sCtx.arc(spritePhysW * 0.5, spritePhysH * 0.5, radius, 0, Math.PI * 2);
        sCtx.fill();
      } else {
        sCtx.font = `${this.options.fontWeight} ${Math.round(size * dpr)}px ${this._getResolvedFontFamily()}`;
        sCtx.fillStyle = color;
        sCtx.textAlign = "center";
        sCtx.textBaseline = "middle";
        sCtx.fillText(this.content, spritePhysW * 0.5, spritePhysH * 0.5);
      }
    }
    sCtx.restore();

    // Calcular el tamaño de la celda repetible (Tile) garantizando que al rotar 360° no se recorten esquinas
    if (this.rotate) {
      const diag = Math.ceil(Math.hypot(itemW, itemH));
      this.tileWidth = Math.max(diag + 4, itemW + gap);
      this.tileHeight = Math.max(diag + 4, itemH + gap);
    } else {
      this.tileWidth = itemW + gap;
      this.tileHeight = itemH + gap;
    }

    this.tileCanvas.width = Math.max(4, Math.round(this.tileWidth * dpr));
    this.tileCanvas.height = Math.max(4, Math.round(this.tileHeight * dpr));

    this._updateTile((this.angle * Math.PI) / 180);
  }

  /**
   * Actualiza la celda única `this.tileCanvas` rotando el Sprite pre-rasterizado
   * y regenera el `CanvasPattern` repetible.
   *
   * @private
   * @param {number} angleRad Ángulo de rotación en radianes.
   */
  _updateTile(angleRad) {
    const tW = this.tileCanvas.width;
    const tH = this.tileCanvas.height;
    const tCtx = this.tileCtx;

    tCtx.clearRect(0, 0, tW, tH);
    tCtx.save();
    tCtx.translate(tW * 0.5, tH * 0.5);

    if (angleRad !== 0) {
      tCtx.rotate(angleRad);
    }

    const sW = this.spriteCanvas.width;
    const sH = this.spriteCanvas.height;
    tCtx.drawImage(this.spriteCanvas, -sW * 0.5, -sH * 0.5, sW, sH);
    tCtx.restore();

    if (this.ctx) {
      this.pattern = this.ctx.createPattern(this.tileCanvas, "repeat");
    }
  }

  /**
   * Pinta el patrón repetido sobre el lienzo principal aplicando el desplazamiento diagonal.
   *
   * @private
   */
  _drawFrame() {
    if (!this.ctx || !this.pattern) return;

    const cW = this.canvas.width;
    const cH = this.canvas.height;
    const ctx = this.ctx;

    ctx.clearRect(0, 0, cW, cH);

    const shiftX = -this.offsetX * this.dpr;
    const shiftY = -this.offsetY * this.dpr;

    if (this.pattern.setTransform && this.patternMatrix) {
      this.patternMatrix.a = 1;
      this.patternMatrix.b = 0;
      this.patternMatrix.c = 0;
      this.patternMatrix.d = 1;
      this.patternMatrix.e = shiftX;
      this.patternMatrix.f = shiftY;
      this.pattern.setTransform(this.patternMatrix);
      ctx.fillStyle = this.pattern;
      ctx.fillRect(0, 0, cW, cH);
    } else {
      ctx.save();
      ctx.translate(shiftX, shiftY);
      ctx.fillStyle = this.pattern;
      ctx.fillRect(-shiftX, -shiftY, cW, cH);
      ctx.restore();
    }
  }

  /**
   * Inicia o detiene el bucle `requestAnimationFrame` según si hay animaciones activas y el elemento es visible.
   *
   * @private
   */
  _syncAnimationState() {
    const shouldAnimate =
      !this.isDestroyed &&
      !this.isPaused &&
      this.isVisible &&
      !document.hidden &&
      (this.rotate || this.moveBg);

    if (shouldAnimate && !this.rafId) {
      this.lastTime = performance.now();
      this.rafId = requestAnimationFrame((ts) => this._tick(ts));
    } else if (!shouldAnimate && this.rafId) {
      cancelAnimationFrame(this.rafId);
      this.rafId = null;
    }
  }

  /**
   * Ciclo de animación por frame.
   * - Si `rotate` es `true`, rota el elemento continuamente (360°).
   * - Si `moveBg` es `true`, desplaza el fondo desde la esquina inferior derecha hacia la superior izquierda.
   *
   * @private
   * @param {number} timestamp Marca de tiempo de alta precisión.
   */
  _tick(timestamp) {
    this.rafId = null;
    if (this.isDestroyed || this.isPaused || !this.isVisible || document.hidden) {
      return;
    }

    const dt = Math.min((timestamp - this.lastTime) / 1000, 0.1);
    this.lastTime = timestamp;

    if (this.rotate) {
      this.angle = (this.angle + this.options.rotateSpeed * dt) % 360;
      this._updateTile((this.angle * Math.PI) / 180);
    }

    if (this.moveBg) {
      // Incrementar offset positivo que en _drawFrame se traduce como (-offsetX, -offsetY):
      // movimiento diagonal desde la esquina inferior derecha hacia la esquina superior izquierda
      this.offsetX = (this.offsetX + this.options.moveSpeed * dt) % this.tileWidth;
      this.offsetY = (this.offsetY + this.options.moveSpeed * dt) % this.tileHeight;
    }

    this._drawFrame();
    this.rafId = requestAnimationFrame((ts) => this._tick(ts));
  }

  /**
   * Cambia dinámicamente la imagen SVG o el texto del patrón.
   *
   * @param {string} content Nuevo texto, nombre de icono o código SVG.
   * @param {string} [type='auto'] Tipo opcional ('auto' | 'text' | 'svg' | 'icon' | 'image').
   * @returns {this}
   */
  setContent(content, type = "auto") {
    this.content = String(content ?? "•");
    this.options.type = type;
    this._loadAndRender();
    return this;
  }

  /**
   * Activa o desactiva la rotación continua del SVG o texto.
   *
   * @param {boolean} rotate `true` para rotar siempre, `false` para detener.
   * @returns {this}
   */
  setRotate(rotate) {
    const next = Boolean(rotate);
    if (this.rotate !== next) {
      this.rotate = next;
      this._rebuildSpriteAndTile();
      this._drawFrame();
      this._syncAnimationState();
    }
    return this;
  }

  /**
   * Activa o desactiva el movimiento diagonal del fondo (esquina baja derecha a alta izquierda).
   *
   * @param {boolean} moveBg `true` para activar el movimiento diagonal, `false` para detener.
   * @returns {this}
   */
  setMoveBg(moveBg) {
    this.moveBg = Boolean(moveBg);
    this._syncAnimationState();
    return this;
  }

  /**
   * Actualiza opciones de estilo y velocidad en caliente.
   *
   * @param {Object} newOptions Nuevas opciones (`gap`, `size`, `opacity`, `color`, `rotateSpeed`, `moveSpeed`).
   * @returns {this}
   */
  setOptions(newOptions = {}) {
    if (!newOptions || typeof newOptions !== "object") return this;
    Object.assign(this.options, newOptions);
    this._loadAndRender();
    return this;
  }

  /**
   * Pausa manualmente la animación del fondo.
   *
   * @returns {this}
   */
  pause() {
    this.isPaused = true;
    this._syncAnimationState();
    return this;
  }

  /**
   * Reanuda la animación del fondo.
   *
   * @returns {this}
   */
  resume() {
    this.isPaused = false;
    this._syncAnimationState();
    return this;
  }

  /**
   * Elimina el lienzo, desconecta observadores y libera memoria.
   */
  destroy() {
    this.isDestroyed = true;
    if (this.rafId) {
      cancelAnimationFrame(this.rafId);
      this.rafId = null;
    }
    if (this._resizeObserver) {
      this._resizeObserver.disconnect();
      this._resizeObserver = null;
    }
    window.removeEventListener("resize", this._onResize);

    if (this._intersectionObserver) {
      this._intersectionObserver.disconnect();
      this._intersectionObserver = null;
    }
    if (this._themeObserver) {
      this._themeObserver.disconnect();
      this._themeObserver = null;
    }
    document.removeEventListener("visibilitychange", this._onVisibilityChange);

    if (this.canvas && this.canvas.parentNode) {
      this.canvas.parentNode.removeChild(this.canvas);
    }
    if (this.container && this.container.__bmt_instance === this) {
      delete this.container.__bmt_instance;
      this.container.removeAttribute("data-bmt-ready");
    }
  }
}

/**
 * Inicializa todos los contenedores HTML con clase `.background-motion-text` o `[data-bmt]`,
 * y si se proporcionan parámetros (por ejemplo desde `jsConfig.json`), crea el fondo dinámico global.
 *
 * @param {string} [content] - Imagen SVG o texto del patrón (1er parámetro).
 * @param {boolean} [rotate=false] - Rotación continua del SVG o texto (`true` / `false`) (2do parámetro).
 * @param {boolean} [moveBg=false] - Movimiento del fondo desde esquina baja derecha a alta izquierda (`true` / `false`) (3er parámetro).
 * @param {Object} [options={}] - Opciones adicionales (`gap`, `size`, `opacity`, `color`, `rotateSpeed`, `moveSpeed`, `target`).
 * @returns {BackgroundMotionText|BackgroundMotionText[]|null}
 */
export function backgroundMotionText(content, rotate = false, moveBg = false, options = {}) {
  const instances = [];

  // 1. Si se invocó con el primer parámetro (ej. desde jsConfig.json o llamada directa), aplicar al target (body por defecto)
  if (content !== undefined && content !== null && content !== "") {
    const globalInstance = new BackgroundMotionText(content, rotate, moveBg, options);
    instances.push(globalInstance);
  }

  // 2. Descubrir e inicializar contenedores declarativos en el DOM (.background-motion-text o [data-bmt])
  const initDeclarativeElements = () => {
    document.querySelectorAll(".background-motion-text, [data-bmt]").forEach((el) => {
      if (!el.__bmt_instance) {
        const inst = new BackgroundMotionText(el);
        instances.push(inst);
      }
    });
  };

  initDeclarativeElements();

  // Re-escanear si se actualiza el contenido dinámico (modales, AJAX, remoteContent)
  if (!window.__bmt_listeners_bound) {
    window.__bmt_listeners_bound = true;
    document.addEventListener("contentUpdated", initDeclarativeElements);
  }

  return instances.length === 1 ? instances[0] : instances;
}

// Exposición global en window para uso programático directo
if (typeof window !== "undefined") {
  window.BackgroundMotionText = BackgroundMotionText;
  window.backgroundMotionText = backgroundMotionText;
}
