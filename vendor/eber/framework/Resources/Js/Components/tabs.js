/**
 * Sistema de pestañas (tabs).
 * Incluye animaciones GSAP opcionales con clase 'animated'.
 * 
 * @function tabs
 * @description Permite cambiar entre diferentes paneles de contenido
 *              usando pestañas navegables.
 * 
 * @example
 * // HTML - Sin animación
 * <div class="tabs">
 *   <div class="tabs-nav">
 *     <button class="tab-btn active" data-tab="tab1">Tab 1</button>
 *     <button class="tab-btn" data-tab="tab2">Tab 2</button>
 *   </div>
 *   <div class="tab-content active" id="tab1">Contenido 1</div>
 *   <div class="tab-content" id="tab2">Contenido 2</div>
 * </div>
 * 
 * @example
 * // HTML - Con animación GSAP
 * <div class="tabs animated">
 *   ...
 * </div>
 * 
 * @css .tabs - Contenedor principal
 * @css .tabs.animated - Activa animaciones GSAP
 * @css .tabs-nav - Contenedor de botones de navegación
 * @css .tab-btn - Botón de pestaña
 * @css .tab-content - Panel de contenido
 * @css .active - Indica tab/contenido activo
 * 
 * @requires gsap - GreenSock Animation Platform (opcional)
 * @returns {void}
 */
export function tabs() {
  // Verificar si GSAP está disponible
  const hasGsap = typeof gsap !== 'undefined';

  // Configuración de animación
  const animConfig = {
    duration: 0.25,
    ease: 'power1.inOut'
  };

  /**
   * Realiza la transición animada entre tabs.
   * @param {HTMLElement} outContent - Contenido saliente
   * @param {HTMLElement} inContent - Contenido entrante
   */
  function animateTransition(outContent, inContent) {
    // Fade out del contenido actual
    gsap.to(outContent, {
      opacity: 0,
      duration: animConfig.duration * 0.6,
      ease: animConfig.ease,
      onComplete: () => {
        outContent.classList.remove('active');
        outContent.classList.add('hidden');
        gsap.set(outContent, { clearProps: 'opacity' });

        // Fade in del nuevo contenido
        inContent.classList.remove('hidden');
        inContent.classList.add('active');

        gsap.fromTo(inContent,
          { opacity: 0 },
          {
            opacity: 1,
            duration: animConfig.duration,
            ease: animConfig.ease
          }
        );
      }
    });
  }

  document.addEventListener('click', (e) => {
    const tabBtn = e.target.closest('.tab-btn');
    if (!tabBtn) return;

    const tabsContainer = tabBtn.closest('.tabs');
    if (!tabsContainer) return;

    const targetId = tabBtn.dataset.tab;
    if (!targetId) return;

    // Si ya está activo, no hacer nada
    if (tabBtn.classList.contains('active')) return;

    const useAnimation = hasGsap && tabsContainer.classList.contains('animated');
    const currentActiveContent = tabsContainer.querySelector('.tab-content.active');
    const targetContent = tabsContainer.querySelector(`#${targetId}`);

    if (!targetContent) return;

    // Desactivar todos los botones
    tabsContainer.querySelectorAll('.tab-btn').forEach(btn => {
      btn.classList.remove('active');
    });

    // Activar el botón clickeado
    tabBtn.classList.add('active');

    if (useAnimation && currentActiveContent && currentActiveContent !== targetContent) {
      // Animación de transición
      animateTransition(currentActiveContent, targetContent);
    } else {
      // Sin animación
      tabsContainer.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
        content.classList.add('hidden');
      });

      targetContent.classList.remove('hidden');
      targetContent.classList.add('active');
    }
  });

  // Inicializar: mostrar el primer tab si ninguno está activo
  document.querySelectorAll('.tabs').forEach(tabsContainer => {
    const activeBtn = tabsContainer.querySelector('.tab-btn.active');
    if (activeBtn) {
      const targetId = activeBtn.dataset.tab;
      const targetContent = tabsContainer.querySelector(`#${targetId}`);
      if (targetContent) {
        targetContent.classList.remove('hidden');
        targetContent.classList.add('active');
      }
    }
  });
}
