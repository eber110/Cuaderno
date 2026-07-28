/**
 * Componente para seleccionar y recortar una imagen antes de subirla.
 * Ideal para avatares, banners y fotos de perfil.
 * 
 * @function cropImage
 * @description Convierte inputs de tipo file con la clase `.selectAndCropImage` en un cargador
 *              interactivo con visor modal de recorte (drag to move, zoom slider) antes del envío.
 * 
 * @example
 * <!-- HTML DECLARATION -->
 * <input type="file" 
 *        name="avatar" 
 *        class="selectAndCropImage btn-style-classes"
 *        placeholder="Cambiar foto de perfil" 
 *        cropping-size="500x500"
 *        box-image="back-color2 br15 border8 p20 shadow-3"
 *        box-btn-image="back-success br5 text-center px10 py5 pointer">
 * 
 * @param {string} class="selectAndCropImage" - Clase obligatoria para habilitar el componente.
 * @param {string} [placeholder="Seleccionar imagen"] - El texto visible que tendrá el botón de máscara.
 * @param {string} [cropping-size="400x400"] - Dimensiones finales de la imagen recortada en formato "ANCHOxALTO".
 * @param {string} [box-image] - Clases utilitarias de CSS aplicadas al contenedor del modal flotante (ej. bordes y fondos).
 * @param {string} [box-btn-image] - Clases utilitarias de CSS aplicadas a los botones del modal (ej. clases de fondo 'back-*' y padding).
 * 
 * @returns {void}
 */
export function cropImage() {
  const cropInputs = document.querySelectorAll(".selectAndCropImage");
  if (cropInputs.length === 0) return;

  // Inyectar estilos CSS para el modal si no están ya presentes
  if (!document.getElementById('crop-image-styles')) {
    const style = document.createElement('style');
    style.id = 'crop-image-styles';
    style.textContent = `
      .crop-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(4px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 99999;
        opacity: 0;
        transition: opacity 0.3s ease;
      }
      .crop-modal-overlay.active {
        opacity: 1;
      }
      .crop-modal-card {
        width: 90%;
        max-width: 380px;
        display: flex;
        flex-direction: column;
        align-items: center;
        transform: scale(0.9);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        font-family: system-ui, -apple-system, sans-serif;
      }
      .crop-modal-overlay.active .crop-modal-card {
        transform: scale(1);
      }
      
      /* Estilos base con especificidad 0 para facilitar la sobreescritura */
      :where(.crop-modal-card) {
        background: #ffffff;
        border: 1px solid #dcdcdc;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        color: #333333;
      }
      :where(.dark-mode) :where(.crop-modal-card) {
        background: #1e1e1e;
        border-color: #333333;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        color: #ffffff;
      }

      /* Compatibilidad con clases asignadas programáticamente */
      :where(.crop-modal-card-default-style) {
        background: #ffffff;
        border: 1px solid #dcdcdc;
        border-radius: 15px;
      }
      :where(.dark-mode) :where(.crop-modal-card-default-style) {
        background: #1e1e1e;
        border-color: #333333;
      }
      :where(.crop-modal-card-default-br) {
        border: 1px solid #dcdcdc;
        border-radius: 15px;
      }
      :where(.dark-mode) :where(.crop-modal-card-default-br) {
        border-color: #333333;
      }
      :where(.crop-modal-card-default-back) {
        background: #ffffff;
        border: 1px solid #dcdcdc;
      }
      :where(.dark-mode) :where(.crop-modal-card-default-back) {
        background: #1e1e1e;
        border-color: #333333;
      }

      .crop-modal-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        width: 100%;
        text-align: center;
      }
      .crop-container {
        width: 320px;
        height: 320px;
        position: relative;
        overflow: hidden;
        border-radius: 12px;
        background: #121212;
        display: flex;
        justify-content: center;
        align-items: center;
      }
      .crop-image {
        position: absolute;
        cursor: move;
        user-select: none;
        -webkit-user-drag: none;
        transform-origin: center center;
      }
      .crop-controls {
        width: 100%;
        margin-top: 15px;
        display: flex;
        flex-direction: column;
        gap: 10px;
      }
      .crop-zoom-container {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
      }
      .crop-zoom-slider {
        flex-grow: 1;
        height: 8px;
        border-radius: 4px;
        outline: none;
        -webkit-appearance: none;
      }
      :where(.crop-zoom-slider) {
        background: #e2e8f0;
      }
      :where(.dark-mode) :where(.crop-zoom-slider) {
        background: rgba(255, 255, 255, 0.2);
      }
      .crop-zoom-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ffffff;
        border: 2px solid #555;
        cursor: pointer;
        transition: transform 0.1s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
      }
      .crop-zoom-slider::-webkit-slider-thumb:hover {
        transform: scale(1.1);
      }
      .crop-buttons {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        width: 100%;
        margin-top: 15px;
      }
      .crop-btn {
        flex: 1;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
      }
      :where(.crop-btn) {
        padding: 10px 16px;
        border-radius: 12px;
      }
      .crop-btn:not([class*="border"]) {
        border: none;
      }
      
      /* Estilos para botón Cancelar con especificidad 0 */
      :where(.crop-btn-cancel-bg) {
        background: #f1f5f9;
        color: #475569;
      }
      :where(.crop-btn-cancel-bg):hover {
        background: #e2e8f0;
      }
      :where(.dark-mode) :where(.crop-btn-cancel-bg) {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
      }
      :where(.dark-mode) :where(.crop-btn-cancel-bg):hover {
        background: rgba(255, 255, 255, 0.15);
      }

      /* Estilos para botón Guardar con especificidad 0 */
      :where(.crop-btn-save-default-bg) {
        background: #3b82f6;
        color: #ffffff;
      }
      :where(.crop-btn-save-default-bg):hover {
        background: #2563eb;
      }
    `;
    document.head.appendChild(style);
  }

  // Auxiliar para renderizar la vista previa de la única imagen
  function renderPreviews(input, previewArea) {
    previewArea.innerHTML = "";

    // Revocar URLs de objeto antiguos para evitar fugas de memoria
    if (previewArea._objectUrl) {
      URL.revokeObjectURL(previewArea._objectUrl);
      previewArea._objectUrl = null;
    }

    if (input.classList.contains('no-preview')) {
      previewArea.style.display = 'none';
      return;
    }

    if (input.files && input.files.length > 0) {
      previewArea.classList.remove("mt0");
      previewArea.classList.add("mt10");

      const file = input.files[0];
      const imgUrl = URL.createObjectURL(file);
      previewArea._objectUrl = imgUrl;

      // Crear contenedor de vista previa
      const item = document.createElement("div");
      item.className = "crop-img-preview-item relative m5 br10 overflow-hidden wpx80 hpx80";

      // Imagen
      const img = document.createElement("img");
      img.src = imgUrl;
      img.className = "w100 h100 cover";
      img.style.pointerEvents = "none";
      item.appendChild(img);

      // Botón de eliminar (redondo, fondo #242424, X svg blanca)
      const deleteBtn = document.createElement("div");
      deleteBtn.className = "absolute flex-row center-center cursor-pointer";
      deleteBtn.style.top = "4px";
      deleteBtn.style.right = "4px";
      deleteBtn.style.width = "18px";
      deleteBtn.style.height = "18px";
      deleteBtn.style.borderRadius = "50%";
      deleteBtn.style.backgroundColor = "#242424";
      deleteBtn.style.zIndex = "10";
      deleteBtn.innerHTML = `
        <svg viewBox="125 125 390 390" width="10" height="10" fill="#ffffff" style="pointer-events: none; display: block;">
          <path d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z"/>
        </svg>
      `;

      deleteBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        input.value = ""; // Limpiar el archivo del input
        renderPreviews(input, previewArea);

        // Disparar evento de cambio
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });

      item.appendChild(deleteBtn);
      previewArea.appendChild(item);
    } else {
      previewArea.classList.remove("mt10");
      previewArea.classList.add("mt0");
    }
  }

  cropInputs.forEach(input => {
    if (input.dataset.cropInitialized) return;
    input.dataset.cropInitialized = 'true';

    // Ocultar input nativo
    input.style.display = 'none';

    // Contenedor principal para la máscara del botón y vista previa
    const containCropImg = document.createElement("div");
    containCropImg.className = "flex wrap";

    // Clonar clases del input al botón de máscara (excluyendo la clase identificadora)
    const inputClasses = input.className.split(' ').filter(c => c !== 'selectAndCropImage' && c !== 'hidden' && c.trim() !== '');
    const maskCropImg = document.createElement("div");
    maskCropImg.className = ["js-crop-img-mask", "cursor-pointer", ...inputClasses].join(' ');

    // Placeholder como texto
    const placeholderText = input.getAttribute('placeholder') || "Seleccionar imagen";
    maskCropImg.textContent = placeholderText;

    // Área para la vista previa
    const previewArea = document.createElement("div");
    previewArea.className = "js-crop-img-preview-area flex wrap w100 mt0";

    // Insertar elementos en el DOM
    input.insertAdjacentElement("beforebegin", containCropImg);
    containCropImg.appendChild(maskCropImg);
    containCropImg.appendChild(previewArea);

    // Evento de clic en el botón de máscara
    maskCropImg.addEventListener('click', () => {
      input.click();
    });

    // Renderizar vistas previas iniciales si ya hay archivos
    renderPreviews(input, previewArea);

    input.addEventListener('change', (e) => {
      // Ignorar si el evento fue disparado programáticamente durante la asignación del recorte
      if (input._isUpdatingCrop) return;

      // Si el archivo ya ha sido recortado previamente, actualizar vista previa y retornar
      if (input.dataset.isCropped === 'true') {
        renderPreviews(input, previewArea);
        // Deferir el reset de isCropped a false para permitir que el evento de cambio actual se propague y sea capturado por auto-submit
        setTimeout(() => {
          input.dataset.isCropped = 'false';
        }, 0);
        return;
      }

      const file = input.files[0];
      if (!file || !file.type.startsWith('image/')) {
        renderPreviews(input, previewArea);
        return;
      }

      const croppingSize = input.getAttribute('cropping-size') || '400x400';
      const [targetW, targetH] = croppingSize.split('x').map(num => parseInt(num) || 400);
      const aspectRatio = targetW / targetH;

      // Dimensiones máximas del visor de recorte en UI
      const containerW = 320;
      const containerH = 320;
      let boxW = 280;
      let boxH = 280;

      // Ajustar visor de recorte al aspect ratio
      if (aspectRatio >= 1) {
        boxH = 280 / aspectRatio;
      } else {
        boxW = 280 * aspectRatio;
      }

      const boxLeft = (containerW - boxW) / 2;
      const boxTop = (containerH - boxH) / 2;

      // Cargar la imagen seleccionada para recortar
      const reader = new FileReader();
      reader.onload = (event) => {
        const imgUrl = event.target.result;

        const boxImage = input.getAttribute('box-image') || '';
        const boxBtnImage = input.getAttribute('box-btn-image') || '';

        // Procesar clases de la tarjeta modal
        const cardClassesArray = boxImage.split(' ').filter(c => c.trim() !== '');
        const hasBackClass = cardClassesArray.some(c => c.startsWith('back'));
        const hasBrClass = cardClassesArray.some(c => c.startsWith('br'));
        let cardDefaultClasses = 'crop-modal-card-default-style';
        if (hasBackClass && hasBrClass) {
          cardDefaultClasses = '';
        } else if (hasBackClass) {
          cardDefaultClasses = 'crop-modal-card-default-br';
        } else if (hasBrClass) {
          cardDefaultClasses = 'crop-modal-card-default-back';
        }
        const cardClassList = `crop-modal-card ${cardDefaultClasses} ${boxImage}`.trim().replace(/\s+/g, ' ');

        // Procesar clases de los botones (Cancelar / Guardar)
        const btnClassesArray = boxBtnImage.split(' ').filter(c => c.trim() !== '');
        const btnBackClasses = btnClassesArray.filter(c => c.startsWith('back'));
        const btnOtherClasses = btnClassesArray.filter(c => !c.startsWith('back'));

        const saveBtnBgClass = btnBackClasses.length > 0 ? btnBackClasses.join(' ') : 'crop-btn-save-default-bg';
        const saveBtnClassList = `crop-btn crop-btn-save ${saveBtnBgClass} ${btnOtherClasses.join(' ')}`.trim().replace(/\s+/g, ' ');

        const cancelBtnClassList = `crop-btn crop-btn-cancel crop-btn-cancel-bg ${btnOtherClasses.join(' ')}`.trim().replace(/\s+/g, ' ');

        // Crear modal de recorte
        const modalOverlay = document.createElement('div');
        modalOverlay.className = 'crop-modal-overlay';
        const maskId = `crop-mask-hole-${Date.now()}`;

        modalOverlay.innerHTML = `
          <div class="${cardClassList}">
            <div class="crop-modal-title">Recortar Imagen</div>
            <div class="crop-container">
              <img src="${imgUrl}" class="crop-image" alt="A recortar">
              <svg class="crop-mask" width="${containerW}" height="${containerH}" style="position: absolute; top:0; left:0; z-index:2; pointer-events:none;">
                <defs>
                  <mask id="${maskId}">
                    <rect width="${containerW}" height="${containerH}" fill="white" />
                    <rect x="${boxLeft}" y="${boxTop}" width="${boxW}" height="${boxH}" fill="black" />
                  </mask>
                </defs>
                <rect width="${containerW}" height="${containerH}" fill="rgba(0, 0, 0, 0.6)" mask="url(#${maskId})" />
              </svg>
              <div class="crop-box-outline" style="position: absolute; left: ${boxLeft}px; top: ${boxTop}px; width: ${boxW}px; height: ${boxH}px; border: 2px dashed #ffffff; box-sizing: border-box; z-index: 3; pointer-events: none;"></div>
            </div>
            <div class="crop-controls">
              <div class="crop-zoom-container">
                <span style="font-size: 14px; opacity: 0.7;">🔍-</span>
                <input type="range" class="crop-zoom-slider" min="1" max="3" step="0.01" value="1">
                <span style="font-size: 14px; opacity: 0.7;">🔍+</span>
              </div>
            </div>
            <div class="crop-buttons">
              <button type="button" class="${cancelBtnClassList}">Cancelar</button>
              <button type="button" class="${saveBtnClassList}">Recortar y Guardar</button>
            </div>
          </div>
        `;

        document.body.appendChild(modalOverlay);

        setTimeout(() => modalOverlay.classList.add('active'), 50);

        const img = modalOverlay.querySelector('.crop-image');
        const zoomSlider = modalOverlay.querySelector('.crop-zoom-slider');
        const btnCancel = modalOverlay.querySelector('.crop-btn-cancel');
        const btnSave = modalOverlay.querySelector('.crop-btn-save');
        const outlineBox = modalOverlay.querySelector('.crop-box-outline');
        const cropContainer = modalOverlay.querySelector('.crop-container');

        let zoom = 1;
        let dragX = 0;
        let dragY = 0;
        let isDragging = false;
        let startX = 0;
        let startY = 0;
        let minScale = 1;

        let imgW = 0;
        let imgH = 0;

        img.onload = () => {
          imgW = img.naturalWidth;
          imgH = img.naturalHeight;

          const scaleX = boxW / imgW;
          const scaleY = boxH / imgH;
          minScale = Math.max(scaleX, scaleY);

          img.style.width = (imgW * minScale) + "px";
          img.style.height = (imgH * minScale) + "px";

          dragX = (containerW - imgW * minScale) / 2;
          dragY = (containerH - imgH * minScale) / 2;

          updateTransform();
        };

        function updateTransform() {
          img.style.transform = `translate(${dragX}px, ${dragY}px) scale(${zoom})`;
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

        img.addEventListener('mousedown', (e) => {
          e.preventDefault();
          startDrag(e.clientX, e.clientY);
        });

        const onMouseMove = (e) => moveDrag(e.clientX, e.clientY);
        const onMouseUp = () => stopDrag();
        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('mouseup', onMouseUp);

        img.addEventListener('touchstart', (e) => {
          if (e.touches.length === 1) {
            startDrag(e.touches[0].clientX, e.touches[0].clientY);
          }
        });
        img.addEventListener('touchmove', (e) => {
          if (e.touches.length === 1) {
            e.preventDefault();
            moveDrag(e.touches[0].clientX, e.touches[0].clientY);
          }
        });
        img.addEventListener('touchend', stopDrag);

        zoomSlider.addEventListener('input', (e) => {
          zoom = parseFloat(e.target.value);
          updateTransform();
        });

        // Zoom con rueda de ratón en el visor
        const onWheel = (e) => {
          e.preventDefault();
          const zoomStep = 0.05;
          let newZoom = zoom;
          if (e.deltaY < 0) {
            newZoom = Math.min(3, zoom + zoomStep);
          } else {
            newZoom = Math.max(1, zoom - zoomStep);
          }
          zoom = newZoom;
          zoomSlider.value = zoom;
          updateTransform();
        };
        cropContainer.addEventListener('wheel', onWheel, { passive: false });

        // Zoom con teclado (+ / -) y escape para salir
        const onKeyDown = (e) => {
          if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

          const zoomStep = 0.05;
          let newZoom = zoom;

          if (e.key === '+' || e.key === '=' || e.code === 'NumpadAdd' || e.code === 'Equal') {
            newZoom = Math.min(3, zoom + zoomStep);
            e.preventDefault();
          } else if (e.key === '-' || e.code === 'NumpadSubtract' || e.code === 'Minus') {
            newZoom = Math.max(1, zoom - zoomStep);
            e.preventDefault();
          } else if (e.key === 'Escape') {
            closeModal();
            input.value = "";
            renderPreviews(input, previewArea);
            e.preventDefault();
          }

          if (newZoom !== zoom) {
            zoom = newZoom;
            zoomSlider.value = zoom;
            updateTransform();
          }
        };
        window.addEventListener('keydown', onKeyDown);

        const closeModal = () => {
          modalOverlay.classList.remove('active');
          window.removeEventListener('mousemove', onMouseMove);
          window.removeEventListener('mouseup', onMouseUp);
          window.removeEventListener('keydown', onKeyDown);
          setTimeout(() => {
            modalOverlay.remove();
          }, 300);
        };

        btnCancel.addEventListener('click', () => {
          closeModal();
          input.value = "";
          renderPreviews(input, previewArea);
        });

        btnSave.addEventListener('click', () => {
          const boxRect = outlineBox.getBoundingClientRect();
          const imgRect = img.getBoundingClientRect();

          const leftInImg = boxRect.left - imgRect.left;
          const topInImg = boxRect.top - imgRect.top;

          const scale = imgW / imgRect.width;

          const cropX = leftInImg * scale;
          const cropY = topInImg * scale;
          const cropW = boxRect.width * scale;
          const cropH = boxRect.height * scale;

          const canvas = document.createElement('canvas');
          canvas.width = targetW;
          canvas.height = targetH;
          const ctx = canvas.getContext('2d');

          ctx.drawImage(img, cropX, cropY, cropW, cropH, 0, 0, targetW, targetH);

          canvas.toBlob((blob) => {
            if (!blob) return;

            const croppedFile = new File([blob], file.name, {
              type: file.type || 'image/jpeg',
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
          }, file.type || 'image/jpeg', 0.95);
        });
      };
      reader.readAsDataURL(file);
    });
  });
}
