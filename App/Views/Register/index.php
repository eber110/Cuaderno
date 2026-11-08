<main class="container-xl flex-column between-center h-dvh text-protected pos-relative background-motion-text" data-bmt-content="Clikhub" data-bmt-rotate="false" data-bmt-move="true" data-bmt-gap="26" data-bmt-size="16" data-bmt-opacity="0.08">
  <?php _menu("Register.menuRegister", ["isLogin" => false]);?>

  <div class="flex-1 flex-column center-center w100 pt100 pt-sml-90 pl20 pr20 pl-sml-10 pr-sml-10">
    <div class="wpx610 w-mid-70 w-sml-100 flex-column gap15">
      <!-- Menú e indicador visual de pasos -->
      <div id="register-steps-indicator" class="register-steps-indicator back9 flex-row center-between gap10 w100">
        <div class="step-indicator-item active flex-row center-center gap10 textc pointer" data-step="0">
          <span class="step-badge bold600 x18">1</span>
          <span class="step-label bold500 x18">Usuario</span>
        </div>
        <div class="step-indicator-divider flex-1">
          <div class="step-indicator-progress" id="step-prog-1"></div>
        </div>
        <div class="step-indicator-item flex-row center-center gap10 textc" data-step="1">
          <span class="step-badge bold600 x18">2</span>
          <span class="step-label bold500 x18">Correo</span>
        </div>
        <div class="step-indicator-divider flex-1">
          <div class="step-indicator-progress" id="step-prog-2"></div>
        </div>
        <div class="step-indicator-item flex-row center-center gap10 textc" data-step="2">
          <span class="step-badge bold600 x18">3</span>
          <span class="step-label bold500 x18">Contraseña</span>
        </div>
      </div>

      <!-- Contenedor animado de los pasos -->
      <div id="register-steps-wrapper" class="register-steps-wrapper w100 pos-relative overflow-hidden br15">
        <div id="step-username-container" class="back9 br15 p15 flex-column gap20 register-step-panel">
          <?php _part("Register.userChoice");?>
        </div>
        <div id="step-email-container" class="back9 br15 p15 flex-column gap20 register-step-panel" style="display: none;">
          <?php _part("Register.emailSelection");?>
        </div>
        <div id="step-password-container" class="back9 br15 p15 flex-column gap20 register-step-panel" style="display: none;">
          <?php _part("Register.enterPassword");?>
        </div>
      </div>

      <div class="flex-row center-center gap10">
        <p class="x18">¿Ya tienes una cuenta?</p>
        <a href="/ingresar" class="color8-hover x18 bold500">Ingresa aquí.</a>
      </div>
    </div>
  </div>

  <?php _template("Footer.footerRegister");?>
</main>
<script src="/App/Public/Js/register.js" defer></script>
