/**
 * Controlador de carrusel infinito continuo para el mockup del smartphone en el Hero.
 *
 * @function heroPhoneSlider
 * @description Avanza siempre en un único sentido (1 -> 2 -> ... -> N -> 1) sin rebobinar
 *              hacia atrás al llegar al final, mediante clones en los extremos y reinicio
 *              instantáneo invisible al completar la transición CSS.
 *
 * @css .hero-phone-slider - Contenedor principal con atributo opcional data-autoplay (ms).
 * @css .hero-phone-track - Pista flex horizontal animada con transform.
 * @css .hero-phone-slide - Cada slide individual (100% del ancho visible).
 * @returns {void}
 */
export function heroPhoneSlider() {
  const sliders = document.querySelectorAll(".hero-phone-slider");
  if (!sliders.length) return;

  sliders.forEach((slider) => {
    if (slider.__hps_initialized) return;
    slider.__hps_initialized = true;

    const track = slider.querySelector(".hero-phone-track");
    if (!track) return;

    const originalSlides = Array.from(track.querySelectorAll(".hero-phone-slide"));
    const total = originalSlides.length;

    if (total === 0) return;

    if (total === 1) {
      slider.dataset.sliderReady = "true";
      return;
    }

    const prefersReducedMotion =
      window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    const firstClone = originalSlides[0].cloneNode(true);
    const lastClone = originalSlides[total - 1].cloneNode(true);
    firstClone.classList.add("is-clone");
    lastClone.classList.add("is-clone");
    firstClone.classList.remove("is-active");
    lastClone.classList.remove("is-active");
    firstClone.setAttribute("aria-hidden", "true");
    lastClone.setAttribute("aria-hidden", "true");

    track.insertBefore(lastClone, originalSlides[0]);
    track.appendChild(firstClone);

    let currentIndex = 1;
    let isTransitioning = false;
    let transitionSafetyTimer = null;
    let autoplayTimer = null;

    const autoplayDelay = parseInt(slider.dataset.autoplay || "3000", 10);

    const setInstantPosition = (index) => {
      track.style.transition = "none";
      track.style.transform = `translate3d(-${index * 100}%, 0, 0)`;
      void track.offsetWidth;
    };

    const syncActiveClass = (index) => {
      let realIdx = index - 1;
      if (realIdx < 0) realIdx = total - 1;
      if (realIdx >= total) realIdx = 0;

      originalSlides.forEach((slide, idx) => {
        slide.classList.toggle("is-active", idx === realIdx);
      });
    };

    const finishTransition = () => {
      if (transitionSafetyTimer) {
        clearTimeout(transitionSafetyTimer);
        transitionSafetyTimer = null;
      }

      if (currentIndex >= total + 1) {
        currentIndex = 1;
        setInstantPosition(currentIndex);
      } else if (currentIndex <= 0) {
        currentIndex = total;
        setInstantPosition(currentIndex);
      }

      syncActiveClass(currentIndex);
      isTransitioning = false;
    };

    track.addEventListener("transitionend", (e) => {
      if (e.target !== track || e.propertyName !== "transform") return;
      if (!isTransitioning) return;
      finishTransition();
    });

    const goTo = (targetIndex, animate = true) => {
      if (isTransitioning && animate) return;

      if (transitionSafetyTimer) {
        clearTimeout(transitionSafetyTimer);
        transitionSafetyTimer = null;
      }

      currentIndex = targetIndex;

      if (!animate || prefersReducedMotion) {
        if (currentIndex >= total + 1) currentIndex = 1;
        if (currentIndex <= 0) currentIndex = total;
        setInstantPosition(currentIndex);
        syncActiveClass(currentIndex);
        isTransitioning = false;
        return;
      }

      isTransitioning = true;
      track.style.transition = "transform 0.55s cubic-bezier(0.22, 1, 0.36, 1)";
      track.style.transform = `translate3d(-${currentIndex * 100}%, 0, 0)`;
      syncActiveClass(currentIndex);

      transitionSafetyTimer = setTimeout(() => {
        if (isTransitioning) {
          finishTransition();
        }
      }, 650);
    };

    const stopAutoplay = () => {
      if (autoplayTimer) {
        clearInterval(autoplayTimer);
        autoplayTimer = null;
      }
    };

    const startAutoplay = () => {
      stopAutoplay();
      if (autoplayDelay > 0 && !document.hidden) {
        autoplayTimer = setInterval(() => {
          goTo(currentIndex + 1, true);
        }, autoplayDelay);
      }
    };

    // Posición inicial en el primer slide real (sin animación)
    setInstantPosition(currentIndex);
    syncActiveClass(currentIndex);

    // Pausa en hover y al ocultar pestaña
    slider.addEventListener("mouseenter", stopAutoplay);
    slider.addEventListener("mouseleave", startAutoplay);

    document.addEventListener("visibilitychange", () => {
      if (document.hidden) {
        stopAutoplay();
      } else {
        startAutoplay();
      }
    });

    // Soporte táctil y arrastre con mouse
    let isDragging = false;
    let startX = 0;
    let deltaX = 0;
    const dragThreshold = 45;

    const getClientX = (e) => (e.touches && e.touches.length ? e.touches[0].clientX : e.clientX);

    const onDragStart = (e) => {
      if (isTransitioning) return;
      isDragging = true;
      startX = getClientX(e);
      deltaX = 0;
      stopAutoplay();
      track.style.transition = "none";
    };

    const onDragMove = (e) => {
      if (!isDragging) return;
      deltaX = getClientX(e) - startX;
      const width = slider.offsetWidth || 1;
      const dragPercent = (deltaX / width) * 100;
      track.style.transform = `translate3d(calc(-${currentIndex * 100}% + ${dragPercent}%), 0, 0)`;
    };

    const onDragEnd = () => {
      if (!isDragging) return;
      isDragging = false;

      if (Math.abs(deltaX) > dragThreshold) {
        if (deltaX < 0) {
          goTo(currentIndex + 1, true);
        } else {
          goTo(currentIndex - 1, true);
        }
      } else {
        goTo(currentIndex, true);
      }

      startAutoplay();
    };

    slider.addEventListener("touchstart", onDragStart, { passive: true });
    slider.addEventListener("touchmove", onDragMove, { passive: true });
    slider.addEventListener("touchend", onDragEnd);
    slider.addEventListener("touchcancel", onDragEnd);

    slider.addEventListener("mousedown", onDragStart);
    window.addEventListener("mousemove", onDragMove);
    window.addEventListener("mouseup", onDragEnd);

    slider.dataset.sliderReady = "true";
    startAutoplay();
  });
}
