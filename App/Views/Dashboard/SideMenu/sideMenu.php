<?php
  /** 
   * @var mixed $card 
   * @var mixed $session
   */
  $item = "p5 pl10 pr10 br10 back-item-sidebar-hover textb w100 flex-row center-start gap5 pointer";
?>
<div class="flex-column between-start h-dvh back-menu-sidebar panel-sidebar no-phone">
  <div class="flex-column top-start gap20 w100 p15 bold500 x17 sticky top">
  
    <?php if (!empty($card["active"])) :?>
      <?php if ($card["active"] == true && $card["hide"] == true):?>
        <p class="p5 pl10 pr10 br10 back-item-sidebar-hover w100 flex-row center-start gap5 back-danger textw tooltip animated bottom" 
          data-tooltip="Puedes ocultar o mostrar tu perfil desde el menú «Visibilidad»."
          style-tooltip="back5 textw shadow x18">
            <?= svg("triangle-exclamation-fill","x16 mr5");?> Perfil oculto
            <span class="flex-row center-center pointer"><?= svg("question");?></span>
        </p>
      <?php else:?>
        <a href="/<?= $session["username"]?>" class="<?= $item?>"><?= svg("arrow-l-l")?> Ver mi perfil</a>
      <?php endif?>
    <?php else:?>
      <p class="p5 pl10 pr10 br10 back-item-sidebar-hover w100 flex-row center-start gap5 back-danger textw tooltip animated bottom" 
      data-tooltip="Completa los datos requeridos en tu perfil para activarlo."
      style-tooltip="back5 textw shadow x18">
        <?= svg("triangle-exclamation-fill","x16 mr5");?> Activa tu perfil
        <span class="flex-row center-center pointer"><?= svg("question");?></span>
      </p>
    <?php endif?>
    
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
            <p class="remote-btn vertical-menu-link <?= $item?> pl20 active" data-remote="header-remote" data-savable="true"><?= svg("angle-r")?>Cabecera</p>
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="background-remote" data-savable="true"><?= svg("angle-r")?>Fondo</p>
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="button-remote" data-savable="true"><?= svg("angle-r")?>Botones</p>
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="color-remote" data-savable="true"><?= svg("angle-r")?>Colores</p>
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="hide-profile-remote" data-savable="true"><?= svg("angle-r")?>Visibilidad</p>
          </div>
        </div>
  
        <!-- Grupo colapsable: Contenido -->
        <div class="vertical-menu-item flex-column top-start gap10 w100">
          <div class="vertical-menu-header w100 br10">
            <div class="p5 pl10 pr10 br10 back-item-sidebar-hover textb w100 flex-row center-between gap5 pointer">
              <p class="flex-row center-start gap5"><?= svg("file-pen")?>Contenido</p>
              <p class="color-icon-accordion"><?= svg("angle-d")?></p>
            </div>
          </div>
          <div class="vertical-menu-content flex-column gap5 w100 hidden">
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="Content-button" data-savable="true"><?= svg("angle-r")?>Enlaces</p>
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="Content-rrss" data-savable="true"><?= svg("angle-r")?>Redes sociales</p>
          </div>
        </div>
        
        <!-- Enlaces raíz (no editables) -->
        <p class="remote-btn vertical-menu-link <?= $item?>" data-remote="statistics-remote" data-savable="false"><?= svg("chart")?>Estadísticas</p>
        <!-- <p class="remote-btn vertical-menu-link <?= $item?>" data-remote="content-remote-4" data-savable="false"><?= svg("list")?>Datos de usuario</p>
        <p class="remote-btn vertical-menu-link <?= $item?>" data-remote="content-remote-5" data-savable="false"><?= svg("list")?>Datos de sesión</p> -->
  
      </div>
    </div>
  </div>

  <div class="flex-column top-start gap10 p20 w100">
    <!-- <div class="flex-row center-between w100" data-savable="false">Ocultar perfil <?php _form("Panel.hideProfile")?></div> -->
    <p class="modal-btn animated darken <?= $item?>"><?= svg("out")?>Cerrar sesión</p>

    <div class="hidden">
      <div class="w100 flex-column center-center h-dvh">
        <div class="flex-column gap20 wpx520 w-sml-100 back-card-graphic p20 br15">
          <p class="x24 bold500">¿Desea cerrar sesión?</p>

          <div class="flex-row center-between gap10">
            <a href="/salir" class="btn-card-graphic shadow-card-graphic hover-scale-soft text-c texto w100 bold500">Salir</a>
            <p class="btn-card-graphic-red shadow-card-graphic hover-scale-soft text-c textc w100 pointer bold500 modal-close-button">Cancelar</p>
          </div>
        </div>
      </div>
    </div>
    
  </div>
</div>