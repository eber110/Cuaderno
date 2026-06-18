<?php if ($connect ?? false == true) :?>
  <div class="flex-column top-end">
    <a href="/panel/<?= $username ?? "Usuario";?>" class="textc">Configuración</a>
    <a href="/salir" class="textc">Salir</a>
  </div>
<?php else :?>
  <?php if (!\Base\Module\Session::session_active()) :?>
    <div class="flex-column top-end">
      <a href="/ingresar" class="textc">Ingresar</a>
    </div>
  <?php else :?>
    <div class="flex-column top-end">
      <a href="/" class="textc">Volver</a>
    </div>
  <?php endif?>
<?php endif?>