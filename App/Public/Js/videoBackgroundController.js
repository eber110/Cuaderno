/**
 * Controlador de Video de Fondo para Perfiles (videoBackgroundController)
 * 
 * Implementa Delegación Global de Eventos (Event Delegation) a nivel de document.
 * Esto garantiza que al actualizarse el formulario vía Fetch/AJAX de forma asíncrona,
 * todos los eventos (subir, cambiar, recortar, eliminar, pausar/reproducir) sigan
 * respondiendo infinitamente sin requerir recargar la página.
 */
export function videoBackgroundController() {
  if (window.__videoBackgroundInitialized) {
    // Si ya está inicializado el delegado global, solo sincronizar miniaturas actuales
    syncThumbnailPlayback();
    return;
  }
  window.__videoBackgroundInitialized = true;

  let currentSelectedFile = null;
  let videoTotalDuration  = 0;
  let trimStartSeconds    = 0;
  let trimEndSeconds      = 0;
  const MAX_TRIM_DURATION = 20;

  // Formato mm:ss
  function formatTime(seconds) {
    const s = Math.max(0, Math.floor(seconds || 0));
    const m = Math.floor(s / 60);
    const remS = s % 60;
    return `${m.toString().padStart(2, '0')}:${remS.toString().padStart(2, '0')}`;
  }

  // Sincronizar UI de los deslizadores de recorte
  function updateTrimUI() {
    const startValText  = document.getElementById("trim-start-val");
    const endValText    = document.getElementById("trim-end-val");
    const rangeText     = document.getElementById("trim-range-text");
    const durationBadge = document.getElementById("trim-duration-badge");

    const diff = Math.max(0.5, trimEndSeconds - trimStartSeconds);
    const diffRounded = Math.round(diff * 10) / 10;

    if (startValText) startValText.textContent = formatTime(trimStartSeconds);
    if (endValText) endValText.textContent = formatTime(trimEndSeconds);
    if (rangeText) rangeText.textContent = `${formatTime(trimStartSeconds)} - ${formatTime(trimEndSeconds)}`;
    if (durationBadge) durationBadge.textContent = `${diffRounded}s`;
  }

  // Cerrar y resetear modal de recorte
  function closeTrimmerModal() {
    const trimmerModal = document.getElementById("video-trimmer-modal");
    const trimmerVideo = document.getElementById("trimmer-video-preview");
    const trimPlayIcon = document.getElementById("trim-play-icon");

    if (trimmerModal) {
      trimmerModal.style.display = "none";
      trimmerModal.classList.add("hidden");
    }
    if (trimmerVideo) {
      trimmerVideo.pause();
      trimmerVideo.removeAttribute("src");
      trimmerVideo.load();
    }
    if (trimPlayIcon) {
      trimPlayIcon.textContent = "▶";
    }
    const videoInput = document.getElementById("upload-video-input");
    if (videoInput) {
      videoInput.value = "";
    }
  }

  // Sincronizar reproducción del thumbnail y visibilidad del panel de video según el estilo seleccionado
  function syncThumbnailPlayback() {
    const checkedRadio = document.querySelector('input[name="style_back"]:checked');
    const isVideoActive = checkedRadio && checkedRadio.value === "video";
    const thumb = document.getElementById("thumb-video-preview");
    const videoWrapper = document.getElementById("video-controls-wrapper");

    if (videoWrapper) {
      videoWrapper.style.display = isVideoActive ? "flex" : "none";
    }

    if (thumb) {
      if (isVideoActive) {
        thumb.play().catch(() => {});
      } else {
        thumb.pause();
        thumb.currentTime = 0;
      }
    }
  }

  // =========================================================================
  // 1. DELEGACIÓN DE EVENTO: CHANGE (Selección de archivo y radios de estilo)
  // =========================================================================
  document.addEventListener("change", (e) => {
    const target = e.target;
    if (!target) return;

    // 1.1 Selección de nuevo archivo de video para recortar y encuadrar
    if (target.id === "upload-video-input") {
      const file = target.files && target.files[0];
      if (!file) return;

      currentSelectedFile = file;
      const trimmerModal = document.getElementById("video-trimmer-modal");
      const trimmerVideo = document.getElementById("trimmer-video-preview");
      const startSlider  = document.getElementById("trim-start-slider");
      const endSlider    = document.getElementById("trim-end-slider");
      const trimPlayIcon = document.getElementById("trim-play-icon");

      if (!trimmerModal || !trimmerVideo) return;

      const objectUrl = URL.createObjectURL(file);
      trimmerVideo.src = objectUrl;

      trimmerVideo.onloadedmetadata = () => {
        videoTotalDuration = trimmerVideo.duration;

        trimStartSeconds = 0;
        trimEndSeconds   = Math.min(videoTotalDuration, MAX_TRIM_DURATION);

        if (startSlider) {
          startSlider.min   = 0;
          startSlider.max   = videoTotalDuration;
          startSlider.step  = 0.1;
          startSlider.value = 0;
        }

        if (endSlider) {
          endSlider.min   = 0;
          endSlider.max   = videoTotalDuration;
          endSlider.step  = 0.1;
          endSlider.value = trimEndSeconds;
        }

        updateTrimUI();

        trimmerModal.classList.remove("hidden");
        trimmerModal.style.display = "flex";

        trimmerVideo.currentTime = 0;
        trimmerVideo.play().then(() => {
          if (trimPlayIcon) trimPlayIcon.textContent = "⏸";
        }).catch(() => {});
      };
    }

    // 1.2 Cambio de estilo de fondo (Video / Sólido / Degradado)
    if (target.name === "style_back") {
      syncThumbnailPlayback();
    }

    // 1.3 Cambio de estilo de cabecera (voidHero / regularHero / midHero)
    if (target.name === "header") {
      const voidContainer = document.getElementById("void-space-container");
      if (voidContainer) {
        voidContainer.style.display = target.value === "voidHero" ? "flex" : "none";
      }
    }

    // 1.3 Al soltar el deslizador de opacidad, persistir con auto-submit
    if (target.id === "select-opacity-overlay") {
      const form = target.closest("form.auto-submit") || target.closest("form");
      if (form && typeof form.requestSubmit === "function") {
        form.requestSubmit();
      }
    }
  });

  // =========================================================================
  // 2. DELEGACIÓN DE EVENTO: INPUT (Deslizadores de Inicio y Fin de Recorte)
  // =========================================================================
  document.addEventListener("input", (e) => {
    const target = e.target;
    if (!target) return;

    const trimmerVideo = document.getElementById("trimmer-video-preview");
    const startSlider  = document.getElementById("trim-start-slider");
    const endSlider    = document.getElementById("trim-end-slider");

    // Slider Inicio
    if (target.id === "trim-start-slider") {
      let val = parseFloat(target.value) || 0;

      if (val >= trimEndSeconds) {
        trimEndSeconds = Math.min(videoTotalDuration, val + 1);
        if (endSlider) endSlider.value = trimEndSeconds;
      }

      if (trimEndSeconds - val > MAX_TRIM_DURATION) {
        trimEndSeconds = Math.min(videoTotalDuration, val + MAX_TRIM_DURATION);
        if (endSlider) endSlider.value = trimEndSeconds;
      }

      trimStartSeconds = val;
      if (trimmerVideo) trimmerVideo.currentTime = trimStartSeconds;
      updateTrimUI();
    }

    // Slider Fin
    if (target.id === "trim-end-slider") {
      let val = parseFloat(target.value) || 0;

      if (val <= trimStartSeconds) {
        trimStartSeconds = Math.max(0, val - 1);
        if (startSlider) startSlider.value = trimStartSeconds;
      }

      if (val - trimStartSeconds > MAX_TRIM_DURATION) {
        trimStartSeconds = Math.max(0, val - MAX_TRIM_DURATION);
        if (startSlider) startSlider.value = trimStartSeconds;
      }

      trimEndSeconds = val;
      if (trimmerVideo) trimmerVideo.currentTime = trimStartSeconds;
      updateTrimUI();
    }

    // Slider de Opacidad del Overlay en vivo (UI: 0% a 100%, Backend y CSS: 0% a 95%)
    if (target.id === "select-opacity-overlay") {
      const sliderVal = Math.max(0, Math.min(100, parseInt(target.value, 10) || 0));
      const actualOpacity = Math.round((sliderVal / 100) * 95);

      target.style.setProperty("--range-progress", `${sliderVal}%`);

      const valText = document.getElementById("video-opacity-val");
      if (valText) valText.textContent = `${sliderVal}%`;

      const hiddenInput = document.getElementById("input-opacity-val");
      if (hiddenInput) hiddenInput.value = actualOpacity;

      const colorPicker = document.getElementById("select-color-overlay");
      const overlayColor = colorPicker ? colorPicker.value : "#000000";
      document.querySelectorAll(".back-video-overlay").forEach((overlay) => {
        overlay.style.backgroundColor = `oklch(from ${overlayColor} l c h / ${actualOpacity}%)`;
      });
    }

    // Selector de Color del Overlay en vivo
    if (target.id === "select-color-overlay") {
      const hiddenInput = document.getElementById("input-opacity-val");
      const val = hiddenInput ? parseInt(hiddenInput.value, 10) : 45;
      document.querySelectorAll(".back-video-overlay").forEach((overlay) => {
        overlay.style.backgroundColor = `oklch(from ${target.value} l c h / ${val}%)`;
      });
    }

    // Color de texto global: actualiza todo el texto Y el título en el preview (y sincroniza titleColor si existe en el panel)
    if (target.name === "colorText") {
      const newColor = target.value;
      document.querySelectorAll(".user-profile-preview .color-text-card").forEach((el) => {
        el.style.color = newColor;
      });
      document.querySelectorAll(".user-profile-preview .title-color, .user-profile-preview .title-hero-regular, .user-profile-preview .title-hero-big, .user-profile-preview .title-hero-mini, .user-profile-preview h1, .user-profile-preview h2, .user-profile-preview h3").forEach((el) => {
        el.style.color = newColor;
      });
      document.querySelectorAll(".user-profile-preview .color-menu-user").forEach((el) => {
        el.style.color = "#ffffff";
      });

      const titleInput = document.getElementById("select-color-title");
      if (titleInput) {
        titleInput.value = newColor;
        const labelText = titleInput.closest("label")?.querySelector("p, span");
        if (labelText) labelText.textContent = newColor;
      }
    }

    // Color de título independiente: actualiza SOLO el título en el preview
    if (target.name === "titleColor") {
      const newColor = target.value;
      document.querySelectorAll(".user-profile-preview .title-color, .user-profile-preview .title-hero-regular, .user-profile-preview .title-hero-big, .user-profile-preview .title-hero-mini, .user-profile-preview h1, .user-profile-preview h2, .user-profile-preview h3").forEach((el) => {
        el.style.color = newColor;
      });
    }
  });

  // =========================================================================
  // 3. DELEGACIÓN DE EVENTO: CLICK (Botones, eliminar, reproducir, confirmar)
  // =========================================================================
  document.addEventListener("click", async (e) => {
    const target = e.target;
    if (!target) return;

    // 3.1 Botón Play/Pausa en Modal de Recorte
    const btnPlayPause = target.closest("#btn-play-pause-trim");
    if (btnPlayPause) {
      e.preventDefault();
      const trimmerVideo = document.getElementById("trimmer-video-preview");
      const trimPlayIcon = document.getElementById("trim-play-icon");
      if (trimmerVideo) {
        if (trimmerVideo.paused) {
          trimmerVideo.play();
          if (trimPlayIcon) trimPlayIcon.textContent = "⏸";
        } else {
          trimmerVideo.pause();
          if (trimPlayIcon) trimPlayIcon.textContent = "▶";
        }
      }
      return;
    }

    // 3.2 Botones Cancelar / Cerrar Modal de Recorte
    if (target.closest("#btn-close-trimmer") || target.closest("#btn-cancel-trim")) {
      e.preventDefault();
      closeTrimmerModal();
      return;
    }

    // 3.3 Confirmar Recorte y Subir en Segundo Plano
    const btnConfirmTrim = target.closest("#btn-confirm-trim");
    if (btnConfirmTrim) {
      e.preventDefault();
      if (!currentSelectedFile) return;

      const fileToUpload = currentSelectedFile;
      const startSec     = Math.max(0, Math.round(trimStartSeconds * 10) / 10);
      const durationSec  = Math.min(MAX_TRIM_DURATION, Math.max(0.5, Math.round((trimEndSeconds - trimStartSeconds) * 10) / 10));

      closeTrimmerModal();

      const form = document.querySelector("#background-remote form") || document.querySelector("form.auto-submit");
      if (!form) return;

      const formAction = form.action || window.location.href;
      const panelMatch = formAction.match(/(\/panel\/[^\/?#]+)/i);
      const panelBasePath = panelMatch ? panelMatch[1] : "/panel";
      const signUrl = `${panelBasePath}/cloudinary-sign?start=${startSec}&duration=${durationSec}`;

      const sizeMb = (fileToUpload.size / (1024 * 1024)).toFixed(1);
      const statusBox = document.getElementById("video-upload-status");

      if (statusBox) {
        statusBox.classList.remove("hidden");
        statusBox.innerHTML = `
          <div class="p15 br15 back-item-menu flex-column gap10 border-smooth">
            <div class="flex-row center-between w100">
              <span class="x13 bold500 texto">⏳ Subiendo y encuadrando video (${sizeMb} MB, ${durationSec}s)...</span>
              <span id="video-progress-percent" class="x13 bold600 color-success">0%</span>
            </div>
            <div class="w100 br10 overflow-hidden" style="background: rgba(120,120,120,0.2); height: 8px;">
              <div id="video-progress-bar" class="h100 br10" style="width: 0%; background: linear-gradient(90deg, #2563eb, #38bdf8); transition: width 0.2s ease;"></div>
            </div>
            <p class="x11 texto opacity-70">Procesando encuadre vertical 9:16 y recorte de ${startSec}s a ${startSec + durationSec}s.</p>
          </div>
        `;
      }

      try {
        const signRes = await fetch(signUrl, {
          headers: { "X-Requested-With": "XMLHttpRequest" }
        });
        const signData = await signRes.json();

        if (!signData || !signData.success) {
          throw new Error(signData.error || "No se pudo obtener la firma de Cloudinary.");
        }

        const cldFormData = new FormData();
        cldFormData.append("file", fileToUpload);
        cldFormData.append("api_key", signData.api_key);
        cldFormData.append("timestamp", signData.timestamp);
        cldFormData.append("signature", signData.signature);
        cldFormData.append("folder", signData.folder);
        if (signData.eager) {
          cldFormData.append("eager", signData.eager);
        }

        const xhr = new XMLHttpRequest();
        xhr.open("POST", signData.upload_url, true);

        const progressBar = document.getElementById("video-progress-bar");
        const progressPercent = document.getElementById("video-progress-percent");

        xhr.upload.onprogress = (event) => {
          if (event.lengthComputable) {
            const percent = Math.round((event.loaded / event.total) * 100);
            if (progressBar) progressBar.style.width = `${percent}%`;
            if (progressPercent) progressPercent.textContent = `${percent}%`;
          }
        };

        xhr.onload = async () => {
          if (xhr.status >= 200 && xhr.status < 300) {
            const cldResponse = JSON.parse(xhr.responseText);
            let secureUrl = cldResponse.secure_url;
            if (cldResponse.eager && cldResponse.eager.length > 0 && cldResponse.eager[0].secure_url) {
              secureUrl = cldResponse.eager[0].secure_url;
            }
            const publicId = cldResponse.public_id;

            const curStatus = document.getElementById("video-upload-status");
            if (curStatus) {
              curStatus.innerHTML = `
                <div class="p12 br15 back-item-menu flex-row center-start gap10">
                  <span class="x16">⚙️</span>
                  <p class="x13 bold500 texto">Guardando fondo en tu perfil...</p>
                </div>
              `;
            }

            // Enviar formulario al backend
            const saveFormData = new FormData(form);
            saveFormData.set("style_back", "video");
            saveFormData.set("back_video_url_direct", secureUrl);
            saveFormData.set("back_video_public_id_direct", publicId);
            saveFormData.delete("back_video");

            const opacityInput = document.getElementById("select-opacity-overlay");
            if (opacityInput) saveFormData.set("back_video_opacity", opacityInput.value);
            const overlayColorInput = document.getElementById("select-color-overlay");
            if (overlayColorInput) saveFormData.set("back_video_overlay", overlayColorInput.value);

            const saveResponse = await fetch(formAction, {
              method: "POST",
              body: saveFormData,
              headers: { "X-Requested-With": "XMLHttpRequest" }
            });

            if (!saveResponse.ok) {
              throw new Error("Error en el servidor al guardar el diseño (" + saveResponse.status + ")");
            }

            const resData = await saveResponse.json();

            // 1. Actualizar vista previa del perfil
            if (resData && resData.html) {
              document.querySelectorAll(".user-profile-preview").forEach((container) => {
                const temp = document.createElement("div");
                temp.innerHTML = resData.html.trim();
                const targetPreview = temp.querySelector(".user-profile-preview") || temp.firstElementChild;
                if (targetPreview && container.parentNode) {
                  container.parentNode.replaceChild(targetPreview, container);
                } else {
                  container.innerHTML = resData.html;
                }
              });
            }

            // 2. Actualizar el panel #background-remote con el nuevo HTML del form
            if (resData && resData.formHtml) {
              const activeRemote = document.querySelector("#background-remote") || form.closest(".remote-content");
              if (activeRemote && activeRemote.id) {
                const tempForm = document.createElement("div");
                tempForm.innerHTML = resData.formHtml.trim();
                const matchingNewContent = tempForm.querySelector("#" + CSS.escape(activeRemote.id));
                if (matchingNewContent) {
                  activeRemote.innerHTML = matchingNewContent.innerHTML;
                }
              }
            }

            // 3. Sincronizar miniaturas y reproducción
            syncThumbnailPlayback();

            const finalStatus = document.getElementById("video-upload-status");
            if (finalStatus) {
              finalStatus.classList.remove("hidden");
              finalStatus.innerHTML = `
                <div class="p12 br15 back-live-view color-live-view flex-row center-start gap10">
                  <span class="x16">✅</span>
                  <p class="x13 bold500">Video encuadrado y subido con éxito.</p>
                </div>
              `;
            }

            // 4. Activar botón guardar global
            const saveBtnContainer = document.getElementById("save-btn-container");
            if (saveBtnContainer) {
              saveBtnContainer.dataset.hasCustom = "true";
              const saveBtn = document.getElementById("save-btn");
              if (saveBtn) {
                saveBtn.classList.remove("disabled-save-btn", "texto");
                saveBtn.classList.add("pointer", "back-save-panel", "textw", "bold500");
                saveBtn.removeAttribute("tabindex");
                saveBtn.removeAttribute("aria-disabled");
              }
              const discardBtn = document.getElementById("discard-btn");
              if (discardBtn) discardBtn.classList.remove("hidden");
            }

          } else {
            let errorMsg = "Error en la subida a Cloudinary.";
            try {
              const errObj = JSON.parse(xhr.responseText);
              if (errObj && errObj.error && errObj.error.message) {
                errorMsg = errObj.error.message;
              }
            } catch (e) {}

            const errStatus = document.getElementById("video-upload-status");
            if (errStatus) {
              errStatus.innerHTML = `
                <div class="p12 br15 back-danger textw flex-column gap5">
                  <p class="x13 bold600">❌ Error al subir video:</p>
                  <p class="x12">${errorMsg}</p>
                </div>
              `;
            }
          }
        };

        xhr.onerror = () => {
          const netStatus = document.getElementById("video-upload-status");
          if (netStatus) {
            netStatus.innerHTML = `
              <div class="p12 br15 back-danger textw flex-column gap5">
                <p class="x13 bold600">❌ Error de conexión:</p>
                <p class="x12">No se pudo completar la transferencia a Cloudinary.</p>
              </div>
            `;
          }
        };

        xhr.send(cldFormData);

      } catch (err) {
        const catchStatus = document.getElementById("video-upload-status");
        if (catchStatus) {
          catchStatus.innerHTML = `
            <div class="p12 br15 back-danger textw flex-column gap5">
              <p class="x13 bold600">❌ Error:</p>
              <p class="x12">${err.message || 'No se pudo iniciar la subida.'}</p>
            </div>
          `;
        }
      }
      return;
    }

    // 3.4 Botón Eliminar Video de Fondo
    const deleteBtn = target.closest("#btn-delete-back-video");
    if (deleteBtn) {
      e.preventDefault();
      e.stopPropagation();

      const form = deleteBtn.closest("form.auto-submit") || deleteBtn.closest("form") || document.querySelector("#background-remote form");
      if (!form) return;

      const deleteFlag = document.getElementById("delete-video-flag");
      if (deleteFlag) deleteFlag.value = "true";

      const solidRadio = form.querySelector('input[name="style_back"][value="solid"]');
      if (solidRadio) solidRadio.checked = true;

      const statusBox = document.getElementById("video-upload-status");
      if (statusBox) {
        statusBox.classList.remove("hidden");
        statusBox.innerHTML = `<div class="p10 br10 back-item-menu"><p class="x13 texto">Eliminando video de fondo...</p></div>`;
      }

      if (typeof form.requestSubmit === "function") {
        form.requestSubmit();
      } else {
        form.submit();
      }
      return;
    }
  });

  // =========================================================================
  // 4. DELEGACIÓN DE EVENTO: TIMEUPDATE (Bucle de reproducción dentro del rango)
  // =========================================================================
  document.addEventListener("timeupdate", (e) => {
    if (e.target && e.target.id === "trimmer-video-preview") {
      const v = e.target;
      if (v.currentTime >= trimEndSeconds || v.currentTime < trimStartSeconds) {
        v.currentTime = trimStartSeconds;
      }
    }
  }, true);

  // =========================================================================
  // 5. DELEGACIÓN DE EVENTO: ERROR (Videos rotos o 404 de Cloudinary)
  // =========================================================================
  document.addEventListener("error", (e) => {
    const target = e.target;
    if (!target) return;

    // Si falla el thumbnail
    if (target.id === "thumb-video-preview") {
      target.style.display = "none";
      const icon = target.nextElementSibling;
      if (icon) icon.style.display = "inline-block";
    }

    // Si falla el visor horizontal del panel de configuración
    if (target.closest && target.closest("#video-background-config") && target.tagName === "VIDEO") {
      const configBox = document.getElementById("video-background-config");
      if (configBox) {
        configBox.innerHTML = `
          <div class="flex-row center-between w100">
            <p class="texto bold500">Video de fondo (máx. 20s)</p>
            <span class="x12 texto opacity-70">Cloudinary</span>
          </div>
          <label for="upload-video-input" class="w100 p20 br15 pointer flex-column center-center gap10 hover-scale-soft back-item-menu" style="border: 2px dashed rgba(150,150,150,0.4);">
            <span class="x28">📹</span>
            <p class="texto x14 bold500 text-center">Haz clic para subir un video corto (MP4, WebM)</p>
            <p class="texto x12 opacity-70 text-center">Duración máxima: 20 segundos</p>
          </label>
          <input type="file" id="upload-video-input" accept="video/mp4,video/webm,video/quicktime" class="hidden no-auto-submit" no-auto-submit>
          <input type="hidden" id="input-back-video-url" name="back_video_url_direct" value="">
          <input type="hidden" id="input-back-video-public-id" name="back_video_public_id_direct" value="">
          <input type="hidden" id="delete-video-flag" name="delete_video" value="false">
          <div id="video-upload-status" class="x13 texto mt5 hidden"></div>
        `;
      }
    }
  }, true);

  // Sincronización inicial
  syncThumbnailPlayback();
}
