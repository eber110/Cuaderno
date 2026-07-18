<div class="">

  <?php if ($connect ?? false == true) :?>
    <div class="flex-row center-between">
      <a href="/panel/<?= $username ?? "Usuario";?>" class="color-text-card back-item-menu">Configuración <?= svg("gear");?></a>
      <a href="/salir" class="color-text-card back-item-menu">Salir <?= svg("out");?></a>
    </div>
  <?php else :?>
    <?php if (!\Base\Module\Session::session_active()) :?>
      <div class="flex-row center-between">
        <a href="/ingresar" class="color-text-card back-item-menu flex-row center-center ar-square"><?= svg("user");?></a>
        <!-- Integrar el modal para registrarse e ingresar al perfil -->
      </div>
    <?php else :?>
      <div class="flex-row center-between">
        <a href="/" class="color-text-card back-item-menu"><?= svg("arrow-l-l");?> Volver</a>
      </div>
    <?php endif?>
  <?php endif?>

</div>