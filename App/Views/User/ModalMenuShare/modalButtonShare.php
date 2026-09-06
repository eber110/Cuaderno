<?php
  /** 
   * @var mixed $data 
   * @var mixed $card
   */
  $card = $card ?? [];
  $data = $data ?? [];

  // Prioridad 1: Imagen personalizada asignada por el usuario (img / imgSrc)
  $imgFile = $data["img"] ?? "";
  $hasCustomImg = (!empty($imgFile) && $imgFile !== "no-image.webp" && $imgFile !== "no-user.webp" && strpos($imgFile, "Custom/") === false);

  $modalImg = "";
  if ($hasCustomImg && file_exists(ROOT_PATH . "/Uploads/" . $imgFile)) {
    $modalImg = DIR_SHOW_MEDIA . $imgFile;
  }

  // Prioridad 2: Si no existe imagen personalizada en disco, verificar si existe metaImg rescatada
  if (empty($modalImg) && !empty($data["metaImg"]) && $data["metaImg"] !== "no-image.webp") {
    $meta = $data["metaImg"];
    if (str_starts_with($meta, "http://") || str_starts_with($meta, "https://")) {
      $modalImg = $meta;
    } elseif (str_starts_with($meta, "/") && file_exists(ROOT_PATH . $meta)) {
      $modalImg = $meta;
    } elseif (file_exists(ROOT_PATH . "/Uploads/" . $meta)) {
      $modalImg = DIR_SHOW_MEDIA . $meta;
    }
  }

  // Resolución de la descripción / título para compartir
  $metaDescRaw = trim($data["metaDesc"] ?? "");
  $titleRaw    = trim($data["title"] ?? $data["content"] ?? $data["metaTitle"] ?? "");

  // Si metaDesc contiene la descripción genérica o el fallback, ignorarlo
  if ($metaDescRaw === "Descripción del usuario" || $metaDescRaw === "Descripción del usuario " || $metaDescRaw === ($card["desc"] ?? "Descripción del usuario")) {
    $metaDescRaw = "";
  }

  $displayDesc = "";
  if (!empty($metaDescRaw)) {
    $displayDesc = $metaDescRaw;
  } elseif (!empty($titleRaw)) {
    $displayDesc = $titleRaw;
  }

  // Garantizar que existan enlaces de compartir si se cuenta con la URL del enlace
  if (empty($data["share"]) && !empty($data["url"]) && $data["url"] !== "#") {
    $acceptedLinks = \App\Controllers\DesignControllers::orderShare();
    $data["share"] = \Base\Module\ShareButtonModule::share($data["url"], $card["desc"] ?? "", $acceptedLinks);
  }
?>
<div class="flex-column center-center gap15 back-modal-item">
  <p class="no-desk no-tablet fixed top right pointer mt15 mr15 modal-close-button z-index-20"><?= svg("xmark")?></p>
  <p class="no-phone absolute top right pointer modal-close-button z-index-20"><?= svg("xmark")?></p>

  <p class="bold600 pb-sml-10">Comparte este link</p>

  <a href="<?= $data["url"] ?? '#' ?>" target="_blank" class="flex-column center-center gap5 wpx320 p20 |p-sml-10 br20 border-card-modal pointer" style="background-color: oklch(from <?= $card["back"] ?? '#d6d6d6' ?> calc(l * 0.40) calc(c - 0.04) h /85%); color: <?= $card["colorText"] ?? '#383838' ?> !important;">
    <?php if (!empty($modalImg)) : ?>
      <figure class="ar-square wpx200 |wpx-sml-160 br15">
        <img src="<?= e($modalImg) ?>" alt="<?= e($displayDesc) ?>" class="cover">
      </figure>
    <?php endif; ?>

    <?php if (!empty($displayDesc)) : ?>
      <p class="bold500 text-c bold400 x20 x-sml-20 textw"><?= e($displayDesc) ?></p>
    <?php endif; ?>
    <p class="x16 text-c cut-phrase textw" cant-col="1"><?= e(urldecode($data["url"] ?? '')) ?></p>
  </a>

  <?php if (!empty($data["share"]) && is_array($data["share"])) : ?>
    <div class="w100 p0 flex-row center-center wrap inline-carousel mt10" data-loop="false" data-gap="25px">
      
      <div class="ic-prev border-card-modal back-body br100 ar-square wpx40 hpx40 flex-row center-center text pointer border-none absolute left" style="flex-shrink: 0; top: 50%; transform: translateY(-50%); z-index: 10; margin-left: 5px; transition: opacity 0.3s;">
        <?= svg("angle-l", "x30 opacity-slide-circle");?>
      </div>

      <div class="ic-track w100">
        <?php foreach ($data["share"] as $networkName => $shareUrl) : ?>
          <a href="<?= $shareUrl ?>" target="_blank" aria-label="<?= e($networkName) ?>" 
            class="ic-item p5 br100 btn-share-profile hpx50 hpx-sml-40 wpx50 wpx-sml-40 flex-row center-center pointer" style="flex-shrink: 0;">
            <?= svg($networkName, "x30 x-sml-25"); ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="ic-next border-card-modal back-body br100 ar-square wpx40 hpx40 flex-row center-center text pointer border-none absolute right" style="flex-shrink: 0; top: 50%; transform: translateY(-50%); z-index: 10; margin-right: 5px; transition: opacity 0.3s;">
        <?= svg("angle-r", "x30 opacity-slide-circle");?>
      </div>
      
    </div>
  <?php endif; ?>

  <div class="flex-column top-start gap20 gap-sml-10 w100 mt20 mt5">
    <div class="">
      <p class="bold700">Únete a <?= e($card["profile"] ?? ($data["profile"] ?? '')) ?> en Clikhub.</p>
      <p>Un solo enlace, todas tus redes. Tu espacio personal gratis para conectar a tu audiencia con todo lo que creas.</p>
    </div>

    <div class="flex-row center-center gap15 w100">
      <a href="/registrar" class="br50 w100 btn-share-register p15 p-sml-10 flex-row center-center text-c bold700">Regístrate gratis</a>
      <a href="/" class="br50 w100 btn-share-see-more p15 p-sml-10 flex-row center-center text-c bold700">Descubre más</a>
    </div>
  </div>

</div>