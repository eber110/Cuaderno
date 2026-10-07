<?php
  /** @var mixed $card */
  $styleBack = $card["backCard"]["style_back"] ?? "solid";
  $backVideo = $card["backCard"]["back_video"] ?? "";
?>
<div class="wpx460 w-sml-100 h-dvh pt40 pb40 p-sml-0 flex-column center-center user-profile-preview">
  <?php _part("User.style.css", ["card" => $card]); ?>
  <div class="flex-column between-center back-card shadow-card-preview color-text-card preview-profile p0 br30 br-sml-0 w100 overflow-hidden position-relative">
    <?php if (\App\Models\DesignModels::isVideoEnabled() && !empty($backVideo)) : ?>
      <video class="back-video-bg" preload="metadata" autoplay loop muted playsinline disablePictureInPicture tabindex="-1" style="<?= ($styleBack === 'video') ? '' : 'display: none;' ?>" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='none';">
        <source src="<?= eUrl($backVideo) ?>" type="video/mp4" onerror="var v = this.parentElement; if(v){ v.style.display='none'; if(v.nextElementSibling) v.nextElementSibling.style.display='none'; }">
      </video>
      <div class="back-video-overlay" style="<?= ($styleBack === 'video') ? '' : 'display: none;' ?>"></div>
    <?php endif; ?>

    <?php 
      $backImage = $card["backCard"]["backImageSrc"] ?? $card["backCard"]["back_image"] ?? ""; 
      if (empty($backImage)) {
        $backImage = URL_IMG . "no-image.webp";
      }
      $backImageFilter = $card["backCard"]["back_image_filter"] ?? "none";
      $numMap = ['0'=>'none', '1'=>'vignette', '2'=>'blur', '3'=>'brightness', '4'=>'contrast', '5'=>'grayscale', '6'=>'hue-rotate', '7'=>'invert', '8'=>'saturate', '9'=>'sepia'];
      if (isset($numMap[$backImageFilter])) $backImageFilter = $numMap[$backImageFilter];
    ?>
    <img src="<?= eUrl($backImage) ?>" class="back-image-bg" style="<?= ($styleBack === 'image') ? '' : 'display: none;' ?>" alt="Background" onerror="this.src='<?= eUrl(URL_IMG . 'no-image.webp') ?>';">
    <div class="back-image-vignette" style="<?= ($styleBack === 'image' && $backImageFilter === 'vignette') ? '' : 'display: none;' ?>"></div>
    <div class="back-image-overlay" style="<?= ($styleBack === 'image') ? '' : 'display: none;' ?>"></div>


    <?php// _component("Menu.menuUser"); ?>

    <div class="w100 h100 flex-column between-center overflow-y-scroll z-index-1" data-scroll-memory="user-preview" style="scroll-behavior: auto !important;">
      <header class="w100">
        <?php
          $currentHeader = $card["header"] ?? "regularHero";
        ?>
        <div class="header-variant-wrapper header-regularHero <?= $currentHeader === 'regularHero' ? '' : 'hidden' ?>" data-header-variant="regularHero">
          <?php _part("User.regularHero", ["card" => $card]); ?>
        </div>
        <div class="header-variant-wrapper header-voidHero <?= $currentHeader === 'voidHero' ? '' : 'hidden' ?>" data-header-variant="voidHero">
          <?php _part("User.voidHero", ["card" => $card]); ?>
        </div>
        <div class="header-variant-wrapper header-midHero <?= $currentHeader === 'midHero' ? '' : 'hidden' ?>" data-header-variant="midHero">
          <?php _part("User.midHero", ["card" => $card]); ?>
        </div>
        <?php
          _part("User.widget", ["card" => $card, "isPreview" => true]);
        ?>
      </header>

      <footer class="w100">
        <?php
          _template("Footer.footerUser")
        ?>
      </footer>

      <!-- Script Anti-FOUC síncrono: Restaura el orden de bloques, redes sociales, estilos y scroll del preview de forma instantánea antes del primer renderizado visual -->
      <script>
        (function() {
          try {
            // 1. Restauración instantánea de scroll sin salto visual (Anti-FOUC de scroll)
            var p = document.currentScript ? document.currentScript.parentElement : null;
            if (p) {
              p.style.setProperty('scroll-behavior', 'auto', 'important');
              var path = window.location.pathname;
              var s = sessionStorage.getItem('fme_scroll_user-preview_' + path) ||
                      sessionStorage.getItem('cuaderno_scroll_user-preview_' + path);
              if (s) {
                var targetTop = parseInt(s, 10);
                if (!isNaN(targetTop) && targetTop > 0) {
                  p.scrollTop = targetTop;

                  if (Math.abs(p.scrollTop - targetTop) > 2) {
                    var prevVis = p.style.visibility;
                    p.style.visibility = 'hidden';

                    var attempts = 0;
                    var stabilizeScroll = function() {
                      attempts++;
                      p.scrollTop = targetTop;
                      if (Math.abs(p.scrollTop - targetTop) <= 2 || attempts >= 8) {
                        p.style.visibility = prevVis;
                      } else {
                        requestAnimationFrame(stabilizeScroll);
                      }
                    };
                    requestAnimationFrame(stabilizeScroll);
                  }
                }
              }
            }

            // 2. Obtener borrador de diseño local (Local-First)
            var user = <?= json_encode($card['profile'] ?? '') ?>;
            if (!user) {
              var parts = window.location.pathname.split('/').filter(Boolean);
              if (parts[0] === 'panel' && parts[1]) user = parts[1];
            }
            if (!user) return;

            var draftKey = 'cuaderno_design_draft_' + user.toLowerCase();
            var draftStr = localStorage.getItem(draftKey);
            if (!draftStr) return;
            var draft = JSON.parse(draftStr);
            if (!draft || typeof draft !== 'object') return;

            // 3. Restaurar orden de Bloques de Contenido en 0ms
            if (Array.isArray(draft.content) && draft.content.length > 0) {
              var pContainers = document.querySelectorAll('.preview-widget-container, #preview-widget-container');
              pContainers.forEach(function(pContainer) {
                var targetNodes = [];
                for (var i = 0; i < draft.content.length; i++) {
                  var block = draft.content[i];
                  if (!block) continue;
                  var prevEls = block.id ? Array.from(pContainer.querySelectorAll('[data-block-id="' + block.id + '"]')) : [];
                  if (!prevEls || prevEls.length === 0) {
                    prevEls = Array.from(pContainer.querySelectorAll('[data-content-index="' + i + '"]'));
                  }
                  for (var j = 0; j < prevEls.length; j++) {
                    prevEls[j].setAttribute('data-content-index', String(i));
                    targetNodes.push(prevEls[j]);
                  }
                }
                for (var idx = 0; idx < targetNodes.length; idx++) {
                  var node = targetNodes[idx];
                  if (pContainer.children[idx] !== node) {
                    pContainer.insertBefore(node, pContainer.children[idx] || null);
                  }
                }
              });

              // Sincronizar también la lista sortable del editor si ya existe en el DOM
              var editorList = document.getElementById('sortable-content-list');
              if (editorList) {
                var editorItems = [];
                for (var bIdx = 0; bIdx < draft.content.length; bIdx++) {
                  var blk = draft.content[bIdx];
                  if (!blk) continue;
                  var edItem = blk.id ? editorList.querySelector('.sortable-item[data-block-id="' + blk.id + '"]') : null;
                  if (edItem) editorItems.push(edItem);
                }
                for (var eIdx = 0; eIdx < editorItems.length; eIdx++) {
                  var eNode = editorItems[eIdx];
                  if (editorList.children[eIdx] !== eNode) {
                    editorList.insertBefore(eNode, editorList.children[eIdx] || null);
                  }
                }
              }
            }

            // 4. Restaurar orden de Redes Sociales en 0ms en todas las variantes de cabecera
            if (Array.isArray(draft.rrss) && draft.rrss.length > 0) {
              var preview = document.querySelector('.user-profile-preview');
              if (preview) {
                var allRrssLinks = Array.from(preview.querySelectorAll('[data-link-id^="rrss_"]'));
                var wrappers = [];
                allRrssLinks.forEach(function(l) {
                  if (l.parentElement && wrappers.indexOf(l.parentElement) === -1) {
                    wrappers.push(l.parentElement);
                  }
                });

                wrappers.forEach(function(rrssWrapper) {
                  var previewLinks = Array.from(rrssWrapper.querySelectorAll('[data-link-id^="rrss_"]'));
                  var targetRrssNodes = [];
                  for (var r = 0; r < draft.rrss.length; r++) {
                    var rItem = draft.rrss[r];
                    var name = (Array.isArray(rItem) ? rItem[0] : (rItem && rItem.name ? rItem.name : '')).trim().toLowerCase();
                    if (!name) continue;
                    for (var l = 0; l < previewLinks.length; l++) {
                      var link = previewLinks[l];
                      var linkId = (link.getAttribute('data-link-id') || '').toLowerCase();
                      if (linkId === 'rrss_' + name) {
                        targetRrssNodes.push(link);
                        break;
                      }
                    }
                  }
                  for (var t = 0; t < targetRrssNodes.length; t++) {
                    var rNode = targetRrssNodes[t];
                    if (rrssWrapper.children[t] !== rNode) {
                      rrssWrapper.insertBefore(rNode, rrssWrapper.children[t] || null);
                    }
                  }
                });
              }

              // Sincronizar también la lista sortable del editor si ya existe en el DOM
              var rrssList = document.getElementById('sortable-rrss-list');
              if (rrssList) {
                var rrssItems = [];
                for (var rsIdx = 0; rsIdx < draft.rrss.length; rsIdx++) {
                  var rSocial = draft.rrss[rsIdx];
                  var rSocialName = (Array.isArray(rSocial) ? rSocial[0] : (rSocial && rSocial.name ? rSocial.name : '')).trim().toLowerCase();
                  if (!rSocialName) continue;
                  var allItems = Array.from(rrssList.querySelectorAll('.sortable-item'));
                  var matched = allItems.find(function(it) {
                    var itName = (it.getAttribute('data-rrss-name') || '').trim().toLowerCase();
                    return itName === rSocialName;
                  });
                  if (matched) rrssItems.push(matched);
                }
                for (var rNodeIdx = 0; rNodeIdx < rrssItems.length; rNodeIdx++) {
                  var rItemNode = rrssItems[rNodeIdx];
                  if (rrssList.children[rNodeIdx] !== rItemNode) {
                    rrssList.insertBefore(rItemNode, rrssList.children[rNodeIdx] || null);
                  }
                }
              }
            }

            // 5. Inyectar estilos visuales inmediatos para prevenir FOUC de colores/fondos
            var styleSheet = document.getElementById('design-draft-live-styles');
            if (!styleSheet) {
              styleSheet = document.createElement('style');
              styleSheet.id = 'design-draft-live-styles';
              document.head.appendChild(styleSheet);
            }

            var cssRules = [];
            var backPerfil = (draft.backCard && draft.backCard.back_perfil) || draft.back_perfil;
            var styleBack = (draft.backCard && draft.backCard.style_back) || draft.style_back || 'solid';

            if (backPerfil) {
              var clean = String(backPerfil).trim();
              if (!clean.startsWith('#')) clean = '#' + clean;
              if (styleBack === 'gradientUp') {
                var gStart = 'oklch(from ' + clean + ' calc(l * 1.12) calc(c * 0.90) h)';
                var gEnd = 'oklch(from ' + clean + ' calc(l * 0.82) calc(c * 1.10) h)';
                cssRules.push('.user-profile-preview .back-card { background: linear-gradient(0deg, ' + gStart + ', ' + gEnd + ') !important; }');
              } else if (styleBack === 'gradientDown') {
                var gdStart = 'oklch(from ' + clean + ' calc(l * 1.12) calc(c * 0.90) h)';
                var gdEnd = 'oklch(from ' + clean + ' calc(l * 0.82) calc(c * 1.10) h)';
                cssRules.push('.user-profile-preview .back-card { background: linear-gradient(180deg, ' + gdStart + ', ' + gdEnd + ') !important; }');
              } else if (styleBack === 'solid') {
                cssRules.push('.user-profile-preview .back-card { background: ' + clean + ' !important; background-color: ' + clean + ' !important; }');
              }
            }

            if (draft.titleColor) {
              cssRules.push('.user-profile-preview .title-color { color: ' + draft.titleColor + ' !important; }');
            }
            if (draft.colorText) {
              cssRules.push('.user-profile-preview .color-text-card { color: ' + draft.colorText + ' !important; }');
            }
            if (draft.back) {
              cssRules.push('.user-profile-preview .theme-button { background-color: ' + draft.back + ' !important; }');
            }
            if (draft.color) {
              cssRules.push('.user-profile-preview .theme-button { color: ' + draft.color + ' !important; }');
              cssRules.push('.user-profile-preview .theme-icon { color: ' + draft.color + ' !important; }');
            }

            if (cssRules.length > 0) {
              styleSheet.textContent = cssRules.join('\n');
            }

            // 6. Restaurar variante de cabecera si difiere del servidor
            if (draft.header) {
              var currentHeader = draft.header;
              var variants = ['regularHero', 'voidHero', 'midHero'];
              variants.forEach(function(v) {
                var wrap = document.querySelector('.header-variant-wrapper.header-' + v);
                if (wrap) {
                  if (v === currentHeader) {
                    wrap.classList.remove('hidden');
                    wrap.style.display = '';
                  } else {
                    wrap.classList.add('hidden');
                    wrap.style.display = 'none';
                  }
                }
              });
            }
          } catch(e) {}
        })();
      </script>
    </div>

  </div>
</div>