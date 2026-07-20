/**
 * Componente sortableContent.
 * 
 * Permite reordenar tarjetas (.sortable-item) mediante arrastrar y soltar (Drag and Drop)
 * dentro del contenedor #sortable-content-list. Al soltar la tarjeta, re-indexa
 * automáticamente los atributos `name` de los campos e impulsa el auto-submit del formulario.
 */
document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("sortable-content-list");
  if (!container) return;

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

  // Re-indexa los nombres de los inputs (`content[index][...]`) según el nuevo orden del DOM
  function updateIndicesAndSubmit(container) {
    const items = container.querySelectorAll(".sortable-item");

    items.forEach((item, index) => {
      // Actualizar la etiqueta visible "Enlace #X" / "Producto #X"
      const label = item.querySelector(".item-title-label");
      if (label) {
        const isProduct = label.textContent.includes("Producto");
        label.textContent = isProduct ? `Producto #${index + 1}` : `Enlace #${index + 1}`;
      }

      // Re-indexar los campos input dentro de la tarjeta
      const inputs = item.querySelectorAll("input, select, textarea");
      inputs.forEach((input) => {
        const oldName = input.getAttribute("name");
        if (oldName && oldName.startsWith("content[")) {
          const newName = oldName.replace(/^content\[\d+\]/, `content[${index}]`);
          input.setAttribute("name", newName);
        }
        if (oldName && oldName.startsWith("content_img_")) {
          input.setAttribute("name", `content_img_${index}`);
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
});
