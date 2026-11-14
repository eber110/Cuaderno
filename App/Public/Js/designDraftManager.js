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
  const INITIAL_KEY = `cuaderno_design_initial_${user}`;
  const DRAFT_KEY = `cuaderno_design_draft_${user}`;

  // Caché en RAM para evitar lecturas/escrituras síncronas repetitivas a localStorage
  let cachedInitialState = null;
  let cachedInitialJson = null;
  let cachedDraftState = null;
  let cachedDraftJson = null;
  let persistDraftTimer = null;
  let isDraftDirtyForPersist = false;
  let rafPreviewTimer = null;

  // Registro exclusivo en RAM de Blob URLs pendientes (nunca persistidas en localStorage)
  const pendingBlobUrls = new Map();
  const pendingUploadFlags = new Set();

  /**
   * Elimina cualquier URL temporal blob: o data: heredada en un estado JSON antes de persistirlo o usarlo.
   * @param {Object|null} state
   * @returns {Object|null}
   */
  function sanitizeBlobStringsInState(state) {
    if (!state || typeof state !== "object") return state;
    if (typeof state.avatar === "string" && (state.avatar.startsWith("blob:") || state.avatar.startsWith("data:"))) {
      state.avatar = "no-user.webp";
    }
    if (state.backCard && typeof state.backCard.back_image === "string" && (state.backCard.back_image.startsWith("blob:") || state.backCard.back_image.startsWith("data:"))) {
      state.backCard.back_image = "";
    }
    if (Array.isArray(state.content)) {
      state.content.forEach((blk) => {
        if (!blk || typeof blk !== "object") return;
        if (typeof blk.imgSrc === "string" && (blk.imgSrc.startsWith("blob:") || blk.imgSrc.startsWith("data:"))) {
          blk.imgSrc = blk.img && blk.img !== "no-image.webp" ? `/Uploads/${blk.img}` : "";
        }
        if (Array.isArray(blk.products)) {
          blk.products.forEach((sub) => {
            if (sub && typeof sub.imgSrc === "string" && (sub.imgSrc.startsWith("blob:") || sub.imgSrc.startsWith("data:"))) {
              sub.imgSrc = sub.img && sub.img !== "no-image.webp" ? `/Uploads/${sub.img}` : "";
            }
          });
        }
      });
    }
    return state;
  }

  /**
   * Revoca de memoria una Blob URL pendiente y limpia su bandera de subida.
   * @param {string} key
   */
  function revokePendingBlob(key) {
    if (!key) return;
    const oldUrl = pendingBlobUrls.get(key);
    if (oldUrl) {
      try { URL.revokeObjectURL(oldUrl); } catch (_) {}
      pendingBlobUrls.delete(key);
    }
    pendingUploadFlags.delete(key);
  }

  /**
   * Crea y registra una nueva Blob URL temporal para vista previa revocando cualquier anterior.
   * @param {string} key
   * @param {File|Blob} file
   * @returns {string}
   */
  function setPendingBlob(key, file) {
    revokePendingBlob(key);
    const url = URL.createObjectURL(file);
    pendingBlobUrls.set(key, url);
    pendingUploadFlags.add(key);
    return url;
  }

  /**
   * Revoca todas las Blob URLs registradas en RAM (libera memoria del navegador).
   */
  function clearAllPendingBlobs() {
    pendingBlobUrls.forEach((url) => {
      try { URL.revokeObjectURL(url); } catch (_) {}
    });
    pendingBlobUrls.clear();
    pendingUploadFlags.clear();
  }

  /**
   * Obtiene el Blob URL temporal activo para un bloque o sub-ítem si existe en RAM.
   * @param {string|null} blockId
   * @param {string|number|null} [subIdx=null]
   * @returns {string|null}
   */
  function getPendingBlockBlobUrl(blockId, subIdx = null) {
    if (!blockId) return null;
    const key = (subIdx !== null && subIdx !== undefined)
      ? `block:${blockId}:sub:${subIdx}`
      : `block:${blockId}`;
    return pendingBlobUrls.get(key) || null;
  }

  /**
   * Revoca todas las Blob URLs asociadas a un bloque (incluido sus sub-productos).
   * @param {string|null} blockId
   */
  function revokeBlockBlobs(blockId) {
    if (!blockId) return;
    const prefix = `block:${blockId}`;
    Array.from(pendingBlobUrls.keys()).forEach((k) => {
      if (k === prefix || k.startsWith(`${prefix}:`)) {
        revokePendingBlob(k);
      }
    });
  }

  /**
   * Ejecuta una actualización visual coalescida al siguiente frame de animación.
   * @param {Function} callback
   */
  function scheduleRafTask(callback) {
    if (rafPreviewTimer) {
      cancelAnimationFrame(rafPreviewTimer);
    }
    rafPreviewTimer = requestAnimationFrame(() => {
      rafPreviewTimer = null;
      callback();
    });
  }

  /**
   * Vuelca inmediatamente el borrador en RAM a localStorage si hay escritura pendiente.
   */
  function flushDraftSave() {
    if (persistDraftTimer) {
      clearTimeout(persistDraftTimer);
      persistDraftTimer = null;
    }
    if (isDraftDirtyForPersist && cachedDraftState) {
      cachedDraftJson = JSON.stringify(cachedDraftState);
      try {
        localStorage.setItem(DRAFT_KEY, cachedDraftJson);
      } catch (e) {
        console.warn("Error al escribir en localStorage:", e);
      }
      isDraftDirtyForPersist = false;
    }
  }

  window.addEventListener("pagehide", flushDraftSave);
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "hidden") {
      flushDraftSave();
    }
  });

  /**
   * Obtiene el estado inicial (oficial) de la tarjeta desde caché RAM o localStorage.
   * @returns {Object|null}
   */
  function getInitialState() {
    if (cachedInitialState !== null) {
      return cachedInitialState;
    }
    try {
      const data = localStorage.getItem(INITIAL_KEY);
      if (!data) return null;
      cachedInitialState = sanitizeBlobStringsInState(JSON.parse(data));
      cachedInitialJson = JSON.stringify(cachedInitialState);
      return cachedInitialState;
    } catch (e) {
      return null;
    }
  }

  /**
   * Guarda el estado inicial oficial en caché RAM y localStorage.
   * @param {Object|null} state
   */
  function setInitialState(state) {
    cachedInitialState = state ? sanitizeBlobStringsInState(JSON.parse(JSON.stringify(state))) : null;
    cachedInitialJson = cachedInitialState ? JSON.stringify(cachedInitialState) : null;
    try {
      if (cachedInitialJson !== null) {
        localStorage.setItem(INITIAL_KEY, cachedInitialJson);
      } else {
        localStorage.removeItem(INITIAL_KEY);
      }
    } catch (e) {
      console.warn("Error al guardar INITIAL_KEY:", e);
    }
  }

  /**
   * Obtiene el estado de trabajo actual (borrador estructurado) desde caché RAM o localStorage.
   * @returns {Object|null}
   */
  function getDraftState() {
    if (cachedDraftState !== null) {
      return cachedDraftState;
    }
    try {
      const data = localStorage.getItem(DRAFT_KEY);
      if (!data) return null;
      cachedDraftState = sanitizeBlobStringsInState(JSON.parse(data));
      cachedDraftJson = JSON.stringify(cachedDraftState);
      return cachedDraftState;
    } catch (e) {
      return null;
    }
  }

  /**
   * Guarda de forma inmediata (síncrona) el borrador en RAM y localStorage.
   * @param {Object|null} state
   */
  function setDraftStateImmediate(state) {
    if (persistDraftTimer) {
      clearTimeout(persistDraftTimer);
      persistDraftTimer = null;
    }
    cachedDraftState = state ? sanitizeBlobStringsInState(JSON.parse(JSON.stringify(state))) : null;
    cachedDraftJson = cachedDraftState ? JSON.stringify(cachedDraftState) : null;
    isDraftDirtyForPersist = false;
    try {
      if (cachedDraftJson !== null) {
        localStorage.setItem(DRAFT_KEY, cachedDraftJson);
      } else {
        localStorage.removeItem(DRAFT_KEY);
      }
    } catch (e) {
      console.warn("Error al escribir en localStorage:", e);
    }
  }

  /**
   * Persiste el estado de trabajo estructurado en RAM con escritura debounced en localStorage y notifica cambios.
   * @param {Object} draft
   * @param {{immediate?: boolean}} [options={}]
   */
  function saveDraftState(draft, options = {}) {
    cachedDraftState = draft;
    if (options && options.immediate) {
      flushDraftSave();
      cachedDraftJson = JSON.stringify(draft);
      try {
        localStorage.setItem(DRAFT_KEY, cachedDraftJson);
      } catch (e) {
        console.warn("Error al escribir en localStorage:", e);
      }
      isDraftDirtyForPersist = false;
    } else {
      isDraftDirtyForPersist = true;
      if (persistDraftTimer) clearTimeout(persistDraftTimer);
      persistDraftTimer = setTimeout(flushDraftSave, 200);
    }
    notifyDraftState();
  }

  /**
   * Comprueba si el borrador de trabajo difiere del estado inicial oficial o hay archivos pendientes.
   * Cero consultas a la base de datos y mínimo overhead en RAM.
   * @returns {boolean}
   */
  function hasDraft() {
    if (pendingUploadFlags.size > 0 || pendingBlobUrls.size > 0) {
      return true;
    }
    try {
      const inputs = document.querySelectorAll("input[type='file']");
      for (let i = 0; i < inputs.length; i++) {
        if (inputs[i].files && inputs[i].files.length > 0 && inputs[i].files[0].size > 0) {
          return true;
        }
      }
    } catch (_) {}

    const initObj = getInitialState();
    const draftObj = getDraftState();
    if (!initObj || !draftObj) return false;
    const initStr = cachedInitialJson ?? JSON.stringify(initObj);
    const draftStr = isDraftDirtyForPersist ? JSON.stringify(draftObj) : (cachedDraftJson ?? JSON.stringify(draftObj));
    return initStr !== draftStr;
  }

  /**
   * Aplana un objeto jerárquico a un diccionario de claves planas para retrocompatibilidad
   * con funciones que esperan paths como 'content[0][title]' o 'back_perfil'.
   * @param {Object} obj
   * @param {string} prefix
   * @returns {Object}
   */
  function flattenObject(obj, prefix = '') {
    if (!obj || typeof obj !== 'object') return {};
    let result = {};
    for (const [key, value] of Object.entries(obj)) {
      const newKey = prefix ? `${prefix}[${key}]` : key;
      if (value && typeof value === 'object' && !(value instanceof File) && !(value instanceof Blob)) {
        if (Array.isArray(value)) {
          value.forEach((item, idx) => {
            if (item && typeof item === 'object') {
              Object.assign(result, flattenObject(item, `${newKey}[${idx}]`));
            } else {
              result[`${newKey}[${idx}]`] = item;
            }
          });
        } else {
          Object.assign(result, flattenObject(value, newKey));
          if (key === 'back_perfil' || key === 'style_back' || key === 'back_image' || key === 'back_video') {
            result[key] = value;
          }
        }
      } else {
        result[newKey] = value;
        if (!prefix) {
          result[key] = value;
        }
      }
    }
    if (obj.titleColor && !result.title_color) result.title_color = obj.titleColor;
    if (obj.colorText && !result.color_text) result.color_text = obj.colorText;
    if (Array.isArray(obj.borders)) {
      result.borders = obj.borders.join(",");
    }
    if (obj.backCard && typeof obj.backCard === "object") {
      if (obj.backCard.back_perfil && !result.back_perfil) result.back_perfil = obj.backCard.back_perfil;
      if (obj.backCard.style_back && !result.style_back) result.style_back = obj.backCard.style_back;
      Object.entries(obj.backCard).forEach(([k, v]) => {
        if (result[k] === undefined) result[k] = v;
      });
    }
    if (obj.voidHero && obj.voidHero.space !== undefined && result.void_space === undefined) {
      result.void_space = obj.voidHero.space;
    }
    return result;
  }

  function appendFormDataRecursive(fd, data, parentKey = '') {
    if (data && typeof data === 'object' && !(data instanceof File) && !(data instanceof Blob)) {
      Object.keys(data).forEach(key => {
        const fieldKey = parentKey ? `${parentKey}[${key}]` : key;
        appendFormDataRecursive(fd, data[key], fieldKey);
      });
    } else {
      const existing = parentKey ? fd.get(parentKey) : null;
      if (existing instanceof File && existing.size > 0) {
        return;
      }
      if (parentKey && !parentKey.includes('[')) {
        fd.delete(parentKey);
      }
      fd.append(parentKey, data === null ? '' : data);
    }
  }

  /**
   * Inicializa la arquitectura de Dos JSONs en localStorage:
   * 1. INITIAL_KEY: versión inicial/oficial de referencia.
   * 2. DRAFT_KEY: versión de trabajo viva donde se acumulan cambios locales.
   */
  function initJsonState() {
    if (window.INITIAL_PROFILE_JSON) {
      try {
        const serverState = window.INITIAL_PROFILE_JSON;
        let storedInitial = getInitialState();
        let storedDraft = getDraftState();

        const serverUpdatedAt = parseInt(serverState.last_updated_at || 0, 10);
        const storedUpdatedAt = parseInt(storedInitial?.last_updated_at || 0, 10);

        // Si no existe INITIAL_KEY o el servidor tiene una versión más nueva (ej. tras guardar/publicar)
        if (!storedInitial || (serverUpdatedAt > 0 && serverUpdatedAt > storedUpdatedAt)) {
          setInitialState(serverState);
          setDraftStateImmediate(serverState);
          return;
        }

        // Si no existe DRAFT_KEY, clonar idéntico al estado inicial
        if (!storedDraft) {
          setDraftStateImmediate(storedInitial);
        } else {
          // Si ambos existían y son idénticos (no hay cambios pendientes), y el servidor cambió, sincronizar ambos
          if (storedInitial && JSON.stringify(storedInitial) === JSON.stringify(storedDraft)) {
            if (JSON.stringify(storedInitial) !== JSON.stringify(serverState)) {
              setInitialState(serverState);
              setDraftStateImmediate(serverState);
            }
          }
        }
      } catch (e) {
        console.error("Error inicializando estado Two-JSON", e);
      }
    }
  }
  
  initJsonState();

  function linkPreviewBlockIds() {
    const previewContainers = document.querySelectorAll(".preview-widget-container, #preview-widget-container");
    previewContainers.forEach((preview) => {
      preview.querySelectorAll("[data-content-index]").forEach((el) => {
        if (el.getAttribute("data-block-id")) return;
        const idx = el.getAttribute("data-content-index");
        const editorItem = document.getElementById("content-item-" + idx);
        if (editorItem) {
          const blkId = editorItem.getAttribute("data-block-id");
          if (blkId) el.setAttribute("data-block-id", blkId);
        }
      });
    });
  }

  function reindexSubProductsInBlock(item, blockIndex) {
    if (!item || item.getAttribute("data-type") !== "product_group") return;
    const subItems = Array.from(item.querySelectorAll(".sub-product-item"));
    const prodCount = subItems.length;

    subItems.forEach((subItem, pIdx) => {
      const lbl = subItem.querySelector(".sub-product-label");
      if (lbl) lbl.textContent = `Producto ${pIdx + 1}`;

      const delBtn = subItem.querySelector(".delete-sub-product-btn, button[name*='[delete_sub_product]']");
      if (delBtn) {
        delBtn.name = `content[${blockIndex}][delete_sub_product]`;
        delBtn.value = String(pIdx);
        delBtn.style.display = prodCount > 2 ? "" : "none";
      }

      const minBadge = subItem.querySelector(".min-sub-product-badge");
      if (minBadge) {
        minBadge.style.display = prodCount > 2 ? "none" : "";
      }

      subItem.querySelectorAll("*").forEach((el) => {
        const name = el.getAttribute("name");
        if (name && name.includes("[products][")) {
          el.setAttribute("name", name.replace(/^content\[[^\]]+\]\[products\]\[[^\]]+\]/, `content[${blockIndex}][products][${pIdx}]`));
        } else if (name && name.startsWith("content_img_")) {
          el.setAttribute("name", `content_img_${blockIndex}_${pIdx}`);
        }

        const id = el.getAttribute("id");
        if (id && id.startsWith("offer-switch-")) {
          el.setAttribute("id", `offer-switch-${blockIndex}-${pIdx}`);
        }
        const forAttr = el.getAttribute("for");
        if (forAttr && forAttr.startsWith("offer-switch-")) {
          el.setAttribute("for", `offer-switch-${blockIndex}-${pIdx}`);
        }
      });
    });

    const counterEl = item.querySelector(".sub-products-counter");
    if (counterEl) counterEl.textContent = `${prodCount} / 8`;

    const addBtn = item.querySelector(".add-sub-product-btn, button[name*='[add_sub_product]']");
    if (addBtn) {
      addBtn.name = `content[${blockIndex}][add_sub_product]`;
      addBtn.style.display = prodCount < 8 ? "" : "none";
      const addLbl = addBtn.querySelector(".add-sub-product-btn-label");
      if (addLbl) addLbl.textContent = `Añadir producto al grupo (${prodCount}/8)`;
    }

    const maxMsg = item.querySelector(".max-sub-products-msg");
    if (maxMsg) {
      maxMsg.style.display = prodCount >= 8 ? "" : "none";
    }

    const isOdd = prodCount % 2 !== 0;
    const oddWarn = item.querySelector(".odd-products-warning");
    if (oddWarn) {
      oddWarn.style.display = isOdd ? "" : "none";
      const oddCount = oddWarn.querySelector(".odd-products-count");
      if (oddCount) oddCount.textContent = String(prodCount);
    }

    const gridRadio = item.querySelector(`input[type="radio"][value="grid"][name$="[layout]"]`);
    const slideRadio = item.querySelector(`input[type="radio"][value="slide"][name$="[layout]"]`);
    const gridLabel = gridRadio ? item.querySelector(`label[for="${gridRadio.id}"]`) : null;
    if (gridRadio) {
      gridRadio.disabled = isOdd;
      if (isOdd && gridRadio.checked && slideRadio) {
        gridRadio.checked = false;
        slideRadio.checked = true;
      }
    }
    if (gridLabel) {
      gridLabel.classList.toggle("opacity-50", isOdd);
      gridLabel.classList.toggle("pointer-events-none", isOdd);
    }
  }

  function syncProductGroupEditorSubItems(item, productsArray, blockIndex) {
    if (!item || item.getAttribute("data-type") !== "product_group" || !Array.isArray(productsArray)) return;
    const subContainer = item.querySelector(".sub-products-container") || item.querySelector(".sub-product-item")?.parentElement;
    if (!subContainer) return;

    let subItems = Array.from(subContainer.querySelectorAll(".sub-product-item"));
    const targetLen = Math.max(2, Math.min(8, productsArray.length));

    const tplGroup = document.getElementById("tpl_block_product_group");
    let templateSubNode = null;
    if (tplGroup) {
      const tmp = document.createElement("div");
      tmp.innerHTML = tplGroup.innerHTML.replace(/\{\{INDEX\}\}/g, String(blockIndex));
      templateSubNode = tmp.querySelector(".sub-product-item");
    }

    while (subItems.length < targetLen) {
      const clone = templateSubNode ? templateSubNode.cloneNode(true) : (subItems[0] ? subItems[0].cloneNode(true) : null);
      if (!clone) break;
      const addBtn = subContainer.querySelector(".add-sub-product-btn, button[name*='[add_sub_product]']");
      if (addBtn) {
        subContainer.insertBefore(clone, addBtn);
      } else {
        subContainer.appendChild(clone);
      }
      subItems.push(clone);
    }

    while (subItems.length > targetLen && subItems.length > 2) {
      const removed = subItems.pop();
      if (removed) removed.remove();
    }

    reindexSubProductsInBlock(item, blockIndex);
  }

  function reindexEditorDOM(container) {
    if (!container) return;
    const items = Array.from(container.querySelectorAll(".sortable-item"));
    const idxSuffixRegex = /-(?:\{\{INDEX\}\}|\d+(?:_\d+)?)$/;

    items.forEach((item, index) => {
      item.id = `content-item-${index}`;
      item.setAttribute("data-index", String(index));
      const elements = item.querySelectorAll("*");
      elements.forEach((el) => {
        const name = el.getAttribute("name");
        if (name && name.startsWith("content[")) {
          el.setAttribute("name", name.replace(/^content\[[^\]]+\]/, `content[${index}]`));
        }
        if (name && name.startsWith("content_img_")) {
          el.setAttribute("name", name.replace(/^content_img_(\d+|[a-zA-Z0-9_{}]+)(_\d+)?$/, (match, p1, p2) => `content_img_${index}${p2 || ""}`));
        }
        const id = el.getAttribute("id");
        if (id) {
          if (/^offer-switch-(?:\{\{INDEX\}\}|\d+)-(\d+)$/.test(id)) {
            el.setAttribute("id", id.replace(/^offer-switch-(?:\{\{INDEX\}\}|\d+)-(\d+)$/, `offer-switch-${index}-$1`));
          } else if (id.startsWith("content_img_banner_")) {
            el.setAttribute("id", id.replace(/^content_img_banner_[^_\s]+/, `content_img_banner_${index}`));
          } else if (id.startsWith("banner-crop-btn-wrap-")) {
            el.setAttribute("id", id.replace(/^banner-crop-btn-wrap-[^_\s]+/, `banner-crop-btn-wrap-${index}`));
          } else if (id.startsWith("banner-size-hint-")) {
            el.setAttribute("id", id.replace(/^banner-size-hint-[^_\s]+/, `banner-size-hint-${index}`));
          } else if (idxSuffixRegex.test(id) && !id.startsWith("content-item-")) {
            el.setAttribute("id", id.replace(idxSuffixRegex, `-${index}`));
          }
        }
        const forAttr = el.getAttribute("for");
        if (forAttr) {
          if (/^offer-switch-(?:\{\{INDEX\}\}|\d+)-(\d+)$/.test(forAttr)) {
            el.setAttribute("for", forAttr.replace(/^offer-switch-(?:\{\{INDEX\}\}|\d+)-(\d+)$/, `offer-switch-${index}-$1`));
          } else if (idxSuffixRegex.test(forAttr)) {
            el.setAttribute("for", forAttr.replace(idxSuffixRegex, `-${index}`));
          }
        }
        if (el.hasAttribute("data-index")) {
          el.setAttribute("data-index", String(index));
        }
        const dataTarget = el.getAttribute("data-target");
        if (dataTarget && idxSuffixRegex.test(dataTarget)) {
          el.setAttribute("data-target", dataTarget.replace(idxSuffixRegex, `-${index}`));
        }
        const dataValTarget = el.getAttribute("data-val-target");
        if (dataValTarget && idxSuffixRegex.test(dataValTarget)) {
          el.setAttribute("data-val-target", dataValTarget.replace(idxSuffixRegex, `-${index}`));
        }
        const dataTriggerColor = el.getAttribute("data-trigger-color");
        if (dataTriggerColor && idxSuffixRegex.test(dataTriggerColor)) {
          el.setAttribute("data-trigger-color", dataTriggerColor.replace(idxSuffixRegex, `-${index}`));
        }
      });

      if (item.getAttribute("data-type") === "product_group") {
        reindexSubProductsInBlock(item, index);
      }

      // Sincronizar data-content-index en el preview según data-block-id
      const blkId = item.getAttribute("data-block-id");
      if (blkId) {
        const previewEls = document.querySelectorAll(
          `#preview-widget-container [data-block-id="${blkId}"], .preview-widget-container [data-block-id="${blkId}"]`
        );
        previewEls.forEach((pEl) => {
          pEl.setAttribute("data-content-index", String(index));
        });
      }
    });

    // Alinear el orden del DOM en el preview con el orden del editor
    const previewContainers = document.querySelectorAll(".preview-widget-container, #preview-widget-container");
    previewContainers.forEach((pContainer) => {
      const targetNodes = [];
      items.forEach((editorItem) => {
        const blkId = editorItem.getAttribute("data-block-id");
        if (!blkId) return;
        const pNodes = Array.from(pContainer.querySelectorAll(`[data-block-id="${blkId}"]`));
        pNodes.forEach((node) => targetNodes.push(node));
      });
      targetNodes.forEach((node, i) => {
        if (pContainer.children[i] !== node) {
          pContainer.insertBefore(node, pContainer.children[i] || null);
        }
      });
    });
  }

  function reindexRrssEditorDOM(rrssList) {
    if (!rrssList) return;
    const items = rrssList.querySelectorAll(".sortable-item");
    const idxSuffixRegex = /-(?:\{\{INDEX\}\}|\d+(?:_\d+)?)$/;
    items.forEach((item, index) => {
      item.id = `rrss-item-${index}`;
      item.setAttribute("data-index", String(index));
      item.querySelectorAll("*").forEach((el) => {
        const n = el.getAttribute("name");
        if (n && n.startsWith("rrss[")) {
          el.setAttribute("name", n.replace(/^rrss\[[^\]]+\]/, `rrss[${index}]`));
        }
        const id = el.getAttribute("id");
        if (id && idxSuffixRegex.test(id) && !id.startsWith("rrss-item-")) {
          el.setAttribute("id", id.replace(idxSuffixRegex, `-${index}`));
        }
        const forAttr = el.getAttribute("for");
        if (forAttr && idxSuffixRegex.test(forAttr)) {
          el.setAttribute("for", forAttr.replace(idxSuffixRegex, `-${index}`));
        }
      });
    });
  }

  /**
   * Construye un ítem de formulario RRSS desde la plantilla #tpl_rrss_item clonando su icono SVG desde el menú selector.
   * @param {string} socialName Nombre normalizado de la red social.
   * @param {number} index Índice inicial en la lista.
   * @param {string} [url=""] URL configurada.
   * @returns {HTMLElement|null}
   */
  function buildRrssEditorItemFromTemplate(socialName, index, url = "") {
    const tpl = document.getElementById("tpl_rrss_item");
    if (!tpl) return null;
    const cleanName = String(socialName || "").trim().toLowerCase();
    const label = cleanName ? cleanName.charAt(0).toUpperCase() + cleanName.slice(1) : "";
    const tempDiv = document.createElement("div");
    tempDiv.innerHTML = tpl.innerHTML
      .replace(/\{\{INDEX\}\}/g, String(index))
      .replace(/\{\{RRSS_NAME\}\}/g, cleanName)
      .replace(/\{\{RRSS_LABEL\}\}/g, label);
    const node = tempDiv.firstElementChild;
    if (!node) return null;

    const pickerSvg = document.querySelector(`button[data-action="add-rrss-template"][data-rrss="${cleanName}"] svg`);
    const iconSlot = node.querySelector(".rrss-icon-slot");
    if (pickerSvg && iconSlot) {
      const clonedSvg = pickerSvg.cloneNode(true);
      clonedSvg.classList.remove("x18");
      clonedSvg.classList.add("x20");
      iconSlot.appendChild(clonedSvg);
    }

    const urlInput = node.querySelector(`input[name^="rrss["][name$="[1]"]`);
    if (urlInput && url !== undefined) {
      urlInput.value = url;
    }
    return node;
  }

  /**
   * Construye un enlace RRSS para la vista previa desde #tpl_preview_rrss clonando el SVG del selector.
   * @param {string} socialName Nombre normalizado de la red social.
   * @param {string} [url=""] URL configurada.
   * @returns {HTMLElement|null}
   */
  function buildRrssPreviewLinkFromTemplate(socialName, url = "") {
    const tpl = document.getElementById("tpl_preview_rrss");
    if (!tpl) return null;
    const cleanName = String(socialName || "").trim().toLowerCase();
    const tempDiv = document.createElement("div");
    tempDiv.innerHTML = tpl.innerHTML.replace(/\{\{RRSS_NAME\}\}/g, cleanName);
    const link = tempDiv.firstElementChild;
    if (!link) return null;

    const pickerSvg = document.querySelector(`button[data-action="add-rrss-template"][data-rrss="${cleanName}"] svg`);
    if (pickerSvg) {
      const clonedSvg = pickerSvg.cloneNode(true);
      clonedSvg.classList.remove("x18");
      link.appendChild(clonedSvg);
    }
    const trimmedUrl = String(url || "").trim();
    link.href = trimmedUrl || "#";
    link.style.display = trimmedUrl !== "" ? "" : "none";
    return link;
  }

  /**
   * Sincroniza bidireccionalmente todos los controles compartidos con el mismo atributo name
   * (por ejemplo, inputs globales duplicados entre colorPanel y paneles individuales).
   *
   * @param {string} name Nombre del campo.
   * @param {*} value Nuevo valor a propagar.
   * @param {HTMLElement|null} [excludeEl=null] Elemento origen a excluir.
   */
  function syncSiblingInputsByName(name, value, excludeEl = null) {
    if (!name) return;
    document.querySelectorAll(`input[name="${CSS.escape(name)}"], textarea[name="${CSS.escape(name)}"], select[name="${CSS.escape(name)}"]`).forEach((inp) => {
      if (inp === excludeEl || inp.type === "file") return;
      if (inp.value !== String(value)) {
        inp.value = value;
      }
      if (inp.type === "color" || inp.classList.contains("color-picker")) {
        const labelText = inp.closest("label")?.querySelector("p, span");
        if (labelText && labelText.textContent !== String(value)) {
          labelText.textContent = value;
        }
      }
    });
  }

  /**
   * Sincroniza adiciones, eliminaciones y orden de bloques/RRSS dentro de una instancia .user-profile-preview.
   *
   * @param {HTMLElement} previewEl Contenedor .user-profile-preview a sincronizar.
   * @param {Object|null} draft Estado del borrador con content y rrss.
   */
  function syncPreviewStructureFromDraft(previewEl, draft) {
    if (!previewEl || !draft) return;

    if (Array.isArray(draft.content)) {
      const pContainer = previewEl.querySelector(".preview-widget-container, #preview-widget-container");
      if (pContainer) {
        const draftBlockIds = new Set(draft.content.map((b) => b && b.id).filter(Boolean));
        Array.from(pContainer.querySelectorAll("[data-block-id]")).forEach((item) => {
          const bId = item.getAttribute("data-block-id");
          if (bId && !draftBlockIds.has(bId)) {
            item.remove();
          }
        });

        draft.content.forEach((block) => {
          if (!block || !block.id) return;
          const existingPrevEl = pContainer.querySelector(`[data-block-id="${block.id}"]`);
          if (!existingPrevEl) {
            const blockType = block.type || "link";
            const tplPreview = document.getElementById("tpl_preview_" + blockType);
            if (tplPreview) {
              const htmlPreview = tplPreview.innerHTML
                .replace(/\{\{INDEX\}\}/g, "0")
                .replace(/\{\{BLOCK_ID\}\}/g, block.id);
              const tempDiv = document.createElement("div");
              tempDiv.innerHTML = htmlPreview;
              Array.from(tempDiv.children).forEach((node) => {
                if (node.hasAttribute && (node.hasAttribute("data-content-index") || node.hasAttribute("data-block-id") || (node.className && node.className.includes("block-wrapper")))) {
                  node.setAttribute("data-block-id", block.id);
                  const isActive = (block.active === true || block.active === "true" || block.active === 1 || block.active === "1");
                  node.style.display = isActive ? "" : "none";
                }
                pContainer.appendChild(node);
              });
            }
          }
        });

        const targetNodes = [];
        draft.content.forEach((block, newIdx) => {
          if (!block || !block.id) return;
          Array.from(pContainer.querySelectorAll(`[data-block-id="${block.id}"]`)).forEach((el) => {
            el.setAttribute("data-content-index", String(newIdx));
            targetNodes.push(el);
          });
        });

        targetNodes.forEach((node, i) => {
          if (pContainer.children[i] !== node) {
            pContainer.insertBefore(node, pContainer.children[i] || null);
          }
        });
      }
    }

    if (Array.isArray(draft.rrss)) {
      const draftRrssNames = new Set(
        draft.rrss.map((r) => (Array.isArray(r) ? r[0] : r?.name || "").trim().toLowerCase()).filter(Boolean)
      );
      Array.from(previewEl.querySelectorAll('[data-link-id^="rrss_"]')).forEach((link) => {
        const linkName = (link.getAttribute("data-link-id") || "").replace(/^rrss_/, "").toLowerCase();
        if (linkName && !draftRrssNames.has(linkName)) {
          link.remove();
        }
      });

      const wrapper = previewEl.querySelector(".preview-rrss-container");
      if (wrapper) {
        wrapper.style.display = draft.rrss.length > 0 ? "" : "none";
      }

      const remainingLinks = Array.from(previewEl.querySelectorAll('[data-link-id^="rrss_"]'));
      const wrappers = new Set([
        ...(wrapper ? [wrapper] : []),
        ...remainingLinks.map((l) => l.parentElement).filter(Boolean)
      ]);

      wrappers.forEach((rrssWrapper) => {
        const previewLinks = Array.from(rrssWrapper.querySelectorAll('[data-link-id^="rrss_"]'));
        const targetNodes = [];
        draft.rrss.forEach((r) => {
          const name = (Array.isArray(r) ? r[0] : r?.name || "").trim().toLowerCase();
          const url = (Array.isArray(r) ? r[1] : r?.url || "");
          if (!name) return;
          let match = previewLinks.find((link) => (link.getAttribute("data-link-id") || "").toLowerCase() === `rrss_${name}`);
          if (!match) {
            match = buildRrssPreviewLinkFromTemplate(name, url);
            if (match) rrssWrapper.appendChild(match);
          }
          if (match) targetNodes.push(match);
        });

        targetNodes.forEach((node, i) => {
          if (rrssWrapper.children[i] !== node) {
            rrssWrapper.insertBefore(node, rrssWrapper.children[i] || null);
          }
        });
      });
    }
  }

  function restoreOrderFromDraft() {
    const draft = getDraftState();
    if (!draft) return;

    // 1. Restaurar orden, adiciones y eliminaciones de Bloques de Contenido en el editor
    if (draft && Array.isArray(draft.content)) {
      const editorList = document.getElementById("sortable-content-list");

      if (editorList) {
        // A) Eliminar del editor cualquier bloque borrado en el borrador
        const existingEditorItems = Array.from(editorList.querySelectorAll(".sortable-item"));
        const draftBlockIds = new Set(draft.content.map((b) => b && b.id).filter(Boolean));
        existingEditorItems.forEach((item) => {
          const bId = item.getAttribute("data-block-id");
          if (bId && !draftBlockIds.has(bId)) {
            item.remove();
          }
        });

        // B) Reconstruir bloques presentes en el borrador que no existan en el HTML del servidor
        draft.content.forEach((block, bIdx) => {
          if (!block || !block.id) return;
          let existingItem = editorList.querySelector(`.sortable-item[data-block-id="${block.id}"]`);
          if (!existingItem) {
            const blockType = block.type || "link";
            const tplBlock = document.getElementById("tpl_block_" + blockType);
            if (tplBlock) {
              const tempDiv = document.createElement("div");
              let html = tplBlock.innerHTML
                .replace(/\{\{INDEX\}\}/g, String(bIdx))
                .replace(/\{\{BLOCK_ID\}\}/g, block.id);
              tempDiv.innerHTML = html;
              const newNode = tempDiv.firstElementChild;
              if (newNode) {
                newNode.setAttribute("data-block-id", block.id);
                newNode.classList.remove("is-collapsed");
                newNode.classList.add("is-open");
                const body = newNode.querySelector(".content-item-body");
                if (body) {
                  body.style.display = "flex";
                }
                editorList.appendChild(newNode);
                existingItem = newNode;
              }
            }
          }
          if (existingItem && block.type === "product_group" && Array.isArray(block.products)) {
            syncProductGroupEditorSubItems(existingItem, block.products, bIdx);
          }
        });

        // C) Reordenar en el editor para que coincidan con draft.content
        draft.content.forEach((block) => {
          if (!block || !block.id) return;
          const editorEl = editorList.querySelector(`.sortable-item[data-block-id="${block.id}"]`);
          if (editorEl) {
            editorList.appendChild(editorEl);
          }
        });

        // D) Re-indexar el editor
        reindexEditorDOM(editorList);
      }
    }

    // 2. Restaurar orden, adiciones y eliminaciones de Redes Sociales en el editor
    if (draft && Array.isArray(draft.rrss)) {
      const rrssList = document.getElementById("sortable-rrss-list");
      const draftRrssNames = new Set(
        draft.rrss.map((r) => (Array.isArray(r) ? r[0] : r?.name || "").trim().toLowerCase()).filter(Boolean)
      );

      if (rrssList) {
        // A) Eliminar redes sociales borradas en el borrador
        const existingRrssItems = Array.from(rrssList.querySelectorAll(".sortable-item"));
        existingRrssItems.forEach((item) => {
          const itemName = (item.getAttribute("data-rrss-name") 
            || item.querySelector('input[name$="[0]"]')?.value 
            || item.querySelector('input[type="hidden"][name*="[0]"]')?.value 
            || "").trim().toLowerCase();
          if (itemName && !draftRrssNames.has(itemName)) {
            item.remove();
          }
        });

        // B) Reconstruir faltantes y reordenar según el borrador
        draft.rrss.forEach((r, idx) => {
          const name = (Array.isArray(r) ? r[0] : r?.name || "").trim().toLowerCase();
          const url = (Array.isArray(r) ? r[1] : r?.url || "");
          if (!name) return;
          const items = Array.from(rrssList.querySelectorAll(".sortable-item"));
          let rrssEl = items.find((item) => {
            const itemName = (item.getAttribute("data-rrss-name") 
              || item.querySelector('input[name$="[0]"]')?.value 
              || item.querySelector('input[type="hidden"][name*="[0]"]')?.value 
              || "").trim().toLowerCase();
            return itemName === name;
          });
          if (!rrssEl) {
            rrssEl = buildRrssEditorItemFromTemplate(name, idx, url);
          }
          if (rrssEl) {
            rrssList.appendChild(rrssEl);
          }
        });

        // C) Re-indexar rrss inputs
        reindexRrssEditorDOM(rrssList);
      }
    }

    // 3. Sincronizar bloques y redes en todas las vistas previas activas en el DOM
    document.querySelectorAll(".user-profile-preview").forEach((previewEl) => {
      syncPreviewStructureFromDraft(previewEl, draft);
    });
  }

  window.updateJsonDraft = function(modifierFunction) {
    try {
      let draft = getDraftState();
      if (draft) {
        draft = modifierFunction(draft) || draft;
        saveDraftState(draft);
      }
    } catch(e) {
      console.error("Error actualizando JSON draft", e);
    }
  };

  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.getAttribute('content')) {
      return meta.getAttribute('content');
    }
    const input = document.querySelector('input[name="_token"], input[name="csrf_token"]');
    return input ? input.value : '';
  }

  // =========================================================================
  // 1. GESTIÓN DEL ALMACENAMIENTO LOCAL (Two-JSON Architecture)
  // =========================================================================

  /**
   * Obtiene los cambios acumulados en el borrador local como diccionario plano.
   * @returns {Object} Diccionario con los campos modificados.
   */
  function getDraft() {
    return flattenObject(getDraftState());
  }

  /**
   * Persiste el borrador en localStorage de manera segura y notifica a la aplicación.
   * @param {Object} draft
   */
  function saveDraftToStorage(draft) {
    if (draft && typeof draft === 'object') {
      if (draft.content !== undefined || draft.profile !== undefined || draft.backCard !== undefined) {
        saveDraftState(draft);
      } else {
        Object.keys(draft).forEach(path => {
          updateJsonDraftFromPath(path, draft[path]);
        });
      }
    }
  }

  /**
   * Guarda un campo en el borrador estructurado de trabajo navegando su path.
   * Si se trata de un campo de contenido (content[idx]...), resuelve primero por blockId
   * para evitar colisiones de índice al reordenar o añadir bloques.
   *
   * @param {string} path Nombre o ruta del campo (ej: 'title' o 'content[0][title]')
   * @param {*} value Valor del campo
   * @param {string|null} [blockId=null] ID único opcional del bloque de contenido
   */
  function updateJsonDraftFromPath(path, value, blockId = null) {
    const draft = getDraftState();
    if (!draft) return;
    const parts = path.split(/\[|\]\[|\]/).filter(Boolean);

    if (parts[0] === "content" && parts.length >= 3 && Array.isArray(draft.content)) {
      const idx = parseInt(parts[1], 10);
      const effectiveBlockId = blockId || document.querySelector(`#content-item-${idx}`)?.getAttribute("data-block-id") || null;
      let targetIdx = -1;
      if (effectiveBlockId) {
        targetIdx = draft.content.findIndex((item) => item && item.id === effectiveBlockId);
      }
      if (targetIdx === -1 && !isNaN(idx)) {
        targetIdx = idx;
      }
      if (targetIdx >= 0) {
        parts[1] = String(targetIdx);
        if (!draft.content[targetIdx]) {
          draft.content[targetIdx] = effectiveBlockId ? { id: effectiveBlockId } : {};
        } else if (effectiveBlockId && !draft.content[targetIdx].id) {
          draft.content[targetIdx].id = effectiveBlockId;
        }
      }
    }

    let current = draft;
    for (let i = 0; i < parts.length - 1; i++) {
      let part = parts[i];
      if (current[part] === undefined) {
        current[part] = (isNaN(parseInt(parts[i + 1], 10))) ? {} : [];
      }
      current = current[part];
    }
    if (parts.length > 0) {
      current[parts[parts.length - 1]] = value;
    }
    if (path === "back_perfil") {
      if (!draft.backCard) draft.backCard = {};
      draft.backCard.back_perfil = value;
    }
    if (path === "style_back") {
      if (!draft.backCard) draft.backCard = {};
      draft.backCard.style_back = value;
    }
    if (
      path === "back_image_opacity" ||
      path === "back_image_overlay" ||
      path === "back_image_filter" ||
      path === "back_image_filter_intensity" ||
      path === "back_video_opacity" ||
      path === "back_video_overlay"
    ) {
      if (!draft.backCard) draft.backCard = {};
      draft.backCard[path] = value;
    }
    if (path === "hide") {
      draft.hide = (value === true || value === "true" || value === 1 || value === "1");
    }
    if (path === "hover") {
      draft.hover = (value === true || value === "true" || value === 1 || value === "1");
    }
    if (path === "title_color") draft.titleColor = value;
    if (path === "color_text") draft.colorText = value;
    if (path === "borders") {
      draft.borders = Array.isArray(value) ? value : (typeof value === "string" ? value.split(",") : ["br0", "br0"]);
    }
    if (path === "void_space" || path === "voidHero[space]") {
      if (!draft.voidHero) draft.voidHero = {};
      draft.voidHero.space = parseInt(value, 10) || 70;
      draft.void_space = draft.voidHero.space;
    }

    saveDraftState(draft);
  }

  /**
   * Registra la modificación de un campo y notifica a la interfaz.
   * @param {string} name
   * @param {*} value
   * @param {string|null} [blockId=null]
   */
  function setDraftField(name, value, blockId = null) {
    if (!name) return;
    updateJsonDraftFromPath(name, value, blockId);
    notifyDraftState();
  }

  /**
   * Limpia el borrador local igualándolo al estado oficial y resetea inputs de archivo pendientes.
   */
  function clearDraft() {
    clearAllPendingBlobs();
    try {
      document.querySelectorAll("input[type='file']").forEach((inp) => {
        inp.value = "";
        delete inp.dataset.isCropped;
      });
    } catch (_) {}
    const initial = getInitialState();
    if (initial) {
      setDraftStateImmediate(initial);
    }
    notifyDraftState();
  }

  /**
   * Notifica a la aplicación (especialmente al saveButtonController) del estado actual.
   */
  function notifyDraftState() {
    const pending = hasDraft();
    if (window.__saveButtonController) {
      if (pending) {
        window.__saveButtonController.enableSaveButton?.();
      } else {
        window.__saveButtonController.disableSaveButton?.();
      }
    }
    const saveContainer = document.getElementById("save-btn-container");
    if (saveContainer) {
      saveContainer.dataset.hasCustom = pending ? "true" : "false";
    }
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

      case "video": {
        const urlInput = block.querySelector(`input[name="content[${idx}][url]"]`);
        return !!(urlInput && urlInput.value.trim() !== "");
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
    const blkId = block.getAttribute("data-block-id") || null;
    const activeSwitch = block.querySelector(`input[type="checkbox"][name="content[${idx}][active]"]`) || block.querySelector(`.checkbox-switch[name="content[${idx}][active]"]`);
    if (!activeSwitch) return;

    const isValid = isBlockValid(block, idx, type, extraData);

    const container = activeSwitch.closest(".checkbox-switch-container") || activeSwitch;
    const hiddenInput = container.previousElementSibling && container.previousElementSibling.name === activeSwitch.name 
      ? container.previousElementSibling 
      : null;

    if (isValid) {
      const wasDisabled = activeSwitch.disabled || activeSwitch.hasAttribute("disabled") || block.dataset.wasInvalid === "true";

      // Habilitar el switch para que el usuario pueda activarlo u ocultarlo a voluntad
      activeSwitch.removeAttribute("disabled");
      activeSwitch.disabled = false;
      delete block.dataset.wasInvalid;

      // Si el bloque recién se completó (pasó de inválido/deshabilitado a válido) y el usuario no lo apagó manualmente,
      // encender automáticamente el switch para que el bloque aparezca de inmediato en la vista previa
      if (wasDisabled && block.dataset.userTurnedOff !== "true") {
        activeSwitch.checked = true;
        activeSwitch.setAttribute("active", "1");
        setDraftField(activeSwitch.name, true, blkId);
      }

      if (hiddenInput && hiddenInput.tagName === "INPUT" && hiddenInput.type === "hidden") {
        hiddenInput.disabled = activeSwitch.checked;
      }

      const draftState = getDraftState() || {};
      const blockData = Array.isArray(draftState.content)
        ? (blkId ? draftState.content.find((b) => b && b.id === blkId) : draftState.content[idx]) || { id: blkId, type, active: activeSwitch.checked }
        : { id: blkId, type, active: activeSwitch.checked };

      if (extraData.previewUrl) {
        blockData.imgSrc = extraData.previewUrl;
        blockData.imgShow = true;
      }
      blockData.active = activeSwitch.checked;

      document.querySelectorAll(".user-profile-preview").forEach((preview) => {
        if (window.LivePreviewManager) {
          window.LivePreviewManager.renderBlockInPreview(preview, blockData, idx, draftState);
        }
      });
    } else {
      block.dataset.wasInvalid = "true";
      activeSwitch.setAttribute("disabled", "disabled");
      activeSwitch.disabled = true;
      activeSwitch.checked = false;
      activeSwitch.setAttribute("active", "2");
      setDraftField(activeSwitch.name, "false", blkId);

      if (hiddenInput && hiddenInput.tagName === "INPUT" && hiddenInput.type === "hidden") {
        hiddenInput.disabled = false;
      }

      document.querySelectorAll(".user-profile-preview").forEach((preview) => {
        let items = blkId ? preview.querySelectorAll(`[data-block-id="${blkId}"]`) : [];
        if (!items || !items.length) {
          if (!blkId) {
            items = preview.querySelectorAll(`[data-content-index="${idx}"]`);
          }
        }
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
   * Alterna la visibilidad de un elemento de interfaz asegurando que venza
   * reglas CSS responsivas con !important (como .flex-column-sml en teléfonos).
   *
   * @param {HTMLElement|null} element Elemento a mostrar u ocultar.
   * @param {boolean} visible True para mostrar, false para ocultar.
   * @param {string} [displayType="flex"] Tipo de display al mostrar.
   */
  function setElementVisibility(element, visible, displayType = "flex") {
    if (!element) return;
    if (visible) {
      element.classList.remove("hidden");
      element.style.setProperty("display", displayType, "important");
    } else {
      element.classList.add("hidden");
      element.style.setProperty("display", "none", "important");
    }
  }

  /**
   * Sincroniza la visibilidad de elementos dependientes en el formulario de edición.
   * @param {HTMLElement} target Elemento modificado
   */
  function syncConditionalUI(target) {
    if (!target) return;

    // 1. Estilo de fondo (Sólido, Degradado, Video, Imagen)
    if (target.name === "style_back" || target.name === "background_mode") {
      const gradientWrapper = document.getElementById("gradient-direction-wrapper");
      const videoWrapper = document.getElementById("video-controls-wrapper");
      const imageWrapper = document.getElementById("image-controls-wrapper");
      const isGradient = target.value === "gradient" || target.value === "gradientUp" || target.value === "gradientDown";
      const isVideo = target.value === "video";
      const isImage = target.value === "image";

      setElementVisibility(gradientWrapper, isGradient);
      setElementVisibility(videoWrapper, isVideo);
      setElementVisibility(imageWrapper, isImage);
    }

    // 2. Selector de color de sombra 3
    if (target.name === "shadow") {
      const shadow3Row = document.getElementById("shadow3-color-row");
      const colorShadow3Row = document.getElementById("color-shadow3-color-row");
      const isShadow3 = target.value === "shadow-3";
      setElementVisibility(shadow3Row, isShadow3);
      setElementVisibility(colorShadow3Row, isShadow3);
    }

    // 3. Separación superior en voidHero
    if (target.name === "header") {
      const voidSpaceContainer = document.getElementById("void-space-container");
      setElementVisibility(voidSpaceContainer, target.value === "voidHero");
    }

    // 4. Bloques de campaña: posición de imagen
    if (target.classList && target.classList.contains("campaign-pos-radio")) {
      const idx = target.dataset.index;
      const opacityOpt = document.getElementById(`campaign-opacity-option-${idx}`);
      setElementVisibility(opacityOpt, target.value === "background");
      const sizeWrap = document.getElementById(`campaign-size-wrap-${idx}`);
      setElementVisibility(sizeWrap, target.value === "background");
      if (target.value === "header") {
        const horizRadio = document.getElementById(`campaign-size-horiz-${idx}`);
        if (horizRadio) {
          horizRadio.checked = true;
          setDraftField(horizRadio.name, "horizontal");
        }
        const textPosWrap = document.getElementById(`campaign-text-pos-wrap-${idx}`);
        setElementVisibility(textPosWrap, false);
      } else {
        const checkedSize = document.querySelector(`input[name="content[${idx}][size]"]:checked`);
        const textPosWrap = document.getElementById(`campaign-text-pos-wrap-${idx}`);
        setElementVisibility(textPosWrap, checkedSize && checkedSize.value !== "horizontal");
      }
    }

    // 5. Bloques de campaña: tamaño de bloque / alineación de texto
    if (target.classList && target.classList.contains("campaign-size-radio")) {
      const idx = target.dataset.index;
      const textPosWrap = document.getElementById(`campaign-text-pos-wrap-${idx}`);
      setElementVisibility(textPosWrap, target.value !== "horizontal");
    }

    // 6. Bloques de campaña: switch de contador regresivo
    if (target.classList && target.classList.contains("campaign-countdown-switch")) {
      const targetId = target.dataset.target;
      if (targetId) {
        const dateWrap = document.getElementById(targetId);
        setElementVisibility(dateWrap, target.checked);
      }
    }

    // 7. Bloques de campaña: switch de campos opcionales
    if (target.classList && target.classList.contains("campaign-toggle-field-switch")) {
      const targetId = target.dataset.target;
      if (targetId) {
        const fieldWrap = document.getElementById(targetId);
        setElementVisibility(fieldWrap, target.checked);
      }
    }

    // 8. Bloques de separador: cambio entre figura (ícono) y espacio en blanco
    if (target.classList && target.classList.contains("separator-icon-radio")) {
      const block = target.closest(".sortable-item.content-block, .content-item-body");
      if (block) {
        const isSpace = (target.value === "none" || target.value === "ban");
        const spaceOpts = block.querySelector(".separator-space-options");
        const sizeOpts = block.querySelector(".separator-size-options");
        setElementVisibility(spaceOpts, isSpace, "flex");
        setElementVisibility(sizeOpts, !isSpace, "flex");

        const modeInput = block.querySelector('input[name*="[separator_mode]"]');
        if (modeInput) {
          modeInput.value = isSpace ? "space" : "line";
        }
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
          const blk = target.closest(".sortable-item") || target.closest(".content-block");
          const bId = blk?.getAttribute("data-block-id");
          document.querySelectorAll(".user-profile-preview").forEach((preview) => {
            const bannerEl = (bId ? preview.querySelector(`[data-block-id="${bId}"]`) : null) || preview.querySelector(`[data-content-index="${idx}"]`);
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
        setElementVisibility(discountWrap, target.checked);
      }
    }

    // 11. Filtro de imagen de fondo: mostrar/ocultar control deslizante de intensidad y actualizar texto del filtro activo
    if (target.name === "back_image_filter") {
      const activeFilterName = document.getElementById("active-filter-name");
      if (activeFilterName) {
        activeFilterName.textContent = target.dataset.filterName || target.value;
      }
      const intensityRow = document.getElementById("image-filter-intensity-row");
      const isNone = target.value === "none" || target.value === "0";
      setElementVisibility(intensityRow, !isNone);
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
    const activeStyleInput = document.getElementById("input_style_back") || document.querySelector('input[name="style_back"]');
    const activeStyleRadio = document.querySelector('input[name="style_back"]:checked');
    let styleBack = fields.style_back;
    if (!styleBack) {
      const bgModeRadio = document.querySelector('input[name="background_mode"]:checked');
      if (bgModeRadio) {
        if (bgModeRadio.value === "gradient") {
          const dirRadio = document.querySelector('input[name="gradient_direction"]:checked');
          styleBack = dirRadio ? dirRadio.value : "gradientDown";
        } else {
          styleBack = bgModeRadio.value;
        }
      } else if (activeStyleInput && activeStyleInput.value) {
        styleBack = activeStyleInput.value;
      } else if (activeStyleRadio) {
        styleBack = activeStyleRadio.value;
      } else {
        styleBack = "solid";
      }
    }

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

    const cleanHex = String(backPerfil).replace("#", "").trim();
    let isDarkBg = false;
    if (cleanHex.length === 6) {
      const r = parseInt(cleanHex.substring(0, 2), 16);
      const g = parseInt(cleanHex.substring(2, 4), 16);
      const b = parseInt(cleanHex.substring(4, 6), 16);
      if (!isNaN(r) && !isNaN(g) && !isNaN(b)) {
        isDarkBg = (0.299 * r + 0.587 * g + 0.114 * b) < 140;
      }
    }
    const dotColor = isDarkBg
      ? `oklch(from ${backPerfil} calc(l * 0.55) c h / 0.85)`
      : `oklch(from ${backPerfil} calc(l * 0.68) calc(c * 1.1) h / 0.85)`;
    cssRules.push(`
      .back-card-container {
        --dot-pattern-color: ${dotColor} !important;
      }
    `);

    // Filtro y porcentaje de intensidad para imagen de fondo
    const checkedFilterRadio = document.querySelector('input[name="back_image_filter"]:checked');
    let backImageFilter = fields.back_image_filter || (checkedFilterRadio ? checkedFilterRadio.value : "none");
    const numToFilterMap = {
      "0": "none",
      "1": "vignette",
      "2": "blur",
      "3": "brightness",
      "4": "contrast",
      "5": "grayscale",
      "6": "hue-rotate",
      "7": "invert",
      "8": "saturate",
      "9": "sepia"
    };
    if (numToFilterMap[backImageFilter]) {
      backImageFilter = numToFilterMap[backImageFilter];
    }

    const intensitySlider = document.getElementById("select-filter-intensity");
    const intensityHidden = document.getElementById("input-filter-intensity-val");
    const backImageFilterIntensity = fields.back_image_filter_intensity !== undefined
      ? fields.back_image_filter_intensity
      : (intensityHidden ? intensityHidden.value : (intensitySlider ? intensitySlider.value : 80));

    const parsedIntensity = parseInt(backImageFilterIntensity, 10);
    const intensityNum = isNaN(parsedIntensity) ? 80 : Math.max(0, Math.min(100, parsedIntensity));
    const filterRatio = (intensityNum / 100).toFixed(2);

    // Video e Imagen de fondo: visibilidad y reproducción según style_back
    if (styleBack !== undefined) {
      previews.forEach((p) => {
        const videoBg = p.querySelector(".back-video-bg");
        const videoOverlay = p.querySelector(".back-video-overlay");
        const imageBg = p.querySelector(".back-image-bg");
        const imageOverlay = p.querySelector(".back-image-overlay");
        const imageVignette = p.querySelector(".back-image-vignette");
        
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
        
        if (imageBg) {
          const pendingBgBlob = pendingBlobUrls.get("back_image");
          if (pendingBgBlob) {
            imageBg.src = pendingBgBlob;
          } else {
            const defaultNoImage = "/App/Public/Img/Custom/no-image.webp";
            if (!imageBg.getAttribute("src") || imageBg.getAttribute("src") === "") {
              imageBg.src = defaultNoImage;
            }
          }
          imageBg.style.display = styleBack === "image" ? "" : "none";
        }
        if (imageOverlay) {
          imageOverlay.style.display = styleBack === "image" ? "" : "none";
        }
        if (imageVignette) {
          imageVignette.style.display = (styleBack === "image" && backImageFilter === "vignette") ? "" : "none";
        }
      });
    }

    // Opacidad y color del overlay de video
    const videoOverlayColor = fields.back_video_overlay || document.getElementById("select-color-overlay")?.value || "#000000";
    const overlayInput = document.getElementById("input-opacity-val");
    const videoOverlayOpacity = fields.back_video_opacity !== undefined
      ? fields.back_video_opacity
      : (overlayInput ? overlayInput.value : 45);

    const parsedVideoOpacity = parseInt(videoOverlayOpacity, 10);
    const opacity = isNaN(parsedVideoOpacity) ? 45 : Math.max(0, Math.min(95, parsedVideoOpacity));
    const opacityFraction = (opacity / 100).toFixed(2);
    cssRules.push(`
      .user-profile-preview .back-video-overlay {
        background-color: ${videoOverlayColor} !important;
        opacity: ${opacityFraction} !important;
      }
    `);

    // Opacidad y color del overlay de imagen (SIEMPRE presente e independiente de los filtros)
    const imageOverlayColor = fields.back_image_overlay || document.getElementById("select-color-image-overlay")?.value || "#000000";
    const imageOverlayInput = document.getElementById("input-opacity-image-val");
    const imageOverlayOpacity = fields.back_image_opacity !== undefined
      ? fields.back_image_opacity
      : (imageOverlayInput ? imageOverlayInput.value : 45);

    const parsedImgOpacity = parseInt(imageOverlayOpacity, 10);
    const imgOpacity = isNaN(parsedImgOpacity) ? 45 : Math.max(0, Math.min(95, parsedImgOpacity));
    const imgOpacityFraction = (imgOpacity / 100).toFixed(2);

    cssRules.push(`
      .user-profile-preview .back-image-overlay {
        background-color: ${imageOverlayColor} !important;
        opacity: ${imgOpacityFraction} !important;
      }
    `);

    // Regla para la viñeta (oscurece bordes con radial-gradient negro e intensidad controlada por el slider)
    cssRules.push(`
      .user-profile-preview .back-image-vignette {
        background: radial-gradient(ellipse at center, transparent 30%, #000000 100%) !important;
        opacity: ${filterRatio} !important;
      }
    `);

    // Reglas CSS para filtros de imagen estándar según la intensidad del slider
    let filterCss = 'none';
    let filterTransform = 'none';
    if (backImageFilter === 'blur') {
      const blurPx = ((intensityNum / 100) * 15).toFixed(1);
      filterCss = `blur(${blurPx}px)`;
      filterTransform = 'scale(1.05)';
    } else if (backImageFilter === 'brightness') {
      const brightnessVal = ((intensityNum / 100) * 2).toFixed(2);
      filterCss = `brightness(${brightnessVal})`;
    } else if (backImageFilter === 'contrast') {
      const contrastVal = ((intensityNum / 100) * 2).toFixed(2);
      filterCss = `contrast(${contrastVal})`;
    } else if (backImageFilter === 'grayscale') {
      filterCss = `grayscale(${intensityNum}%)`;
    } else if (backImageFilter === 'hue-rotate') {
      const hueDeg = Math.round((intensityNum / 100) * 360);
      filterCss = `hue-rotate(${hueDeg}deg)`;
    } else if (backImageFilter === 'invert') {
      filterCss = `invert(${intensityNum}%)`;
    } else if (backImageFilter === 'saturate') {
      const satVal = ((intensityNum / 100) * 3).toFixed(2);
      filterCss = `saturate(${satVal})`;
    } else if (backImageFilter === 'sepia') {
      filterCss = `sepia(${intensityNum}%)`;
    }

    cssRules.push(`
      .user-profile-preview .back-image-bg {
        filter: ${filterCss} !important;
        transform: ${filterTransform} !important;
      }
    `);

    // Animación hover en botones
    const buttonBack = fields.back || document.getElementById("button-select-color")?.value || document.getElementById("select-color-button")?.value || document.querySelector('input[name="back"]')?.value || "#d6d6d6";
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

    // Actualizar la hoja de estilos en vivo (solo para fondo/filtros/hover, sin !important en colores de texto)
    styleSheet.textContent = cssRules.join("\n");

    // --- A2. VARIABLES CSS DE TEMA Y CABECERA DECLARATIVA VÍA LIVEPREVIEWMANAGER ---
    const btnBackVal = fields.back || document.getElementById("button-select-color")?.value || document.getElementById("select-color-button")?.value;
    const btnColorVal = fields.color || document.getElementById("button-select-color-text")?.value || document.getElementById("select-color-text")?.value;
    const colorShadow3 = fields.colorShadow3 || document.getElementById("button-select-color-shadow3")?.value || document.getElementById("select-color-shadow3")?.value || document.querySelector('input[name="colorShadow3"]')?.value || "#000000";

    const themePayload = {
      back: btnBackVal,
      color: btnColorVal,
      colorText: fields.colorText,
      titleColor: fields.titleColor,
      colorShadow3: colorShadow3,
      void_space: fields.void_space !== undefined ? fields.void_space : fields["voidHero[space]"],
      header: fields.header,
      title: fields.title,
      desc: fields.desc,
      avatarSrc: pendingBlobUrls.get("avatar")
    };

    if (window.LivePreviewManager && typeof window.LivePreviewManager.applyThemeVarsToPreview === "function") {
      previews.forEach((p) => {
        window.LivePreviewManager.applyThemeVarsToPreview(p, themePayload);
      });
    }

    // --- B. BORDES Y SOMBRAS EN BOTONES (CLASES DIRECTAS) ---
    const rawBorders = fields.borders || (fields["borders[0]"] ? `${fields["borders[0]"]},${fields["borders[1]"] || fields["borders[0]"]}` : null);
    if (rawBorders) {
      const borderParts = Array.isArray(rawBorders) ? rawBorders : String(rawBorders).split(",");
      const btnBorder = borderParts[0] || "br0";
      const imgBorder = borderParts[1] || "br0";
      const validBorders = ["br0", "br5", "br10", "br12", "br15", "br20", "br30", "br50"];
      const validImgBorders = ["br0", "br5", "br10", "br12", "br15", "br20", "br30", "br50"];

      const clampedBorder = (btnBorder === "br50") ? "br20" : btnBorder;
      const clampedImgBorder = (imgBorder === "br50") ? "br12" : imgBorder;

      document.querySelectorAll(".button-fill-style").forEach((el) => {
        validBorders.forEach((b) => el.classList.remove(b));
        el.classList.add(btnBorder);
      });

      previews.forEach((p) => {
        p.querySelectorAll(".link-item-wrapper").forEach((btn) => {
          validBorders.forEach((b) => btn.classList.remove(b));
          btn.classList.add(btnBorder);
        });
        p.querySelectorAll(".link-item-wrapper img.cover").forEach((img) => {
          validImgBorders.forEach((b) => img.classList.remove(b));
          img.classList.add(imgBorder);
        });

        p.querySelectorAll(".product-item-wrapper, .product-regular-wrapper, .product-grid-card, .product-slide-card, .banner-block-wrapper, .campaign-block-wrapper, .video-block-wrapper").forEach((btn) => {
          validBorders.forEach((b) => btn.classList.remove(b));
          btn.classList.add(clampedBorder);
        });
        p.querySelectorAll(".product-item-wrapper img.cover, .product-regular-wrapper img.cover, .product-grid-card img.cover, .product-slide-card img.cover").forEach((img) => {
          validImgBorders.forEach((b) => img.classList.remove(b));
          img.classList.add(clampedImgBorder);
        });

        p.querySelectorAll(".campaign-content .modal-btn, .campaign-button").forEach((btn) => {
          validBorders.forEach((b) => btn.classList.remove(b));
          btn.classList.add(btnBorder);
        });

        p.querySelectorAll(".theme-button:not(.link-item-wrapper):not(.product-item-wrapper):not(.product-regular-wrapper):not(.product-grid-card):not(.product-slide-card):not(.campaign-block-wrapper):not(.banner-block-wrapper):not(.video-block-wrapper)").forEach((btn) => {
          validBorders.forEach((b) => btn.classList.remove(b));
          btn.classList.add(btnBorder);
        });
        p.querySelectorAll(".theme-button:not(.link-item-wrapper):not(.product-item-wrapper):not(.product-regular-wrapper):not(.product-grid-card):not(.product-slide-card):not(.campaign-block-wrapper):not(.banner-block-wrapper):not(.video-block-wrapper) img.cover").forEach((img) => {
          validImgBorders.forEach((b) => img.classList.remove(b));
          img.classList.add(imgBorder);
        });
      });
    }

    if (fields.shadow) {
      const shadowClass = String(fields.shadow);
      const validShadows = ["shadow-0", "shadow-1", "shadow-2", "shadow-3", "shadow-card"];

      previews.forEach((p) => {
        const targets = p.querySelectorAll(
          ".theme-button, .link-item-wrapper, .product-item-wrapper, .product-regular-wrapper, .product-grid-card, .product-slide-card, .banner-block-wrapper, .campaign-block-wrapper, .video-block-wrapper, .campaign-content .modal-btn, .campaign-button"
        );
        targets.forEach((el) => {
          validShadows.forEach((s) => el.classList.remove(s));
          el.classList.add(shadowClass);
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

    // --- E. ACTUALIZACIÓN DECLARATIVA EN VIVO DE TODOS LOS BLOQUES VÍA LIVEPREVIEWMANAGER ---
    const structuredDraft = getDraftState() || {};
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

    // Unificar índices de bloques presentes en contentMap, contentProductMap o structuredDraft.content
    const allBlockIndices = new Set([
      ...Object.keys(contentMap),
      ...Object.keys(contentProductMap)
    ]);
    if (Array.isArray(structuredDraft.content)) {
      structuredDraft.content.forEach((_, i) => allBlockIndices.add(String(i)));
    }

    if (window.LivePreviewManager && typeof window.LivePreviewManager.renderBlockInPreview === "function") {
      allBlockIndices.forEach((idx) => {
        const numIdx = parseInt(idx, 10);
        const structBlock = (Array.isArray(structuredDraft.content) && structuredDraft.content[numIdx])
          ? structuredDraft.content[numIdx]
          : {};
        const flatBlock = contentMap[idx] || {};
        const mergedBlock = Object.assign({}, structBlock, flatBlock);

        // Fusionar sub-productos si existen
        const subMap = contentProductMap[idx];
        if (subMap || Array.isArray(structBlock.products)) {
          const baseProducts = Array.isArray(structBlock.products) ? structBlock.products.map((p) => Object.assign({}, p)) : [];
          if (subMap) {
            Object.keys(subMap).forEach((pIdx) => {
              const pNum = parseInt(pIdx, 10);
              baseProducts[pNum] = Object.assign({}, baseProducts[pNum] || {}, subMap[pIdx]);
            });
          }
          mergedBlock.products = baseProducts;
        }

        // Asegurar id y type desde el DOM del editor si aún no están en el objeto
        const editorItem = document.querySelector(`#content-item-${idx}`);
        if (!mergedBlock.id && editorItem) {
          mergedBlock.id = editorItem.getAttribute("data-block-id") || editorItem.querySelector('input[name$="[id]"]')?.value;
        }
        if (!mergedBlock.type && editorItem) {
          mergedBlock.type = editorItem.querySelector('input[name$="[type]"]')?.value;
        }

        // Aplicar Blob URLs pendientes desde RAM sin haberlas persistido en localStorage
        const blockBlobKey = mergedBlock.id || idx;
        const mainBlobUrl = pendingBlobUrls.get(`block:${blockBlobKey}`) || pendingBlobUrls.get(`block:${idx}`);
        if (mainBlobUrl) {
          mergedBlock.imgSrc = mainBlobUrl;
          mergedBlock.imgDefault = true;
          mergedBlock.imgShow = true;
        }
        if (Array.isArray(mergedBlock.products)) {
          mergedBlock.products = mergedBlock.products.map((prod, subIdx) => {
            const subBlobUrl = pendingBlobUrls.get(`block:${blockBlobKey}:sub:${subIdx}`) || pendingBlobUrls.get(`block:${idx}:sub:${subIdx}`);
            if (!subBlobUrl) return prod;
            return Object.assign({}, prod, { imgSrc: subBlobUrl, imgDefault: true, imgShow: true });
          });
        }

        previews.forEach((p) => {
          window.LivePreviewManager.renderBlockInPreview(p, mergedBlock, idx, structuredDraft);
        });
      });
    }
  }

  /**
   * Sincroniza la interfaz visual de estado de visibilidad del perfil (texto en Visibilidad y badge en barra lateral).
   * @param {boolean} isHidden
   */
  function syncProfileVisibilityUI(isHidden) {
    const statusText = document.getElementById("profile-visibility-status-text");
    if (statusText) {
      statusText.textContent = isHidden ? "oculto" : "visible";
    }
    const hiddenBadge = document.getElementById("sidebar-status-hidden-badge");
    const visibleLink = document.getElementById("sidebar-status-visible-link");
    if (hiddenBadge && visibleLink) {
      if (isHidden) {
        hiddenBadge.classList.remove("hidden");
        hiddenBadge.style.removeProperty("display");
        visibleLink.classList.add("hidden");
        visibleLink.style.setProperty("display", "none", "important");
      } else {
        hiddenBadge.classList.add("hidden");
        hiddenBadge.style.setProperty("display", "none", "important");
        visibleLink.classList.remove("hidden");
        visibleLink.style.removeProperty("display");
      }
    }
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

      // Manejo específico para style_back / fondo
      if (name === "style_back") {
        const styleVal = String(val);
        const hiddenStyleInput = document.getElementById("input_style_back");
        if (hiddenStyleInput) hiddenStyleInput.value = styleVal;

        const isGrad = (styleVal === "gradientUp" || styleVal === "gradientDown");
        const gradRadio = document.getElementById("style_gradient");
        const solidRadio = document.getElementById("style_solid");
        const videoRadio = document.getElementById("style_video");
        const imageRadio = document.getElementById("style_image");

        if (isGrad && gradRadio) {
          gradRadio.checked = true;
        } else if (styleVal === "solid" && solidRadio) {
          solidRadio.checked = true;
        } else if (styleVal === "video" && videoRadio) {
          videoRadio.checked = true;
        } else if (styleVal === "image" && imageRadio) {
          imageRadio.checked = true;
        }

        if (isGrad) {
          const upRadio = document.getElementById("direction_up");
          const downRadio = document.getElementById("direction_down");
          if (styleVal === "gradientUp" && upRadio) {
            upRadio.checked = true;
          } else if (styleVal === "gradientDown" && downRadio) {
            downRadio.checked = true;
          }
        }

        const gradientWrapper = document.getElementById("gradient-direction-wrapper");
        const videoWrapper = document.getElementById("video-controls-wrapper");
        const imageWrapper = document.getElementById("image-controls-wrapper");
        setElementVisibility(gradientWrapper, isGrad);
        setElementVisibility(videoWrapper, styleVal === "video");
        setElementVisibility(imageWrapper, styleVal === "image");
      }

      // Manejo específico para filtro de imagen
      if (name === "back_image_filter") {
        const numToFilterMap = {
          "0": "none",
          "1": "vignette",
          "2": "blur",
          "3": "brightness",
          "4": "contrast",
          "5": "grayscale",
          "6": "hue-rotate",
          "7": "invert",
          "8": "saturate",
          "9": "sepia"
        };
        const filterVal = numToFilterMap[String(val)] || String(val);
        const radios = document.querySelectorAll(`input[type="radio"][name="back_image_filter"]`);
        radios.forEach((r) => {
          if (r.value === filterVal || r.id === `filter_${val}`) {
            r.checked = true;
            syncConditionalUI(r);
          }
        });
        return;
      }

      // Manejo específico para intensidad de filtro de imagen
      if (name === "back_image_filter_intensity") {
        const intensitySlider = document.getElementById("select-filter-intensity");
        const intensityVal = document.getElementById("image-filter-val");
        const intensityHidden = document.getElementById("input-filter-intensity-val");
        if (intensitySlider) {
          intensitySlider.value = val;
          intensitySlider.style.setProperty("--range-progress", `${val}%`);
        }
        if (intensityVal) {
          intensityVal.textContent = `${val}%`;
        }
        if (intensityHidden) {
          intensityHidden.value = val;
        }
        return;
      }

      // Manejo específico para opacidad de imagen de fondo
      if (name === "back_image_opacity") {
        const opacityHidden = document.getElementById("input-opacity-image-val");
        const opacitySlider = document.getElementById("select-opacity-image-overlay");
        const opacityValText = document.getElementById("image-opacity-val");
        const parsedVal = parseInt(val, 10);
        const actualOpacity = isNaN(parsedVal) ? 45 : Math.max(0, Math.min(95, parsedVal));
        const userPercent = actualOpacity > 0 ? Math.min(100, Math.round((actualOpacity / 95) * 100)) : 0;
        if (opacityHidden) opacityHidden.value = actualOpacity;
        if (opacitySlider) {
          opacitySlider.value = userPercent;
          opacitySlider.style.setProperty("--range-progress", `${userPercent}%`);
        }
        if (opacityValText) opacityValText.textContent = `${userPercent}%`;
        return;
      }

      // Manejo específico para opacidad de video de fondo
      if (name === "back_video_opacity") {
        const opacityHidden = document.getElementById("input-opacity-val");
        const opacitySlider = document.getElementById("select-opacity-overlay");
        const opacityValText = document.getElementById("video-opacity-val");
        const parsedVal = parseInt(val, 10);
        const actualOpacity = isNaN(parsedVal) ? 45 : Math.max(0, Math.min(95, parsedVal));
        const userPercent = actualOpacity > 0 ? Math.min(100, Math.round((actualOpacity / 95) * 100)) : 0;
        if (opacityHidden) opacityHidden.value = actualOpacity;
        if (opacitySlider) {
          opacitySlider.value = userPercent;
          opacitySlider.style.setProperty("--range-progress", `${userPercent}%`);
        }
        if (opacityValText) opacityValText.textContent = `${userPercent}%`;
        return;
      }

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
            syncProfileVisibilityUI(isChecked);
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

  let remoteAjaxQueue = Promise.resolve();
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
      const bId = panelElement?.getAttribute("data-block-id");
      previewElements = bId 
        ? Array.from(document.querySelectorAll(`.user-profile-preview [data-block-id="${bId}"]`))
        : Array.from(document.querySelectorAll(`.user-profile-preview [data-content-index="${contentIndex}"]`));
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
   * Gestiona la eliminación de un bloque o red social localmente en el borrador,
   * actualizando la interfaz y la vista previa de inmediato sin persistir a la base
   * de datos hasta que el usuario pulse explícitamente el botón Guardar.
   *
   * @param {HTMLElement} [triggerElement] Elemento disparador (checkbox, botón)
   * @param {string} [targetId] ID del checkbox objetivo si vino de un label
   * @returns {boolean} True si se procesó la eliminación localmente.
   */
  function handleLocalBlockDelete(triggerElement = null, targetId = null) {
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
      const panelElement = document.getElementById("content-item-" + contentIndex) 
        || (triggerElement ? triggerElement.closest(".sortable-item, .content-block") : null);
      const bId = panelElement ? panelElement.getAttribute("data-block-id") : null;

        // Remover inmediatamente del preview y liberar cualquier Blob temporal en RAM
      revokeBlockBlobs(bId);
      const previewContainers = document.querySelectorAll(".preview-widget-container, #preview-widget-container");
      previewContainers.forEach((pContainer) => {
        let prevEls = bId 
          ? Array.from(pContainer.querySelectorAll(`[data-block-id="${bId}"]`))
          : Array.from(pContainer.querySelectorAll(`[data-content-index="${contentIndex}"]`));
        prevEls.forEach((el) => {
          el.remove();
        });
      });

      // Remover del editor
      if (panelElement) {
        panelElement.remove();
      }

      // Re-indexar el editor
      const list = document.getElementById("sortable-content-list");
      if (list) {
        reindexEditorDOM(list);
      }

      // Actualizar el borrador JSON eliminando el bloque
      window.updateJsonDraft((state) => {
        if (state && state.content && Array.isArray(state.content)) {
          if (bId) {
            state.content = state.content.filter((b) => b && b.id !== bId);
          } else {
            const idxNum = parseInt(contentIndex, 10);
            if (!isNaN(idxNum) && idxNum >= 0 && idxNum < state.content.length) {
              state.content.splice(idxNum, 1);
            }
          }
        }
        return state;
      });

      // Notificar cambio de borrador y habilitar botón guardar
      notifyDraftState();
      if (window.__saveButtonController && typeof window.__saveButtonController.enableSaveButton === "function") {
        window.__saveButtonController.enableSaveButton();
      }
      return true;
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
      const panelElement = document.getElementById("rrss-item-" + rrssIndex) 
        || (triggerElement ? triggerElement.closest(".sortable-item, .rrss-item") : null);
      const rrssNameInput = panelElement ? panelElement.querySelector(`input[name="rrss[${rrssIndex}][0]"]`) : null;
      const socialName = (rrssNameInput ? rrssNameInput.value : panelElement?.getAttribute("data-rrss-name") || "").trim().toLowerCase();

      // Remover del preview
      if (socialName) {
        document.querySelectorAll(`.user-profile-preview [data-link-id="rrss_${socialName}"], .user-profile-preview [aria-label="${socialName}"]`).forEach((el) => {
          el.remove();
        });
      }
      document.querySelectorAll(".user-profile-preview .preview-rrss-container").forEach((wrap) => {
        const remainingVisible = Array.from(wrap.querySelectorAll("a[data-link-id^='rrss_']")).some((a) => a.style.display !== "none");
        wrap.style.display = remainingVisible ? "" : "none";
      });

      // Remover del editor
      if (panelElement) {
        panelElement.remove();
      }

      // Re-indexar lista de redes sociales
      const rrssList = document.getElementById("sortable-rrss-list");
      if (rrssList) {
        reindexRrssEditorDOM(rrssList);
      }

      // Actualizar el borrador JSON eliminando la red social
      window.updateJsonDraft((state) => {
        if (state && state.rrss && Array.isArray(state.rrss)) {
          state.rrss = state.rrss.filter((r) => {
            const name = (Array.isArray(r) ? r[0] : r?.name || "").trim().toLowerCase();
            return name !== socialName;
          });
        }
        return state;
      });

      // Notificar cambio de borrador y habilitar botón guardar
      notifyDraftState();
      if (window.__saveButtonController && typeof window.__saveButtonController.enableSaveButton === "function") {
        window.__saveButtonController.enableSaveButton();
      }
      return true;
    }

    return false;
  }

  /**
   * Procesa en 0ms y de forma 100% local en el cliente las acciones sobre imágenes
   * (toggle_img_show, delete_img) y sub-productos de product_group (add_sub_product, delete_sub_product)
   * sin enviar el formulario al servidor ni reiniciar el estado Guardar/Descartar.
   *
   * @param {HTMLElement} submitBtn Botón disparador
   * @returns {boolean} True si la acción fue manejada localmente.
   */
  function handleLocalImageAndSubProductAction(submitBtn) {
    if (!submitBtn || !submitBtn.name) return false;
    const nameStr = submitBtn.name;
    const block = submitBtn.closest(".sortable-item") || submitBtn.closest(".content-block");
    if (!block) return false;

    const bId = block.getAttribute("data-block-id") || block.querySelector('input[name$="[id]"]')?.value || null;
    const bIdx = getBlockIndex(block) ?? 0;

    // 1. Mostrar / Ocultar imagen (toggle_img_show)
    if (nameStr.endsWith("[toggle_img_show]")) {
      const imgShowName = nameStr.replace(/\[toggle_img_show\]$/, "[imgShow]");
      const imgShowInput = block.querySelector(`input[name="${imgShowName}"]`);
      const currentVal = imgShowInput ? (imgShowInput.value === "true" || imgShowInput.value === "1") : true;
      const newVal = !currentVal;

      if (imgShowInput) {
        imgShowInput.value = newVal ? "true" : "false";
      }

      const eyeOpen = submitBtn.querySelector(".icon-eye-open");
      const eyeClosed = submitBtn.querySelector(".icon-eye-closed");
      if (eyeOpen && eyeClosed) {
        eyeOpen.style.display = newVal ? "" : "none";
        eyeClosed.style.display = newVal ? "none" : "";
      }
      submitBtn.title = newVal ? "Ocultar imagen" : "Mostrar imagen";

      setDraftField(imgShowName, newVal, bId);
      syncBlockActiveState(block, bIdx);
      applyDraftToPreview(getDraft());
      notifyDraftState();
      return true;
    }

    // 2. Eliminar imagen personalizada (delete_img)
    if (nameStr.endsWith("[delete_img]")) {
      const basePrefix = nameStr.replace(/\[delete_img\]$/, "");
      const subMatch = basePrefix.match(/\[products\]\[(\d+)\]$/);
      const blobKey = subMatch ? `block:${bId || bIdx}:sub:${subMatch[1]}` : `block:${bId || bIdx}`;
      revokePendingBlob(blobKey);
      const scopeEl = submitBtn.closest(".sub-product-item") || block;
      const noImgUrl = "/Uploads/Media/Static/Custom/no-image.webp";

      const thumb = scopeEl.querySelector("figure img");
      if (thumb) {
        thumb.src = noImgUrl;
      }

      const imgInput = block.querySelector(`input[name="${basePrefix}[img]"]`);
      const imgDefInput = block.querySelector(`input[name="${basePrefix}[imgDefault]"]`);
      const imgShowInput = block.querySelector(`input[name="${basePrefix}[imgShow]"]`);
      if (imgInput) imgInput.value = "no-image.webp";
      if (imgDefInput) imgDefInput.value = "false";
      if (imgShowInput) imgShowInput.value = "true";

      const fileInp = scopeEl.querySelector('input[type="file"]');
      if (fileInp) {
        try { fileInp.value = ""; } catch (_) {}
        delete fileInp.dataset.isCropped;
      }

      submitBtn.style.display = "none";

      const toggleBtn = scopeEl.querySelector('button[name$="[toggle_img_show]"]');
      if (toggleBtn) {
        const eyeOpen = toggleBtn.querySelector(".icon-eye-open");
        const eyeClosed = toggleBtn.querySelector(".icon-eye-closed");
        if (eyeOpen && eyeClosed) {
          eyeOpen.style.display = "";
          eyeClosed.style.display = "none";
        }
      }

      setDraftField(`${basePrefix}[img]`, "no-image.webp", bId);
      setDraftField(`${basePrefix}[imgDefault]`, false, bId);
      setDraftField(`${basePrefix}[imgSrc]`, noImgUrl, bId);
      setDraftField(`${basePrefix}[imgShow]`, true, bId);

      syncBlockActiveState(block, bIdx);
      applyDraftToPreview(getDraft());
      notifyDraftState();
      return true;
    }

    // 3. Añadir sub-producto en product_group (add_sub_product)
    if (nameStr.endsWith("[add_sub_product]")) {
      const subContainer = block.querySelector(".sub-products-container") || submitBtn.parentElement;
      if (!subContainer) return false;
      const currentSubItems = Array.from(subContainer.querySelectorAll(".sub-product-item"));
      if (currentSubItems.length >= 8) return true;

      const newSubIdx = currentSubItems.length;
      const tplGroup = document.getElementById("tpl_block_product_group");
      let newSubNode = null;
      if (tplGroup) {
        const tmp = document.createElement("div");
        tmp.innerHTML = tplGroup.innerHTML.replace(/\{\{INDEX\}\}/g, String(bIdx));
        const tplSub = tmp.querySelector(".sub-product-item");
        if (tplSub) newSubNode = tplSub.cloneNode(true);
      }

      if (newSubNode) {
        subContainer.insertBefore(newSubNode, submitBtn);
      }

      reindexSubProductsInBlock(block, bIdx);

      const newCount = subContainer.querySelectorAll(".sub-product-item").length;
      const isOdd = newCount % 2 !== 0;

      window.updateJsonDraft((state) => {
        if (!state || !Array.isArray(state.content)) return state;
        const targetBlock = (bId && state.content.find((b) => b && b.id === bId)) || state.content[bIdx];
        if (targetBlock) {
          if (!Array.isArray(targetBlock.products)) targetBlock.products = [];
          targetBlock.products.push({
            img: "no-image.webp",
            imgSrc: "/Uploads/Media/Static/Custom/no-image.webp",
            title: "",
            url: "",
            price: "",
            offer: false,
            discount: "",
            porcentage: 0,
            imgDefault: false,
            imgShow: true,
            metaTitle: "",
            metaDesc: "",
            metaImg: ""
          });
          if (isOdd) {
            targetBlock.layout = "slide";
          }
        }
        return state;
      });

      if (window.__formComponents) {
        window.__formComponents.initCheckboxSwitches?.();
      }
      syncBlockActiveState(block, bIdx);
      applyDraftToPreview(getDraft());
      document.dispatchEvent(new CustomEvent("previewUpdated"));
      document.dispatchEvent(new CustomEvent("contentUpdated"));
      notifyDraftState();
      return true;
    }

    // 4. Eliminar sub-producto en product_group (delete_sub_product)
    if (nameStr.endsWith("[delete_sub_product]")) {
      const subItem = submitBtn.closest(".sub-product-item");
      const subContainer = block.querySelector(".sub-products-container") || subItem?.parentElement;
      if (!subItem || !subContainer) return false;

      const currentSubItems = Array.from(subContainer.querySelectorAll(".sub-product-item"));
      if (currentSubItems.length <= 2) return true;

      const subIdx = currentSubItems.indexOf(subItem);
      if (subIdx === -1) return false;

      subItem.remove();
      reindexSubProductsInBlock(block, bIdx);

      const newCount = subContainer.querySelectorAll(".sub-product-item").length;
      const isOdd = newCount % 2 !== 0;

      window.updateJsonDraft((state) => {
        if (!state || !Array.isArray(state.content)) return state;
        const targetBlock = (bId && state.content.find((b) => b && b.id === bId)) || state.content[bIdx];
        if (targetBlock && Array.isArray(targetBlock.products)) {
          targetBlock.products.splice(subIdx, 1);
          if (isOdd) {
            targetBlock.layout = "slide";
          }
        }
        return state;
      });

      syncBlockActiveState(block, bIdx);
      applyDraftToPreview(getDraft());
      document.dispatchEvent(new CustomEvent("previewUpdated"));
      document.dispatchEvent(new CustomEvent("contentUpdated"));
      notifyDraftState();
      return true;
    }

    return false;
  }

  /**
   * Parsea de manera segura respuestas que deben ser JSON, limpiando
   * cualquier salida previa o posterior (como warnings o notices de depuración).
   *
   * @async
   * @param {Response} response Objeto Response retornado por fetch.
   * @returns {Promise<any>} Objeto JSON parseado.
   */
  async function parseSafeJson(response) {
    const rawText = await response.text();
    try {
      return JSON.parse(rawText);
    } catch (e) {
      const jsonStart = rawText.indexOf("{");
      const jsonEnd = rawText.lastIndexOf("}");
      if (jsonStart !== -1 && jsonEnd !== -1 && jsonEnd > jsonStart) {
        return JSON.parse(rawText.substring(jsonStart, jsonEnd + 1));
      }
      throw e;
    }
  }

  let isCheckingMetadata = false;

  /**
   * Consulta al servidor en segundo plano si existen enlaces o productos con
   * metadatos pendientes de extraer (OpenGraph / YouTube oEmbed) y actualiza
   * la vista previa y los campos sin bloquear la interacción ni recargar la página.
   *
   * @async
   * @returns {Promise<void>}
   */
  async function triggerPendingMetadataCheck() {
    if (isCheckingMetadata || isSubmittingRemoteAjax) return;

    try {
      isCheckingMetadata = true;
      const csrf = getCsrfToken();
      const bodyParams = new URLSearchParams({ compact: "1" });
      if (csrf) bodyParams.set("_token", csrf);

      const response = await fetch(`/panel/${user}/extraer-metadatos?compact=1`, {
        method: "POST",
        body: bodyParams,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          ...(csrf ? { "X-CSRF-TOKEN": csrf } : {})
        }
      });

      if (!response.ok) return;

      const data = await parseSafeJson(response);
      if (data && data.success && data.updated) {
        // Actualizar estados en RAM y localStorage preservando cambios locales en curso
        if (data.card) {
          setInitialState(data.card);
          const currentDraft = getDraftState();
          if (currentDraft && Array.isArray(currentDraft.content) && Array.isArray(data.card.content)) {
            const serverById = new Map();
            data.card.content.forEach((sb) => {
              if (sb && sb.id) serverById.set(sb.id, sb);
            });
            const previews = document.querySelectorAll(".user-profile-preview");

            currentDraft.content.forEach((localBlk, idx) => {
              if (!localBlk || !localBlk.id) return;
              const srvBlk = serverById.get(localBlk.id);
              if (!srvBlk) return;

              let blkChanged = false;
              const editorItem = document.querySelector(`.sortable-item[data-block-id="${localBlk.id}"]`) || document.getElementById(`content-item-${idx}`);

              if (srvBlk.metaScraped && !localBlk.metaScraped) {
                localBlk.metaTitle = srvBlk.metaTitle || localBlk.metaTitle || "";
                localBlk.metaDesc = srvBlk.metaDesc || localBlk.metaDesc || "";
                localBlk.metaImg = srvBlk.metaImg || localBlk.metaImg || "";
                localBlk.metaScraped = true;
                if (!localBlk.title && srvBlk.title) {
                  localBlk.title = srvBlk.title;
                }
                blkChanged = true;
              }

              if (Array.isArray(localBlk.products) && Array.isArray(srvBlk.products)) {
                localBlk.products.forEach((lSub, sIdx) => {
                  const sSub = srvBlk.products[sIdx];
                  if (sSub && sSub.metaScraped && !lSub.metaScraped) {
                    lSub.metaTitle = sSub.metaTitle || lSub.metaTitle || "";
                    lSub.metaDesc = sSub.metaDesc || lSub.metaDesc || "";
                    lSub.metaImg = sSub.metaImg || lSub.metaImg || "";
                    lSub.metaScraped = true;
                    if (!lSub.title && sSub.title) lSub.title = sSub.title;
                    blkChanged = true;
                  }
                });
              }

              if (blkChanged) {
                if (editorItem) {
                  const titleInp = editorItem.querySelector(`input[name^="content["][name$="[title]"]`);
                  if (titleInp && !titleInp.value && localBlk.title) titleInp.value = localBlk.title;
                  const metaT = editorItem.querySelector(`input[name^="content["][name$="[metaTitle]"]`);
                  if (metaT) metaT.value = localBlk.metaTitle || "";
                  const metaD = editorItem.querySelector(`input[name^="content["][name$="[metaDesc]"]`);
                  if (metaD) metaD.value = localBlk.metaDesc || "";
                  const metaI = editorItem.querySelector(`input[name^="content["][name$="[metaImg]"]`);
                  if (metaI) metaI.value = localBlk.metaImg || "";
                }
                if (window.LivePreviewManager?.renderBlockInPreview) {
                  previews.forEach((p) => {
                    window.LivePreviewManager.renderBlockInPreview(p, localBlk, idx, currentDraft);
                  });
                }
              }
            });
            setDraftStateImmediate(currentDraft);
          } else {
            setDraftStateImmediate(data.card);
          }
        }

        if (data.sidebarStatusHtml) {
          document.querySelectorAll(".sidebar-profile-status").forEach((sidebar) => {
            sidebar.innerHTML = data.sidebarStatusHtml;
          });
        }

        // Modo legado: solo si el servidor devolvió explícitamente html completo (no compacto)
        if (!data.compact && data.html) {
          updateUserPreviewTemplate(data.html);
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
          linkPreviewBlockIds();
        }

        document.dispatchEvent(new CustomEvent("previewUpdated", { detail: data }));
        notifyDraftState();
      }
    } catch (e) {
      console.warn("Extracción de metadatos en segundo plano:", e);
    } finally {
      isCheckingMetadata = false;
    }
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
  /**
   * Encola y procesa el envío asíncrono de un formulario de .remote-container
   * garantizando ejecución secuencial y retornando siempre una promesa.
   *
   * @param {HTMLFormElement} [form] Formulario origen
   * @param {HTMLElement} [triggerElement] Elemento botón o checkbox que disparó la acción
   * @returns {Promise<boolean>}
   */
  function submitRemoteFormAjax(form, triggerElement = null) {
    const run = () => executeSubmitRemoteFormAjax(form, triggerElement);
    const promise = remoteAjaxQueue.then(run, run);
    remoteAjaxQueue = promise.then(() => true, () => false);
    return promise;
  }

  /**
   * Ejecución real de la petición Fetch asíncrona hacia el backend.
   *
   * @async
   * @param {HTMLFormElement} [form]
   * @param {HTMLElement} [triggerElement]
   * @returns {Promise<boolean>}
   */
  async function executeSubmitRemoteFormAjax(form, triggerElement = null) {
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

      // 2. Obtener siempre el formulario más actualizado desde el DOM
      let currentForm = form;
      if (!currentForm || !document.body.contains(currentForm)) {
        currentForm = document.querySelector("#sortable-content-list")?.closest("form") 
          || document.querySelector(".remote-content.active form") 
          || document.querySelector(".remote-container form");
      }
      if (!currentForm) {
        return false;
      }

      // 3. Crear FormData con los campos del formulario
      const formData = new FormData(currentForm);

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

      const isReorderAction = formData.has("is_reorder") || (triggerElement && triggerElement.name === "is_reorder");

      Object.keys(draft).forEach((key) => {
        if (deletedPrefix && key.startsWith(deletedPrefix)) {
          return; // Omitir datos antiguos de un elemento que se está borrando
        }
        if (isReorderAction && (key.startsWith("content[") || key.startsWith("rrss["))) {
          return; // En reordenamiento, los inputs del formulario en el DOM ya reflejan el orden y valores exactos reindexados
        }
        if (draft[key] && typeof draft[key] === "object" && !(draft[key] instanceof File) && !(draft[key] instanceof Blob)) { appendFormDataRecursive(formData, draft[key], key); } else { formData.set(key, draft[key]); }
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

      // 8. Enviar petición Fetch con encabezado XMLHttpRequest y token CSRF
      const csrf = getCsrfToken();
      if (csrf && !formData.has("_token") && !formData.has("csrf_token")) {
        formData.append("_token", csrf);
      }
      const postUrl = currentForm.getAttribute("action") || `/panel/${user}/diseno`;
      const response = await fetch(postUrl, {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          ...(csrf ? { "X-CSRF-TOKEN": csrf } : {})
        }
      });

      if (!response.ok) {
        throw new Error(`Error en el servidor al actualizar el diseño: ${response.statusText}`);
      }

      const data = await parseSafeJson(response);

      if (data && data.success) {
        if (data.card) {
          setInitialState(data.card);
          setDraftStateImmediate(data.card);
        }

        // 10. Actualizar vista previa oficial (.user-profile-preview) y plantilla compartida
        if (data.html) {
          updateUserPreviewTemplate(data.html);
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
            let activeId = activeContent ? activeContent.id : null;
            if (!activeId) {
              const closestRemote = (triggerElement && typeof triggerElement.closest === "function") ? triggerElement.closest(".remote-content") : null;
              activeId = closestRemote ? closestRemote.id : (window.__menuMemory?.get?.() || null);
            }

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

              // Sincronizar de inmediato los controles de formulario con cualquier cambio pendiente del borrador
              syncFormControls(getDraft());

              // Inicializar inmediatamente switches y componentes de formulario en el nuevo contenido
              if (window.__formComponents) {
                window.__formComponents.initCheckboxSwitches?.();
                window.__formComponents.styleColorPickers?.();
                window.__formComponents.initCustomRangeSliders?.();
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

        setTimeout(() => {
          triggerPendingMetadataCheck();
        }, 500);

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
        if (handleLocalImageAndSubProductAction(submitter)) {
          return;
        }
        if (submitter.name && (submitter.name.includes("delete_sub_product") || submitter.name.includes("delete_img"))) {
          performOptimisticDelete(submitter);
        }
        submitRemoteFormAjax(form, submitter);
        return;
      }

      // Si es un submit convencional por presionar Enter en un input dentro del editor,
      // desenfocar el campo para consolidar su valor en el borrador local sin disparar
      // un guardado remoto ni recargar el DOM mientras el usuario sigue interactuando
      if (document.activeElement && (document.activeElement.tagName === "INPUT" || document.activeElement.tagName === "TEXTAREA")) {
        document.activeElement.blur();
        return;
      }
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
      
      // Cerrar modal inmediatamente
      document.querySelectorAll(".modal-overlay, .custom-modal-overlay").forEach((m) => m.remove());
      deleteLabel.closest(".content-modal-menu, .modal-menu, .hidden")?.classList.add("hidden");
      
      handleLocalBlockDelete(checkbox, targetId);
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
          if (handleLocalImageAndSubProductAction(submitBtn)) {
            return;
          }
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
    if (!target) return;

    // Control deslizante de opacidad de overlay de imagen
    if (target.id === "select-opacity-image-overlay") {
      const parsedVal = parseInt(target.value, 10);
      const sliderVal = Math.max(0, Math.min(100, isNaN(parsedVal) ? 0 : parsedVal));
      const actualOpacity = Math.round((sliderVal / 100) * 95);
      target.style.setProperty("--range-progress", `${sliderVal}%`);

      const valText = document.getElementById("image-opacity-val");
      if (valText) valText.textContent = `${sliderVal}%`;

      const hiddenInput = document.getElementById("input-opacity-image-val");
      if (hiddenInput) hiddenInput.value = actualOpacity;

      setDraftField("back_image_opacity", actualOpacity);
      applyDraftToPreview(getDraft());
      return;
    }

    // Control deslizante de opacidad de overlay de video
    if (target.id === "select-opacity-overlay") {
      const parsedVal = parseInt(target.value, 10);
      const sliderVal = Math.max(0, Math.min(100, isNaN(parsedVal) ? 0 : parsedVal));
      const actualOpacity = Math.round((sliderVal / 100) * 95);
      target.style.setProperty("--range-progress", `${sliderVal}%`);

      const valText = document.getElementById("video-opacity-val");
      if (valText) valText.textContent = `${sliderVal}%`;

      const hiddenInput = document.getElementById("input-opacity-val");
      if (hiddenInput) hiddenInput.value = actualOpacity;

      setDraftField("back_video_opacity", actualOpacity);
      scheduleRafTask(() => applyDraftToPreview(getDraft()));
      return;
    }

    // Control deslizante de intensidad del filtro de imagen
    if (target.id === "select-filter-intensity") {
      const parsedVal = parseInt(target.value, 10);
      const sliderVal = Math.max(0, Math.min(100, isNaN(parsedVal) ? 0 : parsedVal));
      target.style.setProperty("--range-progress", `${sliderVal}%`);

      const valText = document.getElementById("image-filter-val");
      if (valText) valText.textContent = `${sliderVal}%`;

      const hiddenInput = document.getElementById("input-filter-intensity-val");
      if (hiddenInput) hiddenInput.value = sliderVal;

      setDraftField("back_image_filter_intensity", sliderVal);
      scheduleRafTask(() => applyDraftToPreview(getDraft()));
      return;
    }

    if (!target.name) return;

    // Solo procesar controles pertenecientes al contenedor de edición
    if (!target.closest(".remote-container") && !target.closest(".custom-color-picker-popover")) return;

    const ownerBlock = target.closest(".sortable-item.content-block, .sortable-item[data-block-id]");
    const ownerBlockId = ownerBlock?.getAttribute("data-block-id") || ownerBlock?.querySelector('input[name$="[id]"]')?.value || null;

    const THEME_VAR_FIELDS = new Set(["back", "color", "colorText", "titleColor", "colorShadow3", "title", "desc", "void_space", "voidHero[space]"]);

    const renderGranularBlock = (blockEl, blockId) => {
      const structDraft = getDraftState();
      if (!structDraft || !Array.isArray(structDraft.content)) return;
      const idx = blockEl ? getBlockIndex(blockEl) : null;
      let bIdx = blockId ? structDraft.content.findIndex((b) => b && b.id === blockId) : -1;
      if (bIdx === -1 && idx !== null && !isNaN(parseInt(idx, 10))) {
        bIdx = parseInt(idx, 10);
      }
      if (bIdx < 0 || !structDraft.content[bIdx]) return;

      const blockCopy = Object.assign({}, structDraft.content[bIdx]);
      const blobKey = blockCopy.id || bIdx;
      const mainBlob = pendingBlobUrls.get(`block:${blobKey}`) || pendingBlobUrls.get(`block:${bIdx}`);
      if (mainBlob) {
        blockCopy.imgSrc = mainBlob;
        blockCopy.imgDefault = true;
        blockCopy.imgShow = true;
      }
      if (Array.isArray(blockCopy.products)) {
        blockCopy.products = blockCopy.products.map((pItem, sIdx) => {
          const sBlob = pendingBlobUrls.get(`block:${blobKey}:sub:${sIdx}`) || pendingBlobUrls.get(`block:${bIdx}:sub:${sIdx}`);
          return sBlob ? Object.assign({}, pItem, { imgSrc: sBlob, imgDefault: true, imgShow: true }) : pItem;
        });
      }

      const targetIdx = idx !== null ? idx : bIdx;
      scheduleRafTask(() => {
        if (!window.LivePreviewManager?.renderBlockInPreview) return;
        document.querySelectorAll(".user-profile-preview").forEach((p) => {
          window.LivePreviewManager.renderBlockInPreview(p, blockCopy, targetIdx, structDraft);
        });
      });
    };

    // Manejo de selectores de color
    const isColor = target.type === "color" || target.classList.contains("color-picker");
    if (isColor) {
      const labelText = target.closest("label")?.querySelector("p, span");
      if (labelText && target.value) {
        labelText.textContent = target.value;
      }
      syncSiblingInputsByName(target.name, target.value, target);
      setDraftField(target.name, target.value, ownerBlockId);
      if (ownerBlock) {
        renderGranularBlock(ownerBlock, ownerBlockId);
      } else if (THEME_VAR_FIELDS.has(target.name)) {
        const fieldName = target.name;
        const fieldVal = target.value;
        scheduleRafTask(() => {
          document.querySelectorAll(".user-profile-preview").forEach((p) => {
            window.LivePreviewManager?.applyThemeVarsToPreview?.(p, { [fieldName]: fieldVal });
          });
        });
      } else {
        scheduleRafTask(() => applyDraftToPreview(getDraft()));
      }
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
      if (!ownerBlock) {
        syncSiblingInputsByName(target.name, target.value, target);
      }
      setDraftField(target.name, target.value, ownerBlockId);

      if (ownerBlock) {
        renderGranularBlock(ownerBlock, ownerBlockId);
      } else {
        const matchRrss = target.name.match(/^rrss\[(\d+)\]\[1\]$/);
        if (matchRrss) {
          const rrssIdx = matchRrss[1];
          const rrssUrl = String(target.value || "").trim();
          const nameInput = document.querySelector(`input[name="rrss[${rrssIdx}][0]"]`);
          const socialName = (nameInput ? nameInput.value : "").trim().toLowerCase();
          if (socialName) {
            scheduleRafTask(() => {
              document.querySelectorAll(".user-profile-preview").forEach((p) => {
                const link = p.querySelector(`[data-link-id="rrss_${socialName}"], [aria-label="${socialName}"]`);
                if (link) {
                  link.href = rrssUrl || "#";
                  link.style.display = rrssUrl !== "" ? "" : "none";
                }
                const wrapper = p.querySelector(".preview-rrss-container");
                if (wrapper) {
                  const anyVisible = Array.from(wrapper.querySelectorAll("a[data-link-id^='rrss_']")).some((a) => a.style.display !== "none");
                  wrapper.style.display = anyVisible ? "" : "none";
                }
              });
            });
          }
        } else if (THEME_VAR_FIELDS.has(target.name)) {
          const fieldName = target.name;
          const fieldVal = target.value;
          scheduleRafTask(() => {
            document.querySelectorAll(".user-profile-preview").forEach((p) => {
              window.LivePreviewManager?.applyThemeVarsToPreview?.(p, { [fieldName]: fieldVal });
            });
          });
        } else {
          scheduleRafTask(() => applyDraftToPreview(getDraft()));
        }
      }

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

    const ownerBlock = target.closest(".sortable-item.content-block, .sortable-item[data-block-id]");
    const ownerBlockId = ownerBlock?.getAttribute("data-block-id") || ownerBlock?.querySelector('input[name$="[id]"]')?.value || null;

    // Sincronizar UI condicional del formulario
    syncConditionalUI(target);

    // Checkbox de borrado directo
    if (target.type === "checkbox" && target.name.includes("[delete]") && target.checked) {
      if (handleLocalBlockDelete(target)) {
        return;
      }
      const form = target.closest("form") || document.querySelector(".remote-content.active form") || document.querySelector(".remote-container form");
      submitRemoteFormAjax(form, target);
      return;
    }

    // Radios
    if (target.type === "radio" && target.checked) {
      if (target.name === "background_mode" || target.name === "gradient_direction") {
        let effectiveStyle = "solid";
        const bgModeRadio = document.querySelector('input[name="background_mode"]:checked');
        const bgMode = bgModeRadio ? bgModeRadio.value : (target.name === "background_mode" ? target.value : "solid");

        if (bgMode === "gradient") {
          const gradRadio = document.getElementById("style_gradient");
          if (gradRadio && !gradRadio.checked) {
            gradRadio.checked = true;
          }
          const dirRadio = document.querySelector('input[name="gradient_direction"]:checked');
          effectiveStyle = dirRadio ? dirRadio.value : "gradientDown";
        } else if (bgMode === "video") {
          effectiveStyle = "video";
        } else if (bgMode === "image") {
          effectiveStyle = "image";
        } else {
          effectiveStyle = "solid";
        }

        const hiddenStyleInput = document.getElementById("input_style_back");
        if (hiddenStyleInput) {
          hiddenStyleInput.value = effectiveStyle;
        }

        setDraftField("style_back", effectiveStyle);
        setDraftField("background_mode", bgMode);
        if (bgMode === "gradient") {
          const dirRadio = document.querySelector('input[name="gradient_direction"]:checked');
          if (dirRadio) {
            setDraftField("gradient_direction", dirRadio.value);
          }
        }
        applyDraftToPreview(getDraft());
        return;
      }

      if (target.classList && target.classList.contains("separator-icon-radio")) {
        const isSpace = (target.value === "none" || target.value === "ban");
        const modeFieldName = target.name.replace(/\[separator_icon\]$/, "[separator_mode]");
        setDraftField(modeFieldName, isSpace ? "space" : "line", ownerBlockId);
      }

      setDraftField(target.name, target.value, ownerBlockId);
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
      // Si el usuario conmuta manualmente el switch de activación de un bloque, registrar su preferencia
      if (target.matches(".checkbox-switch") && /^content\[\d+\]\[active\]$/.test(target.name)) {
        const editorBlock = target.closest(".sortable-item");
        if (editorBlock) {
          editorBlock.dataset.userTurnedOff = target.checked ? "false" : "true";
        }
      }

      setDraftField(target.name, target.checked ? (target.value || "true") : "false", ownerBlockId);
      applyDraftToPreview(getDraft());

      // Si es el switch de ocultar perfil, sincronizar el texto explicativo de la tarjeta y badge lateral
      if (target.name === "hide") {
        syncProfileVisibilityUI(target.checked);
      }

      if (!(target.matches(".checkbox-switch") && /^content\[\d+\]\[active\]$/.test(target.name))) {
        const block = target.closest(".sortable-item.content-block");
        if (block) {
          const idx = getBlockIndex(block);
          if (idx !== null) {
            syncBlockActiveState(block, idx);
          }
        }
      }
      return;
    }

    // Selectores y otros inputs
    if (target.type !== "file") {
      setDraftField(target.name, target.value, ownerBlockId);
      applyDraftToPreview(getDraft());
      const block = target.closest(".sortable-item.content-block");
      if (block) {
        const idx = getBlockIndex(block);
        if (idx !== null) {
          syncBlockActiveState(block, idx);
        }
      }

      // Si el campo modificado es una URL válida, programar comprobación de metadatos en segundo plano
      if (target.matches(".content-url-input") || (target.name && target.name.includes("[url]"))) {
        const urlVal = (target.value || "").trim();
        if (urlVal.startsWith("http://") || urlVal.startsWith("https://")) {
          setTimeout(triggerPendingMetadataCheck, 600);
        }
      }
    }
  });

  // Manejo de imágenes (avatar y bloques de contenido) en cliente con vista previa instantánea
  document.addEventListener("change", (e) => {
    const target = e.target;
    if (!target || target.type !== "file") return;

    if (target.name === "avatar" && target.files && target.files[0]) {
      const isCropInput = target.classList.contains("selectAndCropImage");
      if (isCropInput && target.dataset.isCropped !== "true") {
        return;
      }

      const file = target.files[0];
      const previewUrl = setPendingBlob("avatar", file);

      // 1. Actualizar las miniaturas de los botones de estilo de cabecera en el panel del editor
      document.querySelectorAll('label[for="regularHeader"] figure img, label[for="midHeader"] figure img, label[for$="Header"] figure img').forEach((img) => {
        img.src = previewUrl;
      });

      // 2. Actualizar la imagen de cabecera / avatar en todos los contenedores .user-profile-preview vía LivePreviewManager
      document.querySelectorAll(".user-profile-preview").forEach((p) => {
        if (window.LivePreviewManager && typeof window.LivePreviewManager.applyThemeVarsToPreview === "function") {
          window.LivePreviewManager.applyThemeVarsToPreview(p, { avatarSrc: previewUrl });
        } else {
          p.querySelectorAll(".header-variant-wrapper figure img").forEach((img) => {
            img.src = previewUrl;
          });
        }
      });

      notifyDraftState();
    } else if (target.name === "back_image" && target.files && target.files[0]) {
      const isCropInput = target.classList.contains("selectAndCropImage");
      if (isCropInput && target.dataset.isCropped !== "true") {
        return;
      }

      const file = target.files[0];
      const previewUrl = setPendingBlob("back_image", file);

      // 1. Actualizar imagen, overlay y viñeta en la vista previa del celular
      document.querySelectorAll(".user-profile-preview .back-image-bg").forEach((img) => {
        img.src = previewUrl;
        img.style.display = "";
      });
      document.querySelectorAll(".user-profile-preview .back-image-overlay").forEach((ov) => {
        ov.style.display = "";
      });
      document.querySelectorAll(".user-profile-preview .back-image-vignette").forEach((vig) => {
        const checkedFilter = document.querySelector('input[name="back_image_filter"]:checked');
        const filterVal = checkedFilter ? checkedFilter.value : "none";
        vig.style.display = (filterVal === "vignette" || filterVal === "1") ? "" : "none";
      });

      // 2. Actualizar miniaturas en el panel de fondo del editor
      const thumb = document.getElementById("thumb-image-preview");
      if (thumb) {
        thumb.src = previewUrl;
        thumb.style.display = "";
        if (thumb.nextElementSibling) thumb.nextElementSibling.style.display = "none";
      }
      const thumbStyle = document.getElementById("thumb-style-image");
      if (thumbStyle) {
        thumbStyle.src = previewUrl;
      }

      // 3. Conmutar a modo 'image' automáticamente al subir una imagen
      const imageRadio = document.getElementById("style_image");
      if (imageRadio && !imageRadio.checked) {
        imageRadio.checked = true;
      }
      const hiddenStyle = document.getElementById("input_style_back");
      if (hiddenStyle) {
        hiddenStyle.value = "image";
      }
      setDraftField("style_back", "image");
      setDraftField("background_mode", "image");

      notifyDraftState();
      applyDraftToPreview(getDraft());
    } else if (target.name && target.name.startsWith("content_img_") && target.files && target.files[0]) {
      const isCropInput = target.classList.contains("selectAndCropImage");
      if (isCropInput && target.dataset.isCropped !== "true") {
        return;
      }

      const file = target.files[0];
      const match = target.name.match(/^content_img_(\d+)(?:_(\d+))?$/);
      if (match) {
        const itemIdx = match[1];
        const subIdx = match[2];

        // 1. Actualizar miniatura dentro del formulario en el editor
        const subItem = target.closest(".sub-product-item");
        const block = target.closest(".sortable-item") || target.closest(".content-block");
        const bId = block?.getAttribute("data-block-id") || block?.querySelector('input[name$="[id]"]')?.value || null;
        const bIdx = (block ? getBlockIndex(block) : null) ?? itemIdx;
        const blobKey = subIdx !== undefined ? `block:${bId || bIdx}:sub:${subIdx}` : `block:${bId || bIdx}`;
        const previewUrl = setPendingBlob(blobKey, file);

        if (subItem) {
          const thumb = subItem.querySelector("figure img");
          if (thumb) thumb.src = previewUrl;
          const delBtn = subItem.querySelector('button[name$="[delete_img]"], .delete-img-btn');
          if (delBtn) delBtn.style.display = "flex";
        } else if (block) {
          const thumb = block.querySelector("figure img");
          if (thumb) thumb.src = previewUrl;
          const delBtn = block.querySelector('button[name$="[delete_img]"], .delete-img-btn');
          if (delBtn) delBtn.style.display = "flex";
        }

        // 2. Persistir solo imgDefault=true e imgShow=true en localStorage (el Blob URL queda exclusivamente en RAM)
        if (subIdx !== undefined) {
          const subImgDefInput = block?.querySelector(`input[name="content[${bIdx}][products][${subIdx}][imgDefault]"]`);
          if (subImgDefInput) subImgDefInput.value = "true";
          const subImgShowInput = block?.querySelector(`input[name="content[${bIdx}][products][${subIdx}][imgShow]"]`);
          if (subImgShowInput) subImgShowInput.value = "true";
          setDraftField(`content[${bIdx}][products][${subIdx}][imgDefault]`, true, bId);
          setDraftField(`content[${bIdx}][products][${subIdx}][imgShow]`, true, bId);
        } else {
          const imgDefInput = block?.querySelector(`input[name="content[${bIdx}][imgDefault]"]`);
          if (imgDefInput) imgDefInput.value = "true";
          const imgShowInput = block?.querySelector(`input[name="content[${bIdx}][imgShow]"]`);
          if (imgShowInput) imgShowInput.value = "true";
          setDraftField(`content[${bIdx}][imgDefault]`, true, bId);
          setDraftField(`content[${bIdx}][imgShow]`, true, bId);
        }

        // 3. Sincronizar estado del bloque y renderizar exclusivamente este bloque en vista previa vía LivePreviewManager
        if (block) {
          syncBlockActiveState(block, bIdx, { previewUrl });
        }
        const structDraft = getDraftState();
        const blockObj = (bId && structDraft?.content?.find((b) => b && b.id === bId)) || structDraft?.content?.[bIdx];
        if (blockObj && window.LivePreviewManager?.renderBlockInPreview) {
          const renderCopy = Object.assign({}, blockObj);
          if (subIdx !== undefined && Array.isArray(renderCopy.products)) {
            renderCopy.products = renderCopy.products.map((pItem, sI) => (
              String(sI) === String(subIdx) ? Object.assign({}, pItem, { imgSrc: previewUrl, imgDefault: true, imgShow: true }) : pItem
            ));
          } else {
            renderCopy.imgSrc = previewUrl;
            renderCopy.imgDefault = true;
            renderCopy.imgShow = true;
          }
          document.querySelectorAll(".user-profile-preview").forEach((p) => {
            window.LivePreviewManager.renderBlockInPreview(p, renderCopy, bIdx, structDraft || {});
          });
        } else {
          applyDraftToPreview(getDraft());
        }
      }
      notifyDraftState();
    }
  });

  // =========================================================================
  // 6. ACCIONES GLOBALES: GUARDAR Y DESCARTAR
  // =========================================================================

  let isSavingDraft = false;

  /**
   * Vuelca la caché del borrador local a la base de datos del usuario
   * en una única petición consolidada y limpia la caché local al tener éxito.
   *
   * @async
   * @returns {Promise<boolean>} True si se guardó con éxito.
   */
  async function saveDraft() {
    if (isSavingDraft) {
      return false;
    }
    isSavingDraft = true;

    try {
      await remoteAjaxQueue;
      while (isSubmittingRemoteAjax) {
        await new Promise((resolve) => setTimeout(resolve, 80));
      }

      flushDraftSave();
      const draftState = getDraftState();
      const formData = new FormData();

      // Súper Guardado compacto: enviar únicamente draft_json estructurado + flags esenciales + archivos modificados
      if (draftState) {
        formData.set("draft_json", JSON.stringify(draftState));
        formData.set("is_full_content", "1");
      }
      formData.set("compact", "1");

      const hideSubmitted = document.querySelector('input[name="hide_form_submitted"]');
      if (hideSubmitted) {
        formData.set("hide_form_submitted", hideSubmitted.value || "1");
      }
      const videoUrlDirect = document.getElementById("input-back-video-url");
      if (videoUrlDirect && videoUrlDirect.value) {
        formData.set("back_video_url_direct", videoUrlDirect.value);
      }
      const videoPubIdDirect = document.querySelector('input[name="back_video_public_id_direct"]');
      if (videoPubIdDirect && videoPubIdDirect.value) {
        formData.set("back_video_public_id_direct", videoPubIdDirect.value);
      }
      const deleteVideoFlag = document.getElementById("delete-video-flag");
      if (deleteVideoFlag && deleteVideoFlag.value === "true") {
        formData.set("delete_video", "true");
      }

      // Adjuntar únicamente inputs file activos con tamaño > 0
      const fileInputs = document.querySelectorAll("input[type='file']");
      fileInputs.forEach((fileInput) => {
        if (fileInput.name && fileInput.files && fileInput.files.length > 0) {
          const file = fileInput.files[0];
          if (file && file.size > 0) {
            formData.set(fileInput.name, file);
          }
        }
      });

      let csrf = getCsrfToken();
      formData.delete("_token");
      formData.delete("csrf_token");
      if (csrf) {
        formData.set("_token", csrf);
      }

      const saveUrl = `/panel/${user}/guardar?compact=1`;

      let response = await fetch(saveUrl, {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          ...(csrf ? { "X-CSRF-TOKEN": csrf } : {})
        }
      });

      // Si el servidor indica 403 (token expirado), intentar refrescar el token CSRF y reintentar una vez
      if (response.status === 403) {
        try {
          const refreshRes = await fetch(`/panel/${user}/csrf-token`);
          if (refreshRes.ok) {
            const refreshData = await refreshRes.json();
            if (refreshData && refreshData.csrf_token) {
              csrf = refreshData.csrf_token;
              const meta = document.querySelector('meta[name="csrf-token"]');
              if (meta) meta.setAttribute('content', csrf);
              document.querySelectorAll('input[name="_token"], input[name="csrf_token"]').forEach((inp) => {
                inp.value = csrf;
              });
              formData.set("_token", csrf);
              response = await fetch(saveUrl, {
                method: "POST",
                body: formData,
                headers: {
                  "X-Requested-With": "XMLHttpRequest",
                  "X-CSRF-TOKEN": csrf
                }
              });
            }
          }
        } catch (_) {}
      }

      if (!response.ok) {
        throw new Error(`Error en el servidor al guardar el diseño: ${response.statusText}`);
      }

      const data = await parseSafeJson(response);

      if (data && data.success) {
        // En Two-JSON: sincronizar ambos JSONs (initial y draft) con la versión oficial guardada
        const savedCard = data.card || draftState;
        if (savedCard) {
          setInitialState(savedCard);
          setDraftStateImmediate(savedCard);
        }

        // Liberar Blob URLs pendientes y limpiar inputs file tras el guardado exitoso
        clearAllPendingBlobs();
        document.querySelectorAll("input[type='file']").forEach((fileInput) => {
          try {
            fileInput.value = "";
            delete fileInput.dataset.isCropped;
          } catch (_) {}
        });

        // Solo limpiar sobreescrituras CSS temporales cuando llega un preview HTML oficial completo
        if (!data.compact && data.html && dynamicStyleEl) {
          dynamicStyleEl.textContent = "";
        }

        // Si el servidor respondió en modo compacto, reconciliar rutas finales de imágenes sin destruir el DOM
        if (data.compact && savedCard) {
          const finalAvatar = savedCard.avatarSrc || (savedCard.avatar ? `/Uploads/Avatar/${savedCard.avatar}` : "");
          if (finalAvatar) {
            document.querySelectorAll('label[for="regularHeader"] figure img, label[for="midHeader"] figure img, label[for$="Header"] figure img').forEach((img) => {
              img.src = finalAvatar;
            });
            document.querySelectorAll(".user-profile-preview").forEach((p) => {
              window.LivePreviewManager?.applyThemeVarsToPreview?.(p, { avatarSrc: finalAvatar });
            });
          }

          const finalBgImg = savedCard.backCard?.back_image_src || (savedCard.backCard?.back_image ? `/Uploads/Background/${savedCard.backCard.back_image}` : "");
          if (finalBgImg) {
            const thumb = document.getElementById("thumb-image-preview");
            if (thumb) thumb.src = finalBgImg;
            const thumbStyle = document.getElementById("thumb-style-image");
            if (thumbStyle) thumbStyle.src = finalBgImg;
            document.querySelectorAll(".user-profile-preview .back-image-bg").forEach((img) => {
              img.src = finalBgImg;
            });
          }

          if (Array.isArray(savedCard.content)) {
            savedCard.content.forEach((blk, idx) => {
              if (!blk) return;
              const edItem = (blk.id && document.querySelector(`.sortable-item[data-block-id="${blk.id}"]`)) || document.getElementById(`content-item-${idx}`);
              if (edItem) {
                const imgInp = edItem.querySelector(`input[name^="content["][name$="[img]"]`);
                if (imgInp && blk.img) imgInp.value = blk.img;
                const mainThumb = edItem.querySelector(":scope figure img");
                if (mainThumb && blk.imgSrc && !edItem.querySelector(".sub-product-item")) {
                  mainThumb.src = blk.imgSrc;
                }
                if (Array.isArray(blk.products)) {
                  const subItems = edItem.querySelectorAll(".sub-product-item");
                  blk.products.forEach((subP, sIdx) => {
                    const subEl = subItems[sIdx];
                    if (subEl && subP) {
                      const subImgInp = subEl.querySelector(`input[name$="[products][${sIdx}][img]"]`);
                      if (subImgInp && subP.img) subImgInp.value = subP.img;
                      const subThumb = subEl.querySelector("figure img");
                      if (subThumb && subP.imgSrc) subThumb.src = subP.imgSrc;
                    }
                  });
                }
              }
              document.querySelectorAll(".user-profile-preview").forEach((p) => {
                window.LivePreviewManager?.renderBlockInPreview?.(p, blk, idx, savedCard);
              });
            });
          }

          applyDraftToPreview(getDraft());
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
          linkPreviewBlockIds();
        }

        // Actualizar formularios del editor si vienen en la respuesta oficial
        if (data.formHtml) {
          const remoteContainer = document.querySelector(".remote-container");
          if (remoteContainer) {
            const activeContent = remoteContainer.querySelector(".remote-content.active");
            const activeId = activeContent ? activeContent.id : null;
            const savedScrollTop = activeContent ? activeContent.scrollTop : 0;

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
                    if (savedScrollTop > 0) {
                      c.style.setProperty("scroll-behavior", "auto", "important");
                      c.scrollTop = savedScrollTop;
                    }
                  } else {
                    c.classList.remove("active");
                    c.classList.add("hidden");
                  }
                });
              }

              if (window.__formComponents) {
                window.__formComponents.initCheckboxSwitches?.();
                window.__formComponents.styleColorPickers?.();
                window.__formComponents.initCustomRangeSliders?.();
              }
              refreshAllBannersState();
            }
          }
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
        notifyDraftState();
        return true;
      } else {
        throw new Error(data?.message || "No se pudo completar el guardado del diseño.");
      }
    } finally {
      isSavingDraft = false;
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
    await remoteAjaxQueue;
    while (isSubmittingRemoteAjax) {
      await new Promise((resolve) => setTimeout(resolve, 80));
    }

    // 1. Revertir localmente en 0ms igualando el borrador al estado inicial oficial
    clearAllPendingBlobs();
    const initial = getInitialState();
    if (initial) {
      setDraftStateImmediate(initial);
      restoreOrderFromDraft();
      applyDraftToPreview(flattenObject(initial));
      syncFormControls(flattenObject(initial));
    }
    clearDraft();

    // Eliminar sobreescrituras dinámicas
    if (dynamicStyleEl) {
      dynamicStyleEl.textContent = "";
    }

    const csrf = getCsrfToken();
    const discardUrl = `/panel/${user}/descartar`;

    const response = await fetch(discardUrl, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {})
      }
    });

    if (!response.ok) {
      throw new Error(`Error en el servidor al descartar el diseño: ${response.statusText}`);
    }

    const data = await parseSafeJson(response);

    if (data && data.success) {
      clearAllPendingBlobs();
      if (data.card) {
        setInitialState(data.card);
        setDraftStateImmediate(data.card);
      }

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
              window.__formComponents.initCustomRangeSliders?.();
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
    syncFormControls,
    triggerPendingMetadataCheck
  };

  // Restauración inmediata al cargar la página
  // Restauración inmediata al cargar la página
  function initRestoration() {
    linkPreviewBlockIds();
    restoreOrderFromDraft();
    const draft = getDraft();
    if (Object.keys(draft).length > 0) {
      applyDraftToPreview(draft);
      syncFormControls(draft);
      notifyDraftState();
    }
    refreshAllBannersState();
    document.dispatchEvent(new CustomEvent("previewUpdated"));
    document.dispatchEvent(new CustomEvent("contentUpdated"));
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initRestoration);
  } else {
    initRestoration();
  }

  // Lógica para añadir nuevos bloques usando <template> en lugar de submit
  document.addEventListener("click", function(e) {
    const btn = e.target.closest("button[data-action=\"add-block-template\"]");
    if (btn) {
      e.preventDefault();
      const type = btn.getAttribute("data-type");
      if (!type) return;

      const list = document.getElementById("sortable-content-list");
      if (!list) return;

      // Generar un ID de bloque persistente y único
      const newBlockId = "blk_" + Math.random().toString(36).substring(2, 9) + Date.now().toString(36);

      // 1. Obtener el template del formulario (Left side)
      let newNode = null;
      const tplForm = document.getElementById("tpl_block_" + type);
      if (tplForm) {
        let htmlForm = tplForm.innerHTML
          .replace(/\{\{INDEX\}\}/g, "0")
          .replace(/\{\{BLOCK_ID\}\}/g, newBlockId);
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = htmlForm;
        newNode = tempDiv.firstElementChild;
        if (newNode) {
          list.querySelectorAll(".sortable-item.content-block, .sortable-item[data-collapsible]").forEach((openItem) => {
            openItem.classList.remove("is-open");
            openItem.classList.add("is-collapsed");
            const openBody = openItem.querySelector(".content-item-body, [data-sortable-body]");
            if (openBody) {
              openBody.style.display = "none";
              openBody.style.height = "auto";
            }
          });
          newNode.setAttribute("data-block-id", newBlockId);
          list.insertBefore(newNode, list.firstChild);
          newNode.classList.remove("is-collapsed");
          newNode.classList.add("is-open");
          const body = newNode.querySelector(".content-item-body, [data-sortable-body]");
          if (body) {
            body.style.display = "flex";
            body.style.height = "auto";
          }
        }
      }

      // 2. Obtener el template del Preview (Right side)
      const tplPreview = document.getElementById("tpl_preview_" + type);
      const widgetContainers = document.querySelectorAll(".preview-widget-container, #preview-widget-container");
      if (tplPreview && widgetContainers.length > 0) {
        let htmlPreview = tplPreview.innerHTML
          .replace(/\{\{INDEX\}\}/g, "0")
          .replace(/\{\{BLOCK_ID\}\}/g, newBlockId);
        widgetContainers.forEach((widgetContainer) => {
          const tempDiv = document.createElement("div");
          tempDiv.innerHTML = htmlPreview;
          const nodes = Array.from(tempDiv.children);
          const fragment = document.createDocumentFragment();
          nodes.forEach((node) => {
            if (node.hasAttribute && (node.hasAttribute("data-content-index") || node.hasAttribute("data-block-id") || (node.className && node.className.includes("block-wrapper")))) {
              node.setAttribute("data-block-id", newBlockId);
              node.setAttribute("data-content-index", "0");
              node.style.display = "none";
            }
            fragment.appendChild(node);
          });
          widgetContainer.insertBefore(fragment, widgetContainer.firstChild);
        });
      }

      // 3. Reindexar todo el DOM del editor y sincronizar preview inmediatamente
      reindexEditorDOM(list);

      // 4. Crear el objeto por defecto limpio e inactivo para el borrador JSON
      // Soporta automáticamente cualquier bloque actual o futuro leyendo también los valores iniciales del <template>
      let defaultBlock = {
        id: newBlockId,
        type: type,
        active: false,
        img: "no-image.webp",
        imgSrc: "",
        imgShow: true,
        imgDefault: false
      };

      // Hidratar dinámicamente propiedades por defecto desde los controles del nodo de plantilla recién clonado
      if (newNode) {
        newNode.querySelectorAll("input[name], select[name], textarea[name]").forEach((ctrl) => {
          if (ctrl.type === "file") return;
          if (ctrl.type === "radio" && !ctrl.checked) return;
          const m = ctrl.name.match(/^content\[\d+\]\[([^\]]+)\](?:\[(\d+)\]\[([^\]]+)\])?$/);
          if (!m) return;
          const prop = m[1];
          const subIdx = m[2];
          const subProp = m[3];
          if (prop === "delete" || prop === "active") return;
          let val = ctrl.type === "checkbox" ? ctrl.checked : ctrl.value;
          if (val === "true") val = true;
          else if (val === "false") val = false;

          if (subIdx !== undefined && subProp !== undefined) {
            if (!Array.isArray(defaultBlock[prop])) defaultBlock[prop] = [];
            const idxNum = parseInt(subIdx, 10);
            if (!defaultBlock[prop][idxNum]) {
              defaultBlock[prop][idxNum] = { img: "no-image.webp", imgShow: true, imgDefault: false };
            }
            defaultBlock[prop][idxNum][subProp] = val;
          } else {
            defaultBlock[prop] = val;
          }
        });
      }

      if (type === "link") {
        defaultBlock.title = defaultBlock.title || "";
        defaultBlock.url = defaultBlock.url || "";
        defaultBlock.imgShow = true;
      } else if (type === "product") {
        defaultBlock.title = defaultBlock.title || "";
        defaultBlock.url = defaultBlock.url || "";
        defaultBlock.price = defaultBlock.price || "";
        defaultBlock.offer = false;
        defaultBlock.discount = "";
        defaultBlock.porcentage = 0;
        defaultBlock.imgShow = true;
      } else if (type === "product_group") {
        defaultBlock.title = defaultBlock.title || "";
        defaultBlock.layout = defaultBlock.layout || "grid";
        if (!Array.isArray(defaultBlock.products) || defaultBlock.products.length === 0) {
          defaultBlock.products = [
            { img: "no-image.webp", title: "", url: "", price: "", offer: false, discount: "", porcentage: 0, imgDefault: false, imgShow: true, metaTitle: "", metaDesc: "", metaImg: "" },
            { img: "no-image.webp", title: "", url: "", price: "", offer: false, discount: "", porcentage: 0, imgDefault: false, imgShow: true, metaTitle: "", metaDesc: "", metaImg: "" }
          ];
        }
      } else if (type === "campaign") {
        defaultBlock.title = defaultBlock.title || "";
        defaultBlock.desc = defaultBlock.desc || "";
        defaultBlock.button_text = defaultBlock.button_text || "Suscribirme";
        defaultBlock.ask_name = defaultBlock.ask_name !== undefined ? defaultBlock.ask_name : true;
        defaultBlock.ask_whatsapp = defaultBlock.ask_whatsapp !== undefined ? defaultBlock.ask_whatsapp : false;
        defaultBlock.size = defaultBlock.size || "horizontal";
        defaultBlock.text_position = defaultBlock.text_position || "center";
        defaultBlock.text_align = defaultBlock.text_align || "center";
        defaultBlock.title_size = defaultBlock.title_size || "large";
        defaultBlock.desc_size = defaultBlock.desc_size || "medium";
        defaultBlock.title_color = defaultBlock.title_color || "#ffffff";
        defaultBlock.desc_color = defaultBlock.desc_color || "#ffffff";
        defaultBlock.has_countdown = false;
        defaultBlock.img_position = defaultBlock.img_position || "background";
        defaultBlock.bg_opacity = defaultBlock.bg_opacity !== undefined ? defaultBlock.bg_opacity : 80;
        defaultBlock.bg_color = defaultBlock.bg_color || "#1e1e1e";
        defaultBlock.imgShow = true;
      } else if (type === "banner") {
        defaultBlock.url = defaultBlock.url || "";
        defaultBlock.size = defaultBlock.size || "";
        defaultBlock.bg_color = defaultBlock.bg_color || "#f2e5ff";
        defaultBlock.bg_opacity = defaultBlock.bg_opacity !== undefined ? defaultBlock.bg_opacity : 100;
        defaultBlock.imgShow = true;
      } else if (type === "title") {
        defaultBlock.title = defaultBlock.title || "";
        defaultBlock.title_size = defaultBlock.title_size || "small";
        defaultBlock.title_weight = defaultBlock.title_weight || "500";
      } else if (type === "text") {
        defaultBlock.text = defaultBlock.text || "";
        defaultBlock.text_weight = defaultBlock.text_weight || "400";
        defaultBlock.text_align = defaultBlock.text_align || "left";
      } else if (type === "separator") {
        defaultBlock.separator_mode = defaultBlock.separator_mode || "space";
        defaultBlock.separator_icon = defaultBlock.separator_icon || "none";
        defaultBlock.separator_size = defaultBlock.separator_size || "large";
        defaultBlock.space_size = defaultBlock.space_size || "40";
        defaultBlock.active = true;
        if (newNode) {
          const sepActiveSw = newNode.querySelector('input.checkbox-switch[name$="[active]"]');
          if (sepActiveSw) {
            sepActiveSw.checked = true;
            sepActiveSw.setAttribute("active", "1");
          }
        }
      } else if (type === "video") {
        defaultBlock.video_mode = defaultBlock.video_mode || "link";
        defaultBlock.title = defaultBlock.title || "";
        defaultBlock.url = defaultBlock.url || "";
        defaultBlock.imgShow = true;
      }

      window.updateJsonDraft(state => {
        if (!state.content) state.content = [];
        state.content.unshift(defaultBlock);
        return state;
      });

      if (newNode) {
        syncBlockActiveState(newNode, 0);
      }

      if (typeof initColorPickers === "function") initColorPickers();
      if (window.__formComponents) {
        window.__formComponents.initCheckboxSwitches?.();
        window.__formComponents.styleColorPickers?.();
        window.__formComponents.initCustomRangeSliders?.();
      }

      applyDraftToPreview(getDraft());

      // Disparar eventos para que el sistema y componentes (cropImage, cutPhrase, sortable, formComponents) inicialicen el nuevo bloque
      document.dispatchEvent(new CustomEvent("designDraftStateChanged", { detail: { hasDraft: true } }));
      document.dispatchEvent(new CustomEvent("previewUpdated"));
      document.dispatchEvent(new CustomEvent("remoteContentUpdated"));
      document.dispatchEvent(new CustomEvent("contentUpdated"));
      notifyDraftState();

      setTimeout(() => {
        if (window.__saveButtonController && typeof window.__saveButtonController.enableSaveButton === "function") {
          window.__saveButtonController.enableSaveButton();
        }
        if (typeof initColorPickers === "function") initColorPickers();
        if (window.__formComponents) {
          window.__formComponents.initCheckboxSwitches?.();
          window.__formComponents.styleColorPickers?.();
          window.__formComponents.initCustomRangeSliders?.();
        }
        document.dispatchEvent(new CustomEvent("previewUpdated"));
        document.dispatchEvent(new CustomEvent("contentUpdated"));
      }, 50);
    }

    // Añadir red social en 0ms mediante <template> (Local-First)
    const rrssBtn = e.target.closest('button[data-action="add-rrss-template"]');
    if (rrssBtn) {
      e.preventDefault();
      const rawName = rrssBtn.getAttribute("data-rrss");
      const cleanName = rawName ? rawName.trim().toLowerCase() : "";
      if (!cleanName) return;

      const modalMenu = rrssBtn.closest(".content-modal-menu");
      if (modalMenu) {
        modalMenu.classList.add("hidden");
      }

      const rrssList = document.getElementById("sortable-rrss-list");
      const newRrssNode = buildRrssEditorItemFromTemplate(cleanName, 0, "");
      if (rrssList && newRrssNode) {
        rrssList.insertBefore(newRrssNode, rrssList.firstChild);
        reindexRrssEditorDOM(rrssList);
      }

      document.querySelectorAll(".user-profile-preview .preview-rrss-container").forEach((wrapper) => {
        const previewLink = buildRrssPreviewLinkFromTemplate(cleanName, "");
        if (previewLink) {
          wrapper.insertBefore(previewLink, wrapper.firstChild);
        }
      });

      window.updateJsonDraft((state) => {
        if (!Array.isArray(state.rrss)) state.rrss = [];
        state.rrss.unshift([cleanName, ""]);
        return state;
      });

      document.dispatchEvent(new CustomEvent("designDraftStateChanged", { detail: { hasDraft: true } }));
      document.dispatchEvent(new CustomEvent("previewUpdated"));
      document.dispatchEvent(new CustomEvent("contentUpdated"));
      notifyDraftState();

      if (window.__saveButtonController?.enableSaveButton) {
        window.__saveButtonController.enableSaveButton();
      }

      const urlInput = newRrssNode?.querySelector('input[type="text"]');
      if (urlInput) {
        requestAnimationFrame(() => urlInput.focus());
      }
    }
  });

  /**
   * Actualiza la plantilla única #user-preview-template cuando el servidor devuelve un HTML oficial nuevo.
   *
   * @param {string} html HTML renderizado de .user-profile-preview.
   */
  function updateUserPreviewTemplate(html) {
    if (!html || typeof html !== "string") return;
    const tpl = document.getElementById("user-preview-template");
    if (!tpl) return;
    const temp = document.createElement("div");
    temp.innerHTML = html.trim();
    const nextPreview = temp.querySelector(".user-profile-preview") || temp.firstElementChild;
    if (!nextPreview) return;
    tpl.innerHTML = "";
    tpl.content.appendChild(nextPreview);
  }
  window.__updateUserPreviewTemplate = updateUserPreviewTemplate;

  /**
   * Monta e hidrata una instancia de .user-profile-preview en el contenedor indicado,
   * clonando el preview de escritorio si está vivo o desde #user-preview-template si querys.js lo vació.
   *
   * @param {HTMLElement|null} mountEl Contenedor destino donde montar el preview.
   * @param {boolean} [preferDesktopClone=true] Si debe intentar clonar primero #preview-mount-desktop.
   */
  function mountPreviewInto(mountEl, preferDesktopClone = true) {
    if (!mountEl) return;

    const desktopPreview = preferDesktopClone
      ? document.querySelector("#preview-mount-desktop .user-profile-preview")
      : null;

    let fragmentOrNode = null;
    let mountedFromTemplate = false;

    if (desktopPreview) {
      fragmentOrNode = desktopPreview.cloneNode(true);
    } else {
      const tpl = document.getElementById("user-preview-template");
      if (!tpl) return;
      fragmentOrNode = tpl.content.cloneNode(true);
      mountedFromTemplate = true;
    }

    if (fragmentOrNode.querySelectorAll) {
      fragmentOrNode.querySelectorAll("[data-gsap-hover-bound]").forEach((el) => {
        el.removeAttribute("data-gsap-hover-bound");
      });
    }

    mountEl.innerHTML = "";
    mountEl.appendChild(fragmentOrNode);

    const mountedPreview = mountEl.querySelector(".user-profile-preview");
    if (!mountedPreview) return;

    if (mountedFromTemplate) {
      linkPreviewBlockIds();
      syncPreviewStructureFromDraft(mountedPreview, getDraftState());
      const flatDraft = getDraft();
      if (Object.keys(flatDraft).length > 0) {
        applyDraftToPreview(flatDraft);
      }
    }

    const modalVideo = mountedPreview.querySelector(".back-video-bg");
    if (modalVideo && modalVideo.style.display !== "none") {
      modalVideo.muted = true;
      modalVideo.playsInline = true;
      modalVideo.play().catch(() => {});
    }

    document.dispatchEvent(new CustomEvent("contentUpdated"));
    document.dispatchEvent(new CustomEvent("previewUpdated"));
  }

  // Hidratar el montaje de escritorio si la página cargó en tablet/móvil y luego pasó a ancho desktop
  const desktopMountMedia = window.matchMedia("(min-width: 993px)");
  const ensureDesktopMountHydrated = (isDesktop) => {
    if (!isDesktop) return;
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        if (!desktopMountMedia.matches) return;
        const desktopMount = document.getElementById("preview-mount-desktop");
        if (desktopMount && !desktopMount.closest('[aria-hidden="true"]') && !desktopMount.querySelector(".user-profile-preview")) {
          mountPreviewInto(desktopMount, false);
        }
      });
    });
  };
  if (typeof desktopMountMedia.addEventListener === "function") {
    desktopMountMedia.addEventListener("change", (ev) => ensureDesktopMountHydrated(ev.matches));
  } else if (typeof desktopMountMedia.addListener === "function") {
    desktopMountMedia.addListener((ev) => ensureDesktopMountHydrated(ev.matches));
  }

  // Montar bajo demanda el preview dentro del modal móvil/tablet desde desktop o #user-preview-template
  document.addEventListener("click", (e) => {
    const previewTrigger = e.target.closest('[data-preview-modal-trigger="true"]');
    if (!previewTrigger) return;

    queueMicrotask(() => {
      const mount = document.querySelector(".modal-overlay .mobile-preview-mount");
      if (!mount) return;
      mountPreviewInto(mount, true);
    });
  }, true);

  document.addEventListener("sortableUpdated", (e) => {
    if (!e.detail || !e.detail.container) return;
    const container = e.detail.container;

    // 1. Reordenamiento de bloques de contenido
    if (container.id === "sortable-content-list") {
      let draft = getDraftState();
      if (!draft || !Array.isArray(draft.content)) return;

      const items = Array.from(container.querySelectorAll(".sortable-item"));
      const newContent = [];
      items.forEach((item) => {
        const blkId = item.getAttribute("data-block-id");
        let found = blkId ? draft.content.find((b) => b && b.id === blkId) : null;
        if (!found) {
          const oldIdx = parseInt(item.getAttribute("data-index"), 10);
          if (!isNaN(oldIdx) && draft.content[oldIdx]) {
            found = draft.content[oldIdx];
          }
        }
        if (found) newContent.push(found);
      });

      if (newContent.length === draft.content.length) {
        draft.content = newContent;
      }
      saveDraftState(draft);

      // Reindexar DOM del editor para mantener sincronía de índices y atributos name
      reindexEditorDOM(container);

      // ¡Reordenar quirúrgicamente el DOM de la vista previa en 0ms sin destruir iframes ni causar reflow!
      const previewContainers = document.querySelectorAll(".preview-widget-container, #preview-widget-container");
      previewContainers.forEach((pContainer) => {
        const scroller = pContainer.closest(".overflow-y-scroll") ||
          document.querySelector(".user-profile-preview [data-scroll-memory='user-preview'], .user-profile-preview .overflow-y-scroll");
        const prevScroll = scroller ? scroller.scrollTop : 0;

        const targetNodes = [];
        draft.content.forEach((block, newIdx) => {
          if (!block) return;
          const prevEls = block.id ? Array.from(pContainer.querySelectorAll(`[data-block-id="${block.id}"]`)) : [];
          prevEls.forEach((el) => {
            el.setAttribute("data-content-index", String(newIdx));
            targetNodes.push(el);
          });
        });

        targetNodes.forEach((node, i) => {
          if (pContainer.children[i] !== node) {
            pContainer.insertBefore(node, pContainer.children[i] || null);
          }
        });

        if (scroller && prevScroll > 0) {
          scroller.scrollTop = prevScroll;
        }
      });

      setDraftField("is_reorder", "true");
      notifyDraftState();
    }

    // 2. Reordenamiento de redes sociales
    if (container.id === "sortable-rrss-list") {
      let draft = getDraftState();
      if (!draft || !Array.isArray(draft.rrss)) return;

      const items = Array.from(container.querySelectorAll(".sortable-item"));
      const newRrss = [];
      items.forEach((item) => {
        const name = (item.getAttribute("data-rrss-name") 
          || item.querySelector('input[name$="[0]"]')?.value 
          || item.querySelector('input[type="hidden"][name*="[0]"]')?.value 
          || "").trim().toLowerCase();
        const found = draft.rrss.find((r) => {
          const rName = (Array.isArray(r) ? r[0] : r?.name || "").trim().toLowerCase();
          return rName === name;
        });
        if (found) newRrss.push(found);
      });

      if (newRrss.length > 0) {
        const remaining = draft.rrss.filter((r) => !newRrss.includes(r));
        draft.rrss = [...newRrss, ...remaining];
      }
      saveDraftState(draft);

      // Reindexar inputs del formulario de RRSS
      items.forEach((item, index) => {
        item.id = `rrss-item-${index}`;
        item.setAttribute("data-index", String(index));
        item.querySelectorAll("input").forEach((input) => {
          const n = input.getAttribute("name");
          if (n && n.startsWith("rrss[")) {
            input.setAttribute("name", n.replace(/^rrss\[\d+\]/, `rrss[${index}]`));
          }
          if (input.id && input.id.startsWith("delete-rrss-")) {
            input.id = `delete-rrss-${index}`;
          }
        });
        item.querySelectorAll("label").forEach((label) => {
          const f = label.getAttribute("for");
          if (f && f.startsWith("delete-rrss-")) {
            label.setAttribute("for", `delete-rrss-${index}`);
          }
        });
      });

      // ¡Reordenar quirúrgicamente el DOM de redes sociales en TODAS las cabeceras de la vista previa en 0ms!
      const allPreviews = document.querySelectorAll(".user-profile-preview");
      allPreviews.forEach((preview) => {
        const scroller = preview.querySelector("[data-scroll-memory='user-preview'], .overflow-y-scroll");
        const prevScroll = scroller ? scroller.scrollTop : 0;

        const allRrssLinks = Array.from(preview.querySelectorAll('[data-link-id^="rrss_"]'));
        const wrappers = new Set(allRrssLinks.map((l) => l.parentElement).filter(Boolean));

        wrappers.forEach((rrssWrapper) => {
          const previewLinks = Array.from(rrssWrapper.querySelectorAll('[data-link-id^="rrss_"]'));
          const targetNodes = [];
          draft.rrss.forEach((r) => {
            const name = (Array.isArray(r) ? r[0] : r?.name || "").trim().toLowerCase();
            if (!name) return;
            const match = previewLinks.find((link) => {
              const linkId = (link.getAttribute("data-link-id") || "").toLowerCase();
              return linkId === `rrss_${name}`;
            });
            if (match) targetNodes.push(match);
          });

          targetNodes.forEach((node, i) => {
            if (rrssWrapper.children[i] !== node) {
              rrssWrapper.insertBefore(node, rrssWrapper.children[i] || null);
            }
          });
        });

        if (scroller && prevScroll > 0) {
          scroller.scrollTop = prevScroll;
        }
      });

      setDraftField("is_reorder", "true");
      notifyDraftState();
    }
  });

  window.addEventListener("pageshow", initRestoration);
}



