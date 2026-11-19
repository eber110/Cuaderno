/**
 * Componente MultiStepForm.
 *
 * Convierte cualquier <form data-multi-step="true"> con paneles .ui-form-step
 * en un asistente paso a paso con navegación por puntos, barra de progreso,
 * validación por paso en cliente y soporte opcional de validación remota AJAX.
 *
 * @function multiStepForm
 * @description Inicializa formularios multi-paso dinámicos de Eber Framework.
 *
 * @example
 * <form class="ui-form" data-multi-step="true" action="/registro" method="POST">
 *   <div class="ui-steps-nav">...</div>
 *   <div class="ui-step-progress-bar"><div class="ui-step-progress-fill"></div></div>
 *   <section class="ui-form-step is-active" data-step-index="0">...</section>
 *   <section class="ui-form-step" data-step-index="1" hidden>...</section>
 * </form>
 *
 * @returns {void}
 */
export function multiStepForm() {
  initMultiStepForms();
}

/**
 * Busca e inicializa formularios multi-paso presentes y futuros en el DOM.
 *
 * @returns {void}
 */
export function initMultiStepForms() {
  const scanAll = () => {
    document.querySelectorAll('form[data-multi-step="true"]').forEach((form) => {
      bindMultiStepForm(form);
    });
  };

  if (typeof window !== 'undefined' && window.FmeUi && typeof window.FmeUi.observeDynamic === 'function') {
    if (!document.documentElement.dataset.fmeMultiStepObserved) {
      document.documentElement.dataset.fmeMultiStepObserved = '1';
      window.FmeUi.observeDynamic(scanAll);
      return;
    }
  }
  scanAll();
}

/**
 * Conecta eventos, estado y validaciones a una instancia de formulario multi-paso.
 *
 * @param {HTMLFormElement} form Formulario multi-paso
 */
function bindMultiStepForm(form) {
  if (!form || form.dataset.fmeMultiStepBound === '1') return;
  const steps = Array.from(form.querySelectorAll('.ui-form-step'));
  if (steps.length === 0) return;

  form.dataset.fmeMultiStepBound = '1';

  const dots = Array.from(form.querySelectorAll('.ui-step-dot'));
  const progressFill = form.querySelector('.ui-step-progress-fill');

  let currentIndex = steps.findIndex((s) => s.classList.contains('is-active') && !s.hasAttribute('hidden'));
  if (currentIndex < 0) currentIndex = 0;

  /**
   * Comprueba si un elemento está dentro de un bloque oculto por data-show-when.
   *
   * @param {HTMLElement} el Elemento a evaluar
   * @returns {boolean}
   */
  function isHiddenByCondition(el) {
    let cursor = el;
    while (cursor && cursor !== form) {
      if (cursor.hasAttribute && cursor.hasAttribute('data-show-when') && cursor.style.display === 'none') {
        return true;
      }
      cursor = cursor.parentElement;
    }
    return false;
  }

  /**
   * Pinta un mensaje de error debajo del campo correspondiente.
   *
   * @param {HTMLElement} stepEl Paso actual
   * @param {HTMLElement|null} control Control con error
   * @param {string} message Mensaje descriptivo
   */
  function renderFieldError(stepEl, control, message) {
    const container =
      (control && (control.closest('.ui-field-group') || control.closest('.ui-field-card') || control.parentElement)) ||
      stepEl;
    if (!container) return;

    const existing = container.querySelector('.ui-field-error[data-step-error-msg="1"]');
    if (existing) {
      existing.textContent = message;
      return;
    }

    const msgEl = document.createElement('small');
    msgEl.className = 'ui-field-error';
    msgEl.dataset.stepErrorMsg = '1';
    msgEl.textContent = message;
    container.appendChild(msgEl);
  }

  /**
   * Muestra un banner general de error dentro del paso actual.
   *
   * @param {HTMLElement} stepEl Paso actual
   * @param {string} message Mensaje de error
   */
  function renderStepBanner(stepEl, message) {
    if (!stepEl || !message) return;
    let banner = stepEl.querySelector('.ui-step-error-banner');
    if (!banner) {
      banner = document.createElement('div');
      banner.className = 'ui-step-error-banner';
      banner.setAttribute('role', 'alert');
      const actions = stepEl.querySelector('.ui-step-actions');
      if (actions && actions.parentNode === stepEl) {
        stepEl.insertBefore(banner, actions);
      } else {
        stepEl.appendChild(banner);
      }
    }
    banner.textContent = message;
  }

  /**
   * Limpia mensajes de error previos del paso indicado.
   *
   * @param {HTMLElement} stepEl Paso objetivo
   */
  function clearStepErrors(stepEl) {
    if (!stepEl) return;
    stepEl.querySelectorAll('.ui-field-error[data-step-error-msg="1"]').forEach((el) => el.remove());
    stepEl.querySelectorAll('.ui-step-error-banner').forEach((el) => el.remove());
    if (dots[currentIndex]) {
      dots[currentIndex].classList.remove('is-error');
    }
  }

  /**
   * Valida en cliente únicamente los controles activos y visibles del paso actual.
   *
   * @param {HTMLElement} stepEl Paso a validar
   * @returns {boolean}
   */
  function validateStepClient(stepEl) {
    clearStepErrors(stepEl);
    let firstInvalid = null;
    const checkedRadioGroups = new Set();

    // 1. Validación específica de grupos OTP si el input oculto es obligatorio
    stepEl.querySelectorAll('.ui-otp-group').forEach((group) => {
      if (isHiddenByCondition(group)) return;
      const hidden = group.querySelector('input[type="hidden"]');
      const cells = Array.from(group.querySelectorAll('.ui-otp-cell'));
      const required = Boolean(
        (hidden && (hidden.hasAttribute('required') || hidden.getAttribute('data-ui-required') === 'true')) ||
        cells.some((c) => c.hasAttribute('required'))
      );
      if (!required) return;

      const val = hidden ? String(hidden.value || '') : cells.map((c) => c.value).join('');
      if (val.length < cells.length) {
        renderFieldError(stepEl, hidden || group, `Completa los ${cells.length} dígitos requeridos.`);
        if (!firstInvalid) firstInvalid = cells[0] || group;
      }
    });

    // 2. Controles nativos y campos ocultos con data-ui-required="true"
    const controls = Array.from(stepEl.querySelectorAll('input, select, textarea'));
    for (const ctrl of controls) {
      if (ctrl.disabled || isHiddenByCondition(ctrl)) continue;
      if (ctrl.classList.contains('ui-otp-cell') || ctrl.closest('.ui-otp-group')) continue;
      if (ctrl.classList.contains('ui-tags-entry')) continue;

      if (ctrl.type === 'hidden') {
        const isRequiredCustom = ctrl.getAttribute('data-ui-required') === 'true' || ctrl.hasAttribute('required');
        if (isRequiredCustom && String(ctrl.value || '').trim() === '') {
          const trigger = ctrl.closest('.ui-dropdown-wrap')?.querySelector('.dropdown-selected') || ctrl;
          renderFieldError(stepEl, ctrl, 'Selecciona una opción obligatoria.');
          if (!firstInvalid) firstInvalid = trigger;
        }
        continue;
      }

      if (ctrl.type === 'radio') {
        const rName = ctrl.name || '';
        if (!rName || checkedRadioGroups.has(rName)) continue;
        const groupRadios = Array.from(stepEl.querySelectorAll(`input[type="radio"][name="${CSS.escape(rName)}"]`))
          .filter((r) => !r.disabled && !isHiddenByCondition(r));
        const needsOne = groupRadios.some((r) => r.hasAttribute('required') || r.getAttribute('data-ui-required') === 'true');
        checkedRadioGroups.add(rName);
        if (needsOne && !groupRadios.some((r) => r.checked)) {
          renderFieldError(stepEl, groupRadios[0], 'Selecciona una opción.');
          if (!firstInvalid) firstInvalid = groupRadios[0];
        }
        continue;
      }

      if (typeof ctrl.checkValidity === 'function' && !ctrl.checkValidity()) {
        renderFieldError(stepEl, ctrl, ctrl.validationMessage || 'Revisa este campo.');
        if (!firstInvalid) firstInvalid = ctrl;
      }
    }

    if (firstInvalid) {
      if (dots[currentIndex]) {
        dots[currentIndex].classList.add('is-error');
      }
      if (typeof firstInvalid.focus === 'function') {
        firstInvalid.focus({ preventScroll: true });
      }
      return false;
    }

    return true;
  }

  /**
   * Ejecuta validación remota por AJAX si el paso define data-validate-url.
   *
   * @param {HTMLElement} stepEl Paso actual
   * @param {HTMLButtonElement|null} nextBtn Botón siguiente para estado de carga
   * @returns {Promise<boolean>}
   */
  async function validateStepRemote(stepEl, nextBtn) {
    const url = (stepEl.getAttribute('data-validate-url') || '').trim();
    if (!url) return true;

    if (form.dataset.studioPreview === 'true') {
      document.dispatchEvent(
        new CustomEvent('studio:stepValidated', {
          detail: { form, stepIndex: currentIndex, validateUrl: url, simulated: true }
        })
      );
      return true;
    }

    const fd = new FormData();
    stepEl.querySelectorAll('input, select, textarea').forEach((ctrl) => {
      if (!ctrl.name || ctrl.disabled) return;
      if (ctrl.type === 'checkbox' || ctrl.type === 'radio') {
        if (ctrl.checked) fd.append(ctrl.name, ctrl.value || '1');
        return;
      }
      if (ctrl.type === 'file') {
        if (ctrl.files) {
          Array.from(ctrl.files).forEach((f) => fd.append(ctrl.name, f));
        }
        return;
      }
      fd.append(ctrl.name, ctrl.value ?? '');
    });

    const csrfInput = form.querySelector('input[name="csrf_token"], input[name="_token"]');
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfInput?.value || csrfMeta?.getAttribute('content') || '';
    if (csrfInput?.name && !fd.has(csrfInput.name)) {
      fd.append(csrfInput.name, csrfInput.value);
    }

    if (nextBtn) {
      nextBtn.classList.add('is-loading');
      nextBtn.disabled = true;
    }

    try {
      const headers = { 'X-Requested-With': 'XMLHttpRequest' };
      if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

      const res = await fetch(url, {
        method: 'POST',
        headers,
        body: fd
      });

      let payload = null;
      const contentType = res.headers.get('content-type') || '';
      if (contentType.includes('application/json')) {
        payload = await res.json();
      }

      const isValid = res.ok && (!payload || (payload.valid !== false && payload.ok !== false && payload.success !== false));
      if (isValid) {
        return true;
      }

      if (payload && payload.errors && typeof payload.errors === 'object') {
        Object.entries(payload.errors).forEach(([fieldName, errMsg]) => {
          const targetCtrl = stepEl.querySelector(`[name="${CSS.escape(fieldName)}"]`);
          renderFieldError(stepEl, targetCtrl, String(errMsg || 'Valor inválido'));
        });
      }
      const fallbackMsg = (payload && (payload.message || payload.error)) || 'No se pudo validar este paso. Revisa los datos.';
      renderStepBanner(stepEl, String(fallbackMsg));
      if (dots[currentIndex]) dots[currentIndex].classList.add('is-error');
      return false;
    } catch (err) {
      console.error('Error al validar paso vía AJAX:', err);
      renderStepBanner(stepEl, 'Error de conexión al validar el paso. Intenta nuevamente.');
      if (dots[currentIndex]) dots[currentIndex].classList.add('is-error');
      return false;
    } finally {
      if (nextBtn) {
        nextBtn.classList.remove('is-loading');
        nextBtn.disabled = false;
      }
    }
  }

  /**
   * Activa visualmente un paso por su índice y actualiza navegación y progreso.
   *
   * @param {number} nextIndex Índice de destino
   * @param {boolean} [emitEvent=true] Si debe emitir evento ui:stepChanged
   */
  function goToStep(nextIndex, emitEvent = true) {
    const bounded = Math.max(0, Math.min(steps.length - 1, nextIndex));
    const prevIndex = currentIndex;
    currentIndex = bounded;

    steps.forEach((stepEl, idx) => {
      const active = idx === bounded;
      stepEl.classList.toggle('is-active', active);
      if (active) {
        stepEl.removeAttribute('hidden');
      } else {
        stepEl.setAttribute('hidden', 'hidden');
      }
    });

    dots.forEach((dot, idx) => {
      dot.classList.toggle('is-active', idx === bounded);
      if (idx < bounded) {
        dot.classList.add('is-done');
        dot.classList.remove('is-error');
      }
      dot.setAttribute('aria-current', idx === bounded ? 'step' : 'false');
    });

    if (progressFill) {
      const pct = Math.round(((bounded + 1) / steps.length) * 100);
      progressFill.style.width = `${pct}%`;
    }

    form.dataset.currentStep = String(bounded);

    if (typeof window !== 'undefined' && window.FmeUi && typeof window.FmeUi.syncAllFloatingScrolls === 'function') {
      requestAnimationFrame(() => window.FmeUi.syncAllFloatingScrolls());
    }

    if (emitEvent) {
      form.dispatchEvent(
        new CustomEvent('ui:stepChanged', {
          bubbles: true,
          detail: {
            form,
            index: bounded,
            previousIndex: prevIndex,
            total: steps.length,
            stepEl: steps[bounded]
          }
        })
      );
    }
  }

  /**
   * Avanza al siguiente paso validando cliente y servidor.
   *
   * @param {HTMLButtonElement|null} [triggerBtn=null] Botón activador
   */
  async function handleNext(triggerBtn = null) {
    const activeStep = steps[currentIndex];
    if (!activeStep) return;

    if (!validateStepClient(activeStep)) return;

    const remoteOk = await validateStepRemote(activeStep, triggerBtn);
    if (!remoteOk) return;

    if (dots[currentIndex]) {
      dots[currentIndex].classList.add('is-done');
      dots[currentIndex].classList.remove('is-error');
    }

    if (currentIndex < steps.length - 1) {
      goToStep(currentIndex + 1, true);
    }
  }

  // Inicializar estado visual inicial sin disparar transición duplicada
  goToStep(currentIndex, false);

  // Limpiar errores del campo en tiempo real cuando el usuario corrige su valor
  const clearControlError = (e) => {
    const ctrl = e.target;
    if (!ctrl || !form.contains(ctrl)) return;
    const container = ctrl.closest('.ui-field-group') || ctrl.closest('.ui-field-card') || ctrl.parentElement;
    if (!container) return;
    container.querySelectorAll('.ui-field-error[data-step-error-msg="1"]').forEach((msg) => msg.remove());
  };
  form.addEventListener('input', clearControlError);
  form.addEventListener('change', clearControlError);

  // Delegación de botones Siguiente / Anterior / Dots
  form.addEventListener('click', async (e) => {
    const nextBtn = e.target.closest('.js-step-next');
    if (nextBtn && form.contains(nextBtn)) {
      e.preventDefault();
      await handleNext(nextBtn);
      return;
    }

    const prevBtn = e.target.closest('.js-step-prev');
    if (prevBtn && form.contains(prevBtn)) {
      e.preventDefault();
      if (currentIndex > 0) {
        goToStep(currentIndex - 1, true);
      }
      return;
    }

    const dotBtn = e.target.closest('.ui-step-dot');
    if (dotBtn && form.contains(dotBtn)) {
      e.preventDefault();
      const targetAttr = dotBtn.getAttribute('data-step-target');
      const targetIdx = targetAttr !== null ? parseInt(targetAttr, 10) : dots.indexOf(dotBtn);
      if (Number.isNaN(targetIdx) || targetIdx === currentIndex) return;

      const canJump =
        targetIdx < currentIndex ||
        dotBtn.classList.contains('is-done') ||
        form.dataset.studioPreview === 'true';

      if (canJump) {
        goToStep(targetIdx, true);
      } else if (targetIdx === currentIndex + 1) {
        await handleNext(null);
      }
    }
  });

  // Tecla Enter en pasos intermedios avanza en vez de enviar el formulario incompleto
  form.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    const target = e.target;
    if (!target) return;
    if (target.tagName === 'TEXTAREA' || target.tagName === 'BUTTON') return;
    if (target.type === 'submit' || target.classList.contains('ui-tags-entry')) return;

    if (currentIndex < steps.length - 1) {
      e.preventDefault();
      const nextBtn = steps[currentIndex]?.querySelector('.js-step-next') || null;
      handleNext(nextBtn);
    }
  });

  // Prevenir submit final hasta estar en el último paso válido
  form.addEventListener('submit', async (e) => {
    const activeStep = steps[currentIndex];
    if (!activeStep) return;

    if (currentIndex < steps.length - 1) {
      e.preventDefault();
      e.stopImmediatePropagation();
      const nextBtn = activeStep.querySelector('.js-step-next') || null;
      await handleNext(nextBtn);
      return;
    }

    if (!validateStepClient(activeStep)) {
      e.preventDefault();
      e.stopImmediatePropagation();
    }
  }, true);
}
