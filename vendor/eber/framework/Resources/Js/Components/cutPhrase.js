/**
 * Componente para encajar dinámicamente un texto dentro de un div con altura fija o máxima.
 * Utiliza CSS -webkit-line-clamp en un contenedor de envoltura interno (.cut-phrase-wrapper)
 * calculando el número exacto de líneas que caben según la altura interna disponible
 * del elemento padre y su line-height.
 * 
 * Evita colisiones de maquetación cuando el contenedor padre es display: flex o grid.
 * 
 * @function cutPhrase
 * @returns {void}
 */
export function cutPhrase() {
  const elements = document.querySelectorAll(".cut-phrase");
  if (elements.length === 0) return;

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
      wrapper = document.createElement('div');
      
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
      maxLines = Math.floor(innerHeight / lineHeight);
    }

    // 5. Aplicar el line-clamp calculado o forzado al wrapper
    if (maxLines > 0) {
      wrapper.style.setProperty('-webkit-line-clamp', maxLines.toString(), 'important');
    } else {
      wrapper.style.removeProperty('-webkit-line-clamp');
    }

    // 6. Marcar elemento y wrapper como listos para eliminar FOUC
    el.setAttribute('data-cut-phrase-ready', 'true');
    wrapper.setAttribute('data-cut-phrase-ready', 'true');
  }

  const adjustAll = () => {
    elements.forEach(el => {
      updateClamp(el);
    });
  };

  // Ajuste inicial
  adjustAll();

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
