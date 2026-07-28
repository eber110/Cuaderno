/**
 * Componente Remote Content - Módulo de pestañas/contenidos desacoplados.
 * Soporta transiciones animadas con GSAP si el contenedor tiene la clase 'animated'.
 *
 * @function remoteContent
 * @description Activa la navegación de contenidos remotos mediante botones (.remote-btn)
 *              desacoplados que muestran/ocultan paneles (.remote-content) dentro
 *              de contenedores (.remote-container) mediante el atributo data-remote.
 *
 * @example
 * // HTML - Botones en el sidebar
 * <p class="remote-btn" data-remote="tab-1">Cabecera</p>
 * <p class="remote-btn" data-remote="tab-2">Fondo</p>
 *
 * // HTML - Contenedor y contenidos en la zona principal
 * <div class="remote-container animated">
 *   <div id="tab-1" class="remote-content active">Contenido 1</div>
 *   <div id="tab-2" class="remote-content hidden">Contenido 2</div>
 * </div>
 *
 * @css .remote-btn      - El botón gatillo
 * @css .remote-container - El contenedor de contenidos
 * @css .remote-content   - El panel de contenido individual
 * @css .active          - Estado activo
 * @css .hidden          - Estado oculto
 *
 * @requires gsap - GreenSock Animation Platform (opcional)
 * @returns {void}
 */
export function remoteContent() {
  const hasGsap = typeof gsap !== 'undefined';

  const animConfig = {
    duration: 0.25,
    ease: 'power1.inOut'
  };

  /**
   * Realiza la transición animada entre contenidos usando GSAP.
   * @param {HTMLElement} outContent - Contenido saliente
   * @param {HTMLElement} inContent - Contenido entrante
   */
  function animateTransition(outContent, inContent) {
    gsap.to(outContent, {
      opacity: 0,
      duration: animConfig.duration * 0.6,
      ease: animConfig.ease,
      onComplete: () => {
        outContent.classList.remove('active');
        outContent.classList.add('hidden');
        gsap.set(outContent, { clearProps: 'opacity' });

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

  // Delegación del click para los botones remotos (.remote-btn)
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.remote-btn');
    if (!btn) return;

    const targetId = btn.dataset.remote;
    if (!targetId) return;

    // Si ya está activo este botón, no hacer nada
    if (btn.classList.contains('active')) return;

    const targetContent = document.getElementById(targetId);
    if (!targetContent) return;

    const container = targetContent.closest('.remote-container');
    if (!container) return;

    const currentActiveContent = container.querySelector('.remote-content.active');
    const useAnimation = hasGsap && container.classList.contains('animated') && container.hasAttribute('data-container-ready');

    // 1. Gestionar el estado de los botones (desacoplados)
    // Obtener los IDs de todos los contenidos del mismo contenedor
    const contentIds = Array.from(container.querySelectorAll('.remote-content')).map(el => el.id);

    // Remover la clase active de todos los botones que apunten a este contenedor
    document.querySelectorAll('.remote-btn').forEach(b => {
      if (contentIds.includes(b.dataset.remote)) {
        b.classList.remove('active');
      }
    });

    // Agregar la clase active al botón clickeado
    btn.classList.add('active');

    // 2. Transición del contenido
    if (useAnimation && currentActiveContent && currentActiveContent !== targetContent) {
      animateTransition(currentActiveContent, targetContent);
    } else {
      // Cambio inmediato (sin animación)
      container.querySelectorAll('.remote-content').forEach(content => {
        content.classList.remove('active');
        content.classList.add('hidden');
      });

      targetContent.classList.remove('hidden');
      targetContent.classList.add('active');
    }
  });

  // Inicialización: sincronizar botones y contenidos activos
  document.querySelectorAll('.remote-container').forEach(container => {
    const activeContent = container.querySelector('.remote-content.active');
    
    // Ocultar los contenidos no activos
    container.querySelectorAll('.remote-content').forEach(content => {
      if (content !== activeContent) {
        content.classList.add('hidden');
        content.classList.remove('active');
      }
    });

    if (activeContent) {
      // Activar los botones correspondientes al contenido activo por defecto
      document.querySelectorAll('.remote-btn').forEach(btn => {
        if (btn.dataset.remote === activeContent.id) {
          btn.classList.add('active');
        }
      });
    }

    // Si NO hay un menú vertical en la página, marcar el contenedor como listo de inmediato para evitar FOUC
    if (!document.querySelector('.vertical-menu')) {
      container.setAttribute('data-container-ready', 'true');
    }
  });
}
