/**
 * Oculta las rutas de navegación en la barra de estado inferior del navegador.
 *
 * @function hideNavigationPath
 * @description Remueve el atributo `href` en reposo almacenándolo en `data-href`
 *              para mantener limpia la barra de estado del navegador, restaurándolo
 *              transparentemente durante interacciones reales (clic, foco, menú contextual, arrastre).
 *
 * @returns {void}
 */
export function hideNavigationPath() {
  /**
   * Convierte un enlace 'a' con href a un estado seguro en reposo (sin href).
   *
   * @param {HTMLAnchorElement} link
   */
  function convertLink(link) {
    if (link.dataset.secureLinkBound) return;
    link.dataset.secureLinkBound = 'true';

    const originalHref = link.getAttribute('href');
    // Omitir anclas locales o javascript vacío
    if (!originalHref || originalHref.startsWith('javascript:') || originalHref.startsWith('#')) {
      return;
    }

    // Almacenar el enlace original en un data-attribute seguro
    link.dataset.href = originalHref;

    // Preservar el cursor pointer y la accesibilidad por teclado
    link.style.cursor = 'pointer';
    if (!link.hasAttribute('tabindex')) {
      link.setAttribute('tabindex', '0');
    }

    // Remover el href del DOM en reposo para ocultar el status bar por completo
    link.removeAttribute('href');

    const restoreRealHref = () => {
      if (link.dataset.href) {
        link.setAttribute('href', link.dataset.href);
      }
    };

    const removeHref = () => {
      link.removeAttribute('href');
    };

    // Restaurar href en interacciones clave (clic derecho, arrastrar, foco)
    link.addEventListener('mousedown', restoreRealHref);
    link.addEventListener('contextmenu', restoreRealHref);
    link.addEventListener('focus', restoreRealHref);
    link.addEventListener('dragstart', restoreRealHref);

    link.addEventListener('mouseup', () => {
      setTimeout(removeHref, 100);
    });

    link.addEventListener('mouseleave', removeHref);
    link.addEventListener('blur', removeHref);

    // 1. Manejo del clic estándar (izquierdo)
    link.addEventListener('click', function (e) {
      const url = this.dataset.href;
      if (!url) return;

      if (e.ctrlKey || e.shiftKey || e.metaKey) {
        return;
      }

      e.preventDefault();
      if (this.getAttribute('target') === '_blank') {
        window.open(url, '_blank');
      } else {
        window.location.href = url;
      }
    });

    // 2. Soporte para clic central (rueda del mouse)
    link.addEventListener('auxclick', function (e) {
      if (e.button === 1 && this.dataset.href) {
        e.preventDefault();
        window.open(this.dataset.href, '_blank');
      }
    });

    // 3. Soporte para accesibilidad de teclado (Enter / Espacio)
    link.addEventListener('keydown', function (e) {
      if ((e.key === 'Enter' || e.key === ' ') && this.dataset.href) {
        e.preventDefault();
        if (this.getAttribute('target') === '_blank') {
          window.open(this.dataset.href, '_blank');
        } else {
          window.location.href = this.dataset.href;
        }
      }
    });
  }

  // Convertir enlaces existentes en el DOM al cargar
  document.querySelectorAll('a[href]').forEach(convertLink);

  // MutationObserver para enlaces cargados dinámicamente (AJAX, SPA, galerías, etc.)
  const observer = new MutationObserver(mutations => {
    mutations.forEach(mutation => {
      mutation.addedNodes.forEach(node => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          if (node.tagName === 'A' && node.hasAttribute('href')) {
            convertLink(node);
          }
          node.querySelectorAll('a[href]').forEach(convertLink);
        }
      });
    });
  });

  if (document.body) {
    observer.observe(document.body, { childList: true, subtree: true });
  }
}
