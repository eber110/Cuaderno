<?php
  /** 
   * @var mixed $card 
   * @var mixed $dataContent
  */
  //var_dump($data);
  $type = $card["content"][$dataContent]["type"] ?? '';
  $price = $card["content"][$dataContent]["price"] ?? '';
  $offer = $card["content"][$dataContent]["offer"] ?? '';
  $porcentage = $card["content"][$dataContent]["porcentage"] ?? '';
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
<div class="flex-row center-between wrap w100 theme-button pointer <?= ($card["borders"][0] == "br50") ? "br20" : $card["borders"][0]?> <?= $card["shadow"]?>">

  <a href="<?= $card["content"][$dataContent]["url"] ?? '#' ?>" target="_blank" class="flex-row center-start wrap track-link-click" data-user="<?= e($profile) ?>" data-link-id="<?= e($url) ?>" style="text-decoration: none; color: inherit; flex-grow: 1; width: calc(65px - 100%);">
    <div class="w50 h50 flex-row center-center">
      <?php if ($imgShow && $hasImg) :?>
        <figure class="ar-square p7">
          <img src="<?= $imgSrc ?>" alt="" class="cover <?= ($card["borders"][1] == "br50") ? "br12" : $card["borders"][1]?>">
        </figure>
      <?php else :?>
        <div class=""></div>
      <?php endif?>
    </div>

    <div class="flex-column w50 p15">
      <p class="bold500 w100 text-l capitalize-p cut-phrase" cant-col="3"><?= $content;?></p>
      <p>$<?= $price?></p>
    </div>
  </a>

  <div class="flex-column center-end pr15 wrap">

    <div class="theme-button-menu p3 br100 flex-column center-center z-index-10 modal-btn animated darken">
      <?= svg("ellipsis-vertical", "x14");?>
    </div>

    <div class="hidden">
      <div class="flex-column center-center w100">
        <div class="wpx520 w-sml-100 back-modal-item br-desk-15 br-mid-15 br-sml-0 p20 text-menu-modal text-protected h-dvh-sml overflow-y-scroll">
          <?php _part("User.modalProductShare", 
          [
            "data" => 
            [
              "type" => $type,
              "price" => $price,
              "offer" => $offer,
              "porcentage" => $porcentage,
              "img" => $img,
              "imgSrc" => $imgSrc,
              "title" => $content,
              "content" => $content,
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

