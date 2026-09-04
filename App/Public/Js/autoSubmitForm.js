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

    // 4. Alternancia de opciones de fondo en bloques de campaña
    if (target.classList && target.classList.contains('campaign-pos-radio')) {
      const idx = target.dataset.index;
      const bgOpts = document.getElementById(`campaign-bg-options-${idx}`);
      if (bgOpts) {
        bgOpts.style.display = target.value === 'background' ? 'flex' : 'none';
      }
    }

    // 5. Alternancia de fecha límite de contador en bloques de campaña
    if (target.classList && target.classList.contains('campaign-countdown-switch')) {
      const targetId = target.dataset.target;
      if (targetId) {
        const dateWrap = document.getElementById(targetId);
        if (dateWrap) {
          dateWrap.style.display = target.checked ? 'flex' : 'none';
        }
      }
    }
  }

  document.addEventListener('change', (e) => {
    const target = e.target;
    if (!target) return;

    // Sincronizar UI condicional de inmediato
    syncConditionalUI(target);

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

  // Debounce para auto-submit al escribir en campos de texto y textareas
  let inputDebounceTimer = null;
  document.addEventListener('input', (e) => {
    const target = e.target;
    if (!target) return;
    if (target.tagName !== 'INPUT' && target.tagName !== 'TEXTAREA') return;
    if (target.type === 'file' || target.type === 'checkbox' || target.type === 'radio' || target.type === 'color' || target.type === 'range' || target.classList.contains('color-picker') || target.closest('.custom-color-picker-popover')) return;

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
          const isTypingInForm = activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA') && form.contains(activeEl);

          if (!isTypingInForm) {
            const activeRemoteContent = form.closest('.remote-content') || document.querySelector('.remote-content.active');
            if (activeRemoteContent && activeRemoteContent.id) {
              const tempForm = document.createElement('div');
              tempForm.innerHTML = data.formHtml.trim();
              const matchingNewContent = tempForm.querySelector('#' + CSS.escape(activeRemoteContent.id));
              if (matchingNewContent) {
                activeRemoteContent.innerHTML = matchingNewContent.innerHTML;
              }
            }
          }
        }

        // 5. Disparar evento personalizado para sincronizar otros componentes
        document.dispatchEvent(new CustomEvent('previewUpdated', { detail: data }));
      }
    } catch (error) {
      console.error('Error al procesar el formulario con fetch:', error);
    }
  });
}
