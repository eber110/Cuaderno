<footer class="container-xl back8 textw border-top pt80 pt-mid-60 pt-sml-40 pb80 pb-mid-60 pb-sml-40" style="border-color: rgba(255, 255, 255, 0.08);">
  <div class="container container-xl-mid container-sml pl-mid-30 pr-mid-30 pl-sml-20 pr-sml-20 flex-column gap50 gap-mid-40 gap-sml-30 w100">
    
    <div class="flex-row center-between flex-column-mid center-center-mid text-center-mid flex-column-sml center-center-sml text-center-sml gap30 w100">
      <div class="flex-column start-start center-center-mid center-center-sml text-center-mid text-center-sml gap12 max-w450">
        <a href="/" class="bold700 x30 textw text-none flex-row center-start center-center-mid center-center-sml gap10">
          <span class="wpx44 hpx44 br12 back5 flex-row center-center shadow"><?= svg("link", "x24 textw") ?></span>
          <span><?= defined('NAME_SITE') ? e(NAME_SITE) : 'Clikhub' ?></span>
        </a>
        <p class="x18 textc m0 max-w450">
          Tu bio link ultraligero y personalizable. Conecta todos tus contenidos en una sola página rápida y profesional.
        </p>
      </div>

      <!-- <div class="flex-row center-end center-center-mid center-center-sml flex-wrap gap25 gap-mid-20 gap-sml-15">
        <a href="/" class="x18 bold500 color7 color5-hover text-none">Inicio</a>
        <a href="/ingresar" class="x18 bold500 color7 color5-hover text-none">Ingresar</a>
        <a href="/registrar" class="x18 bold500 color7 color5-hover text-none">Crear cuenta</a>
        <a href="/terminos" class="x18 bold500 color7 color5-hover text-none">Términos</a>
        <a href="/privacidad" class="x18 bold500 color7 color5-hover text-none">Privacidad</a>
      </div> -->
    </div>

    <div class="w100 pt25 border-top flex-row center-between flex-column-mid center-center-mid text-center-mid flex-column-sml center-center-sml text-center-sml gap15 x18 color7" style="border-color: rgba(255, 255, 255, 0.08);">
      <p class="m0 x18 textc">
        &copy; <?= date('Y') ?> <?= defined('NAME_SITE') ? e(NAME_SITE) : 'Clikhub' ?>. Todos los derechos reservados.
      </p>
      <p class="m0 x18 textc">
        Hecho con velocidad y rendimiento para creadores.
      </p>
    </div>

  </div>
</footer>
