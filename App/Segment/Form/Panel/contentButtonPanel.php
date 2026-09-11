<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   * @var mixed $prodCount
   */
  $selected = "";
  $cant = is_array($card["content"] ?? null) ? count($card["content"]) : 0;
  $separatorIcons = \App\Models\DesignModels::getSeparatorIcons();
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
        <button type="submit" name="add_content_type" value="campaign" class="p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 texto" style="border: none;">
          <?= svg("add") ?> Campaña
        </button>
        <button type="submit" name="add_content_type" value="banner" class="p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 texto" style="border: none;">
          <?= svg("add") ?> Banner
        </button>
        <button type="submit" name="add_content_type" value="title" class="p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 texto" style="border: none;">
          <?= svg("add") ?> Título
        </button>
        <button type="submit" name="add_content_type" value="text" class="p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 texto" style="border: none;">
          <?= svg("add") ?> Texto
        </button>
        <button type="submit" name="add_content_type" value="separator" class="p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 texto" style="border: none;">
          <?= svg("add") ?> Separador
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

        $itemDesc          = $card["content"][$i]["desc"] ?? '';
        $itemName          = $card["content"][$i]["name"] ?? '';
        $itemEmail         = $card["content"][$i]["email"] ?? '';
        $itemWhatsapp      = $card["content"][$i]["whatsapp"] ?? '';
        $itemImgPosition   = $card["content"][$i]["img_position"] ?? 'background';
        $itemBgColor       = ($itemType === 'banner')
          ? ($card["content"][$i]["bg_color"] ?? '#f2e5ff')
          : ($card["content"][$i]["bg_color"] ?? '#1e1e1e');
        $itemBgOpacity     = isset($card["content"][$i]["bg_opacity"]) ? (int)$card["content"][$i]["bg_opacity"] : (($itemType === 'banner') ? 100 : 80);
        $itemSize          = ($itemType === 'banner')
          ? (in_array($card["content"][$i]["size"] ?? '', ['720x1024', '720x720', '1024x720'], true) ? $card["content"][$i]["size"] : '')
          : (($itemImgPosition === 'header') ? 'horizontal' : ($card["content"][$i]["size"] ?? 'horizontal'));
        $itemTextPosition  = $card["content"][$i]["text_position"] ?? 'center';
        $itemTextAlign     = $card["content"][$i]["text_align"] ?? 'center';
        $itemTitleSize     = ($itemType === 'campaign')
          ? ($card["content"][$i]["title_size"] ?? 'large')
          : ($card["content"][$i]["title_size"] ?? 'small');
        $itemTitleWeight   = $card["content"][$i]["title_weight"] ?? '500';
        $itemText          = $card["content"][$i]["text"] ?? ($card["content"][$i]["title"] ?? '');
        $itemTextWeight    = $card["content"][$i]["text_weight"] ?? '400';
        $itemTextAlignVal  = $card["content"][$i]["text_align"] ?? 'left';
        $itemSepMode       = $card["content"][$i]["separator_mode"] ?? 'space';
        $itemSepIcon       = $card["content"][$i]["separator_icon"] ?? 'none';
        $itemSepSize       = $card["content"][$i]["separator_size"] ?? 'large';
        $itemSpaceSize     = $card["content"][$i]["space_size"] ?? '40';
        $itemDescSize      = $card["content"][$i]["desc_size"] ?? 'medium';
        $itemTitleColor    = $card["content"][$i]["title_color"] ?? '#ffffff';
        $itemDescColor     = $card["content"][$i]["desc_color"] ?? '#ffffff';
        $itemBtnBgColor    = $card["content"][$i]["btn_bg_color"] ?? ($card["back"] ?? '#595a83');
        $itemBtnTextColor  = $card["content"][$i]["btn_text_color"] ?? ($card["color"] ?? '#ffffff');
        $rawAskName        = $card["content"][$i]["ask_name"] ?? true;
        $askName           = ($rawAskName === true || $rawAskName === 'true' || $rawAskName === 1 || $rawAskName === '1');
        $rawAskWhatsapp    = $card["content"][$i]["ask_whatsapp"] ?? (!empty($itemWhatsapp));
        $askWhatsapp       = ($rawAskWhatsapp === true || $rawAskWhatsapp === 'true' || $rawAskWhatsapp === 1 || $rawAskWhatsapp === '1');
        $rawCountdown           = $card["content"][$i]["has_countdown"] ?? false;
        $hasCountdown           = ($rawCountdown === true || $rawCountdown === 'true' || $rawCountdown === 1 || $rawCountdown === '1');
        $itemCountdownDate      = $card["content"][$i]["countdown_date"] ?? '';
        $itemCountdownBgColor   = !empty($card["content"][$i]["countdown_bg_color"]) ? $card["content"][$i]["countdown_bg_color"] : '#24252a';
        $itemCountdownTextColor = !empty($card["content"][$i]["countdown_text_color"]) ? $card["content"][$i]["countdown_text_color"] : '#ffffff';
        $itemCountdownTextSize  = $card["content"][$i]["countdown_text_size"] ?? 'medium';
        if (!in_array($itemCountdownTextSize, ['small', 'medium', 'large'], true)) {
          $itemCountdownTextSize = 'medium';
        }
        $itemCountdownWidgetSize = $card["content"][$i]["countdown_widget_size"] ?? 'medium';
        if (!in_array($itemCountdownWidgetSize, ['small', 'medium', 'large'], true)) {
          $itemCountdownWidgetSize = 'medium';
        }
        $itemButtonText         = trim($card["content"][$i]["button_text"] ?? '');
        if ($itemButtonText === '') {
          $itemButtonText = 'Suscribirme';
        }
        $isCountdownExpired     = ($hasCountdown && !empty($itemCountdownDate) && strtotime($itemCountdownDate) !== false && strtotime($itemCountdownDate) <= time());

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
        } elseif ($itemType === 'campaign') {
          $isEmpty = (trim($itemTitle) === '');
          $itemActive = $isEmpty ? false : ($rawActive === true || $rawActive === 'true' || $rawActive === 1 || $rawActive === '1');
          $isOpen = (trim($itemTitle) === '');
        } elseif ($itemType === 'banner') {
          $isEmpty = (empty($itemSize) || trim($itemUrl) === '' || empty($itemImg) || $itemImg === 'no-image.webp');
          $itemActive = $isEmpty ? false : ($rawActive === true || $rawActive === 'true' || $rawActive === 1 || $rawActive === '1');
          $isOpen = (empty($itemSize) || (trim($itemUrl) === '' && (empty($itemImg) || $itemImg === 'no-image.webp')));
        } elseif ($itemType === 'title') {
          $isEmpty = (trim($itemTitle) === '');
          $itemActive = $isEmpty ? false : ($rawActive === true || $rawActive === 'true' || $rawActive === 1 || $rawActive === '1');
          $isOpen = (trim($itemTitle) === '');
        } elseif ($itemType === 'text') {
          $isEmpty = (trim($itemText) === '');
          $itemActive = $isEmpty ? false : ($rawActive === true || $rawActive === 'true' || $rawActive === 1 || $rawActive === '1');
          $isOpen = (trim($itemText) === '');
        } elseif ($itemType === 'separator') {
          $isEmpty = false;
          $itemActive = ($rawActive === true || $rawActive === 'true' || $rawActive === 1 || $rawActive === '1');
          $isOpen = false;
        } else {
          // Si el título o la URL están vacíos, no se puede activar y permanece inactivo (false)
          $isEmpty = (trim($itemTitle) === '' || trim($itemUrl) === '');
          $itemActive = $isEmpty ? false : ($rawActive === true || $rawActive === 'true' || $rawActive === 1 || $rawActive === '1');
          $isOpen = (trim($itemTitle) === '' && trim($itemUrl) === '');
        }
      ?>
        <div id="content-item-<?= $i?>" class="sortable-item content-block link back-card-graphic shadow-card-graphic |hover-scale-soft flex-column gap10 w100 p20 br15 <?= $isOpen ? 'is-open' : 'is-collapsed' ?> <?php if ($itemActive) echo $selected; ?>" draggable="false" data-type="<?= e($itemType) ?>">
          
          <!-- Cabecera del bloque (Siempre visible) -->
          <div class="content-item-header flex-row center-between gap10 w100 pointer">
            <div class="flex-row center-start gap10 drag-handle flex-1" style="min-width: 0; overflow: hidden;">
              <span class="flex-row top-center drag-icon text-muted pointer" title="Arrastrar para reordenar" style="cursor: grab; font-size: 18px; user-select: none; flex-shrink: 0;">&#x22EE;&#x22EE;</span>
              <p class="bold500 item-title-label texto" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; flex: 1;">
                <?php
                  if ($itemType === 'product_group') {
                    echo 'Grupo de productos - ' . $prodCount . ' productos';
                  } elseif ($itemType === 'product') {
                    $displayTitle = trim($itemTitle);
                    echo ($displayTitle !== '') ? 'Producto - ' . e($displayTitle) : 'Producto - (Sin título)';
                  } elseif ($itemType === 'campaign') {
                    $displayTitle = trim($itemTitle);
                    echo ($displayTitle !== '') ? 'Campaña - ' . e($displayTitle) : 'Campaña - (Sin título)';
                  } elseif ($itemType === 'banner') {
                    $displayUrl = trim($itemUrl);
                    echo ($displayUrl !== '') ? 'Banner - ' . e($displayUrl) : 'Banner - (Sin enlace)';
                  } elseif ($itemType === 'title') {
                    $displayTitle = trim($itemTitle);
                    echo ($displayTitle !== '') ? 'Título - ' . e($displayTitle) : 'Título - (Sin texto)';
                  } elseif ($itemType === 'text') {
                    $displayText = trim($itemText);
                    if (mb_strlen($displayText) > 35) {
                      $displayText = mb_substr($displayText, 0, 35) . '...';
                    }
                    echo ($displayText !== '') ? 'Texto - ' . e($displayText) : 'Texto - (Sin texto)';
                  } elseif ($itemType === 'separator') {
                    if ($itemSepIcon === 'none' || $itemSepIcon === 'ban' || $itemSepMode === 'space') {
                      $sizeName = ($itemSpaceSize === '20') ? 'Pequeño (20px)' : (($itemSpaceSize === '60') ? 'Grande (60px)' : 'Medio (40px)');
                      echo 'Separador - Espacio ' . $sizeName;
                    } else {
                      $sizeLabel = match ($itemSepSize) {
                        'small'  => ' (1 figura)',
                        'medium' => ' (60%)',
                        default  => ' (Completo)',
                      };
                      echo 'Separador - Figuras' . $sizeLabel;
                    }
                  } else {
                    $displayTitle = trim($itemTitle);
                    echo ($displayTitle !== '') ? 'Enlace - ' . e($displayTitle) : 'Enlace - (Sin título)';
                  }
                ?>
              </p>
            </div>
            
            <div class="flex-row center-end gap15 gap-sml-10 no-drag-actions" style="flex-shrink: 0;">
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
          <?php if ($itemType !== 'product_group' && $itemType !== 'title' && $itemType !== 'text' && $itemType !== 'separator') : ?>
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

                <!-- Título del grupo (opcional) -->
                <input type="text" name="content[<?= $i?>][title]" class="back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto" value="<?= e($itemTitle) ?>" placeholder="Título del grupo (opcional, ej: Mi Colección)">


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

            <?php elseif ($itemType === 'campaign') : ?>
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

              <!-- 1. Selector de Tamaño mínimo del Bloque (Horizontal / Cuadrado / Vertical) -->
              <div id="campaign-size-wrap-<?= $i ?>" class="flex-column gap8 w100" style="<?= ($itemImgPosition === 'header') ? 'display: none;' : '' ?>">
                <div class="flex-column gap2">
                  <p class="x13 bold600 texto">Tamaño del bloque</p>
                  <span class="x11 text-muted">Define la proporción y altura mínima del bloque</span>
                </div>
                <div class="flex-row center-between gap10 w100">
                  <input type="radio" id="campaign-size-horiz-<?= $i?>" name="content[<?= $i?>][size]" value="horizontal" class="hidden-radio campaign-size-radio" data-index="<?= $i ?>" <?= ($itemSize === 'horizontal') ? 'checked' : '' ?>>
                  <label for="campaign-size-horiz-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Formato horizontal compacto">
                    <?= svg("bars", "x16") ?>
                    <span class="bold500 x13">Horizontal</span>
                  </label>

                  <input type="radio" id="campaign-size-sq-<?= $i?>" name="content[<?= $i?>][size]" value="square" class="hidden-radio campaign-size-radio" data-index="<?= $i ?>" <?= ($itemSize === 'square') ? 'checked' : '' ?>>
                  <label for="campaign-size-sq-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Formato cuadrado (1:1)">
                    <?= svg("grid", "x16") ?>
                    <span class="bold500 x13">Cuadrado</span>
                  </label>

                  <input type="radio" id="campaign-size-vert-<?= $i?>" name="content[<?= $i?>][size]" value="vertical" class="hidden-radio campaign-size-radio" data-index="<?= $i ?>" <?= ($itemSize === 'vertical') ? 'checked' : '' ?>>
                  <label for="campaign-size-vert-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Formato vertical amplio">
                    <?= svg("film", "x16") ?>
                    <span class="bold500 x13">Vertical</span>
                  </label>
                </div>
              </div>

              <!-- 2. Imagen y posición de la imagen -->
              <!-- Selector de Posición de Imagen (Fondo vs Cabecera) -->
              <div class="flex-column gap8 w100">
                <p class="x13 bold600 texto">Posición de la imagen</p>
                <div class="flex-row center-between gap10 w100">
                  <input type="radio" id="img-pos-bg-<?= $i?>" name="content[<?= $i?>][img_position]" value="background" class="hidden-radio campaign-pos-radio" data-index="<?= $i ?>" <?= ($itemImgPosition === 'background') ? 'checked' : '' ?>>
                  <label for="img-pos-bg-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Imagen como fondo del bloque">
                    <?= svg("images", "x16") ?>
                    <span class="bold500 x13">Fondo</span>
                  </label>

                  <input type="radio" id="img-pos-hdr-<?= $i?>" name="content[<?= $i?>][img_position]" value="header" class="hidden-radio campaign-pos-radio" data-index="<?= $i ?>" <?= ($itemImgPosition === 'header') ? 'checked' : '' ?>>
                  <label for="img-pos-hdr-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Imagen en la cabecera">
                    <?= svg("slide", "x16") ?>
                    <span class="bold500 x13">Cabecera</span>
                  </label>
                </div>
              </div>

              <!-- Imagen de la campaña -->
              <div class="flex-row center-between gap10">
                <div class="flex-row center-center gap10 relative">
                  <figure class="wpx50 hpx50 ar-square back-card-graphic shadow-card-graphic hover-scale-soft br10">
                    <img src="<?= e($displayImgSrc) ?>" alt="Imagen de la campaña" class="cover">
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

              <!-- 3. Título, Descripción y Botón -->
              <!-- Título de la campaña -->
              <div class="flex-column gap5 w100">
                <p class="x12 bold500 texto">Título de la campaña</p>
                <input type="text" name="content[<?= $i?>][title]" class="content-title-input back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto" value="<?= e($itemTitle) ?>" placeholder="Título para la campaña (ej: Suscríbete a mi newsletter)">
              </div>

              <!-- Textarea para la descripción -->
              <div class="flex-column gap5 w100">
                <p class="x12 bold500 texto">Descripción</p>
                <textarea name="content[<?= $i?>][desc]" rows="3" class="back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto w100" placeholder="Descripción de la campaña..." style="resize: vertical;"><?= e($itemDesc) ?></textarea>
              </div>

              <!-- Texto del botón de suscripción -->
              <div class="flex-column gap5 w100">
                <p class="x12 bold500 texto">Texto del botón</p>
                <input type="text" name="content[<?= $i?>][button_text]" class="back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto w100" value="<?= e($itemButtonText) ?>" placeholder="Texto del botón (ej: Suscribirme)">
              </div>

              <!-- 4. Card con toda la disposición del texto -->
              <div class="flex-column gap12 w100 p12 br10 back-card-graphic shadow-card-graphic">
                <div class="flex-column gap2">
                  <p class="x13 bold600 texto flex-row center-start gap6"><?= svg("edit", "x16") ?> Diseño del texto</p>
                  <span class="x11 text-muted">Configura la alineación y los tamaños del título y la descripción</span>
                </div>

                <!-- Alineación horizontal del texto (Izquierda / Centro / Derecha) -->
                <div class="flex-column gap8 w100">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Alineación horizontal</p>
                    <span class="x11 text-muted">Alineación del texto y título en el bloque</span>
                  </div>
                  <div class="flex-row center-between gap10 w100">
                    <input type="radio" id="campaign-text-align-left-<?= $i?>" name="content[<?= $i?>][text_align]" value="left" class="hidden-radio" <?= ($itemTextAlign === 'left') ? 'checked' : '' ?>>
                    <label for="campaign-text-align-left-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Alinear a la izquierda">
                      <?= svg("arrow-l-l", "x14") ?>
                      <span class="bold500 x13">Izquierda</span>
                    </label>

                    <input type="radio" id="campaign-text-align-center-<?= $i?>" name="content[<?= $i?>][text_align]" value="center" class="hidden-radio" <?= ($itemTextAlign === 'center') ? 'checked' : '' ?>>
                    <label for="campaign-text-align-center-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Alinear al centro">
                      <?= svg("bars", "x14") ?>
                      <span class="bold500 x13">Centro</span>
                    </label>

                    <input type="radio" id="campaign-text-align-right-<?= $i?>" name="content[<?= $i?>][text_align]" value="right" class="hidden-radio" <?= ($itemTextAlign === 'right') ? 'checked' : '' ?>>
                    <label for="campaign-text-align-right-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Alinear a la derecha">
                      <?= svg("arrow-r-l", "x14") ?>
                      <span class="bold500 x13">Derecha</span>
                    </label>
                  </div>
                </div>

                <!-- Alineación vertical del texto (Arriba / Centro / Abajo - solo cuadrado y vertical) -->
                <div id="campaign-text-pos-wrap-<?= $i?>" class="flex-column gap8 w100" style="<?= ($itemSize === 'horizontal') ? 'display: none;' : '' ?>">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Alineación vertical</p>
                    <span class="x11 text-muted">Posición vertical del texto en el bloque</span>
                  </div>
                  <div class="flex-row center-between gap10 w100">
                    <input type="radio" id="campaign-text-pos-top-<?= $i?>" name="content[<?= $i?>][text_position]" value="top" class="hidden-radio" <?= ($itemTextPosition === 'top') ? 'checked' : '' ?>>
                    <label for="campaign-text-pos-top-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Texto en la parte superior">
                      <?= svg("arrow-up", "x14") ?>
                      <span class="bold500 x13">Arriba</span>
                    </label>

                    <input type="radio" id="campaign-text-pos-center-<?= $i?>" name="content[<?= $i?>][text_position]" value="center" class="hidden-radio" <?= ($itemTextPosition === 'center') ? 'checked' : '' ?>>
                    <label for="campaign-text-pos-center-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Texto centrado">
                      <?= svg("bars", "x14") ?>
                      <span class="bold500 x13">Centro</span>
                    </label>

                    <input type="radio" id="campaign-text-pos-bottom-<?= $i?>" name="content[<?= $i?>][text_position]" value="bottom" class="hidden-radio" <?= ($itemTextPosition === 'bottom') ? 'checked' : '' ?>>
                    <label for="campaign-text-pos-bottom-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Texto en la parte inferior">
                      <?= svg("arrow-down", "x14") ?>
                      <span class="bold500 x13">Abajo</span>
                    </label>
                  </div>
                </div>

                <!-- Selector de Tamaño del Título (Pequeño / Mediano / Grande) -->
                <div class="flex-column gap8 w100">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Tamaño del título</p>
                    <span class="x11 text-muted">Escala de la fuente para el encabezado principal</span>
                  </div>
                  <div class="flex-row center-between gap10 w100">
                    <input type="radio" id="campaign-title-size-sm-<?= $i?>" name="content[<?= $i?>][title_size]" value="small" class="hidden-radio" <?= ($itemTitleSize === 'small') ? 'checked' : '' ?>>
                    <label for="campaign-title-size-sm-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Título pequeño">
                      <span class="bold500 x12">Pequeño</span>
                    </label>

                    <input type="radio" id="campaign-title-size-md-<?= $i?>" name="content[<?= $i?>][title_size]" value="medium" class="hidden-radio" <?= ($itemTitleSize === 'medium') ? 'checked' : '' ?>>
                    <label for="campaign-title-size-md-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Título mediano">
                      <span class="bold500 x12">Mediano</span>
                    </label>

                    <input type="radio" id="campaign-title-size-lg-<?= $i?>" name="content[<?= $i?>][title_size]" value="large" class="hidden-radio" <?= ($itemTitleSize === 'large') ? 'checked' : '' ?>>
                    <label for="campaign-title-size-lg-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Título grande">
                      <span class="bold500 x12">Grande</span>
                    </label>
                  </div>
                </div>

                <!-- Selector de Tamaño de la Descripción (Pequeño / Mediano / Grande) -->
                <div class="flex-column gap8 w100">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Tamaño de la descripción</p>
                    <span class="x11 text-muted">Escala de la fuente para el texto descriptivo</span>
                  </div>
                  <div class="flex-row center-between gap10 w100">
                    <input type="radio" id="campaign-desc-size-sm-<?= $i?>" name="content[<?= $i?>][desc_size]" value="small" class="hidden-radio" <?= ($itemDescSize === 'small') ? 'checked' : '' ?>>
                    <label for="campaign-desc-size-sm-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Descripción pequeña">
                      <span class="bold500 x12">Pequeño</span>
                    </label>

                    <input type="radio" id="campaign-desc-size-md-<?= $i?>" name="content[<?= $i?>][desc_size]" value="medium" class="hidden-radio" <?= ($itemDescSize === 'medium') ? 'checked' : '' ?>>
                    <label for="campaign-desc-size-md-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Descripción mediana">
                      <span class="bold500 x12">Mediano</span>
                    </label>

                    <input type="radio" id="campaign-desc-size-lg-<?= $i?>" name="content[<?= $i?>][desc_size]" value="large" class="hidden-radio" <?= ($itemDescSize === 'large') ? 'checked' : '' ?>>
                    <label for="campaign-desc-size-lg-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Descripción grande">
                      <span class="bold500 x12">Grande</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- 5. Opciones unificadas de colores de la campaña (Fondo, Opacidad de capa, Título, Descripción, Botón Fondo, Botón Texto) -->
              <div class="flex-column gap12 w100 p12 br10 back-card-graphic shadow-card-graphic">
                <div class="flex-column gap2">
                  <p class="x13 bold600 texto flex-row center-start gap6"><?= svg("palette", "x16") ?> Colores de la campaña</p>
                  <span class="x11 text-muted">Personaliza los colores del bloque, textos, capa y botón</span>
                </div>

                <!-- 1. Color de fondo del bloque -->
                <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
                  <div class="flex-column">
                    <p class="x13 bold500 texto">Color de fondo del bloque</p>
                    <span class="x11 text-muted">Afecta al contenedor de la campaña</span>
                  </div>
                  <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
                    <label data-trigger-color="campaign-color-<?= $i?>" class="flex-row center-start p8 gap10 pointer">
                      <input type="color" id="campaign-color-<?= $i?>" name="content[<?= $i?>][bg_color]" value="<?= e($itemBgColor) ?>" class="color-picker box-color-picker"
                        style-color="wpx35 hpx35 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
                      <p class="x14 bold500 texto"><?= e($itemBgColor) ?></p>
                    </label>
                  </div>
                </div>

                <!-- 2. Opacidad de la capa (dentro de la card de colores, visible solo en modo Fondo) -->
                <div id="campaign-opacity-option-<?= $i?>" class="flex-row center-between flex-column-sml top-start-sml gap10 w100" style="<?= ($itemImgPosition === 'background') ? '' : 'display: none;' ?>">
                  <div class="flex-column">
                    <p class="x13 bold500 texto">Opacidad de la capa</p>
                    <span class="x11 text-muted">Ajusta la intensidad del color sobre la imagen</span>
                  </div>
                  <div class="flex-row center-end gap10 w-sml-100">
                    <span id="campaign-opacity-val-<?= $i?>" class="x14 bold600 texto wpx40 text-right"><?= $itemBgOpacity ?>%</span>
                    <input type="range" name="content[<?= $i?>][bg_opacity]" min="0" max="100" step="1" value="<?= $itemBgOpacity ?>" class="pointer custom-range-slider campaign-opacity-slider" data-val-target="campaign-opacity-val-<?= $i?>" style="--range-progress: <?= $itemBgOpacity ?>%;">
                  </div>
                </div>

                <!-- 3. Color del título -->
                <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
                  <div class="flex-column">
                    <p class="x13 bold500 texto">Color del título</p>
                    <span class="x11 text-muted">Afecta al encabezado principal</span>
                  </div>
                  <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
                    <label data-trigger-color="campaign-title-color-<?= $i?>" class="flex-row center-start p8 gap10 pointer">
                      <input type="color" id="campaign-title-color-<?= $i?>" name="content[<?= $i?>][title_color]" value="<?= e($itemTitleColor) ?>" class="color-picker box-color-picker"
                        style-color="wpx35 hpx35 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
                      <p class="x14 bold500 texto"><?= e($itemTitleColor) ?></p>
                    </label>
                  </div>
                </div>

                <!-- 4. Color de la descripción -->
                <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
                  <div class="flex-column">
                    <p class="x13 bold500 texto">Color de la descripción</p>
                    <span class="x11 text-muted">Afecta al texto explicativo</span>
                  </div>
                  <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
                    <label data-trigger-color="campaign-desc-color-<?= $i?>" class="flex-row center-start p8 gap10 pointer">
                      <input type="color" id="campaign-desc-color-<?= $i?>" name="content[<?= $i?>][desc_color]" value="<?= e($itemDescColor) ?>" class="color-picker box-color-picker"
                        style-color="wpx35 hpx35 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
                      <p class="x14 bold500 texto"><?= e($itemDescColor) ?></p>
                    </label>
                  </div>
                </div>

                <!-- 5. Color de fondo del botón -->
                <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
                  <div class="flex-column">
                    <p class="x13 bold500 texto">Color de fondo del botón</p>
                    <span class="x11 text-muted">Fondo del botón de suscripción</span>
                  </div>
                  <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
                    <label data-trigger-color="campaign-btn-bg-color-<?= $i?>" class="flex-row center-start p8 gap10 pointer">
                      <input type="color" id="campaign-btn-bg-color-<?= $i?>" name="content[<?= $i?>][btn_bg_color]" value="<?= e($itemBtnBgColor) ?>" class="color-picker box-color-picker"
                        style-color="wpx35 hpx35 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
                      <p class="x14 bold500 texto"><?= e($itemBtnBgColor) ?></p>
                    </label>
                  </div>
                </div>

                <!-- 6. Color del texto del botón -->
                <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
                  <div class="flex-column">
                    <p class="x13 bold500 texto">Color del texto del botón</p>
                    <span class="x11 text-muted">Texto del botón de suscripción</span>
                  </div>
                  <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
                    <label data-trigger-color="campaign-btn-text-color-<?= $i?>" class="flex-row center-start p8 gap10 pointer">
                      <input type="color" id="campaign-btn-text-color-<?= $i?>" name="content[<?= $i?>][btn_text_color]" value="<?= e($itemBtnTextColor) ?>" class="color-picker box-color-picker"
                        style-color="wpx35 hpx35 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
                      <p class="x14 bold500 texto"><?= e($itemBtnTextColor) ?></p>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Datos de la suscripción -->
              <div class="flex-column gap12 w100 p12 br10 back-card-graphic shadow-card-graphic">
                <div class="flex-column gap2">
                  <p class="x13 bold600 texto">Datos de la suscripción</p>
                  <span class="x11 text-muted">Configura los campos a solicitar a tus suscriptores</span>
                </div>

                <!-- Correo electrónico (siempre obligatorio) -->
                <div class="flex-column gap5 w100">
                  <span class="x12 bold500 texto flex-row center-start gap5"><?= svg("email", "x14") ?> Correo electrónico (Obligatorio)</span>
                  <input type="email" name="content[<?= $i?>][email]" class="back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto w100" value="<?= e($itemEmail) ?>" placeholder="Correo electrónico de destino / campaña">
                </div>

                <!-- Control para solicitar Nombre (opcional) -->
                <div class="flex-column gap8 w100 p10 br10 back-card-graphic shadow-card-graphic">
                  <div class="flex-row center-between w100">
                    <div class="flex-column">
                      <p class="x13 bold500 texto flex-row center-start gap5"><?= svg("user", "x14") ?> Solicitar nombre</p>
                      <span class="x11 text-muted">Muestra el campo de nombre en el formulario</span>
                    </div>
                    <input type="checkbox" id="ask-name-switch-<?= $i?>" name="content[<?= $i?>][ask_name]" value="true" data-option="true,false" class="checkbox-switch campaign-toggle-field-switch" data-target="campaign-name-field-<?= $i?>" active="<?= $askName ? '1' : '2' ?>" <?= $askName ? 'checked' : '' ?>>
                  </div>
                  <div id="campaign-name-field-<?= $i?>" class="flex-column gap5 w100" style="<?= $askName ? '' : 'display: none;' ?>">
                    <input type="text" name="content[<?= $i?>][name]" class="back-card-graphic shadow-card-graphic hover-scale-soft br10 p8 texto w100 x13" value="<?= e($itemName) ?>" placeholder="Nombre o remitente de la campaña (opcional)">
                  </div>
                </div>

                <!-- Control para solicitar WhatsApp (opcional) -->
                <div class="flex-column gap8 w100 p10 br10 back-card-graphic shadow-card-graphic">
                  <div class="flex-row center-between w100">
                    <div class="flex-column">
                      <p class="x13 bold500 texto flex-row center-start gap5"><?= svg("whatsapp", "x14") ?> Solicitar número de WhatsApp</p>
                      <span class="x11 text-muted">Muestra el campo de WhatsApp en el formulario</span>
                    </div>
                    <input type="checkbox" id="ask-whatsapp-switch-<?= $i?>" name="content[<?= $i?>][ask_whatsapp]" value="true" data-option="true,false" class="checkbox-switch campaign-toggle-field-switch" data-target="campaign-whatsapp-field-<?= $i?>" active="<?= $askWhatsapp ? '1' : '2' ?>" <?= $askWhatsapp ? 'checked' : '' ?>>
                  </div>
                  <div id="campaign-whatsapp-field-<?= $i?>" class="flex-column gap5 w100" style="<?= $askWhatsapp ? '' : 'display: none;' ?>">
                    <input type="tel" name="content[<?= $i?>][whatsapp]" class="back-card-graphic shadow-card-graphic hover-scale-soft br10 p8 texto w100 x13" value="<?= e($itemWhatsapp) ?>" placeholder="Número de WhatsApp de contacto (opcional, ej: +54911...)">
                  </div>
                </div>
              </div>

              <!-- Contador opcional -->
              <div class="flex-row center-between w100 p10 br10 back-card-graphic shadow-card-graphic">
                <div class="flex-column">
                  <p class="x13 bold500 texto">Contador regresivo</p>
                  <span class="x11 text-muted">Muestra una cuenta regresiva para el lanzamiento o cierre</span>
                </div>
                <input type="checkbox" id="countdown-switch-<?= $i?>" name="content[<?= $i?>][has_countdown]" value="true" data-option="true,false" class="checkbox-switch campaign-countdown-switch" data-target="campaign-countdown-date-<?= $i?>" active="<?= $hasCountdown ? '1' : '2' ?>" <?= $hasCountdown ? 'checked' : '' ?>>
              </div>

              <div id="campaign-countdown-date-<?= $i?>" class="flex-column gap12 w100 p10 br10 back-card-graphic shadow-card-graphic" style="<?= $hasCountdown ? '' : 'display: none;' ?>">
                <div class="flex-row center-between w100">
                  <p class="x12 bold500 texto">Fecha y hora límite del contador</p>
                  <?php if ($isCountdownExpired) : ?>
                    <span class="x11 bold500 back-danger textc p2 px6 br10" title="El tiempo límite ha expirado. El bloque se encuentra oculto públicamente.">Tiempo límite alcanzado (Oculto)</span>
                  <?php endif; ?>
                </div>
                <input type="datetime-local" name="content[<?= $i?>][countdown_date]" class="back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto w100 x13" value="<?= e($itemCountdownDate) ?>">

                <!-- Selector de Tamaño del Texto del Contador (Pequeño / Mediano / Grande) -->
                <div class="flex-column gap8 w100 mt5">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Tamaño del texto del contador</p>
                    <span class="x11 text-muted">Escala de los números y etiquetas de la cuenta regresiva</span>
                  </div>
                  <div class="flex-row center-between gap10 w100">
                    <input type="radio" id="campaign-countdown-text-size-sm-<?= $i?>" name="content[<?= $i?>][countdown_text_size]" value="small" class="hidden-radio" <?= ($itemCountdownTextSize === 'small') ? 'checked' : '' ?>>
                    <label for="campaign-countdown-text-size-sm-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Texto pequeño">
                      <span class="bold500 x12">Pequeño</span>
                    </label>

                    <input type="radio" id="campaign-countdown-text-size-md-<?= $i?>" name="content[<?= $i?>][countdown_text_size]" value="medium" class="hidden-radio" <?= ($itemCountdownTextSize === 'medium') ? 'checked' : '' ?>>
                    <label for="campaign-countdown-text-size-md-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Texto mediano">
                      <span class="bold500 x12">Mediano</span>
                    </label>

                    <input type="radio" id="campaign-countdown-text-size-lg-<?= $i?>" name="content[<?= $i?>][countdown_text_size]" value="large" class="hidden-radio" <?= ($itemCountdownTextSize === 'large') ? 'checked' : '' ?>>
                    <label for="campaign-countdown-text-size-lg-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Texto grande">
                      <span class="bold500 x12">Grande</span>
                    </label>
                  </div>
                </div>

                <!-- Selector de Tamaño del Widget del Contador (Pequeño / Mediano / Grande) -->
                <div class="flex-column gap8 w100 mt5">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Tamaño del widget del contador</p>
                    <span class="x11 text-muted">Espaciado y dimensiones del bloque de la cuenta regresiva</span>
                  </div>
                  <div class="flex-row center-between gap10 w100">
                    <input type="radio" id="campaign-countdown-widget-size-sm-<?= $i?>" name="content[<?= $i?>][countdown_widget_size]" value="small" class="hidden-radio" <?= ($itemCountdownWidgetSize === 'small') ? 'checked' : '' ?>>
                    <label for="campaign-countdown-widget-size-sm-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Widget pequeño">
                      <span class="bold500 x12">Pequeño</span>
                    </label>

                    <input type="radio" id="campaign-countdown-widget-size-md-<?= $i?>" name="content[<?= $i?>][countdown_widget_size]" value="medium" class="hidden-radio" <?= ($itemCountdownWidgetSize === 'medium') ? 'checked' : '' ?>>
                    <label for="campaign-countdown-widget-size-md-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Widget mediano">
                      <span class="bold500 x12">Mediano</span>
                    </label>

                    <input type="radio" id="campaign-countdown-widget-size-lg-<?= $i?>" name="content[<?= $i?>][countdown_widget_size]" value="large" class="hidden-radio" <?= ($itemCountdownWidgetSize === 'large') ? 'checked' : '' ?>>
                    <label for="campaign-countdown-widget-size-lg-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Widget grande">
                      <span class="bold500 x12">Grande</span>
                    </label>
                  </div>
                </div>

                <!-- Colores del contador -->
                <div class="flex-column gap8 w100 mt5">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Colores del contador</p>
                    <span class="x11 text-muted">Personaliza el fondo y el texto de la cuenta regresiva</span>
                  </div>

                  <!-- Color de fondo del contador -->
                  <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
                    <div class="flex-column">
                      <p class="x13 bold500 texto">Color de fondo del contador</p>
                      <span class="x11 text-muted">Fondo del widget de cuenta regresiva</span>
                    </div>
                    <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
                      <label data-trigger-color="campaign-countdown-bg-color-<?= $i?>" class="flex-row center-start p8 gap10 pointer">
                        <input type="color" id="campaign-countdown-bg-color-<?= $i?>" name="content[<?= $i?>][countdown_bg_color]" value="<?= e($itemCountdownBgColor) ?>" class="color-picker box-color-picker"
                          style-color="wpx35 hpx35 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
                        <p class="x14 bold500 texto"><?= e($itemCountdownBgColor) ?></p>
                      </label>
                    </div>
                  </div>

                  <!-- Color de texto del contador -->
                  <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
                    <div class="flex-column">
                      <p class="x13 bold500 texto">Color de texto del contador</p>
                      <span class="x11 text-muted">Texto y números de la cuenta regresiva</span>
                    </div>
                    <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
                      <label data-trigger-color="campaign-countdown-text-color-<?= $i?>" class="flex-row center-start p8 gap10 pointer">
                        <input type="color" id="campaign-countdown-text-color-<?= $i?>" name="content[<?= $i?>][countdown_text_color]" value="<?= e($itemCountdownTextColor) ?>" class="color-picker box-color-picker"
                          style-color="wpx35 hpx35 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
                        <p class="x14 bold500 texto"><?= e($itemCountdownTextColor) ?></p>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

            <?php elseif ($itemType === 'banner') : ?>
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
                $hasSize = !empty($itemSize) && in_array($itemSize, ['720x1024', '720x720', '1024x720'], true);
              ?>

              <!-- Selector de tamaño del Banner (3 tamaños: 720x1024, 720x720, 1024x720) -->
              <div class="flex-column gap8 w100">
                <div class="flex-column gap2">
                  <p class="x13 bold600 texto">Tamaño del banner</p>
                  <span class="x11 text-muted">Selecciona la proporción de la imagen antes de subirla</span>
                </div>
                <div class="flex-row center-between gap10 w100">
                  <input type="radio" id="banner-size-vert-<?= $i?>" name="content[<?= $i?>][size]" value="720x1024" class="hidden-radio banner-size-radio" data-index="<?= $i ?>" <?= ($itemSize === '720x1024') ? 'checked' : '' ?>>
                  <label for="banner-size-vert-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Vertical (720px x 1024px)">
                    <?= svg("film", "x16") ?>
                    <span class="bold500 x13">720 x 1024</span>
                  </label>

                  <input type="radio" id="banner-size-sq-<?= $i?>" name="content[<?= $i?>][size]" value="720x720" class="hidden-radio banner-size-radio" data-index="<?= $i ?>" <?= ($itemSize === '720x720') ? 'checked' : '' ?>>
                  <label for="banner-size-sq-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Cuadrado (720px x 720px)">
                    <?= svg("grid", "x16") ?>
                    <span class="bold500 x13">720 x 720</span>
                  </label>

                  <input type="radio" id="banner-size-horiz-<?= $i?>" name="content[<?= $i?>][size]" value="1024x720" class="hidden-radio banner-size-radio" data-index="<?= $i ?>" <?= ($itemSize === '1024x720') ? 'checked' : '' ?>>
                  <label for="banner-size-horiz-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Horizontal (1024px x 720px)">
                    <?= svg("bars", "x16") ?>
                    <span class="bold500 x13">1024 x 720</span>
                  </label>
                </div>
              </div>

              <!-- Imagen del banner -->
              <div class="flex-column gap8 w100">
                <div class="flex-row center-between w100">
                  <p class="x13 bold600 texto">Imagen del banner</p>
                  <span id="banner-size-hint-<?= $i ?>" class="x11 text-muted banner-size-hint flex-row center-start gap4 <?= $hasSize ? 'hidden' : '' ?>">
                    <?= svg("info", "x13") ?> Elige primero un tamaño para habilitar el recorte y subida
                  </span>
                </div>
                <div class="flex-row center-between gap10">
                  <div class="flex-row center-center gap10 relative">
                    <figure class="wpx50 hpx50 ar-square back-card-graphic shadow-card-graphic hover-scale-soft br10">
                      <img src="<?= e($displayImgSrc) ?>" alt="Imagen del banner" class="cover">
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
                  <div id="banner-crop-btn-wrap-<?= $i ?>" class="br15 p10 back-card-graphic shadow-card-graphic hover-scale-soft banner-crop-btn-wrapper <?= !$hasSize ? 'opacity-40 pointer-events-none' : '' ?>">
                    <input type="file" 
                      id="content_img_banner_<?= $i ?>"
                      name="content_img_<?= $i ?>" 
                      class="selectAndCropImage btn-style-classes no-preview process-auto-submit banner-crop-input"
                      placeholder="Subir imagen" 
                      cropping-size="<?= e($hasSize ? $itemSize : '720x1024') ?>"
                      box-image="back-menu-sidebar texto br15 back-card-graphic shadow-card-graphic hover-scale-soft p20 shadow-1"
                      box-btn-image="p10 back7 back-card-graphic shadow-card-graphic hover-scale-soft texto br15 pointer"
                      <?= !$hasSize ? 'disabled' : '' ?>>
                  </div>
                </div>
              </div>

              <!-- Opciones de color del Banner (Fondo y Opacidad de capa) -->
              <div class="flex-column gap12 w100 p12 br10 back-card-graphic shadow-card-graphic">
                <div class="flex-column gap2">
                  <p class="x13 bold600 texto flex-row center-start gap6"><?= svg("palette", "x16") ?> Colores del banner</p>
                  <span class="x11 text-muted">Personaliza el color de fondo y la opacidad de la capa sobre la imagen</span>
                </div>

                <!-- 1. Color de fondo del bloque -->
                <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
                  <div class="flex-column">
                    <p class="x13 bold500 texto">Color de fondo del bloque</p>
                    <span class="x11 text-muted">Afecta al contenedor del banner</span>
                  </div>
                  <div class="back-card-graphic shadow-card-graphic hover-scale-soft wpx140 br15">
                    <label data-trigger-color="banner-color-<?= $i?>" class="flex-row center-start p8 gap10 pointer">
                      <input type="color" id="banner-color-<?= $i?>" name="content[<?= $i?>][bg_color]" value="<?= e($itemBgColor) ?>" class="color-picker box-color-picker"
                        style-color="wpx35 hpx35 br50" style-box="br15 p10 w-auto shadow-1 back-color-picker">
                      <p class="x14 bold500 texto"><?= e($itemBgColor) ?></p>
                    </label>
                  </div>
                </div>

                <!-- 2. Opacidad de la imagen -->
                <div class="flex-row center-between flex-column-sml top-start-sml gap10 w100">
                  <div class="flex-column">
                    <p class="x13 bold500 texto">Opacidad de la imagen</p>
                    <span class="x11 text-muted">Ajusta la transparencia de la imagen sobre el color de fondo</span>
                  </div>
                  <div class="flex-row center-end gap10 w-sml-100">
                    <span id="banner-opacity-val-<?= $i?>" class="x14 bold600 texto wpx40 text-right"><?= $itemBgOpacity ?>%</span>
                    <input type="range" name="content[<?= $i?>][bg_opacity]" min="0" max="100" step="1" value="<?= $itemBgOpacity ?>" class="pointer custom-range-slider campaign-opacity-slider" data-val-target="banner-opacity-val-<?= $i?>" style="--range-progress: <?= $itemBgOpacity ?>%;">
                  </div>
                </div>
              </div>

              <!-- Entrada de enlace -->
              <div class="flex-column gap5 w100">
                <p class="x13 bold600 texto">Enlace del banner</p>
                <input type="text" name="content[<?= $i?>][url]" class="content-url-input back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto" value="<?= e($itemUrl) ?>" placeholder="URL de destino (ej: https://...)">
              </div>

            <?php elseif ($itemType === 'title') : ?>
              <!-- Bloque Título -->
              <div class="flex-column gap15 w100">
                <!-- Campo de texto del título -->
                <div class="flex-column gap5 w100">
                  <p class="x12 bold500 texto">Texto del título</p>
                  <input type="text" name="content[<?= $i?>][title]" class="content-title-input back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto" value="<?= e($itemTitle) ?>" placeholder="Escribe el texto del título...">
                </div>

                <!-- Selector de Tamaño del Título (Pequeño 18px, Mediano 20px, Grande 24px) -->
                <div class="flex-column gap8 w100">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Tamaño del título</p>
                    <span class="x11 text-muted">Pequeño (18px), Mediano (20px) o Grande (24px)</span>
                  </div>
                  <div class="flex-row center-between gap10 w100">
                    <input type="radio" id="title-size-sm-<?= $i?>" name="content[<?= $i?>][title_size]" value="small" class="hidden-radio" <?= ($itemTitleSize === 'small') ? 'checked' : '' ?>>
                    <label for="title-size-sm-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Pequeño (18px)">
                      <span class="bold500 x12">Pequeño (18px)</span>
                    </label>

                    <input type="radio" id="title-size-md-<?= $i?>" name="content[<?= $i?>][title_size]" value="medium" class="hidden-radio" <?= ($itemTitleSize === 'medium') ? 'checked' : '' ?>>
                    <label for="title-size-md-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Mediano (20px)">
                      <span class="bold500 x12">Mediano (20px)</span>
                    </label>

                    <input type="radio" id="title-size-lg-<?= $i?>" name="content[<?= $i?>][title_size]" value="large" class="hidden-radio" <?= ($itemTitleSize === 'large') ? 'checked' : '' ?>>
                    <label for="title-size-lg-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Grande (24px)">
                      <span class="bold500 x12">Grande (24px)</span>
                    </label>
                  </div>
                </div>

                <!-- Selector de Grosor / Bold (500 por defecto, 600, 700, 900) -->
                <div class="flex-column gap8 w100">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Grosor de la fuente (Bold)</p>
                    <span class="x11 text-muted">Por defecto font weight 500</span>
                  </div>
                  <div class="flex-row center-between gap10 w100">
                    <input type="radio" id="title-weight-500-<?= $i?>" name="content[<?= $i?>][title_weight]" value="500" class="hidden-radio" <?= ($itemTitleWeight === '500') ? 'checked' : '' ?>>
                    <label for="title-weight-500-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Grosor 500">
                      <span class="bold500 x12">500</span>
                    </label>

                    <input type="radio" id="title-weight-600-<?= $i?>" name="content[<?= $i?>][title_weight]" value="600" class="hidden-radio" <?= ($itemTitleWeight === '600') ? 'checked' : '' ?>>
                    <label for="title-weight-600-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Grosor 600">
                      <span class="bold600 x12">600</span>
                    </label>

                    <input type="radio" id="title-weight-700-<?= $i?>" name="content[<?= $i?>][title_weight]" value="700" class="hidden-radio" <?= ($itemTitleWeight === '700') ? 'checked' : '' ?>>
                    <label for="title-weight-700-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Grosor 700">
                      <span class="bold700 x12">700</span>
                    </label>

                    <input type="radio" id="title-weight-900-<?= $i?>" name="content[<?= $i?>][title_weight]" value="900" class="hidden-radio" <?= ($itemTitleWeight === '900') ? 'checked' : '' ?>>
                    <label for="title-weight-900-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Grosor 900">
                      <span class="bold900 x12">900</span>
                    </label>
                  </div>
                </div>
              </div>

            <?php elseif ($itemType === 'text') : ?>
              <!-- Bloque Texto -->
              <div class="flex-column gap15 w100">
                <!-- Textarea para el texto -->
                <div class="flex-column gap5 w100">
                  <p class="x12 bold500 texto">Contenido del texto</p>
                  <textarea name="content[<?= $i?>][text]" rows="4" class="content-text-input back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto w100" placeholder="Escribe tu texto aquí..." style="resize: vertical; font-family: inherit;"><?= e($itemText) ?></textarea>
                </div>

                <!-- Selector de Grosor / Bold (400 por defecto, 500) -->
                <div class="flex-column gap8 w100">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Grosor de la fuente (Font weight)</p>
                    <span class="x11 text-muted">Normal (400 por defecto) o Medio (500)</span>
                  </div>
                  <div class="flex-row center-between gap10 w100">
                    <input type="radio" id="text-weight-400-<?= $i?>" name="content[<?= $i?>][text_weight]" value="400" class="hidden-radio" <?= ($itemTextWeight === '400') ? 'checked' : '' ?>>
                    <label for="text-weight-400-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Normal (400)">
                      <span class="bold400 x12">Normal (400)</span>
                    </label>

                    <input type="radio" id="text-weight-500-<?= $i?>" name="content[<?= $i?>][text_weight]" value="500" class="hidden-radio" <?= ($itemTextWeight === '500') ? 'checked' : '' ?>>
                    <label for="text-weight-500-<?= $i?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Medio (500)">
                      <span class="bold500 x12">Medio (500)</span>
                    </label>
                  </div>
                </div>

                <!-- Selector de Posición / Alineación del Texto (Izquierda, Centro, Derecha) -->
                <div class="flex-column gap8 w100">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Posición del texto</p>
                    <span class="x11 text-muted">Alineación del texto (Izquierda, Centro o Derecha)</span>
                  </div>
                  <div class="flex-row center-between gap10 w100">
                    <input type="radio" id="text-align-left-<?= $i?>" name="content[<?= $i?>][text_align]" value="left" class="hidden-radio" <?= ($itemTextAlignVal === 'left') ? 'checked' : '' ?>>
                    <label for="text-align-left-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Alinear a la izquierda">
                      <?= svg("arrow-l-l", "x14") ?>
                      <span class="bold500 x13">Izquierda</span>
                    </label>

                    <input type="radio" id="text-align-center-<?= $i?>" name="content[<?= $i?>][text_align]" value="center" class="hidden-radio" <?= ($itemTextAlignVal === 'center') ? 'checked' : '' ?>>
                    <label for="text-align-center-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Alinear al centro">
                      <?= svg("bars", "x14") ?>
                      <span class="bold500 x13">Centro</span>
                    </label>

                    <input type="radio" id="text-align-right-<?= $i?>" name="content[<?= $i?>][text_align]" value="right" class="hidden-radio" <?= ($itemTextAlignVal === 'right') ? 'checked' : '' ?>>
                    <label for="text-align-right-<?= $i?>" class="flex-1 flex-row center-center gap8 w100 p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Alinear a la derecha">
                      <?= svg("arrow-r-l", "x14") ?>
                      <span class="bold500 x13">Derecha</span>
                    </label>
                  </div>
                </div>
              </div>

            <?php elseif ($itemType === 'separator') : ?>
              <!-- Configuración del Separador -->
              <div class="flex-column gap15 w100">
                <!-- Cuadrícula de Figuras (con ban.svg como primer icono para solo espacio) -->
                <div class="flex-column gap8 w100">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Figura del separador</p>
                    <span class="x11 text-muted">Selecciona una figura para la línea o el primer ícono para un espacio en blanco</span>
                  </div>
                  <div class="separator-icons-grid w100" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(44px, 1fr)); gap: 8px; max-height: 220px; overflow-y: auto; padding: 4px; box-sizing: border-box;">
                    <!-- Primer ícono: ban (solo espacio) -->
                    <input type="radio" id="sep-ico-none-<?= $i ?>" name="content[<?= $i ?>][separator_icon]" value="none" class="hidden-radio separator-icon-radio" <?= ($itemSepIcon === 'none' || $itemSepIcon === 'ban' || $itemSepMode === 'space') ? 'checked' : '' ?>>
                    <label for="sep-ico-none-<?= $i ?>" class="flex-row center-center p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Solo espacio (sin figura)" style="aspect-ratio: 1; font-size: 18px; box-sizing: border-box;">
                      <?= svg("ban", "x18") ?>
                    </label>

                    <?php foreach ($separatorIcons as $icoName) : ?>
                      <input type="radio" id="sep-ico-<?= e($icoName) ?>-<?= $i ?>" name="content[<?= $i ?>][separator_icon]" value="<?= e($icoName) ?>" class="hidden-radio separator-icon-radio" <?= ($itemSepIcon === $icoName && $itemSepMode !== 'space' && $itemSepIcon !== 'none') ? 'checked' : '' ?>>
                      <label for="sep-ico-<?= e($icoName) ?>-<?= $i ?>" class="flex-row center-center p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="<?= e($icoName) ?>" style="aspect-ratio: 1; font-size: 18px; box-sizing: border-box;">
                        <?= svg("Separator/" . $icoName, "x18") ?>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>

                <!-- Opciones de Espacio (Altura del espacio en px, visible cuando se elige ban / solo espacio) -->
                <div class="separator-space-options flex-column gap8 w100" style="<?= ($itemSepIcon === 'none' || $itemSepIcon === 'ban' || $itemSepMode === 'space') ? 'display: flex;' : 'display: none;' ?>">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Tamaño del espacio</p>
                    <span class="x11 text-muted">Altura del espacio vertical entre bloques</span>
                  </div>
                  <div class="flex-row center-between gap10 w100">
                    <input type="radio" id="space-size-20-<?= $i ?>" name="content[<?= $i ?>][space_size]" value="20" class="hidden-radio" <?= ($itemSpaceSize === '20') ? 'checked' : '' ?>>
                    <label for="space-size-20-<?= $i ?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Pequeño (20px)">
                      <span class="bold500 x12">Pequeño (20px)</span>
                    </label>

                    <input type="radio" id="space-size-40-<?= $i ?>" name="content[<?= $i ?>][space_size]" value="40" class="hidden-radio" <?= ($itemSpaceSize === '40') ? 'checked' : '' ?>>
                    <label for="space-size-40-<?= $i ?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Medio (40px)">
                      <span class="bold500 x12">Medio (40px)</span>
                    </label>

                    <input type="radio" id="space-size-60-<?= $i ?>" name="content[<?= $i ?>][space_size]" value="60" class="hidden-radio" <?= ($itemSpaceSize === '60') ? 'checked' : '' ?>>
                    <label for="space-size-60-<?= $i ?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Grande (60px)">
                      <span class="bold500 x12">Grande (60px)</span>
                    </label>
                  </div>
                </div>

                <!-- Opciones de Tamaño del Separador de Figuras (Grande Completo, Mediano 60%, Pequeño 1 figura) -->
                <div class="separator-size-options flex-column gap8 w100" style="<?= ($itemSepIcon !== 'none' && $itemSepIcon !== 'ban' && $itemSepMode !== 'space') ? 'display: flex;' : 'display: none;' ?>">
                  <div class="flex-column gap2">
                    <p class="x13 bold500 texto">Ancho del separador</p>
                    <span class="x11 text-muted">Elige el ancho o cantidad de figuras del separador</span>
                  </div>
                  <div class="flex-row center-between gap10 w100">
                    <input type="radio" id="sep-size-large-<?= $i ?>" name="content[<?= $i ?>][separator_size]" value="large" class="hidden-radio separator-size-radio" <?= ($itemSepSize === 'large') ? 'checked' : '' ?>>
                    <label for="sep-size-large-<?= $i ?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Abarca el ancho completo">
                      <span class="bold500 x12">Grande (Completo)</span>
                    </label>

                    <input type="radio" id="sep-size-medium-<?= $i ?>" name="content[<?= $i ?>][separator_size]" value="medium" class="hidden-radio separator-size-radio" <?= ($itemSepSize === 'medium') ? 'checked' : '' ?>>
                    <label for="sep-size-medium-<?= $i ?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Abarca un 60% del ancho">
                      <span class="bold500 x12">Mediano (60%)</span>
                    </label>

                    <input type="radio" id="sep-size-small-<?= $i ?>" name="content[<?= $i ?>][separator_size]" value="small" class="hidden-radio separator-size-radio" <?= ($itemSepSize === 'small') ? 'checked' : '' ?>>
                    <label for="sep-size-small-<?= $i ?>" class="flex-1 flex-row center-center gap6 w100 p8 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer texto" title="Solo una figura en el medio">
                      <span class="bold500 x12">Pequeño (1 figura)</span>
                    </label>
                  </div>
                </div>
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