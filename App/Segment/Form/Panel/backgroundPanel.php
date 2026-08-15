<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $styleBack         = $card["backCard"]["style_back"] ?? $card["backCard"][1] ?? 'solid';
  $backPerfil        = $card["backCard"]["back_perfil"] ?? $card["backCard"][0] ?? '#272727';
  $backVideo         = $card["backCard"]["back_video"] ?? '';
  $backVideoPublicId = $card["backCard"]["back_video_public_id"] ?? '';
  $backVideoOverlay  = $card["backCard"]["back_video_overlay"] ?? '#000000';
  $backVideoOpacity  = max(0, min(95, intval($card["backCard"]["back_video_opacity"] ?? 45)));
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post" enctype="multipart/form-data">

  <div class="flex-column top-between gap20">

    <!-- Estilo del fondo, opciones de como mostrar el fondo -->
    <div class="flex-column top-start gap10 w100">
      <p class="texto">Estilo del fondo</p>

      <div class="flex-row center-start flex-wrap gap10 w100">
        
        <input type="radio" id="style_gradient" name="style_back" class="hidden-radio" value="gradientDown" <?php if ($styleBack == "gradientUp" || $styleBack == "gradientDown") echo "checked";?>>
        <label for="style_gradient">
          <div class="back-card-graphic shadow-card-graphic hover-scale-soft p5 br20 flex-column center-center gap5">
            <div class="hpx80 wpx80 br15" style="background: linear-gradient(180deg,
              oklch(from <?= $backPerfil?> calc(l * 0.60) calc(c - 0.01) h / 88%),
              oklch(from <?= $backPerfil?> calc(l * 1.15) calc(c - 0.03) calc(h - 30) / 90%)
              );"></div>
            <p class="x16 texto">Degradado</p>
          </div>
        </label>
    
        <input type="radio" id="style_solid" name="style_back" class="hidden-radio" value="solid" <?php if ($styleBack == "solid") echo "checked";?>>
        <label for="style_solid">
          <div class="back-card-graphic shadow-card-graphic hover-scale-soft p5 br20 flex-column center-center gap5">
            <div class="hpx80 wpx80 br15" style="background-color: <?= $backPerfil?>;"></div>
            <p class="x16 texto">Sólido</p>
          </div>
        </label>

        <input type="radio" id="style_video" name="style_back" class="hidden-radio" value="video" <?php if ($styleBack == "video") echo "checked";?>>
        <label for="style_video">
          <div class="back-card-graphic shadow-card-graphic hover-scale-soft p5 br20 flex-column center-center gap5 pointer">
            <div class="hpx80 wpx80 br15 flex-column center-center pointer" style="background-color: #1e1e1e; overflow: hidden; position: relative;">
              <?php if (!empty($backVideo)) : ?>
                <video id="thumb-video-preview" src="<?= $backVideo ?>" class="w100 h100" style="object-fit: cover; pointer-events: none;" muted <?php if ($styleBack === "video") echo "autoplay"; ?> loop playsinline disablePictureInPicture tabindex="-1" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline-block';"></video>
                <span class="x22 textw" style="<?php if (!empty($backVideo)) echo 'display:none;'; ?>">▶</span>
              <?php else : ?>
                <span class="x22 textw">▶</span>
              <?php endif; ?>
            </div>
            <p class="x16 texto">Video</p>
          </div>
        </label>
  
      </div>
    </div>

    <!-- Contenedor de configuración de video (visible solo cuando el estilo es video) -->
    <div id="video-controls-wrapper" class="flex-column gap15 w100" style="<?php if ($styleBack !== 'video') echo 'display: none;'; ?>">
      
      <!-- Panel de subida / visor de video -->
      <div class="flex-column top-start gap10 w100 p15 br20 back-card-graphic shadow-card-graphic" id="video-background-config">
        <div class="flex-row center-between w100">
          <p class="texto bold500">Video de fondo (máx. 20s)</p>
          <span class="x12 texto opacity-70">Cloudinary</span>
        </div>

        <?php if (!empty($backVideo)) : ?>
          <div class="w100 flex-column gap10">
            <div class="w100 hpx140 br15 overflow-hidden" style="background: #000; position: relative;">
              <video src="<?= $backVideo ?>" class="w100 h100" style="object-fit: cover; pointer-events: none;" muted autoplay loop playsinline disablePictureInPicture tabindex="-1" onerror="this.style.display='none';"></video>
            </div>
            <div class="flex-row center-between w100 gap10">
              <label for="upload-video-input" class="pointer p10 br15 back-button-panel text-button-panel hover-scale-soft x14 bold500 flex-row center-center gap5 flex-1">
                <?= svg("edit"); ?> Cambiar video
              </label>
              <button type="button" id="btn-delete-back-video" class="pointer p10 br15 back-danger textw hover-scale-soft x14 bold500 flex-row center-center gap5">
                <?= svg("trash"); ?> Eliminar
              </button>
            </div>
          </div>
        <?php else : ?>
          <label for="upload-video-input" class="w100 p20 br15 pointer flex-column center-center gap10 hover-scale-soft back-item-menu" style="border: 2px dashed rgba(150,150,150,0.4);">
            <span class="x28">📹</span>
            <p class="texto x14 bold500 text-center">Haz clic para subir un video corto (MP4, WebM)</p>
            <p class="texto x12 opacity-70 text-center">Duración máxima: 20 segundos</p>
          </label>
        <?php endif; ?>

        <input type="file" id="upload-video-input" accept="video/mp4,video/webm,video/quicktime" class="hidden no-auto-submit" no-auto-submit>
        <input type="hidden" id="input-back-video-url" name="back_video_url_direct" value="">
        <input type="hidden" id="input-back-video-public-id" name="back_video_public_id_direct" value="">
        <input type="hidden" id="delete-video-flag" name="delete_video" value="false">
        <div id="video-upload-status" class="x13 texto mt5 hidden"></div>
      </div>

      <!-- Control de Opacidad del Overlay (UI: 0% a 100%, Backend: 0% a 95%) -->
      <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100" id="video-overlay-opacity-row">
        <p class="texto">Opacidad del overlay</p>

        <?php $userOpacityPercent = $backVideoOpacity > 0 ? min(100, max(0, round(($backVideoOpacity / 95) * 100))) : 0; ?>
        <div class="flex-row center-end gap10 w-sml-100">
          <span id="video-opacity-val" class="x14 bold600 texto wpx40 text-right"><?= $userOpacityPercent ?>%</span>
          <input type="range" id="select-opacity-overlay" min="0" max="100" step="1" value="<?= $userOpacityPercent ?>" class="pointer custom-range-slider" style="--range-progress: <?= $userOpacityPercent ?>%;">
          <input type="hidden" id="input-opacity-val" name="back_video_opacity" value="<?= $backVideoOpacity ?>">
        </div>
      </div>

      <!-- Selector de color para superposición (Overlay) del video -->
      <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100" id="video-overlay-color-row">
        <p class="texto">Color de superposición (Overlay)</p>

        <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
          <label data-trigger-color="select-color-overlay" class="flex-row center-start p10 gap10 pointer">
            <input type="color" id="select-color-overlay" name="back_video_overlay" value="<?= $backVideoOverlay?>" class="color-picker box-color-picker"
            style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
            <p class="x16 bold500 texto"><?= $backVideoOverlay?></p>
          </label>
        </div>
      </div>

    </div>

    <!-- Color de fondo de la aplicación -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="texto">Color base / fondo</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
        <label data-trigger-color="select-color" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color" name="back_perfil" value="<?= $backPerfil?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="x16 bold500 texto"><?= $backPerfil?></p>
        </label>
      </div>
    </div>

    <!-- Color de texto global -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="texto">Color de texto</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
        <label data-trigger-color="select-color-text-app" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color-text-app" name="colorText" value="<?= $card["colorText"] ?? "#383838"?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker box-color-picker">
          <p class="x16 bold500 texto"><?= $card["colorText"] ?? "#383838"?></p>
        </label>
      </div>
    </div>
  
    <!-- Dirección del degradado -->
    <?php if ($styleBack == "gradientUp" || $styleBack == "gradientDown") :?>

      <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">

        <p class="texto">Dirección del degradado</p>
  
        <div class="flex-row center-end gap10">
          <input type="radio" id="direction_up" name="style_back" class="hidden-radio" value="gradientUp" <?php if ($styleBack == "gradientUp") echo "checked";?>>
          <label for="direction_up">
            <div class="br15 p10 back-card-graphic shadow-card-graphic hover-scale-soft texto">
              <?= svg("arrow-up");?> Arriba
            </div>
          </label>
          
          <input type="radio" id="direction_down" name="style_back" class="hidden-radio" value="gradientDown" <?php if ($styleBack == "gradientDown") echo "checked";?>>
          <label for="direction_down">
            <div class="br15 p10 back-card-graphic shadow-card-graphic hover-scale-soft texto">
              <?= svg("arrow-down");?> Abajo
            </div>
          </label>
        </div>
  
      </div>

    <?php endif?>

  </div>

  <!-- Modal de recorte de tiempo y encuadre vertical de video (Aspecto Teléfono 9:16) -->
  <div id="video-trimmer-modal" class="hidden" style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 99999; display: none; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 15px;">
    <div class="back-card-graphic shadow-card-graphic br25 p20 flex-column center-center gap15 w100" style="max-width: 440px; max-height: 92vh; overflow-y: auto;">
      
      <!-- Cabecera del modal -->
      <div class="flex-row center-between w100">
        <div class="flex-column gap2">
          <p class="x16 bold600 texto">Encuadrar y Recortar Video</p>
          <p class="x12 texto opacity-70">Ajusta inicio, final y encuadre vertical (máx. 20s)</p>
        </div>
        <button type="button" id="btn-close-trimmer" class="pointer p5 br50 hover-scale-soft back-item-menu border-none flex-row center-center">
          <?= svg("xmark", "x16"); ?>
        </button>
      </div>

      <!-- Marco de teléfono (Aspecto 9:16 vertical) -->
      <div class="flex-column center-center w100 my5">
        <div class="relative overflow-hidden br25 shadow-card" style="width: 200px; height: 355px; background: #000; border: 4px solid #333; position: relative;">
          <video id="trimmer-video-preview" class="w100 h100" style="object-fit: cover; pointer-events: none;" playsinline muted></video>
          
          <!-- Botón flotante Play/Pausa -->
          <button type="button" id="btn-play-pause-trim" class="pointer absolute center-center p10 br50 back-card-graphic textw border-none shadow-1 hover-scale-soft" style="bottom: 12px; left: 50%; transform: translateX(-50%); z-index: 10; opacity: 0.9;">
            <span id="trim-play-icon" class="x16">▶</span>
          </button>
        </div>
        <p class="x11 texto opacity-70 mt5">📱 Vista previa en encuadre de teléfono (9:16)</p>
      </div>

      <!-- Controles de tiempo (Inicio, Fin y Duración) -->
      <div class="flex-column gap10 w100 p12 br15 back-item-menu">
        <div class="flex-row center-between w100 x13 bold500 texto">
          <span>⏱️ Intervalo: <b id="trim-range-text" class="color-success">00:00 - 00:20</b></span>
          <span id="trim-duration-badge" class="x12 bold600 p2 px6 br10 back-card-graphic">20s</span>
        </div>

        <!-- Slider Inicio -->
        <div class="flex-column gap5 w100">
          <div class="flex-row center-between w100 x11 texto opacity-80">
            <span>Punto de Inicio</span>
            <span id="trim-start-val">00:00</span>
          </div>
          <input type="range" id="trim-start-slider" min="0" max="100" step="0.1" value="0" class="w100 pointer no-auto-submit" no-auto-submit style="accent-color: #2563eb;">
        </div>

        <!-- Slider Fin -->
        <div class="flex-column gap5 w100">
          <div class="flex-row center-between w100 x11 texto opacity-80">
            <span>Punto de Fin</span>
            <span id="trim-end-val">00:20</span>
          </div>
          <input type="range" id="trim-end-slider" min="0" max="100" step="0.1" value="20" class="w100 pointer no-auto-submit" no-auto-submit style="accent-color: #38bdf8;">
        </div>
      </div>

      <!-- Botones de Acción -->
      <div class="flex-row center-between gap10 w100">
        <button type="button" id="btn-cancel-trim" class="pointer p12 br15 back-item-menu texto hover-scale-soft x14 bold500 flex-1 border-none">
          Cancelar
        </button>
        <button type="button" id="btn-confirm-trim" class="pointer p12 br15 back-save-panel textw hover-scale-soft x14 bold600 flex-1 border-none flex-row center-center gap5">
          <span>✨ Aplicar y Subir</span>
        </button>
      </div>

    </div>
  </div>
  
  <input type="submit" value="guardar" class="hidden">
</form>