<?php
  /** 
   * @var mixed $card 
   * @var mixed $dataContent
  */
  //var_dump($data);
  $img = $card["content"][$dataContent][1];
  $content = $card["content"][$dataContent][2];
  $profile = $card["profile"];
?>
<div class="flex-row center-between wrap hpx65 w100 theme-button pointer <?= $card["borders"][0]?> <?= $card["shadow"]?>">

  <a href="<?= $card["content"][$dataContent][3]?>" target="_blank" class="flex-row center-start h100" style="text-decoration: none; color: inherit; flex-grow: 1; width: calc(100% - 50px);">
    <figure class="ar-square p7 wpx65">
      <img src="<?= DIR_SHOW_MEDIA?><?= $img?>" alt="" class="cover <?= $card["borders"][1]?>">
    </figure>

    <p class="flex-row center-center bold500 text-c cut-phrase hpx65" cant-col="2" style="flex-grow: 1;">
      <?= $content;?>
    </p>
  </a>

  <div class="flex-column center-center wpx50 wrap">

    <div class="theme-button-menu p3 br100 flex-column center-center z-index-10 modal-btn animated darken">
      <?= svg("ellipsis-vertical", "x14");?>
    </div>

    <div class="hidden">
      <div class="flex-column center-center w100 wrap">
        <div class="wpx550 w-sml-95 back-modal-item br15 p20 text-menu-modal text-protected">
          <?php _part("User.modalMenuLink", ["data" => ["img" => $img, "content" => $content, "profile" => $profile]])?>
        </div>
      </div>
    </div>

  </div>
  
</div>

