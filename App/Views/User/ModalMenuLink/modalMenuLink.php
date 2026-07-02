<?php
  /** @var mixed $data */
?>
<div class="flex-column center-center gap15 back-modal-item">
  <p class="absolute top right pointer modal-close-button"><?= svg("xmark")?></p>

  <p>Comparte estos links</p>

  <div class="flex-column center-center gap10 wpx260 p30 br20 border-card-modal hover-scale-soft" style="background-color: #ebebeb;">
    <figure class="ar-square wpx200 br15">
      <img src="<?= DIR_SHOW_MEDIA.$data["img"]?>" alt="" class="cover">
    </figure>

    <p class="x16 bold500"><?= $data["content"]?></p>
    <a href="<?= $data["profile"]?>" class="x16 texto"><?= $data["profile"]?></a>
  </div>

  <div class="hem5 back7 border-card-modal w100 br15">
    
  </div>

  <div class="hem13 back7 border-card-modal w100 br15">

  </div>

</div>