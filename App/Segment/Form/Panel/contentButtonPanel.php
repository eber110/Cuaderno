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
      <p class="bold500 x16 texto">Añadir nuevo elemento</p>
      <div class="flex-row center-start gap10 w100 wrap">
        <button type="submit" name="add_content_type" value="link" class="p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 texto" style="border: none;">
          <?= svg("add") ?> Enlace
        </button>
        <button type="submit" name="add_content_type" value="product" class="p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 texto" style="border: none;">
          <?= svg("add") ?> Producto
        </button>
        <button type="submit" name="add_content_type" value="product_group" class="p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 texto" style="border: none;">
          <?= svg("add") ?> Grupo de productos
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
        
        $itemPrice      = $card["content"][$i]["price"] ?? '';
        $rawOffer       = $card["content"][$i]["offer"] ?? false;
        $itemOffer      = ($rawOffer === true || $rawOffer === 'true' || $rawOffer === 1 || $rawOffer === '1');
        $itemDiscount   = $card["content"][$i]["discount"] ?? '';
        $itemPorcentage = $card["content"][$i]["porcentage"] ?? 0;

        if ($itemType === 'product_group') {
          $groupProducts = $card["content"][$i]["products"] ?? [];
          $prodCount     = count($groupProducts);
          $groupLayout   = $card["content"][$i]["layout"] ?? 'grid';
          if ($prodCount % 2 !== 0) {
            $groupLayout = 'slide';
          }
          $validCount = 0;
          foreach ($groupProducts as $gp) {
            if (trim($gp["title"] ?? '') !== '' && trim($gp["url"] ?? '') !== '') {
              $validCount++;
            }
          }
          $isEmpty = ($validCount < 2);
          $itemActive = $isEmpty ? false : ($rawActive === true || $rawActive === 'true' || $rawActive === 1 || $rawActive === '1');
          $isOpen = ($validCount === 0);
        } else {
          // Si el título o la URL están vacíos, no se puede activar y permanece inactivo (false)
          $isEmpty = (trim($itemTitle) === '' || trim($itemUrl) === '');
          $itemActive = $isEmpty ? false : ($rawActive === true || $rawActive === 'true' || $rawActive === 1 || $rawActive === '1');
          $isOpen = (trim($itemTitle) === '' && trim($itemUrl) === '');
        }
      ?>
        <div id="content-item-<?= $i?>" class="sortable-item content-block link back-card-graphic shadow-card-graphic |hover-scale-soft flex-column gap10 w100 p20 br15 <?= $isOpen ? 'is-open' : 'is-collapsed' ?> <?php if ($itemActive) echo $selected; ?>" draggable="true" data-type="<?= e($itemType) ?>">
          
          <!-- Cabecera del bloque (Siempre visible) -->
          <div class="content-item-header flex-row center-between w100 pointer">
            <div class="flex-row center-start gap10 drag-handle flex-1" style="min-width: 0;">
              <span class="flex-row top-center drag-icon text-muted pointer" title="Arrastrar para reordenar" style="cursor: grab; font-size: 18px; user-select: none;">&#x22EE;&#x22EE;</span>
              <p class="bold500 item-title-label texto" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">
                <?php
                  if ($itemType === 'product_group') {
                    echo 'Grupo de productos - ' . $prodCount . ' productos';
                  } elseif ($itemType === 'product') {
                    $displayTitle = trim($itemTitle);
                    echo ($displayTitle !== '') ? 'Producto - ' . \Base\Module\TextModule::truncateRaw($displayTitle, 4, '...') : 'Producto - (Sin título)';
                  } else {
                    $displayTitle = trim($itemTitle);
                    echo ($displayTitle !== '') ? 'Enlace - ' . \Base\Module\TextModule::truncateRaw($displayTitle, 4, '...') : 'Enlace - (Sin título)';
                  }
                ?>
              </p>
            </div>
            
            <div class="flex-row center-end gap20 tooltip left animated wpx300 wpx-sml-270 no-drag-actions" data-tooltip="Ocultar elemento" style="flex-shrink: 0;">
              <!-- Switch para activar / desactivar enlace o grupo -->
              <input type="checkbox" name="content[<?= $i?>][active]" value="true" data-option="true,false" class="checkbox-switch" active="<?= $itemActive ? '1' : '2' ?>" <?= $itemActive ? 'checked' : '' ?> <?= $isEmpty ? 'disabled' : '' ?>>
              
              <!-- Opción para eliminar enlace o grupo -->
              <div class="modal-btn tooltip left" data-tooltip="Borrar elemento">
                <p class="pointer flex-row center-center texto">
                  <span class="no-phone">Eliminar</span><span class="flex-row center-center br50 back-danger back-danger-hover textc p2 x16 ml5"><?= svg("xmark")?></span>
                </p>
              </div>

              <!-- modal menu eliminar enlace -->
              <div class="hidden">
                <div class="w100 flex-column center-center h-dvh">
                  <div class="flex-column gap20 wpx520 w-sml-100 back-card-graphic p20 br15">
                    <p class="x24 bold500 texto">¿Desea borrar este elemento?</p>

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

          <!-- Inputs ocultos que siempre viajan en el formulario -->
          <input type="hidden" name="content[<?= $i?>][type]" value="<?= e($itemType) ?>">
          <?php if ($itemType !== 'product_group') : ?>
            <input type="hidden" name="content[<?= $i?>][img]" value="<?= e($itemImg) ?>">
            <input type="hidden" name="content[<?= $i?>][imgDefault]" value="<?= $imgDefault ? 'true' : 'false' ?>">
            <input type="hidden" name="content[<?= $i?>][imgShow]" value="<?= $imgShow ? 'true' : 'false' ?>">
            <input type="hidden" name="content[<?= $i?>][metaTitle]" value="<?= e($card["content"][$i]["metaTitle"] ?? '') ?>">
            <input type="hidden" name="content[<?= $i?>][metaDesc]" value="<?= e($card["content"][$i]["metaDesc"] ?? '') ?>">
            <input type="hidden" name="content[<?= $i?>][metaImg]" value="<?= e($card["content"][$i]["metaImg"] ?? '') ?>">
          <?php endif; ?>

          <!-- Cuerpo colapsable del formulario -->
          <div class="content-item-body flex-column gap10 w100" style="<?= $isOpen ? '' : 'display: none;' ?>">

            <?php if ($itemType === 'product_group') : ?>
              <!-- Formato de Visualización (Grid vs Slide) -->
              <!-- <div class="flex-column gap8 w100 p15 br10 back-card-graphic shadow-card-graphic"> -->
                <!-- <p class="x14 bold600 texto">Formato del grupo</p> -->
                <div class="flex-row center-between gap10 w100">
                  <input type="radio" id="layout-grid-<?= $i?>" name="content[<?= $i?>][layout]" value="grid" class="hidden-radio" <?= ($groupLayout === 'grid') ? 'checked' : '' ?> <?= ($prodCount % 2 !== 0) ? 'disabled' : '' ?>>
                  <label for="layout-grid-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto <?= ($prodCount % 2 !== 0) ? 'opacity-50 pointer-events-none' : '' ?>" title="<?= ($prodCount % 2 !== 0) ? 'Modo cuadrícula requiere número par de productos (2, 4, 6 u 8)' : 'Cuadrícula' ?>">
                    <?= svg("grid", "x16") ?>
                    <span class="bold500 x13">Cuadrícula</span>
                  </label>

                  <input type="radio" id="layout-slide-<?= $i?>" name="content[<?= $i?>][layout]" value="slide" class="hidden-radio" <?= ($groupLayout === 'slide') ? 'checked' : '' ?>>
                  <label for="layout-slide-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Carrusel">
                    <?= svg("slide", "x16") ?>
                    <span class="bold500 x13">Carrusel</span>
                  </label>
                </div>
                <?php if ($prodCount % 2 !== 0) : ?>
                  <span class="flex-row center-start gap5 x12 text-muted mt2">
                    <?= svg("info", "x14") ?> Modo cuadrícula requiere número par de productos (2, 4, 6 u 8). Actualmente hay <?= $prodCount ?>.
                  </span>
                <?php endif; ?>
              <!-- </div> -->

              <!-- Lista de Sub-productos (Mínimo 2, Máximo 8) -->
              <div class="flex-column gap15 w100 mt5">
                <div class="flex-row center-between w100">
                  <p class="x14 bold600 texto">Productos en este grupo</p>
                  <span class="x12 text-muted bold500"><?= $prodCount ?> / 8</span>
                </div>

                <?php foreach ($groupProducts as $pIdx => $prod) :
                  $pImg      = $prod["img"] ?? 'no-image.webp';
                  $pTitle    = $prod["title"] ?? '';
                  $pUrl      = $prod["url"] ?? '';
                  if ($pUrl !== '' && !preg_match('#^https?://#i', $pUrl) && strpos($pUrl, 'mailto:') !== 0 && strpos($pUrl, 'tel:') !== 0) {
                    $pUrl = "https://" . $pUrl;
                  }
                  $pRawImgDef  = $prod["imgDefault"] ?? false;
                  $pImgDefault = ($pRawImgDef === true || $pRawImgDef === 'true' || $pRawImgDef === 1 || $pRawImgDef === '1');
                  $pRawImgShow = $prod["imgShow"] ?? true;
                  $pImgShow    = ($pRawImgShow === true || $pRawImgShow === 'true' || $pRawImgShow === 1 || $pRawImgShow === '1');
                  $pPrice      = $prod["price"] ?? '';
                  $pRawOffer   = $prod["offer"] ?? false;
                  $pOffer      = ($pRawOffer === true || $pRawOffer === 'true' || $pRawOffer === 1 || $pRawOffer === '1');
                  $pDiscount   = $prod["discount"] ?? '';
                  $pPorcentage = $prod["porcentage"] ?? 0;

                  $pDisplayImgSrc = $prod["imgSrc"] ?? '';
                  if (empty($pDisplayImgSrc)) {
                    $pMetaImg = $prod["metaImg"] ?? '';
                    if ($pImgDefault && !empty($pImg)) {
                      $pDisplayImgSrc = DIR_SHOW_MEDIA . $pImg;
                    } elseif (!empty($pMetaImg) && $pMetaImg !== 'no-image.webp' && strpos($pMetaImg, 'http') === 0) {
                      $pDisplayImgSrc = $pMetaImg;
                    } else {
                      $pDisplayImgSrc = DIR_UPLOAD_MEDIA_STATIC . "Custom/no-image.webp";
                    }
                  }
                ?>
                  <div class="sub-product-item flex-column gap10 p15 br10 back-card-graphic shadow-card-graphic" style="border: 1px solid rgba(150, 150, 150, 0.2);">
                    <div class="flex-row center-between w100">
                      <span class="x13 bold600 texto">Producto <?= $pIdx + 1 ?></span>
                      <?php if ($prodCount > 2) : ?>
                        <button type="submit" name="content[<?= $i?>][delete_sub_product]" value="<?= $pIdx ?>" class="pointer flex-row center-center text-muted hover-danger" style="background:transparent; border:none; padding:2px 6px; font-size:12px;" title="Eliminar este producto">
                          <?= svg("trash", "x16") ?> Eliminar
                        </button>
                      <?php else : ?>
                        <span class="x11 text-muted" title="Mínimo 2 productos requeridos">Mínimo 2</span>
                      <?php endif; ?>
                    </div>

                    <input type="hidden" name="content[<?= $i?>][products][<?= $pIdx?>][img]" value="<?= e($pImg) ?>">
                    <input type="hidden" name="content[<?= $i?>][products][<?= $pIdx?>][imgDefault]" value="<?= $pImgDefault ? 'true' : 'false' ?>">
                    <input type="hidden" name="content[<?= $i?>][products][<?= $pIdx?>][imgShow]" value="<?= $pImgShow ? 'true' : 'false' ?>">
                    <input type="hidden" name="content[<?= $i?>][products][<?= $pIdx?>][metaTitle]" value="<?= e($prod["metaTitle"] ?? '') ?>">
                    <input type="hidden" name="content[<?= $i?>][products][<?= $pIdx?>][metaDesc]" value="<?= e($prod["metaDesc"] ?? '') ?>">
                    <input type="hidden" name="content[<?= $i?>][products][<?= $pIdx?>][metaImg]" value="<?= e($prod["metaImg"] ?? '') ?>">

                    <!-- Imagen del sub-producto -->
                    <div class="flex-row center-between gap10">
                      <div class="flex-row center-center gap10 relative">
                        <figure class="wpx45 hpx45 ar-square back-card-graphic shadow-card-graphic hover-scale-soft br10">
                          <img src="<?= e($pDisplayImgSrc) ?>" alt="Imagen producto" class="cover">
                        </figure>
                        <div class="flex-row center-center gap0 back-menu-img-form br50 pl8 pr8">
                          <?php if ($pImgDefault) : ?>
                            <button type="submit" name="content[<?= $i?>][products][<?= $pIdx?>][delete_img]" value="true" class="pointer flex-row center-center textc" style="background:transparent; border:none; padding:4px;" title="Borrar imagen">
                              <?= svg("trash", "x16") ?>
                            </button>
                          <?php endif; ?>
                          <button type="submit" name="content[<?= $i?>][products][<?= $pIdx?>][toggle_img_show]" value="true" class="pointer flex-row center-center textc" style="background:transparent; border:none; padding:4px;" title="<?= $pImgShow ? 'Ocultar imagen' : 'Mostrar imagen' ?>">
                            <?= $pImgShow ? svg("eye", "x16") : svg("no-eye", "x16") ?>
                          </button>
                        </div>
                      </div>
                      <div class="br10 p5 back-card-graphic shadow-card-graphic hover-scale-soft">
                        <input type="file" 
                          name="content_img_<?= $i ?>_<?= $pIdx ?>" 
                          class="selectAndCropImage btn-style-classes no-preview process-auto-submit"
                          placeholder="Subir imagen" 
                          cropping-size="500x500"
                          box-image="back-menu-sidebar texto br15 back-card-graphic shadow-card-graphic hover-scale-soft p20 shadow-1"
                          box-btn-image="p8 back7 back-card-graphic shadow-card-graphic hover-scale-soft texto br10 pointer x12">
                      </div>
                    </div>

                    <!-- Campos de título y URL -->
                    <input type="text" name="content[<?= $i?>][products][<?= $pIdx?>][title]" class="back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto" value="<?= e($pTitle) ?>" placeholder="Nombre del producto">
                    <input type="text" name="content[<?= $i?>][products][<?= $pIdx?>][url]" class="back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto" value="<?= e($pUrl) ?>" placeholder="URL del producto (ej: https://...)">

                    <!-- Campo Precio -->
                    <input type="number" step="any" min="0" name="content[<?= $i?>][products][<?= $pIdx?>][price]" class="product-price-input back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto w100" value="<?= e($pPrice) ?>" placeholder="Precio">

                    <!-- Switch Rebaja -->
                    <div class="flex-row center-between w100 p8 br10 back-card-graphic shadow-card-graphic">
                      <div class="flex-column">
                        <p class="x12 bold500 texto">Rebaja</p>
                        <span class="x11 text-muted">Aplica un precio rebajado o porcentaje</span>
                      </div>
                      <input type="checkbox" id="offer-switch-<?= $i?>-<?= $pIdx?>" name="content[<?= $i?>][products][<?= $pIdx?>][offer]" value="true" data-option="true,false" class="checkbox-switch product-offer-switch" active="<?= $pOffer ? '1' : '2' ?>" <?= $pOffer ? 'checked' : '' ?>>
                    </div>

                    <!-- Campos de oferta / descuento -->
                    <div class="product-discount-wrapper flex-row center-between gap10 w100 flex-column-sml" style="display: <?= $pOffer ? 'flex' : 'none' ?>;">
                      <div class="flex-column gap5 w50 w-sml-100">
                        <p class="x11 bold500 texto">Precio rebajado</p>
                        <input type="number" step="any" min="0" name="content[<?= $i?>][products][<?= $pIdx?>][discount]" class="product-discount-input back-card-graphic shadow-card-graphic hover-scale-soft br10 p8 texto w100 x13" value="<?= e($pDiscount) ?>" placeholder="Precio rebajado">
                      </div>
                      <div class="flex-column gap5 w50 w-sml-100">
                        <p class="x11 bold500 texto">% Descuento</p>
                        <input type="number" step="1" min="0" max="100" name="content[<?= $i?>][products][<?= $pIdx?>][porcentage]" class="product-porcentage-input back-card-graphic shadow-card-graphic hover-scale-soft br10 p8 texto w100 x13" value="<?= (int)$pPorcentage ?>" placeholder="% Descuento">
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>

                <?php if ($prodCount < 8) : ?>
                  <button type="submit" name="content[<?= $i?>][add_sub_product]" value="true" class="p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 texto w100 mt5" style="border: 1px dashed rgba(150, 150, 150, 0.4);">
                    <?= svg("add") ?> Añadir producto al grupo (<?= $prodCount ?>/8)
                  </button>
                <?php else : ?>
                  <p class="x12 text-muted text-center p10">Límite máximo de 8 productos alcanzado.</p>
                <?php endif; ?>
              </div>

            <?php else : ?>
              <!-- Imagen enlace / producto individual -->
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
                      <button type="submit" name="content[<?= $i?>][delete_img]" value="true" class="pointer flex-row center-center textc" style="background:transparent; border:none; padding:5px; border-radius:50%;" title="Borrar imagen">
                        <?= svg("trash", "x20") ?>
                      </button>
                    <?php endif; ?>
                    <button type="submit" name="content[<?= $i?>][toggle_img_show]" value="true" class="pointer flex-row center-center textc" style="background:transparent; border:none; padding:5px; border-radius:50%;" title="<?= $imgShow ? 'Ocultar imagen' : 'Mostrar imagen' ?>">
                      <?= $imgShow ? svg("eye", "x20") : svg("no-eye", "x20") ?>
                    </button>
                  </div>
                </div>
                <div class="br15 p10 back-card-graphic shadow-card-graphic hover-scale-soft">
                  <input type="file" 
                    name="content_img_<?= $i ?>" 
                    class="selectAndCropImage btn-style-classes no-preview process-auto-submit"
                    placeholder="Subir imagen" 
                    cropping-size="500x500"
                    box-image="back-menu-sidebar texto br15 back-card-graphic shadow-card-graphic hover-scale-soft p20 shadow-1"
                    box-btn-image="p10 back7 back-card-graphic shadow-card-graphic hover-scale-soft texto br15 pointer">
                </div>
              </div>

              <input type="text" name="content[<?= $i?>][title]" class="content-title-input back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto" value="<?= e($itemTitle) ?>" placeholder="<?= ($itemType === 'product') ? 'Nombre del producto' : 'Título del enlace' ?>">
              <input type="text" name="content[<?= $i?>][url]" class="content-url-input back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto" value="<?= e($itemUrl) ?>" placeholder="<?= ($itemType === 'product') ? 'Detalle o URL del producto' : 'URL (ej: https://...)' ?>">

              <?php if ($itemType === 'product') : ?>
                <!-- Campo Precio -->
                <input type="number" step="any" min="0" name="content[<?= $i?>][price]" class="product-price-input back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto w100" value="<?= e($itemPrice) ?>" placeholder="Precio">

                <!-- Switch de Rebaja -->
                <div class="flex-row center-between w100 p10 br10 back-card-graphic shadow-card-graphic">
                  <div class="flex-column">
                    <p class="x14 bold500 texto">Rebaja</p>
                    <span class="x12 text-muted">Aplica un precio rebajado o porcentaje</span>
                  </div>
                  <input type="checkbox" id="offer-switch-<?= $i?>" name="content[<?= $i?>][offer]" value="true" data-option="true,false" class="checkbox-switch product-offer-switch" active="<?= $itemOffer ? '1' : '2' ?>" <?= $itemOffer ? 'checked' : '' ?>>
                </div>

                <!-- Campos de Oferta / Descuento (Valor rebajado o Porcentaje) -->
                <div class="product-discount-wrapper flex-row center-between gap10 w100 flex-column-sml" style="display: <?= $itemOffer ? 'flex' : 'none' ?>;">
                  <div class="flex-column gap5 w50 w-sml-100">
                    <p class="x12 bold500 texto">Precio rebajado</p>
                    <input type="number" step="any" min="0" name="content[<?= $i?>][discount]" class="product-discount-input back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto w100" value="<?= e($itemDiscount) ?>" placeholder="Precio rebajado">
                  </div>
                  <div class="flex-column gap5 w50 w-sml-100">
                    <p class="x12 bold500 texto">% Descuento</p>
                    <input type="number" step="1" min="0" max="100" name="content[<?= $i?>][porcentage]" class="product-porcentage-input back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto w100" value="<?= (int)$itemPorcentage ?>" placeholder="% Descuento">
                  </div>
                </div>
              <?php endif; ?>
            <?php endif; ?>

          </div>
        </div>
      <?php endfor?>
    </div>

    <input type="submit" value="Guardar" class="hidden">
  </div>

</form>