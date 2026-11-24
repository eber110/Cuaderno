<?php
  $resetToken = isset($token) ? (string)$token : "";
  $style = "color6 br10 mb5 w100";
?>
<main class="container-xl flex-column between-center h-dvh text-protected pos-relative background-motion-text" data-bmt-content="Clikhub" data-bmt-rotate="false" data-bmt-move="true" data-bmt-gap="26" data-bmt-size="16" data-bmt-opacity="0.08">
  <?php _menu("Register.menuRegister", ["isLogin" => true]);?>

  <div class="flex-1 flex-column center-center w100 pt100 pt-sml-90 pl20 pr20 pl-sml-10 pr-sml-10">
    <div class="wpx610 w-mid-70 w-sml-100 flex-column gap10">
      <div class="back9 br15 p15 flex-column gap20">
        <h1 class="textc">Crea una nueva contraseña</h1>

        <form action="/restablecer" class="w100" method="post" autocomplete="off">
          <?= class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::csrfField() : '' ?>
          <input type="hidden" name="token" value="<?= e($resetToken) ?>">

          <div class="flex-column gap10">
            <label for="reset-pass" class="textc x18 bold500">Nueva contraseña
              <input type="password" name="pass" id="reset-pass" placeholder="Nueva contraseña" class="<?= $style?>" autocomplete="new-password" required>
              <p class="input-note animate back5 color7 w-fit p2 pl15 pr15 br10 x18 xp16 bold500">Mín 8 caracteres, mín 1 mayúscula y mín 1 número</p>
            </label>

            <label for="reset-repass" class="textc x18 bold500">Repite tu nueva contraseña
              <input type="password" name="repass" id="reset-repass" placeholder="Repita la contraseña" class="<?= $style?>" autocomplete="new-password" required>
            </label>

            <div class="flex-row center-between gap10 mt10 mb10">
              <button type="submit" class="w-auto br15 back2-hover textc p10 pl20 pr20 pointer bold500 border-none">Guardar contraseña</button>
            </div>
          </div>
        </form>
      </div>

      <div class="flex-row center-center gap10">
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
