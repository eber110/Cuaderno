<?php
  /** 
   * @var mixed $card 
   * @var mixed $session
   * @var mixed $premium
   */
  $item = "p8 pl12 pr12 br12 back-item-sidebar-hover texto w100 flex-row center-start gap8 pointer";
?>
<div class="flex-column between-start h-dvh back-menu-sidebar panel-sidebar pt-sml-35 no-phone">
  <div class="flex-column top-start gap20 w100 p15 bold500 sticky top">
  
    <div class="sidebar-profile-status w100">
      <?php _part("Dashboard.SideMenu.statusBanner", ["card" => $card, "session" => $session]); ?>
    </div>
    
    <div class="vertical-menu animated w100" active-item="back-item-active" active-principal="back-item-active">
      <div class="flex-column top-start gap10 w100">
  
        <!-- Grupo colapsable: Diseño -->
        <div class="vertical-menu-item flex-column top-start gap10 w100">
          <div class="vertical-menu-header w100 br12">
            <div class="p8 pl12 pr12 br12 back-item-sidebar-hover texto w100 flex-row center-between gap8 pointer">
              <p class="flex-row center-start gap8 texto"><?= svg("palette")?>Diseño</p>
              <p class="color-icon-accordion"><?= svg("angle-d")?></p>
            </div>
          </div>
          <div class="vertical-menu-content flex-column gap5 w100 hidden">
            <p class="remote-btn vertical-menu-link <?= $item?> pl20 active" data-remote="header-remote" data-savable="true"><?= svg("angle-r")?>Cabecera</p>
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="background-remote" data-savable="true"><?= svg("angle-r")?>Fondo</p>
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="button-remote" data-savable="true"><?= svg("angle-r")?>Botones</p>
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="color-remote" data-savable="true"><?= svg("angle-r")?>Colores</p>
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="font-remote" data-savable="true"><?= svg("angle-r")?>Fuentes</p>
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="hide-profile-remote" data-savable="true"><?= svg("angle-r")?>Visibilidad</p>
          </div>
        </div>
  
        <!-- Grupo colapsable: Contenido -->
        <div class="vertical-menu-item flex-column top-start gap10 w100">
          <div class="vertical-menu-header w100 br12">
            <div class="p8 pl12 pr12 br12 back-item-sidebar-hover texto w100 flex-row center-between gap8 pointer">
              <p class="flex-row center-start gap8 texto"><?= svg("file-pen")?>Contenido</p>
              <p class="color-icon-accordion"><?= svg("angle-d")?></p>
            </div>
          </div>
          <div class="vertical-menu-content flex-column gap5 w100 hidden">
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="Content-button" data-savable="true"><?= svg("angle-r")?>Enlaces</p>
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="Content-rrss" data-savable="true"><?= svg("angle-r")?>Redes sociales</p>
          </div>
        </div>
        
        <!-- Grupo colapsable: Estadísticas -->
        <div class="vertical-menu-item flex-column top-start gap10 w100">
          <div class="vertical-menu-header w100 br12">
            <div class="p8 pl12 pr12 br12 back-item-sidebar-hover texto w100 flex-row center-between gap8 pointer">
              <p class="flex-row center-start gap8 texto"><?= svg("chart")?>Estadísticas</p>
              <p class="color-icon-accordion"><?= svg("angle-d")?></p>
            </div>
          </div>
          <div class="vertical-menu-content flex-column gap5 w100 hidden">
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="stats-general-remote" data-savable="false"><?= svg("angle-r")?>General</p>
            <p class="remote-btn vertical-menu-link <?= $item?> pl20" data-remote="stats-links-remote" data-savable="false"><?= svg("angle-r")?>Enlaces</p>
          </div>
        </div>
  
      </div>
    </div>
  </div>

  <div class="flex-column top-start gap10 p20 w100">
    <!-- <p><?= ($premium) ? "Es vip": "No suscrito"?></p> -->
    <p class="modal-btn animated darken <?= $item?>"><?= svg("out")?>Cerrar sesión</p>

    <div class="hidden">
      <div class="w100 flex-column center-center h-dvh">
        <div class="flex-column gap20 wpx420 w-sml-100 back-card-graphic shadow-card-graphic p25 br18">
          <div class="ui-field-header">
            <p class="bold500 texto">¿Desea cerrar sesión?</p>
            <span class="text-muted">Se cerrará tu sesión actual en este dispositivo.</span>
          </div>

          <div class="flex-row center-between gap12">
            <a href="/salir" class="btn-card-graphic text-c texto w100 bold500">Salir</a>
            <p class="btn-card-graphic-red text-c textw w100 pointer bold500 modal-close-button">Cancelar</p>
          </div>
        </div>
      </div>
    </div>
    
  </div>
</div>