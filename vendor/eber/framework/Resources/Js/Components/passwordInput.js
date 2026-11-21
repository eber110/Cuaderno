/**
 * Input de Contraseña con botón táctil mostrar/ocultar SVG y medidor de fortaleza opcional.
 *
 * @function passwordInput
 * @description Mejora inputs `.password-input` añadiendo botón de visibilidad y barra opcional de fortaleza de contraseña.
 *
 * @example
 * // HTML - Campo de contraseña con medidor de fortaleza
 * <input type="password"
 *        name="password"
 *        class="password-input"
 *        placeholder="Ingresa tu contraseña"
 *        data-strength="true">
 *
 * @css .password-input - Clase requerida en el `<input type="password">`
 * @attribute data-strength - Muestra el indicador de seguridad cuando vale `'true'` (opcional)
 * @attribute ui-trigger - Clases utilitarias aplicadas al campo de entrada (opcional)
 *
 * @returns {void}
 */
export function passwordInput() {
  const ui = window.FmeUi || null;
  const tr = (k, p, f) => (ui ? ui.t(k, p, f) : (typeof window.t === 'function' ? window.t(k, p, f) : f));
  const icon = (name, fb) => (ui && ui.ICONS && ui.ICONS[name]) ? ui.ICONS[name] : fb;

  function evaluateStrength(pw) {
    if (!pw) return { score: 0, label: '', color: 'var(--ui-track-bg)' };
    let score = 0;
    if (pw.length >= 6) score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
    if (/\d/.test(pw) || /[^A-Za-z0-9]/.test(pw)) score++;

    if (score <= 1) return { score: 1, label: tr('strength_weak', {}, 'Débil'), color: 'var(--danger, #ef4444)' };
    if (score === 2) return { score: 2, label: tr('strength_fair', {}, 'Regular'), color: 'var(--caution, #f59e0b)' };
    if (score === 3) return { score: 3, label: tr('strength_good', {}, 'Buena'), color: 'var(--ui-accent, #3b82f6)' };
    return { score: 4, label: tr('strength_strong', {}, 'Fuerte'), color: 'var(--success, #22c55e)' };
  }

  function initPasswordInputs() {
    document.querySelectorAll('.password-input:not([data-pw-initialized])').forEach((input) => {
      input.dataset.pwInitialized = 'true';
      input.classList.add('ui-password-field');

      const uiTrigger = input.getAttribute('ui-trigger') || '';
      if (uiTrigger && ui) ui.applyClasses(input, uiTrigger);

      const wrap = document.createElement('div');
      wrap.className = 'ui-password-wrap';
      if (ui) ui.copyUiVars(input, wrap);

      const row = document.createElement('div');
      row.className = 'ui-password-input-row';

      const toggleBtn = document.createElement('button');
      toggleBtn.type = 'button';
      toggleBtn.className = 'ui-password-toggle';
      toggleBtn.setAttribute('aria-label', tr('show_password', {}, 'Mostrar contraseña'));
      toggleBtn.innerHTML = icon('eye', '👁');

      input.parentNode.insertBefore(wrap, input);
      row.appendChild(input);
      row.appendChild(toggleBtn);
      wrap.appendChild(row);

      toggleBtn.addEventListener('click', () => {
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        toggleBtn.innerHTML = isHidden ? icon('eyeOff', '🙈') : icon('eye', '👁');
        toggleBtn.setAttribute(
          'aria-label',
          isHidden ? tr('hide_password', {}, 'Ocultar contraseña') : tr('show_password', {}, 'Mostrar contraseña')
        );
      });

      if (input.getAttribute('data-strength') === 'true') {
        const strengthWrap = document.createElement('div');
        strengthWrap.className = 'ui-password-strength';

        const barsWrap = document.createElement('div');
        barsWrap.className = 'ui-strength-bars';
        const bars = [];
        for (let i = 0; i < 4; i++) {
          const bar = document.createElement('div');
          bar.className = 'ui-strength-bar';
          bars.push(bar);
          barsWrap.appendChild(bar);
        }

        const labelEl = document.createElement('div');
        labelEl.className = 'ui-strength-label ui-pwd-strength-label';

        strengthWrap.appendChild(barsWrap);
        strengthWrap.appendChild(labelEl);
        wrap.appendChild(strengthWrap);

        const updateMeter = () => {
          const res = evaluateStrength(input.value);
          bars.forEach((b, idx) => {
            b.style.backgroundColor = idx < res.score ? res.color : 'var(--ui-track-bg)';
          });
          labelEl.textContent = res.label;
          labelEl.style.color = res.score > 0 ? res.color : 'var(--ui-surface-muted)';
        };

        input.addEventListener('input', updateMeter);
        updateMeter();
      }
    });
  }

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initPasswordInputs);
  } else {
    initPasswordInputs();
    document.addEventListener('previewUpdated', initPasswordInputs);
  }
}
