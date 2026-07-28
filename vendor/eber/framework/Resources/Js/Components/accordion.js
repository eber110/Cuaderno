/**
 * Sistema de paneles colapsables (accordion).
 * Incluye animaciones GSAP opcionales con clase 'animated'.
 * 
 * @function accordion
 * @description Permite expandir/colapsar secciones de contenido.
 *              Solo una sección puede estar abierta a la vez por defecto.
 * 
 * @example
 * // HTML - Sin animación
 * <div class="accordion">
 *   <div class="accordion-item">
 *     <div class="accordion-header">Título 1</div>
 *     <div class="accordion-content hidden">Contenido...</div>
 *   </div>
 * </div>
 * 
 * @example
 * // HTML - Con animación GSAP
 * <div class="accordion animated">
 *   <div class="accordion-item">
 *     <div class="accordion-header">Título 1</div>
 *     <div class="accordion-content hidden">Contenido...</div>
 *   </div>
 * </div>
 * 
 * @css .accordion - Contenedor principal
 * @css .accordion.animated - Activa animaciones GSAP
 * @css .accordion-item - Cada panel individual
 * @css .accordion-header - Cabecera clickeable
 * @css .accordion-content - Contenido colapsable
 * @css .active - Indica item expandido
 * 
 * @requires gsap - GreenSock Animation Platform (opcional)
 * @returns {void}
 */
export function accordion() {
  // Verificar si GSAP está disponible
  const hasGsap = typeof gsap !== 'undefined';

  // Configuración de animación
  const animConfig = {
    duration: 0.35,
    ease: 'power2.out',
    easeClose: 'power2.in'
  };

  /**
   * Anima la apertura del contenido del accordion.
   * @param {HTMLElement} content - Contenido a animar
   * @param {HTMLElement} item - Item del accordion
   */
  function animateOpen(content, item) {
    item.classList.add('active');
    content.classList.remove('hidden');

    const height = content.scrollHeight;

    gsap.fromTo(content,
      { height: 0, opacity: 0 },
      {
        height: height,
        opacity: 1,
        duration: animConfig.duration,
        ease: animConfig.ease,
        onComplete: () => {
          content.style.height = 'auto';
        }
      }
    );
  }

  /**
   * Anima el cierre del contenido del accordion.
   * @param {HTMLElement} content - Contenido a animar
   * @param {HTMLElement} item - Item del accordion
   */
  function animateClose(content, item) {
    gsap.to(content, {
      height: 0,
      opacity: 0,
      duration: animConfig.duration * 0.7,
      ease: animConfig.easeClose,
      onComplete: () => {
        item.classList.remove('active');
        content.classList.add('hidden');
        gsap.set(content, { clearProps: 'height,opacity' });
      }
    });
  }

  /**
   * Cierra el contenido sin animación.
   * @param {HTMLElement} content - Contenido a cerrar
   * @param {HTMLElement} item - Item del accordion
   */
  function closeWithoutAnimation(content, item) {
    item.classList.remove('active');
    content.style.maxHeight = null;
    content.classList.add('hidden');
  }

  /**
   * Abre el contenido sin animación.
   * @param {HTMLElement} content - Contenido a abrir
   * @param {HTMLElement} item - Item del accordion
   */
  function openWithoutAnimation(content, item) {
    item.classList.add('active');
    content.classList.remove('hidden');
    content.style.maxHeight = content.scrollHeight + 'px';
  }

  document.addEventListener('click', (e) => {
    const header = e.target.closest('.accordion-header');
    if (!header) return;

    const item = header.closest('.accordion-item');
    const accordion = header.closest('.accordion');
    if (!item || !accordion) return;

    const content = item.querySelector('.accordion-content');
    if (!content) return;

    const isActive = item.classList.contains('active');
    const allowMultiple = accordion.classList.contains('multi');
    const useAnimation = hasGsap && accordion.classList.contains('animated');

    // Si no permite múltiples, cerrar todos los demás
    if (!allowMultiple) {
      accordion.querySelectorAll('.accordion-item.active').forEach(activeItem => {
        if (activeItem !== item) {
          const activeContent = activeItem.querySelector('.accordion-content');
          if (activeContent) {
            if (useAnimation) {
              animateClose(activeContent, activeItem);
            } else {
              closeWithoutAnimation(activeContent, activeItem);
            }
          }
        }
      });
    }

    // Toggle del item actual
    if (isActive) {
      if (useAnimation) {
        animateClose(content, item);
      } else {
        closeWithoutAnimation(content, item);
      }
    } else {
      if (useAnimation) {
        animateOpen(content, item);
      } else {
        openWithoutAnimation(content, item);
      }
    }
  });

  // Inicializar items activos
  document.querySelectorAll('.accordion-item.active .accordion-content').forEach(content => {
    content.classList.remove('hidden');
    content.style.maxHeight = content.scrollHeight + 'px';
  });
}
