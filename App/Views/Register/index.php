<main class="flex-column between-center h-dvh text-protected pl20 pr20 pl-sml-10 pr-sml-10">
  <?php _menu("Register.menuRegister");?>

  <div class="wpx610 w-mid-70 w-sml-100 flex-column gap10">
    <div class="back3 br15 p15 flex-column gap20">
      <h1 class="xp40">Regístrate aquí!</h1>
      <?php _form("Register.register");?>
    </div>

    <div class="flex-row center-center gap10">
      <p class="x18">¿Ya tienes una cuenta?</p>
      <a href="/ingresar" class="color5-hover x18 bold500">Ingresa aquí.</a>
    </div>
  </div>

  <?php _template("Footer.footerRegister");?>
</main>