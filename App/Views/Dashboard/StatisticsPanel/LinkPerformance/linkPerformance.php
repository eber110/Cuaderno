<?php
  use App\Models\StatisticsModels;

  /**
   * Vista de Rendimiento por Botón Individual (Dashboard.StatisticsPanel.LinkPerformance.linkPerformance)
   *
   * @var array|null $items         Lista de enlaces de contenido con métricas individuales
   * @var array|null $linksDetailed Detalle completo de enlaces
   * @var array|null $stats         Estructura analítica global
   * @var bool|null  $isPremium     Booleano que indica si el creador cuenta con suscripción activa
   * @var bool|null  $canViewSubs   Booleano que indica si las suscripciones están publicadas
   */

  $statsList     = isset($stats) && is_array($stats) ? $stats : [];
  $linksDetailed = isset($linksDetailed) && is_array($linksDetailed) 
    ? $linksDetailed 
    : (isset($statsList["links_detailed"]) && is_array($statsList["links_detailed"]) ? $statsList["links_detailed"] : []);
  $itemsList     = isset($items) && is_array($items) 
    ? $items 
    : (isset($linksDetailed["items"]) && is_array($linksDetailed["items"]) ? $linksDetailed["items"] : []);
  $items         = $itemsList;

  $canViewSubs   = isset($canViewSubs) ? (bool)$canViewSubs : StatisticsModels::canViewSubscriptions();
  $rawIsPremium  = isset($isPremium) ? (bool)$isPremium : false;
  $isPremium     = !$canViewSubs ? true : $rawIsPremium;
?>

<div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
  <div class="flex-row center-between wrap gap10 w100">
    <div class="flex-row center-start gap10">
      <span class="flex-row center-center color-secondary x20">
        <?= svg("list", "x20"); ?>
      </span>
      <div class="flex-column gap2">
        <p class="m0 bold600 texto">Rendimiento por Botón Individual</p>
        <p class="m0 x13 color-secondary">Métricas desglosadas para cada uno de los enlaces activos configurados en tu perfil.</p>
      </div>
    </div>

    <?php if (!$isPremium && $canViewSubs): ?>
      <span class="premium-lock-badge">
        <?= svg("lock", "x10"); ?> Premium
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isPremium): ?>
    <!-- Vista Desbloqueada (Premium) -->
    <?php if (!empty($items)): ?>
      <div class="flex-column gap12 w100 pt5">
        <?php foreach ($items as $it): ?>
          <div class="p15 br15 back-card-graphic shadow-card-graphic flex-column gap10 w100 hover-scale-soft">
            <div class="flex-row center-between wrap gap10">
              <div class="flex-column gap4 flex-1" style="min-width: 220px;">
                <div class="flex-row center-start gap8 wrap">
                  <strong class="x15 bold600 texto"><?= e($it['title']); ?></strong>
                  <?php if (!empty($it['is_star'])): ?>
                    <span class="star-link-badge">
                      <?= svg("trophy", "x9"); ?> Estrella
                    </span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($it['url'])): ?>
                  <a href="<?= eUrl($it['url']); ?>" target="_blank" rel="noopener noreferrer" class="x12 color-secondary hover-underline flex-row center-start gap4">
                    <?= svg("link", "x11"); ?>
                    <?= e(mb_strimwidth($it['url'], 0, 50, '...')); ?>
                  </a>
                <?php endif; ?>
              </div>

              <div class="flex-row center-end wrap gap15">
                <div class="flex-column center-end">
                  <span class="x18 bold700 color-success leading-none"><?= e((string)$it['month_clicks']); ?></span>
                  <span class="x11 color-secondary">mes actual</span>
                </div>
                <div class="flex-column center-end">
                  <span class="x18 bold700 texto leading-none"><?= e((string)$it['all_time_clicks']); ?></span>
                  <span class="x11 color-secondary">acumulados</span>
                </div>
                <div class="flex-column center-end">
                  <span class="x18 bold700 texto leading-none"><?= e((string)$it['ctr_month']); ?>%</span>
                  <span class="x11 color-secondary">CTR mes</span>
                </div>
              </div>
            </div>

            <!-- Barra Móvil vs Escritorio para este enlace (Bicolor plana idéntica a cto.php) -->
            <div class="flex-column gap6 pt5" style="border-top: 1px solid rgba(128,128,128,0.1);">
              <div class="flex-row center-between x11 color-secondary">
                <span class="flex-row center-start gap5">
                  <span class="back-success" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%;"></span>
                  <span>Móvil: <strong class="texto"><?= e((string)$it['mobile_percentage']); ?>%</strong> (<?= e((string)$it['mobile_clicks']); ?>)</span>
                </span>
                <span class="flex-row center-start gap5">
                  <span class="back8" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%;"></span>
                  <span>Escritorio: <strong class="texto"><?= e((string)$it['desktop_percentage']); ?>%</strong> (<?= e((string)$it['desktop_clicks']); ?>)</span>
                </span>
              </div>
              <div class="w100 hpx8 br10 flex-row overflow-hidden" style="background: rgba(128,128,128,0.15);">
                <div class="back-success" style="width: <?= min(100, max(0, (float)$it['mobile_percentage'])); ?>%; height: 100%; transition: width 0.3s;" title="Móvil: <?= e((string)$it['mobile_percentage']); ?>%"></div>
                <div class="back8" style="width: <?= min(100, max(0, (float)$it['desktop_percentage'])); ?>%; height: 100%; transition: width 0.3s;" title="Escritorio: <?= e((string)$it['desktop_percentage']); ?>%"></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="flex-column center p30 gap10 text-center">
        <span class="color-secondary x30"><?= svg("link", "x30"); ?></span>
        <p class="m0 bold600 texto">No tienes enlaces de contenido configurados</p>
        <p class="m0 x13 color-secondary">Agrega enlaces desde el menú Contenido > Enlaces para empezar a registrar su rendimiento.</p>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Vista Bloqueada (Free Teaser) -->
    <div class="premium-blur-container w100">
      <div class="premium-blur-content flex-column gap12 w100 pt5">
        <div class="p12 br12 back-card-graphic shadow-card-graphic flex-row center-between">
          <div class="flex-column gap4">
            <strong class="texto">Mi Canal de YouTube</strong>
            <span class="x12 color-secondary">https://youtube.com/@canal</span>
          </div>
          <div class="flex-row gap15">
            <span>85 clics</span>
            <span>12.4% CTR</span>
          </div>
        </div>
        <div class="p12 br12 back-card-graphic shadow-card-graphic flex-row center-between">
          <div class="flex-column gap4">
            <strong class="texto">Descargar E-book Gratuito</strong>
            <span class="x12 color-secondary">https://descargas.com/guia</span>
          </div>
          <div class="flex-row gap15">
            <span>62 clics</span>
            <span>9.1% CTR</span>
          </div>
        </div>
        <div class="p12 br12 back-card-graphic shadow-card-graphic flex-row center-between">
          <div class="flex-column gap4">
            <strong class="texto">Contacto Directo por WhatsApp</strong>
            <span class="x12 color-secondary">https://wa.me/56900000000</span>
          </div>
          <div class="flex-row gap15">
            <span>41 clics</span>
            <span>6.0% CTR</span>
          </div>
        </div>
      </div>

      <div class="premium-lock-overlay">
        <span class="color-warning x28 mb6"><?= svg("lock", "x28"); ?></span>
        <h4 class="m0 x16 bold700 texto mb4">Análisis Detallado por Cada Botón</h4>
        <p class="m0 color-secondary x13 max-wpx450 mb15">Desbloquea la tasa de clics (CTR) individual, porcentaje móvil vs escritorio y métricas históricas de cada enlace.</p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x12"); ?>
          Desbloquear con Premium
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
