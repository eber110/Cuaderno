<?php
  /** @var mixed $card */
  $selected = "border-selected-item";
?>
<form class="auto-submit w100" action="/test/1" method="post" enctype="multipart/form-data">

  <div class="flex-column top-between gap20">
    
    <div class="flex-row center-center w100 p20 br50 border8 pointer">Añadir <?= svg("add")?></div>

  </div>  

  <input type="submit" value="guardar" class="hidden">
</form>