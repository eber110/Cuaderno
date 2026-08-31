/**
 * Componente sortableContent.
 * 
 * Permite reordenar tarjetas (.sortable-item) mediante arrastrar y soltar (Drag and Drop)
 * dentro de los contenedores #sortable-content-list, #sortable-rrss-list o .sortable-container.
 * 
 * Gestiona además el colapso, expansión y foco automático de bloques en #sortable-content-list,
 * optimizando la vista para listas extensas de enlaces y productos.
 * 
 * @function sortableContent
 * @returns {void}
 */
export function sortableContent() {
  let isDragging = false;
  const hasGsap = typeof gsap !== "undefined";

  const animConfig = {
    duration: 0.3,
    ease: "power2.out",
    easeClose: "power2.in"
  };

  /**
   * Expande un bloque de contenido específico con animación y colapsa los demás.
   *
   * @param {HTMLElement} item Elemento .sortable-item a expandir
   * @param {boolean} focusTitle Si es true, enfoca el campo de título
   * @param {boolean} animate Si es true, ejecuta animación fluida
   */
  function expandContentBlock(item, focusTitle = false, animate = true) {
    if (!item) return;

    // Colapsar todos los demás bloques abiertos
    const container = item.closest("#sortable-content-list");
    if (container) {
      container.querySelectorAll(".sortable-item.content-block.is-open").forEach((openItem) => {
        if (openItem !== item) {
          collapseContentBlock(openItem, animate);
        }
      });
    }

    item.classList.remove("is-collapsed");
    item.classList.add("is-open");

    const body = item.querySelector(".content-item-body");
    if (body) {
      body.style.display = "flex";

      if (hasGsap && animate) {
        gsap.killTweensOf(body);
        const height = body.scrollHeight;
        body.style.overflow = "hidden";
        gsap.fromTo(body,
          { height: 0, opacity: 0 },
          {
            height: height,
            opacity: 1,
            duration: animConfig.duration,
            ease: animConfig.ease,
            onComplete: () => {
              body.style.height = "auto";
              body.style.overflow = "visible";
            }
          }
        );
      } else {
        body.style.height = "auto";
        body.style.opacity = "1";
        body.style.overflow = "visible";
      }
    }

    if (focusTitle) {
      const titleInput = item.querySelector('input[name*="[title]"]');
      if (titleInput) {
        setTimeout(() => {
          titleInput.focus();
        }, 120);
      }
    }
  }

  /**
   * Colapsa un bloque de contenido específico con animación.
   *
   * @param {HTMLElement} item Elemento .sortable-item a colapsar
   * @param {boolean} animate Si es true, ejecuta animación fluida
   */
  function collapseContentBlock(item, animate = true) {
    if (!item) return;

    const body = item.querySelector(".content-item-body");
    if (!body) {
      item.classList.remove("is-open");
      item.classList.add("is-collapsed");
      return;
    }

    if (hasGsap && animate && item.classList.contains("is-open")) {
      gsap.killTweensOf(body);
      body.style.overflow = "hidden";
      gsap.to(body, {
        height: 0,
        opacity: 0,
        duration: animConfig.duration * 0.75,
        ease: animConfig.easeClose,
        onComplete: () => {
          item.classList.remove("is-open");
          item.classList.add("is-collapsed");
          body.style.display = "none";
          gsap.set(body, { clearProps: "height,opacity,overflow" });
        }
      });
    } else {
      item.classList.remove("is-open");
      item.classList.add("is-collapsed");
      body.style.display = "none";
      body.style.height = "auto";
    }
  }

  /**
   * Actualiza el texto de la cabecera en tiempo real según el input de título.
   *
   * @param {HTMLElement} item Elemento .sortable-item
   */
  function syncHeaderTitle(item) {
    if (!item) return;

    const label = item.querySelector(".item-title-label");
    if (!label) return;

    const type = item.getAttribute("data-type") || item.querySelector('input[name*="[type]"]')?.value || "link";
    if (type === "product_group") {
      const subCount = item.querySelectorAll(".sub-product-item").length;
      label.textContent = `Grupo de productos - ${subCount} productos`;
      return;
    }

    const titleInput = item.querySelector('input[name*="[title]"]');
    if (!titleInput) return;

    const prefix = type === "product" ? "Producto" : "Enlace";
    const rawVal = titleInput.value.trim();

    label.textContent = rawVal ? `${prefix} - ${rawVal}` : `${prefix} - (Sin título)`;
  }

  /**
   * Inicializa todos los contenedores drag & drop y sus bloques colapsables.
   */
  function initAllContainers() {
    const containers = document.querySelectorAll("#sortable-content-list, #sortable-rrss-list, .sortable-container");
    if (!containers.length) return;

    containers.forEach((container) => {
      if (!container.dataset.sortableBound) {
        container.dataset.sortableBound = "true";
        initSortable(container);
      }
    });

    // En #sortable-content-list, enfocar el bloque recién creado si existe
    const contentList = document.getElementById("sortable-content-list");
    if (contentList) {
      const openBlocks = contentList.querySelectorAll(".sortable-item.content-block.is-open");
      if (openBlocks.length > 0) {
        const lastOpen = openBlocks[openBlocks.length - 1];
        const titleInput = lastOpen.querySelector('input[name*="[title]"]');
        if (titleInput && titleInput.value.trim() === "") {
          setTimeout(() => {
            titleInput.focus();
          }, 60);
        }
      }
    }
  }

  initAllContainers();

  if (!window.__sortableContentInitialized) {
    window.__sortableContentInitialized = true;

    // Auto-recuperación cuando se actualiza la vista previa o formulario por Fetch
    document.addEventListener("previewUpdated", () => {
      const containers = document.querySelectorAll("#sortable-content-list, #sortable-rrss-list, .sortable-container");
      containers.forEach((c) => {
        c.dataset.sortableBound = "";
        delete c.dataset.sortableBound;
      });
      initAllContainers();
    });

    // Auto-recuperación si la página se restaura desde BFCache
    window.addEventListener("pageshow", () => {
      initAllContainers();
    });

    // Sincronización en vivo del título mientras el usuario escribe
    document.addEventListener("input", (e) => {
      const target = e.target;
      if (!target || !target.matches('input[name*="[title]"]')) return;

      const item = target.closest(".sortable-item.content-block");
      if (item) {
        syncHeaderTitle(item);
      }
    });

    // Gestión de apertura/cierre al hacer clic en bloques
    document.addEventListener("click", (e) => {
      if (isDragging) return;

      const target = e.target;
      if (!target) return;

      // 1. Clic dentro de un bloque de contenido
      const contentItem = target.closest("#sortable-content-list .sortable-item.content-block");

      if (contentItem) {
        // Si el clic es en un control interactivo propio (switch, botón eliminar, modal, input, file), no interferir
        if (target.closest(".checkbox-switch, .modal-btn, .modal-overlay, .modal-close-button, input, select, textarea, button, label")) {
          return;
        }

        // Si se hizo clic en la cabecera
        const header = target.closest(".content-item-header");
        if (header) {
          if (contentItem.classList.contains("is-open")) {
            collapseContentBlock(contentItem);
          } else {
            expandContentBlock(contentItem, true);
          }
          return;
        }

        // Si el bloque estaba colapsado y se hace clic en cualquier parte del cuerpo
        if (contentItem.classList.contains("is-collapsed")) {
          expandContentBlock(contentItem, true);
        }
        return;
      }

      // 2. Clic fuera de cualquier bloque de contenido: Colapsar todos los bloques abiertos
      // Ignorar si el clic ocurrió dentro de popovers, modales o cropper
      if (target.closest(".modal-overlay, .content-modal-menu, .crop-container, .custom-color-picker-popover, #sidebar, .vertical-menu")) {
        return;
      }

      const contentList = document.getElementById("sortable-content-list");
      if (contentList) {
        contentList.querySelectorAll(".sortable-item.content-block.is-open").forEach((openItem) => {
          collapseContentBlock(openItem);
        });
      }
    });

    // Limpieza global de seguridad si el arrastre se interrumpe
    window.addEventListener("dragend", () => {
      document.querySelectorAll(".sortable-item.dragging").forEach((el) => {
        el.classList.remove("dragging");
        el.style.opacity = "1";
      });
      setTimeout(() => {
        isDragging = false;
      }, 50);
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

      isDragging = true;

      // Limpiar selecciones de texto activas en el navegador
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

    // Prevenir comportamiento por defecto en drop
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

      setTimeout(() => {
        isDragging = false;
      }, 50);
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

      // Actualizar la etiqueta visible
      syncHeaderTitle(item);

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

        // Re-indexar IDs y atributos for de eliminación y switches para modales
        const oldId = element.getAttribute("id");
        if (oldId && oldId.startsWith("delete-link-")) {
          element.setAttribute("id", `delete-link-${index}`);
        }
        if (oldId && oldId.startsWith("delete-rrss-")) {
          element.setAttribute("id", `delete-rrss-${index}`);
        }
        if (oldId && oldId.startsWith("offer-switch-")) {
          element.setAttribute("id", `offer-switch-${index}`);
        }

        const oldFor = element.getAttribute("for");
        if (oldFor && oldFor.startsWith("delete-link-")) {
          element.setAttribute("for", `delete-link-${index}`);
        }
        if (oldFor && oldFor.startsWith("delete-rrss-")) {
          element.setAttribute("for", `delete-rrss-${index}`);
        }
        if (oldFor && oldFor.startsWith("offer-switch-")) {
          element.setAttribute("for", `offer-switch-${index}`);
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


