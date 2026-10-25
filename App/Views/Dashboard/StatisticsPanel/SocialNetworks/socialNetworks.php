<?php
  /**
   * Vista de Interacción en Redes Sociales (Dashboard.StatisticsPanel.SocialNetworks.socialNetworks)
   *
   * Muestra las métricas de clics hacia redes sociales de cabecera:
   * - Plan Premium: Clics desglosados por cada red configurada (Instagram, TikTok, WhatsApp, etc.).
   * - Plan Free: Teaser desenfocado con candado de suscripción.
   *
   * @var array $rrss      Lista de redes sociales activas con métricas de clics
   * @var bool  $isPremium Booleano que indica si el creador cuenta con suscripción activa
   */

  $linksDetailed = is_array($linksDetailed ?? null) ? $linksDetailed : (is_array($stats["links_detailed"] ?? null) ? $stats["links_detailed"] : []);
  $rrss          = is_array($rrss ?? null) ? $rrss : (is_array($linksDetailed["rrss"] ?? null) ? $linksDetailed["rrss"] : []);
  $canViewSubs   = isset($canViewSubs) ? (bool)$canViewSubs : \App\Models\StatisticsModels::canViewSubscriptions();
  $isPremium     = !$canViewSubs ? true : (bool)($isPremium ?? false);
?>

<?php if (!empty($rrss)): ?>
  <div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
    <div class="flex-row center-between wrap gap10 w100">
      <div class="flex-row center-start gap10">
        <span class="flex-row center-center color-secondary x20">
          <?= svg("share-node", "x20"); ?>
        </span>
        <div class="flex-column gap2">
          <p class="m0 bold600 texto">Interacción en Redes Sociales (Iconos)</p>
          <p class="m0 x13 color-secondary">Clics registrados en los accesos rápidos a redes sociales de tu cabecera.</p>
        </div>
      </div>

      <?php if (!$isPremium && $canViewSubs): ?>
        <span class="premium-lock-badge">
          <?= svg("lock", "x10"); ?> Premium
        </span>
      <?php endif; ?>
    </div>

    <?php if ($isPremium): ?>
      <div class="flex-row wrap gap12 w100 pt5">
        <?php foreach ($rrss as $r): 
          $rName = !empty($r['name']) ? $r['name'] : ucfirst($r['network'] ?? 'Red social');
          $rKey  = !empty($r['key']) ? $r['key'] : ($r['icon'] ?? ($r['network'] ?? 'share-node'));
        ?>
          <div class="flex-row center-between gap15 p12 pl15 pr15 br12 back-card-graphic shadow-card-graphic flex-1 hover-scale-soft" style="min-width: 180px;">
            <div class="flex-row center-start gap10">
              <span class="x20 color-secondary"><?= svg($rKey, 'x20'); ?></span>
              <strong class="texto bold600"><?= e($rName); ?></strong>
            </div>
            <div class="flex-column center-end">
              <span class="bold700 texto x15"><?= e((string)($r['month_clicks'] ?? 0)); ?></span>
              <span class="x11 color-secondary">clics mes</span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="premium-blur-container w100">
        <div class="premium-blur-content flex-row wrap gap12 w100 pt5">
          <div class="flex-row center-between p12 br12 back-card-graphic shadow-card-graphic flex-1" style="min-width: 180px;">
            <span>Instagram</span>
            <span>35 clics</span>
          </div>
          <div class="flex-row center-between p12 br12 back-card-graphic shadow-card-graphic flex-1" style="min-width: 180px;">
            <span>TikTok</span>
            <span>28 clics</span>
          </div>
        </div>
        <div class="premium-lock-overlay">
          <span class="color-warning x24 mb5"><?= svg("lock", "x24"); ?></span>
          <p class="m0 bold700 texto mb4">Métricas de Redes Sociales</p>
          <p class="m0 color-secondary x13 mb12">Descubre cuáles de tus perfiles sociales reciben más interés.</p>
          <a href="/planes" class="btn-premium-unlock">
            <?= svg("lock", "x12"); ?> Desbloquear con Premium
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
