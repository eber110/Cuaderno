<?php
  /** 
   * @var mixed $card 
   * @var mixed $uri
   */
  $cant = count($card["rrss"] ?? []);

  $rrssList = [
    'facebook', 'whatsapp', 'instagram', 'youtube', 'tiktok', 'wechat',
    'telegram', 'snapchat', 'reddit', 'douyin', 'kuaishou', 'x',
    'weibo', 'pinterest', 'threads', 'quora', 'linkedin', 'discord',
    'twitch', 'viber', 'xiaohongshu', 'line', 'tumblr', 'medium',
    'vk', 'github', 'spotify', 'skype', 'patreon', 'substack',
    'bluesky', 'mastodon', 'soundcloud', 'flipboard', 'pocket', 'hackernews',
    'mix'
  ];
?>
<form class="auto-submit w100" action="<?= $uri["formDesign"]?>" method="post">
  <?= class_exists('\Base\Module\SecurityModule') ? \Base\Module\SecurityModule::csrfField() : '' ?>

  <div class="flex-column top-center gap20">

    <!-- Botón Iniciador con Menú Modal para Añadir Red Social -->
    <div class="flex-column top-start gap12 w100">
      <p class="bold500 texto">Añadir nueva red social</p>
      
      <div class="relative">
        <button type="button" class="open-modal-menu p10 pl15 pr15 br20 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-center gap5 bold500 texto" style="border: none;">
          <?= svg("add") ?> Red social
        </button>

        <div class="content-modal-menu hidden back-body br15 p15 shadow-1 back-card-graphic shadow-card-graphic |hover-scale-soft z-index-900" style="max-height: 300px; overflow-y: auto; width: 220px;">
          <div class="flex-column gap10">
            <?php foreach ($rrssList as $social) : ?>
              <button type="button" data-action="add-rrss-template" data-rrss="<?= e($social) ?>" class="p10 br10 back-card-graphic shadow-card-graphic hover-scale-soft pointer flex-row center-start gap10 bold500 texto w100" style="border: none;">
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
        <div id="rrss-item-<?= $i?>" data-rrss-name="<?= e($socialName) ?>" class="sortable-item link back-card-graphic shadow-card-graphic |hover-scale-soft flex-column gap10 w100 p20 br15" draggable="false">
          <div class="flex-row center-between gap10 w100">
            <div class="flex-row center-start gap10 pointer drag-handle flex-1" style="min-width: 0; overflow: hidden;">
              <span class="drag-icon text-muted pointer flex-shrink-0" title="Arrastrar para reordenar" style="cursor: grab; font-size: 18px; user-select: none;">&#x22EE;&#x22EE;</span>
              <div class="flex-row center-start gap8 bold500 item-title-label capitalize texto" style="min-width: 0;">
                <?= svg($socialName, "x20") ?>
                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e(ucfirst($socialName)) ?></span>
              </div>
            </div>
            <div class="flex-row center-center gap10 no-drag-actions flex-shrink-0">
              <!-- Opción para eliminar red social -->
              <div class="modal-btn tooltip left animated flex-shrink-0" data-tooltip="Eliminar enlace">
                <p class="pointer flex-row center-center gap5 nowrap texto block-delete-trigger">
                  <span class="no-phone">Eliminar</span><span class="flex-row center-center br50 back-danger back-danger-hover textc p2 x16 flex-shrink-0"><?= svg("xmark")?></span>
                </p>
              </div>

              <!-- modal confirmación eliminar -->
              <div class="hidden">
                <div class="w100 flex-column center-center h-dvh">
                  <div class="flex-column gap20 wpx520 w-sml-100 back-card-graphic p20 br15">
                    <p class="bold500 texto">¿Desea borrar este enlace?</p>

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
          <div class="back-card-graphic panel-input-box w100">
            <?= uiInput('textInput', [
              'triggerText' => 'texto',
              'fontSize' => 18,
              'placeholderSize' => 18,
              'secondaryFontSize' => 14,
              'value' => $socialUrl,
              'autoSubmit' => true,
              'name' => "rrss[{$i}][1]",
              'placeholder' => 'URL (ej: https://...)',
              'triggerBg' => 'textw',
              'secondaryText' => 'text-inactive',
              'placeholderColor' => 'text-inactive',
              'borderWidth' => 0,
            ]) ?>
          </div>
        </div>
      <?php endfor; ?>
    </div>

    <input type="submit" value="Guardar" class="hidden">
  </div>

</form>

<!-- Plantillas Local-First para Redes Sociales -->
<template id="tpl_rrss_item">
  <div id="rrss-item-{{INDEX}}" data-rrss-name="{{RRSS_NAME}}" data-index="{{INDEX}}" class="sortable-item link back-card-graphic shadow-card-graphic |hover-scale-soft flex-column gap10 w100 p20 br15" draggable="false">
    <div class="flex-row center-between gap10 w100">
      <div class="flex-row center-start gap10 pointer drag-handle flex-1" style="min-width: 0; overflow: hidden;">
        <span class="drag-icon text-muted pointer flex-shrink-0" title="Arrastrar para reordenar" style="cursor: grab; font-size: 18px; user-select: none;">&#x22EE;&#x22EE;</span>
        <div class="flex-row center-start gap8 bold500 item-title-label capitalize texto" style="min-width: 0;">
          <span class="rrss-icon-slot flex-row center-center x20"></span>
          <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{RRSS_LABEL}}</span>
        </div>
      </div>
      <div class="flex-row center-center gap10 no-drag-actions flex-shrink-0">
        <div class="modal-btn tooltip left animated flex-shrink-0" data-tooltip="Eliminar enlace">
          <p class="pointer flex-row center-center gap5 nowrap texto block-delete-trigger">
            <span class="no-phone">Eliminar</span><span class="flex-row center-center br50 back-danger back-danger-hover textc p2 x16 flex-shrink-0"><?= svg("xmark")?></span>
          </p>
        </div>

        <div class="hidden">
          <div class="w100 flex-column center-center h-dvh">
            <div class="flex-column gap20 wpx520 w-sml-100 back-card-graphic p20 br15">
              <p class="bold500 texto">¿Desea borrar este enlace?</p>

              <div class="flex-row center-between gap10">
                <label for="delete-rrss-{{INDEX}}" class="btn-card-graphic shadow-card-graphic hover-scale-soft text-c texto w100 bold500 pointer">
                  Eliminar
                </label>
                <p class="btn-card-graphic-red shadow-card-graphic hover-scale-soft text-c textc w100 pointer bold500 modal-close-button">Cancelar</p>
              </div>
            </div>
          </div>
        </div>

        <input id="delete-rrss-{{INDEX}}" type="checkbox" name="rrss[{{INDEX}}][delete]" value="true" class="hidden">
      </div>
    </div>

    <input type="hidden" name="rrss[{{INDEX}}][0]" value="{{RRSS_NAME}}">
    <div class="back-card-graphic panel-input-box w100">
      <?= uiInput('textInput', [
        'triggerText' => 'texto',
        'fontSize' => 18,
        'placeholderSize' => 18,
        'secondaryFontSize' => 14,
        'value' => '',
        'autoSubmit' => true,
        'name' => 'rrss[{{INDEX}}][1]',
        'placeholder' => 'URL (ej: https://...)',
        'triggerBg' => 'textw',
        'secondaryText' => 'text-inactive',
        'placeholderColor' => 'text-inactive',
        'borderWidth' => 0,
      ]) ?>
    </div>
  </div>
</template>

<template id="tpl_preview_rrss">
  <a href="#" target="_blank" rel="noopener noreferrer" aria-label="{{RRSS_NAME}}" class="color-text-card hover-lift-ns track-link-click rrss-preview-icon-slot" data-user="<?= e($card['profile'] ?? '') ?>" data-link-id="rrss_{{RRSS_NAME}}" style="display: none;"></a>
</template>
