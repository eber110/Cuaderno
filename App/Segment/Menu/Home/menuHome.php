<nav class="container container-mid container-sml sticky top2 flex-column center-center before">
  <div class="w100 p15 pl30 pr30 br100 back-body border1 shadow-menu flex-row center-between gap10">
    <p class="bold700 x30"><?= NAME_SITE;?></p>
    <div class="flex-row center-center gap5">
      <?php if ($connect ?? false) :?>
        <a href="/salir" class="p10 pl20 pr20 br10 color8-hover bold500">Hola, <?= $username ?? null;?></a>
      <?php else:?>
        <a href="/ingresar" class="back3 p10 pl20 pr20 br10 color8-hover">Ingresar</a>
      <?php endif?>
        <a href="/registrar" class="back8-hover p10 pl20 pr20 br100 textc">Regístrate</a>
    </div>
  </div>
</nav>