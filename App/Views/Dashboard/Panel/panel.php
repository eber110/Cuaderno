<?php
  /** 
   * @var mixed $user
   * @var mixed $session
   */
  $item = "p5 pl10 pr10 br10 back-item-sidebar-hover textb w100 flex-row center-start gap5 pointer";
?>

<div class="container-xl h-dvh back-body">
  
  <!-- <div class="post-content x16 p20">
    <code data-lang="php"><?php print_r($user)?></code>
  </div> -->

  <div class="flex-row top-start">

    <div class="flex-column top-start gap5 h-dvh back-menu-sidebar wpx250 p15 bold500 x17">
      <a href="/<?= $user["profile"]?>" class="<?= $item?>"><?= svg("arrow-l-l")?> Ver mi perfil</a>
      <p class="<?= $item?>"><?= svg("palette")?>Diseño</p>
    </div>

    <div class="h-dvh ">

    </div>
  </div>
  
</div>