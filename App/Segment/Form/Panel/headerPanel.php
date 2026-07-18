<?php
  /** @var mixed $card */
  $selected = "border-selected-item";
?>
<form class="auto-submit w100" action="/test/1" method="post" enctype="multipart/form-data">

  <div class="flex-column top-between gap20">
    
    <!-- Estilo de las cabeceras disponibles -->
    <div class="flex-column top-start gap10 w100">
      <p>Estilo de cabecera</p>

      <div class="flex-row center-end gap10 w100">

        <div class="flex-row center-end gap10">
          <input type="radio" id="regularHeader" name="header" class="hidden-radio" value="regularHero" <?php if ($card["header"] == "regularHero") echo "checked";?>>
          <label for="regularHeader">
            <div class="border8 p15 br20 flex-column center-center gap5 pointer <?php if ($card["header"] == "regularHero") echo $selected;?>">
              <div class="hpx70 wpx70 flex-column center-center gap10">
                <figure class="hpx50 br50">
                  <img src="<?= DIR_SHOW_MEDIA."Custom/".$card["avatar"]?>" alt="" class="cover ar-square">
                </figure>  
              </div>
              <p class="x16">Regular</p>
            </div>
          </label>
        </div>
  
        <div class="flex-row center-end gap10">
          <input type="radio" id="midHeader" name="header" class="hidden-radio" value="midHero" <?php if ($card["header"] == "midHero") echo "checked";?>>
          <label for="midHeader">
            <div class="border8 p15 br20 flex-column center-center gap5 pointer <?php if ($card["header"] == "midHero") echo $selected;?>">
              <div class="hpx70 wpx70 flex-column center-center gap10">
                <figure class="brtl10 brtr10 hpx70 wpx70 faded-image">
                  <img src="<?= DIR_SHOW_MEDIA."Custom/".$card["avatar"]?>" alt="" class="cover">
                </figure>  
              </div>
              <p class="x16">Hero</p>
            </div>
          </label>
        </div>

      </div>
    </div>

    <!-- Curvatura de las esquinas -->
    <div class="flex-row center-between">
      <p>Imagen de perfil</p>

      <div class="br15 p15 border8">
        <input type="file" 
        name="avatar" 
        class="selectAndCropImage btn-style-classes no-preview process-auto-submit"
        placeholder="Elige una imagen" 
        cropping-size="500x500"
        box-image="back-menu-sidebar textb br15 border8 p20 shadow-1"
        box-btn-image="p10 back7 border8 textb br15 pointer">
      </div>

    </div>

  </div>  

  <input type="submit" value="guardar" class="hidden">
</form>