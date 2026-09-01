<?php
  /** @var mixed $card */
  $back         = $card["back"] ?? "#d6d6d6";
  $color        = $card["color"] ?? "#494949";
  $hover        = $card["hover"] ?? false;
  $backPerfil   = $card["backCard"]["back_perfil"] ?? "#a0a0a0";
  $styleBack    = $card["backCard"]["style_back"] ?? "solid";
  $colorShadow3     = $card["colorShadow3"] ?? "#000000";
  $colorText        = $card["colorText"] ?? "#383838";
  $titleColor       = $card["titleColor"] ?? "#383838";
  $backVideo        = $card["backCard"]["back_video"] ?? "";
  $backVideoOverlay = $card["backCard"]["back_video_overlay"] ?? "#000000";
  $backVideoOpacity = max(0, min(95, intval($card["backCard"]["back_video_opacity"] ?? 45)));
  $voidSpace = $card["voidHero"]["space"] ?? ($card["void_space"] ?? 70);
  if ($voidSpace == 130) $voidSpace = 20;
  elseif ($voidSpace == 250) $voidSpace = 45;
  elseif ($voidSpace == 450) $voidSpace = 70;
?>

<style>
  .theme-button{
    background-color: <?= $back?>;
    color: <?= $color?>;
  }
  <?php if ($hover === true || $hover === 'true' || $hover === 1 || $hover === '1') echo ".theme-button:hover{background-color:  oklch(from ".$back." calc(l * 0.92) c h);}"?>

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

  <?php if ($styleBack == "solid") :?>
    .back-card{
      background-color: <?= $backPerfil?>;
    }
    .back-card-container{
      background-color: oklch(from <?= $backPerfil?> calc(l * 0.65) c h / 70%);
    }
  <?php elseif ($styleBack == "gradientUp") :?>
    .back-card{
      background: radial-gradient(circle at bottom,
      <?= $backPerfil?> 20%,
      oklch(from <?= $backPerfil?> calc(l * 1.4) calc(c - 0.02) calc(h - 30)) 75%,
      oklch(from <?= $backPerfil?> calc(l * 1.5) calc(c - 0.02) calc(h - 30))) 100%;
    }
    .back-card-container{
      background: linear-gradient(0deg,
        oklch(from <?= $backPerfil?> calc(l * 0.60) c h / 75%),
        oklch(from <?= $backPerfil?> calc(l * 1.35) calc(c - 0.03) calc(h - 30) / 90%)
      );
    }
  <?php elseif ($styleBack == "gradientDown") :?>
    .back-card{
      background: radial-gradient(circle at top,
      <?= $backPerfil?> 20%,
      oklch(from <?= $backPerfil?> calc(l * 1.4) calc(c - 0.02) calc(h - 30)) 75%,
      oklch(from <?= $backPerfil?> calc(l * 1.5) calc(c - 0.02) calc(h - 30))) 100%;
    }
    .back-card-container{
      background: linear-gradient(180deg,
        oklch(from <?= $backPerfil?> calc(l * 0.60) c h / 75%),
        oklch(from <?= $backPerfil?> calc(l * 1.15) calc(c - 0.03) calc(h - 30) / 90%)
      );
    }
  <?php elseif ($styleBack == "video" && !empty($backVideo)) :?>
    .back-card{
      background-color: <?= $backPerfil?>;
      position: relative;
    }
    .back-card-container{
      background-color: oklch(from <?= $backPerfil?> calc(l * 0.60) calc(c - 0.02) h / 88%);
    }
  <?php else :?>
    .back-card{
      background-color: <?= $backPerfil?>;
    }
    .back-card-container{
      background-color: oklch(from <?= $backPerfil?> calc(l * 0.60) calc(c - 0.02) h / 88%);
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
    background-color: <?= "oklch(from {$backVideoOverlay} l c h / {$backVideoOpacity}%)" ?>;
    z-index: 0;
    pointer-events: none;
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
    & img {border: solid 2px <?= $colorShadow3?>;}
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
    padding-top: <?= $voidSpace."%" ?> !important;
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

  /* Carrusel de Productos (Modo Slide) */
  .product-group-slide-container {
    position: relative;
    width: calc(100% + 60px);
    margin-left: -30px;
    margin-right: -30px;
    box-sizing: border-box;
  }

  @media screen and (max-width: 576px) {
    .product-group-slide-container {
      width: calc(100% + 40px);
      margin-left: -20px;
      margin-right: -20px;
    }
  }

  .product-group-slide-track {
    display: flex;
    flex-direction: row;
    gap: 14px;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scroll-padding-left: 30px;
    scroll-padding-right: 30px;
    scroll-behavior: smooth;
    padding: 6px 30px 14px 30px;
    scrollbar-width: none;
    -ms-overflow-style: none;
    -webkit-overflow-scrolling: touch;
    user-select: none;
    box-sizing: border-box;
  }

  @media screen and (max-width: 576px) {
    .product-group-slide-track {
      scroll-padding-left: 20px;
      scroll-padding-right: 20px;
      padding-left: 20px;
      padding-right: 20px;
      gap: 12px;
    }
  }

  .product-group-slide-track::-webkit-scrollbar {
    display: none;
  }

  .product-slide-card {
    flex: 0 0 205px;
    width: 205px;
    max-width: 205px;
    scroll-snap-align: start;
    box-sizing: border-box;
  }

  @media screen and (max-width: 576px) {
    .product-slide-card {
      flex: 0 0 175px;
      width: 175px;
      max-width: 175px;
    }
  }

  .product-slide-btn {
    position: absolute;
    top: 45%;
    transform: translateY(-50%);
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #ffffff;
    color: #222222;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    z-index: 10;
    cursor: pointer;
    transition: opacity 0.25s ease, transform 0.15s ease, background-color 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
  }

  .product-slide-btn:hover {
    background-color: #f5f5f5;
    transform: translateY(-50%) scale(1.08);
  }

  .product-slide-btn:active {
    transform: translateY(-50%) scale(0.95);
  }

  .product-slide-prev {
    left: 10px;
  }

  .product-slide-next {
    right: 10px;
  }

  .product-slide-btn.is-hidden {
    opacity: 0 !important;
    pointer-events: none !important;
    visibility: hidden !important;
  }

</style>