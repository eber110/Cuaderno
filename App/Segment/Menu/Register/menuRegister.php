<?php
$currentUri = parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH) ?? "";
$onLogin = isset($isLogin) ? (bool)$isLogin : (str_starts_with($currentUri, "/ingresar") || str_starts_with($currentUri, "/login"));
?>
<nav class="container container-mid container-sml absolute toppx40 flex-column center-center before">
  <div class="w100 p15 pl30 pr30 pl-sml-15 pr-sml-15 br100 body-back border1 shadow-menu flex-row center-between gap10">
    <a href="/" class="flex-row center-center">
      <figure class="hpx40">
        <img src="<?= LOGOPAG?>" alt="" class="cover no-phone">
        <img src="<?= URL_IMG_PUBLIC?>/Custom/logo-pag-short.webp" alt="" class="cover no-desk no-tablet">
      </figure>
    </a>

    <div class="flex-row center-center gap5">
      <a href="/" class="back3-hover p10 pl20 pr20 pl-sml-12 pr-sml-12 br10 textc flex-row center-center gap5 bold500 pointer">
        <?= svg("arrow-l-l"); ?>
        <span>Volver</span>
      </a>

      <?php if ($onLogin): ?>
        <a href="/registrar" class="back8-hover p10 pl20 pr20 pl-sml-15 pr-sml-15 br100 textc bold500">Regístrate</a>
      <?php else: ?>
        <a href="/ingresar" class="back8-hover p10 pl20 pr20 pl-sml-15 pr-sml-15 br100 textc bold500">Ingresar</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
