<?php
  /** @var mixed $card */
  _part("User.style.css", ["card" => $card]);
  $styleBack = $card["backCard"]["style_back"] ?? "solid";
  $backVideo = $card["backCard"]["back_video"] ?? "";
?>
<div class="container-xl container-xl-sml flex-column center-center text-protected back-card-container overflow-hidden">
  <div class="wpx580 w-sml-100 h-dvh pt40 p-sml-0">
    <div class="flex-column between-center back-card color-text-card shadow-card dvh-cuaderno h100 p0 brtl-desk-30 brtr-desk-30 brtl-mid-30 brtr-mid-30 brtl-sml-0 brtr-sml-0 overflow-hidden position-relative">
      
      <?php if (\App\Models\DesignModels::isVideoEnabled() && $styleBack === "video" && !empty($backVideo)) : ?>
        <video class="back-video-bg" autoplay loop muted playsinline disablePictureInPicture tabindex="-1" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='none';">
          <source src="<?= eUrl($backVideo) ?>" type="video/mp4" onerror="var v = this.parentElement; if(v){ v.style.display='none'; if(v.nextElementSibling) v.nextElementSibling.style.display='none'; }">
        </video>
        <div class="back-video-overlay"></div>
      <?php endif; ?>

      <?php 
        $backImage = $card["backCard"]["backImageSrc"] ?? $card["backCard"]["back_image"] ?? "";
        if (empty($backImage)) {
          $backImage = URL_IMG . "no-image.webp";
        }
        $backImageFilter = $card["backCard"]["back_image_filter"] ?? "none";
        $numMap = ['0'=>'none', '1'=>'vignette', '2'=>'blur', '3'=>'brightness', '4'=>'contrast', '5'=>'grayscale', '6'=>'hue-rotate', '7'=>'invert', '8'=>'saturate', '9'=>'sepia'];
        if (isset($numMap[$backImageFilter])) $backImageFilter = $numMap[$backImageFilter];
      ?>
      <?php if ($styleBack === "image") : ?>
        <img src="<?= eUrl($backImage) ?>" class="back-image-bg" alt="Fondo" onerror="this.src='<?= eUrl(URL_IMG . 'no-image.webp') ?>';">
        <?php if ($backImageFilter === "vignette") : ?>
          <div class="back-image-vignette"></div>
        <?php endif; ?>
        <div class="back-image-overlay"></div>
      <?php endif; ?>

      <?php _component("Menu.menuUser"); ?>

      <div class="w100 h100 flex-column between-center overflow-y-scroll z-index-1" data-scroll-memory="user-profile" style="scroll-behavior: auto !important;">
        <header class="w100">
          <?php
            $allowedHeaders = ['regularHero', 'midHero', 'voidHero'];
            $headerPart = in_array($card["header"] ?? '', $allowedHeaders, true) ? $card["header"] : "regularHero";
            _part("User." . $headerPart, ["card" => $card]);
            _part("User.widget", ["card" => $card]);
          ?>
        </header>

        <footer class="w100">
          <?php
            _template("Footer.footerUser")
          ?>
        </footer>

        <!-- Script Anti-FOUC síncrono: Restaura el scroll de forma instantánea antes del primer renderizado visual -->
        <script>
          (function() {
            try {
              var k = 'cuaderno_scroll_user-profile_' + window.location.pathname;
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
</div>