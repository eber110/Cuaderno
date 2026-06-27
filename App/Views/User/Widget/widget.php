<?php
  /** @var mixed $widget */
?>
<div class="flex-column center-center gap15 p20">
  <?php for ($i=0; $i < 5; $i++) : // Cuando este listo el modelo esto se manejara con un foreach?>

    <?php _part("User.button".$widget['style'].""); // condición de botones?>

  <?php endfor?>
</div>