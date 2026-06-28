<?php
  /** @var mixed $widget */
?>

<style>
  .theme-button{
    background-color: <?= $widget["back"]?>;
    color: <?= $widget["color"]?>;
    <?php if ($widget["hover"]) echo "&:hover{background-color:  oklch(from ".$widget["back"]." calc(l * 0.92) c h);}"?>
  }

  .theme-button-menu{
    background-color: <?= $widget["back"]?>00;
    &:hover{background-color: oklch(from <?= $widget["back"]?> calc(l * 1.04) c h);}
  }

  .w-theme-center{
    width: calc(100% - 115px);
  }
</style>