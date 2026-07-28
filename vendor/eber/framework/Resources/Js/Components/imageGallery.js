/**
 * Galería de imágenes con miniaturas de navegación.
 * 
 * @function imageGallery
 * @description Slider de imágenes con miniaturas clickeables como navegación.
 *              Soporta múltiples modos de transición, swipe/drag y autoplay.
 * 
 * @example
 * // HTML - Galería básica (modo slide por defecto)
 * <div class="image-gallery">
 *   <div class="gallery-main">
 *     <div class="gallery-item active"><img src="img1.jpg"></div>
 *     <div class="gallery-item"><img src="img2.jpg"></div>
 *   </div>
 *   <button class="gallery-prev">❮</button>
 *   <button class="gallery-next">❯</button>
 *   <div class="gallery-thumbs"></div>
 * </div>
 * 
 * @example
 * // HTML - Con efecto slide continuo (loop infinito)
 * <div class="image-gallery gallery-slide-continuous" data-autoplay="4000">...</div>
 * 
 * @example
 * // HTML - Con efecto fade (desvanecimiento)
 * <div class="image-gallery gallery-fade" data-autoplay="3000">...</div>
 * 
 * @example
 * // HTML - Con miniaturas personalizadas
 * <div class="image-gallery">
 *   <div class="gallery-main">
 *     <div class="gallery-item" data-thumb="thumb1.jpg"><img src="full1.jpg"></div>
 *   </div>
 *   <div class="gallery-thumbs"></div>
 * </div>
 * 
 * @css .image-gallery - Contenedor principal (modo slide básico)
 * @css .gallery-slide-continuous - Activa deslizamiento continuo infinito
 * @css .gallery-fade - Activa efecto de desvanecimiento
 * @css .gallery-main - Contenedor de slides principales
 * @css .gallery-item - Cada slide de imagen
 * @css .gallery-prev/.gallery-next - Botones de navegación
 * @css .gallery-thumbs - Contenedor de miniaturas (generadas automáticamente)
 * @attribute data-autoplay - Intervalo de autoplay en ms
 * @attribute data-thumb - URL de miniatura personalizada (en gallery-item)
 * 
 * @returns {void}
 */
export function imageGallery() {
  document.querySelectorAll('.image-gallery').forEach(galleryEl => {
    const main = galleryEl.querySelector('.gallery-main');
    const items = Array.from(galleryEl.querySelectorAll('.gallery-item'));
    const prevBtn = galleryEl.querySelector('.gallery-prev');
    const nextBtn = galleryEl.querySelector('.gallery-next');
    const thumbsContainer = galleryEl.querySelector('.gallery-thumbs');

    if (!main || items.length === 0) return;

    // Detectar modo de transición
    const isContinuousMode = galleryEl.classList.contains('gallery-slide-continuous');
    const isFadeMode = galleryEl.classList.contains('gallery-fade');
    const isSlideMode = !isFadeMode; // Slide es el modo por defecto

    const totalItems = items.length;
    let allItems = items;
    let currentIndex = 0;
    let isTransitioning = false;
    let autoplayInterval = null;
    const autoplayDelay = parseInt(galleryEl.dataset.autoplay) || 0;

    // Configurar estilos del contenedor principal
    main.style.position = 'relative';
    main.style.overflow = 'hidden';
    main.style.width = '100%';

    let inner = null;

    // ========== MODO FADE ==========
    if (isFadeMode) {
      items.forEach((item, idx) => {
        item.style.position = idx === 0 ? 'relative' : 'absolute';
        item.style.top = '0';
        item.style.left = '0';
        item.style.width = '100%';
        item.style.height = '100%';
        item.style.opacity = idx === 0 ? '1' : '0';
        item.style.transition = 'opacity 0.5s ease-in-out';
        item.style.zIndex = idx === 0 ? '1' : '0';
      });
    }
    // ========== MODO SLIDE / SLIDE-CONTINUOUS ==========
    else {
      inner = document.createElement('div');
      inner.className = 'gallery-inner';
      inner.style.display = 'flex';
      inner.style.height = '100%';
      inner.style.transition = 'transform 0.4s ease-in-out';

      // Modo continuo: clonar primer y último slide
      if (isContinuousMode) {
        const firstClone = items[0].cloneNode(true);
        const lastClone = items[items.length - 1].cloneNode(true);
        firstClone.classList.add('gallery-clone');
        lastClone.classList.add('gallery-clone');

        // Insertar clones
        items.forEach(item => inner.appendChild(item));
        inner.appendChild(firstClone);
        inner.insertBefore(lastClone, items[0]);

        allItems = Array.from(inner.querySelectorAll('.gallery-item'));
        currentIndex = 1; // Empezar después del clon
      } else {
        items.forEach(item => inner.appendChild(item));
      }

      // Calcular anchos
      const itemWidthPercent = 100 / allItems.length;
      inner.style.width = `${allItems.length * 100}%`;

      allItems.forEach(item => {
        item.style.flex = `0 0 ${itemWidthPercent}%`;
        item.style.width = `${itemWidthPercent}%`;
        item.style.height = '100%';
      });

      main.appendChild(inner);
    }

    // Generar miniaturas (solo para items originales, no clones)
    if (thumbsContainer) {
      thumbsContainer.style.display = 'flex';
      thumbsContainer.style.gap = '8px';
      thumbsContainer.style.justifyContent = 'center';
      thumbsContainer.style.marginTop = '10px';
      thumbsContainer.style.overflowX = 'auto';

      items.forEach((item, idx) => {
        const thumb = document.createElement('div');
        thumb.className = `gallery-thumb ${idx === 0 ? 'active' : ''}`;
        thumb.dataset.index = idx;
        thumb.style.cursor = 'pointer';
        thumb.style.opacity = idx === 0 ? '1' : '0.5';
        thumb.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        thumb.style.flexShrink = '0';

        let thumbSrc = item.dataset.thumb;
        if (!thumbSrc) {
          const img = item.querySelector('img');
          if (img) thumbSrc = img.src;
        }

        if (thumbSrc) {
          const thumbImg = document.createElement('img');
          thumbImg.src = thumbSrc;
          thumbImg.alt = `Miniatura ${idx + 1}`;
          thumbImg.style.width = '60px';
          thumbImg.style.height = '45px';
          thumbImg.style.objectFit = 'cover';
          //thumbImg.style.borderRadius = '4px';
          thumbImg.style.border = idx === 0 ? '2px solid var(--color2)' : '2px solid transparent';
          thumb.appendChild(thumbImg);
        } else {
          thumb.style.width = '60px';
          thumb.style.height = '45px';
          thumb.style.background = '#ddd';
          //thumb.style.borderRadius = '4px';
          thumb.textContent = idx + 1;
          thumb.style.display = 'flex';
          thumb.style.alignItems = 'center';
          thumb.style.justifyContent = 'center';
        }

        thumbsContainer.appendChild(thumb);
      });
    }

    function updateThumbs() {
      if (!thumbsContainer) return;

      // Calcular índice real para miniaturas (ajustar para modo continuo)
      let realIndex = currentIndex;
      if (isContinuousMode) {
        realIndex = currentIndex - 1;
        if (realIndex < 0) realIndex = totalItems - 1;
        if (realIndex >= totalItems) realIndex = 0;
      }

      thumbsContainer.querySelectorAll('.gallery-thumb').forEach((thumb, idx) => {
        const isActive = idx === realIndex;
        thumb.classList.toggle('active', isActive);
        thumb.style.zIndex = 5;
        thumb.style.opacity = isActive ? '1' : '0.5';
        thumb.style.transform = isActive ? 'scale(1.1)' : 'scale(1)';

        const thumbImg = thumb.querySelector('img');
        if (thumbImg) {
          thumbImg.style.border = isActive ? '2px solid var(--color2)' : '2px solid transparent';
        }
      });
    }

    // Verificar si GSAP está disponible
    const hasGsap = typeof gsap !== 'undefined';

    function goToSlide(index, instant = false) {
      if (isTransitioning && !instant) return;

      const slidePercent = 100 / allItems.length;

      // ========== MODO FADE ==========
      if (isFadeMode) {
        currentIndex = (index + totalItems) % totalItems;
        
        if (hasGsap) {
          isTransitioning = true;
          allItems.forEach((item, idx) => {
            const isActive = idx === currentIndex;
            if (isActive) {
              item.style.position = 'relative';
              gsap.to(item, {
                opacity: 1,
                duration: instant ? 0 : 0.4,
                ease: 'power2.out',
                zIndex: 1,
                onComplete: () => { isTransitioning = false; }
              });
            } else {
              gsap.to(item, {
                opacity: 0,
                duration: instant ? 0 : 0.4,
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
        updateThumbs();
      }
      // ========== MODO SLIDE CONTINUO ==========
      else if (isContinuousMode) {
        isTransitioning = true;
        currentIndex = index;

        if (hasGsap) {
          gsap.to(inner, {
            x: `-${currentIndex * slidePercent}%`,
            duration: instant ? 0 : 0.4,
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
            inner.style.transition = 'transform 0.4s ease-in-out';
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
            }, 400);
          } else {
            isTransitioning = false;
          }
        }

        allItems.forEach((item, idx) => {
          item.classList.toggle('active', idx === currentIndex);
        });

        updateThumbs();
      }
      // ========== MODO SLIDE BÁSICO ==========
      else {
        currentIndex = (index + totalItems) % totalItems;
        
        if (hasGsap) {
          isTransitioning = true;
          gsap.to(inner, {
            x: `-${currentIndex * slidePercent}%`,
            duration: instant ? 0 : 0.4,
            ease: 'power2.out',
            onComplete: () => { isTransitioning = false; }
          });
        } else {
          inner.style.transform = `translateX(-${currentIndex * slidePercent}%)`;
        }

        allItems.forEach((item, idx) => {
          item.classList.toggle('active', idx === currentIndex);
        });

        updateThumbs();
      }
    }

    function goToThumbIndex(thumbIndex) {
      if (isContinuousMode) {
        goToSlide(thumbIndex + 1); // +1 porque hay un clon al inicio
      } else {
        goToSlide(thumbIndex);
      }
    }

    function nextSlide() {
      goToSlide(currentIndex + 1);
    }

    function prevSlide() {
      goToSlide(currentIndex - 1);
    }

    // Event listeners para botones
    if (nextBtn) nextBtn.addEventListener('click', nextSlide);
    if (prevBtn) prevBtn.addEventListener('click', prevSlide);

    // Event listener para miniaturas
    if (thumbsContainer) {
      thumbsContainer.addEventListener('click', (e) => {
        const thumb = e.target.closest('.gallery-thumb');
        if (thumb) {
          goToThumbIndex(parseInt(thumb.dataset.index));
        }
      });
    }

    // Autoplay
    if (autoplayDelay > 0) {
      autoplayInterval = setInterval(nextSlide, autoplayDelay);

      galleryEl.addEventListener('mouseenter', () => clearInterval(autoplayInterval));
      galleryEl.addEventListener('mouseleave', () => {
        autoplayInterval = setInterval(nextSlide, autoplayDelay);
      });
    }

    // Soporte para gestos de swipe/drag (solo para modos slide)
    if (isSlideMode && inner) {
      let isDragging = false;
      let startX = 0;
      let currentX = 0;
      const dragThreshold = 50;

      function handleDragStart(e) {
        if (isTransitioning) return;
        isDragging = true;
        startX = e.type.includes('mouse') ? e.clientX : e.touches[0].clientX;
        currentX = startX;
        if (autoplayInterval) clearInterval(autoplayInterval);
        inner.style.transition = 'none';
      }

      function handleDragMove(e) {
        if (!isDragging) return;
        e.preventDefault();
        currentX = e.type.includes('mouse') ? e.clientX : e.touches[0].clientX;

        const diff = currentX - startX;
        const slidePercent = 100 / allItems.length;
        const baseTranslate = currentIndex * slidePercent;
        const dragPercent = (diff / galleryEl.offsetWidth) * slidePercent;
        inner.style.transform = `translateX(-${baseTranslate - dragPercent}%)`;
      }

      function handleDragEnd() {
        if (!isDragging) return;
        isDragging = false;

        const diff = currentX - startX;
        inner.style.transition = 'transform 0.4s ease-in-out';

        if (Math.abs(diff) > dragThreshold) {
          if (diff > 0) {
            prevSlide();
          } else {
            nextSlide();
          }
        } else {
          goToSlide(currentIndex);
        }

        if (autoplayDelay > 0) {
          autoplayInterval = setInterval(nextSlide, autoplayDelay);
        }
      }

      // Eventos touch
      main.addEventListener('touchstart', handleDragStart, { passive: true });
      main.addEventListener('touchmove', handleDragMove, { passive: false });
      main.addEventListener('touchend', handleDragEnd);
      main.addEventListener('touchcancel', handleDragEnd);

      // Eventos mouse
      main.addEventListener('mousedown', handleDragStart);
      main.addEventListener('mousemove', handleDragMove);
      main.addEventListener('mouseup', handleDragEnd);
      main.addEventListener('mouseleave', handleDragEnd);

      // Prevenir arrastre de imágenes
      main.addEventListener('dragstart', (e) => e.preventDefault());
    }

    // Navegación con teclado
    galleryEl.setAttribute('tabindex', '0');
    galleryEl.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft') prevSlide();
      if (e.key === 'ArrowRight') nextSlide();
    });

    // Inicializar
    goToSlide(isContinuousMode ? 1 : 0, true);

    // Mostrar gallery después de que todo esté construido
    // Usamos doble rAF + setTimeout para evitar FOUC
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        // Pequeño delay para asegurar que los estilos se aplicaron
        setTimeout(() => {
          galleryEl.style.transition = 'opacity 0.3s ease';
          galleryEl.dataset.galleryReady = 'true';
        }, 100);
      });
    });
  });
}
