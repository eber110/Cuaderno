<?php
  /** @var mixed $card */
  _part("User.style.css");
?>
<div class="wpx460 w-sml-100 h-dvh pt40 pb40 p-sml-0 flex-column center-center user-profile-preview">
  <div class="flex-column between-center back-card shadow-card-preview color-text-card preview-profile p0 br30 br-sml-0 w100">
    
    <header class="w100">
      <?php
        
        _part("User." . ($card["header"] ?? "regularHero"));
        _part("User.widget");
        
      ?>
    </header>

    <footer class="w100">
      <?php
        _template("Footer.footerUser")
      ?>
    </footer>

  </div>
</div>