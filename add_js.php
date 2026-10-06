<?php
$c = file_get_contents("App/Public/Js/designDraftManager.js");
$js = <<<JS

  // Lógica para añadir nuevos bloques usando <template> en lugar de submit
  document.addEventListener("click", function(e) {
    const btn = e.target.closest("button[data-action=\"add-block-template\"]");
    if (btn) {
      e.preventDefault();
      const type = btn.getAttribute("data-type");
      if (!type) return;

      const list = document.getElementById("sortable-content-list");
      if (!list) return;

      // Calcular nuevo índice (usando la cantidad actual + timestamp para evitar colisiones)
      const items = list.querySelectorAll(".sortable-item");
      const newIndex = items.length + "_" + Date.now();

      // 1. Obtener el template del formulario (Left side)
      const tplForm = document.getElementById("tpl_block_" + type);
      if (tplForm) {
        let htmlForm = tplForm.innerHTML.replace(/{{INDEX}}/g, newIndex);
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = htmlForm;
        const newNode = tempDiv.firstElementChild;
        if (newNode) {
          list.insertBefore(newNode, list.firstChild);
          newNode.classList.remove("is-collapsed");
          newNode.classList.add("is-open");
        }
      }

      // 2. Obtener el template del Preview (Right side)
      const tplPreview = document.getElementById("tpl_preview_" + type);
      const widgetContainer = document.getElementById("preview-widget-container");
      if (tplPreview && widgetContainer) {
        let htmlPreview = tplPreview.innerHTML.replace(/{{INDEX}}/g, newIndex);
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = htmlPreview;
        const newNodePreview = tempDiv.firstElementChild;
        if (newNodePreview) {
          widgetContainer.insertBefore(newNodePreview, widgetContainer.firstChild);
        }
      }

      // Disparar eventos para que el sistema sepa que hubo cambios
      document.dispatchEvent(new CustomEvent("designDraftStateChanged", { detail: { hasDraft: true } }));
      
      // Forzamos un "setDraftField" virtual para el botón de guardar y el manager
      setDraftField("added_blocks_" + newIndex, type);
      
      setTimeout(() => {
        if (window.__saveButtonController && typeof window.__saveButtonController.enableSaveButton === "function") {
          window.__saveButtonController.enableSaveButton();
        }
        // Inicializar plugins si existen (ej. colorpicker)
        if (typeof initColorPickers === "function") initColorPickers();
      }, 50);
    }
  });

JS;

$c = str_replace("  window.addEventListener(\"pageshow\", initRestoration);\n}", $js . "  window.addEventListener(\"pageshow\", initRestoration);\n}", $c);
file_put_contents("App/Public/Js/designDraftManager.js", $c);
?>
