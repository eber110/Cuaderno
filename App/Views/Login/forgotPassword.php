<main class="container-xl flex-column between-center h-dvh text-protected pos-relative background-motion-text" data-bmt-content="Clikhub" data-bmt-rotate="false" data-bmt-move="true" data-bmt-gap="26" data-bmt-size="16" data-bmt-opacity="0.08">
  <?php _menu("Register.menuRegister", ["isLogin" => true]);?>

  <div class="flex-1 flex-column center-center w100 pt100 pt-sml-90 pl20 pr20 pl-sml-10 pr-sml-10">
    <div class="wpx610 w-mid-70 w-sml-100 flex-column gap10">
      <div class="back9 br15 p15 flex-column gap20">
        <h1 class="textc">Recupera tu contraseña</h1>
        <p class="textc x18 text-center">
          Ingresa tu correo electrónico registrado y te enviaremos un enlace seguro para crear una nueva contraseña.
        </p>

        <form action="/recuperar" class="w100" method="post">
          <?= class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::csrfField() : '' ?>
          <div class="flex-column gap10">
            <label for="forgot-email" class="textc x18 bold500">Correo electrónico
              <input type="email" name="email" id="forgot-email" placeholder="tu@correo.com" class="color6 br10 mb5 w100" autocomplete="email" required>
            </label>

            <div class="flex-row center-between gap10 mt10 mb10">
              <button type="submit" class="w-auto br15 back2-hover textc p10 pl20 pr20 pointer bold500 border-none">Enviar enlace</button>
            </div>
          </div>
        </form>
      </div>

      <div class="flex-row center-center gap10">
        <p class="x18">¿Recordaste tu clave?</p>
        <a href="/ingresar" class="color8-hover x18 bold500">Volver a ingresar.</a>
      </div>
    </div>
  </div>

  <footer class="w100">
    <?php
      _template("Footer.footerUser");
    ?>
  </footer>
</main>
