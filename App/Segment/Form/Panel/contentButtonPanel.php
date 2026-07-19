<?php
  /** @var mixed $card */
  $selected = "border-selected-item";
  $cant = count($card["content"]);
?>
<form class="auto-submit w100" action="/test/1/" method="post" enctype="multipart/form-data">

  <div class="flex-column top-center gap20">

    <!-- Botones Iniciadores para Añadir Contenido -->
    <div class="flex-column top-start gap10 w100">
      <p class="bold500 x16">Añadir nuevo elemento</p>
      <div class="flex-row center-start gap10 w100 wrap">
        <button type="submit" name="add_content_type" value="link" class="p10 pl15 pr15 br20 border-item-panel pointer flex-row center-center gap5 bold500 textb" style="background: transparent;">
          <?= svg("add") ?> Enlace
        </button>
        <button type="submit" name="add_content_type" value="product" class="p10 pl15 pr15 br20 border-item-panel pointer flex-row center-center gap5 bold500 textb" style="background: transparent;">
          <?= svg("add") ?> Producto
        </button>
      </div>
    </div>

    <!-- Lista de elementos existentes -->
    <?php for ($i=0; $i < $cant; $i++) :
      $itemType   = $card["content"][$i][0] ?? 'link';
      $itemImg    = $card["content"][$i][1] ?? '';
      $itemTitle  = $card["content"][$i][2] ?? '';
      $itemUrl    = $card["content"][$i][3] ?? '';
      $rawActive  = $card["content"][$i][4] ?? true;
      $itemActive = ($rawActive === true || $rawActive === 'true' || $rawActive === 1 || $rawActive === '1');
    ?>
      <div id="content-item-<?= $i?>" class="link border-item-panel flex-column gap10 w100 p20 br15">
        <div class="flex-row center-between">
          <p class="bold500">
            <?= ($itemType === 'product') ? 'Producto #' . ($i + 1) : 'Enlace #' . ($i + 1) ?>
          </p>
          <div class="flex-row center-center gap15">
            <!-- Switch para activar / desactivar enlace -->
            <input type="checkbox" name="content[<?= $i?>][4]" value="true" data-option="true,false" class="checkbox-switch" active="<?= $itemActive ? '1' : '2' ?>" <?= $itemActive ? 'checked' : '' ?>>
            
            <!-- Opción para eliminar enlace -->
            <label class="pointer flex-row center-center gap5 x14 text-caution">
              <input type="checkbox" name="content[<?= $i?>][delete]" value="true"> Eliminar
            </label>
          </div>
        </div>

        <input type="hidden" name="content[<?= $i?>][0]" value="<?= e($itemType) ?>">
        <input type="hidden" name="content[<?= $i?>][1]" value="<?= e($itemImg) ?>">

        <div class="flex-row center-between gap10">
          <p class="x14 bold500">Imagen</p>
          <div class="br15 p10 border-item-panel">
            <input type="file" 
              name="content_img_<?= $i ?>" 
              class="selectAndCropImage btn-style-classes no-preview process-auto-submit"
              placeholder="Elige una imagen" 
              cropping-size="500x500"
              box-image="back-menu-sidebar textb br15 border-item-panel p20 shadow-1"
              box-btn-image="p10 back7 border-item-panel textb br15 pointer">
          </div>
        </div>

        <input type="text" name="content[<?= $i?>][2]" class="border-item-panel br10 p10" value="<?= e($itemTitle) ?>" placeholder="Título">
        <input type="text" name="content[<?= $i?>][3]" class="border-item-panel br10 p10" value="<?= e($itemUrl) ?>" placeholder="URL">
      </div>
    <?php endfor?>

    <input type="submit" value="Guardar" class="hidden">
  </div>

</form>