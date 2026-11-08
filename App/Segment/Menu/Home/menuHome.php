<nav class="container container-mid container-sml absolute top2 flex-column center-center before">
  <div class="w100 p15 pl30 pr30 br100 body-back border1 shadow-menu flex-row center-between gap10">
    <figure class="hpx40">
      <img src="<?= LOGOPAG?>" alt="" class="cover no-phone">
      <img src="<?= URL_IMG_PUBLIC?>/Custom/logo-pag-short.webp" alt="" class="cover no-desk no-tablet">
    </figure>
    <!-- <p class="bold700 x30"><?= NAME_SITE;?></p> -->
    <div class="flex-row center-center gap5">
      <?php if ($connect ?? false) :?>
        <a href="/salir" class="p10 pl20 pr20 br10 textc bold500">Hola, <?= $username ?? null;?></a>
      <?php else:?>
        <a href="/ingresar" class="back3-hover p10 pl20 pr20 br10 textc">Ingresar</a>
      <?php endif?>
        <a href="/registrar" class="back8-hover p10 pl20 pr20 br100 textc">Regístrate</a>
    </div>
  </div>
</nav>