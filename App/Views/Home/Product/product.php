<div class="container-xl back5 observer overflow">
  <div class="container container-xl-mid container-sml flex-column gap20 flex-row center-center flex-column-sml center-center-sml pt100 pb100 pt-sml-0 pb-sml-0">
    
    <div class="w100 flex-column around-start flex-column-sml top-start-sml gap20 p15 p-sml-0 back5 faded-image-sml z-index-10 pt-sml-50 pb-sml-100">
      <div>
        <p class="oswald bold600 x28 textc slide-in-right ob-40 dl-100 animate-slow">CÓDIGO ULTRALIGERO</p>
        <h2 class="oswald bold700 capitalize-p line-h10 x80 x-mid-60 x-sml-40 textc slide-in-right ob-40 dl-200 animate-slow m0 p0">Todo lo que necesitas. Nada que sobre.</h2>
        <p class="bold500 x20 x-sml-18 slide-in-right ob-40 dl-400 animate-slow pt20 pb20 pt-mid-10 pb-mid-20 pt-sml-5 textc">Eliminamos el peso innecesario para entregarte una plataforma limpia, moderna y enfocada exclusivamente en hacer crecer tus conversiones.</p>
      </div>
      <a href="/registrar" class="slide-in-right ob-10 dl-600 p10 pr30 pl30 br50 w-auto back7-hover texto x20 x-sml-18 bold600 pointer"">
        <p>Regístrate gratis</p>
      </a>
    </div>  

    <div class="w100 w-sml-100 flex-column center-center m-mid-15 relative pb-sml-50">
      <figure class="br30 w60 w-mid-80 pointer-events-none">
        <img src="<?= DIR_UPLOAD_MEDIA_STATIC."prodBack.webp"?>" alt="" class="contain">
      </figure>

      <?php
        $slideDir = ROUTE_IMG . "slideProduct/";
        $slideFiles = [];
        if (is_dir($slideDir)) {
          $scanned = scandir($slideDir);
          foreach ($scanned as $file) {
            if ($file !== '.' && $file !== '..' && preg_match('/\.(webp|png|jpe?g|svg)$/i', $file)) {
              $slideFiles[] = $file;
            }
          }
          natsort($slideFiles);
        }
      ?>

      <?php if (!empty($slideFiles)): ?>
        <div class="product-vertical-carousel flex-column center-center">
          <div class="product-carousel-track flex-column center-center gap30 absolute ml80 w80">
            <?php for ($s = 0; $s < 3; $s++): ?>
              <div class="product-carousel-set flex-column center-center gap30 w100" <?= $s > 0 ? 'aria-hidden="true"' : '' ?>>
                <?php foreach ($slideFiles as $file): ?>
                  <div class="product-carousel-item">
                    <img src="<?= DIR_UPLOAD_MEDIA_STATIC . "slideProduct/" . $file ?>" alt="" draggable="false">
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endfor; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>