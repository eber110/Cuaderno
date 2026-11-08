<?php
  $style = "color6 br10 mb5 w100";
?>
<form id="form-step-password" action="/registrar" method="post" autocomplete="off">
  <?= class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::csrfField() : '' ?>
  <!-- Campo de usuario para accesibilidad y gestores de contraseñas (flujo multi-paso) -->
  <input type="text" name="username" id="hidden-username" autocomplete="off" style="display: none;" tabindex="-1" aria-hidden="true">
  <input type="email" name="email" id="hidden-email" autocomplete="off" style="display: none;" tabindex="-1" aria-hidden="true">

  <div class="flex-column gap10">
    
    <label for="input-password" class="textc x18 bold500">
      <input type="password" name="pass" id="input-password" placeholder="Contraseña" class="<?= $style?>" autocomplete="off" required>
      <p class="input-note animate back5 color7 w-fit p2 pl15 pr15 br10 x18 xp16 bold500">Mín 8 caracteres, mín 1 mayúscula y mín 1 numero</p>
    </label>

    <label for="input-repassword" class="textc x18 bold500">
      <input type="password" name="repass" id="input-repassword" placeholder="Repita la contraseña" class="<?= $style?>" autocomplete="off" required>
      <p class="input-note animate back5 color7 w-fit p2 pl15 pr15 br10 x18 xp16 bold500">Ingrese nuevamente la contraseña</p>
    </label>

    <p id="error-password" class="textw back-danger bold500 x20 p10 pl20 pr20 br15" style="display: none;"></p>
    
    <div class="flex-row center-between gap10 mt10 mb10">
      <button type="button" id="btn-back-to-email" class="w-auto br15 back8-hover textc p10 pl20 pr20 pointer bold500 border-none">Atrás</button>
      <button type="submit" class="w-auto br15 back2-hover textc p10 pl20 pr20 pointer bold500 border-none">Registrarme</button>
    </div>

  </div>
</form>