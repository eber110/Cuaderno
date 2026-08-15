<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $headerVal = $card["header"] ?? 'regularHero';
  $avatarVal = $card["avatar"] ?? 'no-user.webp';
  $avatarSrc = $card["avatarSrc"] ?? '';
  $voidSpaceVal = $card["voidHero"]["space"] ?? ($card["void_space"] ?? 450);
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post" enctype="multipart/form-data">

  <div class="flex-column top-between gap20">
    
    <!-- Estilo de las cabeceras disponibles -->
    <div class="flex-column top-start gap10 w100">
      <p class="texto">Estilo de cabecera</p>

      <div class="flex-row center-end gap10 w100">

        <!-- Opción Vacío (voidHero) -->
        <div class="flex-row center-end gap10">
          <input type="radio" id="voidHeader" name="header" class="hidden-radio" value="voidHero" <?php if ($headerVal == "voidHero") echo "checked";?>>
          <label for="voidHeader">
            <div class="back-card-graphic shadow-card-graphic hover-scale-soft p15 br20 flex-column center-center gap5 pointer">
              <div class="hpx70 wpx70 flex-column center-center gap10">
                <div class="hpx50 wpx50 br15 flex-column center-center">
                  <span class="opacity-60"><?= svg("ban", "x50")?></span>
                </div>
              </div>
              <p class="x16 texto">Vacío</p>
            </div>
          </label>
        </div>

        <!-- Opción Regular (regularHero) -->
        <div class="flex-row center-end gap10">
          <input type="radio" id="regularHeader" name="header" class="hidden-radio" value="regularHero" <?php if ($headerVal == "regularHero") echo "checked";?>>
          <label for="regularHeader">
            <div class="back-card-graphic shadow-card-graphic hover-scale-soft p15 br20 flex-column center-center gap5 pointer">
              <div class="hpx70 wpx70 flex-column center-center gap10">
                <figure class="hpx50 br50">
                  <img src="<?= $avatarSrc ?>" alt="" class="cover ar-square">
                </figure>  
              </div>
              <p class="x16 texto">Regular</p>
            </div>
          </label>
        </div>
  
        <!-- Opción Hero (midHero) -->
        <div class="flex-row center-end gap10">
          <input type="radio" id="midHeader" name="header" class="hidden-radio" value="midHero" <?php if ($headerVal == "midHero") echo "checked";?>>
          <label for="midHeader">
            <div class="back-card-graphic shadow-card-graphic hover-scale-soft p15 br20 flex-column center-center gap5 pointer">
              <div class="hpx70 wpx70 flex-column center-center gap10">
                <figure class="brtl10 brtr10 hpx70 wpx70 faded-image">
                  <img src="<?= $avatarSrc ?>" alt="" class="cover">
                </figure>  
              </div>
              <p class="x16 texto">Hero</p>
            </div>
          </label>
        </div>

      </div>
    </div>

    <!-- Separaciones superiores de voidHero (solo visible cuando header == voidHero) -->
    <div id="void-space-container" class="flex-column top-start gap10 w100" style="<?php if ($headerVal !== 'voidHero') echo 'display: none;'; ?>">
      <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
        <p class="texto">Separación superior</p>

        <div class="flex-row center-end gap10">
          
          <!-- Separación 1: 20% (Alto) -->
          <div class="flex-row center-end gap10">
            <input type="radio" id="voidSpace20" name="void_space" class="hidden-radio" value="20" <?php if ($voidSpaceVal == 20 || $voidSpaceVal == 130) echo "checked";?>>
            <label for="voidSpace20">
              <div class="back-card-graphic shadow-card-graphic hover-scale-soft p10 br15 pointer flex-column center-center gap2 wpx70">
                <span class="x14 bold600 texto">20%</span>
                <span class="x11 texto opacity-70">Alto</span>
              </div>
            </label>
          </div>

          <!-- Separación 2: 45% (Medio) -->
          <div class="flex-row center-end gap10">
            <input type="radio" id="voidSpace45" name="void_space" class="hidden-radio" value="45" <?php if ($voidSpaceVal == 45 || $voidSpaceVal == 250) echo "checked";?>>
            <label for="voidSpace45">
              <div class="back-card-graphic shadow-card-graphic hover-scale-soft p10 br15 pointer flex-column center-center gap2 wpx70">
                <span class="x14 bold600 texto">45%</span>
                <span class="x11 texto opacity-70">Medio</span>
              </div>
            </label>
          </div>

          <!-- Separación 3: 70% (Bajo) -->
          <div class="flex-row center-end gap10">
            <input type="radio" id="voidSpace70" name="void_space" class="hidden-radio" value="70" <?php if ($voidSpaceVal == 70 || $voidSpaceVal == 450 || empty($voidSpaceVal) || !in_array($voidSpaceVal, [20, 45, 70, 130, 250, 450])) echo "checked";?>>
            <label for="voidSpace70">
              <div class="back-card-graphic shadow-card-graphic hover-scale-soft p10 br15 pointer flex-column center-center gap2 wpx70">
                <span class="x14 bold600 texto">70%</span>
                <span class="x11 texto opacity-70">Bajo</span>
              </div>
            </label>
          </div>

        </div>
      </div>
    </div>

    <!-- Imagen de perfil -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="texto">Imagen de perfil</p>

      <div class="br15 p15 back-card-graphic shadow-card-graphic hover-scale-soft">
        <input type="file" 
        name="avatar" 
        class="selectAndCropImage btn-style-classes no-preview process-auto-submit"
        placeholder="Elige una imagen" 
        cropping-size="500x500"
        box-image="back-menu-sidebar texto br15 back-card-graphic shadow-card-graphic hover-scale-soft p20 shadow-1"
        box-btn-image="p10 back7 back-card-graphic shadow-card-graphic hover-scale-soft texto br15 pointer">
      </div>

    </div>

    <!-- Titulo del perfil -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="texto">Titulo</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft br15 p5">
        <input type="text" name="title" id="" value="<?= e($card["title"] ?? "")?>" class="process-auto-submit wpx320 text-limiter texto"
          data-limit-type="chars" 
          data-limit="150" 
          data-prevent-double-space="true"
          data-counter-el="#my-counter-title">
          <div id="my-counter-title" class="x14 flex-row center-end gap2 pr20 texto">
            <span class="tl-current bold500">0</span>
            <p>/</p>
            <span class="tl-limit">150</span>
          </div>
      </div>
    </div>

    <!-- Descripción del perfil -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="texto">Bio</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft br15 p5">
        <textarea name="desc" id="" class="process-auto-submit wpx320 hpx100 text-limiter texto"
          data-limit-type="chars" 
          data-limit="280" 
          data-prevent-double-space="true"
          data-counter-el="#my-counter"><?= e($card["desc"] ?? "")?>
        </textarea>
        <div id="my-counter" class="x14 flex-row center-end gap2 pr20 texto">
          <span class="tl-current bold500">0</span>
          <p>/</p>
          <span class="tl-limit">280</span>
        </div>
      </div>
    </div>

    <!-- Estilo de color del texto de los botones -->
    <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
      <p class="texto">Color de titulo</p>

      <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
        <label data-trigger-color="select-color-title" class="flex-row center-start p10 gap10 pointer">
          <input type="color" id="select-color-title" name="titleColor" value="<?= $card["titleColor"] ?? "#383838"?>" class="color-picker box-color-picker"
          style-color="wpx40 hpx40 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
          <p class="x16 bold500 texto"><?= $card["titleColor"] ?? "#383838"?></p>
        </label>
      </div>
    </div>

  </div>  

  <input type="submit" value="guardar" class="hidden">
</form>