<?php
  /** @var mixed $widget */
?>

<style>
  .theme-button{
    background-color: <?= $widget["back"]?>;
    color: <?= $widget["color"]?>;
    <?php if ($widget["hover"]) echo "&:hover{background-color:  oklch(from ".$widget["back"]." calc(l * 1.05) calc(c * 1.05) calc(h * 1.05));}"?>
  }

  .theme-button-menu{
    background-color: <?= $widget["back"]?>00;
    &:hover{background-color: oklch(from <?= $widget["back"]?> calc(l * 0.98) calc(c * 0.98) calc(h * 0.98));}
  }

  .w-theme-center{
    width: calc(100% - 115px);
  }
</style>

<div class="flex-column center-center gap15 p20">
  <?php for ($i=0; $i < 5; $i++) :?>

    <a href="#" class="flex-row center-between hpx65 br5 w100 theme-button">

      <figure class="ar-square p7 wpx65">
        <img src="<?= DIR_SHOW_MEDIA?>/prod.jpg" alt="" class="cover br3">
      </figure>

      <p class="flex-row center-center w-theme-center bold500">
        Escribe el links...
      </p>

      <div class="flex-column center-center wpx50">

        <div class="theme-button-menu p3 br100 flex-column center-center modal-btn darken">
          <?= svg("ellipsis-vertical", "x14");?>
        </div>

        <div class="hidden">
          <div class="flex-column center-center w100">
            <div class="wpx550 w-sml-95 back1 br15 p20">
              <h2>Título del Modal</h2>
              <p>Contenido del modal aquí...</p>
              <button>Acción</button>
            </div>
          </div>
        </div>

      </div>
      
    </a>

  <?php endfor?>
</div>