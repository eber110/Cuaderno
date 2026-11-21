/**
 * Input de Código OTP / Verificación 2FA (`.otp-input`).
 * Genera casillas conectadas con auto-avance, retroceso inteligente, pegado completo y teclado numérico móvil.
 *
 * @function otpCodeInput
 * @description Transforma un input `.otp-input` en un grupo de casillas individuales para códigos de verificación 2FA/OTP.
 *
 * @example
 * // HTML - Campo OTP numérico de 6 dígitos
 * <input type="text"
 *        name="codigo_otp"
 *        class="otp-input"
 *        data-length="6"
 *        inputmode="numeric">
 *
 * @css .otp-input - Clase requerida en el `<input>` para generar las casillas OTP
 * @attribute data-length - Cantidad de casillas entre `4` y `8` (defecto `'6'`, opcional)
 * @attribute data-mask - Usa `'password'` para ocultar los dígitos introducidos (opcional)
 * @attribute ui-trigger - Clases utilitarias aplicadas a cada casilla (opcional)
 *
 * @returns {void}
 */
export function otpCodeInput() {
  const ui = window.FmeUi || null;

  function initOtpInputs() {
    document.querySelectorAll('.otp-input:not([data-otp-initialized])').forEach((input) => {
      input.dataset.otpInitialized = 'true';
      input.style.display = 'none';

      const length = Math.max(4, Math.min(8, parseInt(input.getAttribute('data-length') || '6', 10) || 6));
      const inputMode = input.getAttribute('inputmode') || 'numeric';
      const cellMask = (input.getAttribute('data-mask') || 'text').toLowerCase() === 'password' ? 'password' : 'text';
      const uiTrigger = input.getAttribute('ui-trigger') || '';

      const group = document.createElement('div');
      group.className = 'ui-otp-group';
      if (ui) ui.copyUiVars(input, group);

      const cells = [];
      const syncValue = (emitChange = false) => {
        const val = cells.map((c) => c.value).join('');
        input.value = val;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        if (emitChange && val.length === length) {
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      };

      const initialChars = (input.value || '').slice(0, length).split('');

      for (let i = 0; i < length; i++) {
        const cell = document.createElement('input');
        cell.type = cellMask;
        cell.maxLength = 1;
        cell.className = 'ui-otp-cell';
        if (uiTrigger && ui) ui.applyClasses(cell, uiTrigger);
        cell.setAttribute('inputmode', inputMode);
        cell.setAttribute('autocomplete', i === 0 ? 'one-time-code' : 'off');
        cell.value = initialChars[i] || '';

        cell.addEventListener('input', (e) => {
          let char = (cell.value || '').trim();
          if (inputMode === 'numeric') {
            char = char.replace(/\D/g, '');
          }
          if (char.length > 1) {
            // Pegado o autocompletado SMS en una sola celda
            const chars = char.slice(0, length - i).split('');
            chars.forEach((ch, offset) => {
              if (cells[i + offset]) cells[i + offset].value = ch;
            });
            const nextIdx = Math.min(length - 1, i + chars.length);
            cells[nextIdx].focus();
            syncValue(true);
            return;
          }

          cell.value = char;
          if (char && cells[i + 1]) {
            cells[i + 1].focus();
          }
          syncValue(true);
        });

        cell.addEventListener('keydown', (e) => {
          if (e.key === 'Backspace' && !cell.value && cells[i - 1]) {
            e.preventDefault();
            cells[i - 1].value = '';
            cells[i - 1].focus();
            syncValue(false);
          } else if (e.key === 'ArrowLeft' && cells[i - 1]) {
            e.preventDefault();
            cells[i - 1].focus();
          } else if (e.key === 'ArrowRight' && cells[i + 1]) {
            e.preventDefault();
            cells[i + 1].focus();
          }
        });

        cell.addEventListener('paste', (e) => {
          e.preventDefault();
          const pasted = (e.clipboardData || window.clipboardData)?.getData('text') || '';
          const clean = inputMode === 'numeric' ? pasted.replace(/\D/g, '') : pasted.trim();
          if (!clean) return;
          const chars = clean.slice(0, length).split('');
          cells.forEach((c, idx) => {
            c.value = chars[idx] || '';
          });
          const focusIdx = Math.min(length - 1, chars.length - 1);
          if (cells[focusIdx]) cells[focusIdx].focus();
          syncValue(true);
        });

        cells.push(cell);
        group.appendChild(cell);
      }

      input.insertAdjacentElement('beforebegin', group);
    });
  }

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initOtpInputs);
  } else {
    initOtpInputs();
    document.addEventListener('previewUpdated', initOtpInputs);
  }
}
