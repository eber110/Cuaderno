<?php
  /** @var mixed $card */
  $styleBack = $card["backCard"]["style_back"] ?? "solid";
  $backVideo = $card["backCard"]["back_video"] ?? "";
?>
<div class="wpx460 w-sml-100 h-dvh pt40 pb40 p-sml-0 flex-column center-center user-profile-preview">
  <?php _part("User.style.css", ["card" => $card]); ?>
  <div class="flex-column between-center back-card shadow-card-preview color-text-card preview-profile p0 br30 br-sml-0 w100 overflow-hidden position-relative">
    <?php if ($styleBack === "video" && !empty($backVideo)) : ?>
      <video class="back-video-bg" preload="metadata" autoplay loop muted playsinline disablePictureInPicture tabindex="-1" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='none';">
        <source src="<?= $backVideo ?>" type="video/mp4" onerror="var v = this.parentElement; if(v){ v.style.display='none'; if(v.nextElementSibling) v.nextElementSibling.style.display='none'; }">
      </video>
      <div class="back-video-overlay"></div>
    <?php endif; ?>

    <?php// _component("Menu.menuUser"); ?>

    <div class="w100 h100 flex-column between-center overflow-y-scroll z-index-1" data-scroll-memory="user-preview" style="scroll-behavior: auto !important;">
      <header class="w100">
        <?php
          
          _part("User." . ($card["header"] ?? "regularHero"), ["card" => $card]);
          _part("User.widget", ["card" => $card]);
          
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