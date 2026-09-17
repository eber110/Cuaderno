<?php
/**
 * Formulario de inicio de sesión estándar.
 * Incluye token CSRF, validación visual y campos protegidos.
 */
$csrfToken = class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::getCsrfToken() : '';
?>
<form action="/login" method="POST" class="form-login flex-col gap15 max-w400 mx-auto p25 border br8 back8">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

  <div class="form-group flex-col gap5">
    <label for="email" class="form-label bold600 x14 color2">Correo electrónico</label>
    <input type="email" id="email" name="email" class="form-input p10 br6 border w100" required autocomplete="email" placeholder="tu@email.com">
    <p class="input-note animate x14 color2 bold500">Ingresa tu correo electrónico registrado</p>
  </div>

  <div class="form-group flex-col gap5">
    <label for="password" class="form-label bold600 x14 color2">Contraseña</label>
    <input type="password" id="password" name="password" class="form-input p10 br6 border w100" required autocomplete="current-password" placeholder="••••••••">
    <p class="input-note animate x14 color2 bold500">Ingresa tu contraseña</p>
  </div>

  <div class="form-actions flex-row justify-between items-center mt10">
    <a href="/recuperar" class="x13 color-secondary text-none hover-underline">¿Olvidaste tu contraseña?</a>
    <button type="submit" class="btn-submit p10 px20 br6 back-color color2 bold600 border-none cursor-pointer">
      Ingresar
    </button>
  </div>
</form>
