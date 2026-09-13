<?php
/**
 * Formulario de registro estándar.
 * Incluye token CSRF, validación visual y campos protegidos.
 */
$csrfToken = class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::getCsrfToken() : '';
?>
<form action="/registro" method="POST" class="form-register flex-col gap15 max-w400 mx-auto p25 border br8 back8">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

  <div class="form-group flex-col gap5">
    <label for="reg-name" class="form-label bold600 x14 color2">Nombre completo</label>
    <input type="text" id="reg-name" name="name" class="form-input p10 br6 border w100" required autocomplete="name" placeholder="Tu Nombre">
  </div>

  <div class="form-group flex-col gap5">
    <label for="reg-email" class="form-label bold600 x14 color2">Correo electrónico</label>
    <input type="email" id="reg-email" name="email" class="form-input p10 br6 border w100" required autocomplete="email" placeholder="tu@email.com">
  </div>

  <div class="form-group flex-col gap5">
    <label for="reg-password" class="form-label bold600 x14 color2">Contraseña</label>
    <input type="password" id="reg-password" name="password" class="form-input p10 br6 border w100" required autocomplete="new-password" placeholder="Mínimo 8 caracteres" minlength="8">
  </div>

  <div class="form-actions flex-row justify-between items-center mt10">
    <a href="/login" class="x13 color-secondary text-none hover-underline">¿Ya tienes cuenta?</a>
    <button type="submit" class="btn-submit p10 px20 br6 back-color color2 bold600 border-none cursor-pointer">
      Registrarse
    </button>
  </div>
</form>
