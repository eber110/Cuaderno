<?php
/**
 * Plantilla de pie de página estándar (Footer).
 */
$year = date('Y');
$siteName = defined('NAME_SITE') ? NAME_SITE : 'Mi Sitio';
?>
<footer class="site-footer p20 text-center color-secondary mt40 border-top">
  <div class="footer-content max-w1200 mx-auto flex-col center gap10">
    <p class="x14 m0">
      &copy; <?= $year ?> <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>. Todos los derechos reservados.
    </p>
    <div class="footer-links flex-row center gap15 x13">
      <a href="/privacidad" class="color-secondary text-none hover-underline">Privacidad</a>
      <a href="/terminos" class="color-secondary text-none hover-underline">Términos</a>
      <a href="/contacto" class="color-secondary text-none hover-underline">Contacto</a>
    </div>
  </div>
</footer>
