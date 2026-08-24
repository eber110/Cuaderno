/**
 * Componente sortableContent.
 * 
 * Permite reordenar tarjetas (.sortable-item) mediante arrastrar y soltar (Drag and Drop)
 * dentro de los contenedores #sortable-content-list, #sortable-rrss-list o .sortable-container.
 * Al soltar la tarjeta en una nueva posición, re-indexa automáticamente los atributos
 * `name`, `id` y `for` de los campos e impulsa el auto-submit del formulario.
 * 
 * Optimizado para evitar Layout Thrashing y congelamiento del navegador en producción.
 */
export function sortableContent() {
  function initAllContainers() {
    const containers = document.querySelectorAll("#sortable-content-list, #sortable-rrss-list, .sortable-container");
    if (!containers.length) return;

    containers.forEach((container) => {
      if (container.dataset.sortableBound) return;
      container.dataset.sortableBound = "true";
      initSortable(container);
    });
  }

  initAllContainers();

  if (!window.__sortableContentInitialized) {
    window.__sortableContentInitialized = true;
    
    // Auto-recuperación cuando se actualiza la vista previa por Fetch
    document.addEventListener("previewUpdated", () => {
      initAllContainers();
    });

    // Auto-recuperación si la página se restaura desde la caché de navegación (BFCache)
    window.addEventListener("pageshow", () => {
      initAllContainers();
    });

    // Limpieza global de seguridad si el arrastre se interrumpe de forma externa
    window.addEventListener("dragend", () => {
      document.querySelectorAll(".sortable-item.dragging").forEach((el) => {
        el.classList.remove("dragging");
        el.style.opacity = "1";
      });
    });
  }

  function initSortable(container) {
    let draggedItem = null;
    let initialIndex = null;

    // Iniciar arrastre
    container.addEventListener("dragstart", (e) => {
      // Ignorar si el arrastre se inició dentro de un campo interactivo o botón
      if (e.target.closest("input, textarea, select, button, label, .modal-btn, .content-modal-menu, .checkbox-switch, a")) {
        e.preventDefault();
        return;
      }

      const item = e.target.closest(".sortable-item");
      if (!item) return;

      // Limpiar selecciones de texto activas en el navegador para evitar bloqueos del cursor
      if (window.getSelection) {
        window.getSelection().removeAllRanges();
      }

      draggedItem = item;
      const allItems = [...container.querySelectorAll(".sortable-item")];
      initialIndex = allItems.indexOf(item);

      item.classList.add("dragging");
      item.style.opacity = "0.4";
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = "move";
        e.dataTransfer.setData("text/plain", ""); // Compatibilidad Firefox
      }
    });

    // Prevenir comportamiento por defecto en drop para evitar navegación accidental
    container.addEventListener("drop", (e) => {
      e.preventDefault();
    });

    // Finalizar arrastre
    container.addEventListener("dragend", (e) => {
      const item = e.target.closest(".sortable-item") || draggedItem;
      if (item) {
        item.classList.remove("dragging");
        item.style.opacity = "1";
      }

      if (draggedItem) {
        const allItems = [...container.querySelectorAll(".sortable-item")];
        const newIndex = allItems.indexOf(draggedItem);

        // Solo re-indexar y enviar si la posición realmente cambió
        if (initialIndex !== null && newIndex !== -1 && initialIndex !== newIndex) {
          updateIndicesAndSubmit(container);
        }
      }

      draggedItem = null;
      initialIndex = null;
    });

    // Movimiento al arrastrar por encima de otros elementos (con protección anti layout thrashing)
    container.addEventListener("dragover", (e) => {
      e.preventDefault();
      if (e.dataTransfer) {
        e.dataTransfer.dropEffect = "move";
      }

      if (!draggedItem) return;

      const afterElement = getDragAfterElement(container, e.clientY);

      // Mutar el DOM SOLO si la posición de destino es distinta a la actual
      if (afterElement == null) {
        if (container.lastElementChild !== draggedItem) {
          container.appendChild(draggedItem);
        }
      } else {
        if (draggedItem.nextElementSibling !== afterElement && draggedItem !== afterElement) {
          container.insertBefore(draggedItem, afterElement);
        }
      }
    });
  }

  // Calcula el elemento que se encuentra justo debajo de la posición del cursor Y
  function getDragAfterElement(container, y) {
    const draggableElements = [
      ...container.querySelectorAll(".sortable-item:not(.dragging)")
    ];

    let closest = { offset: Number.NEGATIVE_INFINITY, element: null };

    for (let i = 0; i < draggableElements.length; i++) {
      const child = draggableElements[i];
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) {
        closest = { offset: offset, element: child };
      }
    }

    return closest.element;
  }

  // Re-indexa los nombres de los inputs (`content[index][...]` o `rrss[index][...]`) según el nuevo orden del DOM
  function updateIndicesAndSubmit(container) {
    const items = container.querySelectorAll(".sortable-item");

    items.forEach((item, index) => {
      // Actualizar ID del contenedor de la tarjeta
      if (item.id && item.id.startsWith("content-item-")) {
        item.id = `content-item-${index}`;
      }
      if (item.id && item.id.startsWith("rrss-item-")) {
        item.id = `rrss-item-${index}`;
      }

      // Actualizar la etiqueta visible preservando el título del usuario
      const label = item.querySelector(".item-title-label");
      if (label) {
        const titleInput = item.querySelector('input[name*="[title]"]');
        if (titleInput) {
          const isProduct = item.querySelector('input[name*="[type]"]')?.value === "product";
          const currentTitle = titleInput.value.trim();
          const prefix = isProduct ? "Producto" : "Enlace";
          label.textContent = currentTitle ? `${prefix} - ${currentTitle}` : `${prefix} #${index + 1}`;
        }
      }

      // Re-indexar los campos input dentro de la tarjeta
      const inputs = item.querySelectorAll("input, select, textarea, label");
      inputs.forEach((element) => {
        const oldName = element.getAttribute("name");
        if (oldName && oldName.startsWith("content[")) {
          const newName = oldName.replace(/^content\[\d+\]/, `content[${index}]`);
          element.setAttribute("name", newName);
        }
        if (oldName && oldName.startsWith("rrss[")) {
          const newName = oldName.replace(/^rrss\[\d+\]/, `rrss[${index}]`);
          element.setAttribute("name", newName);
        }
        if (oldName && oldName.startsWith("content_img_")) {
          element.setAttribute("name", `content_img_${index}`);
        }

        // Re-indexar IDs y atributos for de eliminación para modales
        const oldId = element.getAttribute("id");
        if (oldId && oldId.startsWith("delete-link-")) {
          element.setAttribute("id", `delete-link-${index}`);
        }
        if (oldId && oldId.startsWith("delete-rrss-")) {
          element.setAttribute("id", `delete-rrss-${index}`);
        }

        const oldFor = element.getAttribute("for");
        if (oldFor && oldFor.startsWith("delete-link-")) {
          element.setAttribute("for", `delete-link-${index}`);
        }
        if (oldFor && oldFor.startsWith("delete-rrss-")) {
          element.setAttribute("for", `delete-rrss-${index}`);
        }
      });
    });

    // Disparar auto-submit
    const form = container.closest("form.auto-submit");
    if (form) {
      if (typeof form.requestSubmit === "function") {
        form.requestSubmit();
      } else {
        form.submit();
      }
    }
  }
}

