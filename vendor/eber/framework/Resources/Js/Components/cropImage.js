/**
 * Componente para seleccionar y recortar una imagen antes de subirla.
 * Soporta recorte cuadrado/circular, rotación opcional, gestos táctiles (pan + pinch-to-zoom),
 * tokens CSS (--ui-*) y retrocompatibilidad con box-image / box-btn-image.
 *
 * @function cropImage
 * @description Convierte inputs de tipo file con la clase `.selectAndCropImage` en un cargador
 *              interactivo con visor modal de recorte antes del envío.
 *
 * @example
 * <input type="file"
 *        name="avatar"
 *        accept="image/*"
 *        class="selectAndCropImage btn-style-classes no-preview process-auto-submit"
 *        placeholder="Elige una imagen"
 *        cropping-size="500x500"
 *        crop-shape="circle"
 *        ui-surface="back1 texto br15 p20"
 *        ui-btn-save="back6 textw br15 p10"
 *        ui-btn-cancel="back2 texto br15 p10">
 *
 * @returns {void}
 */
export function cropImage() {
  const ui = window.FmeUi || null;
  const tr = (k, p, f) => (ui ? ui.t(k, p, f) : (typeof window.t === 'function' ? window.t(k, p, f) : f));
  const icon = (name, fb) => (ui && ui.ICONS && ui.ICONS[name]) ? ui.ICONS[name] : fb;

  function renderPreviews(input, previewArea) {
    previewArea.innerHTML = '';

    if (previewArea._objectUrl) {
      URL.revokeObjectURL(previewArea._objectUrl);
      previewArea._objectUrl = null;
    }

    if (input.classList.contains('no-preview')) {
      previewArea.style.display = 'none';
      return;
    }
    previewArea.style.display = '';

    if (input.files && input.files.length > 0) {
      previewArea.classList.remove('mt0');
      previewArea.classList.add('mt10');

      const file = input.files[0];
      const imgUrl = URL.createObjectURL(file);
      previewArea._objectUrl = imgUrl;

      const isCircle = (input.getAttribute('crop-shape') || '').toLowerCase() === 'circle';
      const item = document.createElement('div');
      item.className = `crop-img-preview-item relative m5 ${isCircle ? 'br100' : 'br10'} overflow-hidden wpx80 hpx80`;

      const img = document.createElement('img');
      img.src = imgUrl;
      img.className = 'w100 h100 cover';
      img.style.pointerEvents = 'none';
      item.appendChild(img);

      const deleteBtn = document.createElement('button');
      deleteBtn.type = 'button';
      deleteBtn.className = 'ui-img-delete-btn';
      deleteBtn.setAttribute('aria-label', tr('remove_item', {}, 'Eliminar'));
      deleteBtn.innerHTML = icon('close', '✕');

      deleteBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        input.value = '';
        renderPreviews(input, previewArea);
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });

      item.appendChild(deleteBtn);
      previewArea.appendChild(item);
    } else {
      previewArea.classList.remove('mt10');
      previewArea.classList.add('mt0');
    }
  }

  function openCropModal(input, previewArea, imgUrl, originalFile, options = {}) {
    const inlineHost = options.inlineHost || null;
    const isInline = !!inlineHost;
    const croppingSize = input.getAttribute('cropping-size') || '400x400';
    const [targetW, targetH] = croppingSize.split('x').map((num) => parseInt(num, 10) || 400);
    const aspectRatio = targetW / targetH;
    const isCircle = (input.getAttribute('crop-shape') || '').toLowerCase() === 'circle';
    const allowRotate = input.getAttribute('data-rotate') === 'true';

    // Tamaño responsive del visor para soportar pantallas móviles estrechas sin desbordar
    const containerSize = Math.min(320, Math.max(220, window.innerWidth - 76));
    const containerW = containerSize;
    const containerH = containerSize;
    const maxBox = Math.round(containerSize * 0.875);
    let boxW = maxBox;
    let boxH = maxBox;

    if (aspectRatio >= 1) {
      boxH = maxBox / aspectRatio;
    } else {
      boxW = maxBox * aspectRatio;
    }

    const boxLeft = (containerW - boxW) / 2;
    const boxTop = (containerH - boxH) / 2;

    // Resolver clases por zona con soporte granular y retrocompatibilidad
    const uiSurface = input.getAttribute('ui-surface') || input.getAttribute('box-image') || '';
    const uiSave = input.getAttribute('ui-btn-save') || input.getAttribute('ui-btn-primary') || '';
    const uiCancel = input.getAttribute('ui-btn-cancel') || input.getAttribute('ui-btn-secondary') || '';
    const legacyBtn = input.getAttribute('box-btn-image') || '';

    let saveClasses = ['crop-btn', 'crop-btn-save', 'ui-btn', 'ui-btn-primary'];
    let cancelClasses = ['crop-btn', 'crop-btn-cancel', 'ui-btn', 'ui-btn-secondary'];

    if (uiSave) {
      saveClasses.push(...uiSave.split(/\s+/).filter(Boolean));
    } else if (legacyBtn) {
      saveClasses.push(...legacyBtn.split(/\s+/).filter(Boolean));
    }

    if (uiCancel) {
      cancelClasses.push(...uiCancel.split(/\s+/).filter(Boolean));
    } else if (legacyBtn) {
      const legacyOthers = legacyBtn.split(/\s+/).filter((c) => c && !c.startsWith('back'));
      cancelClasses.push(...legacyOthers);
    }

    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'crop-modal-overlay';
    if (ui) ui.copyUiVars(input, modalOverlay);

    const cardEl = document.createElement('div');
    cardEl.className = isInline ? 'crop-modal-card is-inline-preview' : 'crop-modal-card';
    if (isInline && ui) ui.copyUiVars(input, cardEl);
    if (uiSurface) {
      if (ui) ui.applyClasses(cardEl, uiSurface);
      else uiSurface.split(/\s+/).filter(Boolean).forEach((c) => cardEl.classList.add(c));
    }

    const maskId = `crop-mask-hole-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
    const cropTitle = input.getAttribute('data-title') || tr('crop_title', {}, 'Recortar Imagen');
    const cropAlt = tr('crop_alt', {}, 'A recortar');
    const cancelLabel = input.getAttribute('data-cancel-label') || tr('cancel', {}, 'Cancelar');
    const saveLabel = input.getAttribute('data-save-label') || tr('crop_save', {}, 'Recortar y Guardar');

    const maskShapeSvg = isCircle
      ? `<circle cx="${containerW / 2}" cy="${containerH / 2}" r="${Math.min(boxW, boxH) / 2}" fill="black" />`
      : `<rect x="${boxLeft}" y="${boxTop}" width="${boxW}" height="${boxH}" rx="4" fill="black" />`;

    const rotateHtml = allowRotate
      ? `<div class="crop-rotate-row" style="display:flex; justify-content:center; gap:8px;">
           <button type="button" class="crop-zoom-btn crop-rot-left" title="${tr('rotate_left', {}, 'Rotar izquierda')}" aria-label="${tr('rotate_left', {}, 'Rotar izquierda')}">${icon('rotateLeft', '↺')}</button>
           <button type="button" class="crop-zoom-btn crop-rot-right" title="${tr('rotate_right', {}, 'Rotar derecha')}" aria-label="${tr('rotate_right', {}, 'Rotar derecha')}">${icon('rotateRight', '↻')}</button>
         </div>`
      : '';

    cardEl.innerHTML = `
      <div class="crop-modal-title">${cropTitle}</div>
      <div class="crop-container" style="width:${containerW}px; height:${containerH}px;">
        <img src="${imgUrl}" class="crop-image" alt="${cropAlt}" style="top:0; left:0; opacity:0;">
        <svg class="crop-mask" width="${containerW}" height="${containerH}" style="position:absolute; top:0; left:0; z-index:2; pointer-events:none;">
          <defs>
            <mask id="${maskId}">
              <rect width="${containerW}" height="${containerH}" fill="white" />
              ${maskShapeSvg}
            </mask>
          </defs>
          <rect width="${containerW}" height="${containerH}" fill="rgba(0, 0, 0, 0.58)" mask="url(#${maskId})" />
        </svg>
        <div class="crop-box-outline ${isCircle ? 'is-circle' : ''}" style="left:${boxLeft}px; top:${boxTop}px; width:${boxW}px; height:${boxH}px;"></div>
      </div>
      <div class="crop-controls">
        <div class="crop-zoom-container">
          <button type="button" class="crop-zoom-btn crop-zoom-minus" title="${tr('zoom_out', {}, 'Alejar')}" aria-label="${tr('zoom_out', {}, 'Alejar')}">${icon('zoomOut', '-')}</button>
          <input type="range" class="crop-zoom-slider" min="1" max="3" step="0.01" value="1" aria-label="Zoom">
          <button type="button" class="crop-zoom-btn crop-zoom-plus" title="${tr('zoom_in', {}, 'Acercar')}" aria-label="${tr('zoom_in', {}, 'Acercar')}">${icon('zoomIn', '+')}</button>
        </div>
        ${rotateHtml}
      </div>
      <div class="crop-buttons">
        <button type="button" class="${cancelClasses.join(' ')}">${cancelLabel}</button>
        <button type="button" class="${saveClasses.join(' ')}">${saveLabel}</button>
      </div>
    `;

    if (isInline) {
      inlineHost.innerHTML = '';
      inlineHost.appendChild(cardEl);
    } else {
      modalOverlay.appendChild(cardEl);
      document.body.appendChild(modalOverlay);
      setTimeout(() => modalOverlay.classList.add('active'), 20);
    }

    const img = cardEl.querySelector('.crop-image');
    const zoomSlider = cardEl.querySelector('.crop-zoom-slider');
    const btnZoomMinus = cardEl.querySelector('.crop-zoom-minus');
    const btnZoomPlus = cardEl.querySelector('.crop-zoom-plus');
    const btnRotLeft = cardEl.querySelector('.crop-rot-left');
    const btnRotRight = cardEl.querySelector('.crop-rot-right');
    const btnCancel = cardEl.querySelector('.crop-btn-cancel');
    const btnSave = cardEl.querySelector('.crop-btn-save');
    const outlineBox = cardEl.querySelector('.crop-box-outline');
    const cropContainer = cardEl.querySelector('.crop-container');

    img.style.top = '0px';
    img.style.left = '0px';
    img.style.maxWidth = 'none';
    img.style.maxHeight = 'none';

    let zoom = 1;
    let rotation = 0;
    let dragX = 0;
    let dragY = 0;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let minScale = 1;
    let imgW = 0;
    let imgH = 0;

    // Estado para Pinch-to-Zoom táctil con 2 dedos
    let pinchStartDist = 0;
    let pinchStartZoom = 1;

    function updateTransform() {
      img.style.transform = `translate(${dragX}px, ${dragY}px) rotate(${rotation}deg) scale(${zoom})`;
    }

    function setZoom(nextZoom) {
      zoom = Math.max(1, Math.min(3, nextZoom));
      zoomSlider.value = String(zoom);
      updateTransform();
    }

    const centerCropImage = () => {
      if (!img.naturalWidth || !img.naturalHeight) return;
      imgW = img.naturalWidth;
      imgH = img.naturalHeight;
      const scaleX = boxW / imgW;
      const scaleY = boxH / imgH;
      minScale = Math.max(scaleX, scaleY);

      const baseW = imgW * minScale;
      const baseH = imgH * minScale;
      img.style.width = `${baseW}px`;
      img.style.height = `${baseH}px`;

      dragX = (containerW - baseW) / 2;
      dragY = (containerH - baseH) / 2;
      updateTransform();
      img.style.opacity = '1';
    };

    img.addEventListener('load', centerCropImage);
    if (img.complete && img.naturalWidth > 0) {
      centerCropImage();
    }

    const startDrag = (clientX, clientY) => {
      isDragging = true;
      startX = clientX - dragX;
      startY = clientY - dragY;
    };

    const moveDrag = (clientX, clientY) => {
      if (!isDragging) return;
      dragX = clientX - startX;
      dragY = clientY - startY;
      updateTransform();
    };

    const stopDrag = () => {
      isDragging = false;
    };

    // Mouse drag
    cropContainer.addEventListener('mousedown', (e) => {
      e.preventDefault();
      startDrag(e.clientX, e.clientY);
    });
    const onMouseMove = (e) => {
      if (!cardEl.isConnected) {
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
        return;
      }
      moveDrag(e.clientX, e.clientY);
    };
    const onMouseUp = () => {
      if (!cardEl.isConnected) {
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
        return;
      }
      stopDrag();
    };
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);

    // Touch pan (1 dedo) + Pinch-to-zoom (2 dedos)
    const getTouchDist = (t1, t2) => Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY);

    cropContainer.addEventListener('touchstart', (e) => {
      if (e.touches.length === 1) {
        pinchStartDist = 0;
        startDrag(e.touches[0].clientX, e.touches[0].clientY);
      } else if (e.touches.length === 2) {
        isDragging = false;
        pinchStartDist = getTouchDist(e.touches[0], e.touches[1]);
        pinchStartZoom = zoom;
      }
    }, { passive: true });

    cropContainer.addEventListener('touchmove', (e) => {
      if (e.touches.length === 1 && isDragging) {
        e.preventDefault();
        moveDrag(e.touches[0].clientX, e.touches[0].clientY);
      } else if (e.touches.length === 2 && pinchStartDist > 0) {
        e.preventDefault();
        const dist = getTouchDist(e.touches[0], e.touches[1]);
        setZoom(pinchStartZoom * (dist / pinchStartDist));
      }
    }, { passive: false });

    cropContainer.addEventListener('touchend', (e) => {
      if (e.touches.length === 0) {
        stopDrag();
        pinchStartDist = 0;
      } else if (e.touches.length === 1) {
        pinchStartDist = 0;
        startDrag(e.touches[0].clientX, e.touches[0].clientY);
      }
    });

    zoomSlider.addEventListener('input', (e) => {
      setZoom(parseFloat(e.target.value) || 1);
    });

    if (btnZoomMinus) {
      btnZoomMinus.addEventListener('click', () => setZoom(zoom - 0.15));
    }
    if (btnZoomPlus) {
      btnZoomPlus.addEventListener('click', () => setZoom(zoom + 0.15));
    }
    if (btnRotLeft) {
      btnRotLeft.addEventListener('click', () => {
        rotation = (rotation - 90) % 360;
        updateTransform();
      });
    }
    if (btnRotRight) {
      btnRotRight.addEventListener('click', () => {
        rotation = (rotation + 90) % 360;
        updateTransform();
      });
    }

    const onWheel = (e) => {
      e.preventDefault();
      const step = e.deltaY < 0 ? 0.08 : -0.08;
      setZoom(zoom + step);
    };
    cropContainer.addEventListener('wheel', onWheel, { passive: false });

    const closeModal = () => {
      if (isInline) return;
      modalOverlay.classList.remove('active');
      window.removeEventListener('mousemove', onMouseMove);
      window.removeEventListener('mouseup', onMouseUp);
      window.removeEventListener('keydown', onKeyDown);
      setTimeout(() => {
        modalOverlay.remove();
      }, 250);
    };

    const onKeyDown = (e) => {
      if (isInline) return;
      if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) return;
      if (e.key === '+' || e.key === '=' || e.code === 'NumpadAdd') {
        e.preventDefault();
        setZoom(zoom + 0.1);
      } else if (e.key === '-' || e.code === 'NumpadSubtract') {
        e.preventDefault();
        setZoom(zoom - 0.1);
      } else if (e.key === 'Escape') {
        e.preventDefault();
        closeModal();
        if (!input._demoMode) {
          input.value = '';
          renderPreviews(input, previewArea);
        }
      }
    };
    if (!isInline) window.addEventListener('keydown', onKeyDown);

    btnCancel.addEventListener('click', () => {
      if (isInline) {
        rotation = 0;
        setZoom(1);
        centerCropImage();
        return;
      }
      closeModal();
      if (!input._demoMode) {
        input.value = '';
        renderPreviews(input, previewArea);
      }
    });

    btnSave.addEventListener('click', () => {
      if (isInline) {
        centerCropImage();
        return;
      }
      if (input._demoMode) {
        closeModal();
        return;
      }

      const boxRect = outlineBox.getBoundingClientRect();
      const canvas = document.createElement('canvas');
      canvas.width = targetW;
      canvas.height = targetH;
      const ctx = canvas.getContext('2d');

      if (isCircle) {
        ctx.beginPath();
        ctx.arc(targetW / 2, targetH / 2, Math.min(targetW, targetH) / 2, 0, Math.PI * 2);
        ctx.closePath();
        ctx.clip();
      }

      // Renderizado fiel considerando rotación, traslación y escala
      const containerRect = cropContainer.getBoundingClientRect();
      const scaleFactorX = targetW / boxRect.width;
      const scaleFactorY = targetH / boxRect.height;
      const boxOffsetX = boxRect.left - containerRect.left;
      const boxOffsetY = boxRect.top - containerRect.top;

      const baseW = imgW * minScale;
      const baseH = imgH * minScale;
      const centerX = dragX + baseW / 2;
      const centerY = dragY + baseH / 2;

      ctx.save();
      ctx.scale(scaleFactorX, scaleFactorY);
      ctx.translate(centerX - boxOffsetX, centerY - boxOffsetY);
      ctx.rotate((rotation * Math.PI) / 180);
      ctx.scale(zoom, zoom);
      ctx.drawImage(img, -baseW / 2, -baseH / 2, baseW, baseH);
      ctx.restore();

      const outMime = isCircle ? 'image/png' : (originalFile?.type || 'image/jpeg');
      const outExt = isCircle ? '.png' : '';
      const outName = isCircle && originalFile?.name
        ? originalFile.name.replace(/\.[^.]+$/, '') + outExt
        : (originalFile?.name || 'cropped-image.jpg');

      canvas.toBlob((blob) => {
        if (!blob) return;

        const croppedFile = new File([blob], outName, {
          type: outMime,
          lastModified: Date.now()
        });

        input._isUpdatingCrop = true;
        input.dataset.isCropped = 'true';

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(croppedFile);
        input.files = dataTransfer.files;
        input._isUpdatingCrop = false;

        input.dispatchEvent(new Event('change', { bubbles: true }));
        closeModal();
      }, outMime, 0.94);
    });
  }

  function initCropElements() {
    const cropInputs = document.querySelectorAll('.selectAndCropImage:not([data-crop-initialized])');
    if (cropInputs.length === 0) return;

    cropInputs.forEach((input) => {
      if (input.dataset.cropInitialized) return;
      input.dataset.cropInitialized = 'true';
      input.style.display = 'none';

      const containCropImg = document.createElement('div');
      containCropImg.className = 'flex wrap';

      const inputClasses = input.className
        .split(/\s+/)
        .filter((c) => c !== 'selectAndCropImage' && c !== 'hidden' && c !== 'ui-input-component' && c.trim() !== '');
      const uiTrigger = (input.getAttribute('ui-trigger') || '').split(/\s+/).filter(Boolean);

      const maskCropImg = document.createElement('button');
      maskCropImg.type = 'button';
      maskCropImg.className = ['js-crop-img-mask', 'cursor-pointer', ...inputClasses, ...uiTrigger].join(' ');
      if (ui) ui.copyUiVars(input, maskCropImg);

      const defaultSelectImg = tr('select_image', {}, 'Seleccionar imagen');
      const placeholderText = input.getAttribute('placeholder') || defaultSelectImg;
      maskCropImg.textContent = placeholderText;

      const previewArea = document.createElement('div');
      previewArea.className = 'js-crop-img-preview-area flex wrap w100 mt0';

      input.insertAdjacentElement('beforebegin', containCropImg);
      containCropImg.appendChild(maskCropImg);
      containCropImg.appendChild(previewArea);

      maskCropImg.addEventListener('click', () => {
        if (input.dataset.sandboxDemo === 'true') {
          input.dispatchEvent(new CustomEvent('fme:openCropDemo', { bubbles: true }));
          return;
        }
        input.click();
      });

      input.addEventListener('fme:openCropDemo', (ev) => {
        const demoUrl = ev.detail?.imgUrl;
        if (!demoUrl) return;
        input._demoMode = true;
        openCropModal(input, previewArea, demoUrl, { name: 'demo.png', type: 'image/png' });
      });

      input.addEventListener('fme:renderInlinePreview', (ev) => {
        const host = ev.detail?.host;
        const demoUrl = ev.detail?.imgUrl;
        if (!host || !demoUrl) return;
        openCropModal(input, previewArea, demoUrl, { name: 'demo.png', type: 'image/png' }, { inlineHost: host });
      });

      renderPreviews(input, previewArea);

      input.addEventListener('change', () => {
        if (input._isUpdatingCrop) return;
        if (input.dataset.isCropped === 'true') {
          renderPreviews(input, previewArea);
          setTimeout(() => {
            input.dataset.isCropped = 'false';
          }, 0);
          return;
        }

        const file = input.files && input.files[0];
        if (!file || !file.type.startsWith('image/')) {
          renderPreviews(input, previewArea);
          return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
          input._demoMode = false;
          openCropModal(input, previewArea, event.target.result, file);
        };
        reader.readAsDataURL(file);
      });
    });
  }

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initCropElements);
  } else {
    initCropElements();
    document.addEventListener('previewUpdated', initCropElements);
  }
}
