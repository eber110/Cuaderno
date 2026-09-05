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

    if (item.id) {
      sessionStorage.setItem("active_content_block_id", item.id);
    }

    // Colapsar todos los demás bloques abiertos
    const container = item.closest("#sortable-content-list");
    if (container) {
      container.querySelectorAll(".sortable-item.content-block.is-open").forEach((openItem) => {
        if (openItem !== item) {
          collapseContentBlock(openItem, animate, false);
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
   * @param {boolean} clearActive Si es true, borra la referencia del bloque activo
   */
  function collapseContentBlock(item, animate = true, clearActive = true) {
    if (!item) return;

    if (clearActive && item.id && sessionStorage.getItem("active_content_block_id") === item.id) {
      sessionStorage.removeItem("active_content_block_id");
    }

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

    const prefix = type === "product" ? "Producto" : (type === "campaign" ? "Campaña" : "Enlace");
    const rawVal = titleInput.value.trim();

    label.textContent = rawVal ? `${prefix} - ${rawVal}` : `${prefix} - (Sin título)`;
  }

  /**
   * Inicializa todos los contenedores drag & drop y restaura el estado del bloque activo.
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

    const contentList = document.getElementById("sortable-content-list");
    if (contentList) {
      const allItems = contentList.querySelectorAll(".sortable-item.content-block");
      const openNew = sessionStorage.getItem("open_new_block_on_load");
      const savedActiveId = sessionStorage.getItem("active_content_block_id");

      if (openNew === "true" && allItems.length > 0) {
        sessionStorage.removeItem("open_new_block_on_load");
        const lastItem = allItems[allItems.length - 1];
        allItems.forEach((it) => {
          if (it === lastItem) {
            expandContentBlock(it, true, false);
          } else {
            collapseContentBlock(it, false, false);
          }
        });
      } else if (savedActiveId) {
        const targetItem = document.getElementById(savedActiveId);
        if (targetItem) {
          allItems.forEach((it) => {
            if (it === targetItem) {
              expandContentBlock(it, false, false);
            } else {
              collapseContentBlock(it, false, false);
            }
          });
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

    // Sincronización en vivo del título y controles mientras el usuario interactúa
    document.addEventListener("input", (e) => {
      const target = e.target;
      if (!target) return;

      // Slider de opacidad de campaña en vivo
      if (target.matches('.campaign-opacity-slider')) {
        const val = Math.max(0, Math.min(100, parseInt(target.value, 10) || 0));
        target.style.setProperty("--range-progress", `${val}%`);
        const targetId = target.dataset.valTarget;
        if (targetId) {
          const label = document.getElementById(targetId);
          if (label) label.textContent = `${val}%`;
        }
        return;
      }

      if (!target.matches('input[name*="[title]"]')) return;

      const item = target.closest(".sortable-item.content-block");
      if (item) {
        syncHeaderTitle(item);
      }
    });

    // Gestión de apertura/cierre exclusivamente manual al hacer clic en bloques
    document.addEventListener("click", (e) => {
      // 1. Detectar clic en botones de añadir nuevo elemento
      const addBtn = e.target.closest('button[name="add_content_type"]');
      if (addBtn) {
        sessionStorage.setItem("open_new_block_on_load", "true");
        return;
      }

      if (isDragging) return;

      const target = e.target;
      if (!target) return;

      // 2. Clic dentro de un bloque de contenido
      const contentItem = target.closest("#sortable-content-list .sortable-item.content-block");

      if (contentItem) {
        // Si el clic es en un control interactivo interno (switch, botón eliminar, modal, input, select, etc.), no colapsar/expandir
        if (target.closest(".checkbox-switch, .modal-btn, .modal-overlay, .modal-close-button, input, select, textarea, button, label")) {
          return;
        }

        // Si se hizo clic en la cabecera / nombre del bloque
        const header = target.closest(".content-item-header");
        if (header) {
          if (contentItem.classList.contains("is-open")) {
            collapseContentBlock(contentItem, true, true);
          } else {
            expandContentBlock(contentItem, false, true);
          }
          return;
        }

        // Si el bloque estaba colapsado y se hace clic en su cuerpo
        if (contentItem.classList.contains("is-collapsed")) {
          expandContentBlock(contentItem, false, true);
        }
        return;
      }
    });

    // Limpieza global de seguridad si el arrastre se interrumpe
    window.addEventListener("dragend", () => {
      document.querySelectorAll(".sortable-item[draggable='true']").forEach((el) => {
        el.setAttribute("draggable", "false");
      });
      document.querySelectorAll(".sortable-item.dragging").forEach((el) => {
        el.classList.remove("dragging");
        el.style.opacity = "1";
      });
      setTimeout(() => {
        isDragging = false;
      }, 50);
    });

    window.addEventListener("mouseup", () => {
      document.querySelectorAll(".sortable-item[draggable='true']").forEach((el) => {
        el.setAttribute("draggable", "false");
      });
    });
  }

  function initSortable(container) {
    let draggedItem = null;
    let initialIndex = null;
    let activeDragHandle = null;

    // Solo habilitar draggable cuando mousedown ocurra sobre .drag-handle
    container.addEventListener("mousedown", (e) => {
      const handle = e.target.closest(".drag-handle");
      if (handle && !e.target.closest("input, textarea, select, button, label, .modal-btn, .checkbox-switch, a")) {
        const item = handle.closest(".sortable-item");
        if (item) {
          activeDragHandle = handle;
          item.setAttribute("draggable", "true");
          return;
        }
      }

      // Si el clic es en cualquier otra parte (slider, inputs, cuerpo, etc.), desactivar draggable
      activeDragHandle = null;
      container.querySelectorAll('.sortable-item[draggable="true"]').forEach((el) => {
        el.setAttribute("draggable", "false");
      });
    });

    // Iniciar arrastre solo si se originó en la zona arrastrable (drag-handle)
    container.addEventListener("dragstart", (e) => {
      if (!activeDragHandle) {
        e.preventDefault();
        return;
      }

      const item = e.target.closest(".sortable-item");
      if (!item || !item.contains(activeDragHandle)) {
        e.preventDefault();
        return;
      }

      // Ignorar si el arrastre se inició dentro de un campo interactivo o botón
      if (e.target.closest("input, textarea, select, button, label, .modal-btn, .content-modal-menu, .checkbox-switch, a")) {
        e.preventDefault();
        return;
      }

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
      activeDragHandle = null;
      container.querySelectorAll('.sortable-item[draggable="true"]').forEach((el) => {
        el.setAttribute("draggable", "false");
      });

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
        if (item.classList.contains("is-open")) {
          sessionStorage.setItem("active_content_block_id", item.id);
        }
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


