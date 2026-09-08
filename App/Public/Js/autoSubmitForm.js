/**
 * Componente AutoSubmitForm.
 * 
 * Permite que los formularios con la clase '.auto-submit' se envíen automáticamente
 * en cuanto cualquiera de sus campos de entrada cambie.
 * 
 * Optimizado para preservar elementos multimedia (<video>) en vivo sin re-descargas innecesarias.
 * 
 * @function autoSubmitForm
 * @returns {void}
 */
export function autoSubmitForm() {
  /**
   * Sincroniza dinámicamente la visibilidad de elementos condicionales en el cliente
   * de forma inmediata al cambiar valores de radios/selects.
   *
   * @param {HTMLElement} target Elemento interactuado
   */
  function syncConditionalUI(target) {
    if (!target) return;

    // 1. Alternancia de estilo de fondo (Sólido, Degradado, Video)
    if (target.name === 'style_back') {
      const gradientWrapper = document.getElementById('gradient-direction-wrapper');
      const videoWrapper = document.getElementById('video-controls-wrapper');

      if (target.value === 'gradientUp' || target.value === 'gradientDown') {
        if (gradientWrapper) gradientWrapper.style.display = 'flex';
        if (videoWrapper) videoWrapper.style.display = 'none';
      } else if (target.value === 'solid') {
        if (gradientWrapper) gradientWrapper.style.display = 'none';
        if (videoWrapper) videoWrapper.style.display = 'none';
      } else if (target.value === 'video') {
        if (gradientWrapper) gradientWrapper.style.display = 'none';
        if (videoWrapper) videoWrapper.style.display = 'flex';
      }
    }

    // 2. Alternancia del selector de color de sombra 3
    if (target.name === 'shadow') {
      const shadow3Row = document.getElementById('shadow3-color-row');
      const colorShadow3Row = document.getElementById('color-shadow3-color-row');
      const isShadow3 = target.value === 'shadow-3';

      if (shadow3Row) shadow3Row.style.display = isShadow3 ? 'flex' : 'none';
      if (colorShadow3Row) colorShadow3Row.style.display = isShadow3 ? 'flex' : 'none';
    }

    // 3. Alternancia de separación superior en cabecera voidHero
    if (target.name === 'header') {
      const voidSpaceContainer = document.getElementById('void-space-container');
      if (voidSpaceContainer) {
        voidSpaceContainer.style.display = target.value === 'voidHero' ? 'flex' : 'none';
      }
    }

    // 4. Alternancia de opciones de posición de imagen en bloques de campaña (tamaño del bloque y opacidad de capa)
    if (target.classList && target.classList.contains('campaign-pos-radio')) {
      const idx = target.dataset.index;
      const opacityOpt = document.getElementById(`campaign-opacity-option-${idx}`);
      if (opacityOpt) {
        opacityOpt.style.display = target.value === 'background' ? 'flex' : 'none';
      }
      const sizeWrap = document.getElementById(`campaign-size-wrap-${idx}`);
      if (sizeWrap) {
        sizeWrap.style.display = target.value === 'background' ? 'flex' : 'none';
      }
      if (target.value === 'header') {
        const horizRadio = document.getElementById(`campaign-size-horiz-${idx}`);
        if (horizRadio) {
          horizRadio.checked = true;
        }
        const textPosWrap = document.getElementById(`campaign-text-pos-wrap-${idx}`);
        if (textPosWrap) {
          textPosWrap.style.display = 'none';
        }
      }
    }

    // 4b. Alternancia de opciones de alineación de texto en bloques de campaña (solo visible en cuadrado o vertical)
    if (target.classList && target.classList.contains('campaign-size-radio')) {
      const idx = target.dataset.index;
      const textPosWrap = document.getElementById(`campaign-text-pos-wrap-${idx}`);
      if (textPosWrap) {
        textPosWrap.style.display = target.value === 'horizontal' ? 'none' : 'flex';
      }
    }

    // 5. Alternancia de configuración de contador en bloques de campaña
    if (target.classList && target.classList.contains('campaign-countdown-switch')) {
      const targetId = target.dataset.target;
      if (targetId) {
        const dateWrap = document.getElementById(targetId);
        if (dateWrap) {
          dateWrap.style.display = target.checked ? 'flex' : 'none';
        }
      }
    }

    // 6. Alternancia de campos opcionales (nombre, whatsapp) en bloques de campaña
    if (target.classList && target.classList.contains('campaign-toggle-field-switch')) {
      const targetId = target.dataset.target;
      if (targetId) {
        const fieldWrap = document.getElementById(targetId);
        if (fieldWrap) {
          fieldWrap.style.display = target.checked ? 'flex' : 'none';
        }
      }
    }
  }

  document.addEventListener('change', (e) => {
    const target = e.target;
    if (!target) return;

    // Sincronizar UI condicional de inmediato
    syncConditionalUI(target);

    // Cancelar cualquier debounce pendiente de color para enviar inmediatamente
    if (target.type === 'color' || target.classList.contains('color-picker')) {
      clearTimeout(colorDebounceTimer);
      applyPreviewColorLive(target.name, target.value, target);
      const labelText = target.closest('label')?.querySelector('p, span');
      if (labelText && target.value) {
        labelText.textContent = target.value;
      }
    }

    // Verificar si el elemento pertenece a un formulario con la clase .auto-submit
    const form = target.closest('form.auto-submit');
    if (!form) return;

    // Si el elemento individual tiene la marca para ser ignorado, no enviar
    if (target.hasAttribute('no-auto-submit') || target.classList.contains('no-auto-submit')) {
      return;
    }

    // Excepción para procesamiento diferido (ej. recortar imagen antes de enviar)
    const isDeferred = target.classList.contains('proccess-auto-submit') || target.classList.contains('process-auto-submit');
    if (isDeferred) {
      if (target.type === 'file' && target.files && target.files.length > 0) {
        if (target.dataset.isCropped !== 'true') {
          return;
        }
      }
    }

    // Usar requestSubmit() para simular un submit estándar
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
    } else {
      const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
      form.dispatchEvent(submitEvent);
      if (!submitEvent.defaultPrevented) {
        form.submit();
      }
    }
  });

  // Debounce para auto-submit al escribir en campos de texto, textareas y selectores de color
  let inputDebounceTimer = null;
  let colorDebounceTimer = null;

  document.addEventListener('input', (e) => {
    const target = e.target;
    if (!target) return;

    // Manejo de selectores de color (input de color directo o interacción en popover)
    const isColorInput = (target.tagName === 'INPUT' && (target.type === 'color' || target.classList.contains('color-picker')));
    const isColorPopover = target.closest('.custom-color-picker-popover');

    if (isColorInput || isColorPopover) {
      if (isColorInput) {
        applyPreviewColorLive(target.name, target.value, target);
        const labelText = target.closest('label')?.querySelector('p, span');
        if (labelText && target.value) {
          labelText.textContent = target.value;
        }
      }

      const form = isColorInput ? target.closest('form.auto-submit') : document.querySelector('.remote-content.active form.auto-submit');
      if (form) {
        clearTimeout(colorDebounceTimer);
        colorDebounceTimer = setTimeout(() => {
          if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
          } else {
            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
            form.dispatchEvent(submitEvent);
          }
        }, 400);
      }
      return;
    }

    if (target.tagName !== 'INPUT' && target.tagName !== 'TEXTAREA') return;
    if (target.type === 'file' || target.type === 'checkbox' || target.type === 'radio' || target.type === 'range') return;

    const form = target.closest('form.auto-submit');
    if (!form) return;

    if (target.hasAttribute('no-auto-submit') || target.classList.contains('no-auto-submit')) return;

    clearTimeout(inputDebounceTimer);
    inputDebounceTimer = setTimeout(() => {
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
        form.dispatchEvent(submitEvent);
      }
    }, 600);
  });

  // Interceptar envíos de formularios .auto-submit o data-fetch-preview para enviar por Fetch y recargar en vivo
  document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!form || !form.matches('form.auto-submit, form[data-fetch-preview]')) return;

    // Evitar la recarga normal de la página
    e.preventDefault();

    // Cerrar cualquier modal abierto al procesar el envío
    document.querySelectorAll('.modal-overlay').forEach(modal => modal.remove());

    try {
      const submitter = e.submitter;
      const formData = submitter ? new FormData(form, submitter) : new FormData(form);

      if (submitter && submitter.name && !formData.has(submitter.name)) {
        formData.append(submitter.name, submitter.value || 'true');
      }

      const action = form.action || window.location.href;

      const response = await fetch(action, {
        method: form.method || 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!response.ok) {
        console.error('Error en envío AJAX:', response.statusText);
        return;
      }

      const data = await response.json();

      if (data && data.success) {
        // 1. Actualizar el banner de estado del perfil en el sidebar (escritorio y móvil)
        if (data.sidebarStatusHtml) {
          document.querySelectorAll('.sidebar-profile-status').forEach((el) => {
            el.innerHTML = data.sidebarStatusHtml;
          });
        }

        // 2. Actualizar el panel de estadísticas si la respuesta contiene statsHtml
        if (data.statsHtml) {
          const statsRemote = document.getElementById('statistics-remote');
          if (statsRemote) {
            statsRemote.innerHTML = data.statsHtml;
          }
        }

        // 3. Actualizar la vista previa (.user-profile-preview) preservando el video existente
        if (data.html) {
          const previewContainers = document.querySelectorAll('.user-profile-preview');
          previewContainers.forEach((container) => {
            const temp = document.createElement('div');
            temp.innerHTML = data.html.trim();
            const targetPreview = temp.querySelector('.user-profile-preview') || temp.firstElementChild;
            if (!targetPreview) return;

            const currentVideo = container.querySelector('video.back-video-bg');
            const newVideo = targetPreview.querySelector('video.back-video-bg');

            const currentSrc = currentVideo?.querySelector('source')?.getAttribute('src') || currentVideo?.getAttribute('src');
            const newSrc = newVideo?.querySelector('source')?.getAttribute('src') || newVideo?.getAttribute('src');

            // Reutilizar el nodo <video> en memoria para no re-descargar el archivo MP4 si es el mismo
            if (currentVideo && newVideo && currentSrc && newSrc && currentSrc === newSrc) {
              newVideo.replaceWith(currentVideo);
            }

            if (container.parentNode) {
              container.parentNode.replaceChild(targetPreview, container);
            } else {
              container.innerHTML = data.html;
            }
          });
        }

        // 4. Si la respuesta incluye formHtml, re-renderizar la sección activa del formulario
        if (data.formHtml) {
          const activeEl = document.activeElement;
          const textInputTypes = ['text', 'search', 'url', 'email', 'password', 'number', 'tel'];
          const isTypingInForm = activeEl && (
            (activeEl.tagName === 'INPUT' && textInputTypes.includes((activeEl.type || 'text').toLowerCase())) ||
            activeEl.tagName === 'TEXTAREA'
          ) && form.contains(activeEl);

          const activeRemoteContent = form.closest('.remote-content') || document.querySelector('.remote-content.active');
          const isColorRemote = activeRemoteContent && activeRemoteContent.id === 'color-remote';
          const isColorPickerOpen = document.querySelector('.custom-color-picker-popover') !== null;

          if (!isTypingInForm && !isColorRemote && !isColorPickerOpen) {
            if (activeRemoteContent && activeRemoteContent.id) {
              // Preservar bloques de contenido abiertos antes de reemplazar HTML
              const openBlockIds = new Set();
              const storedActiveId = sessionStorage.getItem('active_content_block_id');
              if (storedActiveId) openBlockIds.add(storedActiveId);
              activeRemoteContent.querySelectorAll('.sortable-item.content-block.is-open').forEach((el) => {
                if (el.id) openBlockIds.add(el.id);
              });
              const savedScrollTop = activeRemoteContent.scrollTop;

              const tempForm = document.createElement('div');
              tempForm.innerHTML = data.formHtml.trim();
              const matchingNewContent = tempForm.querySelector('#' + CSS.escape(activeRemoteContent.id));
              if (matchingNewContent) {
                activeRemoteContent.innerHTML = matchingNewContent.innerHTML;

                // Restaurar estado abierto de los bloques de contenido
                openBlockIds.forEach((id) => {
                  const item = activeRemoteContent.querySelector('#' + CSS.escape(id));
                  if (item) {
                    item.classList.remove('is-collapsed');
                    item.classList.add('is-open');
                    const body = item.querySelector('.content-item-body');
                    if (body) {
                      body.style.display = 'flex';
                      body.style.height = 'auto';
                      body.style.opacity = '1';
                      body.style.overflow = 'visible';
                    }
                  }
                });
                activeRemoteContent.scrollTop = savedScrollTop;
              }
            }
          }
        }

        // 5. Inicializar contadores regresivos en la nueva vista previa
        initCampaignCountdowns();

        // 6. Disparar evento personalizado para sincronizar otros componentes
        document.dispatchEvent(new CustomEvent('previewUpdated', { detail: data }));
      }
    } catch (error) {
      console.error('Error al procesar el formulario con fetch:', error);
    }
  });

  // Inicializar contadores en carga inicial y registrar observadores
  initCampaignCountdowns();
  document.addEventListener('previewUpdated', () => initCampaignCountdowns());
  window.addEventListener('pageshow', () => initCampaignCountdowns());
}

/**
 * Inicializa contadores regresivos dinámicos en los bloques de campaña
 * para que funcionen tanto en carga inicial como en actualizaciones AJAX de vista previa.
 *
 * @function initCampaignCountdowns
 * @returns {void}
 */
export function initCampaignCountdowns() {
  document.querySelectorAll('[data-countdown]').forEach((box) => {
    if (box.dataset.countdownActive === 'true') return;
    box.dataset.countdownActive = 'true';

    const targetStr = box.getAttribute('data-countdown');
    if (!targetStr) return;
    const target = new Date(targetStr).getTime();
    if (isNaN(target)) return;

    const wrapper = box.closest('.campaign-block-wrapper');

    function updateCountdown() {
      const now = new Date().getTime();
      const diff = target - now;

      if (diff <= 0) {
        if (box.__countdownTimer) clearInterval(box.__countdownTimer);
        if (wrapper) wrapper.style.display = 'none';
        return;
      }

      const days = Math.floor(diff / (1000 * 60 * 60 * 24));
      const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((diff % (1000 * 60)) / 1000);

      const dEl = box.querySelector('.countdown-days');
      const hEl = box.querySelector('.countdown-hours');
      const mEl = box.querySelector('.countdown-minutes');
      const sEl = box.querySelector('.countdown-seconds');

      if (dEl) dEl.textContent = String(days).padStart(2, '0');
      if (hEl) hEl.textContent = String(hours).padStart(2, '0');
      if (mEl) mEl.textContent = String(minutes).padStart(2, '0');
      if (sEl) sEl.textContent = String(seconds).padStart(2, '0');
    }

    updateCountdown();
    box.__countdownTimer = setInterval(updateCountdown, 1000);
  });
}

/**
 * Aplica estilos de color en tiempo real a la vista previa del perfil (.user-profile-preview)
 * con especificidad máxima (!important) para reflejar cambios instantáneos sin esperar la respuesta AJAX.
 *
 * @function applyPreviewColorLive
 * @param {string} name Nombre del campo de formulario
 * @param {string} hex Código hexadecimal del color (#rrggbb)
 * @param {HTMLElement} [inputElement] Elemento input origen opcional
 * @returns {void}
 */
export function applyPreviewColorLive(name, hex, inputElement = null) {
  if (!name || !hex) return;
  const preview = document.querySelector('.user-profile-preview');
  if (!preview) return;

  // 1. Color de fondo general del perfil
  if (name === 'back_perfil') {
    preview.querySelectorAll('.back-card').forEach((el) => {
      el.style.setProperty('background', hex, 'important');
      el.style.setProperty('background-color', hex, 'important');
    });
    preview.querySelectorAll('.back-card-container').forEach((el) => {
      el.style.setProperty('background', hex, 'important');
      el.style.setProperty('background-color', hex, 'important');
    });
  }

  // 2. Color de texto general
  else if (name === 'colorText') {
    preview.querySelectorAll('.color-text-card').forEach((el) => {
      el.style.setProperty('color', hex, 'important');
    });
    preview.querySelectorAll('.desc-hero-regular, .desc-hero-big, .desc-hero-mini').forEach((el) => {
      el.style.setProperty('color', hex, 'important');
    });
    preview.querySelectorAll('.color-text-card p, .color-text-card span').forEach((el) => {
      if (!el.closest('.theme-button') && !el.closest('.title-color') && !el.closest('h1, h2, h3, h4')) {
        el.style.setProperty('color', hex, 'important');
      }
    });
  }

  // 3. Color del título principal
  else if (name === 'titleColor') {
    preview.querySelectorAll('.title-color, .title-hero-regular, .title-hero-big, .title-hero-mini, h1, h2, h3').forEach((el) => {
      el.style.setProperty('color', hex, 'important');
    });
  }

  // 4. Color de fondo de los botones generales
  else if (name === 'back') {
    preview.querySelectorAll('.theme-button').forEach((el) => {
      el.style.setProperty('background-color', hex, 'important');
    });
  }

  // 5. Color de texto de los botones generales
  else if (name === 'color') {
    preview.querySelectorAll('.theme-button').forEach((el) => {
      el.style.setProperty('color', hex, 'important');
    });
    preview.querySelectorAll('.theme-button *, .theme-icon').forEach((el) => {
      el.style.setProperty('color', hex, 'important');
    });
  }

  // 6. Color de la sombra shadow-3
  else if (name === 'colorShadow3') {
    preview.querySelectorAll('.shadow-3').forEach((el) => {
      el.style.setProperty('border-color', hex, 'important');
      el.style.setProperty('box-shadow', `3px 5px 0px ${hex}`, 'important');
    });
  }

  // 7. Colores específicos de bloque de campaña
  else if (name.includes('title_color')) {
    const idx = inputElement?.dataset?.index;
    const targets = idx !== undefined 
      ? preview.querySelectorAll(`.campaign-title[data-index="${idx}"]`)
      : preview.querySelectorAll('.campaign-title');
    targets.forEach((el) => el.style.setProperty('color', hex, 'important'));
  }
  else if (name.includes('desc_color')) {
    const idx = inputElement?.dataset?.index;
    const targets = idx !== undefined 
      ? preview.querySelectorAll(`.campaign-desc[data-index="${idx}"]`)
      : preview.querySelectorAll('.campaign-desc');
    targets.forEach((el) => el.style.setProperty('color', hex, 'important'));
  }
  else if (name.includes('btn_bg_color')) {
    const idx = inputElement?.dataset?.index;
    const targets = idx !== undefined 
      ? preview.querySelectorAll(`.campaign-button[data-index="${idx}"]`)
      : preview.querySelectorAll('.campaign-button');
    targets.forEach((el) => el.style.setProperty('background-color', hex, 'important'));
  }
  else if (name.includes('btn_text_color')) {
    const idx = inputElement?.dataset?.index;
    const targets = idx !== undefined 
      ? preview.querySelectorAll(`.campaign-button[data-index="${idx}"]`)
      : preview.querySelectorAll('.campaign-button');
    targets.forEach((el) => el.style.setProperty('color', hex, 'important'));
  }
  else if (name.includes('countdown_bg_color')) {
    const idx = inputElement?.dataset?.index;
    const targets = idx !== undefined 
      ? preview.querySelectorAll(`.campaign-countdown-wrapper[data-index="${idx}"], [data-countdown][data-index="${idx}"]`)
      : preview.querySelectorAll('.campaign-countdown-wrapper, [data-countdown]');
    targets.forEach((el) => el.style.setProperty('background-color', hex, 'important'));
  }
  else if (name.includes('countdown_text_color')) {
    const idx = inputElement?.dataset?.index;
    const targets = idx !== undefined 
      ? preview.querySelectorAll(`.campaign-countdown-wrapper[data-index="${idx}"] *, [data-countdown][data-index="${idx}"] *`)
      : preview.querySelectorAll('.campaign-countdown-wrapper *, [data-countdown] *');
    targets.forEach((el) => el.style.setProperty('color', hex, 'important'));
  }
}
