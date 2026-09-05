<?php
  $style = "br10 mb5 w100";
?>
<form id="form-step-username" action="/registrar" method="post" autocomplete="off">
  <div class="flex-column gap10">
    
    <label for="username" class="x18 bold500">
      <input type="text" name="username" id="input-username" placeholder="Usuario" class="<?= $style?>" required>
      <p class="input-note x18 xp16 bold700">Mínimo 4 letras y sin espacios</p>
    </label>

    <p id="error-username" class="textw back-danger bold500 x20 p10 pl20 pr20 br15" style="display: none;"></p>
    
    <div class="flex-row center-between gap10 mt10 mb10">
      <button type="submit" class="w-auto br15 back5-hover textc p10 pl20 pr20 pointer bold600 border-none">Continuar</button>
    </div>

  </div>
</form>