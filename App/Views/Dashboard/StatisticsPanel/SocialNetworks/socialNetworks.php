<?php
  use App\Models\StatisticsModels;

  /**
   * Vista de Interacción en Redes Sociales (Dashboard.StatisticsPanel.SocialNetworks.socialNetworks)
   *
   * @var array|null $rrss          Lista de redes sociales activas con métricas de clics
   * @var array|null $linksDetailed Detalle completo de enlaces
   * @var array|null $stats         Estructura analítica global
   * @var bool|null  $isPremium     Booleano que indica si el creador cuenta con suscripción activa
   * @var bool|null  $canViewSubs   Booleano que indica si las suscripciones están publicadas
   */

  $statsList     = isset($stats) && is_array($stats) ? $stats : [];
  $linksDetailed = isset($linksDetailed) && is_array($linksDetailed) 
    ? $linksDetailed 
    : (isset($statsList["links_detailed"]) && is_array($statsList["links_detailed"]) ? $statsList["links_detailed"] : []);
  $rrss          = isset($rrss) && is_array($rrss) 
    ? $rrss 
    : (isset($linksDetailed["rrss"]) && is_array($linksDetailed["rrss"]) ? $linksDetailed["rrss"] : []);

  $canViewSubs   = isset($canViewSubs) ? (bool)$canViewSubs : StatisticsModels::canViewSubscriptions();
  $rawPremium    = isset($isPremium) ? (bool)$isPremium : false;
  $isPremium     = !$canViewSubs ? true : $rawPremium;
?>

<?php if (!empty($rrss)): ?>
  <div class="back-card-graphic shadow-card-graphic stats-card flex-column gap18 w100">
    <div class="flex-row center-between wrap gap12 w100">
      <div class="flex-row top-start gap12">
        <span class="stats-icon-box">
          <?= svg("share-node", "x18"); ?>
        </span>
        <div class="ui-field-header">
          <p class="m0 bold500 texto">Interacción en Redes Sociales (Iconos)</p>
          <p class="m0 color-secondary">Clics registrados en los accesos rápidos a redes sociales de tu cabecera.</p>
        </div>
      </div>

      <?php if (!$isPremium && $canViewSubs): ?>
        <span class="premium-lock-badge">
          <?= svg("lock", "x12"); ?> Premium
        </span>
      <?php endif; ?>
    </div>

    <?php if ($isPremium): ?>
      <div class="flex-row wrap gap12 w100">
        <?php foreach ($rrss as $r): 
          $rName = !empty($r['name']) ? $r['name'] : ucfirst($r['network'] ?? 'Red social');
          $rKey  = !empty($r['key']) ? $r['key'] : ($r['icon'] ?? ($r['network'] ?? 'share-node'));
        ?>
          <div class="stats-subcard flex-row center-between gap15 flex-1" style="min-width: 220px;">
            <div class="flex-row center-start gap12">
              <span class="stats-icon-box"><?= svg($rKey, 'x18'); ?></span>
              <strong class="texto bold500"><?= e($rName); ?></strong>
            </div>
            <div class="flex-column center-end text-right gap2 flex-shrink-0">
              <span class="x24 x-sml-20 bold500 texto stats-kpi-value"><?= e((string)($r['month_clicks'] ?? 0)); ?></span>
              <span class="color-secondary line-h12">clics mes</span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="premium-blur-container w100">
        <div class="premium-blur-content flex-row wrap gap12 w100">
          <div class="stats-subcard flex-row center-between gap15 flex-1" style="min-width: 220px;">
            <strong class="texto bold500">Instagram</strong>
            <div class="flex-column center-end text-right gap2">
              <span class="x24 x-sml-20 bold500 texto stats-kpi-value">35</span>
              <span class="color-secondary line-h12">clics mes</span>
            </div>
          </div>
          <div class="stats-subcard flex-row center-between gap15 flex-1" style="min-width: 220px;">
            <strong class="texto bold500">TikTok</strong>
            <div class="flex-column center-end text-right gap2">
              <span class="x24 x-sml-20 bold500 texto stats-kpi-value">28</span>
              <span class="color-secondary line-h12">clics mes</span>
            </div>
          </div>
        </div>
        <div class="premium-lock-overlay">
          <span class="stats-icon-box mb10"><?= svg("lock", "x20"); ?></span>
          <p class="m0 bold500 texto mb6">Métricas de Redes Sociales</p>
          <p class="m0 color-secondary mb12">Descubre cuáles de tus perfiles sociales reciben más interés.</p>
          <a href="/planes" class="btn-premium-unlock">
            <?= svg("lock", "x14"); ?> Desbloquear con Premium
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
