/**
 * Copiar texto al portapapeles.
 * 
 * @function copyToClipboard
 * @description Permite copiar texto al portapapeles con un click.
 *              Muestra feedback visual al copiar.
 * 
 * @example
 * // HTML - Copiar texto del atributo
 * <button class="copy-btn" data-copy="Texto a copiar">Copiar</button>
 * 
 * // HTML - Copiar contenido de otro elemento
 * <code id="code-block">const x = 1;</code>
 * <button class="copy-btn" data-copy-target="#code-block">Copiar código</button>
 * 
 * @css .copy-btn - Botón de copiar
 * @attribute data-copy - Texto a copiar directamente
 * @attribute data-copy-target - Selector del elemento cuyo texto copiar
 * 
 * @returns {void}
 */
export function copyToClipboard() {

  /**
   * Función auxiliar para copiar usando el método fallback
   * @param {string} text - Texto a copiar
   */
  function copyFallback(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    textarea.remove();
  }

  document.addEventListener('click', async (e) => {
    if (!e.target || !e.target.closest) return;
    const copyBtn = e.target.closest('.copy-btn');
    if (!copyBtn) return;

    let textToCopy = copyBtn.dataset.copy;

    // Si hay un target, copiar el texto de ese elemento
    if (copyBtn.dataset.copyTarget) {
      const targetEl = document.querySelector(copyBtn.dataset.copyTarget);
      if (targetEl) {
        textToCopy = targetEl.textContent || targetEl.value;
      }
    }

    if (!textToCopy) return;

    // Función para mostrar feedback
    const showFeedback = () => {
      const originalText = copyBtn.innerHTML;
      copyBtn.innerHTML = '✓ Copiado';
      copyBtn.classList.add('copied');
      setTimeout(() => {
        copyBtn.innerHTML = originalText;
        copyBtn.classList.remove('copied');
      }, 2000);
    };

    // Usar Clipboard API si está disponible, sino usar fallback
    if (navigator.clipboard && navigator.clipboard.writeText) {
      try {
        await navigator.clipboard.writeText(textToCopy);
        showFeedback();
      } catch (err) {
        // Si falla, intentar con fallback
        copyFallback(textToCopy);
        showFeedback();
      }
    } else {
      // Fallback para navegadores sin Clipboard API
      copyFallback(textToCopy);
      showFeedback();
    }
  });
}
