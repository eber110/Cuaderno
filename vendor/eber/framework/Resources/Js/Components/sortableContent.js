/**
 * Componente sortableContent.
 * 
 * Permite reordenar elementos (.sortable-item) mediante arrastrar y soltar (Drag and Drop)
 * tanto con ratón como con eventos táctiles en dispositivos móviles y tablets.
 * 
 * Contenedores soportados:
 * - `.sortable-container`
 * - `[data-sortable]`
 * - `[data-sortable-list]`
 * - `#sortable-content-list`
 * - `#sortable-rrss-list`
 * 
 * Gestiona además el colapso, expansión y foco automático de bloques interactivos (.content-block, [data-collapsible]),
 * sincronización de títulos de cabecera en vivo, re-indexación de inputs y feedback visual mediante badge de sincronización.
 * 
 * @module sortableContent
 * @function sortableContent
 * @returns {void}
 */
export function sortableContent() {
  let isDragging = false;
  let blockNextClick = false;
  const hasGsap = typeof gsap !== "undefined";

  const animConfig = {
    duration: 0.3,
    ease: "power2.out",
    easeClose: "power2.in"
  };

  /**
   * Expande un bloque de contenido específico con animación y colapsa los demás hermanos.
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

    // Colapsar todos los demás bloques abiertos en el mismo contenedor
    const container = item.closest(".sortable-container, [data-sortable], [data-sortable-list], #sortable-content-list, #sortable-rrss-list");
    if (container) {
      container.querySelectorAll(".sortable-item.content-block.is-open, .sortable-item[data-collapsible].is-open").forEach((openItem) => {
        if (openItem !== item) {
          collapseContentBlock(openItem, animate, false);
        }
      });
    }

    item.classList.remove("is-collapsed");
    item.classList.add("is-open");

    const body = item.querySelector(".content-item-body, [data-sortable-body]");
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
      const titleInput = item.querySelector('input[name*="[title]"], [data-sortable-focus]');
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

    const body = item.querySelector(".content-item-body, [data-sortable-body]");
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

    const label = item.querySelector(".item-title-label, [data-sortable-title-label]");
    if (!label) return;

    const type = item.getAttribute("data-type") || item.querySelector('input[name*="[type]"]')?.value || "link";
    if (type === "product_group") {
      const subCount = item.querySelectorAll(".sub-product-item").length;
      label.textContent = `Grupo de productos - ${subCount} productos`;
      return;
    }

    if (type === "banner") {
      const urlInput = item.querySelector('input[name*="[url]"]');
      const rawUrl = urlInput ? urlInput.value.trim() : "";
      label.textContent = rawUrl ? `Banner - ${rawUrl}` : `Banner - (Sin enlace)`;
      return;
    }

    if (type === "text") {
      const textInput = item.querySelector('textarea[name*="[text]"], input[name*="[text]"]');
      const rawVal = textInput ? textInput.value.trim() : "";
      const displayVal = rawVal.length > 35 ? rawVal.substring(0, 35) + "..." : rawVal;
      label.textContent = displayVal ? `Texto - ${displayVal}` : `Texto - (Sin texto)`;
      return;
    }

    if (type === "separator") {
      const selectedIconRadio = item.querySelector('input[name*="[separator_icon]"]:checked');
      const iconVal = selectedIconRadio ? selectedIconRadio.value : "none";
      if (iconVal === "none" || iconVal === "ban") {
        const sizeRadio = item.querySelector('input[name*="[space_size]"]:checked');
        const size = sizeRadio ? sizeRadio.value : "40";
        const sizeName = size === "20" ? "Pequeño (20px)" : (size === "60" ? "Grande (60px)" : "Medio (40px)");
        label.textContent = `Separador - Espacio ${sizeName}`;
      } else {
        const sepSizeRadio = item.querySelector('input[name*="[separator_size]"]:checked');
        const sepSize = sepSizeRadio ? sepSizeRadio.value : "large";
        const sizeName = (sepSize === "small") ? " (1 figura)" : (sepSize === "medium" ? " (60%)" : " (Completo)");
        label.textContent = `Separador - Figuras${sizeName}`;
      }
      return;
    }

    const titleInput = item.querySelector('input[name*="[title]"], [data-sortable-title-input]');
    if (!titleInput) return;

    const prefix = type === "product" ? "Producto" : (type === "campaign" ? "Campaña" : (type === "title" ? "Título" : (type === "video" ? "Enlace de video" : "Enlace")));
    const rawVal = titleInput.value.trim();
    const emptyPlaceholder = type === "title" ? "(Sin texto)" : "(Sin título)";

    label.textContent = rawVal ? `${prefix} - ${rawVal}` : `${prefix} - ${emptyPlaceholder}`;
  }

  /**
   * Inicializa todos los contenedores drag & drop y restaura el estado del bloque activo.
   */
  function initAllContainers() {
    const containers = document.querySelectorAll(".sortable-container, [data-sortable], [data-sortable-list], #sortable-content-list, #sortable-rrss-list");
    if (!containers.length) return;

    containers.forEach((container) => {
      if (!container.dataset.sortableBound) {
        container.dataset.sortableBound = "true";
        initSortable(container);
      }
    });

    const contentList = document.getElementById("sortable-content-list") || document.querySelector("[data-sortable-accordion]");
    if (contentList) {
      const allItems = contentList.querySelectorAll(".sortable-item.content-block, .sortable-item[data-collapsible]");
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
    const rebindContainers = () => {
      try {
        initAllContainers();
      } catch (err) {
        console.warn("sortableContent rebind warning:", err);
      }
    };

    document.addEventListener("previewUpdated", rebindContainers);
    document.addEventListener("remoteContentUpdated", rebindContainers);
    document.addEventListener("designDraftDiscarded", rebindContainers);

    // Auto-recuperación si la página se restaura desde BFCache
    window.addEventListener("pageshow", () => {
      try {
        initAllContainers();
      } catch (err) {
        console.warn("sortableContent pageshow warning:", err);
      }
    });

    // Sincronización en vivo del título y controles mientras el usuario interactúa
    document.addEventListener("input", (e) => {
      const target = e.target;
      if (!target) return;

      // Slider de opacidad en vivo (Campaña y Banner)
      if (target.matches('.campaign-opacity-slider')) {
        const val = Math.max(0, Math.min(100, parseInt(target.value, 10) || 0));
        target.style.setProperty("--range-progress", `${val}%`);
        const targetId = target.dataset.valTarget;
        if (targetId) {
          const label = document.getElementById(targetId);
          if (label) label.textContent = `${val}%`;
        }

        const bannerItem = target.closest('.sortable-item[data-type="banner"]');
        const campItem = target.closest('.sortable-item[data-type="campaign"]');

        // Si pertenece a un banner, actualizar la opacidad de la imagen en vivo en todas las vistas previas
        if (bannerItem) {
          const contentList = document.getElementById("sortable-content-list");
          if (contentList) {
            const bannerItems = Array.from(contentList.querySelectorAll('.sortable-item[data-type="banner"]'));
            const idx = bannerItems.indexOf(bannerItem);
            if (idx !== -1) {
              document.querySelectorAll(".user-profile-preview").forEach((preview) => {
                const bannerWrappers = preview.querySelectorAll(".banner-block-wrapper");
                if (bannerWrappers[idx]) {
                  const img = bannerWrappers[idx].querySelector("img");
                  if (img) {
                    img.style.setProperty("opacity", (val / 100).toString(), "important");
                  }
                }
              });
            }
          }
        } else if (campItem) {
          const contentList = document.getElementById("sortable-content-list");
          if (contentList) {
            const campItems = Array.from(contentList.querySelectorAll('.sortable-item[data-type="campaign"]'));
            const idx = campItems.indexOf(campItem);
            if (idx !== -1) {
              document.querySelectorAll(".user-profile-preview").forEach((preview) => {
                const campWrappers = preview.querySelectorAll(".campaign-block-wrapper");
                if (campWrappers[idx]) {
                  const overlay = campWrappers[idx].querySelector(".campaign-bg-overlay");
                  if (overlay) {
                    overlay.style.setProperty("opacity", (val / 100).toString(), "important");
                  }
                }
              });
            }
          }
        }
        return;
      }

      if (target.matches('input[name*="[title]"], textarea[name*="[text]"], input[name*="[text]"], [data-sortable-title-input]') || target.matches('.sortable-item[data-type="banner"] input[name*="[url]"]')) {
        const item = target.closest(".sortable-item.content-block, .sortable-item[data-collapsible]");
        if (item) {
          syncHeaderTitle(item);
        }
        return;
      }
    });

    // Ajustar cropping-size, habilitar subida de imagen y sincronizar aspect ratio en tiempo real al elegir tamaño del banner
    document.addEventListener("change", (e) => {
      const target = e.target;
      if (!target) return;

      if (target.matches('.banner-size-radio')) {
        const item = target.closest(".sortable-item.content-block, .sortable-item[data-collapsible]");
        if (item) {
          const cropInput = item.querySelector('.banner-crop-input');
          if (cropInput) {
            cropInput.setAttribute('cropping-size', target.value);
            cropInput.removeAttribute('disabled');
          }
          const cropWrap = item.querySelector('.banner-crop-btn-wrapper');
          if (cropWrap) {
            cropWrap.classList.remove('opacity-40', 'pointer-events-none');
            cropWrap.removeAttribute('title');
          }
          const hint = item.querySelector('.banner-size-hint');
          if (hint) {
            hint.classList.add('hidden');
          }

          // Sincronizar de inmediato la proporción visual (aspect-ratio) en todas las vistas previas
          const contentList = document.getElementById("sortable-content-list");
          if (contentList) {
            const bannerItems = Array.from(contentList.querySelectorAll('.sortable-item[data-type="banner"]'));
            const idx = bannerItems.indexOf(item);
            if (idx !== -1) {
              let aspect = "720 / 1024";
              if (target.value === "720x720") aspect = "720 / 720";
              else if (target.value === "1024x720") aspect = "1024 / 720";

              document.querySelectorAll(".user-profile-preview").forEach((preview) => {
                const bannerWrappers = preview.querySelectorAll(".banner-block-wrapper");
                if (bannerWrappers[idx]) {
                  bannerWrappers[idx].style.setProperty("aspect-ratio", aspect, "important");
                }
              });
            }
          }
        }
      }

      if (target.matches('.separator-icon-radio') || target.matches('input[name*="[space_size]"]') || target.matches('.separator-size-radio')) {
        const item = target.closest(".sortable-item.content-block, .sortable-item[data-collapsible]");
        if (item) {
          if (target.matches('.separator-icon-radio')) {
            const spaceOptions = item.querySelector('.separator-space-options');
            const sizeOptions = item.querySelector('.separator-size-options');
            const isBan = (target.value === 'none' || target.value === 'ban');
            if (spaceOptions) {
              spaceOptions.style.display = isBan ? 'flex' : 'none';
            }
            if (sizeOptions) {
              sizeOptions.style.display = isBan ? 'none' : 'flex';
            }
          }
          syncHeaderTitle(item);
        }
      }
    });

    // Interceptor en fase de captura para bloquear clics sintéticos tras finalizar un arrastre (táctil o ratón)
    document.addEventListener("click", (e) => {
      if (blockNextClick || isDragging) {
        e.preventDefault();
        e.stopPropagation();
        blockNextClick = false;
        return;
      }
    }, true);

    // Evitar menú contextual durante el arrastre
    window.addEventListener("contextmenu", (e) => {
      if (isDragging) {
        e.preventDefault();
      }
    });

    // Gestión de apertura/cierre exclusivamente manual al hacer clic en bloques
    document.addEventListener("click", (e) => {
      // 1. Detectar clic en botones de añadir nuevo elemento
      const addBtn = e.target.closest('button[name="add_content_type"], [data-sortable-add-btn]');
      if (addBtn) {
        sessionStorage.setItem("open_new_block_on_load", "true");
        return;
      }

      if (isDragging || blockNextClick) return;

      const target = e.target;
      if (!target) return;

      // 2. Clic dentro de un bloque de contenido
      const contentItem = target.closest(".sortable-item.content-block, .sortable-item[data-collapsible]");

      if (contentItem) {
        // Si el clic es en un control interactivo interno (switch, botón eliminar, modal, input, select, etc.), no colapsar/expandir
        if (target.closest(".checkbox-switch, .modal-btn, .modal-overlay, .modal-close-button, input, select, textarea, button, label")) {
          return;
        }

        // Si se hizo clic en la cabecera / nombre del bloque
        const header = target.closest(".content-item-header, [data-sortable-header]");
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
      blockNextClick = true;
      setTimeout(() => {
        blockNextClick = false;
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
      const handle = e.target.closest(".drag-handle, [data-drag-handle]");
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
          updateIndicesAndSubmit(container, draggedItem);
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
        const lastSortable = [...container.querySelectorAll(".sortable-item")].pop();
        if (lastSortable && lastSortable !== draggedItem) {
          lastSortable.after(draggedItem);
        } else if (!lastSortable && container.lastElementChild !== draggedItem) {
          container.appendChild(draggedItem);
        }
      } else {
        if (draggedItem.nextElementSibling !== afterElement && draggedItem !== afterElement) {
          container.insertBefore(draggedItem, afterElement);
        }
      }
    });

    // --- SOPORTE PARA PANTALLAS TÁCTILES (Touch Events) ---
    let touchItem = null;
    let touchStartX = 0;
    let touchStartY = 0;
    let touchCurrentY = 0;
    let touchInitialIndex = null;
    let isTouchDragging = false;
    let autoScrollRaf = null;

    /**
     * Obtiene el contenedor desplazable más cercano o window.
     *
     * @param {HTMLElement} element Elemento de referencia.
     * @returns {HTMLElement|Window} Contenedor con scroll.
     */
    function getScrollParent(element) {
      let parent = element ? element.parentElement : null;
      while (parent && parent !== document.body && parent !== document.documentElement) {
        const style = window.getComputedStyle(parent);
        const overflowY = style.overflowY;
        if ((overflowY === "auto" || overflowY === "scroll") && parent.scrollHeight > parent.clientHeight) {
          return parent;
        }
        parent = parent.parentElement;
      }
      return window;
    }

    /**
     * Detiene la animación de auto-desplazamiento.
     */
    function stopAutoScroll() {
      if (autoScrollRaf) {
        cancelAnimationFrame(autoScrollRaf);
        autoScrollRaf = null;
      }
    }

    /**
     * Realiza desplazamiento automático suave si el dedo se acerca a los extremos de la pantalla.
     *
     * @param {number} clientY Coordenada Y del toque.
     */
    function checkAutoScroll(clientY) {
      stopAutoScroll();
      if (!isTouchDragging || !touchItem) return;

      const viewportHeight = window.innerHeight;
      const edgeThreshold = 75;
      let speed = 0;

      if (clientY < edgeThreshold) {
        speed = -Math.min(14, Math.max(4, Math.round((edgeThreshold - clientY) / 3)));
      } else if (clientY > viewportHeight - edgeThreshold) {
        speed = Math.min(14, Math.max(4, Math.round((clientY - (viewportHeight - edgeThreshold)) / 3)));
      }

      if (speed !== 0) {
        const scrollParent = getScrollParent(container);
        const scrollStep = () => {
          if (!isTouchDragging || !touchItem) return;

          if (scrollParent === window || scrollParent === document.body || scrollParent === document.documentElement) {
            window.scrollBy(0, speed);
          } else {
            const prevTop = scrollParent.scrollTop;
            scrollParent.scrollTop += speed;
            if (scrollParent.scrollTop === prevTop) {
              window.scrollBy(0, speed);
            }
          }

          // Recalcular orden en vivo mientras ocurre el scroll
          const afterElement = getDragAfterElement(container, touchCurrentY);
          if (afterElement == null) {
            const lastSortable = [...container.querySelectorAll(".sortable-item")].pop();
            if (lastSortable && lastSortable !== touchItem) {
              lastSortable.after(touchItem);
            } else if (!lastSortable && container.lastElementChild !== touchItem) {
              container.appendChild(touchItem);
            }
          } else {
            if (touchItem.nextElementSibling !== afterElement && touchItem !== afterElement) {
              container.insertBefore(touchItem, afterElement);
            }
          }

          autoScrollRaf = requestAnimationFrame(scrollStep);
        };
        autoScrollRaf = requestAnimationFrame(scrollStep);
      }
    }

    /**
     * Manejador de touchmove en window.
     *
     * @param {TouchEvent} e Evento táctil.
     */
    const onTouchMove = (e) => {
      if (!touchItem || !e.touches || e.touches.length !== 1) return;

      const touch = e.touches[0];
      touchCurrentY = touch.clientY;
      const deltaX = Math.abs(touch.clientX - touchStartX);
      const deltaY = Math.abs(touch.clientY - touchStartY);

      if (!isTouchDragging) {
        // Si el usuario desliza horizontalmente de forma marcada, cancelar arrastre para permitir gestos del navegador
        if (deltaX > 12 && deltaX > deltaY) {
          cleanUpTouch();
          return;
        }

        // Superar umbral para confirmar arrastre
        if (deltaY > 6 && deltaY >= deltaX) {
          isTouchDragging = true;
          isDragging = true;
          touchItem.classList.add("dragging");
          touchItem.style.opacity = "0.4";

          if (window.getSelection) {
            window.getSelection().removeAllRanges();
          }
        }
      }

      if (isTouchDragging) {
        if (e.cancelable) {
          e.preventDefault();
        }

        const afterElement = getDragAfterElement(container, touch.clientY);
        if (afterElement == null) {
          const lastSortable = [...container.querySelectorAll(".sortable-item")].pop();
          if (lastSortable && lastSortable !== touchItem) {
            lastSortable.after(touchItem);
          } else if (!lastSortable && container.lastElementChild !== touchItem) {
            container.appendChild(touchItem);
          }
        } else {
          if (touchItem.nextElementSibling !== afterElement && touchItem !== afterElement) {
            container.insertBefore(touchItem, afterElement);
          }
        }

        checkAutoScroll(touch.clientY);
      }
    };

    /**
     * Manejador de touchend.
     *
     * @param {TouchEvent} e Evento táctil.
     */
    const onTouchEnd = (e) => {
      stopAutoScroll();

      if (isTouchDragging && touchItem) {
        if (e && e.cancelable) {
          e.preventDefault();
        }

        touchItem.classList.remove("dragging");
        touchItem.style.opacity = "1";

        const allItems = [...container.querySelectorAll(".sortable-item")];
        const newIndex = allItems.indexOf(touchItem);

        if (touchInitialIndex !== null && newIndex !== -1 && touchInitialIndex !== newIndex) {
          updateIndicesAndSubmit(container, touchItem);
        }

        blockNextClick = true;
        setTimeout(() => {
          blockNextClick = false;
          isDragging = false;
        }, 200);
      }

      cleanUpTouch();
    };

    /**
     * Manejador de touchcancel.
     */
    const onTouchCancel = () => {
      stopAutoScroll();
      if (touchItem) {
        touchItem.classList.remove("dragging");
        touchItem.style.opacity = "1";
      }
      blockNextClick = true;
      setTimeout(() => {
        blockNextClick = false;
        isDragging = false;
      }, 150);
      cleanUpTouch();
    };

    function cleanUpTouch() {
      stopAutoScroll();
      window.removeEventListener("touchmove", onTouchMove, { passive: false });
      window.removeEventListener("touchend", onTouchEnd);
      window.removeEventListener("touchcancel", onTouchCancel);
      touchItem = null;
      touchInitialIndex = null;
      isTouchDragging = false;
    }

    container.addEventListener("touchstart", (e) => {
      if (!e.touches || e.touches.length !== 1) return;

      const handle = e.target.closest(".drag-handle, [data-drag-handle]");
      if (!handle) return;

      // No iniciar arrastre si el toque ocurre en controles interactivos internos
      if (e.target.closest("input, textarea, select, button, label, .modal-btn, .content-modal-menu, .checkbox-switch, a, [contenteditable]")) {
        return;
      }

      const item = handle.closest(".sortable-item");
      if (!item || !container.contains(item)) return;

      touchItem = item;
      touchStartX = e.touches[0].clientX;
      touchStartY = e.touches[0].clientY;
      touchCurrentY = e.touches[0].clientY;
      const allItems = [...container.querySelectorAll(".sortable-item")];
      touchInitialIndex = allItems.indexOf(item);
      isTouchDragging = false;

      window.addEventListener("touchmove", onTouchMove, { passive: false });
      window.addEventListener("touchend", onTouchEnd);
      window.addEventListener("touchcancel", onTouchCancel);
    }, { passive: true });
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

  /**
   * Muestra o actualiza el badge indicador de procesamiento interno de ordenamiento.
   *
   * @param {HTMLElement} container Contenedor sortable.
   * @param {HTMLElement|null} targetItem Elemento movido.
   * @param {'syncing'|'success'|'error'} state Estado de la operación.
   * @param {string} [customText] Texto a mostrar.
   */
  function showSyncIndicator(container, targetItem, state, customText = "") {
    if (!container) return;

    let badge = container.querySelector(".sortable-sync-badge") || container.parentElement?.querySelector(".sortable-sync-badge");
    if (!badge && state === "syncing") {
      badge = document.createElement("div");
      badge.className = "sortable-sync-badge";
      if (container.parentElement) {
        container.parentElement.insertBefore(badge, container);
      } else {
        container.prepend(badge);
      }
    }

    if (!badge) return;

    if (state === "syncing") {
      badge.className = "sortable-sync-badge";
      badge.style.opacity = "1";
      badge.style.transform = "translateY(0)";
      badge.innerHTML = `<span class="save-btn-spinner-dark"></span> <span>${customText || "Guardando nuevo orden..."}</span>`;
      if (targetItem) {
        targetItem.classList.add("item-syncing");
      }
    } else if (state === "success") {
      badge.classList.add("is-done");
      badge.innerHTML = `<span>${customText || "✓ Orden guardado"}</span>`;
      if (targetItem) {
        targetItem.classList.remove("item-syncing");
      }
      setTimeout(() => {
        badge.style.opacity = "0";
        badge.style.transform = "translateY(-6px)";
        setTimeout(() => {
          if (badge.parentNode) badge.parentNode.removeChild(badge);
        }, 300);
      }, 700);
    } else if (state === "error") {
      badge.classList.add("is-error");
      badge.innerHTML = `<span>${customText || "⚠ Error al guardar orden"}</span>`;
      if (targetItem) {
        targetItem.classList.remove("item-syncing");
      }
      setTimeout(() => {
        badge.style.opacity = "0";
        badge.style.transform = "translateY(-6px)";
        setTimeout(() => {
          if (badge.parentNode) badge.parentNode.removeChild(badge);
        }, 1500);
      }, 1500);
    }
  }

  /**
   * Reordena de forma optimista (0ms de latencia) los elementos en las vistas previas
   * para que el usuario visualice el cambio instantáneamente.
   *
   * @param {HTMLElement} container Contenedor sortable reordenado.
   * @param {Array<number|string>} oldIndices Lista de los índices antiguos en el nuevo orden DOM.
   */
  function reorderPreviewOptimistically(container, oldIndices) {
    if (!container) return;

    const isContent = container.id === "sortable-content-list";
    const isRRSS = container.id === "sortable-rrss-list";

    if (isContent && oldIndices && oldIndices.length) {
      document.querySelectorAll(".user-profile-preview").forEach((preview) => {
        const sampleBlock = preview.querySelector("[data-content-index]");
        if (!sampleBlock || !sampleBlock.parentElement) return;

        const widgetWrapper = sampleBlock.parentElement;
        const previewMap = new Map();

        widgetWrapper.querySelectorAll("[data-content-index]").forEach((el) => {
          const idx = parseInt(el.getAttribute("data-content-index"), 10);
          if (!isNaN(idx)) {
            if (!previewMap.has(idx)) previewMap.set(idx, []);
            previewMap.get(idx).push(el);
          }
        });

        oldIndices.forEach((oldIdx, newIdx) => {
          const elements = previewMap.get(oldIdx);
          if (elements && elements.length) {
            elements.forEach((el) => {
              widgetWrapper.appendChild(el);
              el.setAttribute("data-content-index", String(newIdx));
            });
          }
        });
      });
    } else if (isRRSS) {
      document.querySelectorAll(".user-profile-preview").forEach((preview) => {
        const sampleLink = preview.querySelector('[data-link-id^="rrss_"]');
        if (!sampleLink || !sampleLink.parentElement) return;

        const rrssWrapper = sampleLink.parentElement;
        const allItems = Array.from(container.querySelectorAll(".sortable-item"));

        allItems.forEach((item) => {
          const nameInput = item.querySelector('input[name*="[0]"]');
          const name = nameInput ? nameInput.value.trim().toLowerCase() : "";
          if (name) {
            const previewLink = rrssWrapper.querySelector(`[data-link-id="rrss_${name}"]`);
            if (previewLink) {
              rrssWrapper.appendChild(previewLink);
            }
          }
        });
      });
    }
  }

  // Re-indexa los nombres de los inputs (`content[index][...]` o `rrss[index][...]`) según el nuevo orden del DOM
  function updateIndicesAndSubmit(container, draggedItem = null) {
    const items = Array.from(container.querySelectorAll(".sortable-item"));

    // 1. Capturar índices previos antes de renombrar para la sincronización optimista del preview
    const oldIndices = items.map((item) => {
      if (item.id && item.id.startsWith("content-item-")) {
        const val = parseInt(item.id.replace("content-item-", ""), 10);
        return isNaN(val) ? null : val;
      }
      return null;
    });

    // 2. Notificar INMEDIATAMENTE (0ms) a saveButtonController y designDraftManager
    if (window.__saveButtonController && typeof window.__saveButtonController.enableSaveButton === "function") {
      window.__saveButtonController.enableSaveButton();
    }
    document.dispatchEvent(new CustomEvent("designDraftStateChanged", { detail: { hasDraft: true } }));

    // 3. Reordenar el preview en 0ms
    if (oldIndices.some((idx) => idx !== null)) {
      reorderPreviewOptimistically(container, oldIndices);
    } else if (container.id === "sortable-rrss-list") {
      reorderPreviewOptimistically(container, []);
    }

    // 4. Mostrar indicador de procesamiento interno en la lista y en el elemento movido
    showSyncIndicator(container, draggedItem, "syncing");

    // 5. Re-indexar los campos input dentro de la tarjeta
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

      // Re-indexar los campos dentro de la tarjeta
      const elements = item.querySelectorAll("*");
      elements.forEach((element) => {
        // 1. Re-indexar atributos name
        const oldName = element.getAttribute("name");
        if (oldName) {
          if (oldName.startsWith("content[")) {
            element.setAttribute("name", oldName.replace(/^content\[\d+\]/, `content[${index}]`));
          } else if (oldName.startsWith("rrss[")) {
            element.setAttribute("name", oldName.replace(/^rrss\[\d+\]/, `rrss[${index}]`));
          } else if (oldName.startsWith("content_img_")) {
            const matchSub = oldName.match(/^content_img_\d+_(\d+)$/);
            if (matchSub) {
              element.setAttribute("name", `content_img_${index}_${matchSub[1]}`);
            } else {
              element.setAttribute("name", `content_img_${index}`);
            }
          }
        }

        // 2. Re-indexar IDs
        const oldId = element.getAttribute("id");
        if (oldId) {
          if (/^offer-switch-\d+-\d+$/.test(oldId)) {
            element.setAttribute("id", oldId.replace(/^offer-switch-\d+-/, `offer-switch-${index}-`));
          } else if (/-\d+$/.test(oldId)) {
            element.setAttribute("id", oldId.replace(/-\d+$/, `-${index}`));
          }
        }

        // 3. Re-indexar atributos for
        const oldFor = element.getAttribute("for");
        if (oldFor) {
          if (/^offer-switch-\d+-\d+$/.test(oldFor)) {
            element.setAttribute("for", oldFor.replace(/^offer-switch-\d+-/, `offer-switch-${index}-`));
          } else if (/-\d+$/.test(oldFor)) {
            element.setAttribute("for", oldFor.replace(/-\d+$/, `-${index}`));
          }
        }

        // 4. Re-indexar data-target
        const oldDataTarget = element.getAttribute("data-target");
        if (oldDataTarget && /-\d+$/.test(oldDataTarget)) {
          element.setAttribute("data-target", oldDataTarget.replace(/-\d+$/, `-${index}`));
        }

        // 5. Re-indexar data-val-target
        const oldValTarget = element.getAttribute("data-val-target");
        if (oldValTarget && /-\d+$/.test(oldValTarget)) {
          element.setAttribute("data-val-target", oldValTarget.replace(/-\d+$/, `-${index}`));
        }

        // 6. Re-indexar data-index
        if (element.hasAttribute("data-index")) {
          element.setAttribute("data-index", String(index));
        }
      });
    });

    // 6. Disparar eventos nativos de ordenamiento
    container.dispatchEvent(new CustomEvent("sortableChange", { bubbles: true, detail: { item: draggedItem, container } }));
    document.dispatchEvent(new CustomEvent("sortableUpdated", { bubbles: true, detail: { item: draggedItem, container } }));

    // 7. Disparar actualización asíncrona mediante submitRemoteFormAjax pasando is_reorder
    const form = container.closest("form.auto-submit") || container.closest("form");
    if (form) {
      if (window.__designDraftManager && typeof window.__designDraftManager.submitRemoteFormAjax === "function") {
        window.__designDraftManager.submitRemoteFormAjax(form, { name: "is_reorder", value: "true" })
          .then((success) => {
            if (success !== false) {
              showSyncIndicator(container, draggedItem, "success", "✓ Orden guardado");
            } else {
              showSyncIndicator(container, draggedItem, "error", "⚠ Error al guardar");
            }
          })
          .catch(() => {
            showSyncIndicator(container, draggedItem, "error", "⚠ Error al guardar");
          });
      } else if (typeof form.requestSubmit === "function") {
        form.requestSubmit();
        showSyncIndicator(container, draggedItem, "success", "✓ Orden guardado");
      } else {
        form.submit();
      }
    }
  }

  // API pública global
  window.SortableContent = {
    init: sortableContent,
    initContainer: initSortable,
    expand: expandContentBlock,
    collapse: collapseContentBlock
  };
}
