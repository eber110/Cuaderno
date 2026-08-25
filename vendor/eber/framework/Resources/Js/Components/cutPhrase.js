/**
 * Componente para encajar dinámicamente un texto dentro de un contenedor con altura fija o máxima.
 * Utiliza CSS -webkit-line-clamp en un contenedor de envoltura interno (.cut-phrase-wrapper)
 * calculando el número exacto de líneas que caben según la altura interna disponible
 * del elemento padre y su line-height.
 * 
 * Evita colisiones de maquetación cuando el contenedor padre es display: flex o grid.
 * 
 * @function cutPhrase
 * @description Trunca automáticamente textos largos añadiendo puntos suspensivos según la altura
 *              del contenedor o mediante un número fijo de líneas definido por atributo.
 * 
 * @example
 * // HTML - Truncamiento automático por altura fija o máxima del contenedor
 * <div class="cut-phrase" style="height: 60px;">
 *   Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
 * </div>
 * 
 * @example
 * // HTML - Truncamiento con clases de utilidad de altura del framework
 * <p class="cut-phrase h50 max-w300">
 *   Texto extenso que se truncará dinámicamente según el espacio vertical disponible.
 * </p>
 * 
 * @example
 * // HTML - Forzar un número específico de líneas con el atributo 'cant-col'
 * <div class="cut-phrase" cant-col="3">
 *   Este texto siempre se limitará a un máximo de 3 líneas con puntos suspensivos,
 *   independientemente de la altura fija del contenedor padre.
 * </div>
 * 
 * @example
 * // JS - Inicialización y eventos personalizados para actualizar contenido dinámico
 * import { cutPhrase } from './Components/cutPhrase.js';
 * 
 * // Inicializar
 * cutPhrase();
 * 
 * // Actualizar tras cambios dinámicos en el DOM o vistas previas
 * document.dispatchEvent(new CustomEvent('contentUpdated'));
 * // o también:
 * document.dispatchEvent(new CustomEvent('previewUpdated'));
 * 
 * @css .cut-phrase - Clase requerida en el contenedor principal a truncar
 * @css .cut-phrase-wrapper - Contenedor interno generado dinámicamente que aplica -webkit-line-clamp
 * @attribute cant-col - (Opcional) Número entero de líneas máximas a forzar en lugar del cálculo por altura
 * @attribute data-cut-phrase-ready - Atributo aplicado automáticamente cuando el cálculo está listo (controla opacidad/FOUC)
 * 
 * @returns {void}
 */
export function cutPhrase() {
  /**
   * Obtiene la altura de línea (line-height) del elemento en píxeles.
   * @param {HTMLElement} el 
   * @returns {number}
   */
  function getLineHeight(el) {
    const computed = window.getComputedStyle(el);
    let lh = parseFloat(computed.lineHeight);
    if (!isNaN(lh)) {
      return lh;
    }

    // Fallback: medir la altura de una línea creando un span temporal
    const temp = document.createElement('span');
    temp.style.visibility = 'hidden';
    temp.style.position = 'absolute';
    temp.style.whiteSpace = 'nowrap';
    temp.textContent = 'M';
    el.appendChild(temp);
    const measured = temp.offsetHeight;
    el.removeChild(temp);
    return measured || 18; // 18px como fallback absoluto
  }

  /**
   * Calcula y aplica el número de líneas máximo para truncar el elemento.
   * @param {HTMLElement} el 
   */
  function updateClamp(el) {
    // 1. Obtener o crear el contenedor interno (wrapper) para evitar romper layouts flex/grid del padre
    let wrapper = el.querySelector('.cut-phrase-wrapper');
    if (!wrapper) {
      // Usar span para elementos de fraseo (p, span, a, label, h1-h6) para evitar que el parser HTML rompa el DOM si se clona con innerHTML
      const isPhrasing = ['P', 'SPAN', 'A', 'STRONG', 'EM', 'B', 'I', 'LABEL', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'SMALL'].includes(el.tagName);
      wrapper = document.createElement(isPhrasing ? 'span' : 'div');
      
      // Mover todo el contenido del elemento padre al wrapper
      while (el.firstChild) {
        wrapper.appendChild(el.firstChild);
      }
      el.appendChild(wrapper);
    }

    // Copiar todas las clases del elemento padre al wrapper (excepto 'cut-phrase')
    const parentClasses = Array.from(el.classList).filter(c => c !== 'cut-phrase');
    wrapper.className = ['cut-phrase-wrapper', ...parentClasses].join(' ');

    // 2. Medir el alto interno disponible del contenedor padre (restando paddings)
    const computed = window.getComputedStyle(el);
    const paddingTop = parseFloat(computed.paddingTop) || 0;
    const paddingBottom = parseFloat(computed.paddingBottom) || 0;
    const innerHeight = el.clientHeight - paddingTop - paddingBottom;

    // 3. Medir line-height en el wrapper
    const lineHeight = getLineHeight(wrapper);

    // 4. Calcular cuántas líneas caben (o usar el atributo cant-col si se especifica)
    let maxLines;
    const forceLinesAttr = el.getAttribute('cant-col');
    if (forceLinesAttr !== null && forceLinesAttr !== '') {
      const forced = parseInt(forceLinesAttr, 10);
      if (!isNaN(forced) && forced > 0) {
        maxLines = forced;
      }
    }

    if (maxLines === undefined) {
      if (innerHeight > 0 && lineHeight > 0) {
        maxLines = Math.floor(innerHeight / lineHeight);
      }
    }

    // 5. Aplicar el line-clamp calculado o forzado al wrapper
    if (maxLines !== undefined) {
      if (maxLines > 0) {
        wrapper.style.setProperty('-webkit-line-clamp', maxLines.toString(), 'important');
      } else {
        wrapper.style.removeProperty('-webkit-line-clamp');
      }
    }

    // 6. Marcar elemento y wrapper como listos para eliminar FOUC
    el.setAttribute('data-cut-phrase-ready', 'true');
    wrapper.setAttribute('data-cut-phrase-ready', 'true');
  }

  const adjustAll = () => {
    const elements = document.querySelectorAll(".cut-phrase");
    elements.forEach(el => {
      updateClamp(el);
    });
  };

  // Ajuste inicial
  adjustAll();

  // Escuchar eventos de actualización de vista previa o contenido dinámico
  document.addEventListener('previewUpdated', adjustAll);
  document.addEventListener('contentUpdated', adjustAll);

  // Re-ajustar cuando las fuentes web terminen de cargarse
  if ('fonts' in document) {
    document.fonts.ready.then(adjustAll);
  }

  // Ajuste dinámico al redimensionar la ventana (de-bounced con requestAnimationFrame)
  let resizeTimeout;
  window.addEventListener('resize', () => {
    if (resizeTimeout) {
      cancelAnimationFrame(resizeTimeout);
    }
    resizeTimeout = requestAnimationFrame(adjustAll);
  });
}
