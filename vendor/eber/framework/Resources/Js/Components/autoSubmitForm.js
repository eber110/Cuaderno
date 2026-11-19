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
   * Comprueba si el valor actual de un control coincide con la lista permitida de data-show-when.
   *
   * @param {HTMLElement} control Elemento controlador
   * @param {string[]} allowed Valores permitidos
   * @returns {boolean}
   */
  function matchesAllowedValue(control, allowed) {
    if (!control) return false;
    if (control.type === 'checkbox') {
      if (control.checked) {
        const val = String(control.value ?? 'on');
        return allowed.includes(val) || allowed.includes('1') || allowed.includes('true') || allowed.includes('on');
      }
      return allowed.includes('0') || allowed.includes('false') || allowed.includes('');
    }
    return allowed.includes(String(control.value ?? ''));
  }

  /**
   * Evalúa un único elemento con atributo data-show-when dentro de su formulario o del documento.
   *
   * @param {HTMLElement} el Elemento con data-show-when
   * @param {Document|HTMLElement} [scope] Ámbito de búsqueda
   */
  function syncShowWhenElement(el, scope) {
    if (!el || !el.hasAttribute('data-show-when')) return;
    const expr = el.getAttribute('data-show-when') || '';
    const eqIdx = expr.indexOf('=');
    if (eqIdx === -1) return;

    const fieldName = expr.slice(0, eqIdx).trim();
    if (!fieldName) return;
    const allowed = expr.slice(eqIdx + 1).split(',').map((s) => s.trim());

    const formScope = scope || el.closest('form') || document;
    const controls = Array.from(formScope.querySelectorAll(`[name="${CSS.escape(fieldName)}"]`));
    if (controls.length === 0) return;

    let visible = false;
    const radios = controls.filter((c) => c.type === 'radio');
    if (radios.length > 0) {
      const checkedRadio = radios.find((r) => r.checked);
      visible = checkedRadio ? allowed.includes(String(checkedRadio.value ?? '')) : allowed.includes('');
    } else {
      visible = controls.some((ctrl) => matchesAllowedValue(ctrl, allowed));
    }

    el.style.display = visible ? '' : 'none';
  }

  /**
   * Inicializa y evalúa todos los elementos [data-show-when] presentes en el árbol DOM.
   *
   * @param {Document|HTMLElement} [root=document] Contenedor raíz
   */
  function initShowWhen(root = document) {
    const scopeRoot = root && typeof root.querySelectorAll === 'function' ? root : document;
    if (scopeRoot instanceof Element && scopeRoot.hasAttribute('data-show-when')) {
      syncShowWhenElement(scopeRoot);
    }
    scopeRoot.querySelectorAll('[data-show-when]').forEach((el) => {
      syncShowWhenElement(el);
    });
  }

  /**
   * Indica si un control o su contenedor inmediato está marcado para excluir del auto-submit.
   *
   * @param {HTMLElement} target Elemento originador del evento
   * @param {HTMLFormElement|null} form Formulario contenedor
   * @returns {boolean}
   */
  function isAutoSubmitIgnored(target, form) {
    if (!target) return false;
    if (
      target.hasAttribute('no-auto-submit') ||
      target.getAttribute('data-no-auto-submit') === 'true' ||
      target.classList.contains('no-auto-submit')
    ) {
      return true;
    }
    const ancestor = target.closest('[data-no-auto-submit="true"], [no-auto-submit], .no-auto-submit');
    return Boolean(ancestor && ancestor !== form && (!form || form.contains(ancestor)));
  }

  /**
   * Determina si un formulario multi-paso está en un paso intermedio (no el último),
   * bloqueando el auto-submit prematuro hasta completar el último paso.
   *
   * @param {HTMLFormElement|null} form Formulario contenedor
   * @returns {boolean}
   */
  function isBlockedByMultiStep(form) {
    if (!form || !form.matches('form[data-multi-step="true"]')) return false;
    const steps = Array.from(form.querySelectorAll('.ui-form-step'));
    if (steps.length <= 1) return false;
    const activeStep = form.querySelector('.ui-form-step.is-active') || steps.find((s) => !s.hasAttribute('hidden'));
    if (!activeStep) return false;
    const activeIdx = steps.indexOf(activeStep);
    return activeIdx !== -1 && activeIdx < steps.length - 1;
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
      const parentForm = target.closest('form');
      const searchScope = parentForm || document;
      searchScope.querySelectorAll('[data-show-when]').forEach((el) => {
        const expr = el.getAttribute('data-show-when') || '';
        const eqIdx = expr.indexOf('=');
        if (eqIdx === -1) return;
        const fieldName = expr.slice(0, eqIdx).trim();
        if (fieldName !== target.name) return;
        syncShowWhenElement(el, parentForm || document);
      });
    }
  }

  initShowWhen(document);

  if (document.documentElement.dataset.fmeAutoSubmitBound === '1') {
    return;
  }
  document.documentElement.dataset.fmeAutoSubmitBound = '1';

  if (typeof window !== 'undefined' && window.FmeUi && typeof window.FmeUi.observeDynamic === 'function') {
    window.FmeUi.observeDynamic(() => {
      initShowWhen(document);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initShowWhen(document));
  }
  document.addEventListener('previewUpdated', () => initShowWhen(document));
  document.addEventListener('ui:stepChanged', () => initShowWhen(document));

  document.addEventListener('change', (e) => {
    const target = e.target;
    if (!target) return;
    if (target.closest('.remote-container')) return;

    // Sincronizar UI condicional de inmediato
    syncConditionalUI(target);

    // Verificar si el elemento pertenece a un formulario con la clase .auto-submit
    const form = target.closest('form.auto-submit');
    if (!form) return;

    // En formularios multi-paso, no auto-enviar si no estamos en el último paso
    if (isBlockedByMultiStep(form)) return;

    // Si el elemento individual tiene la marca para ser ignorado, no enviar
    if (isAutoSubmitIgnored(target, form)) {
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

    syncConditionalUI(target);

    const form = target.closest('form.auto-submit');
    if (!form) return;

    if (isBlockedByMultiStep(form)) return;

    if (isAutoSubmitIgnored(target, form)) return;

    clearTimeout(inputDebounceTimer);
    inputDebounceTimer = setTimeout(() => {
      if (isBlockedByMultiStep(form)) return;
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

    // Modo simulación segura dentro del diseñador visual de Theme Studio
    if (form.dataset.studioPreview === 'true') {
      document.dispatchEvent(new CustomEvent('studio:autoSubmitSimulated', { detail: { form } }));
      return;
    }

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
