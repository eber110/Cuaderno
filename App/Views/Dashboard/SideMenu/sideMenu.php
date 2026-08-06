<?php
  /** 
   * @var mixed $card 
   * @var mixed $session
   */
  $item = "p5 pl10 pr10 br10 back-item-sidebar-hover textb w100 flex-row center-start gap5 pointer";
?>
<div class="flex-column between-start h-dvh back-menu-sidebar panel-sidebar">
  <div class="flex-column top-start gap20 |h-dvh |back-menu-sidebar |panel-sidebar p15 bold500 x17 sticky top">
  
    <?php if (!empty($card["active"])) :?>
      <a href="/<?= $session["username"]?>" class="<?= $item?>"><?= svg("arrow-l-l")?> Ver mi perfil</a>
    <?php else:?>
      <p class="p5 pl10 pr10 br10 back-item-sidebar-hover w100 flex-row center-start gap5 back-danger textw tooltip animated bottom" 
      data-tooltip="Completa los datos requeridos de tu perfil, para activarlo"
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
    <div class="flex-row center-between w100" data-savable="false">Ocultar perfil <?php _form("Panel.hideProfile")?></div>
    <a href="/salir">Cerrar sesión</a>
  </div>
</div>