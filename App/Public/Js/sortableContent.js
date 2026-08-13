/**
 * Componente sortableContent.
 * 
 * Permite reordenar tarjetas (.sortable-item) mediante arrastrar y soltar (Drag and Drop)
 * dentro del contenedor #sortable-content-list o #sortable-rrss-list. Al soltar la tarjeta, re-indexa
 * automáticamente los atributos `name` de los campos e impulsa el auto-submit del formulario.
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
  document.addEventListener("previewUpdated", initAllContainers);

  function initSortable(container) {
    let draggedItem = null;

    // Iniciar arrastre
    container.addEventListener("dragstart", (e) => {
      const item = e.target.closest(".sortable-item");
      if (!item) return;

      draggedItem = item;
      item.classList.add("dragging");
      item.style.opacity = "0.4";
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = "move";
        e.dataTransfer.setData("text/plain", ""); // Compatibilidad Firefox
      }
    });

    // Finalizar arrastre
    container.addEventListener("dragend", (e) => {
      const item = e.target.closest(".sortable-item");
      if (item) {
        item.classList.remove("dragging");
        item.style.opacity = "1";
      }
      draggedItem = null;

      // Actualizar los índices de los campos y enviar formulario mediante auto-submit
      updateIndicesAndSubmit(container);
    });

    // Movimiento al arrastrar por encima de otros elementos
    container.addEventListener("dragover", (e) => {
      e.preventDefault();
      if (e.dataTransfer) {
        e.dataTransfer.dropEffect = "move";
      }

      if (!draggedItem) return;

      const afterElement = getDragAfterElement(container, e.clientY);
      if (afterElement == null) {
        container.appendChild(draggedItem);
      } else {
        container.insertBefore(draggedItem, afterElement);
      }
    });
  }

  // Calcula el elemento que se encuentra justo debajo de la posición del cursor Y
  function getDragAfterElement(container, y) {
    const draggableElements = [
      ...container.querySelectorAll(".sortable-item:not(.dragging)")
    ];

    return draggableElements.reduce(
      (closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
          return { offset: offset, element: child };
        } else {
          return closest;
        }
      },
      { offset: Number.NEGATIVE_INFINITY }
    ).element;
  }

  // Re-indexa los nombres de los inputs (`content[index][...]` o `rrss[index][...]`) según el nuevo orden del DOM
  function updateIndicesAndSubmit(container) {
    const items = container.querySelectorAll(".sortable-item");

    items.forEach((item, index) => {
      // Actualizar la etiqueta visible si es Enlace / Producto
      const label = item.querySelector(".item-title-label");
      if (label && (label.textContent.includes("Producto") || label.textContent.includes("Enlace"))) {
        const isProduct = label.textContent.includes("Producto");
        label.textContent = isProduct ? `Producto #${index + 1}` : `Enlace #${index + 1}`;
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
