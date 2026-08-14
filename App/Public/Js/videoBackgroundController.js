/**
 * Controlador de Video de Fondo para Perfiles (videoBackgroundController)
 * 
 * Incluye herramienta interactiva de recorte de tiempo (inicio/fin máx. 20s)
 * y encuadre en aspecto de teléfono (9:16 vertical), con subida directa en 2do plano.
 */
export function videoBackgroundController() {
  const videoInput = document.getElementById("upload-video-input");
  const deleteBtn  = document.getElementById("btn-delete-back-video");
  const deleteFlag = document.getElementById("delete-video-flag");
  const statusBox  = document.getElementById("video-upload-status");

  // Elementos del Modal Trimmer & Aspect Framer
  const trimmerModal   = document.getElementById("video-trimmer-modal");
  const trimmerVideo   = document.getElementById("trimmer-video-preview");
  const btnPlayPause   = document.getElementById("btn-play-pause-trim");
  const trimPlayIcon   = document.getElementById("trim-play-icon");
  const startSlider    = document.getElementById("trim-start-slider");
  const endSlider      = document.getElementById("trim-end-slider");
  const startValText   = document.getElementById("trim-start-val");
  const endValText     = document.getElementById("trim-end-val");
  const rangeText      = document.getElementById("trim-range-text");
  const durationBadge  = document.getElementById("trim-duration-badge");
  const btnCloseModal  = document.getElementById("btn-close-trimmer");
  const btnCancelModal = document.getElementById("btn-cancel-trim");
  const btnConfirmTrim = document.getElementById("btn-confirm-trim");

  let currentSelectedFile = null;
  let videoTotalDuration  = 0;
  let trimStartSeconds    = 0;
  let trimEndSeconds      = 0;
  const MAX_TRIM_DURATION = 20; // 20 segundos máximo

  // Función auxiliar de formato mm:ss
  function formatTime(seconds) {
    const s = Math.max(0, Math.floor(seconds || 0));
    const m = Math.floor(s / 60);
    const remS = s % 60;
    return `${m.toString().padStart(2, '0')}:${remS.toString().padStart(2, '0')}`;
  }

  // Cerrar y resetear modal
  function closeTrimmerModal() {
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
  }

  // Actualizar indicadores visuales de los sliders
  function updateTrimUI() {
    const diff = Math.max(0.5, trimEndSeconds - trimStartSeconds);
    const diffRounded = Math.round(diff * 10) / 10;

    if (startValText) startValText.textContent = formatTime(trimStartSeconds);
    if (endValText) endValText.textContent = formatTime(trimEndSeconds);
    if (rangeText) rangeText.textContent = `${formatTime(trimStartSeconds)} - ${formatTime(trimEndSeconds)}`;
    if (durationBadge) durationBadge.textContent = `${diffRounded}s`;
  }

  // 1. Apertura del Modal Trimmer al seleccionar archivo
  if (videoInput && trimmerModal && trimmerVideo) {
    videoInput.classList.add("no-auto-submit");

    videoInput.addEventListener("change", (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;

      currentSelectedFile = file;
      const objectUrl = URL.createObjectURL(file);
      trimmerVideo.src = objectUrl;

      trimmerVideo.onloadedmetadata = () => {
        videoTotalDuration = trimmerVideo.duration;

        // Configurar rangos iniciales
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

        // Mostrar modal
        trimmerModal.classList.remove("hidden");
        trimmerModal.style.display = "flex";

        // Reproducir automáticamente el fragmento inicial
        trimmerVideo.currentTime = 0;
        trimmerVideo.play().then(() => {
          if (trimPlayIcon) trimPlayIcon.textContent = "⏸";
        }).catch(() => {});
      };
    });

    // 1.1 Slider de Inicio
    if (startSlider) {
      startSlider.addEventListener("input", (e) => {
        let val = parseFloat(e.target.value) || 0;

        if (val >= trimEndSeconds) {
          trimEndSeconds = Math.min(videoTotalDuration, val + 1);
          if (endSlider) endSlider.value = trimEndSeconds;
        }

        if (trimEndSeconds - val > MAX_TRIM_DURATION) {
          trimEndSeconds = Math.min(videoTotalDuration, val + MAX_TRIM_DURATION);
          if (endSlider) endSlider.value = trimEndSeconds;
        }

        trimStartSeconds = val;
        trimmerVideo.currentTime = trimStartSeconds;
        updateTrimUI();
      });
    }

    // 1.2 Slider de Fin
    if (endSlider) {
      endSlider.addEventListener("input", (e) => {
        let val = parseFloat(e.target.value) || 0;

        if (val <= trimStartSeconds) {
          trimStartSeconds = Math.max(0, val - 1);
          if (startSlider) startSlider.value = trimStartSeconds;
        }

        if (val - trimStartSeconds > MAX_TRIM_DURATION) {
          trimStartSeconds = Math.max(0, val - MAX_TRIM_DURATION);
          if (startSlider) startSlider.value = trimStartSeconds;
        }

        trimEndSeconds = val;
        trimmerVideo.currentTime = trimStartSeconds;
        updateTrimUI();
      });
    }

    // 1.3 Control de reproducción en bucle dentro del intervalo
    trimmerVideo.addEventListener("timeupdate", () => {
      if (trimmerVideo.currentTime >= trimEndSeconds || trimmerVideo.currentTime < trimStartSeconds) {
        trimmerVideo.currentTime = trimStartSeconds;
      }
    });

    // 1.4 Botón Play / Pausa
    if (btnPlayPause) {
      btnPlayPause.addEventListener("click", (e) => {
        e.preventDefault();
        if (trimmerVideo.paused) {
          trimmerVideo.play();
          if (trimPlayIcon) trimPlayIcon.textContent = "⏸";
        } else {
          trimmerVideo.pause();
          if (trimPlayIcon) trimPlayIcon.textContent = "▶";
        }
      });
    }

    // 1.5 Botones de Cancelar / Cerrar
    if (btnCloseModal) {
      btnCloseModal.addEventListener("click", () => {
        closeTrimmerModal();
        videoInput.value = "";
      });
    }
    if (btnCancelModal) {
      btnCancelModal.addEventListener("click", () => {
        closeTrimmerModal();
        videoInput.value = "";
      });
    }

    // 1.6 Confirmar y Subir con parámetros de recorte exactos
    if (btnConfirmTrim) {
      btnConfirmTrim.addEventListener("click", async () => {
        if (!currentSelectedFile) return;

        const fileToUpload = currentSelectedFile;
        const startSec     = Math.max(0, Math.round(trimStartSeconds * 10) / 10);
        const durationSec  = Math.min(MAX_TRIM_DURATION, Math.max(0.5, Math.round((trimEndSeconds - trimStartSeconds) * 10) / 10));

        // Cerrar modal
        closeTrimmerModal();

        const form = videoInput.closest("form.auto-submit") || videoInput.closest("form");
        if (!form) return;

        const formAction = form.action || window.location.href;
        const panelMatch = formAction.match(/(\/panel\/[^\/?#]+)/i);
        const panelBasePath = panelMatch ? panelMatch[1] : "/panel";
        const signUrl = `${panelBasePath}/cloudinary-sign?start=${startSec}&duration=${durationSec}`;

        const sizeMb = (fileToUpload.size / (1024 * 1024)).toFixed(1);

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
          // Obtener firma con recorte y encuadre 9:16 dinámico
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
              const publicId  = cldResponse.public_id;

              if (statusBox) {
                statusBox.innerHTML = `
                  <div class="p12 br15 back-item-menu flex-row center-start gap10">
                    <span class="x16">⚙️</span>
                    <p class="x13 bold500 texto">Guardando fondo en tu perfil...</p>
                  </div>
                `;
              }

              // Guardar en base de datos
              const saveFormData = new FormData(form);
              saveFormData.set("style_back", "video");
              saveFormData.set("back_video_url_direct", secureUrl);
              saveFormData.set("back_video_public_id_direct", publicId);
              saveFormData.delete("back_video");

              const saveResponse = await fetch(formAction, {
                method: "POST",
                body: saveFormData,
                headers: { "X-Requested-With": "XMLHttpRequest" }
              });

              const resData = await saveResponse.json();

              if (statusBox) {
                statusBox.innerHTML = `
                  <div class="p12 br15 back-live-view color-live-view flex-row center-start gap10">
                    <span class="x16">✅</span>
                    <p class="x13 bold500">Video encuadrado y subido con éxito.</p>
                  </div>
                `;
              }

              // Actualizar vista previa
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

              // Actualizar formulario del panel activo (#background-remote) para mostrar la miniatura nueva y el botón cambiar/eliminar
              if (resData && resData.formHtml) {
                const activeRemote = form.closest(".remote-content") || document.querySelector("#background-remote");
                if (activeRemote && activeRemote.id) {
                  const tempForm = document.createElement("div");
                  tempForm.innerHTML = resData.formHtml.trim();
                  const matchingNewContent = tempForm.querySelector("#" + CSS.escape(activeRemote.id));
                  if (matchingNewContent) {
                    activeRemote.innerHTML = matchingNewContent.innerHTML;
                    // Re-vincular controladores y componentes interactivos
                    videoBackgroundController();
                  }
                }
              }

              // Actualizar también directamente las etiquetas <video> del formulario
              document.querySelectorAll("video:not(#trimmer-video-preview)").forEach((v) => {
                if (!v.classList.contains("back-video-bg")) {
                  v.src = secureUrl;
                  v.load();
                  v.play().catch(() => {});
                }
              });

              // Activar botón guardar
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

              if (statusBox) {
                statusBox.innerHTML = `
                  <div class="p12 br15 back-danger textw flex-column gap5">
                    <p class="x13 bold600">❌ Error al subir video:</p>
                    <p class="x12">${errorMsg}</p>
                  </div>
                `;
              }
            }
          };

          xhr.onerror = () => {
            if (statusBox) {
              statusBox.innerHTML = `
                <div class="p12 br15 back-danger textw flex-column gap5">
                  <p class="x13 bold600">❌ Error de conexión:</p>
                  <p class="x12">No se pudo completar la transferencia a Cloudinary.</p>
                </div>
              `;
            }
          };

          xhr.send(cldFormData);

        } catch (err) {
          if (statusBox) {
            statusBox.innerHTML = `
              <div class="p12 br15 back-danger textw flex-column gap5">
                <p class="x13 bold600">❌ Error:</p>
                <p class="x12">${err.message || 'No se pudo iniciar la subida.'}</p>
              </div>
            `;
          }
        }
      });
    }
  }

  // 2. Eliminación de video de fondo
  if (deleteBtn && deleteFlag) {
    deleteBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      const form = deleteBtn.closest("form.auto-submit") || deleteBtn.closest("form");
      if (!form) return;

      deleteFlag.value = "true";

      const solidRadio = form.querySelector('input[name="style_back"][value="solid"]');
      if (solidRadio) {
        solidRadio.checked = true;
      }

      if (statusBox) {
        statusBox.classList.remove("hidden");
        statusBox.innerHTML = `<div class="p10 br10 back-item-menu"><p class="x13 texto">Eliminando video de fondo...</p></div>`;
      }

      if (typeof form.requestSubmit === "function") {
        form.requestSubmit();
      } else {
        form.submit();
      }
    });
  }
}
