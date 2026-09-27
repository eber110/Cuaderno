<?php
  /** @var mixed $card */
  $styleBack = $card["backCard"]["style_back"] ?? "solid";
  $backVideo = $card["backCard"]["back_video"] ?? "";
?>
<div class="wpx460 w-sml-100 h-dvh pt40 pb40 p-sml-0 flex-column center-center user-profile-preview">
  <?php _part("User.style.css", ["card" => $card]); ?>
  <div class="flex-column between-center back-card shadow-card-preview color-text-card preview-profile p0 br30 br-sml-0 w100 overflow-hidden position-relative">
    <?php if (\App\Models\DesignModels::isVideoEnabled() && !empty($backVideo)) : ?>
      <video class="back-video-bg" preload="metadata" autoplay loop muted playsinline disablePictureInPicture tabindex="-1" style="<?= ($styleBack === 'video') ? '' : 'display: none;' ?>" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='none';">
        <source src="<?= eUrl($backVideo) ?>" type="video/mp4" onerror="var v = this.parentElement; if(v){ v.style.display='none'; if(v.nextElementSibling) v.nextElementSibling.style.display='none'; }">
      </video>
      <div class="back-video-overlay" style="<?= ($styleBack === 'video') ? '' : 'display: none;' ?>"></div>
    <?php endif; ?>

    <?php 
      $backImage = $card["backCard"]["backImageSrc"] ?? $card["backCard"]["back_image"] ?? ""; 
      $backImageFilter = $card["backCard"]["back_image_filter"] ?? "none";
      $numMap = ['0'=>'none', '1'=>'vignette', '2'=>'blur', '3'=>'brightness', '4'=>'contrast', '5'=>'grayscale', '6'=>'hue-rotate', '7'=>'invert', '8'=>'saturate', '9'=>'sepia'];
      if (isset($numMap[$backImageFilter])) $backImageFilter = $numMap[$backImageFilter];
    ?>
    <img src="<?= !empty($backImage) ? eUrl($backImage) : '' ?>" class="back-image-bg" style="<?= ($styleBack === 'image' && !empty($backImage)) ? '' : 'display: none;' ?>" alt="Background" onerror="this.style.display='none';">
    <div class="back-image-vignette" style="<?= ($styleBack === 'image' && $backImageFilter === 'vignette') ? '' : 'display: none;' ?>"></div>
    <div class="back-image-overlay" style="<?= ($styleBack === 'image') ? '' : 'display: none;' ?>"></div>


    <?php// _component("Menu.menuUser"); ?>

    <div class="w100 h100 flex-column between-center overflow-y-scroll z-index-1" data-scroll-memory="user-preview" style="scroll-behavior: auto !important;">
      <header class="w100">
        <?php
          $currentHeader = $card["header"] ?? "regularHero";
        ?>
        <div class="header-variant-wrapper header-regularHero <?= $currentHeader === 'regularHero' ? '' : 'hidden' ?>" data-header-variant="regularHero">
          <?php _part("User.regularHero", ["card" => $card]); ?>
        </div>
        <div class="header-variant-wrapper header-voidHero <?= $currentHeader === 'voidHero' ? '' : 'hidden' ?>" data-header-variant="voidHero">
          <?php _part("User.voidHero", ["card" => $card]); ?>
        </div>
        <div class="header-variant-wrapper header-midHero <?= $currentHeader === 'midHero' ? '' : 'hidden' ?>" data-header-variant="midHero">
          <?php _part("User.midHero", ["card" => $card]); ?>
        </div>
        <?php
          _part("User.widget", ["card" => $card, "isPreview" => true]);
        ?>
      </header>

      <footer class="w100">
        <?php
          _template("Footer.footerUser")
        ?>
      </footer>

      <!-- Script Anti-FOUC síncrono: Restaura el scroll del preview de forma instantánea antes del primer renderizado visual -->
      <script>
        (function() {
          try {
            var k = 'cuaderno_scroll_user-preview_' + window.location.pathname;
            var s = sessionStorage.getItem(k);
            if (s) {
              var p = document.currentScript ? document.currentScript.parentElement : null;
              if (p) {
                p.style.setProperty('scroll-behavior', 'auto', 'important');
                p.scrollTop = parseInt(s, 10) || 0;
              }
            }
          } catch(e) {}
        })();
      </script>
    </div>

  </div>
</div>