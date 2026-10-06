<?php
  /** 
   * Barra de navegación inferior móvil para el Dashboard.
   * Navegación por niveles:
   * 1. Menú Raíz: Diseño, Contenido, Estadísticas (proporción equitativa de 3 columnas).
   * 2. Submenús: Botón House (volver al inicio) + sub-enlaces remotos con scroll horizontal
   *    y tamaño mínimo de 80px por cuadro (touch targets óptimos).
   */
?>

<div id="bottom-nav-phone" class="bottom-nav-container h100 w100 relative">

  <!-- ========================================================
       NIVEL 1: Menú Raíz (Diseño, Contenido, Estadísticas)
       Mantiene la proporción exacta dividida entre los 3 (1/3 cada uno)
       ======================================================== -->
  <div id="bottom-nav-root" class="bottom-nav-track bottom-nav-root flex-row center-center h100 w100">
    <button type="button" class="bottom-nav-btn flex-column center-center gap4 pointer" data-bottom-target="submenu-design" data-root-group="design" aria-label="Diseño">
      <span class="bottom-nav-icon flex-row center-center"><?= svg("palette") ?></span>
      <span class="bottom-nav-label x12 bold500 texto">Diseño</span>
    </button>

    <button type="button" class="bottom-nav-btn flex-column center-center gap4 pointer" data-bottom-target="submenu-content" data-root-group="content" aria-label="Contenido">
      <span class="bottom-nav-icon flex-row center-center"><?= svg("file-pen") ?></span>
      <span class="bottom-nav-label x12 bold500 texto">Contenido</span>
    </button>

    <button type="button" class="remote-btn bottom-nav-btn flex-column center-center gap4 pointer" data-remote="statistics-remote" data-savable="false" aria-label="Estadísticas">
      <span class="bottom-nav-icon flex-row center-center"><?= svg("chart") ?></span>
      <span class="bottom-nav-label x12 bold500 texto">Estadísticas</span>
    </button>
  </div>

  <!-- ========================================================
       NIVEL 2: Submenú Diseño
       Primer cuadro: house.svg para volver al menú raíz
       Siguientes: Cabecera, Fondo, Botones, Colores, Visibilidad
       Mínimo 80px por cuadro con scroll horizontal fluido sin scrollbar
       ======================================================== -->
  <div id="submenu-design" class="bottom-nav-track flex-row center-start h100 w100 hidden" data-submenu-group="design">
    <!-- Botón Inicio / Volver -->
    <button type="button" class="bottom-nav-btn bottom-nav-btn-home flex-column center-center gap4 pointer" data-bottom-back="true" aria-label="Volver al menú principal">
      <span class="bottom-nav-icon flex-row center-center"><?= svg("house") ?></span>
      <span class="bottom-nav-label x12 bold500 texto">Inicio</span>
    </button>

    <!-- Sub-enlaces remotos de Diseño -->
    <button type="button" class="remote-btn bottom-nav-btn flex-column center-center gap4 pointer" data-remote="header-remote" data-savable="true" aria-label="Cabecera">
      <span class="bottom-nav-icon flex-row center-center"><?= svg("user") ?></span>
      <span class="bottom-nav-label x12 bold500 texto">Cabecera</span>
    </button>

    <button type="button" class="remote-btn bottom-nav-btn flex-column center-center gap4 pointer" data-remote="background-remote" data-savable="true" aria-label="Fondo">
      <span class="bottom-nav-icon flex-row center-center"><?= svg("images") ?></span>
      <span class="bottom-nav-label x12 bold500 texto">Fondo</span>
    </button>

    <button type="button" class="remote-btn bottom-nav-btn flex-column center-center gap4 pointer" data-remote="button-remote" data-savable="true" aria-label="Botones">
      <span class="bottom-nav-icon flex-row center-center"><?= svg("sliders") ?></span>
      <span class="bottom-nav-label x12 bold500 texto">Botones</span>
    </button>

    <button type="button" class="remote-btn bottom-nav-btn flex-column center-center gap4 pointer" data-remote="color-remote" data-savable="true" aria-label="Colores">
      <span class="bottom-nav-icon flex-row center-center"><?= svg("palette") ?></span>
      <span class="bottom-nav-label x12 bold500 texto">Colores</span>
    </button>

    <button type="button" class="remote-btn bottom-nav-btn flex-column center-center gap4 pointer" data-remote="hide-profile-remote" data-savable="true" aria-label="Visibilidad">
      <span class="bottom-nav-icon flex-row center-center"><?= svg("eye") ?></span>
      <span class="bottom-nav-label x12 bold500 texto">Visibilidad</span>
    </button>
  </div>

  <!-- ========================================================
       NIVEL 2: Submenú Contenido
       Primer cuadro: house.svg para volver al menú raíz
       Siguientes: Enlaces, Redes sociales
       Mantiene la proporción con mínimo 80px
       ======================================================== -->
  <div id="submenu-content" class="bottom-nav-track flex-row center-start h100 w100 hidden" data-submenu-group="content">
    <!-- Botón Inicio / Volver -->
    <button type="button" class="bottom-nav-btn bottom-nav-btn-home flex-column center-center gap4 pointer" data-bottom-back="true" aria-label="Volver al menú principal">
      <span class="bottom-nav-icon flex-row center-center"><?= svg("house") ?></span>
      <span class="bottom-nav-label x12 bold500 texto">Inicio</span>
    </button>

    <!-- Sub-enlaces remotos de Contenido -->
    <button type="button" class="remote-btn bottom-nav-btn flex-column center-center gap4 pointer" data-remote="Content-button" data-savable="true" aria-label="Enlaces">
      <span class="bottom-nav-icon flex-row center-center"><?= svg("link") ?></span>
      <span class="bottom-nav-label x12 bold500 texto">Enlaces</span>
    </button>

    <button type="button" class="remote-btn bottom-nav-btn flex-column center-center gap4 pointer" data-remote="Content-rrss" data-savable="true" aria-label="Redes sociales">
      <span class="bottom-nav-icon flex-row center-center"><?= svg("share-node") ?></span>
      <span class="bottom-nav-label x12 bold500 texto">Redes</span>
    </button>
  </div>

</div>
