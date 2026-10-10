/**
 * Componente AutoSubmitForm.
 * 
 * Permite que los formularios con la clase '.auto-submit' se envíen automáticamente
 * en cuanto cualquiera de sus campos de entrada (select, color, radio, checkbox, etc.) cambie.
 * 
 * @function autoSubmitForm
 * @description Escucha los eventos 'change' en formularios .auto-submit y dispara el envío.
 * 
 * @returns {void}
 */
export function autoSubmitForm() {
  // Ignorar completamente dentro del panel de usuario para evitar colisiones con el gestor de diseño local
  if (window.location.pathname.startsWith('/panel/')) {
    initCampaignCountdowns();
    return;
  }

  /**
   * Alterna la visibilidad de un elemento de interfaz asegurando que venza
   * reglas CSS responsivas con !important (como .flex-column-sml en teléfonos).
   *
   * @param {HTMLElement|null} element Elemento a mostrar u ocultar.
   * @param {boolean} visible True para mostrar, false para ocultar.
   * @param {string} [displayType="flex"] Tipo de display al mostrar.
   */
  function setElementVisibility(element, visible, displayType = "flex") {
    if (!element) return;
    if (visible) {
      element.classList.remove("hidden");
      element.style.setProperty("display", displayType, "important");
    } else {
      element.classList.add("hidden");
      element.style.setProperty("display", "none", "important");
    }
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
      const isGrad = target.value === 'gradientUp' || target.value === 'gradientDown';
      const isVid = target.value === 'video';

      setElementVisibility(gradientWrapper, isGrad);
      setElementVisibility(videoWrapper, isVid);
    }

    // 2. Alternancia del selector de color de sombra 3
    if (target.name === 'shadow') {
      const shadow3Row = document.getElementById('shadow3-color-row');
      const colorShadow3Row = document.getElementById('color-shadow3-color-row');
      const isShadow3 = target.value === 'shadow-3';

      setElementVisibility(shadow3Row, isShadow3);
      setElementVisibility(colorShadow3Row, isShadow3);
    }

    // 3. Alternancia de separación superior en cabecera voidHero
    if (target.name === 'header') {
      const voidSpaceContainer = document.getElementById('void-space-container');
      setElementVisibility(voidSpaceContainer, target.value === 'voidHero');
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

  // Interceptar envíos de formularios .auto-submit o data-fetch-preview para enviar por Fetch
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
        // Disparar evento personalizado para sincronizar otros componentes
        document.dispatchEvent(new CustomEvent('previewUpdated', { detail: data }));
      }
    } catch (error) {
      console.error('Error al procesar el formulario con fetch:', error);
    }
  });

  initCampaignCountdowns();
}

/**
 * Inicializa contadores regresivos dinámicos en los bloques de campaña
 * para que funcionen tanto en carga inicial como en actualizaciones de vista previa.
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
