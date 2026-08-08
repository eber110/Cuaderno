<?php
  /** 
   * @var mixed $card
   * @var mixed $session
   */
?>

<div class="container-xl h-dvh back-body overflow-y-scroll text-protected">

  <div class="flex-row top-start">

    <!-- Menu lateral -->
    <?php _part("Dashboard.sideMenu")?>

    <!-- contenedor de items del panel y el contenido remoto -->
    <div class="h-dvh panel-container overflow-y-scroll">

      <!-- Menu superior del panel de administración -->
      <?php _part("Dashboard.navPanel");?>

      <!-- Contenido del panel y el side menu enlazado remotamente -->
      <?php _part("Dashboard.contentPanel")?>

    </div>

    <!-- Vista previa para desktop -->
    <div class="no-tablet no-phone flex-column center-center h-dvh sticky top" style="min-width: 550px;border-left: solid 0.5px #f0f0f0;">

      <button class="absolute z-index-10 top mt20 p10 pl20 pr20 br50 bold500 texto pointer copy-btn back-card-graphic shadow-card-graphic hover-scale-soft" data-copy="<?= DOMAIN.$card["profile"]?>" style="border: none;">
        cuaderno/<?= $card["profile"]?>
      </button>

      <div class="flex-column center-center w100">
        <?php _component("UserPreview.userPreview", ["data" => $card])?>
      </div>

    </div>

  </div>
</div>