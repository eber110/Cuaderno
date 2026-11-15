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

<div class="p25 p-sml-15 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
  <div class="flex-row center-between wrap gap10 w100">
    <div class="flex-row top-start gap10">
      <span class="flex-row center-center color-warning x22 hpx27 flex-shrink-0">
        <?= svg("trophy", "x22 color-warning"); ?>
      </span>
      <div class="flex-column gap2">
        <p class="m0 bold600 texto">Enlace Estrella</p>
        <p class="m0 color-secondary">El enlace o botón de tu perfil que genera la mayor cantidad de clics e interacción.</p>
      </div>
    </div>

    <?php if ($canViewSubs && !$isPremium): ?>
      <span class="premium-lock-badge">
        <?= svg("lock", "x10"); ?> Premium
      </span>
    <?php else: ?>
      <span class="star-link-badge">
        <?= svg("trophy", "x10"); ?> Mayor conversión
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isPremium): ?>
    <!-- Vista Desbloqueada (Premium) -->
    <?php if ($starLink && !empty($starLink['title'])): ?>
      <div class="p15 br15 back-card-graphic shadow-card-graphic flex-column gap12 w100">
        <div class="flex-row-desk flex-column-mid flex-column-sml center-between top-start-mid top-start-sml gap15 w100">
          <div class="flex-row center-start gap12 flex-1 w-mid-100 w-sml-100" style="min-width: 0;">
            <?php if (!empty($starLink['img'])): ?>
              <div class="wpx35 hpx35 br8 overflow-hidden flex-shrink-0 back-card-graphic shadow-card-graphic" style="width: 35px; height: 35px; border: 1px solid rgba(128,128,128,0.15);">
                <img src="<?= eUrl($starLink['img']); ?>" alt="<?= e($starLink['title']); ?>" class="cover w100 h100" width="35" height="35" loading="lazy" referrerpolicy="no-referrer">
              </div>
            <?php endif; ?>
            <div class="flex-column gap4 flex-1" style="min-width: 0;">
              <div class="flex-row center-start gap8 wrap">
                <span class="star-link-badge">
                  <?= svg("trophy", "x10"); ?> #1
                </span>
                <strong class="bold700 texto line-h12"><?= e($starLink['title']); ?></strong>
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
              <span class="x28 x-sml-22 bold700 color-success line-h1"><?= e((string)$starLink['month_clicks']); ?></span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">clics este mes</span>
            </div>
            <div class="flex-column top-center text-center gap4">
              <span class="x28 x-sml-22 bold700 texto line-h1"><?= e((string)$starLink['all_time_clicks']); ?></span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">clics totales</span>
            </div>
          </div>
        </div>

        <!-- Métricas de Tasa CTR y Distribución de Dispositivos -->
        <div class="flex-row center-between wrap gap12 pt10" style="border-top: 1px solid rgba(128,128,128,0.12);">
          <div class="flex-row center-start wrap gap15">
            <span class="color-secondary">
              CTR del mes: <strong class="texto bold600"><?= e((string)$starLink['ctr_month']); ?>%</strong>
            </span>
            <span class="color-secondary">
              CTR histórico: <strong class="texto bold600"><?= e((string)$starLink['ctr_total']); ?>%</strong>
            </span>
          </div>

          <div class="flex-row center-end wrap gap10">
            <span class="color-secondary">Móvil: <strong class="texto"><?= e((string)$starLink['mobile_percentage']); ?>%</strong></span>
            <span class="color-secondary">Escritorio: <strong class="texto"><?= e((string)$starLink['desktop_percentage']); ?>%</strong></span>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="flex-column center p30 gap10 text-center">
        <span class="color-secondary x30"><?= svg("trophy", "x30"); ?></span>
        <p class="m0 bold600 texto">Aún no se determina tu Enlace Estrella</p>
        <p class="m0 color-secondary wpx-max-450">Cuando tus enlaces reciban clics de los visitantes, el botón con mayor interacción se destacará automáticamente aquí.</p>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Vista Bloqueada (Free Teaser) -->
    <div class="premium-blur-container w100">
      <div class="premium-blur-content flex-column gap10 w100 pt5">
        <div class="p15 br15 back-card-graphic shadow-card-graphic flex-row-desk flex-column-mid flex-column-sml center-between top-start-mid top-start-sml gap15">
          <div class="flex-column gap4 w-mid-100 w-sml-100">
            <span class="star-link-badge">★ Enlace Estrella</span>
            <strong class="bold700 texto line-h12">Mi Curso Online / Tienda Oficial</strong>
            <span class="color-secondary truncate">https://ejemplo.com/tienda</span>
          </div>
          <div class="flex-row top-end top-around-mid top-around-sml gap25 gap-mid-15 gap-sml-15 w-mid-100 w-sml-100 pt-mid-5 pt-sml-5 flex-shrink-0">
            <div class="flex-column top-center text-center gap4">
              <span class="x28 x-sml-22 bold700 color-success line-h1">148</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">clics este mes</span>
            </div>
            <div class="flex-column top-center text-center gap4">
              <span class="x28 x-sml-22 bold700 texto line-h1">18.5%</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">tasa CTR</span>
            </div>
          </div>
        </div>
      </div>

      <div class="premium-lock-overlay">
        <span class="color-warning x30 mb6"><?= svg("lock", "x30"); ?></span>
        <h4 class="m0 x18 bold700 texto mb4">Descubre cuál es tu botón más exitoso</h4>
        <p class="m0 color-secondary wpx-max-450 mb15">Identifica con precisión qué enlace de tu perfil convierte mejor y genera más ventas o clics.</p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x12"); ?>
          Desbloquear Enlace Estrella
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
