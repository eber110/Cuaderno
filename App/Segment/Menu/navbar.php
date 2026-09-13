<?php
/**
 * Componente de menú desplegable móvil/desktop accesible con vanilla JS.
 */
?>
<div class="navbar-wrapper flex-row items-center justify-between p10">
  <button type="button" class="navbar-toggle-btn p8 br6 border cursor-pointer md-hide" aria-label="Abrir menú" onclick="document.querySelector('.navbar-menu').classList.toggle('is-active');">
    ☰ Menú
  </button>

  <ul class="navbar-menu list-none flex-row items-center gap15 m0 p0">
    <li class="navbar-item"><a href="/" class="navbar-link color2 text-none p8 hover-underline">Inicio</a></li>
    <li class="navbar-item"><a href="/acerca" class="navbar-link color2 text-none p8 hover-underline">Acerca de</a></li>
    <li class="navbar-item"><a href="/servicios" class="navbar-link color2 text-none p8 hover-underline">Servicios</a></li>
    <li class="navbar-item"><a href="/contacto" class="navbar-link color2 text-none p8 hover-underline">Contacto</a></li>
  </ul>
</div>
