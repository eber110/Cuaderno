<?php
  /** @var mixed $card */
  $styleBack = $card["backCard"]["style_back"] ?? "solid";
  $backVideo = $card["backCard"]["back_video"] ?? "";
  $previewBtnBack      = safeCssColor($card["back"] ?? "#d6d6d6", "#d6d6d6");
  $previewBtnColor     = safeCssColor($card["color"] ?? "#494949", "#494949");
  $previewTxtColor     = safeCssColor($card["colorText"] ?? "#383838", "#383838");
  $previewTtlColor     = safeCssColor($card["titleColor"] ?? "#383838", "#383838");
  $previewShadow3Color = safeCssColor($card["colorShadow3"] ?? "#000000", "#000000");
  $previewHover        = ($card["hover"] ?? false) === true || ($card["hover"] ?? false) === "true" || ($card["hover"] ?? false) === 1 || ($card["hover"] ?? false) === "1";
  $voidSpace           = (int)($card["voidHero"]["space"] ?? ($card["void_space"] ?? 70));
  if ($voidSpace === 130) $voidSpace = 20;
  elseif ($voidSpace === 250) $voidSpace = 45;
  elseif ($voidSpace === 450) $voidSpace = 70;
?>
<div class="wpx460 w-sml-100 h-dvh pt40 pb40 p-sml-0 flex-column center-center user-profile-preview <?= $previewHover ? 'has-btn-hover' : '' ?>"
     style="--preview-btn-back: <?= $previewBtnBack ?>; --preview-btn-color: <?= $previewBtnColor ?>; --preview-txt-color: <?= $previewTxtColor ?>; --preview-title-color: <?= $previewTtlColor ?>; --preview-shadow-color: <?= $previewShadow3Color ?>; --preview-void-space: <?= $voidSpace ?>%;">
  <?php _part("User.style.css", ["card" => $card]); ?>
  <style>
    /* Variables reactivas del preview sin colisiones de especificidad */
    .user-profile-preview .theme-button {
      background-color: var(--preview-btn-back);
      color: var(--preview-btn-color);
    }
    .user-profile-preview.has-btn-hover .theme-button:hover {
      background-color: oklch(from var(--preview-btn-back) calc(l * 0.92) c h);
    }
    .user-profile-preview .theme-button,
    .user-profile-preview .theme-button a,
    .user-profile-preview .theme-button p:not(.text-success),
    .user-profile-preview .theme-button span:not(.theme-icon),
    .user-profile-preview .theme-button .cut-phrase {
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
  </style>
  <div class="flex-column between-center back-card shadow-card-preview color-text-card preview-profile p0 br30 br-sml-0 w100 overflow-hidden position-relative">
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
            });
          } catch (e) {}
        })();
      </script>
    </div>

  </div>
</div>
