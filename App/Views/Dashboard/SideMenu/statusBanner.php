<?php
  /** 
   * @var mixed $card 
   * @var mixed $session
   */
  $item = "p8 pl12 pr12 br12 back-item-sidebar-hover texto w100 flex-row center-start gap8 pointer";
  $username = $session["username"] ?? ($card["profile"] ?? "");
  $isActive = !empty($card["active"]) && ($card["active"] === true || $card["active"] === "true" || $card["active"] === 1 || $card["active"] === "1");
  $isHide   = !empty($card["hide"]) && ($card["hide"] === true || $card["hide"] === "true" || $card["hide"] === 1 || $card["hide"] === "1");
?>
<?php if ($isActive) : ?>
  <p id="sidebar-status-hidden-badge" class="p8 pl12 pr12 br12 w100 flex-row center-between gap8 back-card-graphic-red textw tooltip animated bottom <?= $isHide ? '' : 'hidden' ?>" style="<?= $isHide ? '' : 'display: none !important;' ?>"
    data-tooltip="Puedes ocultar o mostrar tu perfil desde el menú «Visibilidad»."
    style-tooltip="back5 textc shadow x18">
      <span class="flex-row center-start gap8"><?= svg("triangle-exclamation-fill", "x16"); ?> Perfil oculto</span>
      <span class="flex-row center-center pointer"><?= svg("question"); ?></span>
  </p>
  <a id="sidebar-status-visible-link" href="/<?= e($username) ?>" class="<?= $item ?> <?= !$isHide ? '' : 'hidden' ?>" style="<?= !$isHide ? '' : 'display: none !important;' ?>"><?= svg("arrow-l-l") ?> Ver mi perfil</a>
<?php else : ?>
  <p class="p8 pl12 pr12 br12 w100 flex-row center-between gap8 back-card-graphic-red textw tooltip animated bottom" 
    data-tooltip="Completa los datos requeridos en tu perfil para activarlo."
    style-tooltip="back5 textc shadow x18">
      <span class="flex-row center-start gap8"><?= svg("triangle-exclamation-fill", "x16"); ?> Activa tu perfil</span>
      <span class="flex-row center-center pointer"><?= svg("question"); ?></span>
  </p>
<?php endif; ?>
