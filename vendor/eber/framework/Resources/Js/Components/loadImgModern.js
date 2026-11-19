/**
 * Input múltiple de imágenes con vistas previas, zona Dropzone opcional y reordenamiento táctil/mouse.
 * Reemplaza el Drag & Drop HTML5 nativo (no funcional en móviles) por Pointer Events unificados.
 *
 * @function loadImgModern
 * @description Transforma inputs de archivo `.loadImgModern` en botones o zonas Dropzone
 *              con reordenamiento fluido en desktop y pantallas touch.
 *
 * @returns {void}
 */
export function loadImgModern() {
  const ui = window.FmeUi || null;
  const tr = (k, p, f) => (ui ? ui.t(k, p, f) : (typeof window.t === 'function' ? window.t(k, p, f) : f));
  const icon = (name, fb) => (ui && ui.ICONS && ui.ICONS[name]) ? ui.ICONS[name] : fb;

  function updateInputFiles(input) {
    input._isUpdating = true;
    const dataTransfer = new DataTransfer();
    if (input._selectedFiles) {
      input._selectedFiles.forEach((file) => dataTransfer.items.add(file));
    }
    input.files = dataTransfer.files;
    input._isUpdating = false;
  }

  function addFilesToInput(input, fileList, container, previewArea) {
    const incoming = Array.from(fileList || []);
    if (!incoming.length) return;

    const maxFiles = parseInt(input.getAttribute('data-max-files') || '0', 10) || 0;
    const maxSizeMb = parseFloat(input.getAttribute('data-max-size') || '0') || 0;
    const maxBytes = maxSizeMb > 0 ? maxSizeMb * 1024 * 1024 : 0;

    for (const file of incoming) {
      if (maxFiles > 0 && input._selectedFiles.length >= maxFiles) {
        if (typeof window.toast === 'function') {
          window.toast(tr('max_files_error', { max: maxFiles }, `Máximo ${maxFiles} archivo(s) permitido(s).`), 'warning');
        }
        break;
      }
      if (maxBytes > 0 && file.size > maxBytes) {
        if (typeof window.toast === 'function') {
          window.toast(tr('max_size_error', { max: maxSizeMb }, `El archivo supera el límite de ${maxSizeMb} MB.`), 'warning');
        }
        continue;
      }
      const isDuplicate = input._selectedFiles.some(
        (f) => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified
      );
      if (!isDuplicate) {
        input._selectedFiles.push(file);
      }
    }

    updateInputFiles(input);
    renderPreviews(input, container, previewArea);
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function bindPointerSortable(item, previewArea, input, container) {
    let pointerId = null;
    let startX = 0;
    let startY = 0;
    let isDragging = false;
    let cloneEl = null;
    let offsetX = 0;
    let offsetY = 0;

    const cleanup = () => {
      window.removeEventListener('pointermove', onPointerMove);
      window.removeEventListener('pointerup', onPointerUp);
      window.removeEventListener('pointercancel', onPointerUp);
      if (cloneEl && cloneEl.parentNode) {
        cloneEl.parentNode.removeChild(cloneEl);
      }
      cloneEl = null;
      item.classList.remove('is-dragging-source');
      isDragging = false;
      pointerId = null;
    };

    const onPointerMove = (e) => {
      if (pointerId !== e.pointerId) return;
      const dx = e.clientX - startX;
      const dy = e.clientY - startY;

      if (!isDragging) {
        if (Math.hypot(dx, dy) < 8) return;
        isDragging = true;
        const rect = item.getBoundingClientRect();
        offsetX = startX - rect.left;
        offsetY = startY - rect.top;

        cloneEl = item.cloneNode(true);
        cloneEl.className = 'ui-sortable-floating';
        cloneEl.style.width = `${rect.width}px`;
        cloneEl.style.height = `${rect.height}px`;
        cloneEl.style.left = `${rect.left}px`;
        cloneEl.style.top = `${rect.top}px`;
        document.body.appendChild(cloneEl);
        item.classList.add('is-dragging-source');
      }

      e.preventDefault();
      if (cloneEl) {
        cloneEl.style.left = `${e.clientX - offsetX}px`;
        cloneEl.style.top = `${e.clientY - offsetY}px`;
      }

      const siblings = Array.from(previewArea.querySelectorAll('.load-img-preview-item'));
      const target = siblings.find((sib) => {
        if (sib === item) return false;
        const r = sib.getBoundingClientRect();
        return e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
      });

      if (target) {
        const fromIdx = siblings.indexOf(item);
        const toIdx = siblings.indexOf(target);
        if (fromIdx !== -1 && toIdx !== -1 && fromIdx !== toIdx) {
          if (fromIdx < toIdx) {
            target.after(item);
          } else {
            target.before(item);
          }
          const [moved] = input._selectedFiles.splice(fromIdx, 1);
          input._selectedFiles.splice(toIdx, 0, moved);
        }
      }
    };

    const onPointerUp = (e) => {
      if (pointerId !== e.pointerId) return;
      const wasDragging = isDragging;
      cleanup();
      if (wasDragging) {
        updateInputFiles(input);
        renderPreviews(input, container, previewArea);
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    };

    item.addEventListener('pointerdown', (e) => {
      if (e.target.closest('.ui-img-delete-btn')) return;
      if (e.pointerType === 'mouse' && e.button !== 0) return;
      pointerId = e.pointerId;
      startX = e.clientX;
      startY = e.clientY;
      isDragging = false;
      window.addEventListener('pointermove', onPointerMove, { passive: false });
      window.addEventListener('pointerup', onPointerUp);
      window.addEventListener('pointercancel', onPointerUp);
    });
  }

  function renderPreviews(input, container, previewArea) {
    previewArea.innerHTML = '';

    if (previewArea._objectUrls) {
      previewArea._objectUrls.forEach((url) => URL.revokeObjectURL(url));
    }
    previewArea._objectUrls = [];

    const files = input._selectedFiles || [];
    const maskButton = container.querySelector('.js-load-img-mask');
    if (maskButton) {
      maskButton.classList.toggle('is-placeholder', files.length === 0);
    }

    if (files.length === 0) {
      previewArea.style.display = 'none';
    } else {
      previewArea.style.display = '';
    }

    const oldMsg = container.querySelector('.cantImgLoad');
    if (oldMsg) oldMsg.remove();

    const cantImg = files.length;
    if (cantImg > 0 && input.hasAttribute('data-note')) {
      const dataNoteVal = input.getAttribute('data-note') || '';
      const newMsg = document.createElement('div');
      newMsg.className = `cantImgLoad flex-row center-center w-auto ${dataNoteVal}`.trim();
      const defaultFileMsg = `${cantImg} archivo${cantImg !== 1 ? 's' : ''}`;
      newMsg.textContent = tr(cantImg === 1 ? 'files_one' : 'files_many', { count: cantImg }, defaultFileMsg);

      if (maskButton) {
        maskButton.insertAdjacentElement('afterend', newMsg);
      }
    }

    files.forEach((file, index) => {
      const imgUrl = URL.createObjectURL(file);
      previewArea._objectUrls.push(imgUrl);

      const item = document.createElement('div');
      item.className = 'load-img-preview-item relative overflow-hidden';
      item.style.cursor = 'grab';
      item.dataset.index = String(index);

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
        input._selectedFiles.splice(index, 1);
        updateInputFiles(input);
        renderPreviews(input, container, previewArea);
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });

      item.appendChild(deleteBtn);
      bindPointerSortable(item, previewArea, input, container);
      previewArea.appendChild(item);
    });

    if (ui && typeof ui.attachFloatingScroll === 'function') {
      ui.attachFloatingScroll(previewArea, input);
    }
  }

  function initLoadImgInputs() {
    const loadImgModerns = document.querySelectorAll('.loadImgModern:not([data-initialized])');
    if (loadImgModerns.length === 0) return;

    loadImgModerns.forEach((inputEl) => {
      if (inputEl.dataset.initialized) return;
      inputEl.dataset.initialized = 'true';
      inputEl.style.display = 'none';

      const containLoadImg = document.createElement('div');
      containLoadImg.className = 'ui-loadimg-wrap flex wrap';
      if (ui) ui.copyUiVars(inputEl, containLoadImg);

      const inputClasses = inputEl.className
        .split(/\s+/)
        .filter((c) => c !== 'loadImgModern' && c !== 'hidden' && c !== 'ui-input-component' && c.trim() !== '');
      const uiTrigger = (inputEl.getAttribute('ui-trigger') || '').split(/\s+/).filter(Boolean);
      const isDropzone = inputEl.getAttribute('data-dropzone') === 'true';

      const maskEl = document.createElement(isDropzone ? 'div' : 'button');
      if (!isDropzone) maskEl.type = 'button';
      maskEl.className = [
        'js-load-img-mask',
        'is-placeholder',
        isDropzone ? 'ui-dropzone-area' : 'cursor-pointer',
        ...inputClasses,
        ...uiTrigger
      ].join(' ');
      if (ui) ui.copyUiVars(inputEl, maskEl);

      const defaultAddImg = tr('add_image', {}, 'Agregar una imagen');
      const placeholderText = inputEl.getAttribute('placeholder') || defaultAddImg;

      if (isDropzone) {
        maskEl.innerHTML = `${icon('upload', '⬆')}<span>${placeholderText}</span>`;
      } else {
        maskEl.textContent = placeholderText;
      }

      const previewArea = document.createElement('div');
      previewArea.className = 'js-load-img-preview-area flex wrap w100';
      if (ui) ui.copyUiVars(inputEl, previewArea);

      inputEl.insertAdjacentElement('beforebegin', containLoadImg);
      containLoadImg.appendChild(maskEl);
      containLoadImg.appendChild(previewArea);

      maskEl.addEventListener('click', () => {
        inputEl.click();
      });

      if (isDropzone) {
        maskEl.addEventListener('dragover', (e) => {
          e.preventDefault();
          maskEl.classList.add('is-dragover');
        });
        maskEl.addEventListener('dragleave', () => {
          maskEl.classList.remove('is-dragover');
        });
        maskEl.addEventListener('drop', (e) => {
          e.preventDefault();
          maskEl.classList.remove('is-dragover');
          if (e.dataTransfer && e.dataTransfer.files) {
            addFilesToInput(inputEl, e.dataTransfer.files, containLoadImg, previewArea);
          }
        });
      }

      inputEl._selectedFiles = [];
      if (inputEl.files && inputEl.files.length > 0) {
        inputEl._selectedFiles = Array.from(inputEl.files);
      }
      renderPreviews(inputEl, containLoadImg, previewArea);

      inputEl.addEventListener('change', () => {
        if (inputEl._isUpdating) return;
        addFilesToInput(inputEl, inputEl.files, containLoadImg, previewArea);
      });

      inputEl.addEventListener('fme:renderInlinePreview', (ev) => {
        const host = ev.detail?.host;
        if (!host) return;
        const demoArea = document.createElement('div');
        demoArea.className = 'js-load-img-preview-area flex wrap is-inline-preview';
        demoArea.style.justifyContent = 'center';
        if (ui) ui.copyUiVars(inputEl, demoArea);

        const colors = ['#1d4ed8', '#0284c7'];
        const labels = ['IMG 01', 'IMG 02'];
        colors.forEach((bg, idx) => {
          const item = document.createElement('div');
          item.className = 'load-img-preview-item relative overflow-hidden';
          item.style.background = `linear-gradient(135deg, #0f172a, ${bg})`;
          item.style.display = 'flex';
          item.style.alignItems = 'center';
          item.style.justifyContent = 'center';
          item.style.color = '#ffffff';
          item.style.fontSize = 'var(--ui-secondary-font-size, 12px)';
          item.style.fontWeight = '700';
          item.textContent = labels[idx];

          const delBtn = document.createElement('button');
          delBtn.type = 'button';
          delBtn.className = 'ui-img-delete-btn';
          delBtn.setAttribute('aria-label', tr('remove_item', {}, 'Eliminar'));
          delBtn.innerHTML = icon('close', '✕');
          item.appendChild(delBtn);
          demoArea.appendChild(item);
        });

        host.innerHTML = '';
        if (inputEl.hasAttribute('data-note')) {
          const noteEl = document.createElement('div');
          noteEl.className = `cantImgLoad flex-row center-center w-auto ${inputEl.getAttribute('data-note') || ''}`.trim();
          noteEl.style.marginBottom = '10px';
          noteEl.textContent = tr('files_many', { count: 2 }, '2 archivos');
          host.appendChild(noteEl);
        }
        host.appendChild(demoArea);
        if (ui && typeof ui.attachFloatingScroll === 'function') {
          ui.attachFloatingScroll(demoArea, inputEl);
        }
      });
    });
  }

  if (ui && typeof ui.observeDynamic === 'function') {
    ui.observeDynamic(initLoadImgInputs);
  } else {
    initLoadImgInputs();
    document.addEventListener('previewUpdated', initLoadImgInputs);
  }
}
