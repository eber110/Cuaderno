<footer class="container-xl back8 textw border-top pt80 pt-mid-60 pt-sml-40 pb80 pb-mid-60 pb-sml-40" style="border-color: rgba(255, 255, 255, 0.08);">
  <div class="container container-xl-mid container-xl-sml pl-mid-20 pr-mid-20 pl-sml-20 pr-sml-20 flex-column gap30 gap-mid-20 gap-sml-15 w100">
    
    <div class="flex-row center-center center-start-sml">
      <div class="flex-column start-start center-center-mid center-center-sml text-center-mid text-center-sml gap12 max-w450">
        <a href="/" class="bold700 x30 textw text-none flex-row center-start center-center-mid center-center-sml gap0 shine-hover">
          <figure class="hpx44 hpx-sml-35">
            <img src="<?= URL_IMG_PUBLIC?>/Custom/logo-cursor.webp" alt="" class="cover">
          </figure>
          <figure class="hpx44 hpx-sml-35">
            <img src="<?= URL_IMG_PUBLIC?>/Custom/logo-white.webp" alt="" class="cover">
          </figure>
        </a>
      </div>
    </div>

    <div class="flex-row center-center center-start-sml gap5 bold500 x20">
      <a href="/ingresar" class="p10 pl15 pr15 br10 back3-hover">Ingresar</a>
      <a href="/registrar" class="p10 pl15 pr15 br50 back7-hover">Registrar</a>
    </div>

    <div class="flex-column center-center gap10">
      <p class="m0 x20 textc">
        Hecho con velocidad y rendimiento para creadores.
      </p>
      <p class="m0 x20 textc">
        &copy; <?= date('Y') ?> <?= defined('NAME_SITE') ? e(NAME_SITE) : 'Clikhub' ?>. Todos los derechos reservados.
      </p>
    </div>

  </div>
</footer>
