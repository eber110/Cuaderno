<?php
  /** 
   * @var mixed $card 
   * @var mixed $session
   */
  $item = "p5 pl10 pr10 br10 back-item-sidebar-hover texto w100 flex-row center-start gap5 pointer";
?>
<div class="flex-column between-start h-dvh back-menu-sidebar panel-sidebar pt-sml-35">
  <div class="flex-column top-start gap20 w100 p15 bold500 x17 sticky top">
  
    <div class="sidebar-profile-status w100">
      <?php _part("Dashboard.SideMenu.statusBanner", ["card" => $card, "session" => $session]); ?>
    </div>
    
    <div id="side-menu-phone" class="vertical-menu animated w100" active-item="back-item-active" active-principal="back-item-active">
      <div class="flex-column top-start gap10 w100">
  
        <!-- Grupo colapsable: Diseño -->
        <div class="vertical-menu-item flex-column top-start gap10 w100">
          <div class="vertical-menu-header w100 br10">
            <div class="p5 pl10 pr10 br10 back-item-sidebar-hover texto w100 flex-row center-between gap5 pointer">
              <p class="flex-row center-start gap5 texto"><?= svg("palette")?>Diseño</p>
              <p class="color-icon-accordion"><?= svg("angle-d")?></p>
            </div>
          </div>
          <div class="vertical-menu-content flex-column gap5 w100 hidden">
            <a href="javascript:void(0);" class="remote-btn vertical-menu-link <?= $item?> pl20 active" data-remote="header-remote" data-savable="true"><?= svg("angle-r")?>Cabecera</a>
            <a href="javascript:void(0);" class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="background-remote" data-savable="true"><?= svg("angle-r")?>Fondo</a>
            <a href="javascript:void(0);" class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="button-remote" data-savable="true"><?= svg("angle-r")?>Botones</a>
            <a href="javascript:void(0);" class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="color-remote" data-savable="true"><?= svg("angle-r")?>Colores</a>
            <a href="javascript:void(0);" class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="hide-profile-remote" data-savable="true"><?= svg("angle-r")?>Visibilidad</a>
          </div>
        </div>
  
        <!-- Grupo colapsable: Contenido -->
        <div class="vertical-menu-item flex-column top-start gap10 w100">
          <div class="vertical-menu-header w100 br10">
            <div class="p5 pl10 pr10 br10 back-item-sidebar-hover texto w100 flex-row center-between gap5 pointer">
              <p class="flex-row center-start gap5 texto"><?= svg("file-pen")?>Contenido</p>
              <p class="color-icon-accordion"><?= svg("angle-d")?></p>
            </div>
          </div>
          <div class="vertical-menu-content flex-column gap5 w100 hidden">
            <a href="javascript:void(0);" class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="Content-button" data-savable="true"><?= svg("angle-r")?>Enlaces</a>
            <a href="javascript:void(0);" class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="Content-rrss" data-savable="true"><?= svg("angle-r")?>Redes sociales</a>
          </div>
        </div>
        
        <!-- Enlaces raíz (no editables) -->
        <a href="javascript:void(0);" class="remote-btn vertical-menu-link <?= $item?>" data-remote="statistics-remote" data-savable="false"><?= svg("chart")?>Estadísticas</a>
  
      </div>
    </div>
  </div>

  <div class="flex-column top-start gap10 p20 w100">
    <a href="javascript:void(0);" class="modal-btn animated darken <?= $item?>"><?= svg("out")?>Cerrar sesión</a>

    <div class="hidden">
      <div class="w100 flex-column center-center h-dvh">
        <div class="flex-column gap20 wpx520 w-sml-100 back-card-graphic p20 br15">
          <p class="x24 bold500 texto">¿Desea cerrar sesión?</p>

          <div class="flex-row center-between gap10">
            <a href="/salir" class="btn-card-graphic shadow-card-graphic hover-scale-soft text-c texto w100 bold500">Salir</a>
            <p class="btn-card-graphic-red shadow-card-graphic hover-scale-soft text-c textc w100 pointer bold500 modal-close-button">Cancelar</p>
          </div>
        </div>
      </div>
    </div>
    
  </div>
</div>
