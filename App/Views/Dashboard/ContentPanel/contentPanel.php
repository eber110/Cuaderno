<?php
  /**
   * @var mixed $card
   * @var mixed $session
   */
?>
<div class="remote-container animated p20">

  <div id="content-remote-1" class="remote-content active">
    <div class="back-card wpx500 pb20 br15">
      <?php _part("User.midHero")?>
    </div>
  </div>

  <div id="content-remote-2" class="remote-content hidden">
    <p>Aquí va el formulario para cambiar el fondo del perfil</p>
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