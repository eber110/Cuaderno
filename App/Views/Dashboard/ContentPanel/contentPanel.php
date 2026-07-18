<?php
  /**
   * @var mixed $card
   * @var mixed $session
   */
?>
<div class="remote-container animated p20">

  <div id="header-remote" class="remote-content flex-row top-center active">
    <div class="wpx630 w-mid-100 w-sml-100 p20">
      <?php 
        _form("Panel.headerPanel");
      ?>
    </div>
  </div>
<!-- button-remote -->
  <div id="background-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100 p20">
      <?php
        _form("Panel.backgroundPanel");
      ?>
    </div>
  </div>

  <div id="button-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100 p20">
      <?php
        _form("Panel.buttonPanel");
      ?>
    </div>
  </div>

  <div id="content-remote-3" class="remote-content hidden">
    <div class="modal-btn">
      <p>Abrir</p>
    </div>
    <div class="hidden">
      <div class="flex-column center-center w100 before-menu-overlay">
        <div class="absolute m20 top right pointer modal-close-button closed-modal-preview br50 p0 hpx30 wpx30 flex-column center-center">
          <?= svg("xmark")?>
        </div>
        <?php _component("UserPreview.userPreview", ["data" => $card])?>
      </div>
    </div>
  </div>

  <div id="content-remote-4" class="remote-content hidden">
    <div class="post-content">
      <code data-lang="json"><?php print_r(json_encode($card))?></code>
    </div>
  </div>

  <div id="content-remote-5" class="remote-content hidden">
    <div class="post-content">
      <code data-lang="json"><?php print_r(json_encode($session))?></code>
    </div>
  </div>

</div>