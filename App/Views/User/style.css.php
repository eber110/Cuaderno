<?php
  /** @var mixed $card */
?>

<style>
  .theme-button{
    background-color: <?= $card["back"]?>;
    color: <?= $card["color"]?>;
    <?php if ($card["hover"] === true || $card["hover"] === 'true' || $card["hover"] === 1 || $card["hover"] === '1') echo "&:hover{background-color:  oklch(from ".$card["back"]." calc(l * 0.92) c h);}"?>
  }

  .theme-button-menu{
    background-color: <?= $card["back"]?>00;
    &:hover{background-color: oklch(from <?= $card["back"]?> calc(l * 1.04) c h);}
  }

  .w-theme-center{
    width: calc(100% - 115px);
  }

  
  <?php if ($card["backCard"]["style_back"] == "solid") :?>
    .back-card{
      background-color: <?= $card["backCard"]["back_perfil"]?>;
    }
    .back-card-container{
      background-color: oklch(from <?= $card["backCard"]["back_perfil"]?> calc(l * 0.60) calc(c - 0.02) h / 88%);
    }
  <?php elseif ($card["backCard"]["style_back"] == "gradientUp") :?>
    .back-card{
      background: radial-gradient(circle at bottom,
      <?= $card["backCard"]["back_perfil"]?> 20%,
      oklch(from <?= $card["backCard"]["back_perfil"]?> calc(l * 1.4) calc(c - 0.02) calc(h - 30)) 75%,
      oklch(from <?= $card["backCard"]["back_perfil"]?> calc(l * 1.5) calc(c - 0.02) calc(h - 30))) 100%;
    }
    .back-card-container{
      background: linear-gradient(0deg,
        oklch(from <?= $card["backCard"]["back_perfil"]?> calc(l * 0.60) calc(c - 0.01) h / 88%),
        oklch(from <?= $card["backCard"]["back_perfil"]?> calc(l * 1.35) calc(c - 0.03) calc(h - 30) / 90%)
      );
    }
  <?php elseif ($card["backCard"]["style_back"] == "gradientDown") :?>
    .back-card{
      background: radial-gradient(circle at top,
      <?= $card["backCard"]["back_perfil"]?> 20%,
      oklch(from <?= $card["backCard"]["back_perfil"]?> calc(l * 1.4) calc(c - 0.02) calc(h - 30)) 75%,
      oklch(from <?= $card["backCard"]["back_perfil"]?> calc(l * 1.5) calc(c - 0.02) calc(h - 30))) 100%;
    }
    .back-card-container{
      background: linear-gradient(180deg,
        oklch(from <?= $card["backCard"]["back_perfil"]?> calc(l * 0.60) calc(c - 0.01) h / 88%),
        oklch(from <?= $card["backCard"]["back_perfil"]?> calc(l * 1.15) calc(c - 0.03) calc(h - 30) / 90%)
      );
    }
  <?php endif?>

  .shadow-3{
    box-sizing: content-box;
    border: solid 2px <?= $card["colorShadow3"]?>;
    box-shadow: 3px 5px 0px <?= $card["colorShadow3"]?>;
    & img {border: solid 2px <?= $card["colorShadow3"]?>;}
  }

  .color-text-card{
    color: <?=  (!$card["colorText"] || $card["colorText"] === "") ? $card["color"] : $card["colorText"]?>;
  }

  .back-item-menu{
    background-color: oklch(from <?= $card["backCard"]["back_perfil"]?> calc(l * 0.80) calc(c - 0.09) h / 90%);
    padding: 8px 8px;
    border-radius: 15px;
    border-style: solid;
    border-color: #ffffff98;
    border-width: 1px;
  }

  .title-color{
    color: <?= $card["titleColor"]?>;
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

</style>