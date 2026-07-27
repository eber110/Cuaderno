<?php
  /** 
   * @var mixed $share
  */
?>
<div class="">

  <?php if ($connect ?? false == true) :?>
    <div class="flex-row center-between">
      <a href="/panel/<?= $username ?? "Usuario";?>" class="color-text-card back-item-menu pl15 pr15" aria-label="Configuración">Configuración <?= svg("gear");?></a>

      <div class="flex-row center-end gap5">
        <p class="color-text-card back-item-menu flex-row center-center ar-square modal-btn animated"><?= svg("share-from");?></p>
        <div class="hidden">
          <div class="flex-column center-center w100 wrap">
            <div class="wpx520 w-sml-100 back-modal-item br-desk-15 br-mid-15 br-sml-0 p20 text-menu-modal text-protected h-dvh-sml">
              <?php _part("User.modalUserShare", ["data" => $userShareData ?? [], "card" => $card ?? []])?>
            </div>
          </div>
        </div>
        
        <a href="/salir" class="color-text-card back-item-menu pl15 pr15" aria-label="Salir">Salir <?= svg("out");?></a>
      </div>
    </div>
  <?php else :?>
    <?php if (!\Base\Module\Session::session_active()) :?>
      <div class="flex-row center-between">
        <a href="/ingresar" class="color-text-card back-item-menu flex-row center-center ar-square" aria-label="Ingresar"><?= svg("user");?></a>
        <!-- Integrar el modal para registrarse e ingresar al perfil -->
        <p class="color-text-card back-item-menu flex-row center-center ar-square modal-btn animated"><?= svg("share-from");?></p>
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
        <a href="/" class="color-text-card back-item-menu pl15 pr15" aria-label="Volver"><?= svg("arrow-l-l");?> Volver</a>

        <p class="color-text-card back-item-menu flex-row center-center ar-square modal-btn animated"><?= svg("share-from");?></p>
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

</div>