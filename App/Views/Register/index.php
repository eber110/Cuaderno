<main class="flex-column between-center h-dvh text-protected pl20 pr20 pl-sml-10 pr-sml-10">
  <?php _menu("Register.menuRegister");?>

  <div class="wpx610 w-mid-70 w-sml-100 flex-column gap15">
    <!-- Menú e indicador visual de pasos -->
    <div id="register-steps-indicator" class="register-steps-indicator flex-row center-between gap10 w100">
      <div class="step-indicator-item active flex-row center-center gap10 pointer" data-step="0">
        <span class="step-badge bold700 x16">1</span>
        <span class="step-label bold600 x16">Usuario</span>
      </div>
      <div class="step-indicator-divider flex-1">
        <div class="step-indicator-progress" id="step-prog-1"></div>
      </div>
      <div class="step-indicator-item flex-row center-center gap10" data-step="1">
        <span class="step-badge bold700 x16">2</span>
        <span class="step-label bold600 x16">Correo</span>
      </div>
      <div class="step-indicator-divider flex-1">
        <div class="step-indicator-progress" id="step-prog-2"></div>
      </div>
      <div class="step-indicator-item flex-row center-center gap10" data-step="2">
        <span class="step-badge bold700 x16">3</span>
        <span class="step-label bold600 x16">Contraseña</span>
      </div>
    </div>

    <!-- Contenedor animado de los pasos -->
    <div id="register-steps-wrapper" class="register-steps-wrapper w100 pos-relative overflow-hidden br15">
      <div id="step-username-container" class="back3 br15 p15 flex-column gap20 register-step-panel">
        <?php _part("Register.userChoice");?>
      </div>
      <div id="step-email-container" class="back3 br15 p15 flex-column gap20 register-step-panel" style="display: none;">
        <?php _part("Register.emailSelection");?>
      </div>
      <div id="step-password-container" class="back3 br15 p15 flex-column gap20 register-step-panel" style="display: none;">
        <?php _part("Register.enterPassword");?>
      </div>
    </div>

    <div class="flex-row center-center gap10 mt5">
      <p class="x18">¿Ya tienes una cuenta?</p>
      <a href="/ingresar" class="color5-hover x18 bold500">Ingresa aquí.</a>
    </div>
  </div>

  <?php _template("Footer.footerRegister");?>
</main>
<script src="/App/Public/Js/register.js" defer></script>