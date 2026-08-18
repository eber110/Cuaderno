<?php
  /** 
   * @var mixed $card 
   * @var mixed $session
   */
  $item = "p5 pl10 pr10 br10 back-item-sidebar-hover texto w100 flex-row center-start gap5 pointer";
  $username = $session["username"] ?? ($card["profile"] ?? "");
  $isActive = !empty($card["active"]) && ($card["active"] === true || $card["active"] === "true" || $card["active"] === 1 || $card["active"] === "1");
  $isHide   = !empty($card["hide"]) && ($card["hide"] === true || $card["hide"] === "true" || $card["hide"] === 1 || $card["hide"] === "1");
?>
<?php if ($isActive) : ?>
  <?php if ($isHide) : ?>
    <p class="p5 pl10 pr10 br10 back-item-sidebar-hover w100 flex-row center-start gap5 back-danger textc tooltip animated bottom" 
      data-tooltip="Puedes ocultar o mostrar tu perfil desde el menú «Visibilidad»."
      style-tooltip="back5 textc shadow x18">
        <?= svg("triangle-exclamation-fill","x16 mr5");?> Perfil oculto
        <span class="flex-row center-center pointer"><?= svg("question");?></span>
    </p>
  <?php else : ?>
    <a href="/<?= e($username) ?>" class="<?= $item ?>"><?= svg("arrow-l-l") ?> Ver mi perfil</a>
  <?php endif; ?>
<?php else : ?>
  <p class="p5 pl10 pr10 br10 back-item-sidebar-hover w100 flex-row center-start gap5 back-danger textc tooltip animated bottom" 
    data-tooltip="Completa los datos requeridos en tu perfil para activarlo."
    style-tooltip="back5 textc shadow x18">
      <?= svg("triangle-exclamation-fill","x16 mr5");?> Activa tu perfil
      <span class="flex-row center-center pointer"><?= svg("question");?></span>
  </p>
<?php endif; ?>
