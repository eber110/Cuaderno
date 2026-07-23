<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $selected = "border-selected-item";
  $styleBack  = $card["backCard"]["style_back"] ?? $card["backCard"][1] ?? 'solid';
  $backPerfil = $card["backCard"]["back_perfil"] ?? $card["backCard"][0] ?? '#272727';
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post">

  <div class="flex-column top-between gap20">

    <!-- Estilo del fondo, opciones de como mostrar el fondo -->
    <div class="flex-column top-start gap10 w100">
      <p>Estilo del fondo</p>

      <div class="flex-row center-end gap10 w100">
        
        <input type="radio" id="style_gradient" name="style_back" class="hidden-radio" value="gradientDown" <?php if ($styleBack == "gradientUp" || $styleBack == "gradientDown") echo "checked";?>>
        <label for="style_gradient">
          <div class="border-item-panel p5 br20 flex-column center-center gap5 <?php if ($styleBack == "gradientUp" || $styleBack == "gradientDown") echo $selected;?>">
            <div class="hpx80 wpx80 br15" style="background: linear-gradient(180deg,
              oklch(from <?= $backPerfil?> calc(l * 0.60) calc(c - 0.01) h / 88%),
              oklch(from <?= $backPerfil?> calc(l * 1.15) calc(c - 0.03) calc(h - 30) / 90%)
              );"></div>
            <p class="x16">Degradado</p>
          </div>
        </label>
    
        <input type="radio" id="style_solid" name="style_back" class="hidden-radio" value="solid" <?php if ($styleBack == "solid") echo "checked";?>>
        <label for="style_solid">
          <div class="border-item-panel p5 br20 flex-column center-center gap5 <?php if ($styleBack == "solid") echo $selected;?>">
            <div class="hpx80 wpx80 br15" style="background-color: <?= $backPerfil?>;"></div>
            <p class="x16">Solido</p>
          </div>
        </label>
  
      </div>
    </div>

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
  
    <!-- Dirección del degradado -->
    <?php if ($styleBack == "gradientUp" || $styleBack == "gradientDown") :?>

      <div class="flex-row center-between">

        <p>Dirección del degradado</p>
  
        <div class="flex-row center-end gap10">
          <input type="radio" id="direction_up" name="style_back" class="hidden-radio" value="gradientUp" <?php if ($styleBack == "gradientUp") echo "checked";?>>
          <label for="direction_up">
            <div class="br15 p10 border-item-panel <?php if ($styleBack == "gradientUp") echo $selected;?>">
              <?= svg("arrow-up");?> Arriba
            </div>
          </label>
          
          <input type="radio" id="direction_down" name="style_back" class="hidden-radio" value="gradientDown" <?php if ($styleBack == "gradientDown") echo "checked";?>>
          <label for="direction_down">
            <div class="br15 p10 border-item-panel <?php if ($styleBack == "gradientDown") echo $selected;?>">
              <?= svg("arrow-down");?> Abajo
            </div>
          </label>
        </div>
  
      </div>

    <?php endif?>

  </div>
  
  <input type="submit" value="guardar" class="hidden">
</form>