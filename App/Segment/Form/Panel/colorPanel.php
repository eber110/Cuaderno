<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $selected = "border-selected-item";
  $styleBack  = $card["backCard"]["style_back"] ?? $card["backCard"][1] ?? 'solid';
  $backPerfil = $card["backCard"]["back_perfil"] ?? $card["backCard"][0] ?? '#272727';
  $shadowVal   = $card["shadow"] ?? 'shadow-0';
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post">

  <div class="flex-column top-between gap20">

    <p class="bold500">Color general</p>
    <!-- Color de fondo de la aplicación -->
    <div class="flex-row center-between">
      <p>Color de fondo</p>

      <div class="border-item-panel wpx140 br15">
        <label for="select-color" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color" name="back_perfil" value="<?= $backPerfil?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="x16 bold500 textb"><?= $backPerfil?></p>
        </label>
      </div>
    </div>

    <!-- Color de texto global -->
    <div class="flex-row center-between">
      <p>Color de texto</p>

      <div class="border-item-panel wpx140 br15">
        <label for="select-color-text-app" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color-text-app" name="colorText" value="<?= $card["colorText"]?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker box-color-picker">
          <p class="x16 bold500 textb"><?= $card["colorText"]?></p>
        </label>
      </div>
    </div>

    <p class="bold500 mt20">Color del titulo</p>
    <!-- Color del titulo -->
    <div class="flex-row center-between">
      <p>Color de titulo</p>

      <div class="border-item-panel wpx140 br15">
        <label for="select-color-title" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color-title" name="titleColor" value="<?= $card["titleColor"]?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="x16 bold500 textb"><?= $card["titleColor"]?></p>
        </label>
      </div>
    </div>
  
    <p class="bold500 mt20">Color de botones</p>
    <!-- Estilo de color de los botones -->
    <div class="flex-row center-between">
      <p>Color de fondo</p>

      <div class="border-item-panel wpx140 br15">
        <label for="select-color-button" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color-button" name="back" value="<?= $card["back"]?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="x16 bold500 textb"><?= $card["back"]?></p>
        </label>
      </div>
    </div>

    <!-- Estilo de color del texto de los botones -->
    <div class="flex-row center-between">
      <p>Color de texto</p>

      <div class="border-item-panel wpx140 br15">
        <label for="select-color-text" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color-text" name="color" value="<?= $card["color"]?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="x16 bold500 textb"><?= $card["color"]?></p>
        </label>
      </div>
    </div>

    <?php if ($shadowVal == "shadow-3"):?>
      <!-- Estilo de color de la sombra shadow-3 -->
      <div class="flex-row center-between">
        <p>Color de sombra</p>
  
        <div class="border-item-panel wpx140 br15">
          <label for="select-color-shadow3" class="flex-row center-start p10 gap10 pointer">
            <input type="color" id="select-color-shadow3" name="colorShadow3" value="<?= $card["colorShadow3"]?>" class="color-picker box-color-picker"
            style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
            <p class="x16 bold500 textb"><?= $card["colorShadow3"]?></p>
          </label>
        </div>
      </div>
    <?php endif;?>

  </div>
  
  <input type="submit" value="guardar" class="hidden">
</form>