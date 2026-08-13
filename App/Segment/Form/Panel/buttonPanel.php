<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $buttonStyle = $card["style"] ?? 'buttonRegular';
  $borderVal   = $card["borders"][0] ?? 'br0';
  $shadowVal   = $card["shadow"] ?? 'shadow-0';
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post">

  <div class="flex-column top-between gap20 w100">
    
    <!-- Estilo general de los botones -->
    <div class="flex-column top-start gap10 w100">
      <p class="texto">Estilo de botones</p>

      <div class="flex-row center-end gap10 w100">
        <input type="radio" id="button-regular" name="style" class="hidden-radio" value="buttonRegular" <?php if ($buttonStyle == "buttonRegular" || $buttonStyle == "Regular") echo "checked";?>>
        <label for="button-regular">
          <div class="back-card-graphic shadow-card-graphic hover-scale-soft p5 br20 flex-column center-center gap5 pointer">
            <div class="button-style-background flex-row center-center">
              <div class="<?= $borderVal?> button-fill-style"></div>
            </div>
            <p class="x16 texto">Solido</p>
          </div>
        </label>
      </div>
    </div>

    <!-- Curvatura de las esquinas -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="texto">Redondeo de esquinas</p>

      <div class="flex-row center-end gap20">
      
        <div class="">
          <input type="radio" id="square" name="borders" value="br0,br0" class="hidden-radio" <?php if ($borderVal == "br0") echo "checked";?>>
          <label for="square" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="x16 bold500 texto wpx25 hpx25 flex-row center-center">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="round1" name="borders" value="br10,br5" class="hidden-radio" <?php if ($borderVal == "br10" || $borderVal == "br5") echo "checked";?>>
          <label for="round1" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="x16 bold500 texto wpx25 hpx25 flex-row center-center">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V8C4 5.79086 5.79086 4 8 4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="round2" name="borders" value="br20,br12" class="hidden-radio" <?php if ($borderVal == "br20") echo "checked";?>>
          <label for="round2" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="x16 bold500 texto wpx25 hpx25 flex-row center-center">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V12C4 7.58172 7.58172 4 12 4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="round3" name="borders" value="br50,br50" class="hidden-radio" <?php if ($borderVal == "br50") echo "checked";?>>
          <label for="round3" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="x16 bold500 texto wpx25 hpx25 flex-row center-center">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V12C4 7.58172 7.58172 4 12 4H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </p>
          </label>
        </div>

      </div>

    </div>

    <!-- Estilos de sombreado de los botones -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="texto">Estilo de sombra</p>

      <div class="flex-row center-end gap20">

        <div class="">
          <input type="radio" id="shadow0" name="shadow" value="shadow-0" class="hidden-radio" <?php if ($shadowVal == "shadow-0") echo "checked";?>>
          <label for="shadow0" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="x16 bold500 texto wpx25 hpx25 flex-row center-center">
              0
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="shadow1" name="shadow" value="shadow-1" class="hidden-radio" <?php if ($shadowVal == "shadow-1") echo "checked";?>>
          <label for="shadow1" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="x16 bold500 texto wpx25 hpx25 flex-row center-center">
              1
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="shadow2" name="shadow" value="shadow-2" class="hidden-radio" <?php if ($shadowVal == "shadow-2") echo "checked";?>>
          <label for="shadow2" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="x16 bold500 texto wpx25 hpx25 flex-row center-center">
              2
            </p>
          </label>
        </div>
  
        <div class="">
          <input type="radio" id="shadow3" name="shadow" value="shadow-3" class="hidden-radio" <?php if ($shadowVal == "shadow-3") echo "checked";?>>
          <label for="shadow3" class="flex-row center-start p10 gap10 pointer back-card-graphic shadow-card-graphic hover-scale-soft br15">
            <p class="x16 bold500 texto wpx25 hpx25 flex-row center-center">
              3
            </p>
          </label>
        </div>

      </div>

    </div>

    <!-- Estilo de color de los botones -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
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
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
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
      <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
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

    <!-- Habilita la animación hover en los botones -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="texto">Activar hover</p>

      <input type="checkbox" name="hover" id="" value="true" data-option="true,false" active="<?= (!empty($card["hover"])) ? '1' : '2' ?>" class="checkbox-switch">
    </div>

  </div>

  <input type="submit" value="guardar" class="hidden">
</form>