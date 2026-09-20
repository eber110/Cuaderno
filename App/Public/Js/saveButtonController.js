/**
 * Componente Save Button Controller.
 * 
 * Controla la visibilidad del contenedor de acciones según la sección activa del sidebar (data-savable="true" / "false"),
 * gestiona el estado habilitado/desactivado de los botones "Guardar" y "Descartar",
 * procesa las publicaciones y reversiones mediante Fetch con actualización de preview y formularios en tiempo real.
 */
export function saveButtonController() {
  const saveContainer = document.getElementById("save-btn-container");
  const saveBtn = document.getElementById("save-btn");
  const discardBtn = document.getElementById("discard-btn");
  if (!saveContainer || !saveBtn) return;

  /**
   * Actualiza la visibilidad del contenedor de acciones según el botón remoto activo del menú
   */
  function updateSaveButtonVisibility(remoteBtn) {
    if (!remoteBtn) return;

    const isSavable = remoteBtn.dataset.savable === "true";

    if (isSavable) {
      const wasHidden = saveContainer.classList.contains("hidden");
      saveContainer.classList.remove("hidden");

      // Si pasa de oculto a visible y está habilitado, aplicar animación de pulso de entrada
      if (wasHidden && !saveBtn.classList.contains("disabled-save-btn")) {
        triggerPulseAnimation();
      }
    } else {
      saveContainer.classList.add("hidden");
    }
  }

  /**
   * Obtiene el botón remoto activo actual soportando estado guardado en localStorage y clases de estado activo
   */
  function getActiveRemoteBtn() {
    // 1. Priorizar búsqueda según estado guardado en localStorage
    const storageKey = `vertical_menu_active_${window.location.pathname}_default`;
    const savedStateStr = localStorage.getItem(storageKey);
    if (savedStateStr) {
      try {
        const savedState = JSON.parse(savedStateStr);
        if (savedState.remote) {
          const targetBtn = document.querySelector(`.remote-btn[data-remote="${savedState.remote}"]`);
          if (targetBtn) return targetBtn;
        }
      } catch (e) {}
    }

    // 2. Fallback a clases activas en el DOM
    return document.querySelector(".remote-btn.back-item-active, .remote-btn.active, .remote-btn[class*='active']");
  }

  /**
   * Ejecuta la animación de pulso de entrada una sola vez (one-shot)
   */
  function triggerPulseAnimation() {
    saveBtn.classList.remove("pulse-once");
    void saveBtn.offsetWidth;
    saveBtn.classList.add("pulse-once");
  }

  /**
   * Muestra el estado activo de "Guardando..." con animación de spinner
   */
  function setSavingState() {
    saveContainer.dataset.hasCustom = "true";
    const activeBtn = getActiveRemoteBtn();
    if (activeBtn && activeBtn.dataset.savable === "true") {
      saveContainer.classList.remove("hidden");
    }

    saveBtn.classList.remove("disabled-save-btn", "texto", "pulse-once", "back-card-graphic");
    saveBtn.classList.add("pointer", "back-card-graphic-red", "shadow-card-graphic", "hover-scale-soft", "textw", "bold500", "border-none", "save-btn-saving");
    saveBtn.removeAttribute("tabindex");
    saveBtn.removeAttribute("aria-disabled");
    saveBtn.innerHTML = '<span class="save-btn-spinner"></span><span class="save-btn-text">Guardando...</span>';

    if (discardBtn) {
      discardBtn.classList.remove("hidden", "disabled-save-btn");
      discardBtn.classList.add("pointer", "bold500", "texto", "back-card-graphic", "shadow-card-graphic", "hover-scale-soft", "border-none");
      discardBtn.removeAttribute("tabindex");
      discardBtn.removeAttribute("aria-disabled");
      discardBtn.textContent = "Descartar";
    }
  }

  /**
   * Habilita el botón Guardar y muestra el botón Descartar cuando hay cambios pendientes
   */
  function enableSaveButton() {
    saveContainer.dataset.hasCustom = "true";

    const wasSaving = saveBtn.classList.contains("save-btn-saving");
    const wasDisabled = saveBtn.classList.contains("disabled-save-btn");

    saveBtn.classList.remove("disabled-save-btn", "texto", "save-btn-saving", "back-card-graphic");
    saveBtn.classList.add("pointer", "back-card-graphic-red", "shadow-card-graphic", "hover-scale-soft", "textw", "bold500", "border-none");
    saveBtn.removeAttribute("tabindex");
    saveBtn.removeAttribute("aria-disabled");
    saveBtn.innerHTML = "Guardar";

    if (!saveContainer.classList.contains("hidden") && (wasSaving || wasDisabled)) {
      triggerPulseAnimation();
    }

    if (discardBtn) {
      discardBtn.classList.remove("hidden", "disabled-save-btn");
      discardBtn.classList.add("pointer", "bold500", "texto", "back-card-graphic", "shadow-card-graphic", "hover-scale-soft", "border-none");
      discardBtn.removeAttribute("tabindex");
      discardBtn.removeAttribute("aria-disabled");
      discardBtn.textContent = "Descartar";
    }
  }

  /**
   * Deshabilita el botón Guardar y oculta el botón Descartar cuando no hay cambios pendientes
   */
  function disableSaveButton() {
    saveContainer.dataset.hasCustom = "false";

    saveBtn.classList.remove("pointer", "back-card-graphic-red", "textw", "bold500", "pulse-once", "save-btn-saving", "hover-scale-soft");
    saveBtn.classList.add("back-card-graphic", "shadow-card-graphic", "disabled-save-btn", "texto", "border-none");
    saveBtn.setAttribute("tabindex", "-1");
    saveBtn.setAttribute("aria-disabled", "true");
    saveBtn.innerHTML = "Guardar";

    if (discardBtn) {
      discardBtn.classList.remove("pointer", "bold500", "disabled-save-btn", "hover-scale-soft");
      discardBtn.classList.add("hidden", "back-card-graphic", "shadow-card-graphic", "border-none", "texto");
      discardBtn.setAttribute("tabindex", "-1");
      discardBtn.setAttribute("aria-disabled", "true");
      discardBtn.textContent = "Descartar";
    }
  }

  /**
   * Actualiza el HTML de la vista previa (.user-profile-preview) sin recargar la página
   */
  function updatePreviewHtml(html) {
    if (!html) return;
    const previewContainers = document.querySelectorAll(".user-profile-preview");
    previewContainers.forEach((container) => {
      const temp = document.createElement("div");
      temp.innerHTML = html.trim();
      const targetPreview = temp.querySelector(".user-profile-preview") || temp.firstElementChild;
      if (!targetPreview) return;

      // Preservar la posición de scroll interna antes del reemplazo en el DOM
      const currentScrollEl = container.querySelector('.overflow-y-scroll') || container;
      const savedScrollTop = currentScrollEl ? currentScrollEl.scrollTop : 0;

      const currentVideo = container.querySelector("video.back-video-bg");
      const newVideo = targetPreview.querySelector("video.back-video-bg");

      const currentSrc = currentVideo?.querySelector("source")?.getAttribute("src") || currentVideo?.getAttribute("src");
      const newSrc = newVideo?.querySelector("source")?.getAttribute("src") || newVideo?.getAttribute("src");

      // Si el video de fondo es idéntico, conservar el elemento <video> existente para evitar re-descargar los megabytes
      if (currentVideo && newVideo && currentSrc && newSrc && currentSrc === newSrc) {
        newVideo.replaceWith(currentVideo);
      }

      if (container.parentNode) {
        container.parentNode.replaceChild(targetPreview, container);
      } else {
        container.innerHTML = html;
      }

      // Restaurar inmediatamente el scroll de forma instantánea sin animación
      const newScrollEl = targetPreview.querySelector('.overflow-y-scroll') || targetPreview;
      if (newScrollEl && savedScrollTop > 0) {
        newScrollEl.style.setProperty('scroll-behavior', 'auto', 'important');
        newScrollEl.scrollTop = savedScrollTop;
        try {
          sessionStorage.setItem(`cuaderno_scroll_user-preview_${window.location.pathname}`, String(savedScrollTop));
        } catch (e) {}
      }
    });
  }

  /**
   * Restaura los formularios del contenedor remoto con el HTML actualizado
   */
  function restoreFormHtml(formHtml) {
    if (!formHtml) return;
    const remoteContainer = document.querySelector(".remote-container");
    if (!remoteContainer) return;

    const temp = document.createElement("div");
    temp.innerHTML = formHtml.trim();
    const newContainer = temp.querySelector(".remote-container") || temp.firstElementChild;
    if (!newContainer) return;

    // Preservar cuál sección remota estaba activa y su scroll antes de reemplazar
    const activeContent = remoteContainer.querySelector(".remote-content.active");
    const activeId = activeContent ? activeContent.id : null;
    const savedFormScrollTop = activeContent ? activeContent.scrollTop : 0;

    // Capturar bloques de contenido abiertos para no colapsarlos
    const openBlockIds = new Set();
    const storedActiveId = sessionStorage.getItem("active_content_block_id");
    if (storedActiveId) openBlockIds.add(storedActiveId);
    remoteContainer.querySelectorAll(".sortable-item.content-block.is-open").forEach((el) => {
      if (el.id) openBlockIds.add(el.id);
    });

    remoteContainer.innerHTML = newContainer.innerHTML;

    if (activeId) {
      const contents = remoteContainer.querySelectorAll(".remote-content");
      contents.forEach((c) => {
        if (c.id === activeId) {
          c.classList.remove("hidden");
          c.classList.add("active");
          if (savedFormScrollTop > 0) {
            c.style.setProperty('scroll-behavior', 'auto', 'important');
            c.scrollTop = savedFormScrollTop;
          }
        } else {
          c.classList.remove("active");
          c.classList.add("hidden");
        }
      });
    }

    // Restaurar bloques de contenido que estaban expandidos
    openBlockIds.forEach((id) => {
      const item = remoteContainer.querySelector("#" + CSS.escape(id));
      if (item) {
        item.classList.remove("is-collapsed");
        item.classList.add("is-open");
        const body = item.querySelector(".content-item-body");
        if (body) {
          body.style.display = "flex";
          body.style.height = "auto";
          body.style.opacity = "1";
          body.style.overflow = "visible";
        }
      }
    });

    // Inicializar inmediatamente switches y selectores de color en el HTML restaurado
    if (window.__formComponents) {
      window.__formComponents.initCheckboxSwitches?.();
      window.__formComponents.styleColorPickers?.();
    }

    document.dispatchEvent(new CustomEvent("remoteContentUpdated", { detail: { formHtml } }));
  }

  // Escuchar clic en los botones remotos del sidebar
  document.addEventListener("click", (e) => {
    const remoteBtn = e.target.closest(".remote-btn");
    if (remoteBtn) {
      updateSaveButtonVisibility(remoteBtn);
    }
  });

  // Al detectar cualquier interacción de edición o color, habilitar el botón de guardar
  document.addEventListener("input", (e) => {
    if (e.target.closest(".remote-container, .custom-color-picker-popover") || e.target.classList.contains("color-picker") || e.target.type === "color") {
      enableSaveButton();
    }
  });

  document.addEventListener("change", (e) => {
    if (e.target.closest(".remote-container, .custom-color-picker-popover") || e.target.classList.contains("color-picker") || e.target.type === "color") {
      enableSaveButton();
    }
  });

  // Notificación de designDraftManager cuando cambia el estado del borrador local
  document.addEventListener("designDraftStateChanged", (e) => {
    if (isPublishing) return;
    if (e.detail && e.detail.hasDraft) {
      enableSaveButton();
    } else {
      const hasCustomServer = saveContainer.dataset.hasCustom === "true";
      if (!hasCustomServer) {
        disableSaveButton();
      }
    }
  });

  // Notificación de descarte del diseño
  document.addEventListener("designDraftDiscarded", () => {
    if (isPublishing) return;
    saveContainer.dataset.hasCustom = "false";
    disableSaveButton();
  });

  // Compatibilidad con eventos personalizados
  document.addEventListener("draftSaving", () => {
    setSavingState();
  });

  document.addEventListener("draftSaved", (e) => {
    if (isPublishing) return;
    if (e.detail && typeof e.detail.hasCustom === "boolean") {
      saveContainer.dataset.hasCustom = e.detail.hasCustom ? "true" : "false";
      if (e.detail.hasCustom) {
        enableSaveButton();
      } else {
        disableSaveButton();
      }
    } else {
      enableSaveButton();
    }
  });

  document.addEventListener("draftError", () => {
    if (isPublishing) return;
    enableSaveButton();
  });

  document.addEventListener("previewUpdated", (e) => {
    if (isPublishing) return;

    if (e.detail && typeof e.detail.hasCustom === "boolean") {
      saveContainer.dataset.hasCustom = e.detail.hasCustom ? "true" : "false";
      if (e.detail.hasCustom) {
        enableSaveButton();
      } else {
        disableSaveButton();
      }
    }

    if (e.detail && e.detail.sidebarStatusHtml) {
      document.querySelectorAll(".sidebar-profile-status").forEach((el) => {
        el.innerHTML = e.detail.sidebarStatusHtml;
      });
    }
  });

  // Sincronización síncrona inmediata al arrancar el controlador
  const activeBtn = getActiveRemoteBtn();
  if (activeBtn) {
    updateSaveButtonVisibility(activeBtn);
  }

  const hasCustom = saveContainer.dataset.hasCustom === "true" || (window.__designDraftManager && window.__designDraftManager.hasDraft && window.__designDraftManager.hasDraft());
  if (hasCustom) {
    enableSaveButton();
  } else {
    disableSaveButton();
  }

  let isPublishing = false;

  // Interceptar clic en el botón Guardar para publicar cambios en lote con fetch
  saveBtn.addEventListener("click", async (e) => {
    e.preventDefault();

    if (isPublishing) return;

    const isSaveDisabled = saveBtn.classList.contains("disabled-save-btn");
    const hasLocalDraft = window.__designDraftManager && typeof window.__designDraftManager.hasDraft === "function" ? window.__designDraftManager.hasDraft() : false;
    const hasCustomServer = saveContainer.dataset.hasCustom === "true";

    // Si está completamente deshabilitado y no hay cambios, ignorar
    if (isSaveDisabled && !hasLocalDraft && !hasCustomServer) {
      return;
    }

    isPublishing = true;
    setSavingState();

    try {
      if (window.__designDraftManager && typeof window.__designDraftManager.saveDraft === "function") {
        await window.__designDraftManager.saveDraft();
      } else {
        const targetUrl = saveBtn.getAttribute("href") || saveBtn.dataset.href;
        if (targetUrl) {
          const response = await fetch(targetUrl, {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" }
          });
          if (!response.ok) throw new Error("Error en servidor al guardar.");
          const data = await response.json();
          if (data && data.sidebarStatusHtml) {
            document.querySelectorAll(".sidebar-profile-status").forEach((el) => {
              el.innerHTML = data.sidebarStatusHtml;
            });
          }
        }
      }

      saveBtn.classList.remove("save-btn-saving");
      saveBtn.innerHTML = "¡Guardado!";

      setTimeout(() => {
        disableSaveButton();
      }, 600);
    } catch (err) {
      console.error("Error al publicar el diseño con fetch:", err);
      enableSaveButton();
    } finally {
      isPublishing = false;
    }
  });

  // Interceptar clic en el botón Descartar para revertir borrador con fetch
  if (discardBtn) {
    discardBtn.addEventListener("click", async (e) => {
      e.preventDefault();

      if (discardBtn.classList.contains("hidden") || discardBtn.getAttribute("aria-disabled") === "true") {
        return;
      }

      if (isPublishing) return;

      isPublishing = true;
      discardBtn.setAttribute("aria-disabled", "true");
      discardBtn.classList.add("disabled-save-btn");
      discardBtn.classList.remove("pointer");
      discardBtn.innerHTML = '<span class="save-btn-spinner save-btn-spinner-dark"></span><span class="save-btn-text">Descartando...</span>';

      try {
        if (window.__designDraftManager && typeof window.__designDraftManager.discardDraft === "function") {
          await window.__designDraftManager.discardDraft();
        } else {
          const targetUrl = discardBtn.getAttribute("href") || discardBtn.dataset.href;
          if (targetUrl) {
            const response = await fetch(targetUrl, {
              method: "POST",
              headers: { "X-Requested-With": "XMLHttpRequest" }
            });
            if (!response.ok) throw new Error("Error al descartar.");
            const data = await response.json();
            if (data && data.html) updatePreviewHtml(data.html);
            if (data && data.formHtml) restoreFormHtml(data.formHtml);
          }
        }
        disableSaveButton();
      } catch (err) {
        console.error("Error al descartar el diseño con fetch:", err);
        discardBtn.textContent = "Descartar";
        discardBtn.removeAttribute("aria-disabled");
        discardBtn.classList.remove("disabled-save-btn");
        discardBtn.classList.add("pointer", "bold500", "texto", "back-card-graphic", "shadow-card-graphic", "hover-scale-soft", "border-none");
      } finally {
        isPublishing = false;
      }
    });
  }

  /**
   * Carga bajo demanda el panel de estadísticas si aún no ha sido cargado
   */
  async function loadStatisticsIfNeeded() {
    const statsRemote = document.getElementById("statistics-remote");
    if (!statsRemote || statsRemote.dataset.loaded === "true" || statsRemote.dataset.loading === "true") return;

    statsRemote.dataset.loading = "true";
    const pathParts = window.location.pathname.split("/").filter(Boolean);
    const user = pathParts[1] || "";

    try {
      const res = await fetch(`/panel/${user}/estadisticas`, {
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          "Accept": "application/json"
        }
      });
      const data = await res.json();
      if (data.success && data.statsHtml) {
        const wrapper = document.getElementById("statistics-remote-wrapper") || statsRemote;
        wrapper.innerHTML = data.statsHtml;
        statsRemote.dataset.loaded = "true";
      }
    } catch (err) {
      console.error("Error al cargar estadísticas bajo demanda:", err);
    } finally {
      statsRemote.dataset.loading = "false";
    }
  }

  // Detectar clic en el botón de estadísticas para carga bajo demanda
  document.addEventListener("click", (e) => {
    const btn = e.target.closest('.remote-btn[data-remote="statistics-remote"]');
    if (btn) {
      loadStatisticsIfNeeded();
    }
  });

  // Si la pestaña activa al cargar es estadísticas, solicitar los datos
  const currentActiveBtn = getActiveRemoteBtn();
  if (currentActiveBtn && currentActiveBtn.dataset.remote === "statistics-remote") {
    loadStatisticsIfNeeded();
  }
}
