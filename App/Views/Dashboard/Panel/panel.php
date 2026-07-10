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

      <nav class="flex-row center-end gap10 p20 sticky top z-index-20 back-body" style="border-bottom: solid 0.5px #f0f0f0;">
          <div><p class="p5 pl15 pr15 br15 pointer" style="border: solid 0.5px #000000;">Guardar</p></div>

          <div class="no-desk">
            <div class="modal-btn animated pointer before-menu-overlay">
              <p class="p5 pl15 pr15 br15" style="border: solid 0.5px #000000;">Vista previa</p>
            </div>
            <div class="hidden">
              <div class="flex-column center-center w100">
                <div class="absolute m20 top right fadeIn pointer modal-close-button closed-modal-preview br50 p0 hpx30 wpx30 flex-column center-center">
                  <?= svg("xmark")?>
                </div>
                <?php _component("UserPreview.userPreview", ["data" => $card])?>
              </div>
            </div>
          </div>
      </nav>

      <div class="remote-container animated p20">

        <div id="content-remote-1" class="remote-content active">
          <div class="back-card wpx500 pb20 br15">
            <?php _part("User.midHero")?>
          </div>
        </div>

        <div id="content-remote-2" class="remote-content hidden">
          <p>Aquí va el formulario para cambiar el fondo del perfil</p>
        </div>

        <div id="content-remote-3" class="remote-content hidden">
          <div class="modal-btn">
            <p>Abrir</p>
          </div>
          <div class="hidden">
            <div class="flex-column center-center w100 before-menu-overlay">
              <div class="absolute m20 top right pointer modal-close-button closed-modal-preview br50 p0 hpx30 wpx30 flex-column center-center">
                <?= svg("xmark")?>
              </div>
              <?php _component("UserPreview.userPreview", ["data" => $card])?>
            </div>
          </div>
        </div>

        <div id="content-remote-4" class="remote-content hidden">
          <div class="post-content">
            <code data-lang="json"><?php print_r(json_encode($card))?></code>
          </div>
        </div>
      </div>

    </div>


    <div class="no-tablet no-phone flex-column center-center h-dvh sticky top" style="min-width: 550px;border-left: solid 0.5px #f0f0f0;">

      <button class="absolute z-index-10 top mt20 p5 pl15 pr15 br15 copy-btn" data-copy="<?= DOMAIN.$card["profile"]?>" style="border: solid 0.5px #000000;">
        cuaderno/<?= $card["profile"]?>
      </button>

      <div class="flex-column center-center w100">
        <?php _component("UserPreview.userPreview", ["data" => $card])?>
      </div>
    </div>

  </div>
</div>