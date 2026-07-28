/**
 * TextLimiter
 * Módulo para limitar la cantidad de palabras o caracteres en un textarea o contenteditable.
 * 
 * CARACTERÍSTICAS:
 * - No cuenta los espacios simples entre letras para el límite de caracteres.
 * - Cuenta los espacios adicionales si el usuario escribe dos o más seguidos.
 * - (Opcional) Prohíbe físicamente escribir 2 espacios seguidos si se activa `data-prevent-double-space`.
 * - Permite personalizar el contador con HTML propio usando `.tl-current` y `.tl-limit`.
 * - Añade automáticamente la clase `.tl-max-reached` al contador cuando se alcanza el límite.
 * 
 * EJEMPLOS DE USO:
 * 
 * 1. Límite de caracteres simple (borra dobles espacios automáticamente):
 *    <textarea class="text-limiter" 
 *              data-limit-type="chars" 
 *              data-limit="280" 
 *              data-prevent-double-space="true"
 *              data-counter-el="#mi-contador"></textarea>
 *    <div id="mi-contador">0 / 280</div>
 * 
 * 2. Límite de palabras en contenteditable:
 *    <div contenteditable="true" class="text-limiter" data-limit-type="words" data-limit="50" data-counter-el="#contador-palabras"></div>
 *    <span id="contador-palabras">0 / 50</span>
 * 
 * 3. Contador con HTML y estilos personalizados:
 *    <div id="mi-contador">
 *       Llevas <span class="tl-current color-success bold">0</span> 
 *       de <span class="tl-limit color-caution">280</span> caracteres.
 *    </div>
 *    
 *    (Cuando el límite se alcance, el div #mi-contador recibirá la clase 'tl-max-reached',
 *    la cual puedes estilizar en CSS, ej: .tl-max-reached { color: red !important; })
 */
class TextLimiter {
    constructor(element) {
        this.element = element;
        this.limitType = this.element.dataset.limitType || 'chars'; // 'chars' o 'words'
        this.limit = parseInt(this.element.dataset.limit || 100, 10);
        this.counterEl = document.querySelector(this.element.dataset.counterEl);
        
        // Atributo opcional para prohibir físicamente 2 espacios seguidos (borrarlos al instante)
        this.preventDoubleSpace = this.element.dataset.preventDoubleSpace === 'true';

        this.init();
    }

    init() {
        // Ejecutar manejador principal en múltiples eventos para garantizar reactividad en todos los entornos
        const handler = () => this.handleInput();
        this.element.addEventListener('input', handler);
        this.element.addEventListener('keyup', handler);
        this.element.addEventListener('change', handler);
        this.element.addEventListener('blur', handler);
        
        // Evitar que sigan escribiendo espacios si tienen la regla activa (keydown)
        if (this.preventDoubleSpace) {
            this.element.addEventListener('keydown', (e) => {
                if (e.key === ' ') {
                    const text = this.getText();
                    // Si el último caracter es un espacio, prevenimos
                    if (text.endsWith(' ') || text.endsWith('\n')) {
                        e.preventDefault();
                    }
                }
            });
        }

        // Ejecución inicial para setear contadores si ya hay texto
        this.handleInput();
    }

    getText() {
        if (this.element.tagName === 'TEXTAREA' || this.element.tagName === 'INPUT') {
            return this.element.value;
        } else {
            return this.element.innerText || '';
        }
    }

    setText(newText) {
        if (this.element.tagName === 'TEXTAREA' || this.element.tagName === 'INPUT') {
            this.element.value = newText;
        } else {
            // contenteditable: actualizar textContent resetea el cursor, 
            // por lo que intentamos hacerlo lo más suave posible.
            // Para límites estrictos en contenteditable, es mejor prevenir en keydown,
            // pero si pegan texto largo, truncamos.
            const selection = window.getSelection();
            const range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
            
            this.element.innerText = newText;
            
            // Restaurar cursor al final si estábamos editando
            if (range) {
                const newRange = document.createRange();
                newRange.selectNodeContents(this.element);
                newRange.collapse(false); // al final
                selection.removeAllRanges();
                selection.addRange(newRange);
            }
        }
    }

    handleInput() {
        let text = this.getText();
        let modified = false;

        // Regla: No dejar poner 2 espacios seguidos
        if (this.preventDoubleSpace && /\s{2,}/.test(text)) {
            text = text.replace(/\s{2,}/g, ' ');
            modified = true;
        }

        // Calcular conteo actual
        let currentCount = this.calculateCount(text);

        // Si excede el límite, truncamos
        if (currentCount > this.limit) {
            text = this.truncateText(text);
            currentCount = this.calculateCount(text);
            modified = true;
        }

        // Aplicar modificaciones si hubo (truncado o limpieza de espacios)
        if (modified) {
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

            // Agregar clase cuando llega al límite para facilitar el estilado CSS
            if (currentCount >= this.limit) {
                this.counterEl.classList.add('tl-max-reached');
            } else {
                this.counterEl.classList.remove('tl-max-reached');
            }
        }
    }

    calculateCount(text) {
        if (this.limitType === 'words') {
            const trimmed = text.trim();
            return trimmed === '' ? 0 : trimmed.split(/\s+/).length;
        } else {
            // characters: No contamos el espacio simple entre palabras.
            // Ejemplo: "a b" -> 1 espacio, se quita, cuenta 2.
            // "a  b" -> 2 espacios, se quita 1, cuenta 3.
            // Reemplazamos cada bloque de espacios por (n-1) espacios.
            const countText = text.replace(/\s+/g, (match) => ' '.repeat(match.length - 1));
            return countText.length;
        }
    }

    truncateText(text) {
        if (this.limitType === 'words') {
            const words = text.split(/\s+/);
            if (words.length > this.limit) {
                // Conservamos solo la cantidad de palabras permitidas
                return words.slice(0, this.limit).join(' ') + (text.endsWith(' ') ? ' ' : '');
            }
            return text;
        } else {
            // characters
            // Truncamos iterativamente desde el final hasta que cumpla el límite.
            // No es el más óptimo para textos inmensos, pero es robusto y simple para los límites normales (280, etc.)
            let truncated = text;
            while (this.calculateCount(truncated) > this.limit && truncated.length > 0) {
                truncated = truncated.slice(0, -1);
            }
            return truncated;
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
