<?php
  /** 
   * @var mixed $user
   * @var mixed $session
   */
  $item = "p5 pl10 pr10 br10 back-item-sidebar-hover textb w100 flex-row center-start gap5 pointer";
?>

<div class="container-xl h-dvh back-body text-protected">

  <div class="flex-row top-start">

    <div class="flex-column top-start gap20 h-dvh back-menu-sidebar wpx250 p15 bold500 x17">
      <a href="/<?= $user["profile"]?>" class="<?= $item?>"><?= svg("arrow-l-l")?> Ver mi perfil</a>
      
      <div class="vertical-menu animated w100" active-item="back-item-active" active-principal="back-item-active">
        <div class="flex-column top-start gap5 w100">

          <!-- Grupo colapsable: Diseño -->
          <div class="vertical-menu-item flex-column top-start gap5 w100">
            <div class="vertical-menu-header w100 br10">
              <div class="p5 pl10 pr10 br10 back-item-sidebar-hover textb w100 flex-row center-between gap5 pointer">
                <p class="flex-row center-start gap5"><?= svg("palette")?>Diseño</p>
                <p class="color-icon-accordion"><?= svg("angle-d")?></p>
              </div>
            </div>
            <div class="vertical-menu-content w100 hidden">
              <p class="remote-btn vertical-menu-link <?= $item?> pl20 active" data-remote="content-remote-1"><?= svg("angle-r")?>Cabecera</p>
              <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="content-remote-2"><?= svg("angle-r")?>Fondo</p>
            </div>
          </div>
          
          <!-- Enlaces raíz -->
          <p class="remote-btn vertical-menu-link <?= $item?>" data-remote="content-remote-3"><?= svg("file-pen")?>Contenido</p>
          <p class="vertical-menu-link <?= $item?>"><?= svg("list")?>Enlaces</p>

        </div>
      </div>
    </div>

    <div class="h-dvh ">

      <div class="remote-container animated">
        <div id="content-remote-1" class="remote-content active">Contenido 1, puede ser un formulario incrustado con php</div>
        <div id="content-remote-2" class="remote-content hidden">Contenido 2, puede ser un formulario incrustado con php</div>
        <div id="content-remote-3" class="remote-content hidden">Contenido 3, puede ser un formulario incrustado con php</div>
      </div>

    </div>
  </div>
  
</div>