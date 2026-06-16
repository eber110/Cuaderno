<?php
  $style = "br10 mb5";
?>
<form id="form-step-email" action="/registrar" method="post" autocomplete="on">
  <div class="flex-column gap10">
    
    <label for="email" class="x18 bold500">
      <input type="email" name="email" id="input-email" placeholder="Correo electrónico" class="<?= $style?>" required>
    </label>

    <p id="error-email" class="textw back-danger bold500 x20 p10 pl20 pr20 br15" style="display: none;"></p>
    
    <div class="flex-row center-between gap10 mt10 mb10">
      <button type="button" id="btn-back-to-username" class="w-auto br15 back8-hover textc p10 pl20 pr20 pointer bold600 border-none">Atrás</button>
      <button type="submit" class="w-auto br15 back5-hover textc p10 pl20 pr20 pointer bold600 border-none">Continuar</button>
    </div>

  </div>
</form>