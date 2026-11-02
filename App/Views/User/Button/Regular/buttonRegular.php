<?php
  /** 
   * @var mixed $card 
   * @var mixed $dataContent
  */
  $img = $card["content"][$dataContent]["img"] ?? '';
  $imgSrc = $card["content"][$dataContent]["imgSrc"] ?? '';
  $content = $card["content"][$dataContent]["title"] ?? '';
  $profile = $card["profile"];
  $url = $card["content"][$dataContent]["url"];
  $metaTitle = $card["content"][$dataContent]["metaTitle"];
  $metaDesc = $card["content"][$dataContent]["metaDesc"];
  $metaImg = $card["content"][$dataContent]["metaImg"];
  $share = $card["content"][$dataContent]["share"] ?? [];

  $rawImgShow = $card["content"][$dataContent]["imgShow"] ?? true;
  $imgShow = ($rawImgShow === true || $rawImgShow === 'true' || $rawImgShow === 1 || $rawImgShow === '1');
  $hasImg = !empty($imgSrc) && strpos($imgSrc, 'no-image.webp') === false;
?>
<div data-content-index="<?= e((string)$dataContent) ?>" data-block-id="<?= e($blockId ?? ($card['content'][$dataContent]['id'] ?? '')) ?>" data-block-type="link" class="link-item-wrapper flex-row center-between wrap hpx65 w100 theme-button pointer <?= e($card["borders"][0] ?? '')?> <?= e($card["shadow"] ?? '')?>"<?= (isset($isActive) && !$isActive) ? ' style="display: none;"' : '' ?>>

  <a data-bind-attr="href:url,data-link-id:url" href="<?= eUrl($card["content"][$dataContent]["url"] ?? '#') ?>" target="_blank" class="flex-row center-start hpx65 wrap track-link-click" data-user="<?= e($profile) ?>" data-link-id="<?= e($url) ?>" style="text-decoration: none; color: inherit; flex-grow: 1; width: calc(65px - 100%);">
    <div class="wpx65 flex-row center-center">
      <figure data-bind-show="hasImg" class="ar-square p7" style="<?= ($imgShow && $hasImg) ? '' : 'display: none;' ?>">
        <img data-bind-attr="src:imgSrc" src="<?= eUrl($imgSrc) ?>" alt="" class="cover <?= e($card["borders"][1] ?? '')?>" referrerpolicy="no-referrer">
      </figure>
    </div>

    <p data-bind="title" class="flex-column center-center wrap bold500 w90 text-c cut-phrase" cant-col="2" style="flex-grow: 1; width: calc(134px - 100%);"><?= e($content);?></p>
  </a>

  <div class="flex-column center-end pr15 wrap">

    <div class="theme-button-menu p3 br100 flex-column center-center z-index-10 modal-btn animated darken">
      <?= svg("ellipsis-vertical", "x14");?>
    </div>

    <div class="hidden">
      <div class="flex-column center-center w100 wrap">
        <div class="wpx520 w-sml-100 back-modal-item br-desk-15 br-mid-15 br-sml-0 p20 text-menu-modal text-protected h-dvh-sml overflow-y-scroll">
          <?php _part("User.modalButtonShare", 
          [
            "data" => 
            [
              "img" => $img,
              "imgSrc" => $imgSrc,
              "title" => $content,
              "profile" => $profile,
              "url" => $url,
              "share" => $share,
              "metaTitle" => $metaTitle,
              "metaDesc" => $metaDesc,
              "metaImg" => $metaImg,
            ],
            "card" => $card
          ])?>
        </div>
      </div>
    </div>

  </div>
  
</div>
