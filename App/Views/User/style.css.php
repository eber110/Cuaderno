<?php
  /** @var mixed $card */


  $back             = safeCssColor($card["back"] ?? "#d6d6d6", "#d6d6d6");
  $color            = safeCssColor($card["color"] ?? "#494949", "#494949");
  $hover            = ($card["hover"] ?? false) === true || ($card["hover"] ?? false) === 'true' || ($card["hover"] ?? false) === 1 || ($card["hover"] ?? false) === '1';
  $backPerfil       = safeCssColor($card["backCard"]["back_perfil"] ?? "#a0a0a0", "#a0a0a0");
  $styleBack        = in_array($card["backCard"]["style_back"] ?? "solid", ["solid", "gradientUp", "gradientDown", "video", "image"], true) ? ($card["backCard"]["style_back"] ?? "solid") : "solid";
  $colorShadow3     = safeCssColor($card["colorShadow3"] ?? "#000000", "#000000");
  $colorText        = safeCssColor($card["colorText"] ?? "#383838", "#383838");
  $titleColor       = safeCssColor($card["titleColor"] ?? "#383838", "#383838");
  $backVideo        = $card["backCard"]["back_video"] ?? "";
  $backVideoOverlay = safeCssColor($card["backCard"]["back_video_overlay"] ?? "#000000", "#000000");
  $backVideoOpacity = max(0, min(95, intval($card["backCard"]["back_video_opacity"] ?? 45)));
  $backImage        = $card["backCard"]["backImageSrc"] ?? $card["backCard"]["back_image"] ?? "";
  $backImageOverlay = safeCssColor($card["backCard"]["back_image_overlay"] ?? "#000000", "#000000");
  $backImageOpacity = max(0, min(95, intval($card["backCard"]["back_image_opacity"] ?? 45)));
  $rawFilter        = $card["backCard"]["back_image_filter"] ?? 'none';
  $numMap           = ['0'=>'none', '1'=>'vignette', '2'=>'blur', '3'=>'brightness', '4'=>'contrast', '5'=>'grayscale', '6'=>'hue-rotate', '7'=>'invert', '8'=>'saturate', '9'=>'sepia'];
  if (isset($numMap[$rawFilter])) $rawFilter = $numMap[$rawFilter];
  $validFilters     = ['none', 'vignette', 'blur', 'brightness', 'contrast', 'grayscale', 'hue-rotate', 'invert', 'saturate', 'sepia'];
  $backImageFilter  = in_array($rawFilter, $validFilters, true) ? $rawFilter : 'none';
  $backImageFilterIntensity = max(0, min(100, intval($card["backCard"]["back_image_filter_intensity"] ?? 80)));
  $voidSpace        = (int)($card["voidHero"]["space"] ?? ($card["void_space"] ?? 70));
  if ($voidSpace == 130) $voidSpace = 20;
  elseif ($voidSpace == 250) $voidSpace = 45;
  elseif ($voidSpace == 450) $voidSpace = 70;
  $fontTitle        = \App\Models\DesignModels::resolveFont((string)($card["font_title"] ?? ""));
  $fontText         = \App\Models\DesignModels::resolveFont((string)($card["font_text"] ?? ""));
  $fontButton       = \App\Models\DesignModels::resolveFont((string)($card["font_button"] ?? ""));
?>

<style>
  .theme-button{
    background-color: <?= $back?>;
    color: <?= $color?>;
    transition: background-color 200ms ease, box-shadow 200ms ease;
  }
  <?php if ($hover) echo ".theme-button:hover{background-color: oklch(from ".$back." calc(l * 0.92) c h);}"?>

  .theme-button-menu{
    background-color: <?= $back?>00;
  }
  .theme-button-menu:hover{background-color: oklch(from <?= $back?> calc(l * 1.04) c h);}

  .w-theme-center{
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
  }

  .theme-icon{
    color: <?= $color?>;
  }

  <?php
    [$gradStart, $gradEnd]                   = \App\Models\DesignModels::getGradientColors($backPerfil, "card");
    [$gradStartContainer, $gradEndContainer] = \App\Models\DesignModels::getGradientColors($backPerfil, "container");
    $containerSolid                          = \App\Models\DesignModels::getContainerSolidColor($backPerfil);
    $gradStart                               = safeCssColor($gradStart, $backPerfil);
    $gradEnd                                 = safeCssColor($gradEnd, $backPerfil);
    $gradStartContainer                      = safeCssColor($gradStartContainer, $backPerfil);
    $gradEndContainer                        = safeCssColor($gradEndContainer, $backPerfil);
    $containerSolid                          = safeCssColor($containerSolid, $backPerfil);
    $dotPatternColor                         = \App\Models\DesignModels::getDotPatternColor($backPerfil);
  ?>
  <?php if ($styleBack == "solid") :?>
    .back-card{
      background-color: <?= $backPerfil?>;
    }
    .back-card-container{
      background-color: <?= $containerSolid ?>;
      --dot-pattern-color: <?= $dotPatternColor ?>;
    }
  <?php elseif ($styleBack == "gradientUp") :?>
    .back-card{
      background: linear-gradient(0deg, <?= $gradStart ?>, <?= $gradEnd ?>);
    }
    .back-card-container{
      background: linear-gradient(0deg, <?= $gradStartContainer ?>, <?= $gradEndContainer ?>);
      --dot-pattern-color: <?= $dotPatternColor ?>;
    }
  <?php elseif ($styleBack == "gradientDown") :?>
    .back-card{
      background: linear-gradient(180deg, <?= $gradStart ?>, <?= $gradEnd ?>);
    }
    .back-card-container{
      background: linear-gradient(180deg, <?= $gradStartContainer ?>, <?= $gradEndContainer ?>);
      --dot-pattern-color: <?= $dotPatternColor ?>;
    }
  <?php elseif (\App\Models\DesignModels::isVideoEnabled() && $styleBack == "video" && !empty($backVideo)) :?>
    .back-card{
      background-color: <?= $backPerfil?>;
      position: relative;
    }
    .back-card-container{
      background-color: <?= $containerSolid ?>;
      --dot-pattern-color: <?= $dotPatternColor ?>;
    }
  <?php elseif ($styleBack == "image" && !empty($backImage)) :?>
    .back-card{
      background-color: <?= $backPerfil?>;
      position: relative;
    }
    .back-card-container{
      background-color: <?= $containerSolid ?>;
      --dot-pattern-color: <?= $dotPatternColor ?>;
    }
  <?php else :?>
    .back-card{
      background-color: <?= $backPerfil?>;
    }
    .back-card-container{
      background-color: <?= $containerSolid ?>;
      --dot-pattern-color: <?= $dotPatternColor ?>;
    }
  <?php endif?>

  .back-video-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
    pointer-events: none;
  }
  
  .back-video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: <?= $backVideoOverlay ?>;
    opacity: <?= ($backVideoOpacity / 100) ?>;
    z-index: 0;
    pointer-events: none;
  }

  .back-image-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
    pointer-events: none;
    <?php
      $filterCss = 'none';
      $scaleCss = '';
      $ratio = $backImageFilterIntensity / 100;
      if ($backImageFilter === 'blur') {
        $blurPx = round($ratio * 15, 1);
        $filterCss = "blur({$blurPx}px)";
        $scaleCss = "transform: scale(1.06);";
      } elseif ($backImageFilter === 'brightness') {
        $val = round($ratio * 2, 2);
        $filterCss = "brightness({$val})";
      } elseif ($backImageFilter === 'contrast') {
        $val = round($ratio * 2, 2);
        $filterCss = "contrast({$val})";
      } elseif ($backImageFilter === 'grayscale') {
        $filterCss = "grayscale({$backImageFilterIntensity}%)";
      } elseif ($backImageFilter === 'hue-rotate') {
        $deg = round($ratio * 360);
        $filterCss = "hue-rotate({$deg}deg)";
      } elseif ($backImageFilter === 'invert') {
        $filterCss = "invert({$backImageFilterIntensity}%)";
      } elseif ($backImageFilter === 'saturate') {
        $val = round($ratio * 3, 2);
        $filterCss = "saturate({$val})";
      } elseif ($backImageFilter === 'sepia') {
        $filterCss = "sepia({$backImageFilterIntensity}%)";
      }
    ?>
    filter: <?= $filterCss ?>;
    <?= $scaleCss ?>
  }
  
  .back-image-vignette {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    pointer-events: none;
    background: radial-gradient(ellipse at center, transparent 30%, #000000 100%);
    opacity: <?= ($backImageFilterIntensity / 100) ?>;
  }

  .back-image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    pointer-events: none;
    background-color: <?= $backImageOverlay ?>;
    opacity: <?= ($backImageOpacity / 100) ?>;
  }

  .z-index-1 {
    position: relative;
    z-index: 1;
  }
  .position-relative {
    position: relative;
  }
  .overflow-hidden {
    overflow: hidden;
  }

  .shadow-3{
    box-sizing: content-box;
    border: solid 2px <?= $colorShadow3?>;
    box-shadow: 3px 5px 0px <?= $colorShadow3?>;
  }
  .shadow-3 img {
    border: solid 2px <?= $colorShadow3?>;
  }

  .color-menu-user, .color-menu-user * {
    color: #ffffff !important;
  }

  .color-text-card{
    color: <?= (!$colorText || $colorText === "") ? $color : $colorText?>;
  }

  .menu-user-fixed {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 20;
    pointer-events: none;
  }

  .back-item-menu{
    background-color: <?= "oklch(from {$backPerfil} calc(l * 0.40) calc(c - 0.09) h / 90%)" ?>;
    padding: 8px 8px;
    border-radius: 15px;
    border-style: solid;
    border-color: #ffffff98;
    border-width: 1px;
    pointer-events: auto;
  }

  .title-color{
    color: <?= $titleColor?>;
    font-family: <?= $fontTitle["cssFamily"] ?>;
  }

  .back-card,
  .back-card .color-text-card {
    font-family: <?= $fontText["cssFamily"] ?>;
  }

  .back-card .theme-button,
  .back-card .theme-button a,
  .back-card .theme-button p,
  .back-card .theme-button span,
  .back-card .campaign-button {
    font-family: <?= $fontButton["cssFamily"] ?>;
    font-weight: <?= (int)$fontButton["weight"] ?>;
  }

  /* Estilo Claymorphism 3D suave (buttonClay) */
  .back-card.btn-style-clay .theme-button,
  .back-card.btn-style-clay .campaign-button {
    --clay-bg: <?= $back ?>;
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

  .back-card.btn-style-clay .theme-button.br0,
  .back-card.btn-style-clay .campaign-button.br0 {
    border-radius: 10px !important;
  }

  .back-card.btn-style-clay .theme-button.shadow-3 img {
    border: none;
  }

  .back-card.btn-style-clay .theme-button,
  .back-card.btn-style-clay .theme-button a,
  .back-card.btn-style-clay .theme-button p,
  .back-card.btn-style-clay .theme-button span,
  .back-card.btn-style-clay .campaign-button {
    font-family: 'GoogleSansFlex_120pt', sans-serif;
    font-weight: 500;
  }

  <?php if ($hover) : ?>
  .back-card.btn-style-clay .theme-button:hover,
  .back-card.btn-style-clay .campaign-button:hover {
    background-color: oklch(from var(--clay-bg) calc(l + 0.05) calc(c * 0.95) h) !important;
    box-shadow:
      0 14px 26px -6px oklch(from var(--clay-bg) calc(l * 0.35) calc(c * 0.55) h / 0.42),
      0 6px 12px -2px rgba(0, 0, 0, 0.16),
      inset 0 5px 8px oklch(from var(--clay-bg) calc(l + 0.20) calc(c * 0.50) h / 0.78),
      inset 0 -6px 10px oklch(from var(--clay-bg) calc(l * 0.62) calc(c * 1.05) h / 0.45);
  }
  <?php endif; ?>

  /* Estilo Liquid Glass / Cristal (buttonGlass) */
  .back-card.btn-style-glass .theme-button,
  .back-card.btn-style-glass .campaign-button {
    --glass-bg: <?= $backPerfil ?>;
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

  .back-card.btn-style-glass .theme-button::before,
  .back-card.btn-style-glass .campaign-button::before {
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

  .back-card.btn-style-glass .theme-button.br0,
  .back-card.btn-style-glass .campaign-button.br0 {
    border-radius: 10px !important;
  }

  .back-card.btn-style-glass .theme-button.shadow-3 img {
    border: none;
  }

  .back-card.btn-style-glass .theme-button,
  .back-card.btn-style-glass .theme-button a,
  .back-card.btn-style-glass .theme-button p,
  .back-card.btn-style-glass .theme-button span,
  .back-card.btn-style-glass .campaign-button {
    font-family: 'GoogleSansFlex_120pt', sans-serif;
    font-weight: 500;
  }

  <?php if ($hover) : ?>
  .back-card.btn-style-glass .theme-button:hover,
  .back-card.btn-style-glass .campaign-button:hover {
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
  <?php endif; ?>

  .back-card p[data-profile-bind="desc"] {
    font-family: <?= $fontText["cssFamily"] ?>;
    font-weight: <?= (int)$fontText["weight"] ?>;
  }

  .back-card h1[data-profile-bind="title"] {
    font-family: <?= $fontTitle["cssFamily"] ?>;
    font-weight: <?= (int)$fontTitle["weight"] ?>;
  }

  :root{
    --live-view: #02b629;
  }
  
  .back-live-view{
    background-color: #ffffff;
    border: solid 0.5px #f0f0f0;
  }

  .color-live-view{
    color: var(--live-view);
  }

  .live-dot-pulse {
    width: 8px;
    height: 8px;
    background-color: var(--live-view);
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 0 oklch(from var(--live-view) calc(l * 0.70) c h / 100%);
    animation: livePulse 1.8s infinite;
  }

  .void-space{
    padding-top: var(--preview-void-space, <?= $voidSpace."%" ?>) !important;
  }

  @keyframes livePulse {
    0% {
      transform: scale(0.95);
      box-shadow: 0 0 0 0 oklch(from var(--live-view) calc(l * 0.98) c h / 90%);
    }
    70% {
      transform: scale(1);
      box-shadow: 0 0 0 6px oklch(from var(--live-view) calc(l * 0.40) c h / 0%);
    }
    100% {
      transform: scale(0.95);
      box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
    }
  }

  /* Evitar que las palabras se partan a la mitad en todos los bloques del perfil y preview */
  .back-card,
  .user-profile-preview,
  .back-card-container,
  .product-item-wrapper,
  .product-grid-card,
  .product-slide-card,
  .link-item-wrapper,
  .campaign-block-wrapper,
  .text-block-wrapper,
  .title-block-wrapper,
  .banner-block-wrapper,
  .video-block-wrapper,
  .header-variant-wrapper,
  .cut-phrase,
  .cut-phrase-wrapper,
  .profile-line-clamp {
    word-break: normal !important;
    overflow-wrap: break-word !important;
    hyphens: none !important;
  }

  .video-block-wrapper {
    transition: background-color 200ms ease, box-shadow 200ms ease;
  }
  .video-block-wrapper:hover .video-play-button {
    transform: scale(1.1);
    background-color: rgba(0, 0, 0, 0.85) !important;
  }

  .cut-phrase-wrapper {
    word-break: normal !important;
    overflow-wrap: break-word !important;
    hyphens: none !important;
  }

</style>