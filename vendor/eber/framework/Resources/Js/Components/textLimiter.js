/**
 * Limitador y contador dinámico de caracteres o palabras en inputs, textareas y contenteditable.
 * Incluye estados visuales progresivos (`.tl-warning` >= 80%, `.tl-max-reached` >= 100%).
 *
 * @function textLimiter
 * @description Limita y muestra el conteo en tiempo real de caracteres o palabras en elementos `.text-limiter`.
 *
 * @example
 * // HTML - Textarea con límite de 200 caracteres y contador enlazado
 * <textarea name="bio"
 *           class="text-limiter"
 *           data-limit-type="chars"
 *           data-limit="200"
 *           data-counter-el="#bioCounter"></textarea>
 * <small id="bioCounter"></small>
 *
 * @css .text-limiter - Clase aplicada al `<input>`, `<textarea>` o elemento `contenteditable`
 * @css .tl-warning - Clase aplicada cuando el texto alcanza el 80% del límite
 * @css .tl-max-reached - Clase aplicada cuando el texto alcanza el 100% del límite
 * @attribute data-limit-type - Tipo de conteo: `'chars'` (defecto) o `'words'` (opcional)
 * @attribute data-limit - Límite numérico máximo (defecto `'100'`, opcional)
 * @attribute data-counter-el - Selector CSS del elemento donde se muestra el contador (opcional)
 *
 * @returns {void}
 */
class TextLimiter {
  constructor(element) {
    this.element = element;
    this.limitType = this.element.dataset.limitType || 'chars';
    this.limit = parseInt(this.element.dataset.limit || '100', 10) || 100;
    this.counterEl = this.element.dataset.counterEl
      ? document.querySelector(this.element.dataset.counterEl)
      : null;

    this.init();
  }

  init() {
    if (this.limitType === 'chars' && (this.element.tagName === 'INPUT' || this.element.tagName === 'TEXTAREA')) {
      this.element.setAttribute('maxlength', String(this.limit));
    }

    const inputHandler = (e) => {
      if (e && e.isComposing) return;
      this.handleInput();
    };

    this.element.addEventListener('input', inputHandler);
    this.element.addEventListener('compositionend', () => this.handleInput());
    if (this.element.tagName === 'TEXTAREA' && window.FmeUi && typeof window.FmeUi.attachFloatingScroll === 'function') {
      window.FmeUi.attachFloatingScroll(this.element, this.element);
    }
    this.handleInput();
  }

  getText() {
    if (this.element.tagName === 'TEXTAREA' || this.element.tagName === 'INPUT') {
      return this.element.value || '';
    }
    return this.element.innerText || '';
  }

  setText(newText) {
    if (this.element.tagName === 'TEXTAREA' || this.element.tagName === 'INPUT') {
      const start = this.element.selectionStart;
      const end = this.element.selectionEnd;
      this.element.value = newText;
      if (typeof this.element.setSelectionRange === 'function' && start !== null) {
        try {
          this.element.setSelectionRange(Math.min(start, newText.length), Math.min(end, newText.length));
        } catch (_) {}
      }
    } else {
      const selection = window.getSelection();
      const range = selection && selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
      this.element.innerText = newText;
      if (range && selection) {
        const newRange = document.createRange();
        newRange.selectNodeContents(this.element);
        newRange.collapse(false);
        selection.removeAllRanges();
        selection.addRange(newRange);
      }
    }
  }

  calculateCount(text) {
    if (this.limitType === 'words') {
      const trimmed = text.trim();
      return trimmed === '' ? 0 : trimmed.split(/\s+/).length;
    }
    return text.length;
  }

  truncateText(text) {
    if (this.limitType === 'words') {
      const words = text.split(/\s+/);
      if (words.length > this.limit) {
        return words.slice(0, this.limit).join(' ') + (text.endsWith(' ') ? ' ' : '');
      }
      return text;
    }
    return text.slice(0, this.limit);
  }

  handleInput() {
    let text = this.getText();
    let currentCount = this.calculateCount(text);

    if (currentCount > this.limit) {
      text = this.truncateText(text);
      currentCount = this.calculateCount(text);
      this.setText(text);
    }

    if (this.counterEl) {
      const currentEl = this.counterEl.querySelector('.tl-current');
      const limitEl = this.counterEl.querySelector('.tl-limit');

      if (currentEl) {
        currentEl.textContent = String(currentCount);
        if (limitEl) limitEl.textContent = String(this.limit);
      } else {
        this.counterEl.textContent = `${currentCount} / ${this.limit}`;
      }

      const ratio = this.limit > 0 ? currentCount / this.limit : 0;
      this.counterEl.classList.toggle('tl-warning', ratio >= 0.8 && currentCount < this.limit);
      this.counterEl.classList.toggle('tl-max-reached', currentCount >= this.limit);
    }

    if (this.element.__floatingScrollInstance) {
      this.element.__floatingScrollInstance.refresh();
    }
  }
}

export function textLimiter() {
  const initAll = () => {
    document.querySelectorAll('.text-limiter, [data-limit-type]').forEach((el) => {
      if (!el.__tl_instance) {
        el.__tl_instance = new TextLimiter(el);
      }
    });
  };

  if (window.FmeUi && typeof window.FmeUi.observeDynamic === 'function') {
    window.FmeUi.observeDynamic(initAll);
  } else {
    initAll();
    const observer = new MutationObserver((mutations) => {
      for (const m of mutations) {
        if (m.addedNodes.length > 0) {
          initAll();
          break;
        }
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }
}
