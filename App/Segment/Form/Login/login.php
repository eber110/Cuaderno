<?php
  $style = "br10 mb5";
?>
<form action="/ingresar" method="post" autocomplete="off">
  <div class="flex-column gap10">
    
    <label for="username" class="x18 bold500">Ingrese su usuario
      <input type="text" name="username" id="" placeholder="Usuario" class="<?= $style?>" |required>
      <p class="input-note x18 xp16 bold700">Ingresa con tu usuario o tu correo electrónico</p>
    </label>

    <label for="pass" class="x18 bold500">Ingrese su contraseña
      <input type="password" name="pass" id="" placeholder="Contraseña" class="<?= $style?>" |required>
    </label>
    
    <div class="flex-row center-between gap10 mt10 mb10">
      <input type="submit" value="Ingresar" class="w-auto br15 back5-hover textc p10 pl20 pr20">
    </div>

  </div>
</form>