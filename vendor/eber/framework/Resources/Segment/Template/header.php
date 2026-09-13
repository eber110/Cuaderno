<?php
/**
 * Plantilla de cabecera estándar (Header).
 * Incluye barra de navegación, selector de tema (claro/oscuro) y estructura adaptable.
 */
$siteName = defined('NAME_SITE') ? NAME_SITE : 'Mi Sitio';
?>
<header class="site-header flex-row items-center justify-between p15 border-bottom">
  <div class="header-brand flex-row items-center gap15">
    <a href="/" class="brand-link bold600 x20 color2 text-none">
      <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>
    </a>
  </div>

  <nav class="header-nav flex-row items-center gap20">
    <a href="/" class="nav-link color2 text-none">Inicio</a>
    <?php if (class_exists('\Base\Module\AuthModule') && \Base\Module\AuthModule::check()): ?>
      <a href="/dashboard" class="nav-link color2 text-none">Panel</a>
      <a href="/logout" class="nav-link color-danger text-none">Salir</a>
    <?php else: ?>
      <a href="/login" class="nav-link color2 text-none">Ingresar</a>
    <?php endif; ?>

    <!-- Botón de cambio de tema -->
    <button type="button" class="theme-toggle-btn button-theme p8 br50 border-none cursor-pointer" aria-label="Cambiar tema" onclick="if(window.toggleTheme) window.toggleTheme();">
      🌓
    </button>
  </nav>
</header>
