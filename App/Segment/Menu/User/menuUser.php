<?php if ($connect ?? false == true) :?>
  <div class="flex-column top-end">
    <a href="/panel/<?= $username ?? "Usuario";?>" class="textc">Configuración <?= svg("gear");?></a>
    <a href="/salir" class="textc">Salir <?= svg("out");?></a>
  </div>
<?php else :?>
  <?php if (!\Base\Module\Session::session_active()) :?>
    <div class="flex-column top-end">
      <a href="/ingresar" class="textc">Ingresar <?= svg("user");?></a>
    </div>
  <?php else :?>
    <div class="flex-column top-end">
      <a href="/" class="textc"><?= svg("arrow-l-l");?> Volver</a>
    </div>
  <?php endif?>
<?php endif?>