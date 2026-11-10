<?php
  /** 
   * @var mixed $share
  */
?>
<nav class="menu-user-fixed p30 p-sml-20 pt30 pb30 pt-sml-20">

  <?php if ($connect ?? false == true) :?>
    <div class="flex-row center-between">
      <!-- <a href="/panel/<?= e($username ?? "Usuario");?>" class="flex-row center-center color-menu-user back-item-menu hpx36 wpx36" aria-label="Configuración"><?= svg("gear");?></a> -->
      <a href="/ingresar" class="flex-row center-center color-menu-user back-item-menu hpx36 wpx36" aria-label="Ingresar"><?= svg("logoSvg");?></a>

      <div class="flex-row center-end gap5">
        <p class="color-menu-user back-item-menu flex-row center-center hpx36 wpx36 modal-btn animated"><?= svg("share-from");?></p>
        <div class="hidden">
          <div class="flex-column center-center w100 wrap">
            <div class="wpx520 w-sml-100 back-modal-item br-desk-15 br-mid-15 br-sml-0 p20 text-menu-modal text-protected h-dvh-sml">
              <?php _part("User.modalUserShare", ["data" => $userShareData ?? [], "card" => $card ?? []])?>
            </div>
          </div>
        </div>
        
        <a href="/salir" class="flex-row center-center color-menu-user back-item-menu hpx36 wpx36" aria-label="Salir"><?= svg("out");?></a>
      </div>
    </div>
  <?php else :?>
    <?php if (!\Base\Module\Session::session_active()) :?>
      <div class="flex-row center-between">
        <p class="color-menu-user back-item-menu flex-row center-center hpx36 wpx36 modal-btn animated"><?= svg("logoSvg");?></p>
        <div class="hidden">
          <div class="flex-column center-center w100 wrap">
            <div class="wpx520 w-sml-100 back-modal-item br-desk-15 br-mid-15 br-sml-0 p20 text-menu-modal text-protected h-dvh-sml back10">
              <?php _part("User.modalRegister", ["data" => $userShareData ?? [], "card" => $card ?? []])?>
            </div>
          </div>
        </div>

        <!-- Integrar el modal para registrarse e ingresar al perfil -->
        <p class="color-menu-user back-item-menu flex-row center-center hpx36 wpx36 modal-btn animated"><?= svg("share-from");?></p>
        <div class="hidden">
          <div class="flex-column center-center w100 wrap">
            <div class="wpx520 w-sml-100 back-modal-item br-desk-15 br-mid-15 br-sml-0 p20 text-menu-modal text-protected h-dvh-sml">
              <?php _part("User.modalUserShare", ["data" => $userShareData ?? [], "card" => $card ?? []])?>
            </div>
          </div>
        </div>
      </div>
    <?php else :?>
      <div class="flex-row center-between">
        <a href="/" class="flex-row center-center color-menu-user back-item-menu hpx36 wpx36" aria-label="Volver"><?= svg("arrow-l-l");?></a>

        <p class="color-menu-user back-item-menu flex-row center-center hpx36 wpx36 modal-btn animated"><?= svg("share-from");?></p>
        <div class="hidden">
          <div class="flex-column center-center w100 wrap">
            <div class="wpx520 w-sml-100 back-modal-item br-desk-15 br-mid-15 br-sml-0 p20 text-menu-modal text-protected h-dvh-sml">
              <?php _part("User.modalUserShare", ["data" => $userShareData ?? [], "card" => $card ?? []])?>
            </div>
          </div>
        </div>
      </div>
    <?php endif?>
  <?php endif?>

</nav>