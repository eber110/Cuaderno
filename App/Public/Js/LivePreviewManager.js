/**
 * Módulo LivePreviewManager
 *
 * Motor declarativo Zero-Code para la sincronización en vivo (0ms) de la vista previa
 * del perfil de usuario (.user-profile-preview) en escritorio y modal móvil.
 *
 * Procesa automáticamente atributos declarativos en las plantillas PHP:
 * - data-bind / data-sub-bind / data-profile-bind (texto, prefijos, conservación de SVG, saltos de línea)
 * - data-bind-html / data-sub-bind-html (texto multilínea escapado con <br>)
 * - data-bind-attr / data-sub-bind-attr / data-profile-bind-attr (href, src, alt, data-*)
 * - data-bind-show / data-sub-bind-show (visibilidad reactiva display: none / "")
 * - data-bind-style / data-profile-bind-style (propiedades CSS en línea reactivas)
 * - data-bind-class / data-sub-bind-class (conmutación declarativa de clases CSS)
 *
 * Cualquier nuevo bloque añadido en el futuro en App/Views/User/ con atributos data-bind*
 * es absorbido automáticamente sin necesidad de añadir selectores específicos en JavaScript.
 *
 * @function LivePreviewManager
 * @description Inicializa y expone globalmente window.LivePreviewManager.
 * @returns {Object} API del motor declarativo de vista previa.
 */
export function LivePreviewManager() {
  if (window.LivePreviewManager && window.LivePreviewManager.__initialized) {
    return window.LivePreviewManager;
  }

  /**
   * Escapa caracteres HTML especiales para prevenir XSS al usar innerHTML con nl2br.
   * @param {string} str Texto de entrada.
   * @returns {string} Texto escapado.
   */
  function escapeHtml(str) {
    return String(str ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  /**
   * Extrae el ID de un video de YouTube desde cualquier variante de URL.
   * @param {string} url URL del video.
   * @returns {string} ID de 11 caracteres o cadena vacía.
   */
  function extractYouTubeId(url) {
    if (!url || typeof url !== "string") return "";
    const m = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts|live)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i);
    return m && m[1] ? m[1] : "";
  }

  /**
   * Extrae el ID numérico de un video de Vimeo.
   * @param {string} url URL del video.
   * @returns {string} ID de Vimeo o cadena vacía.
   */
  function extractVimeoId(url) {
    if (!url || typeof url !== "string") return "";
    const m = url.match(/vimeo\.com\/(?:video\/)?([0-9]+)/i);
    return m && m[1] ? m[1] : "";
  }

  /**
   * Evalúa un valor booleano proveniente de formulario o JSON.
   * @param {*} val Valor a evaluar.
   * @param {boolean} [defaultVal=false] Valor por defecto si es undefined.
   * @returns {boolean}
   */
  function toBool(val, defaultVal = false) {
    if (val === undefined || val === null) return defaultVal;
    return val === true || val === "true" || val === 1 || val === "1";
  }

  /**
   * Enriquecedor de datos de bloque: conserva todas las propiedades originales del bloque
   * (para soportar cualquier bloque futuro sin tocar código) y calcula banderas derivadas
   * estándar (imágenes, precios de oferta, estilos de campaña, proporciones de banner y miniaturas de video).
   *
   * @param {Object} rawBlock Datos crudos del bloque o sub-ítem.
   * @param {Object} [cardState={}] Estado general de la tarjeta.
   * @returns {Object} Modelo de datos enriquecido listo para el micro-binder.
   */
  function computeDerivedBlockData(rawBlock = {}, cardState = {}) {
    const data = { ...rawBlock };

    // 1. Visibilidad y estado de imagen
    const imgShow = toBool(rawBlock.imgShow, true);
    const imgSrc = String(rawBlock.imgSrc || "");
    const imgName = String(rawBlock.img || "");
    const hasValidImgSrc =
      Boolean(imgSrc && !imgSrc.includes("no-image.webp")) ||
      Boolean(imgName && imgName !== "no-image.webp" && imgSrc !== "");
    const effectiveImgSrc = hasValidImgSrc ? imgSrc : (imgName && imgName !== "no-image.webp" ? `/Uploads/${imgName}` : "");
    data.imgShow = imgShow;
    data.hasImg = Boolean(imgShow && hasValidImgSrc);
    data.hasVisibleImg = data.hasImg;
    data.effectiveImgSrc = effectiveImgSrc;
    if (effectiveImgSrc) {
      data.imgSrc = effectiveImgSrc;
    }

    // 2. Textos y banderas de presencia
    const titleStr = String(rawBlock.title ?? "").trim();
    const descStr = String(rawBlock.desc ?? "").trim();
    const textStr = String(rawBlock.text !== undefined ? rawBlock.text : (rawBlock.title ?? "")).trim();
    const urlStr = String(rawBlock.url ?? "").trim();
    data.hasTitle = titleStr !== "";
    data.hasDesc = descStr !== "";
    data.hasText = textStr !== "";
    data.hasUrl = urlStr !== "" && urlStr !== "#";
    if (data.hasUrl && !/^https?:\/\//i.test(urlStr) && !urlStr.startsWith("mailto:") && !urlStr.startsWith("tel:")) {
      data.url = `https://${urlStr}`;
    }
    if (data.text === undefined) {
      data.text = rawBlock.title ?? "";
    }

    // Clases declarativas de Título y Texto
    const titleSizeMap = { small: "x18", medium: "x20", large: "x24" };
    data.titleSizeClass = titleSizeMap[rawBlock.title_size] || "x18";
    data.titleWeightClass = rawBlock.title_weight ? `bold${rawBlock.title_weight}` : "bold500";
    data.textWeightClass = rawBlock.text_weight ? `bold${rawBlock.text_weight}` : "bold400";
    data.textAlignClass = rawBlock.text_align ? `text-${rawBlock.text_align}` : "text-left";

    // 3. Precios y ofertas (Productos individuales y sub-productos de grupo)
    const isOfferRaw = toBool(rawBlock.offer, false);
    const priceStr = String(rawBlock.price ?? "").trim();
    let discountStr = String(rawBlock.discount ?? "").trim();
    const pctStr = String(rawBlock.porcentage ?? "").trim();

    if ((!discountStr || discountStr === priceStr) && pctStr && parseFloat(pctStr) > 0 && parseFloat(priceStr) > 0) {
      discountStr = String(Math.round(parseFloat(priceStr) * (1 - parseFloat(pctStr) / 100)));
    }
    const effectiveDiscount = discountStr || priceStr;
    const isOffer = Boolean(isOfferRaw && effectiveDiscount !== "");
    data.effectiveDiscount = effectiveDiscount;
    data.effectiveDiscountDisplay = effectiveDiscount !== "" ? `$${effectiveDiscount}` : "";
    data.priceDisplay = priceStr !== "" ? `$${priceStr}` : "";
    data.isOffer = isOffer;
    data.showOfferPrice = isOffer;
    data.isRegularPrice = Boolean(!isOffer && priceStr !== "");
    data.showRegularPrice = data.isRegularPrice;

    // 4. Propiedades derivadas de Campaña
    const imgPosition = rawBlock.img_position || "background";
    const effectiveCampSize = imgPosition === "header" ? "horizontal" : (rawBlock.size || "horizontal");
    const isNotHorizontal = effectiveCampSize !== "horizontal";
    data.isBgImg = Boolean(data.hasImg && imgPosition === "background");
    data.isBgImageMode = data.isBgImg;
    data.isHeaderImg = Boolean(data.hasImg && imgPosition === "header");
    data.isHeaderImageMode = data.isHeaderImg;
    data.campaignSizeClass = `campaign-size-${effectiveCampSize}`;
    data.campaignContentPtClass = data.isHeaderImg ? "pt0" : "pt20";
    data.campaignContentFlexClass = isNotHorizontal ? "flex-1" : "";
    data.campaignBtnAnchorClass = isNotHorizontal ? "campaign-btn-anchor-bottom" : "";
    data.campaignAlignClass = `campaign-align-${rawBlock.text_align || "center"}`;
    data.campaignTextPosClass = isNotHorizontal ? `campaign-text-pos-${rawBlock.text_position || "center"}` : "";
    data.campaignTitleSizeClass = `campaign-title-${rawBlock.title_size || "large"}`;
    data.campaignDescSizeClass = `campaign-desc-${rawBlock.desc_size || "medium"}`;
    data.countdownTextSizeClass = `countdown-text-${rawBlock.countdown_text_size || "medium"}`;
    data.countdownWidgetSizeClass = `campaign-countdown-widget-${rawBlock.countdown_widget_size || "medium"}`;

    const bgColor = rawBlock.bg_color || "#1e1e1e";
    const bgOpacity = rawBlock.bg_opacity !== undefined ? parseInt(rawBlock.bg_opacity, 10) : 80;
    data.bgOverlayCss = `oklch(from ${bgColor} l c h / ${isNaN(bgOpacity) ? 80 : bgOpacity}%)`;
    data.campaignOverlayBg = data.bgOverlayCss;
    const hasCountdown = toBool(rawBlock.has_countdown, false);
    data.showCountdown = Boolean(hasCountdown && rawBlock.countdown_date);
    data.button_text = String(rawBlock.button_text ?? "").trim() || "Suscribirme";

    // 5. Propiedades derivadas de Banner
    const ratioMap = {
      "720x720": "720 / 720",
      "1024x720": "1024 / 720",
      "720x1024": "720 / 1024"
    };
    data.aspectRatio = ratioMap[rawBlock.size] || "720 / 1024";
    data.bannerAspectRatio = data.aspectRatio;
    const bannerOpacity = rawBlock.bg_opacity !== undefined ? parseInt(rawBlock.bg_opacity, 10) : 100;
    data.imgOpacity = ((isNaN(bannerOpacity) ? 100 : Math.max(0, Math.min(100, bannerOpacity))) / 100).toFixed(2);
    data.bannerImgOpacity = data.imgOpacity;

    // 6. Propiedades derivadas de Video
    const ytId = extractYouTubeId(rawBlock.url || "");
    const vimeoId = extractVimeoId(rawBlock.url || "");
    data.youtubeId = ytId;
    data.vimeoId = vimeoId;
    if (data.hasImg && effectiveImgSrc) {
      data.videoThumbUrl = effectiveImgSrc;
    } else if (ytId) {
      data.videoThumbUrl = `https://i.ytimg.com/vi/${ytId}/hqdefault.jpg`;
    } else {
      data.videoThumbUrl = imgSrc || "/Uploads/Custom/no-image.webp";
    }
    data.videoThumbSrc = data.videoThumbUrl;
    data.videoFacadeEmbedUrl = ytId
      ? `https://www.youtube-nocookie.com/embed/${ytId}?rel=0&autoplay=1`
      : (vimeoId ? `https://player.vimeo.com/video/${vimeoId}?autoplay=1` : "");

    return data;
  }

  /**
   * Aplica las directivas declarativas a un nodo individual según el prefijo indicado.
   *
   * @param {HTMLElement} el Elemento DOM objetivo.
   * @param {Object} data Datos enriquecidos.
   * @param {string} prefix Prefijo de atributo ('data-bind', 'data-sub-bind', 'data-profile-bind').
   */
  function applyNodeBindings(el, data, prefix) {
    if (!el || !data) return;

    // 1. Visibilidad condicional (data-bind-show)
    const showKey = el.getAttribute(`${prefix}-show`);
    if (showKey && data[showKey] !== undefined) {
      const visible = Boolean(data[showKey]);
      el.style.display = visible ? "" : "none";
      if (visible && el.classList) {
        el.classList.remove("hidden");
      }
    }

    // 2. Contenido de texto / HTML seguro (data-bind o data-bind-html)
    const bindKey = el.getAttribute(prefix);
    const htmlKey = el.getAttribute(`${prefix}-html`);
    if (bindKey && data[bindKey] !== undefined) {
      const rawVal = String(data[bindKey] ?? "");
      const textPrefix = el.getAttribute(`${prefix}-prefix`) || el.getAttribute("data-bind-prefix") || "";
      const keepSvg =
        el.getAttribute(`${prefix}-keep-svg`) === "true" ||
        el.getAttribute("data-bind-keep-svg") === "true" ||
        el.getAttribute(`${prefix}-preserve-svg`) === "true" ||
        el.getAttribute("data-bind-preserve-svg") === "true";
      const useNl2br = el.getAttribute(`${prefix}-nl2br`) === "true" || el.getAttribute("data-bind-nl2br") === "true";
      const finalVal = rawVal !== "" ? `${textPrefix}${rawVal}` : "";

      const cutWrapper = (el.classList && el.classList.contains("cut-phrase") && el.querySelector)
        ? el.querySelector(".cut-phrase-wrapper")
        : null;
      const targetTextEl = cutWrapper || el;

      if (keepSvg && targetTextEl.querySelector) {
        const svgEl = targetTextEl.querySelector("svg") || (el.querySelector ? el.querySelector("svg") : null);
        targetTextEl.innerHTML = "";
        if (svgEl) {
          targetTextEl.appendChild(svgEl);
        }
        targetTextEl.appendChild(document.createTextNode(svgEl ? ` ${finalVal}` : finalVal));
      } else if (useNl2br) {
        targetTextEl.innerHTML = escapeHtml(finalVal).replace(/\r?\n/g, "<br>");
      } else {
        targetTextEl.textContent = finalVal;
      }

      if (el.classList && el.classList.contains("cut-phrase") && el.setAttribute) {
        el.setAttribute("data-cut-phrase-ready", "true");
        if (cutWrapper && cutWrapper.setAttribute) {
          cutWrapper.setAttribute("data-cut-phrase-ready", "true");
        }
      }
    } else if (htmlKey && data[htmlKey] !== undefined) {
      const rawHtmlVal = String(data[htmlKey] ?? "");
      el.innerHTML = escapeHtml(rawHtmlVal).replace(/\r?\n/g, "<br>");
      if (el.classList && el.classList.contains("cut-phrase") && el.setAttribute) {
        el.setAttribute("data-cut-phrase-ready", "true");
      }
    }

    // 3. Atributos dinámicos (data-bind-attr="href:url,src:imgSrc" o con ";")
    const attrSpec = el.getAttribute(`${prefix}-attr`);
    if (attrSpec) {
      const pairs = attrSpec.split(/[;,]/);
      for (let i = 0; i < pairs.length; i++) {
        const [attrName, dataKey] = pairs[i].split(":").map((s) => s.trim());
        if (!attrName || !dataKey || data[dataKey] === undefined) continue;
        const val = String(data[dataKey] ?? "");
        if (attrName === "href") {
          let hrefVal = val.trim() || "#";
          if (hrefVal !== "#" && !/^(https?:\/\/|mailto:|tel:|\/)/i.test(hrefVal)) {
            hrefVal = "https://" + hrefVal;
          }
          el.setAttribute("href", hrefVal);
        } else if (attrName === "src") {
          if (val !== "") {
            el.src = val;
            el.setAttribute("src", val);
          }
        } else {
          el.setAttribute(attrName, val);
        }
      }
    }

    // 4. Estilos CSS en línea (data-bind-style="background-color:bg_color,padding-top:void_space:%")
    const styleSpec = el.getAttribute(`${prefix}-style`);
    if (styleSpec) {
      const rules = styleSpec.split(/[;,]/);
      for (let i = 0; i < rules.length; i++) {
        const parts = rules[i].split(":").map((s) => s.trim());
        const cssProp = parts[0];
        const dataKey = parts[1];
        const unit = parts[2] || "";
        if (!cssProp || !dataKey || data[dataKey] === undefined || data[dataKey] === "") continue;
        const cssVal = `${data[dataKey]}${unit}`;
        if (el.style && typeof el.style.setProperty === "function") {
          el.style.setProperty(cssProp, cssVal, cssProp === "padding-top" ? "important" : "");
        }
        if (el.style && cssProp !== "padding-top") {
          el.style[cssProp] = cssVal;
        }
      }
    }

    // 5. Clases dinámicas (data-bind-class="x18|x20|x24:titleSizeClass;bold500|bold700:titleWeightClass")
    const classSpec = el.getAttribute(`${prefix}-class`);
    if (classSpec && el.classList) {
      const groups = classSpec.split(/[;,]/);
      for (let i = 0; i < groups.length; i++) {
        const [candidatesStr, dataKey] = groups[i].split(":").map((s) => s.trim());
        if (!candidatesStr || !dataKey || data[dataKey] === undefined) continue;
        const candidates = candidatesStr.split("|").map((s) => s.trim()).filter(Boolean);
        candidates.forEach((c) => el.classList.remove(c));
        const activeClass = String(data[dataKey] || "").trim();
        if (activeClass) {
          el.classList.add(activeClass);
        }
      }
    }
  }

  /**
   * Recorre un contenedor DOM y aplica todos los bindings declarativos que coincidan con el prefijo.
   * Si el prefijo es 'data-bind', ignora nodos dentro de [data-sub-index] para no colisionar con sub-ítems.
   *
   * @param {HTMLElement} container Contenedor raíz del bloque o perfil.
   * @param {Object} data Datos enriquecidos.
   * @param {string} [prefix="data-bind"] Prefijo de los atributos.
   */
  function bindPreviewContainer(container, data, prefix = "data-bind") {
    if (!container || !data) return;

    // Aplicar también sobre el propio contenedor raíz si posee atributos declarativos
    if (
      container.hasAttribute && (
        container.hasAttribute(prefix) ||
        container.hasAttribute(`${prefix}-html`) ||
        container.hasAttribute(`${prefix}-attr`) ||
        container.hasAttribute(`${prefix}-show`) ||
        container.hasAttribute(`${prefix}-style`) ||
        container.hasAttribute(`${prefix}-class`)
      )
    ) {
      applyNodeBindings(container, data, prefix);
    }

    if (!container.querySelectorAll) return;
    const selector = `[${prefix}], [${prefix}-html], [${prefix}-attr], [${prefix}-show], [${prefix}-style], [${prefix}-class]`;
    const nodes = container.querySelectorAll(selector);
    nodes.forEach((node) => {
      if (prefix === "data-bind" && node.closest && node.closest("[data-sub-index]") && (!container.hasAttribute || !container.hasAttribute("data-sub-index"))) {
        return;
      }
      applyNodeBindings(node, data, prefix);
    });
  }

  /**
   * Actualiza clases estructurales y casos especiales (como iconos de separador o reproductor de video)
   * sobre un nodo de bloque en la vista previa.
   *
   * @param {HTMLElement} block Nodo del bloque en el preview.
   * @param {Object} cData Datos enriquecidos del bloque.
   * @param {string|number} idx Índice del bloque.
   * @param {string} blkId ID único del bloque.
   */
  function applyBlockStructuralUpdates(block, cData, idx, blkId) {
    if (!block || !block.classList) return;

    // 1. Separador: modo espacio vs línea de iconos SVG
    if (block.classList.contains("separator-block-wrapper") || (block.hasAttribute && block.hasAttribute("data-bind-separator"))) {
      const sepIcon = cData.separator_icon || "none";
      const sepMode = cData.separator_mode || (sepIcon === "none" || sepIcon === "ban" ? "space" : "line");
      const sepSize = cData.separator_size || "large";
      const spaceSize = cData.space_size || "40";
      const isSpace = sepMode === "space" || sepIcon === "none" || sepIcon === "ban";

      if (isSpace) {
        block.className = "separator-block-wrapper w100";
        block.style.margin = "";
        block.style.height = `${spaceSize}px`;
        block.innerHTML = "";
      } else {
        const editorItem = blkId
          ? document.querySelector(`.sortable-item[data-block-id="${blkId}"]`)
          : document.querySelector(`#content-item-${idx}`);
        let svgHtml = "";
        if (editorItem) {
          const iconInput = editorItem.querySelector(`input.separator-icon-radio[value="${sepIcon}"]`);
          const iconLbl = iconInput ? (iconInput.nextElementSibling || editorItem.querySelector(`label[for="${iconInput.id}"]`)) : null;
          const sEl = iconLbl ? iconLbl.querySelector("svg") : null;
          if (sEl) svgHtml = sEl.outerHTML;
        }
        block.className = "separator-block-wrapper w100 flex-row center-center color-text-card p0";
        block.style.margin = "10px 0";
        block.style.height = "";
        if (svgHtml) {
          if (sepSize === "small") {
            block.innerHTML = `<span class="flex-row center-center" style="width: 18px; height: 18px; font-size: 18px; flex-shrink: 0; line-height: 1;">${svgHtml}</span>`;
          } else {
            const widthPercent = sepSize === "medium" ? "60%" : "100%";
            let spans = "";
            for (let k = 0; k < 35; k++) {
              spans += `<span class="flex-row center-center" style="width: 16px; height: 16px; font-size: 16px; flex-shrink: 0; line-height: 1;">${svgHtml}</span>`;
            }
            block.innerHTML = `<div style="display: flex; flex-wrap: wrap; justify-content: center; align-content: flex-start; align-items: center; gap: 8px; height: 18px; overflow: hidden; width: ${widthPercent}; max-width: ${widthPercent};">${spans}</div>`;
          }
        }
      }
    }

    // 2. Campaña: ajuste de flex en contenedor vertical/cuadrado y actualización en vivo de cuenta regresiva
    if (block.classList.contains("campaign-block-wrapper") && block.querySelector) {
      const contentEl = block.querySelector(".campaign-content");
      const isNotHorizontal = cData.campaignSizeClass !== "campaign-size-horizontal";
      if (contentEl && contentEl.style) {
        contentEl.style.flex = isNotHorizontal ? "1 1 auto" : "";
        contentEl.style.minHeight = isNotHorizontal ? "max-content" : "";
      }

      const countdownBox = block.querySelector(".campaign-countdown-box");
      if (countdownBox) {
        if (countdownBox.__countdownTimer) {
          clearInterval(countdownBox.__countdownTimer);
          countdownBox.__countdownTimer = null;
        }
        const updateBoxNumbers = () => {
          const dateStr = cData.countdown_date || countdownBox.getAttribute("data-countdown") || "";
          const dEl = countdownBox.querySelector(".countdown-days");
          const hEl = countdownBox.querySelector(".countdown-hours");
          const mEl = countdownBox.querySelector(".countdown-minutes");
          const sEl = countdownBox.querySelector(".countdown-seconds");
          if (!dateStr) {
            if (dEl) dEl.textContent = "00";
            if (hEl) hEl.textContent = "00";
            if (mEl) mEl.textContent = "00";
            if (sEl) sEl.textContent = "00";
            return;
          }
          const targetMs = new Date(dateStr).getTime();
          const diff = isNaN(targetMs) ? 0 : Math.max(0, targetMs - Date.now());
          const days = Math.floor(diff / (1000 * 60 * 60 * 24));
          const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
          const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
          const seconds = Math.floor((diff % (1000 * 60)) / 1000);
          if (dEl) dEl.textContent = String(days).padStart(2, "0");
          if (hEl) hEl.textContent = String(hours).padStart(2, "0");
          if (mEl) mEl.textContent = String(minutes).padStart(2, "0");
          if (sEl) sEl.textContent = String(seconds).padStart(2, "0");
        };
        updateBoxNumbers();
        if (cData.showCountdown && cData.countdown_date) {
          countdownBox.__countdownTimer = setInterval(updateBoxNumbers, 1000);
        }
      }
    }
  }

  /**
   * Renderiza y actualiza un bloque específico dentro de un contenedor .user-profile-preview.
   *
   * @param {HTMLElement} previewEl Contenedor .user-profile-preview.
   * @param {Object} rawBlockData Datos del bloque desde el borrador.
   * @param {string|number} idx Índice del bloque.
   * @param {Object} [cardState={}] Estado general de la tarjeta.
   */
  function renderBlockInPreview(previewEl, rawBlockData, idx, cardState = {}) {
    if (!previewEl || !rawBlockData) return;

    const blkId = rawBlockData.id || document.querySelector(`#content-item-${idx}`)?.getAttribute("data-block-id") || "";
    let blocks = blkId ? previewEl.querySelectorAll(`[data-block-id="${blkId}"]`) : [];
    if (!blocks || !blocks.length) {
      blocks = previewEl.querySelectorAll(`[data-content-index="${idx}"]`);
    }
    if (!blocks || !blocks.length) return;

    const cData = computeDerivedBlockData(rawBlockData, cardState);
    const activeSwitch = blkId
      ? document.querySelector(`.sortable-item[data-block-id="${blkId}"] input[type="checkbox"][name$="[active]"]`)
      : document.querySelector(`input[type="checkbox"][name="content[${idx}][active]"]`);

    const isAct = rawBlockData.active !== undefined
      ? toBool(rawBlockData.active, false)
      : (activeSwitch ? activeSwitch.checked : true);

    const subItems = Array.isArray(rawBlockData.products)
      ? rawBlockData.products
      : (Array.isArray(rawBlockData.items) ? rawBlockData.items : null);

    blocks.forEach((block) => {
      // 1. Control de visibilidad del bloque y sus variantes (grid/slide o player/link)
      const layoutVariant = block.dataset ? block.dataset.layoutVariant : null;
      const videoVariant = block.dataset ? block.dataset.videoVariant : null;

      if (layoutVariant) {
        const currentLayout = (subItems && subItems.length % 2 !== 0) ? "slide" : (cData.layout || "grid");
        const showVariant = isAct && layoutVariant === currentLayout;
        block.style.display = showVariant ? "" : "none";
        if (block.classList && block.classList.toggle) block.classList.toggle("hidden", !showVariant);
      } else if (videoVariant) {
        const currentVideoMode = cData.video_mode || "link";
        const showVariant = isAct && videoVariant === currentVideoMode;
        block.style.display = showVariant ? "" : "none";
        if (block.classList && block.classList.toggle) block.classList.toggle("hidden", !showVariant);
      } else if (block.classList && block.classList.contains("title-block-wrapper")) {
        block.style.display = isAct && cData.hasTitle ? "" : "none";
      } else if (block.classList && block.classList.contains("text-block-wrapper")) {
        block.style.display = isAct && cData.hasText ? "" : "none";
      } else if (block.classList && block.classList.contains("banner-block-wrapper")) {
        block.style.display = isAct && cData.hasImg ? "" : "none";
      } else {
        block.style.display = isAct ? "" : "none";
      }

      // 2. Enlace declarativo universal (data-bind*)
      bindPreviewContainer(block, cData, "data-bind");

      // 3. Enlace declarativo para sub-ítems (ej. product_group o cualquier bloque futuro con lista de hijos)
      if (subItems && block.querySelectorAll) {
        const subContainer = block.querySelector(".product-group-grid, .product-group-slide-track");
        if (subContainer) {
          let existingCards = Array.from(subContainer.querySelectorAll("[data-sub-index]"));
          while (existingCards.length < subItems.length && existingCards.length > 0) {
            const clone = existingCards[0].cloneNode(true);
            clone.setAttribute("data-sub-index", String(existingCards.length));
            subContainer.appendChild(clone);
            existingCards.push(clone);
          }
          while (existingCards.length > subItems.length && existingCards.length > 0) {
            const removed = existingCards.pop();
            if (removed && removed.remove) removed.remove();
          }
        }

        const subCards = block.querySelectorAll("[data-sub-index]");
        subCards.forEach((subCard) => {
          const sIdx = parseInt(subCard.getAttribute("data-sub-index"), 10);
          if (!isNaN(sIdx) && subItems[sIdx]) {
            const subDerived = computeDerivedBlockData(subItems[sIdx], cardState);
            bindPreviewContainer(subCard, subDerived, "data-sub-bind");
          }
        });
      }

      // 4. Actualizaciones estructurales complementarias
      applyBlockStructuralUpdates(block, cData, idx, blkId);

      // 5. Asegurar visibilidad de textos con .cut-phrase (evitar opacity: 0 de FOUC en bloques nuevos)
      if (block.querySelectorAll) {
        block.querySelectorAll(".cut-phrase, .cut-phrase-wrapper").forEach((cp) => {
          if (cp.setAttribute) cp.setAttribute("data-cut-phrase-ready", "true");
        });
      }
    });
  }

  /**
   * Actualiza las CSS Custom Properties y los datos de cabecera del perfil en un contenedor .user-profile-preview.
   *
   * @param {HTMLElement} previewEl Contenedor .user-profile-preview.
   * @param {Object} fields Estado plano o estructurado del borrador.
   */
  function applyThemeVarsToPreview(previewEl, fields = {}) {
    if (!previewEl || !fields) return;

    if (fields.back) {
      previewEl.style.setProperty("--preview-btn-back", fields.back);
    }
    if (fields.color) {
      previewEl.style.setProperty("--preview-btn-color", fields.color);
    }
    if (fields.colorText) {
      previewEl.style.setProperty("--preview-txt-color", fields.colorText);
    }
    if (fields.titleColor) {
      previewEl.style.setProperty("--preview-title-color", fields.titleColor);
    }
    if (fields.colorShadow3) {
      previewEl.style.setProperty("--preview-shadow-color", fields.colorShadow3);
    }
    if (fields.hover !== undefined && previewEl.classList && previewEl.classList.toggle) {
      previewEl.classList.toggle("has-btn-hover", toBool(fields.hover, false));
    }

    const rawVoidSpace = fields.void_space !== undefined
      ? fields.void_space
      : (fields["voidHero[space]"] !== undefined ? fields["voidHero[space]"] : fields.voidHero?.space);
    let voidSpace = rawVoidSpace !== undefined ? parseInt(rawVoidSpace, 10) : NaN;

    if (!isNaN(voidSpace)) {
      if (voidSpace === 130) voidSpace = 20;
      else if (voidSpace === 250) voidSpace = 45;
      else if (voidSpace === 450) voidSpace = 70;
      previewEl.style.setProperty("--preview-void-space", `${voidSpace}%`);
      if (previewEl.querySelectorAll) {
        previewEl.querySelectorAll(".void-space").forEach((el) => {
          el.style.setProperty("padding-top", `${voidSpace}%`, "important");
        });
      }
    }

    // Conmutar variante de cabecera si fields.header está presente
    if (fields.header && previewEl.querySelectorAll) {
      previewEl.querySelectorAll(".header-variant-wrapper").forEach((wrap) => {
        if (wrap.dataset && wrap.dataset.headerVariant === fields.header) {
          wrap.classList.remove("hidden");
          wrap.style.display = "";
        } else {
          wrap.classList.add("hidden");
          wrap.style.display = "none";
        }
      });
    }

    // Actualizar bindings declarativos del perfil (título, descripción, avatar, void_space) en las variantes de Hero
    const profileData = {};
    if (fields.title !== undefined) profileData.title = fields.title;
    if (fields.desc !== undefined) profileData.desc = fields.desc;
    if (fields.avatarSrc !== undefined) profileData.avatarSrc = fields.avatarSrc;
    if (!isNaN(voidSpace)) profileData.void_space = voidSpace;

    if (Object.keys(profileData).length > 0 && previewEl.querySelectorAll) {
      previewEl.querySelectorAll(".header-variant-wrapper").forEach((wrap) => {
        bindPreviewContainer(wrap, profileData, "data-profile-bind");
      });
    }
  }

  const api = {
    __initialized: true,
    bindPreviewContainer,
    computeDerivedBlockData,
    renderBlockInPreview,
    applyThemeVarsToPreview
  };

  window.LivePreviewManager = api;
  return api;
}

// Inicializar inmediatamente para que esté disponible antes de cualquier otro script
LivePreviewManager();
