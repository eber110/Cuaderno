<?php
  /** @var mixed $card */
?>

<style>
  .theme-button{
    background-color: <?= $card["back"]?>;
    color: <?= $card["color"]?>;
    <?php if ($card["hover"]) echo "&:hover{background-color:  oklch(from ".$card["back"]." calc(l * 0.92) c h);}"?>
  }

  .theme-button-menu{
    background-color: <?= $card["back"]?>00;
    &:hover{background-color: oklch(from <?= $card["back"]?> calc(l * 1.04) c h);}
  }

  .w-theme-center{
    width: calc(100% - 115px);
  }

  <?php if ($card["backCard"][1] == "solid") :?>
    .back-card{
      background-color: <?= $card["backCard"][0]?>;
    }
  <?php elseif ($card["backCard"][1] == "gradientUp") :?>
    .back-card{
      background: radial-gradient(circle at top, <?= $card["backCard"][0]?> 20%, oklch(from <?= $card["backCard"][0]?> calc(l * 0.70) calc(c * 0.90) h) 100%);
    }
  <?php elseif ($card["backCard"][1] == "gradientDown") :?>
    .back-card{
      background: radial-gradient(circle at bottom, <?= $card["backCard"][0]?> 20%, oklch(from <?= $card["backCard"][0]?> calc(l * 0.70) calc(c * 0.90) h) 100%);
    }
  <?php endif?>

  .color-text-card{
    color: <?=  (!$card["colorText"] || $card["colorText"] === "") ? $card["color"] : $card["colorText"]?>;
  }

</style>