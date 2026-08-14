<?php
  /** @var mixed $card */
  _part("User.style.css", ["card" => $card]);
  $styleBack = $card["backCard"]["style_back"] ?? "solid";
  $backVideo = $card["backCard"]["back_video"] ?? "";
?>
<div class="container-xl container-xl-sml flex-column center-center text-protected back-card-container overflow-y-scroll">
  <div class="wpx580 w-sml-100 h-dvh pt40 p-sml-0">
    <div class="flex-column between-center back-card color-text-card shadow-card dvh-cuaderno p0 brtl-desk-30 brtr-desk-30 brtl-mid-30 brtr-mid-30 brtl-sml-0 brtr-sml-0 overflow-y-scroll position-relative">
      
      <?php if ($styleBack === "video" && !empty($backVideo)) : ?>
        <video class="back-video-bg" autoplay loop muted playsinline disablePictureInPicture tabindex="-1" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='none';">
          <source src="<?= $backVideo ?>" type="video/mp4" onerror="var v = this.parentElement; if(v){ v.style.display='none'; if(v.nextElementSibling) v.nextElementSibling.style.display='none'; }">
        </video>
        <div class="back-video-overlay"></div>
      <?php endif; ?>

      <header class="w100 z-index-1">
        <?php
          
          _part("User." . ($card["header"] ?? "regularHero"), ["card" => $card]);
          _part("User.widget", ["card" => $card]);
          
        ?>
      </header>

      <footer class="w100 z-index-1">
        <?php
          _template("Footer.footerUser")
        ?>
      </footer>
      
    </div>
  </div>
</div>