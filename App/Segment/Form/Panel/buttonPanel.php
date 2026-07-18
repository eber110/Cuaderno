<?php
  /** @var mixed $card */
  $selected = "border-selected-item";
?>
<form class="auto-submit w100" action="/test/1" method="post">

  <div class="flex-column top-between gap20">
    
    <!-- Estilo general de los botones -->
    <div class="flex-column top-start gap10 w100">
      <p>Estilo de botones</p>

      <div class="flex-row center-end gap10 w100">
        <input type="radio" id="button-solid" name="style" class="hidden-radio" value="Regular" <?php if ($card["style"] == "Regular") echo "checked";?>>
        <label for="button-solid">
          <div class="border8 p5 br20 flex-column center-center gap5 pointer <?php if ($card["style"] == "Regular") echo $selected;?>">
            <div class="button-style-background flex-row center-center">
              <div class="<?= $card["borders"][0]?> button-fill-style"></div>
            </div>
            <p class="x16">Solido</p>
          </div>
        </label>
      </div>
    </div>

    <!-- Curvatura de las esquinas -->
    <div class="flex-row center-between">
      <p>Redondeo de esquinas</p>

      <div class="flex-row center-end gap20">
      
        <div class="">
          <label for="square" class="flex-row center-start p10 gap10 pointer border8 br15 <?php if ($card["borders"][0] == "br0") echo $selected;?>">
          <input type="radio" id="square" name="borders" value="br0,br0" class="hidden-radio" <?php if ($card["borders"][0] == "br0") echo "checked";?>>
            <p class="x16 bold500 textb wpx25 hpx25 flex-row center-center">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </p>
          </label>
        </div>
  
        <div class="">
          <label for="round1" class="flex-row center-start p10 gap10 pointer border8 br15 <?php if ($card["borders"][0] == "br10") echo $selected;?>">
          <input type="radio" id="round1" name="borders" value="br10,br5" class="hidden-radio" <?php if ($card["borders"][0] == "br10") echo "checked";?>>
            <p class="x16 bold500 textb wpx25 hpx25 flex-row center-center">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V8C4 5.79086 5.79086 4 8 4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="round2" name="borders" value="br20,br12" class="hidden-radio" <?php if ($card["borders"][0] == "br20") echo "checked";?>>
          <label for="round2" class="flex-row center-start p10 gap10 pointer border8 br15 <?php if ($card["borders"][0] == "br20") echo $selected;?>">
            <p class="x16 bold500 textb wpx25 hpx25 flex-row center-center">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V12C4 7.58172 7.58172 4 12 4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="round3" name="borders" value="br50,br50" class="hidden-radio" <?php if ($card["borders"][0] == "br50") echo "checked";?>>
          <label for="round3" class="flex-row center-start p10 gap10 pointer border8 br15 <?php if ($card["borders"][0] == "br50") echo $selected;?>">
            <p class="x16 bold500 textb wpx25 hpx25 flex-row center-center">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V12C4 7.58172 7.58172 4 12 4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </p>
          </label>
        </div>

      </div>

    </div>

    <!-- Estilos de sombreado de los botones -->
    <div class="flex-row center-between">
      <p>Estilo de sombra</p>

      <div class="flex-row center-end gap20">

        <div class="">
          <label for="shadow0" class="flex-row center-start p10 gap10 pointer border8 br15 <?php if ($card["shadow"] == "shadow-0") echo $selected;?>">
          <input type="radio" id="shadow0" name="shadow" value="shadow-0" class="hidden-radio" <?php if ($card["shadow"] == "shadow-0") echo "checked";?>>
            <p class="x16 bold500 textb wpx25 hpx25 flex-row center-center">
              0
            </p>
          </label>
        </div>
  
        <div class="">
          <label for="shadow1" class="flex-row center-start p10 gap10 pointer border8 br15 <?php if ($card["shadow"] == "shadow-1") echo $selected;?>">
          <input type="radio" id="shadow1" name="shadow" value="shadow-1" class="hidden-radio" <?php if ($card["shadow"] == "shadow-1") echo "checked";?>>
            <p class="x16 bold500 textb wpx25 hpx25 flex-row center-center">
              1
            </p>
          </label>
        </div>
  
        <div class="">
          <label for="shadow2" class="flex-row center-start p10 gap10 pointer border8 br15 <?php if ($card["shadow"] == "shadow-2") echo $selected;?>">
          <input type="radio" id="shadow2" name="shadow" value="shadow-2" class="hidden-radio" <?php if ($card["shadow"] == "shadow-2") echo "checked";?>>
            <p class="x16 bold500 textb wpx25 hpx25 flex-row center-center">
              2
            </p>
          </label>
        </div>
  
        <div class="">
          <label for="shadow3" class="flex-row center-start p10 gap10 pointer border8 br15 <?php if ($card["shadow"] == "shadow-3") echo $selected;?>">
          <input type="radio" id="shadow3" name="shadow" value="shadow-3" class="hidden-radio" <?php if ($card["shadow"] == "shadow-3") echo "checked";?>>
            <p class="x16 bold500 textb wpx25 hpx25 flex-row center-center">
              3
            </p>
          </label>
        </div>

      </div>

    </div>

    <!-- Estilo de color de los botones -->
    <div class="flex-row center-between">
      <p>Color de fondo</p>

      <div class="border8 wpx140 br15">
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

      <div class="border8 wpx140 br15">
        <label for="select-color-text" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color-text" name="color" value="<?= $card["color"]?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="x16 bold500 textb"><?= $card["color"]?></p>
        </label>
      </div>
    </div>

    <?php if ($card["shadow"] == "shadow-3"):?>
      <!-- Estilo de color de la sombra shadow-3 -->
      <div class="flex-row center-between">
        <p>Color de sombra</p>
  
        <div class="border8 wpx140 br15">
          <label for="select-color-shadow3" class="flex-row center-start p10 gap10 pointer">
            <input type="color" id="select-color-shadow3" name="colorShadow3" value="<?= $card["colorShadow3"]?>" class="color-picker box-color-picker"
            style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
            <p class="x16 bold500 textb"><?= $card["colorShadow3"]?></p>
          </label>
        </div>
      </div>
    <?php endif;?>

    <!-- Habilita la animación hover en los botones -->
    <div class="flex-row center-between">
      <p>Activar hover</p>

      <input type="checkbox" name="hover" id="" value="true" data-option="true,false" active="<?= ($card["hover"]) ? '1' : '2' ?>" class="checkbox-switch">
    </div>

  </div>

  <input type="submit" value="guardar" class="hidden">
</form>