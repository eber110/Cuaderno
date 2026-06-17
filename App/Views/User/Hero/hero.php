<main class="">
  <div class="back5 textc hem15 br15 flex-column center-center">

    <?php if ($connect ?? false) :?>
      <div class="absolute top right p20 flex-column top-end">
        <a href="/configuracion/<?= $dataUser["username"] ?? "Usuario";?>" class="textc">Configuración</a>
        <a href="/salir" class="textc">Salir</a>
      </div>
    <?php endif?>
    
    <h1>Hola, <?= $dataUser["full_name"] ?? "Usuario";?></h1>

  </div>
</main>
