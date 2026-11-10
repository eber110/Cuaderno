<main class="container-xl flex-column between-center h-dvh text-protected pos-relative background-motion-text" data-bmt-content="Clikhub" data-bmt-rotate="false" data-bmt-move="true" data-bmt-gap="26" data-bmt-size="16" data-bmt-opacity="0.08">
  <?php _menu("Register.menuRegister", ["isLogin" => true]);?>
  
  <div class="flex-1 flex-column center-center w100 pt100 pt-sml-90 pl20 pr20 pl-sml-10 pr-sml-10">
    <div class="wpx610 w-mid-70 w-sml-100 flex-column gap10">
      <div class="back9 br15 p15 flex-column gap20">
        <h1 class="textc">Ingresa a tu perfil</h1>
        <?php _form("Login.login");?>
      </div>

      <div class="flex-row center-center gap10">
        <p class="x18">¿No tienes una cuenta?</p>
        <a href="/registrar" class="color8-hover x18 bold500">Crea una aquí.</a>
      </div>
    </div>
  </div>

  <?php _template("Footer.footerRegister");?>
</main>
