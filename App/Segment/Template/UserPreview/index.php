<div class="wpx460 w-sml-100 h-dvh pt40 pb40 p-sml-0 flex-column center-center user-profile-preview">
  <?php _part("User.style.css", ["card" => $card]); ?>
  <div class="flex-column between-center back-card shadow-card-preview color-text-card preview-profile p0 br30 br-sml-0 w100">
    
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

  </div>
</div>