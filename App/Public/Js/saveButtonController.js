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
   * Obtiene el botón remoto activo actual soportando clases de estado activo personalizadas (ej: back-item-active)
   */
  function getActiveRemoteBtn() {
    return document.querySelector(".remote-btn.back-item-active, .remote-btn.active, .remote-btn[class*='active']");
  }

  /**
   * Ejecuta la animación de pulso de entrada una sola vez (one-shot)
   */
  function triggerPulseAnimation() {
    saveBtn.classList.remove("pulse-once");
    // Forzar reflow para reiniciar la animación limpiamente
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

  // Sincronizar visibilidad tras la carga inicial esperando que verticalMenu restaure el estado desde localStorage
  setTimeout(() => {
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
  }, 100);
}
