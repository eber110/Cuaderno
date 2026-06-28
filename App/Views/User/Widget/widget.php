<?php
  /** @var mixed $card */
?>
<div class="flex-column center-center gap15 p20">
  <?php for ($i=0; $i < 5; $i++) : // Cuando este listo el modelo esto se manejara con un foreach?>

    <?php _part("User.button".$card['style'].""); // condición de botones?>

  <?php endfor?>
</div>