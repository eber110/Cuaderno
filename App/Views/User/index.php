<?php
  /** @var mixed $card */
  _part("User.style.css");
?>
<div class="container-xl container-xl-sml flex-column center-center text-protected back-card-container">
  <div class="wpx580 w-sml-100 h-dvh pt40 p-sml-0">
    <div class="back-card color-text-card shadow-card dvh-cuaderno p0 brtl-desk-30 brtr-desk-30 brtl-mid-30 brtr-mid-30 brtl-sml-0 brtr-sml-0">
      <?php
        
        _part("User.".$card["header"]);
        _part("User.widget");
        
      ?>
    </div>
  </div>
</div>