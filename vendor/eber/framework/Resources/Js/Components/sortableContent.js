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
    window.addEventListener("blur", () => {
      document.querySelectorAll(".sortable-floating-avatar").forEach((el) => el.remove());
      document.querySelectorAll(".sortable-item.dragging, .sortable-item.is-drop-placeholder").forEach((el) => {
        el.classList.remove("dragging", "is-drop-placeholder");
        el.style.height = "";
        el.style.opacity = "";
      });
      document.body.classList.remove("is-sorting-items");
      isDragging = false;
    });
  }

  function initSortable(container) {
    let activeItem = null;
    let floatingAvatar = null;
    let initialIndex = null;
    let startX = 0;
    let startY = 0;
    let currentX = 0;
    let currentY = 0;
    let startRect = null;
    let isPointerDragging = false;
    let autoScrollRaf = null;
    let touchDelayTimeout = null;
    let pointerMode = null; // "mouse" | "touch"
    let lastTouchTime = 0;

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
     * Crea el clon visual elevado (100% opaco y con sombra profunda) que sigue al cursor
     * por encima de los demás bloques, convirtiendo el ítem original en el hueco de destino.
     *
     * @param {HTMLElement} item Elemento .sortable-item a elevar.
     */
    function activateFloatingDrag(item) {
      if (!item || isPointerDragging) return;
      isPointerDragging = true;
      isDragging = true;

      if (window.getSelection) {
        window.getSelection().removeAllRanges();
      }

      startRect = item.getBoundingClientRect();
      floatingAvatar = item.cloneNode(true);
      floatingAvatar.removeAttribute("id");
      floatingAvatar.querySelectorAll("[id]").forEach((el) => el.removeAttribute("id"));

      // Sincronizar valores actuales de inputs/textareas/selects en el clon visual
      const origInputs = item.querySelectorAll("input, textarea, select");
      const cloneInputs = floatingAvatar.querySelectorAll("input, textarea, select");
      origInputs.forEach((inp, idx) => {
        const cInp = cloneInputs[idx];
        if (!cInp) return;
        if (inp.type === "checkbox" || inp.type === "radio") {
          cInp.checked = inp.checked;
        } else if (inp.type !== "file") {
          cInp.value = inp.value;
        }
      });

      floatingAvatar.classList.remove("dragging", "is-drop-placeholder");
      floatingAvatar.classList.add("sortable-floating-avatar");
      floatingAvatar.style.top = `${startRect.top}px`;
      floatingAvatar.style.left = `${startRect.left}px`;
      floatingAvatar.style.width = `${startRect.width}px`;
      floatingAvatar.style.height = `${startRect.height}px`;
      floatingAvatar.style.transform = "translate3d(0px, 0px, 0px) scale(1.025)";
      document.body.appendChild(floatingAvatar);
      document.body.classList.add("is-sorting-items");

      // Convertir el elemento original en un hueco marcador limpio con su altura exacta
      item.style.height = `${startRect.height}px`;
      item.classList.add("dragging", "is-drop-placeholder");
    }

    /**
     * Mueve el hueco marcador en la lista aplicando animación FLIP suave a los demás bloques.
     *
     * @param {number} clientY Coordenada Y actual del puntero.
     */
    function updateSlotPositionWithFlip(clientY) {
      if (!activeItem || !isPointerDragging) return;

      const afterElement = getDragAfterElement(container, clientY);
      let shouldMove = false;

      if (afterElement == null) {
        const lastSortable = [...container.querySelectorAll(".sortable-item")].pop();
        if ((lastSortable && lastSortable !== activeItem) || (!lastSortable && container.lastElementChild !== activeItem)) {
          shouldMove = true;
        }
      } else if (activeItem.nextElementSibling !== afterElement && activeItem !== afterElement) {
        shouldMove = true;
      }

      if (!shouldMove) return;

      // 1. First: registrar posiciones actuales de los hermanos antes del cambio DOM
      const siblings = [...container.querySelectorAll(".sortable-item:not(.dragging)")];
      const prevTops = new Map();
      siblings.forEach((sib) => {
        prevTops.set(sib, sib.getBoundingClientRect().top);
      });

      // 2. Ejecutar inserción en el DOM
      if (afterElement == null) {
        const lastSortable = [...container.querySelectorAll(".sortable-item")].pop();
        if (lastSortable && lastSortable !== activeItem) {
          lastSortable.after(activeItem);
        } else if (!lastSortable && container.lastElementChild !== activeItem) {
          container.appendChild(activeItem);
        }
      } else {
        container.insertBefore(activeItem, afterElement);
      }

      // 3. Invert & Play: animar suavemente el desplazamiento de los bloques sobrepasados
      siblings.forEach((sib) => {
        const oldTop = prevTops.get(sib);
        const newTop = sib.getBoundingClientRect().top;
        const deltaY = oldTop - newTop;
        if (Math.abs(deltaY) > 1) {
          sib.style.transition = "none";
          sib.style.transform = `translate3d(0, ${deltaY}px, 0)`;
          void sib.offsetHeight;
          sib.style.transition = "transform 220ms cubic-bezier(0.2, 1, 0.2, 1)";
          sib.style.transform = "";
        }
      });
    }

    /**
     * Actualiza la posición de la tarjeta flotante elevada siguiendo el puntero.
     *
     * @param {number} clientX Coordenada X.
     * @param {number} clientY Coordenada Y.
     */
    function updateAvatarTransform(clientX, clientY) {
      if (!floatingAvatar || !startRect) return;
      const dx = (clientX - startX) * 0.08;
      const dy = clientY - startY;
      floatingAvatar.style.transform = `translate3d(${dx}px, ${dy}px, 0) scale(1.025)`;
    }

    /**
     * Realiza desplazamiento automático suave si el puntero se acerca a los extremos de la pantalla.
     *
     * @param {number} clientY Coordenada Y del puntero.
     */
    function checkAutoScroll(clientY) {
      stopAutoScroll();
      if (!isPointerDragging || !activeItem) return;

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
          if (!isPointerDragging || !activeItem) return;

          if (scrollParent === window || scrollParent === document.body || scrollParent === document.documentElement) {
            window.scrollBy(0, speed);
          } else {
            const prevTop = scrollParent.scrollTop;
            scrollParent.scrollTop += speed;
            if (scrollParent.scrollTop === prevTop) {
              window.scrollBy(0, speed);
            }
          }

          updateSlotPositionWithFlip(currentY);
          autoScrollRaf = requestAnimationFrame(scrollStep);
        };
        autoScrollRaf = requestAnimationFrame(scrollStep);
      }
    }

    /**
     * Finaliza el arrastre animando el aterrizaje de la tarjeta flotante hacia el hueco final.
     */
    function finishDragSession() {
      stopAutoScroll();
      if (touchDelayTimeout) {
        clearTimeout(touchDelayTimeout);
        touchDelayTimeout = null;
      }

      window.removeEventListener("mousemove", onMouseMove);
      window.removeEventListener("mouseup", onMouseUp);
      window.removeEventListener("touchmove", onTouchMove, { passive: false });
      window.removeEventListener("touchend", onTouchEnd);
      window.removeEventListener("touchcancel", onTouchCancel);

      const item = activeItem;
      const avatar = floatingAvatar;
      const sRect = startRect;
      const initIdx = initialIndex;

      activeItem = null;
      floatingAvatar = null;
      startRect = null;
      initialIndex = null;
      pointerMode = null;
      document.body.classList.remove("is-sorting-items");

      if (!isPointerDragging || !item) {
        isPointerDragging = false;
        if (avatar) avatar.remove();
        return;
      }

      isPointerDragging = false;
      const allItems = [...container.querySelectorAll(".sortable-item")];
      const newIndex = allItems.indexOf(item);
      const orderChanged = initIdx !== null && newIndex !== -1 && initIdx !== newIndex;

      // Actualizar índices y vista previa en 0ms al soltar
      if (orderChanged) {
        updateIndicesAndSubmit(container, item);
      }

      // Animar aterrizaje suave de la tarjeta flotante hacia la posición exacta del hueco
      if (avatar && sRect) {
        const targetRect = item.getBoundingClientRect();
        const targetDx = targetRect.left - sRect.left;
        const targetDy = targetRect.top - sRect.top;
        avatar.style.transition = "transform 165ms cubic-bezier(0.2, 1, 0.3, 1), box-shadow 165ms ease";
        avatar.style.transform = `translate3d(${targetDx}px, ${targetDy}px, 0) scale(1)`;
        avatar.style.boxShadow = "0 4px 12px rgba(0, 0, 0, 0.08)";

        setTimeout(() => {
          avatar.remove();
          item.classList.remove("dragging", "is-drop-placeholder");
          item.style.height = "";
          item.style.opacity = "";
        }, 165);
      } else {
        item.classList.remove("dragging", "is-drop-placeholder");
        item.style.height = "";
        item.style.opacity = "";
      }

      blockNextClick = true;
      setTimeout(() => {
        blockNextClick = false;
        isDragging = false;
      }, 180);
    }

    // Prevenir dragstart nativo HTML5 para evitar la imagen fantasma semitransparente del navegador
    container.addEventListener("dragstart", (e) => {
      e.preventDefault();
    });

    // --- 1. SOPORTE PARA RATÓN (Desktop Mouse Events) ---
    const onMouseMove = (e) => {
      if (!activeItem || pointerMode !== "mouse") return;
      currentX = e.clientX;
      currentY = e.clientY;

      if (!isPointerDragging) {
        const dist = Math.hypot(currentX - startX, currentY - startY);
        if (dist < 4) return;
        activateFloatingDrag(activeItem);
      }

      e.preventDefault();
      updateAvatarTransform(currentX, currentY);
      updateSlotPositionWithFlip(currentY);
      checkAutoScroll(currentY);
    };

    const onMouseUp = () => {
      if (pointerMode !== "mouse") return;
      finishDragSession();
    };

    container.addEventListener("mousedown", (e) => {
      if (e.button !== 0) return;
      // Evitar que eventos sintéticos de ratón en pantallas táctiles se salten el delay de 500ms
      if (Date.now() - lastTouchTime < 800) return;

      const handle = e.target.closest(".drag-handle, [data-drag-handle]");
      if (!handle) return;

      if (e.target.closest("input, textarea, select, button, label, .modal-btn, .content-modal-menu, .checkbox-switch, a, [contenteditable]")) {
        return;
      }

      const item = handle.closest(".sortable-item");
      if (!item || !container.contains(item)) return;

      pointerMode = "mouse";
      activeItem = item;
      startX = e.clientX;
      startY = e.clientY;
      currentX = e.clientX;
      currentY = e.clientY;
      const allItems = [...container.querySelectorAll(".sortable-item")];
      initialIndex = allItems.indexOf(item);
      isPointerDragging = false;

      window.addEventListener("mousemove", onMouseMove);
      window.addEventListener("mouseup", onMouseUp);
    });

    // --- 2. SOPORTE PARA PANTALLAS TÁCTILES (Touch Events con delay de 500ms anti-scroll accidental) ---
    const onTouchMove = (e) => {
      if (!activeItem || pointerMode !== "touch" || !e.touches || e.touches.length !== 1) return;

      const touch = e.touches[0];
      currentX = touch.clientX;
      currentY = touch.clientY;
      const deltaX = Math.abs(currentX - startX);
      const deltaY = Math.abs(currentY - startY);

      if (!isPointerDragging) {
        // Si el dedo se mueve más de 8px antes de cumplirse los 500ms, el usuario está haciendo scroll: cancelar arrastre
        if (deltaX > 8 || deltaY > 8) {
          finishDragSession();
          return;
        }
      }

      if (isPointerDragging) {
        if (e.cancelable) {
          e.preventDefault();
        }
        updateAvatarTransform(currentX, currentY);
        updateSlotPositionWithFlip(currentY);
        checkAutoScroll(currentY);
      }
    };

    const onTouchEnd = (e) => {
      lastTouchTime = Date.now();
      if (pointerMode !== "touch") return;
      if (isPointerDragging && e && e.cancelable) {
        e.preventDefault();
      }
      finishDragSession();
    };

    const onTouchCancel = () => {
      lastTouchTime = Date.now();
      if (pointerMode !== "touch") return;
      finishDragSession();
    };

    container.addEventListener("touchstart", (e) => {
      lastTouchTime = Date.now();
      if (!e.touches || e.touches.length !== 1) return;

      const handle = e.target.closest(".drag-handle, [data-drag-handle]");
      if (!handle) return;

      if (e.target.closest("input, textarea, select, button, label, .modal-btn, .content-modal-menu, .checkbox-switch, a, [contenteditable]")) {
        return;
      }

      const item = handle.closest(".sortable-item");
      if (!item || !container.contains(item)) return;

      pointerMode = "touch";
      activeItem = item;
      startX = e.touches[0].clientX;
      startY = e.touches[0].clientY;
      currentX = startX;
      currentY = startY;
      const allItems = [...container.querySelectorAll(".sortable-item")];
      initialIndex = allItems.indexOf(item);
      isPointerDragging = false;

      // Delay de 500ms (medio segundo) para arrastrar en táctil sin interferir con el scroll
      touchDelayTimeout = setTimeout(() => {
        activateFloatingDrag(activeItem);
        if (navigator.vibrate) navigator.vibrate(50);
      }, 500);

      window.addEventListener("touchmove", onTouchMove, { passive: false });
      window.addEventListener("touchend", onTouchEnd, { passive: true });
      window.addEventListener("touchcancel", onTouchCancel, { passive: true });
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

    if (isContent) {
      const items = Array.from(container.querySelectorAll(".sortable-item"));
      document.querySelectorAll(".preview-widget-container, #preview-widget-container").forEach((widgetWrapper) => {
        const scroller = widgetWrapper.closest(".overflow-y-scroll") ||
          document.querySelector(".user-profile-preview [data-scroll-memory='user-preview'], .user-profile-preview .overflow-y-scroll");
        const prevScroll = scroller ? scroller.scrollTop : 0;

        const targetNodes = [];
        items.forEach((item, newIdx) => {
          const blkId = item.getAttribute("data-block-id");
          let elements = blkId ? Array.from(widgetWrapper.querySelectorAll(`[data-block-id="${blkId}"]`)) : [];
          if (!elements.length) {
            const oldIdx = (oldIndices && oldIndices[newIdx] !== null) ? oldIndices[newIdx] : newIdx;
            elements = Array.from(widgetWrapper.querySelectorAll(`[data-content-index="${oldIdx}"]`));
          }
          elements.forEach((el) => {
            el.setAttribute("data-content-index", String(newIdx));
            targetNodes.push(el);
          });
        });

        // Reordenamiento quirúrgico en el DOM: solo mover nodos que no estén en su posición correcta
        targetNodes.forEach((node, i) => {
          if (widgetWrapper.children[i] !== node) {
            widgetWrapper.insertBefore(node, widgetWrapper.children[i] || null);
          }
        });

        // Preservar la posición exacta de scroll en el preview sin saltos al top
        if (scroller && prevScroll > 0) {
          scroller.scrollTop = prevScroll;
        }
      });
    } else if (isRRSS) {
      document.querySelectorAll(".user-profile-preview").forEach((preview) => {
        const scroller = preview.querySelector("[data-scroll-memory='user-preview'], .overflow-y-scroll");
        const prevScroll = scroller ? scroller.scrollTop : 0;

        // Encontrar TODOS los contenedores de redes sociales en todas las variantes de cabecera
        const allRrssLinks = Array.from(preview.querySelectorAll('[data-link-id^="rrss_"]'));
        const wrappers = new Set(allRrssLinks.map((l) => l.parentElement).filter(Boolean));
        const allItems = Array.from(container.querySelectorAll(".sortable-item"));

        wrappers.forEach((rrssWrapper) => {
          const previewLinks = Array.from(rrssWrapper.querySelectorAll('[data-link-id^="rrss_"]'));
          const targetNodes = [];
          allItems.forEach((item) => {
            const name = (item.getAttribute("data-rrss-name") 
              || item.querySelector('input[name$="[0]"]')?.value 
              || item.querySelector('input[type="hidden"][name*="[0]"]')?.value 
              || "").trim().toLowerCase();
            if (name) {
              const previewLink = previewLinks.find((link) => {
                const linkId = (link.getAttribute("data-link-id") || "").toLowerCase();
                return linkId === `rrss_${name}`;
              });
              if (previewLink) {
                targetNodes.push(previewLink);
              }
            }
          });

          // Reordenamiento quirúrgico: solo mover los que cambiaron de posición
          targetNodes.forEach((node, i) => {
            if (rrssWrapper.children[i] !== node) {
              rrssWrapper.insertBefore(node, rrssWrapper.children[i] || null);
            }
          });
        });

        // Preservar la posición de scroll
        if (scroller && prevScroll > 0) {
          scroller.scrollTop = prevScroll;
        }
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

        // 6. Re-indexar data-index y data-trigger-color
        if (element.hasAttribute("data-index")) {
          element.setAttribute("data-index", String(index));
        }
        const oldTriggerColor = element.getAttribute("data-trigger-color");
        if (oldTriggerColor && /-\d+$/.test(oldTriggerColor)) {
          element.setAttribute("data-trigger-color", oldTriggerColor.replace(/-\d+$/, `-${index}`));
        }
      });
    });

    // 6. Disparar eventos nativos de ordenamiento
    container.dispatchEvent(new CustomEvent("sortableChange", { bubbles: true, detail: { item: draggedItem, container, oldIndices: oldIndices } }));
    document.dispatchEvent(new CustomEvent("sortableUpdated", { bubbles: true, detail: { item: draggedItem, container, oldIndices: oldIndices } }));

    // 7. Bloquear actualización asíncrona al backend (Local-First)
    // El orden ya fue modificado en el DOM y reflejado en el JSON local mediante eventos.
    setTimeout(() => {
      showSyncIndicator(container, draggedItem, "success", "✓ Orden guardado localmente");
    }, 50);
  }

  // API pública global
  window.SortableContent = {
    init: sortableContent,
    initContainer: initSortable,
    expand: expandContentBlock,
    collapse: collapseContentBlock
  };
}
