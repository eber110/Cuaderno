<?php
  /** 
   * @var mixed $card 
   * @var int $dataContent
   */
  $groupData  = $card["content"][$dataContent] ?? [];
  $layout     = $groupData["layout"] ?? "grid";
  $products   = is_array($groupData["products"] ?? null) ? $groupData["products"] : [];
  $profile    = $card["profile"] ?? "";
  $cardStyle  = $card["style"] ?? "buttonRegular";
  $groupTitle = trim($groupData["title"] ?? "");

  if (count($products) % 2 !== 0) {
    $layout = "slide";
  }
?>

<?php if ($layout === "grid") : ?>
  <!-- Formato Cuadricula (Grid 2 Columnas) -->
  <div class="product-group-grid-wrapper flex-column gap8 w100">
    <?php if (!empty($groupTitle)) : ?>
      <p class="bold700 x15 text-c title-color w100"><?= e($groupTitle) ?></p>
    <?php endif; ?>

    <div class="product-group-grid w100" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
      <?php foreach ($products as $pIdx => $prod) :
        $pTitle      = $prod["title"] ?? '';
        $pUrl        = $prod["url"] ?? '#';
        $pPrice      = $prod["price"] ?? '';
        $pOffer      = $prod["offer"] ?? false;
        $pDiscount   = $prod["discount"] ?? '';
        $pPorcentage = $prod["porcentage"] ?? 0;
        $pImgSrc     = $prod["imgSrc"] ?? '';
        $pRawImgShow = $prod["imgShow"] ?? true;
        $pImgShow    = ($pRawImgShow === true || $pRawImgShow === 'true' || $pRawImgShow === 1 || $pRawImgShow === '1');
        $hasImg      = !empty($pImgSrc) && strpos($pImgSrc, 'no-image.webp') === false;

        if (trim($pTitle) === '' && trim($pUrl) === '#') continue;
      ?>
        <div class="product-grid-card theme-button pointer flex-column between-stretch p7 gap8 <?= ($card["borders"][0] == "br50") ? "br20" : $card["borders"][0] ?> <?= $card["shadow"] ?> position-relative" style="overflow: hidden;">
          <a href="<?= e($pUrl) ?>" target="_blank" class="track-link-click flex-column gap8 w100 h100" data-user="<?= e($profile) ?>" data-link-id="<?= e($pUrl) ?>" style="text-decoration: none; color: inherit;">
            <?php if ($pImgShow && $hasImg) : ?>
              <figure class="w100 ar-square overflow-hidden" style="background: rgba(0,0,0,0.03);">
                <img src="<?= e($pImgSrc) ?>" alt="<?= e($pTitle) ?>" class="cover w100 h100 <?= ($card["borders"][1] == "br50") ? "br12" : $card["borders"][1] ?>" fetchpriority="high">
              </figure>
            <?php endif; ?>

            <div class="flex-column gap4 w100 flex-1">
              <p class="bold500 w100 capitalize-p cut-phrase" cant-col="2">
                <?= svg("basket-shopping", "x20") ?> <?= e($pTitle) ?>
              </p>

              <div class="mt-auto flex-row center-between w100 pr25">
                <?php if (!$pOffer || empty($pDiscount)) : ?>
                  <?php if ($pPrice !== '') : ?>
                    <p class="bold500">$<?= e($pPrice) ?></p>
                  <?php endif; ?>
                <?php else : ?>
                  <div class="flex-column gap0">
                    <p class="inactive x16" style="text-decoration: line-through;">$<?= e($pPrice) ?></p>
                    <p class="bold500 text-success">$<?= e($pDiscount) ?></p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </a>

          <!-- Boton de compartir modal -->
          <div class="flex-column center-end">
            <div class="theme-button-menu p3 br100 flex-column center-center modal-btn animated darken pointer" style="width: 24px; height: 24px;">
              <?= svg("ellipsis-vertical", "x14") ?>
            </div>

            <div class="hidden">
              <div class="flex-column center-center w100">
                <div class="wpx520 w-sml-100 back-modal-item br-desk-15 br-mid-15 br-sml-0 p20 text-menu-modal text-protected h-dvh-sml overflow-y-scroll">
                  <?php _part("User.modalProductShare", [
                    "data" => [
                      "price"      => $pPrice,
                      "offer"      => $pOffer,
                      "discount"   => $pDiscount,
                      "porcentage" => $pPorcentage,
                      "img"        => $prod["img"] ?? '',
                      "imgSrc"     => $pImgSrc,
                      "title"      => $pTitle,
                      "profile"    => $profile,
                      "url"        => $pUrl,
                      "share"      => $prod["share"] ?? [],
                      "metaImg"    => $prod["metaImg"] ?? '',
                    ],
                    "card" => $card
                  ]); ?>
                </div>
              </div>
            </div>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  </div>

<?php else : ?>
  <!-- Formato Carrusel Deslizante (Slide Horizontal) -->
  <div class="product-group-slide-wrapper flex-column gap8 w100">
    <?php if (!empty($groupTitle)) : ?>
      <p class="bold700 x15 text-c title-color w100"><?= e($groupTitle) ?></p>
    <?php endif; ?>

    <div class="product-group-slide-container position-relative">
      <button type="button" class="product-slide-btn product-slide-prev is-hidden" aria-label="Anterior">
        <?= svg("angle-l", "x14") ?>
      </button>
      <button type="button" class="product-slide-btn product-slide-next" aria-label="Siguiente">
        <?= svg("angle-r", "x14") ?>
      </button>

      <div class="product-group-slide-track">
        <?php foreach ($products as $pIdx => $prod) :
          $pTitle      = $prod["title"] ?? '';
          $pUrl        = $prod["url"] ?? '#';
          $pPrice      = $prod["price"] ?? '';
          $pOffer      = $prod["offer"] ?? false;
          $pDiscount   = $prod["discount"] ?? '';
          $pPorcentage = $prod["porcentage"] ?? 0;
          $pImgSrc     = $prod["imgSrc"] ?? '';
          $pRawImgShow = $prod["imgShow"] ?? true;
          $pImgShow    = ($pRawImgShow === true || $pRawImgShow === 'true' || $pRawImgShow === 1 || $pRawImgShow === '1');
          $hasImg      = !empty($pImgSrc) && strpos($pImgSrc, 'no-image.webp') === false;

          if (trim($pTitle) === '' && trim($pUrl) === '#') continue;
        ?>
          <div class="product-slide-card theme-button pointer flex-column p7 gap8 <?= ($card["borders"][0] == "br50") ? "br20" : $card["borders"][0] ?> <?= $card["shadow"] ?> position-relative" style="overflow: hidden;">
            <a href="<?= e($pUrl) ?>" target="_blank" class="track-link-click flex-column gap8 w100 h100" data-user="<?= e($profile) ?>" data-link-id="<?= e($pUrl) ?>" style="text-decoration: none; color: inherit;">
              <?php if ($pImgShow && $hasImg) : ?>
                <figure class="w100 ar-square overflow-hidden" style="background: rgba(0,0,0,0.03);">
                  <img src="<?= e($pImgSrc) ?>" alt="<?= e($pTitle) ?>" class="cover w100 h100 <?= ($card["borders"][1] == "br50") ? "br12" : $card["borders"][1] ?>" fetchpriority="high">
                </figure>
              <?php endif; ?>

              <div class="flex-column gap4 w100 flex-1">
                <p class="bold500 w100 capitalize-p cut-phrase" cant-col="2">
                  <?= svg("basket-shopping", "x20") ?> <?= e($pTitle) ?>
                </p>

                <div class="mt-auto flex-row center-between w100 pr25">
                  <?php if (!$pOffer || empty($pDiscount)) : ?>
                    <?php if ($pPrice !== '') : ?>
                      <p class="bold500">$<?= e($pPrice) ?></p>
                    <?php endif; ?>
                  <?php else : ?>
                    <div class="flex-column gap0">
                      <p class="inactive x16" style="text-decoration: line-through;">$<?= e($pPrice) ?></p>
                      <p class="bold500 text-success">$<?= e($pDiscount) ?></p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </a>

            <!-- Boton de compartir modal -->
            <div class="flex-column center-end">
              <div class="theme-button-menu p3 br100 flex-column center-center modal-btn animated darken pointer" style="width: 24px; height: 24px;">
                <?= svg("ellipsis-vertical", "x14") ?>
              </div>

              <div class="hidden">
                <div class="flex-column center-center w100">
                  <div class="wpx520 w-sml-100 back-modal-item br-desk-15 br-mid-15 br-sml-0 p20 text-menu-modal text-protected h-dvh-sml overflow-y-scroll">
                    <?php _part("User.modalProductShare", [
                      "data" => [
                        "price"      => $pPrice,
                        "offer"      => $pOffer,
                        "discount"   => $pDiscount,
                        "porcentage" => $pPorcentage,
                        "img"        => $prod["img"] ?? '',
                        "imgSrc"     => $pImgSrc,
                        "title"      => $pTitle,
                        "profile"    => $profile,
                        "url"        => $pUrl,
                        "share"      => $prod["share"] ?? [],
                        "metaImg"    => $prod["metaImg"] ?? '',
                      ],
                      "card" => $card
                    ]); ?>
                  </div>
                </div>
              </div>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

