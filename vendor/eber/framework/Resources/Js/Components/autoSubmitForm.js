/**
 * Componente AutoSubmitForm.
 * 
 * Permite que los formularios con la clase '.auto-submit' se envíen automáticamente
 * en cuanto cualquiera de sus campos de entrada (select, color, radio, checkbox, etc.) cambie.
 * 
 * @function autoSubmitForm
 * @description Escucha los eventos 'change' en formularios .auto-submit y dispara el envío.
 * 
 * @example
 * <form class="auto-submit" action="/filtrar" method="POST">
 *   <input type="color" name="color_tema"> <!-- Se envía inmediatamente al elegir color -->
 *   <select name="ordenar">               <!-- Se envía inmediatamente al cambiar opción -->
 *      <option value="reciente">Reciente</option>
 *   </select>
 * </form>
 * 
 * @returns {void}
 */
export function autoSubmitForm() {
  // Ignorar completamente dentro del panel de usuario para evitar colisiones con el gestor de diseño local
  if (typeof window !== 'undefined' && window.location.pathname.startsWith('/panel/')) {
    return;
  }

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

    // 4. Visibilidad condicional declarativa universal: data-show-when="campo=val1,val2"
    if (target.name) {
      document.querySelectorAll('[data-show-when]').forEach((el) => {
        const expr = el.getAttribute('data-show-when') || '';
        const eqIdx = expr.indexOf('=');
        if (eqIdx === -1) return;
        const fieldName = expr.slice(0, eqIdx).trim();
        if (fieldName !== target.name) return;
        const allowed = expr.slice(eqIdx + 1).split(',').map((s) => s.trim());
        el.style.display = allowed.includes(String(target.value ?? '')) ? '' : 'none';
      });
    }
  }

  document.addEventListener('change', (e) => {
    const target = e.target;
    if (!target) return;
    if (target.closest('.remote-container')) return;

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
    if (target.closest('.remote-container')) return;
    if (target.tagName !== 'INPUT' && target.tagName !== 'TEXTAREA') return;
    if (
      target.type === 'file' ||
      target.type === 'checkbox' ||
      target.type === 'radio' ||
      target.type === 'color' ||
      target.type === 'range' ||
      target.classList.contains('color-picker') ||
      target.classList.contains('range-slider') ||
      target.classList.contains('date-picker') ||
      target.classList.contains('time-picker') ||
      target.classList.contains('tags-input') ||
      target.classList.contains('otp-input') ||
      target.closest('.custom-color-picker-popover') ||
      target.closest('.ui-dp-popover')
    ) return;

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
    if (form.closest('.remote-container')) return;

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

      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
      const requestHeaders = {
        'X-Requested-With': 'XMLHttpRequest'
      };
      if (csrfToken) {
        requestHeaders['X-CSRF-TOKEN'] = csrfToken;
      }

      const sanitizeHtmlFragment = (rawHtml) => {
        const doc = new DOMParser().parseFromString(String(rawHtml ?? ''), 'text/html');
        doc.querySelectorAll('script, iframe, object, embed, base, meta').forEach((node) => node.remove());
        doc.querySelectorAll('*').forEach((el) => {
          Array.from(el.attributes).forEach((attr) => {
            const name = attr.name.toLowerCase();
            const val = attr.value.trim().toLowerCase();
            if (name.startsWith('on') || ((name === 'href' || name === 'src' || name === 'action') && (val.startsWith('javascript:') || val.startsWith('data:text/html')))) {
              el.removeAttribute(attr.name);
            }
          });
        });
        return doc.body;
      };

      const response = await fetch(action, {
        method: form.method || 'POST',
        body: formData,
        headers: requestHeaders
      });

      if (!response.ok) {
        console.error('Error en envío AJAX:', response.statusText);
        return;
      }

      const data = await response.json();

      if (data && data.success) {
        // 1. Actualizar el banner de estado del perfil en el sidebar (escritorio y móvil)
        if (data.sidebarStatusHtml) {
          const cleanBody = sanitizeHtmlFragment(data.sidebarStatusHtml);
          document.querySelectorAll('.sidebar-profile-status').forEach((el) => {
            el.replaceChildren(...Array.from(cleanBody.cloneNode(true).childNodes));
          });
        }

        // 2. Actualizar el panel de estadísticas si la respuesta contiene statsHtml
        if (data.statsHtml) {
          const statsRemote = document.getElementById('statistics-remote');
          if (statsRemote) {
            const cleanBody = sanitizeHtmlFragment(data.statsHtml);
            statsRemote.replaceChildren(...Array.from(cleanBody.childNodes));
          }
        }

        // 3. Actualizar la vista previa (.user-profile-preview) sin recargar
        if (data.html) {
          const cleanBody = sanitizeHtmlFragment(data.html.trim());
          const previewContainers = document.querySelectorAll('.user-profile-preview');
          previewContainers.forEach((container) => {
            const clone = cleanBody.cloneNode(true);
            const targetPreview = clone.querySelector('.user-profile-preview') || clone.firstElementChild;
            if (targetPreview && container.parentNode) {
              container.parentNode.replaceChild(targetPreview, container);
            } else {
              container.replaceChildren(...Array.from(clone.childNodes));
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
              const cleanBody = sanitizeHtmlFragment(data.formHtml.trim());
              const matchingNewContent = cleanBody.querySelector('#' + CSS.escape(activeRemoteContent.id));
              if (matchingNewContent) {
                activeRemoteContent.replaceChildren(...Array.from(matchingNewContent.cloneNode(true).childNodes));
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
