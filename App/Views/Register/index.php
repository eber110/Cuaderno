<main class="flex-column between-center h-dvh text-protected pl20 pr20 pl-sml-10 pr-sml-10">
  <?php _menu("Register.menuRegister");?>

  <div class="wpx610 w-mid-70 w-sml-100 flex-column gap10">
    <div id="step-username-container" class="back3 br15 p15 flex-column gap20">
      <?php _part("Register.userChoice");?>
    </div>
    <div id="step-email-container" class="back3 br15 p15 flex-column gap20" style="display: none;">
      <?php _part("Register.emailSelection");?>
    </div>
    <div id="step-password-container" class="back3 br15 p15 flex-column gap20" style="display: none;">
      <?php _part("Register.enterPassword");?>
    </div>

    <div class="flex-row center-center gap10">
      <p class="x18">¿Ya tienes una cuenta?</p>
      <a href="/ingresar" class="color5-hover x18 bold500">Ingresa aquí.</a>
    </div>
  </div>

  <?php _template("Footer.footerRegister");?>
</main>
<script src="/App/Public/Js/register.js" defer></script>