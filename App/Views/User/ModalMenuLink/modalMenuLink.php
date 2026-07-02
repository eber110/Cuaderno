<?php
  /** @var mixed $data */
?>
<div class="flex-column center-center gap10 back-modal-item wrap">

  <div class="flex-column center-center gap15 wpx260 p30 br20 border-card-modal hover-scale-soft" style="background-color: #ebebeb;">
hover-scale-soft    <figure class="ar-square wpx200 br15">
      <img src="<?= DIR_SHOW_MEDIA.$data["img"]?>" alt="" class="cover">
    </figure>

    <p class="x16 bold500"><?= $data["content"]?></p>
    <a href="<?= $data["profile"]?>" class="x16"><?= $data["profile"]?></a>
  </div>

</div>