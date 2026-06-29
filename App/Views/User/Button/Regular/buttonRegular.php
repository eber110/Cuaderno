<?php
  /** @var mixed $card */
?>
<a href="#" target="_blank" class="flex-row center-between hpx65 w100 theme-button <?= $card["borders"][0]?> <?= $card["shadow"]?>">

  <figure class="ar-square p7 wpx65">
    <img src="<?= DIR_SHOW_MEDIA?>/prod.jpg" alt="" class="cover <?= $card["borders"][1]?>">
  </figure>

  <p class="flex-row center-center w80 bold500 text-c cut-phrase hpx65" cant-col="2">
    Lorem ipsum dolor sit amet consectetur, adipisicing elit. Itaque quos consequatur necessitatibus atque maxime laborum dolores, pariatur in aut obcaecati accusantium, iusto totam. Deleniti fugiat a quisquam tempore unde excepturi!
  </p>

  <div class="flex-column center-center wpx50">

    <div class="theme-button-menu p3 br100 flex-column center-center z-index-10 modal-btn darken">
      <?= svg("ellipsis-vertical", "x14");?>
    </div>

    <div class="hidden">
      <div class="flex-column center-center w100">
        <div class="wpx550 w-sml-95 back-modal-item br15 p20 text-menu-modal text-protected">
          <h2>Título del Modal</h2>
          <p>Contenido del modal aquí...</p>
          <button>Acción</button>
        </div>
      </div>
    </div>

  </div>
  
</a>