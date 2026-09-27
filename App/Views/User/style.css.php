<?php
  /** @var mixed $card */
  if (!function_exists('safeCssColor')) {
    function safeCssColor(mixed $val, string $default = '#000000'): string {
      $v = trim((string)$val);
      if ($v === '') {
        return $default;
      }
      if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $v)) {
        return $v;
      }
      if ($v === 'transparent' || $v === 'currentColor') {
        return $v;
      }
      if (preg_match('/^(rgba?|hsla?|oklch|oklab|color)\([0-9a-zA-Z#.,%+\-*\/ ()]+\)$/i', $v)) {
        if (stripos($v, 'url') !== false || stripos($v, 'javascript') !== false || stripos($v, 'expression') !== false || stripos($v, '@import') !== false) {
          return $default;
        }
        $depth = 0;
        $len = strlen($v);
        for ($i = 0; $i < $len; $i++) {
          if ($v[$i] === '(') {
            $depth++;
          } elseif ($v[$i] === ')') {
            $depth--;
            if ($depth < 0) {
              return $default;
            }
          }
        }
        if ($depth === 0) {
          return $v;
        }
      }
      return $default;
    }
  }

  $back             = safeCssColor($card["back"] ?? "#d6d6d6", "#d6d6d6");
  $color            = safeCssColor($card["color"] ?? "#494949", "#494949");
  $hover            = ($card["hover"] ?? false) === true || ($card["hover"] ?? false) === 'true' || ($card["hover"] ?? false) === 1 || ($card["hover"] ?? false) === '1';
  $backPerfil       = safeCssColor($card["backCard"]["back_perfil"] ?? "#a0a0a0", "#a0a0a0");
  $styleBack        = in_array($card["backCard"]["style_back"] ?? "solid", ["solid", "gradientUp", "gradientDown", "video"], true) ? ($card["backCard"]["style_back"] ?? "solid") : "solid";
  $colorShadow3     = safeCssColor($card["colorShadow3"] ?? "#000000", "#000000");
  $colorText        = safeCssColor($card["colorText"] ?? "#383838", "#383838");
  $titleColor       = safeCssColor($card["titleColor"] ?? "#383838", "#383838");
  $backVideo        = $card["backCard"]["back_video"] ?? "";
  $backVideoOverlay = safeCssColor($card["backCard"]["back_video_overlay"] ?? "#000000", "#000000");
  $backVideoOpacity = max(0, min(95, intval($card["backCard"]["back_video_opacity"] ?? 45)));
  $voidSpace        = (int)($card["voidHero"]["space"] ?? ($card["void_space"] ?? 70));
  if ($voidSpace == 130) $voidSpace = 20;
  elseif ($voidSpace == 250) $voidSpace = 45;
  elseif ($voidSpace == 450) $voidSpace = 70;
?>

<style>
  .theme-button{
    background-color: <?= $back?>;
    color: <?= $color?>;
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
  ?>
  <?php if ($styleBack == "solid") :?>
    .back-card{
      background-color: <?= $backPerfil?>;
    }
    .back-card-container{
      background-color: <?= $containerSolid ?>;
    }
  <?php elseif ($styleBack == "gradientUp") :?>
    .back-card{
      background: linear-gradient(0deg, <?= $gradStart ?>, <?= $gradEnd ?>);
    }
    .back-card-container{
      background: linear-gradient(0deg, <?= $gradStartContainer ?>, <?= $gradEndContainer ?>);
    }
  <?php elseif ($styleBack == "gradientDown") :?>
    .back-card{
      background: linear-gradient(180deg, <?= $gradStart ?>, <?= $gradEnd ?>);
    }
    .back-card-container{
      background: linear-gradient(180deg, <?= $gradStartContainer ?>, <?= $gradEndContainer ?>);
    }
  <?php elseif (\App\Models\DesignModels::isVideoEnabled() && $styleBack == "video" && !empty($backVideo)) :?>
    .back-card{
      background-color: <?= $backPerfil?>;
      position: relative;
    }
    .back-card-container{
      background-color: <?= $containerSolid ?>;
    }
  <?php else :?>
    .back-card{
      background-color: <?= $backPerfil?>;
    }
    .back-card-container{
      background-color: <?= $containerSolid ?>;
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
  .header-variant-wrapper,
  .cut-phrase,
  .cut-phrase-wrapper {
    word-break: normal !important;
    overflow-wrap: break-word !important;
    hyphens: none !important;
  }

  .cut-phrase-wrapper {
    word-break: normal !important;
    overflow-wrap: break-word !important;
    hyphens: none !important;
  }

</style>