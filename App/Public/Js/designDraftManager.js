/**
 * Componente DesignDraftManager.
 *
 * Administrador de borrador de diseño local (Local-First).
 * Acumula todas las modificaciones visuales (colores, clases de botones, bordes, sombras,
 * cabeceras, textos, switches) en el almacenamiento local del navegador (localStorage).
 *
 * Aplica los cambios de diseño en vivo al DOM de la vista previa (.user-profile-preview)
 * con 0ms de latencia sin disparar peticiones HTTP innecesarias al backend.
 *
 * Al presionar "Guardar", vuelca todo el lote acumulado en una única petición al servidor y limpia la caché.
 * Al presionar "Descartar", limpia la caché local y revierte la vista previa a la versión oficial.
 *
 * @function designDraftManager
 * @returns {void}
 */
export function designDraftManager() {
  const pathParts = window.location.pathname.split("/").filter(Boolean);
  if (pathParts[0] !== "panel" || !pathParts[1]) {
    return; // Solo opera dentro del panel de usuario
  }

  const user = pathParts[1].toLowerCase();
  const DRAFT_KEY = `cuaderno_design_draft_${user}`;
  const INITIAL_KEY = `cuaderno_design_initial_${user}`;

  // =========================================================================
  // 1. GESTIÓN DEL ALMACENAMIENTO LOCAL (localStorage)
  // =========================================================================

  /**
   * Obtiene los cambios acumulados en el borrador local.
   * @returns {Object} Diccionario con los campos modificados.
   */
  function getDraft() {
    try {
      const data = localStorage.getItem(DRAFT_KEY);
      return data ? JSON.parse(data) : {};
    } catch (e) {
      return {};
    }
  }

  /**
   * Guarda un campo en el borrador local.
   * @param {string} name Nombre del campo
   * @param {*} value Valor del campo
   */
  function setDraftField(name, value) {
    if (!name) return;
    const draft = getDraft();
    draft[name] = value;
    try {
      localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
    } catch (e) {
      console.warn("Error al escribir en localStorage:", e);
    }
    notifyDraftState();
  }

  /**
   * Limpia el borrador local.
   */
  function clearDraft() {
    try {
      localStorage.removeItem(DRAFT_KEY);
    } catch (e) {}
    notifyDraftState();
  }

  /**
   * Comprueba si existen cambios acumulados pendientes de guardar.
   * @returns {boolean}
   */
  function hasDraft() {
    const draft = getDraft();
    return Object.keys(draft).length > 0;
  }

  /**
   * Notifica a la aplicación (especialmente al saveButtonController) del estado actual.
   */
  function notifyDraftState() {
    const pending = hasDraft();
    document.dispatchEvent(new CustomEvent("designDraftStateChanged", {
      detail: { hasDraft: pending, draft: getDraft() }
    }));
  }

  // =========================================================================
  // 2. SINCRONIZACIÓN DE UI CONDICIONAL EN FORMULARIOS
  // =========================================================================

  /**
   * Sincroniza la visibilidad de elementos dependientes en el formulario de edición.
   * @param {HTMLElement} target Elemento modificado
   */
  function syncConditionalUI(target) {
    if (!target) return;

    // 1. Estilo de fondo (Sólido, Degradado, Video)
    if (target.name === "style_back") {
      const gradientWrapper = document.getElementById("gradient-direction-wrapper");
      const videoWrapper = document.getElementById("video-controls-wrapper");
      if (target.value === "gradientUp" || target.value === "gradientDown") {
        if (gradientWrapper) gradientWrapper.style.display = "flex";
        if (videoWrapper) videoWrapper.style.display = "none";
      } else if (target.value === "solid") {
        if (gradientWrapper) gradientWrapper.style.display = "none";
        if (videoWrapper) videoWrapper.style.display = "none";
      } else if (target.value === "video") {
        if (gradientWrapper) gradientWrapper.style.display = "none";
        if (videoWrapper) videoWrapper.style.display = "flex";
      }
    }

    // 2. Selector de color de sombra 3
    if (target.name === "shadow") {
      const shadow3Row = document.getElementById("shadow3-color-row");
      const colorShadow3Row = document.getElementById("color-shadow3-color-row");
      const isShadow3 = target.value === "shadow-3";
      if (shadow3Row) shadow3Row.style.display = isShadow3 ? "flex" : "none";
      if (colorShadow3Row) colorShadow3Row.style.display = isShadow3 ? "flex" : "none";
    }

    // 3. Separación superior en voidHero
    if (target.name === "header") {
      const voidSpaceContainer = document.getElementById("void-space-container");
      if (voidSpaceContainer) {
        voidSpaceContainer.style.display = target.value === "voidHero" ? "flex" : "none";
      }
    }

    // 4. Bloques de campaña: posición de imagen
    if (target.classList && target.classList.contains("campaign-pos-radio")) {
      const idx = target.dataset.index;
      const opacityOpt = document.getElementById(`campaign-opacity-option-${idx}`);
      if (opacityOpt) opacityOpt.style.display = target.value === "background" ? "flex" : "none";
      const sizeWrap = document.getElementById(`campaign-size-wrap-${idx}`);
      if (sizeWrap) sizeWrap.style.display = target.value === "background" ? "flex" : "none";
      if (target.value === "header") {
        const horizRadio = document.getElementById(`campaign-size-horiz-${idx}`);
        if (horizRadio) horizRadio.checked = true;
        const textPosWrap = document.getElementById(`campaign-text-pos-wrap-${idx}`);
        if (textPosWrap) textPosWrap.style.display = "none";
      }
    }

    // 5. Bloques de campaña: tamaño de bloque / alineación de texto
    if (target.classList && target.classList.contains("campaign-size-radio")) {
      const idx = target.dataset.index;
      const textPosWrap = document.getElementById(`campaign-text-pos-wrap-${idx}`);
      if (textPosWrap) textPosWrap.style.display = target.value === "horizontal" ? "none" : "flex";
    }

    // 6. Bloques de campaña: switch de contador regresivo
    if (target.classList && target.classList.contains("campaign-countdown-switch")) {
      const targetId = target.dataset.target;
      if (targetId) {
        const dateWrap = document.getElementById(targetId);
        if (dateWrap) dateWrap.style.display = target.checked ? "flex" : "none";
      }
    }

    // 7. Bloques de campaña: switch de campos opcionales
    if (target.classList && target.classList.contains("campaign-toggle-field-switch")) {
      const targetId = target.dataset.target;
      if (targetId) {
        const fieldWrap = document.getElementById(targetId);
        if (fieldWrap) fieldWrap.style.display = target.checked ? "flex" : "none";
      }
    }
  }

  // =========================================================================
  // 3. APLICACIÓN EN VIVO AL DOM DE LA VISTA PREVIA (0ms LATENCIA)
  // =========================================================================

  let dynamicStyleEl = null;

  /**
   * Obtiene o crea el elemento <style> dedicado para sobreescrituras en vivo.
   * @returns {HTMLStyleElement}
   */
  function getDynamicStyleElement() {
    if (!dynamicStyleEl || !document.contains(dynamicStyleEl)) {
      dynamicStyleEl = document.getElementById("design-draft-live-styles");
      if (!dynamicStyleEl) {
        dynamicStyleEl = document.createElement("style");
        dynamicStyleEl.id = "design-draft-live-styles";
        document.head.appendChild(dynamicStyleEl);
      }
    }
    return dynamicStyleEl;
  }

  function hexToHsl(hex) {
    let clean = hex.replace(/^#/, "");
    if (clean.length === 3) {
      clean = clean[0] + clean[0] + clean[1] + clean[1] + clean[2] + clean[2];
    }
    if (clean.length !== 6) return { h: 0, s: 0, l: 0 };
    let r = parseInt(clean.slice(0, 2), 16) / 255;
    let g = parseInt(clean.slice(2, 4), 16) / 255;
    let b = parseInt(clean.slice(4, 6), 16) / 255;
    let max = Math.max(r, g, b), min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;
    if (max === min) {
      h = s = 0;
    } else {
      let d = max - min;
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      switch (max) {
        case r: h = (g - b) / d + (g < b ? 6 : 0); break;
        case g: h = (b - r) / d + 2; break;
        case b: h = (r - g) / d + 4; break;
      }
      h = Math.round(h * 60);
    }
    return { h, s: Math.round(s * 100), l: Math.round(l * 100) };
  }

  function hslToHex(h, s, l) {
    l /= 100;
    const a = s * Math.min(l, 1 - l) / 100;
    const f = (n) => {
      const k = (n + h / 30) % 12;
      const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
      return Math.round(255 * color).toString(16).padStart(2, "0");
    };
    return `#${f(0)}${f(8)}${f(4)}`;
  }

  function getGradientColors(hex) {
    let clean = hex.replace(/^#/, "");
    if (clean.length === 3) {
      clean = clean[0] + clean[0] + clean[1] + clean[1] + clean[2] + clean[2];
    }
    if (clean.length !== 6) return ["#272727", "#444444"];
    const hsl = hexToHsl(clean);
    let endS, endL;
    if (hsl.l >= 70) {
      endS = hsl.s > 5 ? Math.max(40, hsl.s) : hsl.s;
      endL = Math.max(40, hsl.l - 22);
    } else if (hsl.l <= 30) {
      endS = hsl.s > 5 ? Math.max(25, hsl.s) : hsl.s;
      endL = Math.min(65, hsl.l + 22);
    } else {
      endS = Math.min(100, hsl.s + 5);
      endL = Math.max(15, hsl.l - 20);
    }
    const endHex = hslToHex(hsl.h, endS, endL);
    return [`#${clean}`, endHex];
  }

  /**
   * Aplica un conjunto de estilos acumulados a la vista previa.
   * @param {Object} fields Diccionario con los campos del borrador
   */
  function applyDraftToPreview(fields) {
    if (!fields || typeof fields !== "object") return;

    const previews = document.querySelectorAll(".user-profile-preview");
    if (!previews.length) return;

    const styleSheet = getDynamicStyleElement();
    const cssRules = [];

    // --- A. COLORES GENERALES Y FONDOS ---
    const activeStyleRadio = document.querySelector('input[name="style_back"]:checked');
    const styleBack = fields.style_back || (activeStyleRadio ? activeStyleRadio.value : "solid");

    const backPerfilInput = document.querySelector('input[name="back_perfil"]');
    const backPerfil = fields.back_perfil || (backPerfilInput ? backPerfilInput.value : "#272727");

    // Actualizar miniaturas en backgroundPanel.php
    const [gradStart, gradEnd] = getGradientColors(backPerfil);
    const thumbSolid = document.getElementById("preview-style-solid");
    if (thumbSolid) thumbSolid.style.backgroundColor = backPerfil;
    const thumbGradient = document.getElementById("preview-style-gradient");
    if (thumbGradient) thumbGradient.style.background = `linear-gradient(180deg, ${gradStart}, ${gradEnd})`;

    if (styleBack === "gradientUp") {
      cssRules.push(`
        .user-profile-preview .back-card,
        .user-profile-preview .back-card-container {
          background: linear-gradient(0deg, ${gradStart}, ${gradEnd}) !important;
        }
      `);
    } else if (styleBack === "gradientDown") {
      cssRules.push(`
        .user-profile-preview .back-card,
        .user-profile-preview .back-card-container {
          background: linear-gradient(180deg, ${gradStart}, ${gradEnd}) !important;
        }
      `);
    } else {
      cssRules.push(`
        .user-profile-preview .back-card,
        .user-profile-preview .back-card-container {
          background: ${backPerfil} !important;
          background-color: ${backPerfil} !important;
        }
      `);
    }

    // Video de fondo: visibilidad y reproducción según style_back
    if (styleBack !== undefined) {
      previews.forEach((p) => {
        const videoBg = p.querySelector(".back-video-bg");
        const videoOverlay = p.querySelector(".back-video-overlay");
        if (videoBg) {
          if (styleBack === "video") {
            videoBg.style.display = "";
            videoBg.play().catch(() => {});
          } else {
            videoBg.style.display = "none";
            videoBg.pause();
          }
        }
        if (videoOverlay) {
          videoOverlay.style.display = styleBack === "video" ? "" : "none";
        }
      });
    }

    // Opacidad y color del overlay de video
    const videoOverlayColor = fields.back_video_overlay || document.getElementById("select-color-overlay")?.value || "#000000";
    const overlayInput = document.getElementById("input-opacity-val");
    const videoOverlayOpacity = fields.back_video_opacity !== undefined
      ? fields.back_video_opacity
      : (overlayInput ? overlayInput.value : 45);

    const opacity = Math.max(0, Math.min(95, parseInt(videoOverlayOpacity, 10) || 45));
    const opacityFraction = (opacity / 100).toFixed(2);
    cssRules.push(`
      .user-profile-preview .back-video-overlay {
        background-color: ${videoOverlayColor} !important;
        opacity: ${opacityFraction} !important;
      }
    `);

    // Color de texto general
    if (fields.colorText) {
      cssRules.push(`
        .user-profile-preview .color-text-card,
        .user-profile-preview .desc-hero-regular,
        .user-profile-preview .desc-hero-big,
        .user-profile-preview .desc-hero-mini,
        .user-profile-preview .color-text-card p,
        .user-profile-preview .color-text-card span:not(.theme-icon) {
          color: ${fields.colorText} !important;
        }
      `);
    }

    // Color del título principal
    if (fields.titleColor) {
      cssRules.push(`
        .user-profile-preview .title-color,
        .user-profile-preview .title-hero-regular,
        .user-profile-preview .title-hero-big,
        .user-profile-preview .title-hero-mini,
        .user-profile-preview h1,
        .user-profile-preview h2,
        .user-profile-preview h3 {
          color: ${fields.titleColor} !important;
        }
      `);
    }

    // Color de botones generales
    if (fields.back) {
      cssRules.push(`
        .user-profile-preview .theme-button {
          background-color: ${fields.back} !important;
        }
      `);
    }

    // Color de texto de botones generales
    if (fields.color) {
      cssRules.push(`
        .user-profile-preview .theme-button,
        .user-profile-preview .theme-button *,
        .user-profile-preview .theme-icon {
          color: ${fields.color} !important;
        }
      `);
    }

    // Sombra shadow-3 y color de sombra 3
    const colorShadow3 = fields.colorShadow3 || "#000000";
    if (fields.colorShadow3) {
      cssRules.push(`
        .user-profile-preview .shadow-3 {
          border-color: ${colorShadow3} !important;
          box-shadow: 3px 5px 0px ${colorShadow3} !important;
        }
        .user-profile-preview .shadow-3 img {
          border-color: ${colorShadow3} !important;
        }
      `);
    }

    // Actualizar la hoja de estilos en vivo
    styleSheet.textContent = cssRules.join("\n");

    // --- B. BORDES Y SOMBRAS EN BOTONES (CLASES DIRECTAS) ---
    if (fields.borders) {
      const borderParts = String(fields.borders).split(",");
      const btnBorder = borderParts[0] || "br0";
      const imgBorder = borderParts[1] || "br0";
      const validBorders = ["br0", "br10", "br20", "br50"];
      const validImgBorders = ["br0", "br5", "br12", "br50"];

      // En productos y banners, br50 se adapta a br20 (y br12 en imágenes) para evitar deformaciones
      const clampedBorder = (btnBorder === "br50") ? "br20" : btnBorder;
      const clampedImgBorder = (imgBorder === "br50") ? "br12" : imgBorder;

      // 1. Actualizar indicador visual en el panel de botones del dashboard
      document.querySelectorAll(".button-fill-style").forEach((el) => {
        validBorders.forEach((b) => el.classList.remove(b));
        el.classList.add(btnBorder);
      });

      previews.forEach((p) => {
        // 2. Enlaces regulares
        p.querySelectorAll(".link-item-wrapper").forEach((btn) => {
          validBorders.forEach((b) => btn.classList.remove(b));
          btn.classList.add(btnBorder);
        });
        p.querySelectorAll(".link-item-wrapper img.cover").forEach((img) => {
          validImgBorders.forEach((b) => img.classList.remove(b));
          img.classList.add(imgBorder);
        });

        // 3. Productos regulares
        p.querySelectorAll(".product-item-wrapper, .product-regular-wrapper").forEach((btn) => {
          validBorders.forEach((b) => btn.classList.remove(b));
          btn.classList.add(clampedBorder);
        });
        p.querySelectorAll(".product-item-wrapper img.cover, .product-regular-wrapper img.cover").forEach((img) => {
          validImgBorders.forEach((b) => img.classList.remove(b));
          img.classList.add(clampedImgBorder);
        });

        // 4. Grupos de productos (Cuadrícula y Carrusel)
        p.querySelectorAll(".product-grid-card, .product-slide-card").forEach((btn) => {
          validBorders.forEach((b) => btn.classList.remove(b));
          btn.classList.add(clampedBorder);
        });
        p.querySelectorAll(".product-grid-card img.cover, .product-slide-card img.cover").forEach((img) => {
          validImgBorders.forEach((b) => img.classList.remove(b));
          img.classList.add(clampedImgBorder);
        });

        // 5. Banners
        p.querySelectorAll(".banner-block-wrapper").forEach((banner) => {
          validBorders.forEach((b) => banner.classList.remove(b));
          banner.classList.add(clampedBorder);
        });

        // 6. Campañas (bloque contenedor y botón de acción)
        p.querySelectorAll(".campaign-block-wrapper").forEach((camp) => {
          validBorders.forEach((b) => camp.classList.remove(b));
          camp.classList.add(clampedBorder);
        });
        p.querySelectorAll(".campaign-content .modal-btn, .campaign-button").forEach((btn) => {
          validBorders.forEach((b) => btn.classList.remove(b));
          btn.classList.add(btnBorder);
        });

        // Fallback genérico para cualquier otro elemento con clase .theme-button
        p.querySelectorAll(".theme-button:not(.link-item-wrapper):not(.product-item-wrapper):not(.product-regular-wrapper):not(.product-grid-card):not(.product-slide-card)").forEach((btn) => {
          validBorders.forEach((b) => btn.classList.remove(b));
          btn.classList.add(btnBorder);
        });
        p.querySelectorAll(".theme-button:not(.link-item-wrapper):not(.product-item-wrapper):not(.product-regular-wrapper):not(.product-grid-card):not(.product-slide-card) img.cover").forEach((img) => {
          validImgBorders.forEach((b) => img.classList.remove(b));
          img.classList.add(imgBorder);
        });
      });
    }

    if (fields.shadow) {
      const shadowClass = String(fields.shadow);
      const validShadows = ["shadow-0", "shadow-1", "shadow-2", "shadow-3", "shadow-card"];

      previews.forEach((p) => {
        // Aplicar sombras a todos los bloques: enlaces, productos regulares, grupos de productos, banners y campañas
        const targets = p.querySelectorAll(
          ".theme-button, .link-item-wrapper, .product-item-wrapper, .product-regular-wrapper, .product-grid-card, .product-slide-card, .banner-block-wrapper, .campaign-block-wrapper, .campaign-content .modal-btn, .campaign-button"
        );
        targets.forEach((el) => {
          validShadows.forEach((s) => el.classList.remove(s));
          el.classList.add(shadowClass);
        });
      });
    }

    // --- C. ESTILO DE CABECERA Y SEPARACIÓN VOID ---
    if (fields.header) {
      previews.forEach((p) => {
        p.querySelectorAll(".header-variant-wrapper").forEach((wrap) => {
          if (wrap.dataset.headerVariant === fields.header) {
            wrap.classList.remove("hidden");
          } else {
            wrap.classList.add("hidden");
          }
        });
      });
    }

    if (fields.void_space !== undefined) {
      const spaceVal = String(fields.void_space);
      previews.forEach((p) => {
        p.querySelectorAll(".void-space").forEach((el) => {
          el.style.setProperty("padding-top", `${spaceVal}%`, "important");
        });
      });
    }

    // --- D. TEXTOS DEL PERFIL (TÍTULO Y BIO) ---
    if (fields.title !== undefined) {
      previews.forEach((p) => {
        p.querySelectorAll("header .title-color, .title-hero-regular, .title-hero-big, .title-hero-mini").forEach((el) => {
          el.textContent = fields.title;
        });
      });
    }

    if (fields.desc !== undefined) {
      previews.forEach((p) => {
        p.querySelectorAll("main p.bold500, .desc-hero-regular, .desc-hero-big, .desc-hero-mini").forEach((el) => {
          el.textContent = fields.desc;
        });
      });
    }

    // --- E. COLORES DE CAMPAÑA Y BANNER ---
    Object.keys(fields).forEach((key) => {
      const val = fields[key];
      if (!val) return;

      if (key.includes("title_color")) {
        previews.forEach((p) => p.querySelectorAll(".campaign-title").forEach((el) => el.style.setProperty("color", val, "important")));
      } else if (key.includes("desc_color")) {
        previews.forEach((p) => p.querySelectorAll(".campaign-desc").forEach((el) => el.style.setProperty("color", val, "important")));
      } else if (key.includes("btn_bg_color")) {
        previews.forEach((p) => p.querySelectorAll(".campaign-button").forEach((el) => el.style.setProperty("background-color", val, "important")));
      } else if (key.includes("btn_text_color")) {
        previews.forEach((p) => p.querySelectorAll(".campaign-button").forEach((el) => el.style.setProperty("color", val, "important")));
      } else if (key.includes("countdown_bg_color")) {
        previews.forEach((p) => p.querySelectorAll(".campaign-countdown-wrapper, [data-countdown]").forEach((el) => el.style.setProperty("background-color", val, "important")));
      } else if (key.includes("countdown_text_color")) {
        previews.forEach((p) => p.querySelectorAll(".campaign-countdown-wrapper *, [data-countdown] *").forEach((el) => el.style.setProperty("color", val, "important")));
      }
    });

    // --- F. VISIBILIDAD DE BLOQUES DE CONTENIDO (SWITCHES) ---
    Object.keys(fields).forEach((key) => {
      const match = key.match(/^content\[(\d+)\]\[active\]$/);
      if (match) {
        const idx = match[1];
        const isAct = (fields[key] === "true" || fields[key] === true || fields[key] === 1 || fields[key] === "1");
        previews.forEach((p) => {
          const item = p.querySelector(`[data-content-index="${idx}"]`);
          if (item) {
            item.style.display = isAct ? "" : "none";
          }
        });
      }
    });
  }

  // =========================================================================
  // 4. RESTAURACIÓN DE FORMULARIOS DESDE EL BORRADOR
  // =========================================================================

  /**
   * Sincroniza los controles del formulario (inputs, radios, selects, color pickers)
   * para reflejar el estado actual del borrador acumulado.
   * @param {Object} fields Diccionario con los campos del borrador
   */
  function syncFormControls(fields) {
    if (!fields || typeof fields !== "object") return;

    Object.keys(fields).forEach((name) => {
      const val = fields[name];

      // 1. Inputs tipo radio
      const radios = document.querySelectorAll(`input[type="radio"][name="${name}"]`);
      if (radios.length) {
        radios.forEach((r) => {
          if (r.value === String(val)) {
            r.checked = true;
            syncConditionalUI(r);
          }
        });
        return;
      }

      // 2. Inputs tipo checkbox
      const checkboxes = document.querySelectorAll(`input[type="checkbox"][name="${name}"]`);
      if (checkboxes.length) {
        checkboxes.forEach((cb) => {
          const isChecked = (val === true || val === "true" || val === 1 || val === "1");
          cb.checked = isChecked;
          cb.setAttribute("active", isChecked ? "1" : "2");
          if (cb.previousElementSibling && cb.previousElementSibling.type === "hidden" && cb.previousElementSibling.name === cb.name) {
            cb.previousElementSibling.disabled = isChecked;
          }
        });
        return;
      }

      // 3. Inputs tipo color / texto / textarea / select
      const inputs = document.querySelectorAll(`input[name="${name}"], textarea[name="${name}"], select[name="${name}"]`);
      inputs.forEach((input) => {
        if (input.type === "file") return;
        input.value = val;

        // Actualizar etiqueta de texto asociada a selectores de color (#hex)
        if (input.type === "color" || input.classList.contains("color-picker")) {
          const labelText = input.closest("label")?.querySelector("p, span");
          if (labelText) {
            labelText.textContent = val;
          }
        }
      });
    });
  }

  // =========================================================================
  // 5. MANEJO ESTRUCTURAL ASÍNCRONO (AJAX PARA AÑADIR / ELIMINAR / TOGGLE)
  // =========================================================================

  let isSubmittingRemoteAjax = false;
  let lastClickedSubmitButton = null;

  /**
   * Determina si un elemento de envío o botón corresponde a una acción estructural
   * (añadir bloque, añadir red, añadir subproducto, eliminar elemento/subproducto, toggle imagen).
   *
   * @param {HTMLElement} el Elemento disparador
   * @returns {boolean}
   */
  function isStructuralAction(el) {
    if (!el || !el.name) return false;
    const name = el.name;
    return (
      name === "add_content_type" ||
      name === "add_rrss_name" ||
      name.includes("add_sub_product") ||
      name.includes("delete_sub_product") ||
      name.includes("delete_img") ||
      name.includes("toggle_img_show") ||
      name.includes("[delete]")
    );
  }

  /**
   * Envía el formulario de .remote-container mediante petición asíncrona (Fetch/AJAX),
   * procesa adiciones o eliminaciones y actualiza la vista previa y el editor en vivo
   * sin provocar recargas completas de la página ni perder la pestaña activa.
   *
   * @async
   * @param {HTMLFormElement} [form] Formulario origen
   * @param {HTMLElement} [triggerElement] Elemento botón o checkbox que disparó la acción
   * @returns {Promise<boolean>}
   */
  async function submitRemoteFormAjax(form, triggerElement = null) {
    if (isSubmittingRemoteAjax) return false;
    isSubmittingRemoteAjax = true;

    try {
      // 1. Cerrar cualquier modal abierto (confirmación de eliminar, menús emergentes)
      document.querySelectorAll(".modal-overlay, .custom-modal-overlay").forEach((m) => m.remove());
      document.querySelectorAll(".content-modal-menu").forEach((m) => m.classList.add("hidden"));
      document.querySelectorAll(".open-modal-menu").forEach((b) => b.classList.remove("active"));

      // 2. Obtener el formulario activo si no fue provisto
      if (!form) {
        form = document.querySelector(".remote-content.active form") || document.querySelector(".remote-container form");
      }
      if (!form) {
        isSubmittingRemoteAjax = false;
        return false;
      }

      // 3. Crear FormData con los campos del formulario
      const formData = new FormData(form);

      // 4. Si hay un disparador con nombre y valor (ej. botón submit o checkbox), asegurar su valor
      if (triggerElement && triggerElement.name) {
        const val = triggerElement.value !== undefined && triggerElement.value !== null ? triggerElement.value : "true";
        formData.set(triggerElement.name, val);
      }

      // 5. Incorporar cambios acumulados en el borrador local (excepto si el ítem fue eliminado)
      const draft = getDraft();
      const isDeleteAction = triggerElement && triggerElement.name && triggerElement.name.includes("[delete]");
      let deletedPrefix = null;
      if (isDeleteAction) {
        deletedPrefix = triggerElement.name.replace(/\[delete\]$/, "");
      }

      Object.keys(draft).forEach((key) => {
        if (deletedPrefix && key.startsWith(deletedPrefix)) {
          return; // Omitir datos antiguos de un elemento que se está borrando
        }
        formData.set(key, draft[key]);
      });

      // 6. Si es una acción de añadir nuevo elemento, marcar sessionStorage para expandirlo al renderizar
      const isAddAction = triggerElement && (
        triggerElement.name === "add_content_type" ||
        triggerElement.name === "add_rrss_name" ||
        (typeof triggerElement.name === "string" && triggerElement.name.includes("add_sub_product"))
      );
      if (isAddAction) {
        sessionStorage.setItem("open_new_block_on_load", "true");
      }

      // 7. Guardar posición de scroll actual para evitar saltos indeseados
      const currentScrollY = window.scrollY || document.documentElement.scrollTop;

      // 8. Enviar petición Fetch con encabezado XMLHttpRequest
      const postUrl = form.getAttribute("action") || `/panel/${user}/diseno`;
      const response = await fetch(postUrl, {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!response.ok) {
        throw new Error(`Error en el servidor al actualizar el diseño: ${response.statusText}`);
      }

      const data = await response.json();

      if (data && data.success) {
        // 9. Como el servidor ya consolidó los cambios en SQLite, limpiar borrador local para evitar desfaces
        clearDraft();

        if (dynamicStyleEl) {
          dynamicStyleEl.textContent = "";
        }

        // 10. Actualizar vista previa oficial (.user-profile-preview)
        if (data.html) {
          document.querySelectorAll(".user-profile-preview").forEach((container) => {
            const temp = document.createElement("div");
            temp.innerHTML = data.html.trim();
            const targetPreview = temp.querySelector(".user-profile-preview") || temp.firstElementChild;
            if (targetPreview && container.parentNode) {
              container.parentNode.replaceChild(targetPreview, container);
            } else {
              container.innerHTML = data.html;
            }
          });
        }

        // 11. Actualizar banner de estado en barra lateral
        if (data.sidebarStatusHtml) {
          document.querySelectorAll(".sidebar-profile-status").forEach((sidebar) => {
            sidebar.outerHTML = data.sidebarStatusHtml;
          });
        }

        // 12. Actualizar formularios en .remote-container manteniendo la pestaña activa
        if (data.formHtml) {
          const remoteContainer = document.querySelector(".remote-container");
          if (remoteContainer) {
            const activeContent = remoteContainer.querySelector(".remote-content.active");
            const activeId = activeContent ? activeContent.id : null;

            const temp = document.createElement("div");
            temp.innerHTML = data.formHtml.trim();
            const newContainer = temp.querySelector(".remote-container") || temp.firstElementChild;
            if (newContainer) {
              remoteContainer.innerHTML = newContainer.innerHTML;
              if (activeId) {
                remoteContainer.querySelectorAll(".remote-content").forEach((c) => {
                  if (c.id === activeId) {
                    c.classList.remove("hidden");
                    c.classList.add("active");
                  } else {
                    c.classList.remove("active");
                    c.classList.add("hidden");
                  }
                });
              }

              // Inicializar inmediatamente switches y componentes de formulario en el nuevo contenido
              if (window.__formComponents) {
                window.__formComponents.initCheckboxSwitches?.();
                window.__formComponents.styleColorPickers?.();
              }
            }
          }
        }

        // 13. Restaurar scroll de la ventana
        window.scrollTo(0, currentScrollY);

        // 14. Notificar a otros módulos (sortableContent, formComponents, saveButtonController)
        document.dispatchEvent(new CustomEvent("previewUpdated", { detail: data }));
        document.dispatchEvent(new CustomEvent("remoteContentUpdated", { detail: data }));
        notifyDraftState();
        return true;
      } else {
        throw new Error(data?.message || "No se pudo actualizar el elemento.");
      }
    } catch (err) {
      console.error("Error en submitRemoteFormAjax:", err);
      return false;
    } finally {
      isSubmittingRemoteAjax = false;
    }
  }

  // =========================================================================
  // 6. CAPTURA Y DELEGACIÓN DE EVENTOS DE INTERACCIÓN (SUBMIT / CLICK / INPUT / CHANGE)
  // =========================================================================

  // Prevenir envíos de navegación convencionales en el panel y delegar a AJAX
  document.addEventListener("submit", (e) => {
    const form = e.target;
    if (form && form.closest(".remote-container")) {
      e.preventDefault();

      const submitter = e.submitter || lastClickedSubmitButton;
      if (submitter && isStructuralAction(submitter)) {
        submitRemoteFormAjax(form, submitter);
        return;
      }

      // Si es un submit estándar de formulario (ej. Enter en un campo)
      saveDraft();
    }
  });

  // Delegación de clics: interceptar modales de borrado y botones de adición/eliminación
  document.addEventListener("click", (e) => {
    // 1. Confirmación de eliminación en modal (label asociado a checkbox delete)
    const deleteLabel = e.target.closest('label[for^="delete-link-"], label[for^="delete-rrss-"]');
    if (deleteLabel) {
      e.preventDefault();
      const targetId = deleteLabel.getAttribute("for");
      const checkbox = document.getElementById(targetId);
      if (checkbox) {
        checkbox.checked = true;
        const form = checkbox.closest("form") || document.querySelector(".remote-content.active form") || document.querySelector(".remote-container form");
        submitRemoteFormAjax(form, checkbox);
      }
      return;
    }

    // 2. Botones submit dentro de .remote-container (añadir tipo, red social, subproducto, borrar imagen)
    const submitBtn = e.target.closest('button[type="submit"], input[type="submit"]');
    if (submitBtn) {
      const form = submitBtn.closest("form") || document.querySelector(".remote-content.active form") || document.querySelector(".remote-container form");
      if (form && form.closest(".remote-container")) {
        lastClickedSubmitButton = submitBtn;
        setTimeout(() => {
          if (lastClickedSubmitButton === submitBtn) lastClickedSubmitButton = null;
        }, 300);

        if (isStructuralAction(submitBtn)) {
          e.preventDefault();
          submitRemoteFormAjax(form, submitBtn);
        }
      }
    }
  });

  document.addEventListener("input", (e) => {
    const target = e.target;
    if (!target || !target.name) return;

    // Solo procesar controles pertenecientes al contenedor de edición
    if (!target.closest(".remote-container") && !target.closest(".custom-color-picker-popover")) return;

    // Manejo de selectores de color
    const isColor = target.type === "color" || target.classList.contains("color-picker");
    if (isColor) {
      const labelText = target.closest("label")?.querySelector("p, span");
      if (labelText && target.value) {
        labelText.textContent = target.value;
      }
      setDraftField(target.name, target.value);
      applyDraftToPreview(getDraft());
      return;
    }

    // Manejo de campos de texto, bio y deslizadores de rango
    if (target.tagName === "INPUT" || target.tagName === "TEXTAREA") {
      if (target.type === "file") return;
      setDraftField(target.name, target.value);
      applyDraftToPreview(getDraft());
    }
  });

  document.addEventListener("change", (e) => {
    const target = e.target;
    if (!target || !target.name) return;

    if (!target.closest(".remote-container") && !target.closest(".custom-color-picker-popover")) return;

    // Sincronizar UI condicional del formulario
    syncConditionalUI(target);

    // Checkbox de borrado directo
    if (target.type === "checkbox" && target.name.includes("[delete]") && target.checked) {
      const form = target.closest("form") || document.querySelector(".remote-content.active form") || document.querySelector(".remote-container form");
      submitRemoteFormAjax(form, target);
      return;
    }

    // Radios
    if (target.type === "radio" && target.checked) {
      setDraftField(target.name, target.value);
      applyDraftToPreview(getDraft());
      return;
    }

    // Checkboxes
    if (target.type === "checkbox") {
      setDraftField(target.name, target.checked ? (target.value || "true") : "false");
      applyDraftToPreview(getDraft());

      // Sincronizar visibilidad de elementos en la vista previa al conmutar switches
      if (target.matches(".checkbox-switch")) {
        const match = target.name && target.name.match(/^content\[(\d+)\]/);
        if (match) {
          const idx = match[1];
          document.querySelectorAll(".user-profile-preview").forEach((preview) => {
            const item = preview.querySelector(`[data-content-index="${idx}"]`);
            if (item) {
              item.style.display = target.checked ? "" : "none";
            }
          });
        }
        return;
      }
    }

    // Selectores y otros inputs
    if (target.type !== "file") {
      setDraftField(target.name, target.value);
      applyDraftToPreview(getDraft());
    }
  });

  // Manejo de imágenes (avatar y bloques de contenido) en cliente con vista previa instantánea
  document.addEventListener("change", (e) => {
    const target = e.target;
    if (!target || target.type !== "file") return;

    if (target.name === "avatar" && target.files && target.files[0]) {
      const file = target.files[0];
      const previewUrl = URL.createObjectURL(file);
      document.querySelectorAll(".user-profile-preview figure img.cover").forEach((img) => {
        img.src = previewUrl;
      });
      notifyDraftState();
    } else if (target.name && target.name.startsWith("content_img_") && target.files && target.files[0]) {
      const file = target.files[0];
      const previewUrl = URL.createObjectURL(file);
      const match = target.name.match(/^content_img_(\d+)(?:_(\d+))?$/);
      if (match) {
        const itemIdx = match[1];
        const subIdx = match[2];

        // 1. Actualizar miniatura dentro del formulario en el editor
        const block = target.closest(".sortable-item") || target.closest(".content-block");
        if (block) {
          const thumb = block.querySelector("figure img");
          if (thumb) thumb.src = previewUrl;
        }

        // 2. Actualizar imagen en la vista previa
        document.querySelectorAll(".user-profile-preview").forEach((preview) => {
          const item = preview.querySelector(`[data-content-index="${itemIdx}"]`);
          if (item) {
            if (subIdx !== undefined) {
              const subImgs = item.querySelectorAll("img");
              if (subImgs[subIdx]) {
                subImgs[subIdx].src = previewUrl;
              }
            } else {
              const previewImg = item.querySelector("img");
              if (previewImg) {
                previewImg.src = previewUrl;
              }
            }
          }
        });
      }
      notifyDraftState();
    }
  });

  // =========================================================================
  // 6. ACCIONES GLOBALES: GUARDAR Y DESCARTAR
  // =========================================================================

  /**
   * Vuelca la caché del borrador local a la base de datos del usuario
   * en una única petición consolidada y limpia la caché local al tener éxito.
   *
   * @async
   * @returns {Promise<boolean>} True si se guardó con éxito.
   */
  async function saveDraft() {
    const draft = getDraft();
    const activeForm = document.querySelector(".remote-content.active form") || document.querySelector(".remote-container form");
    // Usar FormData nativo para respetar disabled en inputs hidden emparejados con switches
    const formData = activeForm ? new FormData(activeForm) : new FormData();

    // 1. Sobrescribir con todos los campos acumulados en el borrador (tienen prioridad)
    Object.keys(draft).forEach((key) => {
      formData.set(key, draft[key]);
    });

    const saveUrl = `/panel/${user}/guardar`;

    const response = await fetch(saveUrl, {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    });

    if (!response.ok) {
      throw new Error(`Error en el servidor al guardar el diseño: ${response.statusText}`);
    }

    const data = await response.json();

    if (data && data.success) {
      // Limpiar la caché local tras guardar con éxito
      clearDraft();

      // Eliminar sobreescrituras dinámicas temporales ya que el preview oficial las reemplazará
      if (dynamicStyleEl) {
        dynamicStyleEl.textContent = "";
      }

      // Actualizar la vista previa con el HTML oficial retornado por el servidor
      if (data.html) {
        document.querySelectorAll(".user-profile-preview").forEach((container) => {
          const temp = document.createElement("div");
          temp.innerHTML = data.html.trim();
          const targetPreview = temp.querySelector(".user-profile-preview") || temp.firstElementChild;
          if (targetPreview && container.parentNode) {
            container.parentNode.replaceChild(targetPreview, container);
          } else {
            container.innerHTML = data.html;
          }
        });
      }

      // Actualizar el estado de la barra lateral si viene en la respuesta
      if (data.sidebarStatusHtml) {
        const sidebar = document.querySelector(".sidebar-profile-status");
        if (sidebar) {
          sidebar.outerHTML = data.sidebarStatusHtml;
        }
      }

      document.dispatchEvent(new CustomEvent("designDraftSaved", { detail: data }));
      document.dispatchEvent(new CustomEvent("previewUpdated", { detail: data }));
      return true;
    } else {
      throw new Error(data?.message || "No se pudo completar el guardado del diseño.");
    }
  }

  /**
   * Descarta los cambios acumulados en el borrador local, limpia la caché
   * y revierte la vista previa y formularios a la versión oficial.
   *
   * @async
   * @returns {Promise<boolean>} True si se descartó con éxito.
   */
  async function discardDraft() {
    clearDraft();

    // Eliminar sobreescrituras dinámicas
    if (dynamicStyleEl) {
      dynamicStyleEl.textContent = "";
    }

    const discardUrl = `/panel/${user}/descartar`;

    const response = await fetch(discardUrl, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest"
      }
    });

    if (!response.ok) {
      throw new Error(`Error en el servidor al descartar el diseño: ${response.statusText}`);
    }

    const data = await response.json();

    if (data && data.success) {
      // 1. Restaurar HTML oficial de la vista previa
      if (data.html) {
        document.querySelectorAll(".user-profile-preview").forEach((container) => {
          const temp = document.createElement("div");
          temp.innerHTML = data.html.trim();
          const targetPreview = temp.querySelector(".user-profile-preview") || temp.firstElementChild;
          if (targetPreview && container.parentNode) {
            container.parentNode.replaceChild(targetPreview, container);
          } else {
            container.innerHTML = data.html;
          }
        });
      }

      // 2. Restaurar formularios con los valores oficiales
      if (data.formHtml) {
        const remoteContainer = document.querySelector(".remote-container");
        if (remoteContainer) {
          const activeContent = remoteContainer.querySelector(".remote-content.active");
          const activeId = activeContent ? activeContent.id : null;

          const temp = document.createElement("div");
          temp.innerHTML = data.formHtml.trim();
          const newContainer = temp.querySelector(".remote-container") || temp.firstElementChild;
          if (newContainer) {
            remoteContainer.innerHTML = newContainer.innerHTML;
            if (activeId) {
              remoteContainer.querySelectorAll(".remote-content").forEach((c) => {
                if (c.id === activeId) {
                  c.classList.remove("hidden");
                  c.classList.add("active");
                } else {
                  c.classList.remove("active");
                  c.classList.add("hidden");
                }
              });
            }
            // Inicializar inmediatamente switches y componentes de formulario en el nuevo contenido
            if (window.__formComponents) {
              window.__formComponents.initCheckboxSwitches?.();
              window.__formComponents.styleColorPickers?.();
            }
          }
        }
      }

      // 3. Restaurar banner de estado en barra lateral si viene en la respuesta
      if (data.sidebarStatusHtml) {
        document.querySelectorAll(".sidebar-profile-status").forEach((sidebar) => {
          sidebar.outerHTML = data.sidebarStatusHtml;
        });
      }

      document.dispatchEvent(new CustomEvent("designDraftDiscarded", { detail: data }));
      document.dispatchEvent(new CustomEvent("previewUpdated", { detail: data }));
      document.dispatchEvent(new CustomEvent("remoteContentUpdated", { detail: data }));
      notifyDraftState();
      return true;
    } else {
      throw new Error(data?.message || "No se pudo descartar el diseño.");
    }
  }

  // =========================================================================
  // 7. EXPOSICIÓN DE LA API GLOBAL Y RESTAURACIÓN INICIAL
  // =========================================================================

  window.__designDraftManager = {
    getDraft,
    setDraftField,
    clearDraft,
    hasDraft,
    saveDraft,
    discardDraft,
    submitRemoteFormAjax,
    applyDraftToPreview,
    syncFormControls
  };

  // Restauración inmediata al cargar la página
  function initRestoration() {
    const draft = getDraft();
    if (Object.keys(draft).length > 0) {
      applyDraftToPreview(draft);
      syncFormControls(draft);
      notifyDraftState();
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initRestoration);
  } else {
    initRestoration();
  }

  window.addEventListener("pageshow", initRestoration);
}
