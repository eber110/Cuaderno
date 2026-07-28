/**
 * Input de archivos con UI mejorada.
 * 
 * @function loadImgModern
 * @description Transforma inputs de archivo en botones personalizables
 *              con contador de archivos seleccionados.
 * 
 * @param {string|null} [styleBtn=null] - Clases CSS para el botón
 * 
 * @example
 * // HTML
 * <input type="file" class="loadImgModern" multiple accept="image/*">
 * 
 * @returns {void}
 */
export function loadImgModern() {
  const loadImgModerns = document.querySelectorAll(".loadImgModern");
  if (loadImgModerns.length === 0) return;

  // Handler para clicks en la máscara del input
  document.addEventListener('click', (event) => {
    if (!event.target || !event.target.closest) return;
    const maskButton = event.target.closest('.js-load-img-mask');
    if (!maskButton) return;

    const containLoadImg = maskButton.parentElement;
    const loadImgModernInput = containLoadImg?.nextElementSibling;

    if (loadImgModernInput && loadImgModernInput.classList.contains('loadImgModern')) {
      loadImgModernInput.click();
    }
  });

  // Auxiliar para actualizar el FileList del input desde el array interno
  function updateInputFiles(input) {
    input._isUpdating = true;
    const dataTransfer = new DataTransfer();
    if (input._selectedFiles) {
      input._selectedFiles.forEach(file => {
        dataTransfer.items.add(file);
      });
    }
    input.files = dataTransfer.files;
    input._isUpdating = false;
  }

  // Auxiliar para renderizar las vistas previas de imágenes
  function renderPreviews(input, container, previewArea) {
    previewArea.innerHTML = "";

    // Revocar objectURLs antiguos para evitar fugas de memoria
    if (previewArea._objectUrls) {
      previewArea._objectUrls.forEach(url => URL.revokeObjectURL(url));
    }
    previewArea._objectUrls = [];

    const files = input._selectedFiles || [];

    // Ajustar margen superior dinámicamente según si hay imágenes o no
    if (files.length === 0) {
      previewArea.classList.remove("mt10");
      previewArea.classList.add("mt0");
    } else {
      previewArea.classList.remove("mt0");
      previewArea.classList.add("mt10");
    }

    // Actualizar el contador de imágenes seleccionadas
    const oldMsg = container.querySelector(".cantImgLoad");
    if (oldMsg) oldMsg.remove();

    const cantImg = files.length;
    if (cantImg > 0 && input.hasAttribute('data-note')) {
      const dataNoteVal = input.getAttribute('data-note') || '';
      const newMsg = document.createElement("div");
      newMsg.className = `cantImgLoad flex-row center-center w-auto ${dataNoteVal}`.trim();
      newMsg.innerHTML = `${cantImg} archivo${cantImg !== 1 ? 's' : ''}`;

      const maskButton = container.querySelector('.js-load-img-mask');
      if (maskButton) {
        maskButton.insertAdjacentElement("afterend", newMsg);
      }
    }

    files.forEach((file, index) => {
      // Generar URL temporal para la imagen
      const imgUrl = URL.createObjectURL(file);
      previewArea._objectUrls.push(imgUrl);

      // Crear contenedor de la vista previa
      const item = document.createElement("div");
      item.className = "load-img-preview-item relative m5 br10 overflow-hidden wpx80 hpx80";
      item.style.cursor = "grab";
      item.setAttribute("draggable", "true");
      item.dataset.index = index;

      // Imagen
      const img = document.createElement("img");
      img.src = imgUrl;
      img.className = "w100 h100 cover";
      img.style.pointerEvents = "none";
      item.appendChild(img);

      // Botón de eliminar
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
        input._selectedFiles.splice(index, 1);
        updateInputFiles(input);
        renderPreviews(input, container, previewArea);
        
        // Disparar evento change manualmente por si otros scripts lo escuchan
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });

      item.appendChild(deleteBtn);

      // --- EVENTOS DRAG & DROP ---
      item.addEventListener("dragstart", (e) => {
        item.classList.add("dragging");
        e.dataTransfer.effectAllowed = "move";
        previewArea._dragSource = item;
      });

      item.addEventListener("dragover", (e) => {
        e.preventDefault();
        return false;
      });

      item.addEventListener("dragenter", (e) => {
        if (previewArea._dragSource && previewArea._dragSource !== item) {
          item.style.transform = "scale(1.08)";
          item.style.boxShadow = "0 4px 10px rgba(0,0,0,0.3)";
        }
      });

      item.addEventListener("dragleave", (e) => {
        item.style.transform = "none";
        item.style.boxShadow = "none";
      });

      item.addEventListener("drop", (e) => {
        e.preventDefault();
        e.stopPropagation();

        item.style.transform = "none";
        item.style.boxShadow = "none";

        const dragSource = previewArea._dragSource;
        if (dragSource && dragSource !== item) {
          const fromIndex = parseInt(dragSource.dataset.index);
          const toIndex = parseInt(item.dataset.index);

          // Reordenar en el array
          const [movedFile] = input._selectedFiles.splice(fromIndex, 1);
          input._selectedFiles.splice(toIndex, 0, movedFile);

          // Actualizar input.files y re-renderizar
          updateInputFiles(input);
          renderPreviews(input, container, previewArea);

          // Disparar evento change manualmente
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        return false;
      });

      item.addEventListener("dragend", () => {
        item.classList.remove("dragging");
        previewArea._dragSource = null;
      });

      previewArea.appendChild(item);
    });
  }

  loadImgModerns.forEach(loadImgModern => {
    if (loadImgModern.dataset.initialized) return;
    loadImgModern.dataset.initialized = 'true';

    // Ocultar el input original
    loadImgModern.style.display = 'none';

    // Contenedor principal de la UI personalizada
    const containLoadImg = document.createElement("div");
    containLoadImg.className = "flex wrap";

    // Copiar clases del input original al botón máscara (excluyendo la clase identificadora loadImgModern)
    const inputClasses = loadImgModern.className.split(' ').filter(c => c !== 'loadImgModern' && c !== 'hidden' && c.trim() !== '');
    const maskLoadImgModern = document.createElement("div");
    maskLoadImgModern.className = ["js-load-img-mask", "cursor-pointer", ...inputClasses].join(' ');

    // Usar placeholder como texto, o el valor por defecto si no existe
    const placeholderText = loadImgModern.getAttribute('placeholder') || "Agregar una imagen";
    maskLoadImgModern.textContent = placeholderText;

    const previewArea = document.createElement("div");
    previewArea.className = "js-load-img-preview-area flex wrap w100 mt0";

    loadImgModern.insertAdjacentElement("beforebegin", containLoadImg);
    containLoadImg.appendChild(maskLoadImgModern);
    containLoadImg.appendChild(previewArea);

    // Inicializar el array de archivos seleccionados
    loadImgModern._selectedFiles = [];

    // Si ya tiene archivos cargados previamente por el navegador
    if (loadImgModern.files && loadImgModern.files.length > 0) {
      loadImgModern._selectedFiles = Array.from(loadImgModern.files);
    }
    renderPreviews(loadImgModern, containLoadImg, previewArea);

    loadImgModern.addEventListener('change', (e) => {
      const input = e.target;
      if (input._isUpdating) return;

      const container = input.previousElementSibling;
      if (!container) return;

      const previewArea = container.querySelector(".js-load-img-preview-area");
      if (!previewArea) return;

      // Obtener nuevos archivos y añadirlos de forma acumulativa (sin duplicados)
      const newFiles = Array.from(input.files);
      newFiles.forEach(file => {
        const isDuplicate = input._selectedFiles.some(f => 
          f.name === file.name && 
          f.size === file.size && 
          f.lastModified === file.lastModified
        );
        if (!isDuplicate) {
          input._selectedFiles.push(file);
        }
      });

      // Actualizar el FileList nativo del input
      updateInputFiles(input);

      // Renderizar vistas previas
      renderPreviews(input, container, previewArea);
    });
  });
}
