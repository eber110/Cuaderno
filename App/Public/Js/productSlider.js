/**
 * Controlador de carrusel de productos (Modo Slide).
 * 
 * @function productSlider
 * @description Maneja el desplazamiento horizontal suave, botones de navegación anterior/siguiente
 *              y gestos de arrastre táctil / mouse para grupos de productos en modo slide.
 * 
 * @returns {void}
 */
export function productSlider() {
  const initSlider = (container) => {
    if (container.__ps_initialized) return;
    container.__ps_initialized = true;

    const track = container.querySelector('.product-group-slide-track');
    const prevBtn = container.querySelector('.product-slide-prev');
    const nextBtn = container.querySelector('.product-slide-next');

    if (!track) return;

    // Actualiza la visibilidad de los botones según la posición del scroll
    const updateButtons = () => {
      const scrollLeft = track.scrollLeft;
      const clientWidth = track.clientWidth;
      const scrollWidth = track.scrollWidth;

      // Si todo el contenido cabe en la pantalla sin desborde
      if (scrollWidth <= clientWidth + 5) {
        if (prevBtn) prevBtn.classList.add('is-hidden');
        if (nextBtn) nextBtn.classList.add('is-hidden');
        return;
      }

      if (prevBtn) {
        if (scrollLeft <= 5) {
          prevBtn.classList.add('is-hidden');
        } else {
          prevBtn.classList.remove('is-hidden');
        }
      }

      if (nextBtn) {
        if (scrollLeft + clientWidth >= scrollWidth - 5) {
          nextBtn.classList.add('is-hidden');
        } else {
          nextBtn.classList.remove('is-hidden');
        }
      }
    };

    const getScrollStep = () => {
      const firstCard = track.querySelector('.product-slide-card');
      if (!firstCard) return Math.max(180, track.clientWidth * 0.75);
      const gap = parseFloat(window.getComputedStyle(track).gap) || 14;
      return firstCard.offsetWidth + gap;
    };

    // Navegación con flechas
    if (prevBtn) {
      prevBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const step = getScrollStep();
        if (track.scrollLeft <= step * 1.2) {
          track.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
          track.scrollBy({ left: -step, behavior: 'smooth' });
        }
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const step = getScrollStep();
        track.scrollBy({ left: step, behavior: 'smooth' });
      });
    }


    // Eventos de scroll
    track.addEventListener('scroll', updateButtons, { passive: true });

    // Soporte para arrastre con ratón (Desktop drag)
    let isDown = false;
    let startX = 0;
    let scrollStart = 0;
    let isDragging = false;

    track.addEventListener('mousedown', (e) => {
      // Ignorar si se hace clic en botones de modal o menús
      if (e.target.closest('.modal-btn') || e.target.closest('.product-slide-btn')) return;

      isDown = true;
      isDragging = false;
      startX = e.pageX - track.offsetLeft;
      scrollStart = track.scrollLeft;
      track.style.scrollBehavior = 'auto';
    });

    window.addEventListener('mousemove', (e) => {
      if (!isDown) return;
      const x = e.pageX - track.offsetLeft;
      const walk = (x - startX);

      if (Math.abs(walk) > 4) {
        isDragging = true;
      }

      if (isDragging) {
        e.preventDefault();
        track.scrollLeft = scrollStart - walk;
      }
    });

    const stopDragging = () => {
      if (!isDown) return;
      isDown = false;
      track.style.scrollBehavior = 'smooth';
      setTimeout(() => {
        isDragging = false;
        updateButtons();
      }, 50);
    };

    window.addEventListener('mouseup', stopDragging);
    track.addEventListener('mouseleave', stopDragging);

    // Evitar abrir enlaces accidentalmente al arrastrar
    track.addEventListener('click', (e) => {
      if (isDragging) {
        e.preventDefault();
        e.stopPropagation();
      }
    }, true);

    // Prevenir arrastre nativo de imágenes y recalcular botones tras cargar
    track.querySelectorAll('img').forEach((img) => {
      img.addEventListener('dragstart', (e) => e.preventDefault());
      if (!img.complete) {
        img.addEventListener('load', () => updateButtons(), { once: true });
      }
    });

    // Actualización inicial
    requestAnimationFrame(() => {
      updateButtons();
      setTimeout(updateButtons, 150);
      setTimeout(updateButtons, 500);
    });

    // Actualización en redimensionamiento
    if (window.ResizeObserver) {
      const ro = new ResizeObserver(() => updateButtons());
      ro.observe(track);
    }
  };

  const initAll = () => {
    document.querySelectorAll('.product-group-slide-container').forEach(initSlider);
  };

  initAll();

  // Observador para elementos agregados dinámicamente
  const observer = new MutationObserver((mutations) => {
    let shouldInit = false;
    for (let m of mutations) {
      if (m.addedNodes.length > 0) {
        shouldInit = true;
        break;
      }
    }
    if (shouldInit) initAll();
  });

  observer.observe(document.body, { childList: true, subtree: true });

  // Escuchar cuando se actualiza la vista previa en vivo en el dashboard
  document.addEventListener('previewUpdated', () => {
    initAll();
  });
}
