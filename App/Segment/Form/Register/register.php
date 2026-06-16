<?php
  $style = "br10 mb5";
?>
<form id="form-step-password" action="/registrar" method="post" autocomplete="off">
  <input type="hidden" name="username" id="hidden-username">
  <input type="hidden" name="email" id="hidden-email">

  <div class="flex-column gap10">
    
    <label for="pass" class="x18 bold500">
      <input type="password" name="pass" id="input-password" placeholder="Contraseña" class="<?= $style?>" required>
      <p class="input-note x18 xp16 bold700">Mín 8 caracteres, mín 1 mayúscula y mín 1 numero</p>
    </label>

    <label for="repass" class="x18 bold500">
      <input type="password" name="repass" id="input-repassword" placeholder="Repita la contraseña" class="<?= $style?>" required>
      <p class="input-note x18 xp16 bold700">Ingrese nuevamente la contraseña</p>
    </label>

    <p id="error-password" class="color6 bold700 x16 mt5 mb5" style="display: none;"></p>
    
    <div class="flex-row center-between gap10 mt10 mb10">
      <button type="button" id="btn-back-to-email" class="w-auto br15 back8-hover textc p10 pl20 pr20 cursor-pointer bold600">Atrás</button>
      <button type="submit" class="w-auto br15 back5-hover textc p10 pl20 pr20 cursor-pointer bold600">Registrarme</button>
    </div>

  </div>
</form>