<?php
  /** 
   * @var mixed $card 
   * @var int $dataContent
   * @var mixed $isBgMode
   */
  $campaignData    = $card["content"][$dataContent] ?? [];
  $title           = trim($campaignData["title"] ?? "");
  $desc            = trim($campaignData["desc"] ?? "");
  $name            = trim($campaignData["name"] ?? "");
  $email           = trim($campaignData["email"] ?? "");
  $whatsapp        = trim($campaignData["whatsapp"] ?? "");
  $imgSrc          = $campaignData["imgSrc"] ?? "";
  $imgPosition     = $campaignData["img_position"] ?? "background";
  $bgOpacity       = (int)($campaignData["bg_opacity"] ?? 80);
  $bgColor         = $campaignData["bg_color"] ?? "#1e1e1e";
  $hasCountdown    = !empty($campaignData["has_countdown"]);
  $countdownDate   = $campaignData["countdown_date"] ?? "";
  if ($hasCountdown && !empty($countdownDate)) {
    $targetTimestamp = strtotime($countdownDate);
    if ($targetTimestamp !== false && $targetTimestamp <= time()) {
      return;
    }
  }
  $buttonText      = !empty(trim($campaignData["button_text"] ?? "")) ? trim($campaignData["button_text"]) : "Suscribirme";
  $titleColor      = !empty($campaignData["title_color"]) ? $campaignData["title_color"] : ($isBgMode ? "#ffffff" : ($card["titleColor"] ?? "#1e1e1e"));
  $descColor       = !empty($campaignData["desc_color"]) ? $campaignData["desc_color"] : ($isBgMode ? "#ffffff" : ($card["colorText"] ?? "#4a4a4a"));
  $btnBgColor      = !empty($campaignData["btn_bg_color"]) ? $campaignData["btn_bg_color"] : ($card["back"] ?? "#595a83");
  $btnTextColor    = !empty($campaignData["btn_text_color"]) ? $campaignData["btn_text_color"] : ($card["color"] ?? "#ffffff");
  $countdownBgColor   = !empty($campaignData["countdown_bg_color"]) ? $campaignData["countdown_bg_color"] : ($isBgMode ? "rgba(0,0,0,0.35)" : "rgba(150,150,150,0.1)");
  $countdownTextColor = !empty($campaignData["countdown_text_color"]) ? $campaignData["countdown_text_color"] : ($isBgMode ? "#ffffff" : "#1e1e1e");
  $countdownTextSize  = $campaignData["countdown_text_size"] ?? "medium";
  if (!in_array($countdownTextSize, ["small", "medium", "large"], true)) {
    $countdownTextSize = "medium";
  }
  $countdownWidgetSize = $campaignData["countdown_widget_size"] ?? "medium";
  if (!in_array($countdownWidgetSize, ["small", "medium", "large"], true)) {
    $countdownWidgetSize = "medium";
  }
  $textAlign       = $campaignData["text_align"] ?? "center";
  if (!in_array($textAlign, ["left", "center", "right"], true)) {
    $textAlign = "center";
  }
  $titleSize       = $campaignData["title_size"] ?? "large";
  if (!in_array($titleSize, ["small", "medium", "large"], true)) {
    $titleSize = "large";
  }
  $descSize        = $campaignData["desc_size"] ?? "medium";
  if (!in_array($descSize, ["small", "medium", "large"], true)) {
    $descSize = "medium";
  }
  $rawAskName      = $campaignData["ask_name"] ?? true;
  $askName         = ($rawAskName === true || $rawAskName === 'true' || $rawAskName === 1 || $rawAskName === '1');
  $rawAskWhatsapp  = $campaignData["ask_whatsapp"] ?? (!empty($whatsapp));
  $askWhatsapp     = ($rawAskWhatsapp === true || $rawAskWhatsapp === 'true' || $rawAskWhatsapp === 1 || $rawAskWhatsapp === '1');
  $profile         = $card["profile"] ?? "";
  $size            = $campaignData["size"] ?? "horizontal";
  if (!in_array($size, ["horizontal", "square", "vertical"], true)) {
    $size = "horizontal";
  }
  $textPosition    = $campaignData["text_position"] ?? "center";
  if (!in_array($textPosition, ["top", "center", "bottom"], true)) {
    $textPosition = "center";
  }

  $sizeClass      = "campaign-size-" . $size;
  $textPosClass   = ($size !== "horizontal") ? ("campaign-text-pos-" . $textPosition) : "";
  $btnAnchorClass = ($size !== "horizontal") ? "campaign-btn-anchor-bottom" : "";

  $rawImgShow = $campaignData["imgShow"] ?? true;
  $imgShow    = ($rawImgShow === true || $rawImgShow === 'true' || $rawImgShow === 1 || $rawImgShow === '1');
  $hasImg     = !empty($imgSrc) && strpos($imgSrc, 'no-image.webp') === false;

  $borderCard = ($card["borders"][0] == "br50") ? "br20" : ($card["borders"][0] ?? "br15");
  $shadowCard = $card["shadow"] ?? "shadow-card";
  $isBgMode     = ($imgPosition === "background" && $imgShow && $hasImg);
  $hasHeaderImg = ($imgPosition === "header" && $imgShow && $hasImg);
  $campaignId   = "campaign-block-" . $dataContent;
?>

<div id="<?= $campaignId ?>" class="campaign-block-wrapper |theme-button w100 flex-column overflow-hidden position-relative <?= $sizeClass ?> <?= $borderCard ?> <?= $shadowCard ?>" style="background-color: <?= $bgColor?>;">

  <?php if ($isBgMode) : ?>
    <!-- Imagen de fondo y capa de opacidad/color -->
    <div class="campaign-bg-layer" style="position: absolute; inset: 0; width: 100%; height: 100%; z-index: 0; overflow: hidden; pointer-events: none;">
      <img src="<?= e($imgSrc) ?>" alt="<?= e($title) ?>" class="cover w100 h100" style="object-fit: cover;">
      <div class="campaign-bg-overlay" style="position: absolute; inset: 0; width: 100%; height: 100%; background-color: oklch(from <?= e($bgColor) ?> l c h / <?= $bgOpacity ?>%);"></div>
    </div>
  <?php elseif ($hasHeaderImg) : ?>
    <!-- Imagen destacada de cabecera cuadrada -->
    <figure class="w100 ar-square faded-image">
      <img src="<?= e($imgSrc) ?>" alt="<?= e($title) ?>" class="cover w100 ar-square">
    </figure>
  <?php endif; ?>

  <!-- Contenido de la campaña -->
  <div class="campaign-content flex-column gap15 p20 <?= ($hasHeaderImg) ? "pt0" : "pt20"?> w100 position-relative z-index-1 <?= ($size !== 'horizontal') ? 'flex-1' : '' ?>" style="box-sizing: border-box;<?= ($size !== 'horizontal') ? ' flex: 1 1 auto; min-height: max-content;' : '' ?>">
    
    <!-- Grupo de texto y contador -->
    <div class="campaign-text-group flex-column gap15 w100 campaign-align-<?= $textAlign ?> <?= $textPosClass ?>">
      <!-- Título y Descripción -->
      <div class="flex-column gap5 w100 campaign-align-<?= $textAlign ?>">
        <?php if (!empty($title)) : ?>
          <h3 class="bold700 campaign-title-<?= $titleSize ?> w100" style="color: <?= e($titleColor) ?>;"><?= e($title) ?></h3>
        <?php endif; ?>

        <?php if (!empty($desc)) : ?>
          <p class="campaign-desc-<?= $descSize ?> w100" style="color: <?= e($descColor) ?>;"><?= nl2br(e($desc)) ?></p>
        <?php endif; ?>
      </div>

      <!-- Contador Regresivo (si está configurado) -->
      <?php if ($hasCountdown && !empty($countdownDate)) : ?>
        <div class="campaign-countdown-box grid col-7 campaign-countdown-widget-<?= $countdownWidgetSize ?> countdown-text-<?= $countdownTextSize ?>" data-countdown="<?= e($countdownDate) ?>" style="background-color: <?= e($countdownBgColor) ?>; color: <?= e($countdownTextColor) ?>; backdrop-filter: blur(4px);">
          <div class="flex-column center-center flex-1">
            <span class="countdown-days countdown-num bold700">00</span>
            <span class="countdown-unit text-uppercase opacity-70">Días</span>
          </div>
          <span class="countdown-sep bold700 opacity-50">:</span>
          <div class="flex-column center-center flex-1">
            <span class="countdown-hours countdown-num bold700">00</span>
            <span class="countdown-unit text-uppercase opacity-70">Horas</span>
          </div>
          <span class="countdown-sep bold700 opacity-50">:</span>
          <div class="flex-column center-center flex-1">
            <span class="countdown-minutes countdown-num bold700">00</span>
            <span class="countdown-unit text-uppercase opacity-70">Min</span>
          </div>
          <span class="countdown-sep bold700 opacity-50">:</span>
          <div class="flex-column center-center flex-1">
            <span class="countdown-seconds countdown-num bold700">00</span>
            <span class="countdown-unit text-uppercase opacity-70">Seg</span>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <p class="modal-btn animated darken p15 bold500 pointer text-center <?= $btnAnchorClass ?> <?= $card["shadow"]?> <?= $card["borders"][0]?>" style="background-color: <?= e($btnBgColor) ?>; color: <?= e($btnTextColor) ?>;"><?= e($buttonText) ?></p>

    <div class="hidden">
      <div class="flex-column center-center w100 wrap">
        <div class="wpx520 w-sml-100 back-modal-item br-desk-15 br-mid-15 br-sml-0 p20 text-menu-modal text-protected h-dvh-sml overflow-y-scroll">
          <p class="absolute top right mt10 mr10 pointer modal-close-button z-index-20"><?= svg("xmark")?></p>
          <br>
          <div class="flex-column top-start gap10">
            <p>Ingresa tus datos para mantener el contacto</p>
            <!-- Formulario de Suscripción (Boceto para diseño) -->
            <form class="campaign-sub-form flex-column gap10 w100" onsubmit="event.preventDefault(); alert('¡Gracias por suscribirte!');">
              <?php if ($askName) : ?>
                <input type="text" name="subscriber_name" class="p15 br15" placeholder="Tu nombre" style="background-color: #ffffff; border: solid 1px #595a83;">
              <?php endif; ?>
        
              <input type="email" name="subscriber_email" required class="p15 br15" placeholder="Tu correo electrónico" style="background-color: #ffffff; border: solid 1px #595a83;">
        
              <?php if ($askWhatsapp) : ?>
                <input type="tel" name="subscriber_whatsapp" class="p15 br15" placeholder="Tu número de WhatsApp" style="background-color: #ffffff; border: solid 1px #595a83;">
              <?php endif; ?>
        
              <button type="submit" class="p15 br50 bold500 pointer text-center" style="background-color: <?= e($btnBgColor) ?>; color: <?= e($btnTextColor) ?>; border: none;">
                <?= e($buttonText) ?>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>

  <?php if ($hasCountdown && !empty($countdownDate)) : ?>
    <script>
      (function() {
        const wrapper = document.getElementById("<?= $campaignId ?>");
        if (!wrapper) return;
        const box = wrapper.querySelector('[data-countdown]');
        if (!box) return;
        const targetStr = box.getAttribute('data-countdown');
        if (!targetStr) return;
        const target = new Date(targetStr).getTime();
        let timer = null;

        function updateCountdown() {
          const now = new Date().getTime();
          const diff = target - now;
          if (diff <= 0) {
            if (timer) clearInterval(timer);
            wrapper.style.display = 'none';
            return;
          }
          const days = Math.floor(diff / (1000 * 60 * 60 * 24));
          const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
          const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
          const seconds = Math.floor((diff % (1000 * 60)) / 1000);

          const dEl = box.querySelector('.countdown-days');
          const hEl = box.querySelector('.countdown-hours');
          const mEl = box.querySelector('.countdown-minutes');
          const sEl = box.querySelector('.countdown-seconds');

          if (dEl) dEl.textContent = String(days).padStart(2, '0');
          if (hEl) hEl.textContent = String(hours).padStart(2, '0');
          if (mEl) mEl.textContent = String(minutes).padStart(2, '0');
          if (sEl) sEl.textContent = String(seconds).padStart(2, '0');
        }

        updateCountdown();
        timer = setInterval(updateCountdown, 1000);
      })();
    </script>
  <?php endif; ?>

</div>
