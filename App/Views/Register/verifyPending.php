<?php
  $pendingEmail = isset($pendingEmail) ? (string)$pendingEmail : "";
?>
<main class="container-xl flex-column between-center h-dvh text-protected pos-relative background-motion-text" data-bmt-content="Clikhub" data-bmt-rotate="false" data-bmt-move="true" data-bmt-gap="26" data-bmt-size="16" data-bmt-opacity="0.08">
  <?php _menu("Register.menuRegister", ["isLogin" => true]);?>

  <div class="flex-1 flex-column center-center w100 pt100 pt-sml-90 pl20 pr20 pl-sml-10 pr-sml-10">
    <div class="wpx610 w-mid-70 w-sml-100 flex-column gap10">
      <div class="back9 br15 p15 flex-column gap20">
        <h1 class="textc">Verifica tu correo electrónico</h1>

        <?php if ($pendingEmail !== ""): ?>
          <p class="textc x18 text-center">
            Enviamos un enlace de activación a <strong><?= e($pendingEmail) ?></strong>. Haz clic en ese enlace para activar tu cuenta.
          </p>
        <?php else: ?>
          <p class="textc x18 text-center">
            Revisa tu bandeja de entrada o carpeta de spam y haz clic en el enlace de verificación para activar tu cuenta.
          </p>
        <?php endif; ?>

        <form action="/verificar-reenviar" class="w100" method="post">
          <?= class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::csrfField() : '' ?>
          <div class="flex-column gap10">
            <label for="resend-email" class="textc x18 bold500">¿No recibiste el correo? Reenviar enlace
              <input type="email" name="email" id="resend-email" value="<?= e($pendingEmail) ?>" placeholder="Tu correo electrónico" class="color6 br10 mb5 w100" autocomplete="email" required>
            </label>

            <div class="flex-row center-between gap10 mt10 mb10">
              <button type="submit" class="w-auto br15 back2-hover textc p10 pl20 pr20 pointer bold500 border-none">Reenviar enlace</button>
            </div>
          </div>
        </form>
      </div>

      <div class="flex-row center-center gap10">
        <p class="x18">¿Ya verificaste tu cuenta?</p>
        <a href="/ingresar" class="color8-hover x18 bold500">Ingresa aquí.</a>
      </div>
    </div>
  </div>

  <footer class="w100">
    <?php
      _template("Footer.footerUser");
    ?>
  </footer>
</main>
