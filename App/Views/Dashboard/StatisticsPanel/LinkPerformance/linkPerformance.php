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

<div class="back-card-graphic shadow-card-graphic stats-card flex-column gap18 w100">
  <div class="flex-row center-between wrap gap12 w100">
    <div class="flex-row top-start gap12">
      <span class="stats-icon-box">
        <?= svg("list", "x18"); ?>
      </span>
      <div class="ui-field-header">
        <p class="m0 bold500 texto">Rendimiento por Botón Individual</p>
        <p class="m0 color-secondary">Métricas desglosadas para cada uno de los enlaces activos configurados en tu perfil.</p>
      </div>
    </div>

    <?php if (!$isPremium && $canViewSubs): ?>
      <span class="premium-lock-badge">
        <?= svg("lock", "x12"); ?> Premium
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isPremium): ?>
    <!-- Vista Desbloqueada (Premium) -->
    <?php if (!empty($items)): ?>
      <div class="flex-column gap12 w100">
        <?php foreach ($items as $it): ?>
          <div class="stats-subcard flex-column gap12 w100">
            <div class="flex-row-desk flex-column-mid flex-column-sml center-between top-start-mid top-start-sml gap15 w100">
              <div class="flex-row center-start gap12 flex-1 w-mid-100 w-sml-100" style="min-width: 0;">
                <?php if (!empty($it['img'])): ?>
                  <div class="wpx35 hpx35 br8 overflow-hidden flex-shrink-0 stats-inner-card" style="width: 35px; height: 35px; padding: 0 !important;">
                    <img src="<?= eUrl($it['img']); ?>" alt="<?= e($it['title']); ?>" class="cover w100 h100" width="35" height="35" loading="lazy" referrerpolicy="no-referrer">
                  </div>
                <?php endif; ?>
                <div class="ui-field-header flex-1" style="min-width: 0;">
                  <div class="flex-row center-start gap8 wrap">
                    <strong class="bold500 texto"><?= e($it['title']); ?></strong>
                    <?php if (!empty($it['is_star'])): ?>
                      <span class="star-link-badge">
                        <?= svg("trophy", "x10"); ?> Estrella
                      </span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($it['url'])): ?>
                    <p class="m0 color-secondary flex-row center-start gap6">
                      <span class="flex-row center-center x16 flex-shrink-0"><?= svg("link", "x16"); ?></span>
                      <span class="truncate"><?= e(mb_strimwidth($it['url'], 0, 50, '...')); ?></span>
                    </p>
                  <?php endif; ?>
                </div>
              </div>

              <div class="flex-row top-end top-around-mid top-around-sml gap25 gap-mid-15 gap-sml-10 w-mid-100 w-sml-100 pt-mid-5 pt-sml-5 flex-shrink-0">
                <div class="flex-column top-center text-center gap4">
                  <span class="x24 x-sml-20 bold500 texto stats-kpi-value"><?= e((string)$it['month_clicks']); ?></span>
                  <span class="color-secondary line-h12" style="white-space: nowrap;">mes actual</span>
                </div>
                <div class="flex-column top-center text-center gap4">
                  <span class="x24 x-sml-20 bold500 texto stats-kpi-value"><?= e((string)$it['all_time_clicks']); ?></span>
                  <span class="color-secondary line-h12" style="white-space: nowrap;">acumulados</span>
                </div>
                <div class="flex-column top-center text-center gap4">
                  <span class="x24 x-sml-20 bold500 texto stats-kpi-value"><?= e((string)$it['ctr_month']); ?>%</span>
                  <span class="color-secondary line-h12" style="white-space: nowrap;">CTR mes</span>
                </div>
              </div>
            </div>

            <!-- Barra Móvil vs Escritorio para este enlace -->
            <div class="flex-column gap8 pt10 stats-divider">
              <div class="flex-row center-between wrap gap10 color-secondary">
                <span class="flex-row center-start gap8">
                  <span class="stats-dot-primary"></span>
                  <span class="color-secondary">Móvil: <strong class="texto bold500"><?= e((string)$it['mobile_percentage']); ?>%</strong> (<?= e((string)$it['mobile_clicks']); ?>)</span>
                </span>
                <span class="flex-row center-start gap8">
                  <span class="stats-dot-secondary"></span>
                  <span class="color-secondary">Escritorio: <strong class="texto bold500"><?= e((string)$it['desktop_percentage']); ?>%</strong> (<?= e((string)$it['desktop_clicks']); ?>)</span>
                </span>
              </div>
              <div class="stats-dual-bar">
                <div class="stats-bar-primary" style="width: <?= min(100, max(0, (float)$it['mobile_percentage'])); ?>%;" title="Móvil: <?= e((string)$it['mobile_percentage']); ?>%"></div>
                <div class="stats-bar-secondary" style="width: <?= min(100, max(0, (float)$it['desktop_percentage'])); ?>%;" title="Escritorio: <?= e((string)$it['desktop_percentage']); ?>%"></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="stats-subcard flex-column center p30 gap10 text-center">
        <span class="stats-icon-box"><?= svg("link", "x20"); ?></span>
        <p class="m0 bold500 texto">No tienes enlaces de contenido configurados</p>
        <p class="m0 color-secondary">Agrega enlaces desde el menú Contenido > Enlaces para empezar a registrar su rendimiento.</p>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Vista Bloqueada (Free Teaser) -->
    <div class="premium-blur-container w100">
      <div class="premium-blur-content flex-column gap12 w100">
        <div class="stats-subcard flex-row-desk flex-column-mid flex-column-sml center-between top-start-mid top-start-sml gap15">
          <div class="ui-field-header w-mid-100 w-sml-100">
            <strong class="bold500 texto">Mi Canal de YouTube</strong>
            <span class="color-secondary truncate">https://youtube.com/@canal</span>
          </div>
          <div class="flex-row top-end top-around-mid top-around-sml gap25 gap-mid-15 gap-sml-10 w-mid-100 w-sml-100 pt-mid-5 pt-sml-5 flex-shrink-0">
            <div class="flex-column top-center text-center gap4">
              <span class="x24 x-sml-20 bold500 texto stats-kpi-value">85</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">clics</span>
            </div>
            <div class="flex-column top-center text-center gap4">
              <span class="x24 x-sml-20 bold500 texto stats-kpi-value">12.4%</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">CTR</span>
            </div>
          </div>
        </div>
        <div class="stats-subcard flex-row-desk flex-column-mid flex-column-sml center-between top-start-mid top-start-sml gap15">
          <div class="ui-field-header w-mid-100 w-sml-100">
            <strong class="bold500 texto">Descargar E-book Gratuito</strong>
            <span class="color-secondary truncate">https://descargas.com/guia</span>
          </div>
          <div class="flex-row top-end top-around-mid top-around-sml gap25 gap-mid-15 gap-sml-10 w-mid-100 w-sml-100 pt-mid-5 pt-sml-5 flex-shrink-0">
            <div class="flex-column top-center text-center gap4">
              <span class="x24 x-sml-20 bold500 texto stats-kpi-value">62</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">clics</span>
            </div>
            <div class="flex-column top-center text-center gap4">
              <span class="x24 x-sml-20 bold500 texto stats-kpi-value">9.1%</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">CTR</span>
            </div>
          </div>
        </div>
        <div class="stats-subcard flex-row-desk flex-column-mid flex-column-sml center-between top-start-mid top-start-sml gap15">
          <div class="ui-field-header w-mid-100 w-sml-100">
            <strong class="bold500 texto">Contacto Directo por WhatsApp</strong>
            <span class="color-secondary truncate">https://wa.me/56900000000</span>
          </div>
          <div class="flex-row top-end top-around-mid top-around-sml gap25 gap-mid-15 gap-sml-10 w-mid-100 w-sml-100 pt-mid-5 pt-sml-5 flex-shrink-0">
            <div class="flex-column top-center text-center gap4">
              <span class="x24 x-sml-20 bold500 texto stats-kpi-value">41</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">clics</span>
            </div>
            <div class="flex-column top-center text-center gap4">
              <span class="x24 x-sml-20 bold500 texto stats-kpi-value">6.0%</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">CTR</span>
            </div>
          </div>
        </div>
      </div>

      <div class="premium-lock-overlay">
        <span class="stats-icon-box mb10"><?= svg("lock", "x20"); ?></span>
        <h4 class="m0 bold500 texto mb6">Análisis Detallado por Cada Botón</h4>
        <p class="m0 color-secondary wpx-max-450 mb15">Desbloquea la tasa de clics (CTR) individual, porcentaje móvil vs escritorio y métricas históricas de cada enlace.</p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x14"); ?>
          Desbloquear con Premium
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
