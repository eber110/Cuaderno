<?php

  $msg[] = "";//variable declarativa
  $svg = "";
  $svgConstruct = explode(".", $msg[5]);

  if (!isset($svgConstruct[1])) {

    $svgConstruct[1] = "color-caution";

  }

  if ($svgConstruct[0] == '') {

    $svg = svg('triangle-exclamation-fill', 'color-caution');

  }else{

    $svg = svg($svgConstruct[0], $svgConstruct[1]);

  }

?>

<div id="error-<?= $msg[1]?>" class="container-xl h-dvh flex column-direction center-center">
  <div class="card back8 br0 w100 flex column-direction center-center">

    <div class="w50 w-mid-90 w-sml-100 flex-between-column">

      <div class="text-center color2 x50 bold600 flex row-direction center-start gap10">
        <p class="x50 bold600 flex row-direction center-center"><?= $svg?></p>
        <p class="x50 bold600">Error</p>
        <p class="x50 bold600"><?= $msg[1]?>!</p>
      </div>
      <p class="mb10 m0 x25 color2 no-indent pt10 pb10"><?= $msg[2]?></p>

      <?php if ($msg[3] != '') : ?>
        <div class="btn-cancel x20 br50 bold400 mb5">
          <a href="<?= $msg[3]?>"><?= $msg[4]?></a>
        </div>
      <?php endif;?>

    </div>

  </div>
</div>