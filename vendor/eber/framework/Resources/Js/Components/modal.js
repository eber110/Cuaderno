/**
 * Sistema de modales con overlay.
 * Incluye animaciones GSAP opcionales con clase 'animated'.
 * 
 * @function modal
 * @description Crea modales dinámicos copiando el contenido del elemento
 *              siguiente al botón activador.
 * 
 * @example
 * // HTML - Sin animación
 * <button class="modal-btn">Abrir Modal</button>
 * <div class="hidden">
 *   <h2>Contenido del Modal</h2>
 * </div>
 * 
 * @example
 * // HTML - Con animación GSAP
 * <button class="modal-btn animated">Abrir Modal</button>
 * <div class="hidden">
 *   <h2>Contenido del Modal</h2>
 * </div>
 * 
 * @css .modal-btn - Botón que abre el modal
 * @css .modal-btn.animated - Activa animaciones GSAP
 * @css .modal-overlay - Overlay de fondo (generado)
 * @css .modal-close-button - Botón de cerrar (generado)
 * @css .modal-content-area - Área de contenido (generada)
 * 
 * @requires gsap - GreenSock Animation Platform (opcional)
 * @returns {void}
 */
export function modal() {
  const body = document.querySelector('body');

  // Verificar si GSAP está disponible
  const hasGsap = typeof gsap !== 'undefined';

  // Configuración de animación
  const animConfig = {
    duration: 0.35,
    ease: 'power2.out',
    easeClose: 'power2.in'
  };

  /**
   * Anima la apertura del modal.
   * @param {HTMLElement} overlay - Overlay del modal
   * @param {HTMLElement} content - Contenido del modal
   */
  function animateOpen(overlay, content) {
    gsap.fromTo(overlay,
      { opacity: 0 },
      { opacity: 1, duration: animConfig.duration, ease: animConfig.ease }
    );

    gsap.fromTo(content,
      { opacity: 0, scale: 0.9, y: 20 },
      {
        opacity: 1,
        scale: 1,
        y: 0,
        duration: animConfig.duration,
        ease: 'back.out(1.2)',
        delay: 0.05
      }
    );
  }

  /**
   * Anima el cierre del modal.
   * @param {HTMLElement} overlay - Overlay del modal
   * @param {HTMLElement} content - Contenido del modal
   * @param {Function} onComplete - Callback al completar
   */
  function animateClose(overlay, content, onComplete) {
    gsap.to(content, {
      opacity: 0,
      scale: 0.9,
      y: 20,
      duration: animConfig.duration * 0.6,
      ease: animConfig.easeClose
    });

    gsap.to(overlay, {
      opacity: 0,
      duration: animConfig.duration * 0.7,
      ease: animConfig.easeClose,
      onComplete: onComplete
    });
  }

  document.addEventListener("click", (e) => {
    const clickedElement = e.target;

    // --- ABRIR MODAL ---
    const btnModal = clickedElement.closest(".modal-btn");
    if (btnModal) {
      e.preventDefault();
      e.stopPropagation();
      const originalModalContentSource = btnModal.nextElementSibling;
      const useAnimation = hasGsap && btnModal.classList.contains('animated');
      const hasDarken = btnModal.classList.contains('darken');

      if (originalModalContentSource) {
        // Crear Overlay
        const background = document.createElement('section');
        background.className = "modal-overlay before-form";
        background.dataset.animated = useAnimation ? 'true' : 'false';
        background.style.position = "fixed";
        background.style.top = "0";
        background.style.left = "0";
        background.style.width = "100%";
        background.style.height = "100%";
        
        if (hasDarken) {
          background.style.backgroundColor = "rgba(0, 0, 0, 0.5)";
          background.style.backdropFilter = "none";
        } else {
          background.style.backgroundColor = "rgba(14, 33, 60, 0.2)";
          background.style.backdropFilter = "blur(5px)";
        }
        
        background.style.display = "flex";
        background.style.justifyContent = "center";
        background.style.alignItems = "center";
        background.style.zIndex = "99999";

        // Crear Botón de Cerrar
        const btnClosed = document.createElement('div');
        btnClosed.id = "close-modal-btn";
        btnClosed.className = "modal-close-button hidden";

        // Crear Contenedor de Contenido
        const modalContentContainer = document.createElement('div');
        modalContentContainer.className = "w100 modal-content-area flex column-direction center-center";
        modalContentContainer.innerHTML = originalModalContentSource.innerHTML;

        // Ensamblar
        background.appendChild(btnClosed);
        background.appendChild(modalContentContainer);
        body.appendChild(background);

        // Remover flag de bindeo previo copiado por innerHTML para poder re-vincularlos en el modal
        modalContentContainer.querySelectorAll('[data-gsap-hover-bound]').forEach(el => {
          el.removeAttribute('data-gsap-hover-bound');
        });

        // Re-inicializar animaciones hover para los nuevos elementos dentro del modal
        if (typeof initGsapHoverAnimations === 'function') {
          initGsapHoverAnimations();
        }

        // Notificar a componentes dinámicos (cutPhrase, galerías, etc.) que el contenido del modal está en el DOM
        document.dispatchEvent(new CustomEvent('contentUpdated'));

        // Animar si corresponde
        if (useAnimation) {
          animateOpen(background, modalContentContainer);
        }
      } else {
        console.warn("Modal: no se encontró contenido para el botón:", btnModal);
      }
    }

    // --- CERRAR MODAL ---
    const btnClose = clickedElement.closest(".modal-close-button");
    const overlayClicked = clickedElement.classList.contains("modal-overlay");

    if (btnClose || overlayClicked) {
      const modalToClose = clickedElement.closest(".modal-overlay");
      if (modalToClose) {
        const useAnimation = hasGsap && modalToClose.dataset.animated === 'true';
        const content = modalToClose.querySelector('.modal-content-area');

        if (useAnimation && content) {
          animateClose(modalToClose, content, () => modalToClose.remove());
        } else {
          modalToClose.remove();
        }
      }
    }
  });
}
