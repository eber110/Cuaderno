/**
 * Controlador de carrusel infinito vertical de productos (GSAP).
 * 
 * @function productInfiniteCarousel
 * @description Maneja un desplazamiento continuo e infinito de abajo hacia arriba utilizando GSAP,
 *              con detección dinámica de altura, loop continuo sin saltos, desaceleración suave
 *              en hover y escalado interactivo de las tarjetas individuales al pasar el cursor.
 * 
 * @returns {void}
 */
export function productInfiniteCarousel() {
  const container = document.querySelector(".product-vertical-carousel");
  if (!container) return;

  // Evitar doble inicialización
  if (container.__pic_initialized) return;
  container.__pic_initialized = true;

  const track = container.querySelector(".product-carousel-track");
  if (!track) return;

  const sets = track.querySelectorAll(".product-carousel-set");
  if (sets.length === 0) return;

  let carouselTween = null;
  let singleSetHeight = 0;

  const init = () => {
    if (typeof gsap === "undefined") {
      setTimeout(init, 50);
      return;
    }

  /**
   * Calcula la distancia exacta entre el primer set y el segundo para el reinicio continuo.
   */
  const calculateDistance = () => {
    if (sets.length < 2) return 0;

    // Medición precisa en el sistema de coordenadas del track
    const offsetDiff = sets[1].offsetTop - sets[0].offsetTop;
    if (offsetDiff > 0) return offsetDiff;

    // Fallback con bounding client rect y gap computado
    const setRect = sets[0].getBoundingClientRect();
    const style = window.getComputedStyle(track);
    const gap = parseFloat(style.gap) || parseFloat(style.rowGap) || 0;

    return setRect.height + gap;
  };

  /**
   * Inicializa o reinicia la animación continua de GSAP.
   */
  const startLoop = () => {
    singleSetHeight = calculateDistance();
    if (singleSetHeight <= 0) return;

    const currentProgress = carouselTween ? carouselTween.progress() : 0;
    const currentTimeScale = carouselTween ? carouselTween.timeScale() : 1;

    if (carouselTween) {
      carouselTween.kill();
    }

    // Velocidad constante en píxeles por segundo (aprox 35px/s para lectura suave)
    const pixelsPerSecond = 35;
    const duration = Math.max(12, singleSetHeight / pixelsPerSecond);

    carouselTween = gsap.fromTo(track, 
      { y: 0 },
      {
        y: -singleSetHeight,
        duration: duration,
        ease: "none",
        repeat: -1
      }
    );

    // Restaurar progreso y timeScale si venía de un redimensionamiento
    if (currentProgress > 0) {
      carouselTween.progress(currentProgress);
    }
    if (currentTimeScale !== 1) {
      carouselTween.timeScale(currentTimeScale);
    }
  };

  // Interacción suave con las tarjetas
  const cards = track.querySelectorAll(".product-carousel-item");

  cards.forEach((card) => {
    // Escalar al pasar el ratón
    card.addEventListener("mouseenter", () => {
      gsap.to(card, {
        scale: 1.07,
        zIndex: 25,
        duration: 0.35,
        ease: "power2.out",
        overwrite: "auto"
      });

      // Ralentizar suavemente el carrusel para inspección cómoda
      if (carouselTween) {
        gsap.to(carouselTween, {
          timeScale: 0.15,
          duration: 0.4,
          overwrite: "auto"
        });
      }
    });

    // Volver a tamaño normal al retirar el ratón
    card.addEventListener("mouseleave", () => {
      gsap.to(card, {
        scale: 1,
        zIndex: 1,
        duration: 0.35,
        ease: "power2.out",
        overwrite: "auto"
      });

      // Reanudar la velocidad habitual del carrusel
      if (carouselTween) {
        gsap.to(carouselTween, {
          timeScale: 1,
          duration: 0.4,
          overwrite: "auto"
        });
      }
    });
  });

  // Pausar si el usuario cambia de pestaña para optimizar recursos
  document.addEventListener("visibilitychange", () => {
    if (!carouselTween) return;
    if (document.hidden) {
      carouselTween.pause();
    } else {
      carouselTween.resume();
    }
  });

  // Asegurar recálculo tras carga completa de todas las imágenes
  const images = track.querySelectorAll("img");
  images.forEach((img) => {
    if (!img.complete) {
      img.addEventListener("load", () => {
        startLoop();
      }, { once: true });
    }
  });

  // Ajuste en cambio de tamaño de pantalla
  let resizeTimeout = null;
  const handleResize = () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      startLoop();
    }, 150);
  };

  if (window.ResizeObserver) {
    const ro = new ResizeObserver(handleResize);
    ro.observe(container);
  } else {
    window.addEventListener("resize", handleResize);
  }

    // Inicio inmediato
    requestAnimationFrame(() => {
      startLoop();
      setTimeout(startLoop, 300);
    });
  };

  init();
}
