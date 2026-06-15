<main class="pt20">
  <div class="back5 textc hem25 br15 flex-column center-center">

    <?php if ($connect ?? false) :?>
      <a href="/salir" class="absolute top right p20 color1-hover">Salir</a>
    <?php endif?>
    
    <h1>Hola, <?= $dataUser["full_name"] ?? "Usuario";?></h1>
  </div>
</main>
