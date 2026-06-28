<div class="p20 pt30 pb30">

  <?php if ($connect ?? false == true) :?>
    <div class="flex-row center-between">
      <a href="/panel/<?= $username ?? "Usuario";?>" class="color-text-card">Configuración <?= svg("gear");?></a>
      <a href="/salir" class="color-text-card">Salir <?= svg("out");?></a>
    </div>
  <?php else :?>
    <?php if (!\Base\Module\Session::session_active()) :?>
      <div class="flex-row center-between">
        <a href="/ingresar" class="color-text-card">Ingresar <?= svg("user");?></a>
      </div>
    <?php else :?>
      <div class="flex-row center-between">
        <a href="/" class="color-text-card"><?= svg("arrow-l-l");?> Volver</a>
      </div>
    <?php endif?>
  <?php endif?>

</div>