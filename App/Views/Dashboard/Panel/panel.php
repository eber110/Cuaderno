<?php
  /** 
   * @var mixed $card
   * @var mixed $session
   */
  $item = "p5 pl10 pr10 br10 back-item-sidebar-hover textb w100 flex-row center-start gap5 pointer";
  _part("User.style.css");
?>

<div class="container-xl h-dvh back-body text-protected">

  <div class="flex-row top-start">

    <div class="flex-column top-start gap20 h-dvh back-menu-sidebar panel-sidebar p15 bold500 x17 sticky top">
      <a href="/<?= $card["profile"]?>" class="<?= $item?>"><?= svg("arrow-l-l")?> Ver mi perfil</a>
      
      <div class="vertical-menu animated w100" active-item="back-item-active" active-principal="back-item-active">
        <div class="flex-column top-start gap10 w100">

          <!-- Grupo colapsable: Diseño -->
          <div class="vertical-menu-item flex-column top-start gap10 w100">
            <div class="vertical-menu-header w100 br10">
              <div class="p5 pl10 pr10 br10 back-item-sidebar-hover textb w100 flex-row center-between gap5 pointer">
                <p class="flex-row center-start gap5"><?= svg("palette")?>Diseño</p>
                <p class="color-icon-accordion"><?= svg("angle-d")?></p>
              </div>
            </div>
            <div class="vertical-menu-content flex-column gap5 w100 hidden">
              <p class="remote-btn vertical-menu-link <?= $item?> pl20 active" data-remote="content-remote-1"><?= svg("angle-r")?>Cabecera</p>
              <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="content-remote-2"><?= svg("angle-r")?>Fondo</p>
            </div>
          </div>
          
          <!-- Enlaces raíz -->
          <p class="remote-btn vertical-menu-link <?= $item?>" data-remote="content-remote-3"><?= svg("file-pen")?>Contenido</p>
          <p class="remote-btn vertical-menu-link <?= $item?>" data-remote="content-remote-4"><?= svg("list")?>Enlaces</p>

        </div>
      </div>
    </div>

    <div class="h-dvh panel-container p20">

      <div class="remote-container |animated">

        <div id="content-remote-1" class="remote-content active">
          <div class="back-card wpx500 pb20 br15">
            <?php _part("User.regularHero")?>
          </div>
        </div>

        <div id="content-remote-2" class="remote-content hidden h-dvh flex-column center-center">
          <div class="hem65 w60 w-mid-100 back-card-container brtl15 brtr15 flex-column bottom-center">
            <div class="hem60 wpx500 back-card shadow-card br15">
  
            </div>
          </div>
        </div>

        <div id="content-remote-3" class="remote-content hidden">
          Contenido 3, puede ser un formulario incrustado con php
        </div>

        <div id="content-remote-4" class="remote-content hidden">
          Contenido 4, puede ser un formulario incrustado con php
        </div>
      </div>

    </div>
  </div>
</div>