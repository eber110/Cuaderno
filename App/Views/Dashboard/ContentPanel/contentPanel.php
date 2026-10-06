<?php
  /**
   * @var mixed $card
   * @var mixed $stats
   * @var mixed $user
   * @var mixed $uri
   * @var mixed $session
   */
?>
<div class="remote-container animated p40 p-sml-20 pb-sml-100">

  <div id="header-remote" class="remote-content flex-row top-center active">
    <div class="wpx630 w-mid-100 w-sml-100">
      <?php 
        _form("Panel.headerPanel", ["card" => $card, "uri" => $uri]);
      ?>
    </div>
  </div>
  <!-- button-remote -->
  <div id="background-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100">
      <?php
        _form("Panel.backgroundPanel", ["card" => $card, "uri" => $uri]);
      ?>
    </div>
  </div>

  <div id="button-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100">
      <?php
        _form("Panel.buttonPanel", ["card" => $card, "uri" => $uri]);
      ?>
    </div>
  </div>

  <div id="color-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100">
      <?php
        _form("Panel.colorPanel", ["card" => $card, "uri" => $uri]);
      ?>
    </div>
  </div>

  <div id="hide-profile-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100">
      <?php $isProfileHidden = !empty($card["hide"]) && ($card["hide"] === true || $card["hide"] === 'true' || $card["hide"] === 1 || $card["hide"] === '1'); ?>
      <div class="mb20 p20 br15 back-card-graphic shadow-card-graphic">
        <p class="texto">Tu perfil esta <span class="bold600" id="profile-visibility-status-text"><?= $isProfileHidden ? "oculto" : "visible";?></span> para todo publico.</p>
      </div>
      <div class="flex-row center-between">
        <div class="texto">Ocultar perfil</div>
        <?php _form("Panel.hideProfile", ["card" => $card, "uri" => $uri]);?>
      </div>
    </div>
  </div>

  <div id="Content-button" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100">
      <?php
        _form("Panel.contentButtonPanel", ["card" => $card, "uri" => $uri]);
      ?>
    </div>
  </div>

  <div id="Content-rrss" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100">
      <?php
        _form("Panel.contentRRSSPanel", ["card" => $card, "uri" => $uri]);
      ?>
    </div>
  </div>

  <div id="statistics-remote" class="remote-content flex-row top-center hidden" data-loaded="<?= !empty($stats) ? 'true' : 'false'; ?>">
    <div class="wpx890 w-mid-100 w-sml-100" id="statistics-remote-wrapper">
      <?php if (!empty($stats)): ?>
        <?php
          _part("Dashboard.statisticsPanel", [
            "stats" => $stats, 
            "card"  => $card ?? [],
            "user"  => $user ?? $card["profile"] ?? "",
            "uri"   => $uri ?? []
          ]);
        ?>
      <?php else: ?>
        <div id="stats-loading-placeholder" class="flex-column center p40 gap15 text-center">
          <div class="bold600 texto-sml color-secundary">Cargando estadísticas...</div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div id="content-remote-4" class="remote-content hidden">
    <div class="post-content">
      <script id="initial-design-state" type="application/json">
        <?= json_encode($card, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
      </script>
    </div>
  </div>

  <div id="content-remote-5" class="remote-content hidden">
    <div class="post-content">
      <script id="initial-session-state" type="application/json">
        <?= json_encode($session, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
      </script>
    </div>
  </div>

</div>

<!-- Script Anti-FOUT / Anti-Parpadeo Síncrono: Restaura el estado guardado del menú y panel antes del primer pintado con expiración de 1 hora -->
<script>
  (function() {
    try {
      var baseKey = 'vertical_menu_active_' + window.location.pathname;
      var saved = localStorage.getItem(baseKey) || localStorage.getItem(baseKey + '_default');
      var now = Date.now();
      var ONE_HOUR = 60 * 60 * 1000; // 1 hora
      var targetId = 'header-remote'; // Panel inicial por defecto: Diseño -> Cabecera
      var hasValidMemory = false;

      if (saved) {
        var parsed = JSON.parse(saved);
        if (parsed && parsed.remote && typeof parsed.timestamp === 'number' && (now - parsed.timestamp < ONE_HOUR)) {
          if (document.getElementById(parsed.remote)) {
            targetId = parsed.remote;
            hasValidMemory = true;
            // Refrescar timestamp ante actividad/navegación dentro de la hora
            var freshData = JSON.stringify({ remote: targetId, timestamp: now });
            localStorage.setItem(baseKey, freshData);
            localStorage.setItem(baseKey + '_default', freshData);
          }
        }
      }

      if (!hasValidMemory) {
        // Expirado (> 1 hora) o sin timestamp: limpiar memoria
        localStorage.removeItem(baseKey);
        localStorage.removeItem(baseKey + '_default');
      }

      var container = document.querySelector('.remote-container');
      var menu = document.querySelector('.vertical-menu');

      // 1. Activar de inmediato el panel de contenido remoto correspondiente (Cabecera por defecto)
      if (container && document.getElementById(targetId)) {
        var contents = container.querySelectorAll('.remote-content');
        contents.forEach(function(c) {
          if (c.id === targetId) {
            c.classList.remove('hidden');
            c.classList.add('active');
          } else {
            c.classList.remove('active');
            c.classList.add('hidden');
          }
        });
      }

      // 2. Activar de inmediato el enlace del menú y expandir su acordeón si aplica
      if (menu) {
        var activeClass = menu.getAttribute('active-item') || 'active';
        var links = menu.querySelectorAll('.vertical-menu-link');
        var targetLink = menu.querySelector('.vertical-menu-link[data-remote="' + targetId + '"]');

        if (targetLink) {
          links.forEach(function(l) {
            l.classList.remove(activeClass, 'active');
          });
          targetLink.classList.add(activeClass);

          var parentItem = targetLink.closest('.vertical-menu-item');
          if (parentItem) {
            parentItem.classList.add('open');
            var parentContent = parentItem.querySelector('.vertical-menu-content');
            if (parentContent) {
              parentContent.classList.remove('hidden');
              parentContent.style.height = 'auto';
            }
            var parentHeader = parentItem.querySelector('.vertical-menu-header');
            if (parentHeader) {
              var pClass = menu.getAttribute('active-principal') || 'active';
              parentHeader.classList.add(pClass);
              if (parentHeader.firstElementChild) {
                parentHeader.firstElementChild.classList.add(pClass);
              }
            }
          }
        }
      }
    } catch(e) {}
  })();
</script>