<?php
  $style = "br10 mb5";
?>
<form action="/registrar" method="post">
  <div class="flex-column gap10">
    
    <label for="username" class="x18 bold500">
      <input type="text" name="username" id="" placeholder="Usuario" class="<?= $style?>" required>
    </label>

    <label for="email" class="x18 bold500">
      <input type="email" name="email" id="" placeholder="Correo electrónico" class="<?= $style?>" required>
    </label>

    <label for="pass" class="x18 bold500">
      <input type="password" name="pass" id="" placeholder="Contraseña" class="<?= $style?>" required>
      <p class="input-note x18 xp16 bold700">Mínimo 8 caracteres y 1 caracter especial ( _ @ # / - )</p>
    </label>

    <label for="repass" class="x18 bold500">
      <input type="password" name="repass" id="" placeholder="Repita la contraseña" class="<?= $style?>" required>
      <p class="input-note x18 xp16 bold700">Ingrese nuevamente la contraseña</p>
    </label>
    
    <div class="flex-row center-between gap10 mt10 mb10">
      <input type="submit" value="Registrarme" class="w-auto br15 back5-hover textc p10 pl20 pr20">
    </div>

  </div>
</form>