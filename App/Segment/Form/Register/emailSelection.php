<?php
  $style = "color6 br10 mb5 w100";
?>
<form id="form-step-email" action="/registrar" method="post" autocomplete="off">
  <?= class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::csrfField() : '' ?>
  <div class="flex-column gap10">
    
    <label for="input-email" class="textc x18 bold500">
      <input type="email" name="email" id="input-email" placeholder="Correo electrónico" class="<?= $style?>" autocomplete="off" required>
    </label>

    <p id="error-email" class="textw back-danger bold500 x20 p10 pl20 pr20 br15" style="display: none;"></p>
    
    <div class="flex-row center-between gap10 mt10 mb10">
      <button type="button" id="btn-back-to-username" class="w-auto br15 back8-hover textc p10 pl20 pr20 pointer bold500 border-none">Atrás</button>
      <button type="submit" class="w-auto br15 back2-hover textc p10 pl20 pr20 pointer bold500 border-none">Continuar</button>
    </div>

  </div>
</form>