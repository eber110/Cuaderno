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
   * Obtiene el índice global del bloque de contenido.
   * @param {HTMLElement} block
   * @returns {string|null}
   */
  function getBlockIndex(block) {
    if (!block) return null;
    const matchId = block.id && block.id.match(/^content-item-(\d+)$/);
    if (matchId) return matchId[1];
    const anyInput = block.querySelector('[name^="content["]');
    if (anyInput) {
      const matchName = anyInput.name.match(/^content\[(\d+)\]/);
      if (matchName) return matchName[1];
    }
    return null;
  }

  /**
   * Determina si un bloque de contenido cumple con sus requisitos mínimos para poder activarse.
   *
   * @param {HTMLElement} block Bloque en el editor.
   * @param {string|number} idx Índice del bloque.
   * @param {string} type Tipo de bloque (link, product, product_group, campaign, banner, title, text, separator).
   * @param {Object} [extraData] Datos auxiliares (como previewUrl de una imagen recién subida).
   * @returns {boolean}
   */
  function isBlockValid(block, idx, type, extraData = {}) {
    if (!block) return false;

    switch (type) {
      case "separator":
        return true;

      case "title": {
        const input = block.querySelector(`input[name="content[${idx}][title]"]`);
        return !!(input && input.value.trim() !== "");
      }

      case "text": {
        const textarea = block.querySelector(`textarea[name="content[${idx}][text]"]`);
        return !!(textarea && textarea.value.trim() !== "");
      }

      case "campaign": {
        const titleInput = block.querySelector(`input[name="content[${idx}][title]"]`);
        return !!(titleInput && titleInput.value.trim() !== "");
      }

      case "banner": {
        const sizeRadio = block.querySelector(".banner-size-radio:checked");
        const hasSize = !!sizeRadio && !!sizeRadio.value;

        const fileInput = block.querySelector(`input[name="content_img_${idx}"]`);
        const hiddenImg = block.querySelector(`input[name="content[${idx}][img]"]`);
        const thumbImg = block.querySelector("figure img");

        const hasNewFile = fileInput && fileInput.files && fileInput.files.length > 0;
        const hasSavedImg = hiddenImg && hiddenImg.value && hiddenImg.value !== "no-image.webp" && hiddenImg.value !== "";
        const hasThumbImg = thumbImg && thumbImg.src && !thumbImg.src.includes("no-image.webp") && thumbImg.src !== "";
        const hasImg = hasNewFile || hasSavedImg || hasThumbImg || !!extraData.previewUrl;

        return hasSize && hasImg;
      }

      case "product_group": {
        const subProds = block.querySelectorAll(".sub-product-item");
        let validCount = 0;
        subProds.forEach((sub) => {
          const title = sub.querySelector('input[name*="[title]"]')?.value || "";
          const url = sub.querySelector('input[name*="[url]"]')?.value || "";
          if (title.trim() !== "" && url.trim() !== "") {
            validCount++;
          }
        });
        return validCount >= 2;
      }

      case "product":
      case "link":
      default: {
        const titleInput = block.querySelector(`input[name="content[${idx}][title]"]`);
        const urlInput = block.querySelector(`input[name="content[${idx}][url]"]`);
        const hasTitle = !!(titleInput && titleInput.value.trim() !== "");
        const hasUrl = !!(urlInput && urlInput.value.trim() !== "");
        return hasTitle && hasUrl;
      }
    }
  }

  /**
   * Sincroniza el estado de activación y visibilidad de cualquier bloque de contenido.
   * Si cumple los requisitos mínimos, habilita el switch de activación (remueve disabled)
   * permitiendo que el usuario lo active a voluntad. El bloque solo se mostrará en la
   * vista previa si el switch se encuentra encendido (checked).
   *
   * @param {HTMLElement} block Bloque en el editor.
   * @param {string|number} idx Índice del bloque.
   * @param {Object} [extraData] Datos auxiliares (como previewUrl para imágenes).
   */
  function syncBlockActiveState(block, idx, extraData = {}) {
    if (!block) return;
    const type = block.dataset.type || "link";
    const activeSwitch = block.querySelector(`input[type="checkbox"][name="content[${idx}][active]"]`) || block.querySelector(`.checkbox-switch[name="content[${idx}][active]"]`);
    if (!activeSwitch) return;

    const isValid = isBlockValid(block, idx, type, extraData);

    const container = activeSwitch.closest(".checkbox-switch-container") || activeSwitch;
    const hiddenInput = container.previousElementSibling && container.previousElementSibling.name === activeSwitch.name 
      ? container.previousElementSibling 
      : null;

    if (isValid) {
      // Habilitar el switch para que el usuario pueda activarlo u ocultarlo a voluntad
      activeSwitch.removeAttribute("disabled");
      activeSwitch.disabled = false;

      if (hiddenInput && hiddenInput.tagName === "INPUT" && hiddenInput.type === "hidden") {
        hiddenInput.disabled = activeSwitch.checked;
      }

      // Actualizar datos del elemento en la vista previa (imagen, proporción), respetando estrictamente el estado del switch
      document.querySelectorAll(".user-profile-preview").forEach((preview) => {
        const items = preview.querySelectorAll(`[data-content-index="${idx}"]`);
        items.forEach((item) => {
          if (type === "banner") {
            if (extraData.previewUrl) {
              const previewImg = item.querySelector("img");
              if (previewImg) previewImg.src = extraData.previewUrl;
            }
            const sizeRadio = block.querySelector(".banner-size-radio:checked");
            if (sizeRadio && sizeRadio.value) {
              const ratioMap = {
                "720x720": "720 / 720",
                "1024x720": "1024 / 720",
                "720x1024": "720 / 1024"
              };
              if (ratioMap[sizeRadio.value]) {
                item.style.aspectRatio = ratioMap[sizeRadio.value];
              }
            }
          }

          const variant = item.dataset.layoutVariant;
          if (variant) {
            const selectedLayout = block.querySelector(`input[name="content[${idx}][layout]"]:checked`)?.value || "grid";
            if (variant === selectedLayout) {
              item.style.display = activeSwitch.checked ? "" : "none";
              if (activeSwitch.checked) item.classList.remove("hidden");
            } else {
              item.style.display = "none";
              item.classList.add("hidden");
            }
          } else {
            // El bloque SOLO se muestra en el preview si el switch está activado (checked)
            item.style.display = activeSwitch.checked ? "" : "none";
          }
        });
      });
    } else {
      activeSwitch.setAttribute("disabled", "disabled");
      activeSwitch.disabled = true;
      activeSwitch.checked = false;
      activeSwitch.setAttribute("active", "2");
      setDraftField(activeSwitch.name, "false");

      if (hiddenInput && hiddenInput.tagName === "INPUT" && hiddenInput.type === "hidden") {
        hiddenInput.disabled = false;
      }

      document.querySelectorAll(".user-profile-preview").forEach((preview) => {
        const items = preview.querySelectorAll(`[data-content-index="${idx}"]`);
        items.forEach((item) => {
          item.style.display = "none";
        });
      });
    }
  }

  /**
   * Refresca el estado de todos los bloques de contenido en el panel.
   */
  function refreshAllBlocksActiveState() {
    document.querySelectorAll("#sortable-content-list .sortable-item.content-block").forEach((block) => {
      const idx = getBlockIndex(block);
      if (idx !== null) {
        syncBlockActiveState(block, idx);
      }
    });
  }

  // Alias para mantener compatibilidad
  function syncBannerState(block, idx, previewUrl = null) {
    syncBlockActiveState(block, idx, { previewUrl });
  }
  function refreshAllBannersState() {
    refreshAllBlocksActiveState();
  }

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
        if (horizRadio) {
          horizRadio.checked = true;
          setDraftField(horizRadio.name, "horizontal");
        }
        const textPosWrap = document.getElementById(`campaign-text-pos-wrap-${idx}`);
        if (textPosWrap) textPosWrap.style.display = "none";
      } else {
        const checkedSize = document.querySelector(`input[name="content[${idx}][size]"]:checked`);
        const textPosWrap = document.getElementById(`campaign-text-pos-wrap-${idx}`);
        if (textPosWrap) textPosWrap.style.display = (checkedSize && checkedSize.value !== "horizontal") ? "flex" : "none";
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

    // 8. Bloques de separador: cambio entre figura (ícono) y espacio en blanco
    if (target.classList && target.classList.contains("separator-icon-radio")) {
      const block = target.closest(".sortable-item, .content-item-body, .flex-column");
      if (block) {
        const isSpace = (target.value === "none" || target.value === "ban");
        const spaceOpts = block.querySelector(".separator-space-options");
        const sizeOpts = block.querySelector(".separator-size-options");
        if (spaceOpts) spaceOpts.style.display = isSpace ? "flex" : "none";
        if (sizeOpts) sizeOpts.style.display = isSpace ? "none" : "flex";
      }
    }

    // 9. Bloques de banner: selector de tamaño habilita recorte y subida
    if (target.classList && target.classList.contains("banner-size-radio")) {
      const idx = target.dataset.index;
      if (idx !== undefined) {
        const hint = document.getElementById(`banner-size-hint-${idx}`);
        if (hint) hint.classList.add("hidden");
        const cropWrap = document.getElementById(`banner-crop-btn-wrap-${idx}`);
        if (cropWrap) cropWrap.classList.remove("opacity-40", "pointer-events-none");
        const cropInput = document.getElementById(`content_img_banner_${idx}`);
        if (cropInput) {
          cropInput.removeAttribute("disabled");
          cropInput.setAttribute("cropping-size", target.value);
        }

        // Sincronizar de inmediato la proporción en las vistas previas
        const ratioMap = {
          "720x720": "720 / 720",
          "1024x720": "1024 / 720",
          "720x1024": "720 / 1024"
        };
        if (ratioMap[target.value]) {
          document.querySelectorAll(".user-profile-preview").forEach((preview) => {
            const bannerEl = preview.querySelector(`[data-content-index="${idx}"]`);
            if (bannerEl) bannerEl.style.aspectRatio = ratioMap[target.value];
          });
        }

        const block = target.closest(".sortable-item") || target.closest(".content-block");
        syncBannerState(block, idx);
      }
    }

    // 10. Productos: switch de oferta/rebaja habilita wrapper de descuentos
    if (target.classList && target.classList.contains("product-offer-switch")) {
      const block = target.closest(".sub-product-item, .content-item-body");
      if (block) {
        const discountWrap = block.querySelector(".product-discount-wrapper");
        if (discountWrap) discountWrap.style.display = target.checked ? "flex" : "none";
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

  function getGradientColors(hex, target = "card") {
    let clean = String(hex || "#272727").trim();
    if (!clean.startsWith("#")) clean = `#${clean}`;
    if (target === "container") {
      return [clean, `oklch(from ${clean} calc(l + 0.15) calc(c - 0.085) h)`];
    }
    return [clean, `oklch(from ${clean} calc(l + 0.15) c h)`];
  }

  function getContainerSolidColor(hex) {
    let clean = String(hex || "#272727").trim();
    if (!clean.startsWith("#")) clean = `#${clean}`;
    return `oklch(from ${clean} calc(l + 0.015) calc(c - 0.025) h)`;
  }

  function escapeHtml(str) {
    if (typeof str !== "string") return String(str || "");
    return str
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
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
    const [gradStart, gradEnd] = getGradientColors(backPerfil, "card");
    const thumbSolid = document.getElementById("preview-style-solid");
    if (thumbSolid) thumbSolid.style.backgroundColor = backPerfil;
    const thumbGradient = document.getElementById("preview-style-gradient");
    if (thumbGradient) thumbGradient.style.background = `linear-gradient(180deg, ${gradStart}, ${gradEnd})`;

    if (styleBack === "gradientUp") {
      const [gradStartCont, gradEndCont] = getGradientColors(backPerfil, "container");
      cssRules.push(`
        .user-profile-preview .back-card {
          background: linear-gradient(0deg, ${gradStart}, ${gradEnd}) !important;
        }
        .user-profile-preview .back-card-container {
          background: linear-gradient(0deg, ${gradStartCont}, ${gradEndCont}) !important;
        }
      `);
    } else if (styleBack === "gradientDown") {
      const [gradStartCont, gradEndCont] = getGradientColors(backPerfil, "container");
      cssRules.push(`
        .user-profile-preview .back-card {
          background: linear-gradient(180deg, ${gradStart}, ${gradEnd}) !important;
        }
        .user-profile-preview .back-card-container {
          background: linear-gradient(180deg, ${gradStartCont}, ${gradEndCont}) !important;
        }
      `);
    } else {
      const containerSolid = getContainerSolidColor(backPerfil);
      cssRules.push(`
        .user-profile-preview .back-card {
          background: ${backPerfil} !important;
          background-color: ${backPerfil} !important;
        }
        .user-profile-preview .back-card-container {
          background: ${containerSolid} !important;
          background-color: ${containerSolid} !important;
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

    // Animación hover en botones
    const buttonBack = fields.back || document.getElementById("select-color-button")?.value || "#d6d6d6";
    if (fields.hover !== undefined) {
      const isHoverActive = (fields.hover === "true" || fields.hover === true || fields.hover === 1 || fields.hover === "1");
      if (isHoverActive) {
        cssRules.push(`
          .user-profile-preview .theme-button:hover {
            background-color: oklch(from ${buttonBack} calc(l * 0.92) c h) !important;
          }
        `);
      } else {
        cssRules.push(`
          .user-profile-preview .theme-button:hover {
            background-color: ${buttonBack} !important;
          }
        `);
      }
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
        p.querySelectorAll(".theme-button:not(.link-item-wrapper):not(.product-item-wrapper):not(.product-regular-wrapper):not(.product-grid-card):not(.product-slide-card):not(.campaign-block-wrapper):not(.banner-block-wrapper)").forEach((btn) => {
          validBorders.forEach((b) => btn.classList.remove(b));
          btn.classList.add(btnBorder);
        });
        p.querySelectorAll(".theme-button:not(.link-item-wrapper):not(.product-item-wrapper):not(.product-regular-wrapper):not(.product-grid-card):not(.product-slide-card):not(.campaign-block-wrapper):not(.banner-block-wrapper) img.cover").forEach((img) => {
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

    // --- D2. REDES SOCIALES (RRSS) ---
    Object.keys(fields).forEach((key) => {
      const matchRrss = key.match(/^rrss\[(\d+)\]\[1\]$/);
      if (matchRrss) {
        const rrssIdx = matchRrss[1];
        const rrssUrl = String(fields[key] || "").trim();
        const nameInput = document.querySelector(`input[name="rrss[${rrssIdx}][0]"]`);
        const socialName = nameInput ? nameInput.value : "";
        if (socialName) {
          previews.forEach((p) => {
            const link = p.querySelector(`[data-link-id="rrss_${socialName}"], [aria-label="${socialName}"]`);
            if (link) {
              link.href = rrssUrl || "#";
              link.style.display = rrssUrl !== "" ? "" : "none";
            }
          });
        }
      }
    });

    // --- E. ACTUALIZACIÓN EN VIVO DE BLOQUES DE CONTENIDO (CAMPAÑAS, BANNERS, TÍTULOS, TEXTOS, ETC.) ---
    const contentMap = {};
    const contentProductMap = {};

    Object.keys(fields).forEach((key) => {
      const matchSub = key.match(/^content\[(\d+)\]\[products\]\[(\d+)\]\[([^\]]+)\]$/);
      if (matchSub) {
        const idx = matchSub[1];
        const pIdx = matchSub[2];
        const prop = matchSub[3];
        if (!contentProductMap[idx]) contentProductMap[idx] = {};
        if (!contentProductMap[idx][pIdx]) contentProductMap[idx][pIdx] = {};
        contentProductMap[idx][pIdx][prop] = fields[key];
        return;
      }

      const match = key.match(/^content\[(\d+)\]\[([^\]]+)\]$/);
      if (match) {
        const idx = match[1];
        const prop = match[2];
        if (!contentMap[idx]) contentMap[idx] = {};
        contentMap[idx][prop] = fields[key];
      }
    });

    previews.forEach((p) => {
      // 1. Actualización de cada bloque por su índice
      Object.keys(contentMap).forEach((idx) => {
        const cData = contentMap[idx];
        const blocks = p.querySelectorAll(`[data-content-index="${idx}"]`);
        if (!blocks.length) return;

        blocks.forEach((block) => {
          // Visibilidad (switch active)
          if (cData.active !== undefined) {
            const isAct = (cData.active === "true" || cData.active === true || cData.active === 1 || cData.active === "1");
            const variant = block.dataset.layoutVariant;
            if (variant) {
              const currentLayout = cData.layout || (document.querySelector(`input[name="content[${idx}][layout]"]:checked`)?.value || "grid");
              if (variant === currentLayout) {
                block.style.display = isAct ? "" : "none";
              } else {
                block.style.display = "none";
              }
            } else {
              block.style.display = isAct ? "" : "none";
            }
          }

          // A. CAMPAÑA
          if (block.classList.contains("campaign-block-wrapper")) {
            const imgPosition = cData.img_position || (block.querySelector("figure.faded-image:not([style*='display: none'])") ? "header" : "background");
            const effectiveSize = (imgPosition === "header") ? "horizontal" : (cData.size || (block.classList.contains("campaign-size-square") ? "square" : (block.classList.contains("campaign-size-vertical") ? "vertical" : "horizontal")));

            // Tamaño del contenedor
            block.classList.remove("campaign-size-horizontal", "campaign-size-square", "campaign-size-vertical");
            block.classList.add("campaign-size-" + effectiveSize);

            // Posición de imagen (Fondo vs Cabecera)
            const bgLayer = block.querySelector(".campaign-bg-layer");
            const headerFig = block.querySelector("figure.faded-image");
            const contentEl = block.querySelector(".campaign-content");

            if (imgPosition === "header") {
              if (bgLayer) bgLayer.style.display = "none";
              if (headerFig) {
                headerFig.style.display = "";
              } else if (bgLayer) {
                const bgImg = bgLayer.querySelector("img");
                if (bgImg && bgImg.src) {
                  const fig = document.createElement("figure");
                  fig.className = "w100 ar-square overflow-hidden faded-image";
                  fig.innerHTML = `<img src="${bgImg.src}" alt="Campaña" class="cover w100 ar-square">`;
                  if (contentEl) block.insertBefore(fig, contentEl);
                }
              }
              if (contentEl) {
                contentEl.classList.remove("pt20");
                contentEl.classList.add("pt0");
              }
            } else {
              if (headerFig) headerFig.style.display = "none";
              if (bgLayer) {
                bgLayer.style.display = "";
              } else if (headerFig) {
                const hImg = headerFig.querySelector("img");
                if (hImg && hImg.src) {
                  const newBgLayer = document.createElement("div");
                  newBgLayer.className = "campaign-bg-layer";
                  newBgLayer.style.cssText = "position: absolute; inset: 0; width: 100%; height: 100%; z-index: 0; overflow: hidden; pointer-events: none;";
                  newBgLayer.innerHTML = `
                    <img src="${hImg.src}" alt="Campaña" class="cover w100 h100" style="object-fit: cover;">
                    <div class="campaign-bg-overlay" style="position: absolute; inset: 0; width: 100%; height: 100%; background-color: oklch(from ${cData.bg_color || "#1e1e1e"} l c h / ${cData.bg_opacity || 80}%);"></div>
                  `;
                  block.insertBefore(newBgLayer, block.firstChild);
                }
              }
              if (contentEl) {
                contentEl.classList.remove("pt0");
                contentEl.classList.add("pt20");
              }
            }

            // Formato flex de contenido y ancla del botón según tamaño
            const isNotHorizontal = (effectiveSize !== "horizontal");
            if (contentEl) {
              if (isNotHorizontal) {
                contentEl.classList.add("flex-1");
                contentEl.style.flex = "1 1 auto";
                contentEl.style.minHeight = "max-content";
              } else {
                contentEl.classList.remove("flex-1");
                contentEl.style.flex = "";
                contentEl.style.minHeight = "";
              }
            }

            const btnEl = block.querySelector(".campaign-button");
            if (btnEl) {
              if (isNotHorizontal) {
                btnEl.classList.add("campaign-btn-anchor-bottom");
              } else {
                btnEl.classList.remove("campaign-btn-anchor-bottom");
              }
              if (cData.button_text !== undefined) {
                btnEl.textContent = cData.button_text.trim() || "Suscribirme";
              }
              if (cData.btn_bg_color) {
                btnEl.style.setProperty("background-color", cData.btn_bg_color, "important");
              }
              if (cData.btn_text_color) {
                btnEl.style.setProperty("color", cData.btn_text_color, "important");
              }
            }

            // Colores de fondo y opacidad de overlay
            const bgColor = cData.bg_color || block.style.backgroundColor || "#1e1e1e";
            if (cData.bg_color) {
              block.style.setProperty("background-color", bgColor, "important");
            }

            const bgOverlay = block.querySelector(".campaign-bg-overlay");
            if (bgOverlay && (cData.bg_color || cData.bg_opacity !== undefined)) {
              const opacityVal = (cData.bg_opacity !== undefined) ? cData.bg_opacity : 80;
              bgOverlay.style.setProperty("background-color", `oklch(from ${bgColor} l c h / ${opacityVal}%)`, "important");
            }

            // Grupo de texto
            const textGroup = block.querySelector(".campaign-text-group");
            if (textGroup) {
              // Alineación horizontal
              if (cData.text_align) {
                ["left", "center", "right"].forEach((a) => {
                  textGroup.classList.remove("campaign-align-" + a);
                });
                textGroup.classList.add("campaign-align-" + cData.text_align);

                const innerDiv = textGroup.querySelector("div.flex-column.gap5");
                if (innerDiv) {
                  ["left", "center", "right"].forEach((a) => {
                    innerDiv.classList.remove("campaign-align-" + a);
                  });
                  innerDiv.classList.add("campaign-align-" + cData.text_align);
                }
              }

              // Alineación vertical
              ["top", "center", "bottom"].forEach((p) => {
                textGroup.classList.remove("campaign-text-pos-" + p);
              });
              if (isNotHorizontal && cData.text_position) {
                textGroup.classList.add("campaign-text-pos-" + cData.text_position);
              }

              // Título
              let h3 = textGroup.querySelector("h3");
              const titleVal = (cData.title !== undefined) ? cData.title : (h3 ? h3.textContent : "");
              if (titleVal.trim() !== "") {
                if (!h3) {
                  h3 = document.createElement("h3");
                  h3.className = "bold700 campaign-title-large w100";
                  const innerDiv = textGroup.querySelector("div.flex-column.gap5") || textGroup;
                  innerDiv.insertBefore(h3, innerDiv.firstChild);
                }
                h3.textContent = titleVal;
                h3.style.display = "";
              } else if (h3) {
                h3.style.display = "none";
              }

              if (h3) {
                if (cData.title_size) {
                  ["small", "medium", "large"].forEach((s) => h3.classList.remove("campaign-title-" + s));
                  h3.classList.add("campaign-title-" + cData.title_size);
                }
                if (cData.title_color) {
                  h3.style.setProperty("color", cData.title_color, "important");
                }
              }

              // Descripción
              let pDesc = textGroup.querySelector("p:not(.modal-btn):not(.campaign-button)");
              const descVal = (cData.desc !== undefined) ? cData.desc : (pDesc ? pDesc.textContent : "");
              if (descVal.trim() !== "") {
                if (!pDesc) {
                  pDesc = document.createElement("p");
                  pDesc.className = "campaign-desc-medium w100";
                  const innerDiv = textGroup.querySelector("div.flex-column.gap5") || textGroup;
                  innerDiv.appendChild(pDesc);
                }
                pDesc.innerHTML = escapeHtml(descVal).replace(/\n/g, "<br>");
                pDesc.style.display = "";
              } else if (pDesc) {
                pDesc.style.display = "none";
              }

              if (pDesc) {
                if (cData.desc_size) {
                  ["small", "medium", "large"].forEach((s) => pDesc.classList.remove("campaign-desc-" + s));
                  pDesc.classList.add("campaign-desc-" + cData.desc_size);
                }
                if (cData.desc_color) {
                  pDesc.style.setProperty("color", cData.desc_color, "important");
                }
              }

              // Contador regresivo
              const countdownBox = block.querySelector(".campaign-countdown-box, [data-countdown]");
              if (countdownBox) {
                if (cData.has_countdown !== undefined) {
                  const hasCd = (cData.has_countdown === true || cData.has_countdown === "true" || cData.has_countdown === "1" || cData.has_countdown === 1);
                  countdownBox.style.display = hasCd ? "" : "none";
                }
                if (cData.countdown_date) {
                  countdownBox.setAttribute("data-countdown", cData.countdown_date);
                }
                if (cData.countdown_bg_color) {
                  countdownBox.style.setProperty("background-color", cData.countdown_bg_color, "important");
                }
                if (cData.countdown_text_color) {
                  countdownBox.style.setProperty("color", cData.countdown_text_color, "important");
                }
                if (cData.countdown_text_size) {
                  ["small", "medium", "large"].forEach((s) => countdownBox.classList.remove("countdown-text-" + s));
                  countdownBox.classList.add("countdown-text-" + cData.countdown_text_size);
                }
                if (cData.countdown_widget_size) {
                  ["small", "medium", "large"].forEach((s) => countdownBox.classList.remove("campaign-countdown-widget-" + s));
                  countdownBox.classList.add("campaign-countdown-widget-" + cData.countdown_widget_size);
                }
              }
            }
          }

          // B. TÍTULO
          else if (block.classList.contains("title-block-wrapper")) {
            const h2 = block.querySelector("h2");
            if (h2) {
              if (cData.title !== undefined) {
                h2.textContent = cData.title;
                block.style.display = (cData.title.trim() === "") ? "none" : "";
              }
              if (cData.title_size) {
                ["x18", "x20", "x24"].forEach((s) => h2.classList.remove(s));
                const sizeMap = { small: "x18", medium: "x20", large: "x24" };
                h2.classList.add(sizeMap[cData.title_size] || "x18");
              }
              if (cData.title_weight) {
                ["bold500", "bold600", "bold700", "bold900"].forEach((w) => h2.classList.remove(w));
                h2.classList.add("bold" + cData.title_weight);
              }
            }
          }

          // C. TEXTO
          else if (block.classList.contains("text-block-wrapper")) {
            const pEl = block.querySelector("p");
            if (pEl) {
              if (cData.text !== undefined) {
                pEl.innerHTML = escapeHtml(cData.text).replace(/\n/g, "<br>");
                block.style.display = (cData.text.trim() === "") ? "none" : "";
              }
              if (cData.text_weight) {
                ["bold400", "bold500"].forEach((w) => pEl.classList.remove(w));
                pEl.classList.add("bold" + cData.text_weight);
              }
              if (cData.text_align) {
                ["text-left", "text-center", "text-right"].forEach((a) => block.classList.remove(a));
                block.classList.add("text-" + cData.text_align);
              }
            }
          }

          // D. SEPARADOR
          else if (block.classList.contains("separator-block-wrapper")) {
            const sepIcon = cData.separator_icon !== undefined 
              ? cData.separator_icon 
              : (document.querySelector(`input[name="content[${idx}][separator_icon]"]:checked`)?.value || "none");
            const sepSize = cData.separator_size !== undefined 
              ? cData.separator_size 
              : (document.querySelector(`input[name="content[${idx}][separator_size]"]:checked`)?.value || "large");
            const spaceSize = cData.space_size !== undefined 
              ? cData.space_size 
              : (document.querySelector(`input[name="content[${idx}][space_size]"]:checked`)?.value || "40");

            const isSpace = (sepIcon === "none" || sepIcon === "ban" || cData.separator_mode === "space");

            if (isSpace) {
              block.className = "separator-block-wrapper w100";
              block.style.margin = "";
              block.style.boxSizing = "";
              block.style.userSelect = "";
              block.style.height = spaceSize + "px";
              block.innerHTML = "";
            } else {
              const iconRadio = document.querySelector(`input[name="content[${idx}][separator_icon]"][value="${sepIcon}"]`);
              const iconLabel = iconRadio ? iconRadio.nextElementSibling : document.querySelector(`label[for="sep-ico-${sepIcon}-${idx}"]`);
              const svgEl = iconLabel ? iconLabel.querySelector("svg") : null;
              const svgHtml = svgEl ? svgEl.outerHTML : "";

              block.className = "separator-block-wrapper w100 flex-row center-center color-text-card p0";
              block.style.margin = "10px 0";
              block.style.boxSizing = "border-box";
              block.style.userSelect = "none";
              block.style.height = "";

              if (svgHtml) {
                if (sepSize === "small") {
                  block.innerHTML = `<span class="flex-row center-center" style="width: 18px; height: 18px; font-size: 18px; flex-shrink: 0; line-height: 1;">${svgHtml}</span>`;
                } else {
                  const widthPercent = (sepSize === "medium") ? "60%" : "100%";
                  let spans = "";
                  for (let k = 0; k < 35; k++) {
                    spans += `<span class="flex-row center-center" style="width: 16px; height: 16px; font-size: 16px; flex-shrink: 0; line-height: 1;">${svgHtml}</span>`;
                  }
                  block.innerHTML = `<div style="display: flex; flex-wrap: wrap; justify-content: center; align-content: flex-start; align-items: center; gap: 8px; height: 18px; overflow: hidden; width: ${widthPercent}; max-width: ${widthPercent};">${spans}</div>`;
                }
              }
            }
          }

          // E. BANNER
          else if (block.classList.contains("banner-block-wrapper")) {
            if (cData.bg_color) {
              block.style.setProperty("background-color", cData.bg_color, "important");
            }
            if (cData.bg_opacity !== undefined) {
              const img = block.querySelector("img");
              if (img) {
                img.style.opacity = (cData.bg_opacity / 100).toFixed(2);
              }
            }
            if (cData.size) {
              const ratioMap = {
                "720x720": "720 / 720",
                "1024x720": "1024 / 720",
                "720x1024": "720 / 1024"
              };
              if (ratioMap[cData.size]) {
                block.style.aspectRatio = ratioMap[cData.size];
              }
            }
            if (cData.url !== undefined) {
              const aEl = block.querySelector("a");
              if (aEl) aEl.href = cData.url || "#";
            }
          }

          // F. PRODUCTO REGULAR
          else if (block.classList.contains("product-regular-wrapper")) {
            if (cData.title !== undefined) {
              const pTitle = block.querySelector(".capitalize-p");
              if (pTitle) {
                const svgEl = pTitle.querySelector("svg");
                pTitle.innerHTML = "";
                if (svgEl) pTitle.appendChild(svgEl);
                pTitle.appendChild(document.createTextNode(" " + cData.title));
              }
            }
            if (cData.url !== undefined) {
              const aEl = block.querySelector("a");
              if (aEl) aEl.href = cData.url || "#";
            }
            if (cData.price !== undefined || cData.offer !== undefined || cData.discount !== undefined || cData.porcentage !== undefined) {
              const priceContainer = block.querySelector(".flex-column.gap5.w50.p15");
              if (priceContainer) {
                const offerSwitch = document.querySelector(`input[type="checkbox"][name="content[${idx}][offer]"]`) || document.querySelector(`.checkbox-switch[name="content[${idx}][offer]"]`);
                const isOffer = (cData.offer !== undefined)
                  ? (cData.offer === true || cData.offer === "true" || cData.offer === 1 || cData.offer === "1")
                  : (offerSwitch ? (offerSwitch.checked || offerSwitch.getAttribute("active") === "1") : false);

                const priceInput = document.querySelector(`input[name="content[${idx}][price]"]`);
                const priceVal = (cData.price !== undefined)
                  ? String(cData.price).trim()
                  : (priceInput ? priceInput.value.trim() : (priceContainer.querySelector(".bold500, .inactive")?.textContent.replace("$", "").trim() || ""));

                const discountInput = document.querySelector(`input[name="content[${idx}][discount]"]`);
                const percentageInput = document.querySelector(`input[name="content[${idx}][porcentage]"]`);

                let discountVal = (cData.discount !== undefined)
                  ? String(cData.discount).trim()
                  : (discountInput ? discountInput.value.trim() : (priceContainer.querySelector(".bold500:last-child")?.textContent.replace("$", "").trim() || ""));

                let pctVal = (cData.porcentage !== undefined)
                  ? String(cData.porcentage).trim()
                  : (percentageInput ? percentageInput.value.trim() : "");

                // Si discountVal está vacío o es igual al precio y tenemos porcentaje > 0, calcularlo
                if ((!discountVal || discountVal === "" || discountVal === priceVal) && pctVal && parseFloat(pctVal) > 0 && parseFloat(priceVal) > 0) {
                  discountVal = String(Math.round(parseFloat(priceVal) * (1 - (parseFloat(pctVal) / 100))));
                }

                // Eliminar contenido de precios anterior
                const existingPrices = priceContainer.querySelectorAll("p:not(.capitalize-p), div.flex-column");
                existingPrices.forEach((el) => el.remove());

                if (!isOffer) {
                  if (priceVal !== "") {
                    const p = document.createElement("p");
                    p.className = "bold500";
                    p.textContent = "$" + priceVal;
                    priceContainer.appendChild(p);
                  }
                } else {
                  const effectiveDiscount = discountVal || priceVal;
                  const div = document.createElement("div");
                  div.className = "flex-column center-start gap0";
                  div.innerHTML = `
                    <p class="inactive" style="text-decoration:line-through;">$${priceVal}</p>
                    <p class="x16">Precio oferta</p>
                    <p class="bold500">$${effectiveDiscount}</p>
                  `;
                  priceContainer.appendChild(div);
                }
              }
            }
          }

          // G. GRUPO DE PRODUCTOS
          else if (block.classList.contains("product-group-wrapper")) {
            if (cData.title !== undefined) {
              let pTitle = block.querySelector("p.title-color");
              if (cData.title.trim() !== "") {
                if (!pTitle) {
                  pTitle = document.createElement("p");
                  pTitle.className = "bold600 text-c title-color w100";
                  block.insertBefore(pTitle, block.firstChild);
                }
                pTitle.textContent = cData.title;
                pTitle.style.display = "";
              } else if (pTitle) {
                pTitle.style.display = "none";
              }
            }

            // Cambio instantáneo de formato (Cuadrícula vs Carrusel)
            if (cData.layout !== undefined) {
              const variant = block.dataset.layoutVariant;
              if (variant) {
                const isSelected = (variant === cData.layout);
                if (isSelected) {
                  block.classList.remove("hidden");
                  const activeSwitch = document.querySelector(`input[name="content[${idx}][active]"]`);
                  const isAct = (cData.active !== undefined)
                    ? (cData.active === "true" || cData.active === true || cData.active === 1 || cData.active === "1")
                    : (activeSwitch ? (activeSwitch.checked || activeSwitch.getAttribute("active") === "1") : true);
                  block.style.display = isAct ? "" : "none";
                } else {
                  block.classList.add("hidden");
                  block.style.display = "none";
                }
              }
            }
          }

          // H. ENLACE REGULAR
          else if (block.classList.contains("link-item-wrapper")) {
            if (cData.title !== undefined) {
              const pTitle = block.querySelector(".cut-phrase");
              if (pTitle) {
                pTitle.textContent = cData.title;
              }
            }
            if (cData.url !== undefined) {
              const aEl = block.querySelector("a");
              if (aEl) aEl.href = cData.url || "#";
            }
          }
        });
      });

      // 2. Sub-productos de grupos de productos
      Object.keys(contentProductMap).forEach((idx) => {
        const blocks = p.querySelectorAll(`[data-content-index="${idx}"]`);
        if (!blocks.length) return;
        const subMap = contentProductMap[idx];

        blocks.forEach((block) => {
          const cards = block.querySelectorAll(".product-grid-card, .product-slide-card");

          Object.keys(subMap).forEach((pIdx) => {
            const card = cards[pIdx];
            if (!card) return;
            const pData = subMap[pIdx];

            if (pData.title !== undefined) {
              const titleEl = card.querySelector(".capitalize-p");
              if (titleEl) {
                const svgEl = titleEl.querySelector("svg");
                titleEl.innerHTML = "";
                if (svgEl) titleEl.appendChild(svgEl);
                titleEl.appendChild(document.createTextNode(" " + pData.title));
              }
            }

            if (pData.url !== undefined) {
              const aEl = card.querySelector("a");
              if (aEl) aEl.href = pData.url || "#";
            }

            if (pData.price !== undefined || pData.offer !== undefined || pData.discount !== undefined || pData.porcentage !== undefined) {
              const priceContainer = card.querySelector(".mt-auto");
              if (priceContainer) {
                const offerSwitch = document.querySelector(`input[type="checkbox"][name="content[${idx}][products][${pIdx}][offer]"]`) || document.querySelector(`.checkbox-switch[name="content[${idx}][products][${pIdx}][offer]"]`);
                const isOffer = (pData.offer !== undefined)
                  ? (pData.offer === true || pData.offer === "true" || pData.offer === 1 || pData.offer === "1")
                  : (offerSwitch ? (offerSwitch.checked || offerSwitch.getAttribute("active") === "1") : false);

                const priceInput = document.querySelector(`input[name="content[${idx}][products][${pIdx}][price]"]`);
                const priceVal = (pData.price !== undefined)
                  ? String(pData.price).trim()
                  : (priceInput ? priceInput.value.trim() : (priceContainer.querySelector(".bold500, .inactive")?.textContent.replace("$", "").trim() || ""));

                const discountInput = document.querySelector(`input[name="content[${idx}][products][${pIdx}][discount]"]`);
                const percentageInput = document.querySelector(`input[name="content[${idx}][products][${pIdx}][porcentage]"]`);

                let discountVal = (pData.discount !== undefined)
                  ? String(pData.discount).trim()
                  : (discountInput ? discountInput.value.trim() : (priceContainer.querySelector(".text-success")?.textContent.replace("$", "").trim() || ""));

                let pctVal = (pData.porcentage !== undefined)
                  ? String(pData.porcentage).trim()
                  : (percentageInput ? percentageInput.value.trim() : "");

                if ((!discountVal || discountVal === "" || discountVal === priceVal) && pctVal && parseFloat(pctVal) > 0 && parseFloat(priceVal) > 0) {
                  discountVal = String(Math.round(parseFloat(priceVal) * (1 - (parseFloat(pctVal) / 100))));
                }

                if (!isOffer) {
                  priceContainer.innerHTML = priceVal !== "" ? `<p class="bold500">$${priceVal}</p>` : "";
                } else {
                  const effectiveDiscount = discountVal || priceVal;
                  priceContainer.innerHTML = `
                    <div class="flex-column gap0">
                      <p class="inactive x16" style="text-decoration: line-through;">$${priceVal}</p>
                      <p class="bold500 text-success">$${effectiveDiscount}</p>
                    </div>
                  `;
                }
              }
            }
          });
        });
      });
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
          const container = cb.closest(".checkbox-switch-container") || cb;
          const hiddenInput = container.previousElementSibling && container.previousElementSibling.name === cb.name ? container.previousElementSibling : null;
          if (hiddenInput && hiddenInput.type === "hidden") {
            hiddenInput.disabled = isChecked;
          }

          if (cb.name === "hide") {
            const statusText = document.getElementById("profile-visibility-status-text");
            if (statusText) {
              statusText.textContent = isChecked ? "oculto" : "visible";
            }
          }

          syncConditionalUI(cb);
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

        // Actualizar sliders de rango
        if (input.type === "range") {
          input.style.setProperty("--range-progress", `${val}%`);
          const valTargetId = input.dataset.valTarget;
          if (valTargetId) {
            const valEl = document.getElementById(valTargetId);
            if (valEl) valEl.textContent = `${val}%`;
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
   * Muestra un aviso visual flotante (toast) con estado de la acción en curso.
   *
   * @param {string} message Texto a mostrar
   * @param {"loading"|"success"|"error"} [type="loading"] Tipo de aviso
   * @param {number} [duration=0] Duración en ms antes de auto-ocultarse
   */
  function showActionToast(message, type = "loading", duration = 0) {
    let toast = document.getElementById("action-feedback-toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "action-feedback-toast";
      toast.style.position = "fixed";
      toast.style.bottom = "28px";
      toast.style.left = "50%";
      toast.style.transform = "translateX(-50%) translateY(20px)";
      toast.style.opacity = "0";
      toast.style.transition = "all 0.25s cubic-bezier(0.16, 1, 0.3, 1)";
      toast.style.zIndex = "999999";
      toast.style.pointerEvents = "none";
      toast.style.borderRadius = "50px";
      toast.style.padding = "10px 22px";
      toast.style.fontWeight = "500";
      toast.style.fontSize = "14px";
      toast.style.boxShadow = "0 8px 24px rgba(0,0,0,0.18)";
      toast.style.display = "flex";
      toast.style.alignItems = "center";
      toast.style.gap = "10px";
      document.body.appendChild(toast);
    }

    if (toast.__timer) {
      clearTimeout(toast.__timer);
      toast.__timer = null;
    }

    if (type === "loading") {
      toast.style.background = "rgba(25, 25, 25, 0.92)";
      toast.style.backdropFilter = "blur(8px)";
      toast.style.color = "#ffffff";
      toast.innerHTML = `
        <span class="save-btn-spinner" style="margin-right: 0; width: 13px; height: 13px; border-width: 2px;"></span>
        <span>${message}</span>
      `;
    } else if (type === "success") {
      toast.style.background = "rgba(22, 101, 52, 0.95)";
      toast.style.backdropFilter = "blur(8px)";
      toast.style.color = "#ffffff";
      toast.innerHTML = `
        <span style="font-size: 15px; line-height: 1; font-weight: bold;">✓</span>
        <span>${message}</span>
      `;
    } else if (type === "error") {
      toast.style.background = "rgba(185, 28, 28, 0.95)";
      toast.style.backdropFilter = "blur(8px)";
      toast.style.color = "#ffffff";
      toast.innerHTML = `
        <span style="font-size: 15px; line-height: 1; font-weight: bold;">✕</span>
        <span>${message}</span>
      `;
    }

    requestAnimationFrame(() => {
      toast.style.transform = "translateX(-50%) translateY(0)";
      toast.style.opacity = "1";
    });

    if (duration > 0) {
      toast.__timer = setTimeout(() => {
        hideActionToast();
      }, duration);
    }
  }

  /**
   * Oculta suavemente el aviso flotante (toast).
   */
  function hideActionToast() {
    const toast = document.getElementById("action-feedback-toast");
    if (toast) {
      toast.style.transform = "translateX(-50%) translateY(20px)";
      toast.style.opacity = "0";
    }
  }

  /**
   * Muestra el aviso visual directamente en la posición del bloque que se está eliminando.
   *
   * @param {HTMLElement} panelElement Elemento contenedor del bloque en el panel
   * @param {string} message Mensaje a mostrar (ej: "Eliminando bloque...")
   */
  function showInPlaceDeleteNotice(panelElement, message = "Eliminando bloque...") {
    if (!panelElement || panelElement.querySelector(".in-place-delete-notice")) return;

    panelElement.classList.add("item-deleting-in-place");
    panelElement.style.pointerEvents = "none";

    const currentHeight = panelElement.offsetHeight;
    if (currentHeight > 0) {
      panelElement.style.minHeight = Math.min(currentHeight, 70) + "px";
    }

    // Ocultar elementos visuales existentes sin tocar los inputs (para que FormData los conserve)
    Array.from(panelElement.children).forEach((child) => {
      if (child.tagName !== "INPUT" && !child.classList.contains("in-place-delete-notice")) {
        child.dataset.prevDisplay = child.style.display || "";
        child.style.display = "none";
      }
    });

    // Crear el aviso en la posición exacta del bloque
    const noticeEl = document.createElement("div");
    noticeEl.className = "in-place-delete-notice flex-row center-center gap10 w100 p15 text-c";
    noticeEl.style.minHeight = "48px";
    noticeEl.style.opacity = "0";
    noticeEl.style.transform = "scale(0.96)";
    noticeEl.style.transition = "all 0.25s cubic-bezier(0.16, 1, 0.3, 1)";
    noticeEl.innerHTML = `
      <span class="save-btn-spinner save-btn-spinner-dark" style="margin-right: 0; width: 15px; height: 15px; border-width: 2px;"></span>
      <span class="bold500 texto x15">${message}</span>
    `;

    panelElement.appendChild(noticeEl);

    requestAnimationFrame(() => {
      noticeEl.style.opacity = "1";
      noticeEl.style.transform = "scale(1)";
    });
  }

  /**
   * Restaura el contenido visual del bloque si la eliminación falló en el servidor.
   *
   * @param {HTMLElement} panelElement Elemento contenedor del bloque
   */
  function restoreInPlaceBlock(panelElement) {
    if (!panelElement) return;
    panelElement.classList.remove("item-deleting-in-place");
    panelElement.style.pointerEvents = "";
    panelElement.style.minHeight = "";

    const noticeEl = panelElement.querySelector(".in-place-delete-notice");
    if (noticeEl) {
      noticeEl.remove();
    }

    Array.from(panelElement.children).forEach((child) => {
      if (child.tagName !== "INPUT" && child.dataset.prevDisplay !== undefined) {
        child.style.display = child.dataset.prevDisplay;
        delete child.dataset.prevDisplay;
      }
    });
  }

  /**
   * Anima la salida y colapso de un elemento visual (panel o preview).
   *
   * @param {HTMLElement} element Elemento a colapsar y ocultar
   */
  function animateElementRemoval(element) {
    if (!element || element.classList.contains("item-deleting")) return;
    element.classList.add("item-deleting");
    element.style.pointerEvents = "none";
    const startHeight = element.offsetHeight;
    element.style.maxHeight = startHeight + "px";
    
    // Forzar reflujo para que la transición CSS desde startHeight hasta 0 funcione
    void element.offsetHeight;
    
    element.style.opacity = "0";
    element.style.transform = "scale(0.92) translateY(-8px)";
    element.style.maxHeight = "0px";
    element.style.paddingTop = "0px";
    element.style.paddingBottom = "0px";
    element.style.marginTop = "0px";
    element.style.marginBottom = "0px";
    element.style.overflow = "hidden";
  }

  /**
   * Ejecuta la eliminación optimista en la interfaz (panel + vista previa).
   *
   * @param {HTMLElement} [triggerElement] Elemento disparador (checkbox, botón)
   * @param {string} [targetId] ID del checkbox objetivo si vino de un label
   */
  function performOptimisticDelete(triggerElement = null, targetId = null) {
    let panelElement = null;
    let previewElements = [];
    let deleteNotice = "Eliminando bloque...";

    const idStr = targetId || (triggerElement && triggerElement.id ? triggerElement.id : "");
    const nameStr = triggerElement && triggerElement.name ? triggerElement.name : "";

    // 1. Caso: delete-link-X o content[X][delete]
    let contentIndex = null;
    if (idStr.startsWith("delete-link-")) {
      contentIndex = idStr.replace("delete-link-", "");
    } else if (nameStr) {
      const match = nameStr.match(/content\[(\d+)\]\[delete\]/);
      if (match) contentIndex = match[1];
    }

    if (contentIndex !== null) {
      panelElement = document.getElementById("content-item-" + contentIndex) || (triggerElement ? triggerElement.closest(".sortable-item, .content-block") : null);
      previewElements = Array.from(document.querySelectorAll(`.user-profile-preview [data-content-index="${contentIndex}"]`));
      deleteNotice = "Eliminando bloque...";
    }

    // 2. Caso: delete-rrss-X o rrss[X][delete]
    let rrssIndex = null;
    if (idStr.startsWith("delete-rrss-")) {
      rrssIndex = idStr.replace("delete-rrss-", "");
    } else if (nameStr) {
      const match = nameStr.match(/rrss\[(\d+)\]\[delete\]/);
      if (match) rrssIndex = match[1];
    }

    if (rrssIndex !== null) {
      panelElement = document.getElementById("rrss-item-" + rrssIndex) || (triggerElement ? triggerElement.closest(".sortable-item, .rrss-item") : null);
      const rrssNameInput = panelElement ? panelElement.querySelector(`input[name="rrss[${rrssIndex}][0]"]`) : null;
      const socialName = rrssNameInput ? rrssNameInput.value : "";
      if (socialName) {
        previewElements = Array.from(document.querySelectorAll(`.user-profile-preview [data-link-id="rrss_${socialName}"], .user-profile-preview [aria-label="${socialName}"]`));
      }
      deleteNotice = "Eliminando red social...";
    }

    // 3. Caso: sub-producto dentro de product_group
    if (nameStr.includes("delete_sub_product")) {
      panelElement = triggerElement ? (triggerElement.closest(".sub-product-item, .product-item, [class*='sub-product']") || triggerElement.closest("div.flex-column, div.flex-row")) : null;
      deleteNotice = "Eliminando sub-producto...";
    } else if (nameStr.includes("delete_img")) {
      deleteNotice = "Eliminando imagen...";
    }

    // Mostrar el aviso directamente en la posición del bloque en el panel
    if (panelElement) {
      showInPlaceDeleteNotice(panelElement, deleteNotice);
    }

    // Animar salida en la vista previa
    if (previewElements && previewElements.length > 0) {
      previewElements.forEach((pItem) => {
        animateElementRemoval(pItem);
      });
    }

    // Activar inmediatamente los botones Guardar / Descartar
    document.dispatchEvent(new CustomEvent("designDraftChanged"));
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

    const isDeleteAction = triggerElement && triggerElement.name && (
      triggerElement.name.includes("[delete]") ||
      triggerElement.name.includes("delete_sub_product") ||
      triggerElement.name.includes("delete_img")
    );

    // Si no se había ejecutado el borrado optimista aún, ejecutarlo ahora
    if (isDeleteAction) {
      performOptimisticDelete(triggerElement);
    }

    // Deshabilitar temporalmente clics en botones de eliminación para evitar peticiones duplicadas
    document.querySelectorAll('label[for^="delete-link-"], label[for^="delete-rrss-"], .modal-btn').forEach((btn) => {
      btn.style.pointerEvents = "none";
    });

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
      let deletedPrefix = null;
      if (isDeleteAction && triggerElement.name) {
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
            sidebar.innerHTML = data.sidebarStatusHtml;
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
              refreshAllBannersState();
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
      if (isDeleteAction) {
        // Restaurar bloques en el panel
        document.querySelectorAll(".item-deleting-in-place").forEach((el) => {
          restoreInPlaceBlock(el);
        });
        // Revertir animación de los elementos en preview
        document.querySelectorAll(".item-deleting").forEach((el) => {
          el.classList.remove("item-deleting");
          el.style.opacity = "";
          el.style.transform = "";
          el.style.maxHeight = "";
          el.style.paddingTop = "";
          el.style.paddingBottom = "";
          el.style.marginTop = "";
          el.style.marginBottom = "";
          el.style.overflow = "";
          el.style.pointerEvents = "";
        });
      }
      return false;
    } finally {
      document.querySelectorAll('label[for^="delete-link-"], label[for^="delete-rrss-"], .modal-btn').forEach((btn) => {
        btn.style.pointerEvents = "";
      });
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
        if (submitter.name && (submitter.name.includes("delete_sub_product") || submitter.name.includes("delete_img"))) {
          performOptimisticDelete(submitter);
        }
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
        
        // Cerrar modal inmediatamente
        document.querySelectorAll(".modal-overlay, .custom-modal-overlay").forEach((m) => m.remove());
        
        // Aplicar eliminación optimista inmediata en panel y preview
        performOptimisticDelete(checkbox, targetId);

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
          if (submitBtn.name && (submitBtn.name.includes("delete_sub_product") || submitBtn.name.includes("delete_img"))) {
            performOptimisticDelete(submitBtn);
          }
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
      if (target.type === "range") {
        target.style.setProperty("--range-progress", `${target.value}%`);
        const valTargetId = target.dataset.valTarget;
        if (valTargetId) {
          const valEl = document.getElementById(valTargetId);
          if (valEl) valEl.textContent = `${target.value}%`;
        }
      }
      setDraftField(target.name, target.value);
      applyDraftToPreview(getDraft());

      // Sincronizar estado de activación/desbloqueo del bloque en tiempo real mientras se escribe
      const block = target.closest(".sortable-item.content-block");
      if (block) {
        const idx = getBlockIndex(block);
        if (idx !== null) {
          syncBlockActiveState(block, idx);
        }
      }
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
      const block = target.closest(".sortable-item.content-block");
      if (block) {
        const idx = getBlockIndex(block);
        if (idx !== null) {
          syncBlockActiveState(block, idx);
        }
      }
      return;
    }

    // Checkboxes
    if (target.type === "checkbox") {
      setDraftField(target.name, target.checked ? (target.value || "true") : "false");
      applyDraftToPreview(getDraft());

      // Si es el switch de ocultar perfil, sincronizar el texto explicativo de la tarjeta
      if (target.name === "hide") {
        const statusText = document.getElementById("profile-visibility-status-text");
        if (statusText) {
          statusText.textContent = target.checked ? "oculto" : "visible";
        }
      }

      // Sincronizar visibilidad de elementos en la vista previa al conmutar switches de activación de bloque
      if (target.matches(".checkbox-switch")) {
        const match = target.name && target.name.match(/^content\[(\d+)\]\[active\]$/);
        if (match) {
          const idx = match[1];
          document.querySelectorAll(".user-profile-preview").forEach((preview) => {
            const items = preview.querySelectorAll(`[data-content-index="${idx}"]`);
            items.forEach((item) => {
              const variant = item.dataset.layoutVariant;
              if (variant) {
                const selectedLayout = document.querySelector(`input[name="content[${idx}][layout]"]:checked`)?.value || "grid";
                if (variant === selectedLayout) {
                  item.style.display = target.checked ? "" : "none";
                  if (target.checked) item.classList.remove("hidden");
                } else {
                  item.style.display = "none";
                  item.classList.add("hidden");
                }
              } else {
                item.style.display = target.checked ? "" : "none";
              }
            });
          });
        }
        return;
      }
    }

    // Selectores y otros inputs
    if (target.type !== "file") {
      setDraftField(target.name, target.value);
      applyDraftToPreview(getDraft());
      const block = target.closest(".sortable-item.content-block");
      if (block) {
        const idx = getBlockIndex(block);
        if (idx !== null) {
          syncBlockActiveState(block, idx);
        }
      }
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
        const subItem = target.closest(".sub-product-item");
        const block = target.closest(".sortable-item") || target.closest(".content-block");
        if (subItem) {
          const thumb = subItem.querySelector("figure img");
          if (thumb) thumb.src = previewUrl;
        } else if (block) {
          const thumb = block.querySelector("figure img");
          if (thumb) thumb.src = previewUrl;
        }

        // 2. Actualizar imagen en la vista previa
        document.querySelectorAll(".user-profile-preview").forEach((preview) => {
          const items = preview.querySelectorAll(`[data-content-index="${itemIdx}"]`);
          items.forEach((item) => {
            if (subIdx !== undefined) {
              const card = item.querySelector(`[data-sub-index="${subIdx}"]`) 
                || item.querySelectorAll(".product-grid-card, .product-slide-card")[subIdx];
              if (card) {
                let fig = card.querySelector("figure");
                let img = card.querySelector("figure img");
                if (!fig) {
                  fig = document.createElement("figure");
                  fig.className = "w100 ar-square overflow-hidden";
                  img = document.createElement("img");
                  img.className = "cover w100 h100 br12";
                  fig.appendChild(img);
                  const link = card.querySelector("a");
                  if (link) {
                    link.insertBefore(fig, link.firstElementChild);
                  } else {
                    card.insertBefore(fig, card.firstElementChild);
                  }
                }
                if (img) {
                  img.src = previewUrl;
                }
                fig.style.display = "";
              }
            } else {
              let fig = item.querySelector("figure");
              let previewImg = item.querySelector("figure img") || item.querySelector("img");
              if (previewImg) {
                previewImg.src = previewUrl;
              }
              if (fig) {
                fig.style.display = "";
              }
            }
          });
        });

        // 3. Sincronizar estado del bloque y habilitar/activar switch en tiempo real
        if (block) {
          const bIdx = getBlockIndex(block) || itemIdx;
          syncBlockActiveState(block, bIdx, { previewUrl });
        }
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
    while (isSubmittingRemoteAjax) {
      await new Promise((resolve) => setTimeout(resolve, 80));
    }

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
        document.querySelectorAll(".sidebar-profile-status").forEach((sidebar) => {
          sidebar.innerHTML = data.sidebarStatusHtml;
        });
      }

      // Si data.card tiene hide, asegurar que el texto explicativo y el switch estén sincronizados
      if (data.card && data.card.hide !== undefined) {
        const isHidden = (data.card.hide === true || data.card.hide === "true" || data.card.hide === 1 || data.card.hide === "1");
        const statusText = document.getElementById("profile-visibility-status-text");
        if (statusText) {
          statusText.textContent = isHidden ? "oculto" : "visible";
        }
        const hideCheckbox = document.querySelector('input[type="checkbox"][name="hide"]');
        if (hideCheckbox) {
          hideCheckbox.checked = isHidden;
          hideCheckbox.setAttribute("active", isHidden ? "1" : "2");
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
    while (isSubmittingRemoteAjax) {
      await new Promise((resolve) => setTimeout(resolve, 80));
    }

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
            refreshAllBannersState();
          }
        }
      }

      // 3. Restaurar banner de estado en barra lateral si viene en la respuesta
      if (data.sidebarStatusHtml) {
        document.querySelectorAll(".sidebar-profile-status").forEach((sidebar) => {
          sidebar.innerHTML = data.sidebarStatusHtml;
        });
      }

      if (data.card && data.card.hide !== undefined) {
        const isHidden = (data.card.hide === true || data.card.hide === "true" || data.card.hide === 1 || data.card.hide === "1");
        const statusText = document.getElementById("profile-visibility-status-text");
        if (statusText) {
          statusText.textContent = isHidden ? "oculto" : "visible";
        }
        const hideCheckbox = document.querySelector('input[type="checkbox"][name="hide"]');
        if (hideCheckbox) {
          hideCheckbox.checked = isHidden;
          hideCheckbox.setAttribute("active", isHidden ? "1" : "2");
        }
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
    refreshAllBannersState();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initRestoration);
  } else {
    initRestoration();
  }

  window.addEventListener("pageshow", initRestoration);
}
