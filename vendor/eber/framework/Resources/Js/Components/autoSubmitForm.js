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
  document.addEventListener('change', (e) => {
    const target = e.target;
    if (!target) return;

    // Verificar si el elemento pertenece a un formulario con la clase .auto-submit
    const form = target.closest('form.auto-submit');
    if (!form) return;

    // Si el elemento individual tiene la marca para ser ignorado, no enviar
    if (target.hasAttribute('no-auto-submit') || target.classList.contains('no-auto-submit')) {
      return;
    }

    // Excepción para procesamiento diferido (ej. recortar imagen antes de enviar con la clase proccess-auto-submit o process-auto-submit)
    const isDeferred = target.classList.contains('proccess-auto-submit') || target.classList.contains('process-auto-submit');
    if (isDeferred) {
      // Si es un selector de archivos y tiene un archivo seleccionado, pero aún no ha sido recortado/procesado, pausar envío
      if (target.type === 'file' && target.files && target.files.length > 0) {
        if (target.dataset.isCropped !== 'true') {
          return; // No enviar todavía, esperar a que el usuario confirme el recorte
        }
      }
    }

    // Usar requestSubmit() para simular un submit estándar (con validaciones de HTML5 y eventos de submit)
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
    } else {
      // Fallback para navegadores antiguos
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

  // Interceptar envíos de formularios .auto-submit o data-fetch-preview para enviar por Fetch y recargar el preview en vivo sin recargar la página
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
        if (data.html) {
          // Actualizar los contenedores de vista previa (UserPreview) sin parpadear ni recargar
          const previewContainers = document.querySelectorAll('.user-profile-preview');
          previewContainers.forEach((container) => {
            const temp = document.createElement('div');
            temp.innerHTML = data.html.trim();
            const targetPreview = temp.querySelector('.user-profile-preview') || temp.firstElementChild;
            if (targetPreview && container.parentNode) {
              container.parentNode.replaceChild(targetPreview, container);
            } else {
              container.innerHTML = data.html;
            }
          });
        }

        // Si la respuesta incluye formHtml, re-renderizar la sección activa del formulario
        // pero NO si el usuario está actualmente escribiendo en un input/textarea dentro del formulario
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

        // Disparar evento personalizado para otros componentes
        document.dispatchEvent(new CustomEvent('previewUpdated', { detail: data }));
      }
    } catch (error) {
      console.error('Error al procesar el formulario con fetch:', error);
    }
  });
}
