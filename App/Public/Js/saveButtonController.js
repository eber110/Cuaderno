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
   * Habilita el botón Guardar y muestra el botón Descartar cuando hay cambios pendientes
   */
  function enableSaveButton() {
    saveContainer.dataset.hasCustom = "true";

    if (saveBtn.classList.contains("disabled-save-btn")) {
      saveBtn.classList.remove("disabled-save-btn", "texto");
      saveBtn.classList.add("pointer", "back-save-panel", "textw", "bold500");
      saveBtn.removeAttribute("tabindex");
      saveBtn.removeAttribute("aria-disabled");

      if (!saveContainer.classList.contains("hidden")) {
        triggerPulseAnimation();
      }
    }

    if (discardBtn) {
      discardBtn.classList.remove("hidden");
      discardBtn.removeAttribute("tabindex");
      discardBtn.removeAttribute("aria-disabled");
    }
  }

  /**
   * Deshabilita el botón Guardar y oculta el botón Descartar cuando no hay cambios pendientes
   */
  function disableSaveButton() {
    saveContainer.dataset.hasCustom = "false";

    saveBtn.classList.add("disabled-save-btn", "texto");
    saveBtn.classList.remove("pointer", "back-save-panel", "textw", "bold500", "pulse-once");
    saveBtn.setAttribute("tabindex", "-1");
    saveBtn.setAttribute("aria-disabled", "true");

    if (discardBtn) {
      discardBtn.classList.add("hidden");
      discardBtn.setAttribute("tabindex", "-1");
      discardBtn.setAttribute("aria-disabled", "true");
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
      if (targetPreview && container.parentNode) {
        container.parentNode.replaceChild(targetPreview, container);
      } else {
        container.innerHTML = html;
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

    // Preservar cuál sección remota estaba activa antes de reemplazar
    const activeContent = remoteContainer.querySelector(".remote-content.active");
    const activeId = activeContent ? activeContent.id : null;

    remoteContainer.innerHTML = newContainer.innerHTML;

    if (activeId) {
      const contents = remoteContainer.querySelectorAll(".remote-content");
      contents.forEach((c) => {
        if (c.id === activeId) {
          c.classList.remove("hidden");
          c.classList.add("active");
        } else {
          c.classList.remove("active");
          c.classList.add("hidden");
        }
      });
    }
  }

  // Escuchar clic en los botones remotos del sidebar
  document.addEventListener("click", (e) => {
    const remoteBtn = e.target.closest(".remote-btn");
    if (remoteBtn) {
      updateSaveButtonVisibility(remoteBtn);
    }
  });

  // Habilitar botones cuando se modifica cualquier formulario editable del dashboard
  document.addEventListener("input", (e) => {
    if (e.target.closest("form.auto-submit, .remote-container")) {
      enableSaveButton();
    }
  });

  document.addEventListener("change", (e) => {
    if (e.target.closest("form.auto-submit, .remote-container")) {
      enableSaveButton();
    }
  });

  // Escuchar evento personalizado disparado por autoSubmitForm
  document.addEventListener("previewUpdated", (e) => {
    if (e.detail && typeof e.detail.hasCustom === "boolean") {
      if (e.detail.hasCustom) {
        enableSaveButton();
      } else {
        disableSaveButton();
      }
    }
  });

  // Sincronización síncrona inmediata al arrancar el controlador
  const activeBtn = getActiveRemoteBtn();
  if (activeBtn) {
    updateSaveButtonVisibility(activeBtn);
  }

  const hasCustom = saveContainer.dataset.hasCustom === "true";
  if (hasCustom) {
    enableSaveButton();
  } else {
    disableSaveButton();
  }

  // Interceptar clic en el botón Guardar para publicar cambios con fetch
  saveBtn.addEventListener("click", async (e) => {
    e.preventDefault();

    if (saveBtn.classList.contains("disabled-save-btn") || saveBtn.getAttribute("aria-disabled") === "true") {
      return;
    }

    const targetUrl = saveBtn.getAttribute("href") || saveBtn.dataset.href;
    if (!targetUrl) return;

    try {
      const response = await fetch(targetUrl, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      if (!response.ok) return;

      const data = await response.json();

      if (data && data.success) {
        disableSaveButton();

        if (data.html) {
          updatePreviewHtml(data.html);
        }

        document.dispatchEvent(new CustomEvent("previewUpdated", { detail: data }));
      }
    } catch (err) {
      console.error("Error al publicar el diseño con fetch:", err);
    }
  });

  // Interceptar clic en el botón Descartar para revertir borrador con fetch
  if (discardBtn) {
    discardBtn.addEventListener("click", async (e) => {
      e.preventDefault();

      if (discardBtn.classList.contains("hidden") || discardBtn.getAttribute("aria-disabled") === "true") {
        return;
      }

      const targetUrl = discardBtn.getAttribute("href") || discardBtn.dataset.href;
      if (!targetUrl) return;

      try {
        const response = await fetch(targetUrl, {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest"
          }
        });

        if (!response.ok) return;

        const data = await response.json();

        if (data && data.success) {
          disableSaveButton();

          if (data.html) {
            updatePreviewHtml(data.html);
          }

          if (data.formHtml) {
            restoreFormHtml(data.formHtml);
          }

          document.dispatchEvent(new CustomEvent("previewUpdated", { detail: data }));
        }
      } catch (err) {
        console.error("Error al descartar el diseño con fetch:", err);
      }
    });
  }
}
