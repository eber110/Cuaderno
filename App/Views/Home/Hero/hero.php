<?php
  $slides = (isset($heroSlides) && is_array($heroSlides)) ? $heroSlides : [];
?>
<div class="container-xl observer h-dvh-desk hpxmin-mid-1000 pt190 pb100 pb-mid-100 pt-sml-140 pb-sml-50 back-[/App/Public/Img/fondo-hero.webp] back-cover back-center back8-overlay-20">
  <div class="container flex-column container-xl-mid container-sml flex-row center-center flex-column-sml center-center-sml gap-sml-30 gap20">
    <div class="w100 |w-mid-50 p25 p-mid-15 p-sml-0 flex-column center-start wrap">
      <div class="flex-column gap5">
        <p class="oswald bold600 x28 textc slide-in-bottom ob-0 dl-100 animate-slow">BIO LINK ULTRALIGERO</p>
        <h1 class="oswald bold700 capitalize-p line-h10 x80 x-mid-60 x-sml-40 textc slide-in-bottom ob-0 dl-200 animate-slow m0 p0 limel">Todo lo que haces. En un solo link instantáneo.</h1>
        <p class="bold500 x24 textc x-sml-22 slide-in-bottom ob-0 dl-400 animate-slow pt20 pb25 pt-mid-10 pb-mid-20 pt-sml-5">Conecta tus productos, redes, eventos y campañas en una página limpia que carga en milisegundos.</p>
      </div>
      <div class="flex-column gap10 pt10">
        <a href="/registrar" class="slide-in-bottom ob-0 dl-600 p14 pr35 pl35 br50 w-auto back9 back9-hover textw textc x20 x-sml-18 bold600 pointer shadow hover-scale-soft flex-row center-center gap10 text-none">
          <span>Crea tu Clikhub gratis</span>
          <?= svg("arrow-r-l", "x18 textw") ?>
        </a>
        <span class="x18 bold500 flex-row center-start gap8 slide-in-bottom ob-0 dl-700 animate-slow textc">
          <span class="textc"><?= svg("check", "x18") ?></span> Gratis para siempre • Sin tarjeta de crédito
        </span>
      </div>
    </div>
  
    <div class="w100 |w-mid-50 flex-column center-center |m-mid-15">
      <div class="hero-phone-stage slide-in-right ob-0 dl-300 animate-slow">
        <div class="hero-phone-device">
          <!-- Botones físicos laterales estilo teléfono de fondo-description -->
          <span class="hero-phone-btn hero-phone-btn-mute" aria-hidden="true"></span>
          <span class="hero-phone-btn hero-phone-btn-vol-up" aria-hidden="true"></span>
          <span class="hero-phone-btn hero-phone-btn-vol-down" aria-hidden="true"></span>
          <span class="hero-phone-btn hero-phone-btn-power" aria-hidden="true"></span>

          <!-- Chasis 3D escultórico -->
          <div class="hero-phone-frame">
            <div class="hero-phone-top" aria-hidden="true">
              <span class="hero-phone-speaker"></span>
              <span class="hero-phone-camera"></span>
            </div>

            <div class="hero-phone-screen">
              <div class="hero-phone-slider" data-autoplay="3000" aria-label="Galería de vistas previas de Clikhub">
                <div class="hero-phone-track">
                  <?php foreach ($slides as $idx => $slide): ?>
                    <div class="hero-phone-slide<?= $idx === 0 ? " is-active" : "" ?>">
                      <img src="<?= eUrl((string)($slide["src"] ?? "")) ?>" alt="<?= e((string)($slide["alt"] ?? "Vista previa de Clikhub")) ?>" class="hero-phone-slide-img" draggable="false">
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="hero-phone-glare" aria-hidden="true"></div>
            </div>

            <div class="hero-phone-bottom" aria-hidden="true">
              <span class="hero-phone-homebar"></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>