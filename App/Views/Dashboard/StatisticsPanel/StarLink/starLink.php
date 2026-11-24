<?php
  use App\Models\StatisticsModels;

  /**
   * Vista de Destacado Enlace Estrella (Dashboard.StatisticsPanel.StarLink.starLink)
   *
   * @var array|null $starLink      Datos del enlace con mayor rendimiento
   * @var array|null $linksDetailed Detalle completo de enlaces
   * @var array|null $stats         Estructura analítica global
   * @var bool|null  $isPremium     Booleano que indica si el creador cuenta con suscripción activa
   * @var bool|null  $canViewSubs   Booleano que indica si las suscripciones están publicadas
   */

  $statsList     = isset($stats) && is_array($stats) ? $stats : [];
  $linksDetailed = isset($linksDetailed) && is_array($linksDetailed) 
    ? $linksDetailed 
    : (isset($statsList["links_detailed"]) && is_array($statsList["links_detailed"]) ? $statsList["links_detailed"] : []);
  $starLink      = isset($starLink) && is_array($starLink) 
    ? $starLink 
    : (isset($linksDetailed["star_link"]) && is_array($linksDetailed["star_link"]) ? $linksDetailed["star_link"] : null);

  $canViewSubs   = isset($canViewSubs) ? (bool)$canViewSubs : StatisticsModels::canViewSubscriptions();
  $rawPremium    = isset($isPremium) ? (bool)$isPremium : false;
  $isPremium     = !$canViewSubs ? true : $rawPremium;
?>

<div class="back-card-graphic shadow-card-graphic stats-card flex-column gap18 w100">
  <div class="flex-row center-between wrap gap12 w100">
    <div class="flex-row top-start gap12">
      <span class="stats-icon-box">
        <?= svg("trophy", "x18"); ?>
      </span>
      <div class="ui-field-header">
        <p class="m0 bold500 texto">Enlace Estrella</p>
        <p class="m0 color-secondary">El enlace o botón de tu perfil que genera la mayor cantidad de clics e interacción.</p>
      </div>
    </div>

    <?php if ($canViewSubs && !$isPremium): ?>
      <span class="premium-lock-badge">
        <?= svg("lock", "x12"); ?> Premium
      </span>
    <?php else: ?>
      <span class="star-link-badge">
        <?= svg("trophy", "x12"); ?> Mayor conversión
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isPremium): ?>
    <!-- Vista Desbloqueada (Premium) -->
    <?php if ($starLink && !empty($starLink['title'])): ?>
      <div class="stats-subcard flex-column gap14 w100">
        <div class="flex-row-desk flex-column-mid flex-column-sml center-between top-start-mid top-start-sml gap15 w100">
          <div class="flex-row center-start gap12 flex-1 w-mid-100 w-sml-100" style="min-width: 0;">
            <?php if (!empty($starLink['img'])): ?>
              <div class="wpx35 hpx35 br8 overflow-hidden flex-shrink-0 stats-inner-card" style="width: 35px; height: 35px; padding: 0 !important;">
                <img src="<?= eUrl($starLink['img']); ?>" alt="<?= e($starLink['title']); ?>" class="cover w100 h100" width="35" height="35" loading="lazy" referrerpolicy="no-referrer">
              </div>
            <?php endif; ?>
            <div class="ui-field-header flex-1" style="min-width: 0;">
              <div class="flex-row center-start gap8 wrap">
                <span class="stats-pill-badge-solid">#1</span>
                <strong class="bold500 texto"><?= e($starLink['title']); ?></strong>
              </div>
              <?php if (!empty($starLink['url'])): ?>
                <p class="m0 color-secondary flex-row center-start gap6">
                  <span class="flex-row center-center x16 flex-shrink-0"><?= svg("link", "x16"); ?></span>
                  <span class="truncate"><?= e(mb_strimwidth($starLink['url'], 0, 60, '...')); ?></span>
                </p>
              <?php endif; ?>
            </div>
          </div>

          <div class="flex-row top-end top-around-mid top-around-sml gap25 gap-mid-15 gap-sml-15 w-mid-100 w-sml-100 pt-mid-5 pt-sml-5 flex-shrink-0">
            <div class="flex-column top-center text-center gap4">
              <span class="x28 x-sml-22 bold500 texto stats-kpi-value"><?= e((string)$starLink['month_clicks']); ?></span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">clics este mes</span>
            </div>
            <div class="flex-column top-center text-center gap4">
              <span class="x28 x-sml-22 bold500 texto stats-kpi-value"><?= e((string)$starLink['all_time_clicks']); ?></span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">clics totales</span>
            </div>
          </div>
        </div>

        <!-- Métricas de Tasa CTR y Distribución de Dispositivos -->
        <div class="flex-row center-between wrap gap12 pt12 stats-divider">
          <div class="flex-row center-start wrap gap15">
            <span class="color-secondary">
              CTR del mes: <strong class="texto bold500"><?= e((string)$starLink['ctr_month']); ?>%</strong>
            </span>
            <span class="color-secondary">
              CTR histórico: <strong class="texto bold500"><?= e((string)$starLink['ctr_total']); ?>%</strong>
            </span>
          </div>

          <div class="flex-row center-end wrap gap12">
            <span class="color-secondary">Móvil: <strong class="texto bold500"><?= e((string)$starLink['mobile_percentage']); ?>%</strong></span>
            <span class="color-secondary">Escritorio: <strong class="texto bold500"><?= e((string)$starLink['desktop_percentage']); ?>%</strong></span>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="stats-subcard flex-column center p30 gap10 text-center">
        <span class="stats-icon-box"><?= svg("trophy", "x20"); ?></span>
        <p class="m0 bold500 texto">Aún no se determina tu Enlace Estrella</p>
        <p class="m0 color-secondary wpx-max-450">Cuando tus enlaces reciban clics de los visitantes, el botón con mayor interacción se destacará automáticamente aquí.</p>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Vista Bloqueada (Free Teaser) -->
    <div class="premium-blur-container w100">
      <div class="premium-blur-content flex-column gap10 w100">
        <div class="stats-subcard flex-row-desk flex-column-mid flex-column-sml center-between top-start-mid top-start-sml gap15">
          <div class="ui-field-header w-mid-100 w-sml-100">
            <span class="star-link-badge">★ Enlace Estrella</span>
            <strong class="bold500 texto">Mi Curso Online / Tienda Oficial</strong>
            <span class="color-secondary truncate">https://ejemplo.com/tienda</span>
          </div>
          <div class="flex-row top-end top-around-mid top-around-sml gap25 gap-mid-15 gap-sml-15 w-mid-100 w-sml-100 pt-mid-5 pt-sml-5 flex-shrink-0">
            <div class="flex-column top-center text-center gap4">
              <span class="x28 x-sml-22 bold500 texto stats-kpi-value">148</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">clics este mes</span>
            </div>
            <div class="flex-column top-center text-center gap4">
              <span class="x28 x-sml-22 bold500 texto stats-kpi-value">18.5%</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">tasa CTR</span>
            </div>
          </div>
        </div>
      </div>

      <div class="premium-lock-overlay">
        <span class="stats-icon-box mb10"><?= svg("lock", "x20"); ?></span>
        <h4 class="m0 bold500 texto mb6">Descubre cuál es tu botón más exitoso</h4>
        <p class="m0 color-secondary wpx-max-450 mb15">Identifica con precisión qué enlace de tu perfil convierte mejor y genera más ventas o clics.</p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x14"); ?>
          Desbloquear Enlace Estrella
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
