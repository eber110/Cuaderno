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

<div class="p25 p-sml-15 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
  <div class="flex-row center-between wrap gap10 w100">
    <div class="flex-row top-start gap10">
      <span class="flex-row center-center color-secondary x20 hpx27 flex-shrink-0">
        <?= svg("list", "x20"); ?>
      </span>
      <div class="flex-column gap2">
        <p class="m0 bold600 texto">Rendimiento por Botón Individual</p>
        <p class="m0 color-secondary">Métricas desglosadas para cada uno de los enlaces activos configurados en tu perfil.</p>
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
            <div class="flex-row-desk flex-column-mid flex-column-sml center-between top-start-mid top-start-sml gap15 w100">
              <div class="flex-row center-start gap12 flex-1 w-mid-100 w-sml-100" style="min-width: 0;">
                <?php if (!empty($it['img'])): ?>
                  <div class="wpx35 hpx35 br8 overflow-hidden flex-shrink-0 back-card-graphic shadow-card-graphic" style="width: 35px; height: 35px; border: 1px solid rgba(128,128,128,0.15);">
                    <img src="<?= eUrl($it['img']); ?>" alt="<?= e($it['title']); ?>" class="cover w100 h100" width="35" height="35" loading="lazy" referrerpolicy="no-referrer">
                  </div>
                <?php endif; ?>
                <div class="flex-column gap4 flex-1" style="min-width: 0;">
                  <div class="flex-row center-start gap8 wrap">
                    <strong class="bold600 texto line-h12"><?= e($it['title']); ?></strong>
                    <?php if (!empty($it['is_star'])): ?>
                      <span class="star-link-badge">
                        <?= svg("trophy", "x9"); ?> Estrella
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
                  <span class="x24 x-sml-20 bold700 color-success line-h1"><?= e((string)$it['month_clicks']); ?></span>
                  <span class="color-secondary line-h12" style="white-space: nowrap;">mes actual</span>
                </div>
                <div class="flex-column top-center text-center gap4">
                  <span class="x24 x-sml-20 bold700 texto line-h1"><?= e((string)$it['all_time_clicks']); ?></span>
                  <span class="color-secondary line-h12" style="white-space: nowrap;">acumulados</span>
                </div>
                <div class="flex-column top-center text-center gap4">
                  <span class="x24 x-sml-20 bold700 texto line-h1"><?= e((string)$it['ctr_month']); ?>%</span>
                  <span class="color-secondary line-h12" style="white-space: nowrap;">CTR mes</span>
                </div>
              </div>
            </div>

            <!-- Barra Móvil vs Escritorio para este enlace (Bicolor plana idéntica a cto.php) -->
            <div class="flex-column gap6 pt10" style="border-top: 1px solid rgba(128,128,128,0.1);">
              <div class="flex-row center-between wrap gap10 color-secondary">
                <span class="flex-row center-start gap5">
                  <span class="back-success br50" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%;"></span>
                  <p class="color-secondary">Móvil: <strong class="texto"><?= e((string)$it['mobile_percentage']); ?>%</strong> (<?= e((string)$it['mobile_clicks']); ?>)</p>
                </span>
                <span class="flex-row center-start gap5">
                  <span class="back8 br50" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%;"></span>
                  <p class="color-secondary">Escritorio: <strong class="texto"><?= e((string)$it['desktop_percentage']); ?>%</strong> (<?= e((string)$it['desktop_clicks']); ?>)</p>
                </span>
              </div>
              <div class="w100 hpx12 br10 flex-row overflow-hidden" style="background: rgba(128,128,128,0.15);">
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
        <p class="m0 color-secondary">Agrega enlaces desde el menú Contenido > Enlaces para empezar a registrar su rendimiento.</p>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Vista Bloqueada (Free Teaser) -->
    <div class="premium-blur-container w100">
      <div class="premium-blur-content flex-column gap12 w100 pt5">
        <div class="p15 br12 back-card-graphic shadow-card-graphic flex-row-desk flex-column-mid flex-column-sml center-between top-start-mid top-start-sml gap15">
          <div class="flex-column gap4 w-mid-100 w-sml-100">
            <strong class="texto line-h12">Mi Canal de YouTube</strong>
            <span class="color-secondary truncate">https://youtube.com/@canal</span>
          </div>
          <div class="flex-row top-end top-around-mid top-around-sml gap25 gap-mid-15 gap-sml-10 w-mid-100 w-sml-100 pt-mid-5 pt-sml-5 flex-shrink-0">
            <div class="flex-column top-center text-center gap4">
              <span class="x24 x-sml-20 bold700 color-success line-h1">85</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">clics</span>
            </div>
            <div class="flex-column top-center text-center gap4">
              <span class="x24 x-sml-20 bold700 texto line-h1">12.4%</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">CTR</span>
            </div>
          </div>
        </div>
        <div class="p15 br12 back-card-graphic shadow-card-graphic flex-row-desk flex-column-mid flex-column-sml center-between top-start-mid top-start-sml gap15">
          <div class="flex-column gap4 w-mid-100 w-sml-100">
            <strong class="texto line-h12">Descargar E-book Gratuito</strong>
            <span class="color-secondary truncate">https://descargas.com/guia</span>
          </div>
          <div class="flex-row top-end top-around-mid top-around-sml gap25 gap-mid-15 gap-sml-10 w-mid-100 w-sml-100 pt-mid-5 pt-sml-5 flex-shrink-0">
            <div class="flex-column top-center text-center gap4">
              <span class="x24 x-sml-20 bold700 color-success line-h1">62</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">clics</span>
            </div>
            <div class="flex-column top-center text-center gap4">
              <span class="x24 x-sml-20 bold700 texto line-h1">9.1%</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">CTR</span>
            </div>
          </div>
        </div>
        <div class="p15 br12 back-card-graphic shadow-card-graphic flex-row-desk flex-column-mid flex-column-sml center-between top-start-mid top-start-sml gap15">
          <div class="flex-column gap4 w-mid-100 w-sml-100">
            <strong class="texto line-h12">Contacto Directo por WhatsApp</strong>
            <span class="color-secondary truncate">https://wa.me/56900000000</span>
          </div>
          <div class="flex-row top-end top-around-mid top-around-sml gap25 gap-mid-15 gap-sml-10 w-mid-100 w-sml-100 pt-mid-5 pt-sml-5 flex-shrink-0">
            <div class="flex-column top-center text-center gap4">
              <span class="x24 x-sml-20 bold700 color-success line-h1">41</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">clics</span>
            </div>
            <div class="flex-column top-center text-center gap4">
              <span class="x24 x-sml-20 bold700 texto line-h1">6.0%</span>
              <span class="color-secondary line-h12" style="white-space: nowrap;">CTR</span>
            </div>
          </div>
        </div>
      </div>

      <div class="premium-lock-overlay">
        <span class="color-warning x28 mb6"><?= svg("lock", "x28"); ?></span>
        <h4 class="m0 x18 bold700 texto mb4">Análisis Detallado por Cada Botón</h4>
        <p class="m0 color-secondary wpx-max-450 mb15">Desbloquea la tasa de clics (CTR) individual, porcentaje móvil vs escritorio y métricas históricas de cada enlace.</p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x12"); ?>
          Desbloquear con Premium
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
