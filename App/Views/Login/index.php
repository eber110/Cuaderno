<main class="flex-column between-center h-dvh text-protected pl20 pr20 pl-sml-10 pr-sml-10">
  <?php _menu("Register.menuRegister");?>
  
  <div class="wpx610 w-mid-70 w-sml-100 flex-column gap10">
    <div class="back3 br15 p15 flex-column gap20">
      <h1>Ingresa a tu cuaderno</h1>
      <?php _form("Login.login");?>
    </div>

    <div class="flex-row center-center gap10">
      <p class="x18">¿No tienes una cuenta?</p>
      <a href="/registrar" class="color5-hover x18 bold500">Crea una aquí.</a>
    </div>
  </div>
  <?//= var_dump(\Base\Module\Session::session_active())?>
  <?php _template("Footer.footerRegister");?>
</main>