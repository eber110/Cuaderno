<main class="flex-column between-center h-dvh text-protected pl20 pr20 pl-sml-10 pr-sml-10 background-motion-text" data-bmt-content="Clikhub" data-bmt-rotate="false" data-bmt-move="true" data-bmt-gap="26" data-bmt-size="16" data-bmt-opacity="0.08">
  <?php _menu("Register.menuRegister");?>
  
  <div class="wpx610 w-mid-70 w-sml-100 flex-column gap10">
    <div class="back9 br15 p15 flex-column gap20">
      <h1 class="color2">Ingresa a tu perfil</h1>
      <?php _form("Login.login");?>
    </div>

    <div class="flex-row center-center gap10">
      <p class="x18">¿No tienes una cuenta?</p>
      <a href="/registrar" class="color8-hover x18 bold500">Crea una aquí.</a>
    </div>
  </div>
  <?//= var_dump(\Base\Module\Session::session_active())?>
  <?php _template("Footer.footerRegister");?>
</main>