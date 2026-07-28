// Modulo para ocultar las rutas de navegacion en la parte inferior del navegador
export function hideNavigationPath() {

  /**
   * Convierte un enlace 'a' con href a un estado seguro en reposo (sin href).
   * Al remover el 'href' en la carga de la página, el navegador no muestra absolutamente nada (100% limpio)
   * en la barra de estado inferior durante el hover, evitando que aparezca 'javascript:void(0);' o cualquier texto.
   * Preservamos el cursor pointer y accesibilidad por teclado inline para no alterar en absoluto ningún estilo de la app.
   * Durante interacciones reales (mousedown, contextmenu, dragstart, focus), restauramos temporalmente el href
   * real para soportar copiar dirección, abrir en nueva pestaña, arrastrar y accesibilidad nativa.
   */
  function convertLink(link) {
    if (link.dataset.secureLinkBound) return;
    link.dataset.secureLinkBound = "true";

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

    // Remover el href del DOM de forma permanente en reposo para ocultar el status bar por completo (100% limpio)
    link.removeAttribute('href');

    // Funciones auxiliares para cambiar y restaurar el href de forma transparente al interactuar
    const restoreRealHref = () => {
      if (link.dataset.href) {
        link.setAttribute('href', link.dataset.href);
      }
    };

    const removeHref = () => {
      link.removeAttribute('href');
    };

    // Restauramos el href en interacciones clave del usuario (clic derecho, arrastrar, foco)
    link.addEventListener('mousedown', restoreRealHref);
    link.addEventListener('contextmenu', restoreRealHref);
    link.addEventListener('focus', restoreRealHref);
    link.addEventListener('dragstart', restoreRealHref);

    // Volver a enmascarar tras terminar la interacción o cuando el cursor/foco abandona el elemento
    link.addEventListener('mouseup', () => {
      // Usamos un retraso de 100ms para asegurar que el navegador procese el clic nativo o menú contextual antes de ocultar
      setTimeout(removeHref, 100);
    });
    
    link.addEventListener('mouseleave', removeHref);
    link.addEventListener('blur', removeHref);

    // 1. Manejo del clic estándar (izquierdo)
    link.addEventListener('click', function (e) {
      const url = this.dataset.href;
      if (!url) return;

      // Si se hizo clic con modificadores de teclado (Ctrl, Shift, Command/Meta),
      // dejamos que el navegador actúe de manera nativa ya que restauramos el href en mousedown
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

    // 2. Soporte para clic central (rueda del mouse) mediante auxclick
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

  // MutationObserver para dar soporte a enlaces cargados dinámicamente (AJAX, SPA, galerías, etc.)
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

  observer.observe(document.body, { childList: true, subtree: true });
}

