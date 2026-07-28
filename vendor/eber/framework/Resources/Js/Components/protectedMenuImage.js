/**
 * Protege las imágenes contra el menú contextual del navegador (clic derecho)
 * y opcionalmente evita que sean arrastradas (drag).
 * 
 * @function protectedMenuImage
 * @param {boolean} [allowDrag=true] - Si es true (por defecto), permite arrastrar imágenes. Si es false, lo bloquea.
 * @returns {void}
 */
export function protectedMenuImage(allowDrag = true) {
  // Evitar menú contextual en todas las imágenes mediante delegación en document
  document.addEventListener('contextmenu', (e) => {
    if (e.target && e.target.tagName === 'IMG') {
      e.preventDefault();
    }
  }, true);

  // Si allowDrag es false, evitar que las imágenes puedan ser arrastradas
  if (allowDrag === false) {
    document.addEventListener('dragstart', (e) => {
      if (e.target && e.target.tagName === 'IMG') {
        e.preventDefault();
      }
    }, true);
  }
}
