<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $cant = count($card["rrss"] ?? []);

  $rrssList = [
    'x', 'facebook', 'linkedin', 'reddit', 'tumblr', 'whatsapp',
    'pinterest', 'telegram', 'skype', 'email', 'threads', 'bluesky',
    'mastodon', 'vk', 'line', 'viber', 'pocket', 'flipboard',
    'hackernews', 'mix', 'snapchat'
  ];
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post">

  <div class="flex-column top-center gap20">

    <!-- Botón Iniciador con Menú Modal para Añadir Red Social -->
    <div class="flex-column top-start gap10 w100">
      <p class="bold500 x16">Añadir nueva red social</p>
      
      <div class="relative">
        <button type="button" class="open-modal-menu p10 pl15 pr15 br20 border-item-panel pointer flex-row center-center gap5 bold500 textb" style="background: transparent;">
          <?= svg("add") ?> Red social
        </button>

        <div class="content-modal-menu hidden back-body br15 p15 shadow-1 border-item-panel z-index-900" style="max-height: 300px; overflow-y: auto; width: 220px;">
          <div class="flex-column gap5">
            <?php foreach ($rrssList as $social) : ?>
              <button type="submit" name="add_rrss_name" value="<?= $social ?>" class="p10 br10 border-item-panel pointer flex-row center-start gap10 bold500 textb w100" style="background: transparent;">
                <?= svg($social, "x18") ?>
                <span class="capitalize"><?= ucfirst($social) ?></span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de Redes Sociales existentes -->
    <div class="flex-column gap20 w100">
      <?php for ($i = 0; $i < $cant; $i++) :
        $socialName = $card["rrss"][$i][0] ?? $card["rrss"][$i]["name"] ?? '';
        $socialUrl  = $card["rrss"][$i][1] ?? $card["rrss"][$i]["url"] ?? '';
      ?>
        <div id="rrss-item-<?= $i?>" class="sortable-item link border-item-panel flex-column gap10 w100 p20 br15">
          <div class="flex-row center-between">
            <div class="flex-row center-start gap10 pointer drag-handle">
              <span class="drag-icon text-muted pointer" style="cursor: grab; font-size: 18px; user-select: none;">&#x22EE;&#x22EE;</span>
              <div class="flex-row center-start gap8 bold500 item-title-label capitalize">
                <?= svg($socialName, "x20") ?>
                <span><?= e(ucfirst($socialName)) ?></span>
              </div>
            </div>
            <div class="flex-row center-center gap10">
              <!-- Opción para eliminar red social -->
              <div class="modal-btn"><p class="pointer text-caution x14 flex-row center-center gap5"><?= svg("xmark")?> Eliminar</p></div>

              <!-- modal confirmación eliminar -->
              <div class="hidden">
                <div class="v-dvh w100 flex-column center-center">
                  <div class="wpx580 w-sml-95 back-body br15 p20 flex-column center-center gap20">
                    <p class="x30">¿Desea borrar esta red social?</p>

                    <div class="flex-row center-around gap15 w100">
                      <label for="delete-rrss-<?= $i?>" class="pointer flex-row center-center gap2 textw back-danger br15 p15">
                         Eliminar
                      </label>
                      <div class="back7 p15 br15 pointer modal-close-button">
                        <p>Cancelar</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- input oculto para marcar borrado -->
              <input id="delete-rrss-<?= $i?>" type="checkbox" name="rrss[<?= $i?>][delete]" value="true" class="hidden">
            </div>
          </div>

          <!-- Input oculto con el nombre de la red social -->
          <input type="hidden" name="rrss[<?= $i?>][0]" value="<?= e($socialName) ?>">

          <!-- Input para la URL -->
          <input type="text" name="rrss[<?= $i?>][1]" class="border-item-panel br10 p10" value="<?= e($socialUrl) ?>" placeholder="URL (ej: https://...)">
        </div>
      <?php endfor; ?>
    </div>

    <input type="submit" value="Guardar" class="hidden">
  </div>

</form>
