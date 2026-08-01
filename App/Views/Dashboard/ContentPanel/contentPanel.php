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