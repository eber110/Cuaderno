/**
 * Componente Save Button Controller.
 * 
 * Controla la visibilidad del botón "Guardar" según la sección activa del sidebar (data-savable="true" / "false"),
 * gestiona su estado habilitado/desactivado y aplica una animación de pulso de entrada (one-shot) con delay suave tras la carga.
 */
export function saveButtonController() {
  const saveContainer = document.getElementById("save-btn-container");
  const saveBtn = document.getElementById("save-btn");
  if (!saveContainer || !saveBtn) return;

  /**
   * Actualiza la visibilidad del contenedor del botón según el botón remoto activo
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
   * Habilita el botón guardar cuando hay cambios sin guardar
   */
  function enableSaveButton() {
    if (saveBtn.classList.contains("disabled-save-btn")) {
      saveBtn.classList.remove("disabled-save-btn", "texto");
      saveBtn.classList.add("pointer", "back-save-panel", "textw", "bold500");
      saveBtn.removeAttribute("tabindex");
      saveBtn.removeAttribute("aria-disabled");

      if (!saveContainer.classList.contains("hidden")) {
        triggerPulseAnimation();
      }
    }
  }

  // Escuchar clic en los botones remotos del sidebar
  document.addEventListener("click", (e) => {
    const remoteBtn = e.target.closest(".remote-btn");
    if (remoteBtn) {
      updateSaveButtonVisibility(remoteBtn);
    }
  });

  // Habilitar el botón Guardar cuando se modifica cualquier formulario editable del dashboard
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

  // Sincronización síncrona inmediata al arrancar el controlador
  const activeBtn = getActiveRemoteBtn();
  if (activeBtn) {
    updateSaveButtonVisibility(activeBtn);
  }

  const hasCustom = saveContainer.dataset.hasCustom === "true";
  if (hasCustom && !saveBtn.classList.contains("disabled-save-btn")) {
    if (!saveContainer.classList.contains("hidden")) {
      triggerPulseAnimation();
    }
  }

  // Interceptar clic en el botón Guardar para publicar con fetch sin recargar la página
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
        // Deshabilitar el botón Guardar tras publicar los cambios
        saveBtn.classList.add("disabled-save-btn", "texto");
        saveBtn.classList.remove("pointer", "back-save-panel", "textw", "bold500", "pulse-once");
        saveBtn.setAttribute("tabindex", "-1");
        saveBtn.setAttribute("aria-disabled", "true");
        saveContainer.dataset.hasCustom = "false";

        // Actualizar la vista previa con los datos oficiales publicados
        if (data.html) {
          const previewContainers = document.querySelectorAll(".user-profile-preview");
          previewContainers.forEach((container) => {
            const temp = document.createElement("div");
            temp.innerHTML = data.html.trim();
            const targetPreview = temp.querySelector(".user-profile-preview") || temp.firstElementChild;
            if (targetPreview && container.parentNode) {
              container.parentNode.replaceChild(targetPreview, container);
            } else {
              container.innerHTML = data.html;
            }
          });

          document.dispatchEvent(new CustomEvent("previewUpdated", { detail: data }));
        }
      }
    } catch (err) {
      console.error("Error al publicar el diseño con fetch:", err);
    }
  });
}
