/**
 * TextLimiter
 * Módulo para limitar y contar la cantidad de caracteres o palabras en un input, textarea o contenteditable.
 * 
 * CARACTERÍSTICAS:
 * - Cuenta caracteres o palabras de forma limpia y precisa.
 * - No altera ni formatea el texto mientras el usuario escribe libremente (permite tildes, espacios y saltos de línea).
 * - Aplica límites de forma no intrusiva (utiliza maxlength nativo en inputs/textareas para caracteres).
 * - Respeta eventos de composición de teclado (IME / teclas muertas / tildes).
 * - Actualiza contadores dinámicos con .tl-current y .tl-limit.
 * - Añade la clase .tl-max-reached cuando se alcanza el límite.
 */
class TextLimiter {
    constructor(element) {
        this.element = element;
        this.limitType = this.element.dataset.limitType || 'chars'; // 'chars' o 'words'
        this.limit = parseInt(this.element.dataset.limit || 100, 10);
        this.counterEl = this.element.dataset.counterEl ? document.querySelector(this.element.dataset.counterEl) : null;

        this.init();
    }

    init() {
        // En inputs y textareas con límite de caracteres, establecer maxlength nativo
        if (this.limitType === 'chars' && (this.element.tagName === 'INPUT' || this.element.tagName === 'TEXTAREA')) {
            this.element.setAttribute('maxlength', this.limit);
        }

        // Manejar evento input para contar y actualizar UI
        const inputHandler = (e) => {
            // Ignorar durante composición de teclado (tildes / caracteres acentuados)
            if (e && e.isComposing) return;
            this.handleInput();
        };

        this.element.addEventListener('input', inputHandler);
        this.element.addEventListener('compositionend', () => this.handleInput());

        // Conteo inicial
        this.handleInput();
    }

    getText() {
        if (this.element.tagName === 'TEXTAREA' || this.element.tagName === 'INPUT') {
            return this.element.value || '';
        } else {
            return this.element.innerText || '';
        }
    }

    setText(newText) {
        if (this.element.tagName === 'TEXTAREA' || this.element.tagName === 'INPUT') {
            const start = this.element.selectionStart;
            const end = this.element.selectionEnd;
            this.element.value = newText;
            if (typeof this.element.setSelectionRange === 'function' && start !== null) {
                try {
                    this.element.setSelectionRange(Math.min(start, newText.length), Math.min(end, newText.length));
                } catch (e) {}
            }
        } else {
            const selection = window.getSelection();
            const range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;

            this.element.innerText = newText;

            if (range) {
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
        } else {
            return text.length;
        }
    }

    truncateText(text) {
        if (this.limitType === 'words') {
            const words = text.split(/\s+/);
            if (words.length > this.limit) {
                return words.slice(0, this.limit).join(' ') + (text.endsWith(' ') ? ' ' : '');
            }
            return text;
        } else {
            return text.slice(0, this.limit);
        }
    }

    handleInput() {
        let text = this.getText();
        let currentCount = this.calculateCount(text);

        // Si excede el límite (por ejemplo al pegar texto largo), truncar limpiamente
        if (currentCount > this.limit) {
            text = this.truncateText(text);
            currentCount = this.calculateCount(text);
            this.setText(text);
        }

        // Actualizar UI del contador si existe
        if (this.counterEl) {
            const currentEl = this.counterEl.querySelector('.tl-current');
            const limitEl = this.counterEl.querySelector('.tl-limit');

            if (currentEl) {
                currentEl.textContent = currentCount;
                if (limitEl) limitEl.textContent = this.limit;
            } else {
                this.counterEl.textContent = `${currentCount} / ${this.limit}`;
            }

            if (currentCount >= this.limit) {
                this.counterEl.classList.add('tl-max-reached');
            } else {
                this.counterEl.classList.remove('tl-max-reached');
            }
        }
    }
}

export function textLimiter() {
    const initAll = () => {
        document.querySelectorAll('.text-limiter, [data-limit-type]').forEach(el => {
            if (!el.__tl_instance) {
                el.__tl_instance = new TextLimiter(el);
            }
        });
    };

    initAll();

    const observer = new MutationObserver((mutations) => {
        let shouldInit = false;
        for (let m of mutations) {
            if (m.addedNodes.length > 0) {
                shouldInit = true;
                break;
            }
        }
        if (shouldInit) initAll();
    });

    observer.observe(document.body, { childList: true, subtree: true });
}
