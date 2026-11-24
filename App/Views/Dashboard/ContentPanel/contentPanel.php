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

  <div id="font-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100">
      <?php
        _form("Panel.fontPanel", ["card" => $card, "uri" => $uri]);
      ?>
    </div>
  </div>

  <div id="hide-profile-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100">
      <?php $isProfileHidden = !empty($card["hide"]) && ($card["hide"] === true || $card["hide"] === 'true' || $card["hide"] === 1 || $card["hide"] === '1'); ?>
      <div class="p25 p-sml-15 br18 back-card-graphic shadow-card-graphic flex-column gap18 w100">
        <div class="ui-field-header">
          <p class="bold500 texto ui-section-title"><?= svg("eye") ?> Visibilidad del perfil</p>
          <span class="text-muted">Controla cuándo tu página pública está disponible para tus visitantes</span>
        </div>
        <div class="panel-subcard flex-row center-start gap10 w100">
          <p class="m0 texto">Tu perfil está <span class="bold500" id="profile-visibility-status-text"><?= $isProfileHidden ? "oculto" : "visible";?></span> para todo público.</p>
        </div>
        <div class="back-card-graphic panel-toggle-card flex-row center-between gap15 w100">
          <div class="ui-field-header">
            <p class="bold500 texto">Ocultar perfil</p>
            <span class="text-muted">Pausa temporalmente el acceso público a tu perfil</span>
          </div>
          <?php _form("Panel.hideProfile", ["card" => $card, "uri" => $uri]);?>
        </div>
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

  <!-- Submenú de Estadísticas 1: General -->
  <div id="stats-general-remote" class="remote-content flex-row top-center hidden" data-loaded="<?= !empty($stats) ? 'true' : 'false'; ?>">
    <div class="wpx630 w-mid-100 w-sml-100" id="stats-general-remote-wrapper">
      <?php if (!empty($stats)): ?>
        <?php
          _part("Dashboard.StatisticsPanel.statisticsPanel", [
            "stats"        => $stats,
            "monthlyViews" => $monthlyViews ?? ($stats["monthly_views"] ?? []),
            "isPremium"    => $premium ?? false,
            "card"         => $card ?? [],
            "user"         => $user ?? $card["profile"] ?? "",
            "uri"          => $uri ?? []
          ]);
        ?>
      <?php else: ?>
        <div id="stats-general-loading-placeholder" class="back-card-graphic shadow-card-graphic stats-card flex-row center-center p40 gap12 text-center w100">
          <span class="save-btn-spinner save-btn-spinner-dark"></span>
          <span class="bold500 text-muted">Cargando estadísticas generales...</span>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Submenú de Estadísticas 2: Enlaces -->
  <div id="stats-links-remote" class="remote-content flex-row top-center hidden" data-loaded="<?= !empty($stats) ? 'true' : 'false'; ?>">
    <div class="wpx630 w-mid-100 w-sml-100" id="stats-links-remote-wrapper">
      <?php if (!empty($stats)): ?>
        <?php
          _part("Dashboard.StatisticsPanel.statisticsLink", [
            "stats"        => $stats,
            "cto"          => $cto ?? ($stats["cto"] ?? []),
            "isPremium"    => $premium ?? false,
            "card"         => $card ?? [],
            "user"         => $user ?? $card["profile"] ?? "",
            "uri"          => $uri ?? []
          ]);
        ?>
      <?php else: ?>
        <div id="stats-links-loading-placeholder" class="back-card-graphic shadow-card-graphic stats-card flex-row center-center p40 gap12 text-center w100">
          <span class="save-btn-spinner save-btn-spinner-dark"></span>
          <span class="bold500 text-muted">Cargando estadísticas de enlaces...</span>
        </div>
      <?php endif; ?>
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