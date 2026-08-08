<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $selected = "";
  $cant = is_array($card["content"] ?? null) ? count($card["content"]) : 0;
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post" enctype="multipart/form-data">

  <div class="flex-column top-center gap20">

    <!-- Botones Iniciadores para Añadir Contenido -->
    <div class="flex-column top-start gap10 w100">
      <p class="bold500 x16">Añadir nuevo elemento</p>
      <div class="flex-row center-start gap10 w100 wrap">
        <button type="submit" name="add_content_type" value="link" class="p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 textb" style="border: none;">
          <?= svg("add") ?> Enlace
        </button>
        <button type="submit" name="add_content_type" value="product" class="p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 textb" style="border: none;">
          <?= svg("add") ?> Producto
        </button>
      </div>
    </div>

    <!-- Lista de elementos existentes (Sortable Drag & Drop) -->
    <div id="sortable-content-list" class="flex-column gap20 w100">
      <?php for ($i=0; $i < $cant; $i++) :
        $itemType   = $card["content"][$i]["type"] ?? 'link';
        $itemImg    = $card["content"][$i]["img"] ?? '';
        $itemTitle  = $card["content"][$i]["title"] ?? '';
        $itemUrl    = $card["content"][$i]["url"] ?? '';
        if ($itemUrl !== '' && !preg_match('#^https?://#i', $itemUrl) && strpos($itemUrl, 'mailto:') !== 0 && strpos($itemUrl, 'tel:') !== 0) {
          $itemUrl = "https://" . $itemUrl;
        }
        $rawActive  = $card["content"][$i]["active"] ?? false;
        $rawImgDef  = $card["content"][$i]["imgDefault"] ?? false;
        $imgDefault = ($rawImgDef === true || $rawImgDef === 'true' || $rawImgDef === 1 || $rawImgDef === '1');
        $rawImgShow = $card["content"][$i]["imgShow"] ?? true;
        $imgShow    = ($rawImgShow === true || $rawImgShow === 'true' || $rawImgShow === 1 || $rawImgShow === '1');
        
        // Si el título o la URL están vacíos, no se puede activar y permanece inactivo (false)
        $isEmpty = (trim($itemTitle) === '' || trim($itemUrl) === '');
        $itemActive = $isEmpty ? false : ($rawActive === true || $rawActive === 'true' || $rawActive === 1 || $rawActive === '1');
      ?>
        <div id="content-item-<?= $i?>" class="sortable-item link back-card-graphic shadow-card-graphic |hover-scale-soft flex-column gap10 w100 p20 br15 <?php if ($itemActive) echo $selected; ?>" draggable="true">
          <div class="flex-row center-between w100">
            <div class="flex-row top-start gap10 pointer drag-handle w100">
              <span class="flex-row top-center drag-icon text-muted pointer" title="Arrastrar para reordenar" style="cursor: grab; font-size: 18px; user-select: none;">&#x22EE;&#x22EE;</span>
              <p class="bold500 item-title-label w100">
                <?= ($itemType === 'product') ? 'Producto - ' . \Base\Module\TextModule::truncateRaw($itemTitle, 3 ,"...") : 'Enlace - ' . \Base\Module\TextModule::truncateRaw($itemTitle, 3 ,"...") ?>
              </p>
            </div>
            <div class="flex-row center-end gap20 tooltip left animated wpx300 wpx-sml-270" data-tooltip="Ocultar enlace">
              <!-- Switch para activar / desactivar enlace -->
              <input type="checkbox" name="content[<?= $i?>][active]" value="true" data-option="true,false" class="checkbox-switch" active="<?= $itemActive ? '1' : '2' ?>" <?= $itemActive ? 'checked' : '' ?> <?= $isEmpty ? 'disabled' : '' ?>>
              
              <!-- Opción para eliminar enlace -->
              <div class="modal-btn tooltip left" data-tooltip="Borrar enlace">
                <p class="pointer flex-row center-center">
                  Eliminar <span class="flex-row center-center br50 back-danger back-danger-hover textw p2 x16 ml5"><?= svg("xmark")?></span>
                </p>
              </div>

              <!-- modal menu eliminar enlace -->
              <div class="hidden">
                <div class="w100 flex-column center-center h-dvh">
                  <div class="flex-column gap20 wpx520 w-sml-100 back-card-graphic p20 br15">
                    <p class="x24 bold500">¿Desea borrar este enlace?</p>

                    <div class="flex-row center-between gap10">
                      <label for="delete-link-<?= $i?>" class="btn-card-graphic shadow-card-graphic hover-scale-soft text-c texto w100 bold500 pointer">
                         Eliminar
                      </label>
                      <p class="btn-card-graphic-red shadow-card-graphic hover-scale-soft text-c textc w100 pointer bold500 modal-close-button">Cancelar</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- input para borrar los links (esta enlazado con su label con id delete-link) -->
              <input id="delete-link-<?= $i?>" type="checkbox" name="content[<?= $i?>][delete]" value="true" class="hidden">
              
            </div>
          </div>

          <input type="hidden" name="content[<?= $i?>][type]" value="<?= e($itemType) ?>">
          <input type="hidden" name="content[<?= $i?>][img]" value="<?= e($itemImg) ?>">
          <input type="hidden" name="content[<?= $i?>][imgDefault]" value="<?= $imgDefault ? 'true' : 'false' ?>">
          <input type="hidden" name="content[<?= $i?>][imgShow]" value="<?= $imgShow ? 'true' : 'false' ?>">

          <div class="flex-row center-between gap10">
            <?php 
              $displayImgSrc = $card["content"][$i]["imgSrc"] ?? '';
              if (empty($displayImgSrc)) {
                $itemMetaImg = $card["content"][$i]["metaImg"] ?? '';
                if ($imgDefault && !empty($itemImg)) {
                  $displayImgSrc = DIR_SHOW_MEDIA . $itemImg;
                } elseif (!empty($itemMetaImg) && $itemMetaImg !== 'no-image.webp' && strpos($itemMetaImg, 'http') === 0) {
                  $displayImgSrc = $itemMetaImg;
                } else {
                  $displayImgSrc = DIR_UPLOAD_MEDIA_STATIC . "Custom/no-image.webp";
                }
              }
            ?>
            <div class="flex-row center-center gap10 relative">
              <figure class="wpx50 hpx50 ar-square back-card-graphic shadow-card-graphic hover-scale-soft br10">
                <img src="<?= e($displayImgSrc) ?>" alt="Imagen del enlace" class="cover">
              </figure>

              <div class="flex-row center-center gap0 back-menu-img-form br50 pl10 pl-sml-5 pr10 pr-sml-5">
                <?php if ($imgDefault) : ?>
                  <button type="submit" name="content[<?= $i?>][delete_img]" value="true" class="pointer flex-row center-center textw" style="background:transparent; border:none; padding:5px; border-radius:50%;" title="Borrar imagen">
                    <?= svg("trash", "x20") ?>
                  </button>
                <?php endif; ?>
                <button type="submit" name="content[<?= $i?>][toggle_img_show]" value="true" class="pointer flex-row center-center textw" style="background:transparent; border:none; padding:5px; border-radius:50%;" title="<?= $imgShow ? 'Ocultar imagen' : 'Mostrar imagen' ?>">
                  <?= $imgShow ? svg("eye", "x20") : svg("no-eye", "x20") ?>
                </button>
              </div>
            </div>
            <div class="br15 p10 back-card-graphic shadow-card-graphic hover-scale-soft">
              <input type="file" 
                name="content_img_<?= $i ?>" 
                class="selectAndCropImage btn-style-classes no-preview process-auto-submit"
                placeholder="Elige una imagen" 
                cropping-size="500x500"
                box-image="back-menu-sidebar textb br15 back-card-graphic shadow-card-graphic hover-scale-soft p20 shadow-1"
                box-btn-image="p10 back7 back-card-graphic shadow-card-graphic hover-scale-soft textb br15 pointer">
            </div>
          </div>

          <input type="text" name="content[<?= $i?>][title]" class="back-card-graphic shadow-card-graphic hover-scale-soft br10 p10" value="<?= e($itemTitle) ?>" placeholder="<?= ($itemType === 'product') ? 'Nombre del producto' : 'Título del enlace' ?>">
          <input type="text" name="content[<?= $i?>][url]" class="back-card-graphic shadow-card-graphic hover-scale-soft br10 p10" value="<?= e($itemUrl) ?>" placeholder="<?= ($itemType === 'product') ? 'Detalle o URL del producto' : 'URL (ej: https://...)' ?>">
        </div>
      <?php endfor?>
    </div>

    <input type="submit" value="Guardar" class="hidden">
  </div>

</form>