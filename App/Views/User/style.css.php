<?php
  /** @var mixed $card */
  $back         = $card["back"] ?? "#d6d6d6";
  $color        = $card["color"] ?? "#494949";
  $hover        = $card["hover"] ?? false;
  $backPerfil   = $card["backCard"]["back_perfil"] ?? "#a0a0a0";
  $styleBack    = $card["backCard"]["style_back"] ?? "solid";
  $colorShadow3 = $card["colorShadow3"] ?? "#000000";
  $colorText    = $card["colorText"] ?? "#383838";
  $titleColor   = $card["titleColor"] ?? "#383838";
?>

<style>
  .theme-button{
    background-color: <?= $back?>;
    color: <?= $color?>;
    <?php if ($hover === true || $hover === 'true' || $hover === 1 || $hover === '1') echo "&:hover{background-color:  oklch(from ".$back." calc(l * 0.92) c h);}"?>
  }

  .theme-button-menu{
    background-color: <?= $back?>00;
    &:hover{background-color: oklch(from <?= $back?> calc(l * 1.04) c h);}
  }

  .w-theme-center{
    width: calc(100% - 115px);
  }

  
  <?php if ($styleBack == "solid") :?>
    .back-card{
      background-color: <?= $backPerfil?>;
    }
    .back-card-container{
      background-color: oklch(from <?= $backPerfil?> calc(l * 0.60) calc(c - 0.02) h / 88%);
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
        oklch(from <?= $backPerfil?> calc(l * 0.60) calc(c - 0.01) h / 88%),
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
        oklch(from <?= $backPerfil?> calc(l * 0.60) calc(c - 0.01) h / 88%),
        oklch(from <?= $backPerfil?> calc(l * 1.15) calc(c - 0.03) calc(h - 30) / 90%)
      );
    }
  <?php endif?>

  .shadow-3{
    box-sizing: content-box;
    border: solid 2px <?= $colorShadow3?>;
    box-shadow: 3px 5px 0px <?= $colorShadow3?>;
    & img {border: solid 2px <?= $colorShadow3?>;}
  }

  .color-text-card{
    color: <?= (!$colorText || $colorText === "") ? $color : $colorText?>;
  }

  .back-item-menu{
    background-color: oklch(from <?= $backPerfil?> calc(l * 0.80) calc(c - 0.09) h / 90%);
    padding: 8px 8px;
    border-radius: 15px;
    border-style: solid;
    border-color: #ffffff98;
    border-width: 1px;
  }

  .title-color{
    color: <?= $titleColor?>;
  }

  /* Estilos rápidos, que después pasaran a su propio archivo css*/
  .back-container-graphic{
    background-color: #f0f0f0;
  }

  .back-card-graphic{
    background-color: #ffffff;
    filter: drop-shadow(0px 0px 1px #bdbdbd);
  }

  .shadow-card-graphic{
    filter: drop-shadow(0px 1px 2px #b6b6b6);
  }

  .btn-card-graphic{
    background-color: #e2e2e2;
    padding: 10px 15px;
    border-radius: 100px;
    filter: drop-shadow(0px 1px 2px #d8d7d7);
  }

  .btn-card-graphic-red{
    background-color: #e94646;
    padding: 10px 15px;
    border-radius: 100px;
    filter: drop-shadow(0px 1px 2px #d8d7d7);
  }

  .advice{
    border-left: #b6b6b6 5px solid;
    padding-left: 10px !important;
    padding: 5px;
    filter: drop-shadow(-1px 1px 1px #b6b6b6);
  }

  .text-advice{
    filter: drop-shadow(-1px 1px 1px #b6b6b6);
  }

  .closed-modal-preview{
    position: absolute;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    font-size: 20px;
    margin: 10px;
    z-index: 20;
    object-fit: contain;
    width: 28px;
    height: 28px;
    border-radius: 50px;
    background-color: #000000;
    color: #ffffff;
    &:hover{
      background-color: #464646;
    }
  }

  .live-dot-pulse {
    width: 8px;
    height: 8px;
    background-color: #22c55e;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
    animation: livePulse 1.8s infinite;
  }

  @keyframes livePulse {
    0% {
      transform: scale(0.95);
      box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
    }
    70% {
      transform: scale(1);
      box-shadow: 0 0 0 6px rgba(34, 197, 94, 0);
    }
    100% {
      transform: scale(0.95);
      box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
    }
  }

</style>