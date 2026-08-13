<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $styleBack  = $card["backCard"]["style_back"] ?? $card["backCard"][1] ?? 'solid';
  $backPerfil = $card["backCard"]["back_perfil"] ?? $card["backCard"][0] ?? '#272727';
  $shadowVal   = $card["shadow"] ?? 'shadow-0';
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post">

  <div class="flex-column top-between gap20">

    <p class="bold500 texto">Color general</p>
    <!-- Color de fondo de la aplicación -->
    <div class="flex-row center-between">
      <p class="texto">Color de fondo</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
        <label for="select-color" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color" name="back_perfil" value="<?= $backPerfil?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="x16 bold500 texto"><?= $backPerfil?></p>
        </label>
      </div>
    </div>

    <!-- Color de texto global -->
    <div class="flex-row center-between">
      <p class="texto">Color de texto</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
        <label for="select-color-text-app" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color-text-app" name="colorText" value="<?= $card["colorText"] ?? "#383838"?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker box-color-picker">
          <p class="x16 bold500 texto"><?= $card["colorText"] ?? "#383838"?></p>
        </label>
      </div>
    </div>

    <p class="bold500 mt20 texto">Color del titulo</p>
    <!-- Color del titulo -->
    <div class="flex-row center-between">
      <p class="texto">Color de titulo</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
        <label for="select-color-title" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color-title" name="titleColor" value="<?= $card["titleColor"] ?? "#383838"?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="x16 bold500 texto"><?= $card["titleColor"] ?? "#383838"?></p>
        </label>
      </div>
    </div>
  
    <p class="bold500 mt20 texto">Color de botones</p>
    <!-- Estilo de color de los botones -->
    <div class="flex-row center-between">
      <p class="texto">Color de fondo</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
        <label for="select-color-button" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color-button" name="back" value="<?= $card["back"] ?? "#d6d6d6"?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="x16 bold500 texto"><?= $card["back"] ?? "#d6d6d6"?></p>
        </label>
      </div>
    </div>

    <!-- Estilo de color del texto de los botones -->
    <div class="flex-row center-between">
      <p class="texto">Color de texto</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
        <label for="select-color-text" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color-text" name="color" value="<?= $card["color"] ?? "#494949"?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="x16 bold500 texto"><?= $card["color"] ?? "#494949"?></p>
        </label>
      </div>
    </div>

    <?php if ($shadowVal == "shadow-3"):?>
      <!-- Estilo de color de la sombra shadow-3 -->
      <div class="flex-row center-between">
        <p class="texto">Color de sombra</p>
  
        <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
          <label for="select-color-shadow3" class="flex-row center-start p10 gap10 pointer">
            <input type="color" id="select-color-shadow3" name="colorShadow3" value="<?= $card["colorShadow3"] ?? "#000000"?>" class="color-picker box-color-picker"
            style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
            <p class="x16 bold500 texto"><?= $card["colorShadow3"] ?? "#000000"?></p>
          </label>
        </div>
      </div>
    <?php endif;?>

  </div>
  
  <input type="submit" value="guardar" class="hidden">
</form>