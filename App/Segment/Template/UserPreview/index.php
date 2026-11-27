<?php
  /** @var mixed $card */
  $styleBack = $card["backCard"]["style_back"] ?? "solid";
  $backVideo = $card["backCard"]["back_video"] ?? "";
  $previewBtnBack      = safeCssColor($card["back"] ?? "#d6d6d6", "#d6d6d6");
  $previewBackPerfil   = safeCssColor($card["backCard"]["back_perfil"] ?? "#272727", "#272727");
  $previewBtnColor     = safeCssColor($card["color"] ?? "#494949", "#494949");
  $previewTxtColor     = safeCssColor($card["colorText"] ?? "#383838", "#383838");
  $previewTtlColor     = safeCssColor($card["titleColor"] ?? "#383838", "#383838");
  $previewShadow3Color = safeCssColor($card["colorShadow3"] ?? "#000000", "#000000");
  $previewHover        = ($card["hover"] ?? false) === true || ($card["hover"] ?? false) === "true" || ($card["hover"] ?? false) === 1 || ($card["hover"] ?? false) === "1";
  $voidSpace           = (int)($card["voidHero"]["space"] ?? ($card["void_space"] ?? 70));
  if ($voidSpace === 130) $voidSpace = 20;
  elseif ($voidSpace === 250) $voidSpace = 45;
  elseif ($voidSpace === 450) $voidSpace = 70;
  $previewFontTitle    = \App\Models\DesignModels::resolveFont((string)($card["font_title"] ?? ""));
  $previewFontText     = \App\Models\DesignModels::resolveFont((string)($card["font_text"] ?? ""));
  $previewFontButton   = \App\Models\DesignModels::resolveFont((string)($card["font_button"] ?? ""));
  $previewStyle        = $card["style"] ?? "buttonRegular";
  $previewStyleClass   = match ($previewStyle) {
    "buttonClay"  => "btn-style-clay",
    "buttonGlass" => "btn-style-glass",
    default       => ""
  };
?>
<div class="wpx460 w-sml-100 h-dvh pt40 pb40 p-sml-0 flex-column center-center user-profile-preview <?= $previewHover ? 'has-btn-hover' : '' ?> <?= $previewStyleClass ?>"
     style="--preview-btn-back: <?= $previewBtnBack ?>; --preview-back-perfil: <?= $previewBackPerfil ?>; --preview-btn-color: <?= $previewBtnColor ?>; --preview-txt-color: <?= $previewTxtColor ?>; --preview-title-color: <?= $previewTtlColor ?>; --preview-shadow-color: <?= $previewShadow3Color ?>; --preview-void-space: <?= $voidSpace ?>%; --preview-font-title: <?= e($previewFontTitle['cssFamily']) ?>; --preview-font-title-weight: <?= (int)$previewFontTitle['weight'] ?>; --preview-font-text: <?= e($previewFontText['cssFamily']) ?>; --preview-font-text-weight: <?= (int)$previewFontText['weight'] ?>; --preview-font-button: <?= e($previewFontButton['cssFamily']) ?>; --preview-font-button-weight: <?= (int)$previewFontButton['weight'] ?>;">
  <?php _part("User.style.css", ["card" => $card]); ?>
  <style>
    /* Variables reactivas del preview sin colisiones de especificidad */
    .user-profile-preview .theme-button {
      background-color: var(--preview-btn-back);
      color: var(--preview-btn-color);
      transition: background-color 200ms ease, box-shadow 200ms ease;
    }
    .user-profile-preview.has-btn-hover .theme-button:hover {
      background-color: oklch(from var(--preview-btn-back) calc(l * 0.92) c h);
    }
    .user-profile-preview .theme-button,
    .user-profile-preview .theme-button a,
    .user-profile-preview .theme-button p:not(.text-success),
    .user-profile-preview .theme-button span:not(.theme-icon),
    .user-profile-preview .theme-button .cut-phrase,
    .user-profile-preview .theme-button .profile-line-clamp {
      color: var(--preview-btn-color);
    }
    .user-profile-preview .theme-icon,
    .user-profile-preview .theme-button-menu svg {
      color: var(--preview-btn-color);
    }
    .user-profile-preview .color-text-card,
    .user-profile-preview .desc-hero-regular,
    .user-profile-preview .desc-hero-big,
    .user-profile-preview .desc-hero-mini,
    .user-profile-preview .color-text-card a.color-text-card {
      color: var(--preview-txt-color);
    }
    .user-profile-preview .title-color {
      color: var(--preview-title-color);
    }
    .user-profile-preview .shadow-3 {
      box-sizing: content-box;
      border: solid 2px var(--preview-shadow-color);
      box-shadow: 3px 5px 0px var(--preview-shadow-color);
    }
    .user-profile-preview .shadow-3 img {
      border: solid 2px var(--preview-shadow-color);
    }
    .user-profile-preview .void-space {
      padding-top: var(--preview-void-space) !important;
    }
    .user-profile-preview .back-card,
    .user-profile-preview .back-card .color-text-card {
      font-family: var(--preview-font-text);
    }
    .user-profile-preview .back-card p[data-profile-bind="desc"] {
      font-family: var(--preview-font-text);
      font-weight: var(--preview-font-text-weight);
    }
    .user-profile-preview .back-card .theme-button,
    .user-profile-preview .back-card .theme-button a,
    .user-profile-preview .back-card .theme-button p,
    .user-profile-preview .back-card .theme-button span,
    .user-profile-preview .back-card .campaign-button {
      font-family: var(--preview-font-button);
      font-weight: var(--preview-font-button-weight);
    }
    .user-profile-preview .back-card .title-color {
      font-family: var(--preview-font-title);
    }
    .user-profile-preview .back-card h1[data-profile-bind="title"] {
      font-family: var(--preview-font-title);
      font-weight: var(--preview-font-title-weight);
    }
    /* Estilo Claymorphism 3D en vista previa */
    .user-profile-preview .back-card.btn-style-clay .theme-button,
    .user-profile-preview .back-card.btn-style-clay .campaign-button {
      --clay-bg: var(--preview-btn-back);
      background-color: var(--clay-bg) !important;
      background-image: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.14) 0%,
        rgba(255, 255, 255, 0) 52%,
        rgba(0, 0, 0, 0.10) 100%
      );
      border: 1px solid oklch(from var(--clay-bg) calc(l + 0.10) calc(c * 0.60) h / 0.45);
      box-sizing: border-box;
      box-shadow:
        0 10px 22px -6px oklch(from var(--clay-bg) calc(l * 0.35) calc(c * 0.55) h / 0.34),
        0 4px 10px -2px rgba(0, 0, 0, 0.14),
        inset 0 4px 7px oklch(from var(--clay-bg) calc(l + 0.18) calc(c * 0.50) h / 0.72),
        inset 0 -6px 10px oklch(from var(--clay-bg) calc(l * 0.62) calc(c * 1.05) h / 0.45);
      transition: background-color 200ms ease, box-shadow 200ms ease;
    }
    .user-profile-preview .back-card.btn-style-clay .theme-button.br0,
    .user-profile-preview .back-card.btn-style-clay .campaign-button.br0 {
      border-radius: 10px !important;
    }
    .user-profile-preview .back-card.btn-style-clay .theme-button.shadow-3 img {
      border: none;
    }
    .user-profile-preview .back-card.btn-style-clay .theme-button,
    .user-profile-preview .back-card.btn-style-clay .theme-button a,
    .user-profile-preview .back-card.btn-style-clay .theme-button p,
    .user-profile-preview .back-card.btn-style-clay .theme-button span,
    .user-profile-preview .back-card.btn-style-clay .campaign-button {
      font-family: 'GoogleSansFlex_120pt', sans-serif;
      font-weight: 500;
    }
    .user-profile-preview.has-btn-hover .back-card.btn-style-clay .theme-button:hover,
    .user-profile-preview.has-btn-hover .back-card.btn-style-clay .campaign-button:hover {
      background-color: oklch(from var(--clay-bg) calc(l + 0.05) calc(c * 0.95) h) !important;
      box-shadow:
        0 14px 26px -6px oklch(from var(--clay-bg) calc(l * 0.35) calc(c * 0.55) h / 0.42),
        0 6px 12px -2px rgba(0, 0, 0, 0.16),
        inset 0 5px 8px oklch(from var(--clay-bg) calc(l + 0.20) calc(c * 0.50) h / 0.78),
        inset 0 -6px 10px oklch(from var(--clay-bg) calc(l * 0.62) calc(c * 1.05) h / 0.45);
    }
    .user-profile-preview:not(.has-btn-hover) .back-card.btn-style-clay .theme-button:hover,
    .user-profile-preview:not(.has-btn-hover) .back-card.btn-style-clay .campaign-button:hover {
      background-color: var(--clay-bg) !important;
      box-shadow:
        0 10px 22px -6px oklch(from var(--clay-bg) calc(l * 0.35) calc(c * 0.55) h / 0.34),
        0 4px 10px -2px rgba(0, 0, 0, 0.14),
        inset 0 4px 7px oklch(from var(--clay-bg) calc(l + 0.18) calc(c * 0.50) h / 0.72),
        inset 0 -6px 10px oklch(from var(--clay-bg) calc(l * 0.62) calc(c * 1.05) h / 0.45);
    }
    /* Estilo Liquid Glass / Cristal en vista previa */
    .user-profile-preview .back-card.btn-style-glass .theme-button,
    .user-profile-preview .back-card.btn-style-glass .campaign-button {
      --glass-bg: var(--preview-back-perfil);
      position: relative;
      background-color: rgba(255, 255, 255, 0) !important;
      background-image: linear-gradient(
        165deg,
        rgba(255, 255, 255, 0.14) 0%,
        rgba(255, 255, 255, 0.03) 40%,
        rgba(255, 255, 255, 0.01) 68%,
        rgba(255, 255, 255, 0.09) 100%
      );
      backdrop-filter: blur(6px) saturate(160%);
      -webkit-backdrop-filter: blur(6px) saturate(160%);
      border: none;
      box-sizing: border-box;
      box-shadow:
        0 16px 32px -8px rgba(0, 0, 0, 0.30),
        0 6px 12px -4px rgba(0, 0, 0, 0.18),
        0 10px 24px -4px oklch(from var(--glass-bg) calc(l + 0.22) calc(c * 1.35) h / 0.42),
        0 0 1px 0 rgba(255, 255, 255, 0.22),
        inset 0 1px 1px rgba(255, 255, 255, 0.52),
        inset 0 -1px 1px rgba(255, 255, 255, 0.24),
        inset 0 -8px 18px -10px oklch(from var(--glass-bg) calc(l + 0.28) calc(c * 1.25) h / 0.32),
        inset 0 8px 16px -12px rgba(255, 255, 255, 0.20);
      transition: background-color 200ms ease, box-shadow 200ms ease;
    }
    .user-profile-preview .back-card.btn-style-glass .theme-button::before,
    .user-profile-preview .back-card.btn-style-glass .campaign-button::before {
      content: "";
      position: absolute;
      inset: 0;
      border-radius: inherit;
      padding: 1.5px;
      background:
        radial-gradient(circle 48px at 10% 0%, rgba(255, 255, 255, 0.98) 0%, rgba(255, 255, 255, 0.45) 38%, transparent 72%),
        radial-gradient(circle 54px at 92% 100%, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0.50) 36%, transparent 72%),
        linear-gradient(
          145deg,
          rgba(255, 255, 255, 0.52) 0%,
          rgba(255, 255, 255, 0.12) 45%,
          rgba(255, 255, 255, 0.08) 60%,
          rgba(255, 255, 255, 0.48) 100%
        );
      -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
      -webkit-mask-composite: xor;
      mask-composite: exclude;
      pointer-events: none;
      z-index: 2;
    }
    .user-profile-preview .back-card.btn-style-glass .theme-button.br0,
    .user-profile-preview .back-card.btn-style-glass .campaign-button.br0 {
      border-radius: 10px !important;
    }
    .user-profile-preview .back-card.btn-style-glass .theme-button.shadow-3 img {
      border: none;
    }
    .user-profile-preview .back-card.btn-style-glass .theme-button,
    .user-profile-preview .back-card.btn-style-glass .theme-button a,
    .user-profile-preview .back-card.btn-style-glass .theme-button p,
    .user-profile-preview .back-card.btn-style-glass .theme-button span,
    .user-profile-preview .back-card.btn-style-glass .campaign-button {
      font-family: 'GoogleSansFlex_120pt', sans-serif;
      font-weight: 500;
    }
    .user-profile-preview.has-btn-hover .back-card.btn-style-glass .theme-button:hover,
    .user-profile-preview.has-btn-hover .back-card.btn-style-glass .campaign-button:hover {
      background-color: rgba(255, 255, 255, 0.11) !important;
      box-shadow:
        0 18px 36px -8px rgba(0, 0, 0, 0.34),
        0 7px 14px -4px rgba(0, 0, 0, 0.20),
        0 12px 28px -4px oklch(from var(--glass-bg) calc(l + 0.26) calc(c * 1.40) h / 0.52),
        0 0 1px 0 rgba(255, 255, 255, 0.32),
        inset 0 1px 1px rgba(255, 255, 255, 0.65),
        inset 0 -1px 1px rgba(255, 255, 255, 0.34),
        inset 0 -8px 18px -10px oklch(from var(--glass-bg) calc(l + 0.30) calc(c * 1.30) h / 0.40),
        inset 0 8px 16px -12px rgba(255, 255, 255, 0.28);
    }
    .user-profile-preview:not(.has-btn-hover) .back-card.btn-style-glass .theme-button:hover,
    .user-profile-preview:not(.has-btn-hover) .back-card.btn-style-glass .campaign-button:hover {
      background-color: rgba(255, 255, 255, 0) !important;
      box-shadow:
        0 16px 32px -8px rgba(0, 0, 0, 0.30),
        0 6px 12px -4px rgba(0, 0, 0, 0.18),
        0 10px 24px -4px oklch(from var(--glass-bg) calc(l + 0.22) calc(c * 1.35) h / 0.42),
        0 0 1px 0 rgba(255, 255, 255, 0.22),
        inset 0 1px 1px rgba(255, 255, 255, 0.52),
        inset 0 -1px 1px rgba(255, 255, 255, 0.24),
        inset 0 -8px 18px -10px oklch(from var(--glass-bg) calc(l + 0.28) calc(c * 1.25) h / 0.32),
        inset 0 8px 16px -12px rgba(255, 255, 255, 0.20);
    }
  </style>
  <div class="flex-column between-center back-card shadow-card-preview color-text-card preview-profile p0 br30 br-sml-0 w100 overflow-hidden position-relative <?= $previewStyleClass ?>">
    <?php if (\App\Models\DesignModels::isVideoEnabled()) : ?>
      <video class="back-video-bg" preload="metadata" autoplay loop muted playsinline disablePictureInPicture tabindex="-1" style="<?= ($styleBack === 'video' && !empty($backVideo)) ? '' : 'display: none;' ?>" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='none';">
        <source src="<?= eUrl($backVideo) ?>" type="video/mp4" onerror="var v = this.parentElement; if(v){ v.style.display='none'; if(v.nextElementSibling) v.nextElementSibling.style.display='none'; }">
      </video>
      <div class="back-video-overlay" style="<?= ($styleBack === 'video' && !empty($backVideo)) ? '' : 'display: none;' ?>"></div>
    <?php endif; ?>

    <?php 
      $backImage = $card["backCard"]["backImageSrc"] ?? $card["backCard"]["back_image"] ?? ""; 
      if (empty($backImage)) {
        $backImage = URL_IMG . "no-image.webp";
      }
      $backImageFilter = $card["backCard"]["back_image_filter"] ?? "none";
      $numMap = ['0'=>'none', '1'=>'vignette', '2'=>'blur', '3'=>'brightness', '4'=>'contrast', '5'=>'grayscale', '6'=>'hue-rotate', '7'=>'invert', '8'=>'saturate', '9'=>'sepia'];
      if (isset($numMap[$backImageFilter])) $backImageFilter = $numMap[$backImageFilter];
    ?>
    <img src="<?= eUrl($backImage) ?>" class="back-image-bg" style="<?= ($styleBack === 'image') ? '' : 'display: none;' ?>" alt="Background" onerror="this.src='<?= eUrl(URL_IMG . 'no-image.webp') ?>';">
    <div class="back-image-vignette" style="<?= ($styleBack === 'image' && $backImageFilter === 'vignette') ? '' : 'display: none;' ?>"></div>
    <div class="back-image-overlay" style="<?= ($styleBack === 'image') ? '' : 'display: none;' ?>"></div>

    <div class="w100 h100 flex-column between-center overflow-y-scroll z-index-1" data-scroll-memory="user-preview" style="scroll-behavior: auto !important;">
      <header class="w100">
        <?php
          $currentHeader = $card["header"] ?? "regularHero";
        ?>
        <div class="header-variant-wrapper header-regularHero <?= $currentHeader === 'regularHero' ? '' : 'hidden' ?>" data-header-variant="regularHero">
          <?php _part("User.regularHero", ["card" => $card]); ?>
        </div>
        <div class="header-variant-wrapper header-voidHero <?= $currentHeader === 'voidHero' ? '' : 'hidden' ?>" data-header-variant="voidHero">
          <?php _part("User.voidHero", ["card" => $card]); ?>
        </div>
        <div class="header-variant-wrapper header-midHero <?= $currentHeader === 'midHero' ? '' : 'hidden' ?>" data-header-variant="midHero">
          <?php _part("User.midHero", ["card" => $card]); ?>
        </div>
        <?php
          _part("User.widget", ["card" => $card, "isPreview" => true]);
        ?>
      </header>

      <footer class="w100">
        <?php
          _template("Footer.footerUser")
        ?>
      </footer>

      <?php _scrollMemoryAntiFouc("user-preview"); ?>

      <!-- Hidratación rápida de variables CSS desde borrador local para evitar parpadeo visual -->
      <script>
        (function() {
          try {
            var user = <?= json_encode($card['profile'] ?? '') ?>;
            if (!user) {
              var parts = window.location.pathname.split('/').filter(Boolean);
              if (parts[0] === 'panel' && parts[1]) user = parts[1];
            }
            if (!user) return;
            var draftStr = localStorage.getItem('cuaderno_design_draft_' + user.toLowerCase());
            if (!draftStr) return;
            var draft = JSON.parse(draftStr);
            if (!draft || typeof draft !== 'object') return;
            document.querySelectorAll('.user-profile-preview').forEach(function(preview) {
              if (draft.back) preview.style.setProperty('--preview-btn-back', draft.back);
              var draftBackPerfil = (draft.backCard && draft.backCard.back_perfil) ? draft.backCard.back_perfil : draft.back_perfil;
              if (draftBackPerfil) preview.style.setProperty('--preview-back-perfil', draftBackPerfil);
              if (draft.color) preview.style.setProperty('--preview-btn-color', draft.color);
              if (draft.colorText) preview.style.setProperty('--preview-txt-color', draft.colorText);
              if (draft.titleColor) preview.style.setProperty('--preview-title-color', draft.titleColor);
              if (draft.colorShadow3) preview.style.setProperty('--preview-shadow-color', draft.colorShadow3);
              if (draft.void_space !== undefined) {
                var vs = parseInt(draft.void_space, 10);
                if (vs === 130) vs = 20;
                else if (vs === 250) vs = 45;
                else if (vs === 450) vs = 70;
                if (!isNaN(vs)) preview.style.setProperty('--preview-void-space', vs + '%');
              }
              var fontsMap = window.AVAILABLE_PROFILE_FONTS || {};
              if (draft.font_title && fontsMap[draft.font_title]) {
                preview.style.setProperty('--preview-font-title', fontsMap[draft.font_title].cssFamily);
                preview.style.setProperty('--preview-font-title-weight', String(fontsMap[draft.font_title].weight));
              }
              if (draft.font_text && fontsMap[draft.font_text]) {
                preview.style.setProperty('--preview-font-text', fontsMap[draft.font_text].cssFamily);
                preview.style.setProperty('--preview-font-text-weight', String(fontsMap[draft.font_text].weight));
              }
              if (draft.font_button && fontsMap[draft.font_button]) {
                preview.style.setProperty('--preview-font-button', fontsMap[draft.font_button].cssFamily);
                preview.style.setProperty('--preview-font-button-weight', String(fontsMap[draft.font_button].weight));
              }
              if (draft.style) {
                var isClay = draft.style === 'buttonClay';
                var isGlass = draft.style === 'buttonGlass';
                preview.classList.toggle('btn-style-clay', isClay);
                preview.classList.toggle('btn-style-glass', isGlass);
                var bc = preview.querySelector('.back-card');
                if (bc) {
                  bc.classList.toggle('btn-style-clay', isClay);
                  bc.classList.toggle('btn-style-glass', isGlass);
                }
              }
              if (draft.hover !== undefined) {
                var isHover = (draft.hover === true || draft.hover === 'true' || draft.hover === 1 || draft.hover === '1');
                preview.classList.toggle('has-btn-hover', isHover);
              }
            });
          } catch (e) {}
        })();
      </script>
    </div>

  </div>
</div>
