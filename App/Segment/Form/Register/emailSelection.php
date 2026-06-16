<?php
  $style = "br10 mb5";
?>
<form id="form-step-email" action="/registrar" method="post" autocomplete="off">
  <div class="flex-column gap10">
    
    <label for="email" class="x18 bold500">
      <input type="email" name="email" id="input-email" placeholder="Correo electrónico" class="<?= $style?>" required>
    </label>

    <p id="error-email" class="color-caution bold700 x16 mt5 mb5" style="display: none;"></p>
    
    <div class="flex-row center-between gap10 mt10 mb10">
      <button type="button" id="btn-back-to-username" class="w-auto br15 back8-hover textc p10 pl20 pr20 cursor-pointer bold600">Atrás</button>
      <button type="submit" class="w-auto br15 back5-hover textc p10 pl20 pr20 cursor-pointer bold600">Continuar</button>
    </div>

  </div>
</form>