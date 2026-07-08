<?php
  /** 
   * @var mixed $card
   * @var mixed $session
   */
  $item = "p5 pl10 pr10 br10 back-item-sidebar-hover textb w100 flex-row center-start gap5 pointer";
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

    <!-- contenedor de items del panel -->
    <div class="h-dvh panel-container">

      <nav class="flex-row center-end p20">
          <div class="no-desk">
            <div class="modal-btn">
              <p class="p5 pl15 pr15 br15" style="border: solid 0.5px #000000;">Vista previa</p>
            </div>
            <div class="hidden">
              <div class="flex-column center-center w100">
                <div class="absolute m20 top right pointer modal-close-button closed-modal-preview br50 p0 hpx30 wpx30 flex-column center-center">
                  <?= svg("xmark")?>
                </div>
                <?php _component("UserPreview.userPreview", $card["profile"])?>
              </div>
            </div>
          </div>
      </nav>

      <hr class="m0" style="border: solid 0.5px #f0f0f0;">

      <div class="remote-container animated p20">

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
          <div class="modal-btn">
            <p>Abrir</p>
          </div>
          <div class="hidden">
            <div class="flex-column center-center w100">
              <div class="absolute m20 top right pointer modal-close-button closed-modal-preview br50 p0 hpx30 wpx30 flex-column center-center">
                <?= svg("xmark")?>
              </div>
              <?php _component("UserPreview.userPreview", $card["profile"])?>
            </div>
          </div>
        </div>

        <div id="content-remote-4" class="remote-content hidden">
          <div class="post-content">
            <code><?php print_r($card)?></code>
          </div>
        </div>
      </div>

    </div>


    <div class="no-tablet no-phone flex-column center-center h-dvh" style="min-width: 550px;border-left: solid 0.5px #f0f0f0;">
      <div class="flex-column center-center w100">
        <?php _component("UserPreview.userPreview", $card["profile"])?>
      </div>
    </div>

  </div>
</div>