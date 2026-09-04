<?php
  /** 
   * @var mixed $card 
   * @var int $dataContent
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
  $profile         = $card["profile"] ?? "";

  $rawImgShow = $campaignData["imgShow"] ?? true;
  $imgShow    = ($rawImgShow === true || $rawImgShow === 'true' || $rawImgShow === 1 || $rawImgShow === '1');
  $hasImg     = !empty($imgSrc) && strpos($imgSrc, 'no-image.webp') === false;

  $borderCard = ($card["borders"][0] == "br50") ? "br20" : ($card["borders"][0] ?? "br15");
  $shadowCard = $card["shadow"] ?? "shadow-card";
  $isBgMode   = ($imgPosition === "background" && $imgShow && $hasImg);
  $campaignId = "campaign-block-" . $dataContent;
?>

<div id="<?= $campaignId ?>" class="campaign-block-wrapper theme-button w100 flex-column overflow-hidden position-relative <?= $borderCard ?> <?= $shadowCard ?>" style="<?= $isBgMode ? 'color: #ffffff;' : '' ?>">

  <?php if ($isBgMode) : ?>
    <!-- Imagen de fondo y capa de opacidad/color -->
    <div class="campaign-bg-layer" style="position: absolute; inset: 0; width: 100%; height: 100%; z-index: 0; overflow: hidden; pointer-events: none;">
      <img src="<?= e($imgSrc) ?>" alt="<?= e($title) ?>" class="cover w100 h100" style="object-fit: cover;">
      <div class="campaign-bg-overlay" style="position: absolute; inset: 0; width: 100%; height: 100%; background-color: oklch(from <?= e($bgColor) ?> l c h / <?= $bgOpacity ?>%);"></div>
    </div>
  <?php elseif ($imgPosition === "header" && $imgShow && $hasImg) : ?>
    <!-- Imagen destacada de cabecera -->
    <figure class="w100 hpx150 overflow-hidden" style="margin: 0;">
      <img src="<?= e($imgSrc) ?>" alt="<?= e($title) ?>" class="cover w100 h100" style="object-fit: cover;">
    </figure>
  <?php endif; ?>

  <!-- Contenido de la campaña -->
  <div class="campaign-content flex-column gap15 p20 w100 position-relative z-index-1" style="box-sizing: border-box;">
    
    <!-- Título y Descripción -->
    <div class="flex-column gap8 text-center w100">
      <?php if (!empty($title)) : ?>
        <h3 class="bold700 x18 <?= $isBgMode ? 'textw' : 'title-color' ?>"><?= e($title) ?></h3>
      <?php endif; ?>

      <?php if (!empty($desc)) : ?>
        <p class="x14 opacity-90 <?= $isBgMode ? 'textw' : 'texto' ?>" style="line-height: 1.4;"><?= nl2br(e($desc)) ?></p>
      <?php endif; ?>
    </div>

    <!-- Contador Regresivo (si está configurado) -->
    <?php if ($hasCountdown && !empty($countdownDate)) : ?>
      <div class="campaign-countdown-box flex-row center-center gap10 p10 br10 w100" data-countdown="<?= e($countdownDate) ?>" style="background: <?= $isBgMode ? 'rgba(0,0,0,0.35)' : 'rgba(150,150,150,0.1)' ?>; backdrop-filter: blur(4px);">
        <div class="flex-column center-center flex-1">
          <span class="countdown-days bold700 x18 <?= $isBgMode ? 'textw' : 'texto' ?>">00</span>
          <span class="x10 text-uppercase opacity-70 <?= $isBgMode ? 'textw' : 'texto' ?>">Días</span>
        </div>
        <span class="bold700 x16 opacity-50 <?= $isBgMode ? 'textw' : 'texto' ?>">:</span>
        <div class="flex-column center-center flex-1">
          <span class="countdown-hours bold700 x18 <?= $isBgMode ? 'textw' : 'texto' ?>">00</span>
          <span class="x10 text-uppercase opacity-70 <?= $isBgMode ? 'textw' : 'texto' ?>">Horas</span>
        </div>
        <span class="bold700 x16 opacity-50 <?= $isBgMode ? 'textw' : 'texto' ?>">:</span>
        <div class="flex-column center-center flex-1">
          <span class="countdown-minutes bold700 x18 <?= $isBgMode ? 'textw' : 'texto' ?>">00</span>
          <span class="x10 text-uppercase opacity-70 <?= $isBgMode ? 'textw' : 'texto' ?>">Min</span>
        </div>
        <span class="bold700 x16 opacity-50 <?= $isBgMode ? 'textw' : 'texto' ?>">:</span>
        <div class="flex-column center-center flex-1">
          <span class="countdown-seconds bold700 x18 <?= $isBgMode ? 'textw' : 'texto' ?>">00</span>
          <span class="x10 text-uppercase opacity-70 <?= $isBgMode ? 'textw' : 'texto' ?>">Seg</span>
        </div>
      </div>
    <?php endif; ?>

    <!-- Formulario de Suscripción (Boceto para diseño) -->
    <form class="campaign-sub-form flex-column gap10 w100" onsubmit="event.preventDefault(); alert('¡Gracias por suscribirte!');">
      <input type="text" name="subscriber_name" class="p10 br10 x13 w100" placeholder="Tu nombre" style="border: 1px solid rgba(150,150,150,0.3); background: <?= $isBgMode ? 'rgba(255,255,255,0.15)' : 'rgba(150,150,150,0.08)' ?>; color: inherit;">
      <input type="email" name="subscriber_email" required class="p10 br10 x13 w100" placeholder="Tu correo electrónico" style="border: 1px solid rgba(150,150,150,0.3); background: <?= $isBgMode ? 'rgba(255,255,255,0.15)' : 'rgba(150,150,150,0.08)' ?>; color: inherit;">
      <input type="tel" name="subscriber_whatsapp" class="p10 br10 x13 w100" placeholder="Tu número de WhatsApp (opcional)" style="border: 1px solid rgba(150,150,150,0.3); background: <?= $isBgMode ? 'rgba(255,255,255,0.15)' : 'rgba(150,150,150,0.08)' ?>; color: inherit;">
      <button type="submit" class="p12 br10 bold600 x14 pointer text-center hover-scale-soft w100" style="background: var(--button-bg, #0070f3); color: #ffffff; border: none; transition: transform 0.15s ease;">
        Suscribirme
      </button>
    </form>

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

        function updateCountdown() {
          const now = new Date().getTime();
          const diff = Math.max(0, target - now);
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
        setInterval(updateCountdown, 1000);
      })();
    </script>
  <?php endif; ?>

</div>
