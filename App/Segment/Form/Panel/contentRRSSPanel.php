<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $cant = count($card["rrss"] ?? []);

  $rrssList = [
    'x', 'facebook', 'linkedin', 'reddit', 'tumblr', 'whatsapp', 'github',
    'pinterest', 'telegram', 'skype', 'email', 'threads', 'bluesky',
    'mastodon', 'vk', 'line', 'viber', 'pocket', 'flipboard',
    'hackernews', 'mix', 'snapchat'
  ];
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post">

  <div class="flex-column top-center gap20">

    <!-- Botón Iniciador con Menú Modal para Añadir Red Social -->
    <div class="flex-column top-start gap10 w100">
      <p class="bold500 x16 texto">Añadir nueva red social</p>
      
      <div class="relative">
        <button type="button" class="open-modal-menu p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 texto" style="border: none;">
          <?= svg("add") ?> Red social
        </button>

        <div class="content-modal-menu hidden back-body br15 p15 shadow-1 back-card-graphic shadow-card-graphic |hover-scale-soft z-index-900" style="max-height: 300px; overflow-y: auto; width: 220px;">
          <div class="flex-column gap10">
            <?php foreach ($rrssList as $social) : ?>
              <button type="submit" name="add_rrss_name" value="<?= $social ?>" class="p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-start gap10 bold500 texto w100" style="border: none;">
                <?= svg($social, "x18") ?>
                <span class="capitalize"><?= ucfirst($social) ?></span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de Redes Sociales existentes (Sortable Drag & Drop) -->
    <div id="sortable-rrss-list" class="flex-column gap20 w100">
      <?php for ($i = 0; $i < $cant; $i++) :
        $socialName = $card["rrss"][$i][0] ?? $card["rrss"][$i]["name"] ?? '';
        $socialUrl  = $card["rrss"][$i][1] ?? $card["rrss"][$i]["url"] ?? '';
        if ($socialUrl !== '' && !preg_match('#^https?://#i', $socialUrl) && strpos($socialUrl, 'mailto:') !== 0 && strpos($socialUrl, 'tel:') !== 0) {
          $socialUrl = "https://" . $socialUrl;
        }
      ?>
        <div id="rrss-item-<?= $i?>" class="sortable-item link back-card-graphic shadow-card-graphic |hover-scale-soft flex-column gap10 w100 p20 br15" draggable="true">
          <div class="flex-row center-between">
            <div class="flex-row center-start gap10 pointer drag-handle">
              <span class="drag-icon text-muted pointer" title="Arrastrar para reordenar" style="cursor: grab; font-size: 18px; user-select: none;">&#x22EE;&#x22EE;</span>
              <div class="flex-row center-start gap8 bold500 item-title-label capitalize texto">
                <?= svg($socialName, "x20") ?>
                <span><?= e(ucfirst($socialName)) ?></span>
              </div>
            </div>
            <div class="flex-row center-center gap10">
              <!-- Opción para eliminar red social -->
              <div class="modal-btn tooltip left animated" data-tooltip="Eliminar enlace">
                <p class="pointer flex-row center-center texto">
                  Eliminar <span class="flex-row center-center br50 back-danger back-danger-hover textc p2 x16 ml5"><?= svg("xmark")?></span>
                </p>
              </div>

              <!-- modal confirmación eliminar -->
              <div class="hidden">
                <div class="w100 flex-column center-center h-dvh">
                  <div class="flex-column gap20 wpx520 w-sml-100 back-card-graphic p20 br15">
                    <p class="x24 bold500 texto">¿Desea borrar este enlace?</p>

                    <div class="flex-row center-between gap10">
                      <label for="delete-rrss-<?= $i?>" class="btn-card-graphic shadow-card-graphic hover-scale-soft text-c texto w100 bold500 pointer">
                         Eliminar
                      </label>
                      <p class="btn-card-graphic-red shadow-card-graphic hover-scale-soft text-c textc w100 pointer bold500 modal-close-button">Cancelar</p>
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
          <input type="text" name="rrss[<?= $i?>][1]" class="back-card-graphic shadow-card-graphic hover-scale-soft br10 p10 texto" value="<?= e($socialUrl) ?>" placeholder="URL (ej: https://...)">
        </div>
      <?php endfor; ?>
    </div>

    <input type="submit" value="Guardar" class="hidden">
  </div>

</form>
