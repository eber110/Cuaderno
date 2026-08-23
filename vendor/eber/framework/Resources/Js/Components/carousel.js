/**
 * Carrusel/slider de imágenes con efectos de transiciÃ³n.
 * 
 * @function carousel
 * @description Slider con navegaciÃ³n, autoplay y efectos de transiciÃ³n opcionales.
 * 
 * @example
 * // HTML - Básico (sin animaciÃ³n)
 * <div class="carousel" data-autoplay="5000">
 *   <div class="carousel-inner">
 *     <div class="carousel-item active">Slide 1</div>
 *     <div class="carousel-item">Slide 2</div>
 *   </div>
 *   <button class="carousel-prev">â®</button>
 *   <button class="carousel-next">â¯</button>
 *   <div class="carousel-dots"></div>
 * </div>
 * 
 * @example
 * // HTML - Con efecto slide (deslizamiento)
 * <div class="carousel carousel-slide" data-autoplay="4000">...</div>
 * 
 * @example
 * // HTML - Con efecto fade (desvanecimiento)
 * <div class="carousel carousel-fade" data-autoplay="4000">...</div>
 * 
 * @example
 * // HTML - Con slide continuo infinito
 * <div class="carousel carousel-slide-continuous" data-autoplay="4000">...</div>
 * 
 * @example
 * // HTML - Con separaciÃ³n entre slides (20px)
 * <div class="carousel carousel-slide-continuous" data-spacing="20">...</div>
 * 
 * @css .carousel - Contenedor principal
 * @css .carousel-slide - Activa efecto de deslizamiento
 * @css .carousel-fade - Activa efecto de desvanecimiento
 * @css .carousel-slide-continuous - Activa deslizamiento continuo infinito
 * @css .carousel-inner - Contenedor de slides
 * @css .carousel-item - Cada slide
 * @css .carousel-prev/.carousel-next - Botones de navegaciÃ³n
 * @attribute data-autoplay - Intervalo de autoplay en ms
 * @attribute data-spacing - SeparaciÃ³n entre slides en pÃ­xeles
 * 
 * @returns {void}
 */
export function carousel() {
  document.querySelectorAll('.carousel').forEach(carouselEl => {
    const inner = carouselEl.querySelector('.carousel-inner');
    const items = Array.from(carouselEl.querySelectorAll('.carousel-item'));
    const prevBtn = carouselEl.querySelector('.carousel-prev');
    const nextBtn = carouselEl.querySelector('.carousel-next');
    const dotsContainer = carouselEl.querySelector('.carousel-dots');

    if (!inner || items.length === 0) return;

    // Detectar modo de transiciÃ³n
    const isSlideMode = carouselEl.classList.contains('carousel-slide');
    const isFadeMode = carouselEl.classList.contains('carousel-fade');
    const isContinuousMode = carouselEl.classList.contains('carousel-slide-continuous');

    // Detectar separaciÃ³n entre slides (data-spacing="20" = 20px)
    const spacing = parseInt(carouselEl.dataset.spacing) || 0;

    // CSS ya oculta el carousel con :not([data-carousel-ready])
    // Solo ocultamos items si hay spacing para evitar flash durante wrapping
    if (spacing > 0) {
      items.forEach(item => {
        item.style.opacity = '0';
      });
    }

    const totalItems = items.length;
    let allItems = items; // Para modos normales
    let currentIndex = 0;
    let isTransitioning = false;

    // Aplicar spacing a los items originales ANTES de clonar
    // para que los clones hereden el wrapper
    if ((isSlideMode || isContinuousMode) && spacing > 0) {
      // Clases que deben moverse al wrapper (relacionadas con fondo/estilo visual)
      const bgClasses = ['back1', 'back2', 'back3', 'back4', 'back5', 'back6', 'back7',
        'back-success', 'back-error', 'back-warning', 'back-info',
        'br5', 'br10', 'br15', 'br20', 'br25', 'br30', 'br50', 'br100',
        'p5', 'p10', 'p15', 'p20', 'p25', 'p30'];

      items.forEach((item) => {
        if (!item.dataset.spacingApplied) {
          item.dataset.spacingApplied = 'true';

          // Crear wrapper para el contenido
          const wrapper = document.createElement('div');
          wrapper.className = 'carousel-item-content';
          wrapper.style.marginLeft = `${spacing / 2}px`;
          wrapper.style.marginRight = `${spacing / 2}px`;
          wrapper.style.height = '100%';

          // Mover clases de fondo y estilos del item al wrapper
          bgClasses.forEach(cls => {
            if (item.classList.contains(cls)) {
              wrapper.classList.add(cls);
              item.classList.remove(cls);
            }
          });

          // Copiar estilos inline de background si existen
          if (item.style.background) {
            wrapper.style.background = item.style.background;
            item.style.background = '';
          }
          if (item.style.backgroundColor) {
            wrapper.style.backgroundColor = item.style.backgroundColor;
            item.style.backgroundColor = '';
          }

          // Mover todo el contenido del item al wrapper
          while (item.firstChild) {
            wrapper.appendChild(item.firstChild);
          }
          item.appendChild(wrapper);
        }
      });
    }

    // Modo continuo: clonar primer y Ãºltimo slide (ahora con spacing aplicado)
    if (isContinuousMode) {
      const firstClone = items[0].cloneNode(true);
      const lastClone = items[items.length - 1].cloneNode(true);
      firstClone.classList.add('carousel-clone');
      lastClone.classList.add('carousel-clone');

      inner.appendChild(firstClone);
      inner.insertBefore(lastClone, items[0]);

      allItems = Array.from(inner.querySelectorAll('.carousel-item'));
      currentIndex = 1; // Empezar en el primer item real (despuÃ©s del clon)
    }

    // Calcular el ancho de cada item en porcentaje
    const itemWidthPercent = 100 / allItems.length;

    // Configurar estilos del inner segÃºn el modo
    if (isSlideMode || isContinuousMode) {
      inner.style.display = 'flex';
      inner.style.transition = 'transform 0.5s ease-in-out';
      inner.style.width = `${allItems.length * 100}%`;
    }

    // Configurar estilos de cada item (dimensiones)
    allItems.forEach((item, idx) => {
      if (isSlideMode || isContinuousMode) {
        item.style.flex = `0 0 ${itemWidthPercent}%`;
        item.style.width = `${itemWidthPercent}%`;
        item.style.boxSizing = 'border-box';
      } else if (isFadeMode) {
        item.style.position = idx === 0 ? 'relative' : 'absolute';
        item.style.top = '0';
        item.style.left = '0';
        item.style.width = '100%';
        item.style.opacity = idx === 0 ? '1' : '0';
        item.style.transition = 'opacity 0.5s ease-in-out';
        item.style.zIndex = idx === 0 ? '1' : '0';
      } else {
        // Modo bÃ¡sico: ocultar todos excepto el primero
        item.style.display = idx === 0 ? 'block' : 'none';
      }
    });

    // Asegurar que los botones prev/next estÃ©n siempre visibles sobre los slides
    if (prevBtn) {
      prevBtn.style.position = 'absolute';
      prevBtn.style.zIndex = '10';
    }
    if (nextBtn) {
      nextBtn.style.position = 'absolute';
      nextBtn.style.zIndex = '10';
    }

    let autoplayInterval = null;
    const autoplayDelay = parseInt(carouselEl.dataset.autoplay) || 0;

    // Crear dots si existe el contenedor (solo para items reales)
    if (dotsContainer) {
      for (let i = 0; i < totalItems; i++) {
        const dot = document.createElement('button');
        dot.className = `carousel-dot ${i === 0 ? 'active' : ''}`;
        dot.dataset.index = i;
        dotsContainer.appendChild(dot);
      }
    }

    function updateDots() {
      if (!dotsContainer) return;
      let realIndex = currentIndex;
      if (isContinuousMode) {
        realIndex = currentIndex - 1;
        if (realIndex < 0) realIndex = totalItems - 1;
        if (realIndex >= totalItems) realIndex = 0;
      }
      dotsContainer.querySelectorAll('.carousel-dot').forEach((dot, idx) => {
        dot.classList.toggle('active', idx === realIndex);
      });
    }

    // Verificar si GSAP está disponible
    const hasGsap = typeof gsap !== 'undefined';

    function goToSlide(index, instant = false) {
      if (isTransitioning && !instant) return;

      // Calcular el desplazamiento en porcentaje
      const slidePercent = 100 / allItems.length;

      if (isContinuousMode) {
        isTransitioning = true;
        currentIndex = index;

        if (hasGsap) {
          gsap.to(inner, {
            x: `-${currentIndex * slidePercent}%`,
            duration: instant ? 0 : 0.5,
            ease: 'power2.out',
            onComplete: () => {
              if (!instant) {
                // Saltar al slide real si estamos en un clon
                if (currentIndex === 0) {
                  currentIndex = totalItems;
                  gsap.set(inner, { x: `-${currentIndex * slidePercent}%` });
                } else if (currentIndex === allItems.length - 1) {
                  currentIndex = 1;
                  gsap.set(inner, { x: `-${currentIndex * slidePercent}%` });
                }
              }
              isTransitioning = false;
            }
          });
        } else {
          if (instant) {
            inner.style.transition = 'none';
          } else {
            inner.style.transition = 'transform 0.5s ease-in-out';
          }

          inner.style.transform = `translateX(-${currentIndex * slidePercent}%)`;

          if (!instant) {
            setTimeout(() => {
              // Saltar al slide real si estamos en un clon
              if (currentIndex === 0) {
                currentIndex = totalItems;
                inner.style.transition = 'none';
                inner.style.transform = `translateX(-${currentIndex * slidePercent}%)`;
              } else if (currentIndex === allItems.length - 1) {
                currentIndex = 1;
                inner.style.transition = 'none';
                inner.style.transform = `translateX(-${currentIndex * slidePercent}%)`;
              }
              isTransitioning = false;
            }, 500);
          } else {
            isTransitioning = false;
          }
        }

        allItems.forEach((item, idx) => {
          item.classList.toggle('active', idx === currentIndex);
        });

        updateDots();

      } else if (isSlideMode) {
        currentIndex = (index + totalItems) % totalItems;
        
        if (hasGsap) {
          isTransitioning = true;
          gsap.to(inner, {
            x: `-${currentIndex * slidePercent}%`,
            duration: instant ? 0 : 0.5,
            ease: 'power2.out',
            onComplete: () => { isTransitioning = false; }
          });
        } else {
          inner.style.transform = `translateX(-${currentIndex * slidePercent}%)`;
        }

        allItems.forEach((item, idx) => {
          item.classList.toggle('active', idx === currentIndex);
        });
        updateDots();

      } else if (isFadeMode) {
        currentIndex = (index + totalItems) % totalItems;
        
        if (hasGsap) {
          isTransitioning = true;
          allItems.forEach((item, idx) => {
            const isActive = idx === currentIndex;
            if (isActive) {
              item.style.position = 'relative';
              gsap.to(item, {
                opacity: 1,
                duration: instant ? 0 : 0.5,
                ease: 'power2.out',
                zIndex: 1,
                onComplete: () => { isTransitioning = false; }
              });
            } else {
              gsap.to(item, {
                opacity: 0,
                duration: instant ? 0 : 0.5,
                ease: 'power2.out',
                zIndex: 0,
                onComplete: () => {
                  item.style.position = 'absolute';
                }
              });
            }
            item.classList.toggle('active', isActive);
          });
        } else {
          allItems.forEach((item, idx) => {
            const isActive = idx === currentIndex;
            item.style.opacity = isActive ? '1' : '0';
            item.style.zIndex = isActive ? '1' : '0';
            item.style.position = isActive ? 'relative' : 'absolute';
            item.classList.toggle('active', isActive);
          });
        }
        updateDots();

      } else {
        // Modo básico
        currentIndex = (index + totalItems) % totalItems;
        allItems.forEach((item, idx) => {
          item.classList.toggle('active', idx === currentIndex);
          item.style.display = idx === currentIndex ? 'block' : 'none';
        });
        updateDots();
      }
    }

    function nextSlide() {
      goToSlide(currentIndex + 1);
    }

    function prevSlide() {
      goToSlide(currentIndex - 1);
    }

    function goToDotIndex(dotIndex) {
      if (isContinuousMode) {
        goToSlide(dotIndex + 1); // +1 porque hay un clon al inicio
      } else {
        goToSlide(dotIndex);
      }
    }

    // Event listeners
    if (nextBtn) nextBtn.addEventListener('click', nextSlide);
    if (prevBtn) prevBtn.addEventListener('click', prevSlide);

    if (dotsContainer) {
      dotsContainer.addEventListener('click', (e) => {
        const dot = e.target.closest('.carousel-dot');
        if (dot) goToDotIndex(parseInt(dot.dataset.index));
      });
    }

    // Autoplay
    if (autoplayDelay > 0) {
      autoplayInterval = setInterval(nextSlide, autoplayDelay);

      carouselEl.addEventListener('mouseenter', () => clearInterval(autoplayInterval));
      carouselEl.addEventListener('mouseleave', () => {
        autoplayInterval = setInterval(nextSlide, autoplayDelay);
      });
    }

    // Soporte para gestos de swipe/drag (touch y mouse)
    let isDragging = false;
    let startX = 0;
    let currentX = 0;
    let dragThreshold = 50; // PÃ­xeles mÃ­nimos para considerar un swipe

    function handleDragStart(e) {
      if (isTransitioning) return;
      isDragging = true;
      startX = e.type.includes('mouse') ? e.clientX : e.touches[0].clientX;
      currentX = startX;

      // Pausar autoplay durante el drag
      if (autoplayInterval) clearInterval(autoplayInterval);

      // Deshabilitar transiciÃ³n durante el drag
      if (isSlideMode || isContinuousMode) {
        inner.style.transition = 'none';
      }
    }

    function handleDragMove(e) {
      if (!isDragging) return;
      e.preventDefault();
      currentX = e.type.includes('mouse') ? e.clientX : e.touches[0].clientX;

      // Mover el carousel siguiendo el dedo/mouse
      if (isSlideMode || isContinuousMode) {
        const diff = currentX - startX;
        const slidePercent = 100 / allItems.length;
        const baseTranslate = currentIndex * slidePercent;
        const dragPercent = (diff / carouselEl.offsetWidth) * slidePercent;
        inner.style.transform = `translateX(-${baseTranslate - dragPercent}%)`;
      }
    }

    function handleDragEnd() {
      if (!isDragging) return;
      isDragging = false;

      const diff = currentX - startX;

      // Restaurar transiciÃ³n
      if (isSlideMode || isContinuousMode) {
        inner.style.transition = 'transform 0.5s ease-in-out';
      }

      // Determinar si fue un swipe vÃ¡lido
      if (Math.abs(diff) > dragThreshold) {
        if (diff > 0) {
          prevSlide(); // Swipe a la derecha = slide anterior
        } else {
          nextSlide(); // Swipe a la izquierda = slide siguiente
        }
      } else {
        // Volver a la posiciÃ³n actual si el swipe fue muy corto
        goToSlide(currentIndex, false);
      }

      // Reanudar autoplay
      if (autoplayDelay > 0) {
        autoplayInterval = setInterval(nextSlide, autoplayDelay);
      }
    }

    // Eventos touch
    inner.addEventListener('touchstart', handleDragStart, { passive: true });
    inner.addEventListener('touchmove', handleDragMove, { passive: false });
    inner.addEventListener('touchend', handleDragEnd);
    inner.addEventListener('touchcancel', handleDragEnd);

    // Eventos mouse (para arrastrar con el mouse)
    inner.addEventListener('mousedown', handleDragStart);
    inner.addEventListener('mousemove', handleDragMove);
    inner.addEventListener('mouseup', handleDragEnd);
    inner.addEventListener('mouseleave', handleDragEnd);

    // Prevenir arrastre de imÃ¡genes
    inner.addEventListener('dragstart', (e) => e.preventDefault());

    // Inicializar primer slide
    goToSlide(isContinuousMode ? 1 : 0, true);

    // Mostrar carousel despuÃ©s de que todo estÃ© construido
    // Usamos doble rAF + setTimeout para evitar FOUC
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        // PequeÃ±o delay para asegurar que los estilos se aplicaron
        setTimeout(() => {
          // Restaurar opacidad de los items (si se ocultaron por spacing)
          allItems.forEach(item => {
            item.style.opacity = '1';
          });
          // Marcar como listo - CSS mostrarÃ¡ el carousel con transiciÃ³n
          carouselEl.style.transition = 'opacity 0.3s ease';
          carouselEl.dataset.carouselReady = 'true';
        }, 100);
      });
    });
  });
}
