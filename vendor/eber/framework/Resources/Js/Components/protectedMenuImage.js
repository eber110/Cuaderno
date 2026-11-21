/**
 * Protege las imágenes contra el menú contextual del navegador (clic derecho)
 * y opcionalmente evita que sean arrastradas (drag).
 *
 * @function protectedMenuImage
 * @description Bloquea el menú contextual sobre etiquetas `<img>` y permite deshabilitar el arrastre de imágenes mediante parámetro.
 *
 * @param {boolean} [allowDrag=true] - Si es `true` (por defecto), permite arrastrar imágenes; si es `false`, bloquea el evento `dragstart`.
 *
 * @example
 * // HTML - Todas las imágenes del documento quedan protegidas automáticamente
 * <img src="/assets/galeria/obra.webp" alt="Obra protegida">
 *
 * @example
 * // Configuración en jsConfig.json (bloquear también arrastre)
 * "protectedMenuImage": [false]
 *
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
