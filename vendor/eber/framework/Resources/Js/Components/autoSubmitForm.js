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
}
