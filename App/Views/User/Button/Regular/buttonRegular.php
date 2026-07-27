<?php
  /** 
   * @var mixed $card 
   * @var mixed $dataContent
  */
  //var_dump($data);
  $img = $card["content"][$dataContent]["img"] ?? '';
  $imgSrc = $card["content"][$dataContent]["imgSrc"] ?? '';
  $content = $card["content"][$dataContent]["title"] ?? '';
  $profile = $card["profile"];
  $url = $card["content"][$dataContent]["url"];
  $metaTitle = $card["content"][$dataContent]["metaTitle"];
  $metaDesc = $card["content"][$dataContent]["metaDesc"];
  $metaImg = $card["content"][$dataContent]["metaImg"];
  $share = $card["content"][$dataContent]["share"] ?? [];
?>
<div class="flex-row center-between wrap hpx65 w100 theme-button pointer <?= $card["borders"][0]?> <?= $card["shadow"]?>">

  <a href="<?= $card["content"][$dataContent]["url"] ?? '#' ?>" target="_blank" class="flex-row center-start h100" style="text-decoration: none; color: inherit; flex-grow: 1; width: calc(100% - 65px);">
    <?php if ($card["content"][$dataContent]["imgDefault"]) :?>
      <figure class="ar-square p7 wpx65">
        <img src="<?= $imgSrc ?>" alt="" class="cover <?= $card["borders"][1]?>">
      </figure>
    <?php else :?>
      <div class="ar-square p7 wpx65"></div>
    <?php endif?>

    <p class="flex-row center-center bold500 text-c cut-phrase" cant-col="2" style="flex-grow: 1;">
      <?= $content;?>
    </p>
  </a>

  <div class="flex-column center-end pr15 wpx65 wrap">

    <div class="theme-button-menu p3 br100 flex-column center-center z-index-10 modal-btn animated darken">
      <?= svg("ellipsis-vertical", "x14");?>
    </div>

    <div class="hidden">
      <div class="flex-column center-center w100 wrap">
        <div class="wpx520 w-sml-100 back-modal-item br-desk-15 br-mid-15 br-sml-0 p20 text-menu-modal text-protected h-dvh-sml">
          <?php _part("User.modalMenuShare", 
          ["data" => 
            [
              "img" => $img,
              "imgSrc" => $imgSrc,
              "content" => $content,
              "profile" => $profile,
              "url" => $url,
              "share" => $share,
              "metaTitle" => $metaTitle,
              "metaDesc" => $metaDesc,
              "metaImg" => $metaImg,
            ]
          ])?>
        </div>
      </div>
    </div>

  </div>
  
</div>

