<?php
  /** 
   * @var mixed $card
   * @var mixed $session
   */
?>

<div class="container-xl h-dvh back-body overflow-y-scroll text-protected">
  
  <meta name="last-updated-at" content="<?= (int)($card['last_updated_at'] ?? 0) ?>">
  <script>
    window.INITIAL_PROFILE_JSON = <?= json_encode($card, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    window.AVAILABLE_PROFILE_FONTS = <?= json_encode(\App\Models\DesignModels::getAvailableFonts(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
  </script>

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
    <div class="no-tablet no-phone flex-column center-center h-dvh sticky top panel-preview-aside">

      <button class="absolute z-index-10 top mt20 bold500 texto pointer copy-btn panel-copy-pill back-card-graphic shadow-card-graphic hover-scale-soft" data-copy="<?= DOMAIN.$card["profile"]?>">
        <?= svg("link", "x16") ?>
        <span>clikhub/<?= e($card["profile"]) ?></span>
      </button>

      <?php _component("UserPreview.userPreview", ["data" => $card])?>

    </div>

  </div>

</div>