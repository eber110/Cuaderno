<?php
  $style = "color6 br10 mb5 w100";
?>
<form action="/ingresar" class="w100" method="post">
  <?= class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::csrfField() : '' ?>
  <div class="flex-column gap10">
    
    <label for="username" class="textc x18 bold500">Ingrese su usuario
      <input type="text" name="username" id="username" placeholder="Usuario" class="<?= $style?>" autocomplete="username" required>
      <p class="input-note animate back5 color7 w-fit p2 pl15 pr15 br10 x18 xp16 bold500">Ingresa con tu usuario o tu correo electrónico</p>
    </label>

    <label for="password" class="textc x18 bold500">Ingrese su contraseña
      <input type="password" name="pass" id="password" placeholder="Contraseña" class="<?= $style?>" autocomplete="current-password" required>
    </label>
    
    <div class="flex-row center-between gap10 mt10 mb10">
      <input type="submit" value="Ingresar" class="w-auto br15 back2-hover textc p10 pl20 pr20 pointer bold500 border-none">
    </div>

  </div>
</form>