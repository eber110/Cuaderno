<?php
  /**
   * @var mixed $card
   * @var mixed $stats
   * @var mixed $user
   * @var mixed $uri
   * @var mixed $session
   */
?>
<div class="remote-container animated p20">

  <div id="header-remote" class="remote-content flex-row top-center active">
    <div class="wpx630 w-mid-100 w-sml-100 p20">
      <?php 
        _form("Panel.headerPanel");
      ?>
    </div>
  </div>
<!-- button-remote -->
  <div id="background-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100 p20">
      <?php
        _form("Panel.backgroundPanel");
      ?>
    </div>
  </div>

  <div id="button-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100 p20">
      <?php
        _form("Panel.buttonPanel");
      ?>
    </div>
  </div>

  <div id="color-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100 p20">
      <?php
        _form("Panel.colorPanel");
      ?>
    </div>
  </div>

  <div id="hide-profile-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100 p20">
      <?php
        _form("Panel.hideProfile");
      ?>
    </div>
  </div>

  <div id="Content-button" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100 p20">
      <?php
        _form("Panel.contentButtonPanel");
      ?>
    </div>
  </div>

  <div id="Content-rrss" class="remote-content flex-row top-center hidden">
    <div class="wpx630 w-mid-100 w-sml-100 p20">
      <?php
        _form("Panel.contentRRSSPanel");
      ?>
    </div>
  </div>

  <div id="statistics-remote" class="remote-content flex-row top-center hidden">
    <div class="wpx890 w-mid-100 w-sml-100 p20">
      <?php
        _part("Dashboard.statisticsPanel", [
          "stats" => $stats ?? [], 
          "card"  => $card ?? [],
          "user"  => $user ?? $card["profile"] ?? "",
          "uri"   => $uri ?? []
        ]);
      ?>
    </div>
  </div>

  <div id="content-remote-4" class="remote-content hidden">
    <div class="post-content">
      <code data-lang="json"><?php print_r($card)?></code>
    </div>
  </div>

  <div id="content-remote-5" class="remote-content hidden">
    <div class="post-content">
      <code data-lang="json"><?php print_r(json_encode($session))?></code>
    </div>
  </div>

</div>

<!-- Script Anti-FOUT / Anti-Parpadeo Síncrono: Restaura el estado guardado del menú y panel antes del primer pintado del navegador -->
<script>
  (function() {
    try {
      var key = 'vertical_menu_active_' + window.location.pathname + '_default';
      var saved = localStorage.getItem(key);
      if (!saved) return;
      
      var parsed = JSON.parse(saved);
      if (!parsed || !parsed.remote) return;
      
      var targetId = parsed.remote;
      var container = document.querySelector('.remote-container');
      var menu = document.querySelector('.vertical-menu');

      // 1. Activar de inmediato el panel de contenido remoto correspondiente
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