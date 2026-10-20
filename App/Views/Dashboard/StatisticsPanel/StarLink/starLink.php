<?php
  /**
   * Vista de Destacado Enlace Estrella (Dashboard.StatisticsPanel.StarLink.starLink)
   *
   * Destaca el enlace o botón con mayor volumen de interacción y conversión:
   * - Plan Premium: Detalle de clics del mes, clics acumulados, CTR individual y proporción móvil/desktop.
   * - Plan Free: Teaser desenfocado con candado de suscripción.
   *
   * @var array|null $starLink  Datos del enlace con mayor rendimiento
   * @var bool       $isPremium Booleano que indica si el creador cuenta con suscripción activa
   */

  $linksDetailed = is_array($linksDetailed ?? null) ? $linksDetailed : (is_array($stats["links_detailed"] ?? null) ? $stats["links_detailed"] : []);
  $starLink      = isset($starLink) ? $starLink : ($linksDetailed["star_link"] ?? null);
  $isPremium     = (bool)($isPremium ?? false);
?>

<div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
  <div class="flex-row center-between wrap gap10 w100">
    <div class="flex-row center-start gap10">
      <span class="flex-row center-center color-warning x22">
        <?= svg("trophy", "x22 color-warning"); ?>
      </span>
      <div class="flex-column gap2">
        <p class="m0 bold600 texto">Enlace Estrella</p>
        <p class="m0 x13 color-secondary">El enlace o botón de tu perfil que genera la mayor cantidad de clics e interacción.</p>
      </div>
    </div>

    <?php if ($isPremium): ?>
      <span class="star-link-badge">
        <?= svg("trophy", "x10"); ?> Mayor conversión
      </span>
    <?php else: ?>
      <span class="premium-lock-badge">
        <?= svg("lock", "x10"); ?> Premium
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isPremium): ?>
    <!-- Vista Desbloqueada (Premium) -->
    <?php if ($starLink && !empty($starLink['title'])): ?>
      <div class="p15 br15 back-card-graphic shadow-card-graphic flex-column gap12 w100">
        <div class="flex-row center-between wrap gap10">
          <div class="flex-column gap4">
            <div class="flex-row center-start gap8">
              <span class="star-link-badge">
                <?= svg("trophy", "x10"); ?> #1
              </span>
              <strong class="x16 bold700 texto"><?= e($starLink['title']); ?></strong>
            </div>
            <?php if (!empty($starLink['url'])): ?>
              <a href="<?= eUrl($starLink['url']); ?>" target="_blank" rel="noopener noreferrer" class="x12 color-secondary hover-underline flex-row center-start gap4">
                <?= svg("link", "x11"); ?>
                <?= e(mb_strimwidth($starLink['url'], 0, 60, '...')); ?>
              </a>
            <?php endif; ?>
          </div>

          <div class="flex-row center-end wrap gap10">
            <div class="flex-column center-end">
              <span class="x22 bold700 color-success leading-none"><?= e((string)$starLink['month_clicks']); ?></span>
              <span class="x11 color-secondary">clics este mes</span>
            </div>
            <div class="flex-column center-end pl15" style="border-left: 1px solid rgba(128,128,128,0.2);">
              <span class="x22 bold700 texto leading-none"><?= e((string)$starLink['all_time_clicks']); ?></span>
              <span class="x11 color-secondary">clics totales</span>
            </div>
          </div>
        </div>

        <!-- Métricas de Tasa CTR y Distribución de Dispositivos -->
        <div class="flex-row center-between wrap gap12 pt10" style="border-top: 1px solid rgba(128,128,128,0.12);">
          <div class="flex-row center-start gap15">
            <span class="x13 color-secondary">
              CTR del mes: <strong class="texto bold600"><?= e((string)$starLink['ctr_month']); ?>%</strong>
            </span>
            <span class="x13 color-secondary">
              CTR histórico: <strong class="texto bold600"><?= e((string)$starLink['ctr_total']); ?>%</strong>
            </span>
          </div>

          <div class="flex-row center-end gap10">
            <span class="x12 color-secondary">Móvil: <strong class="texto"><?= e((string)$starLink['mobile_percentage']); ?>%</strong></span>
            <span class="x12 color-secondary">Escritorio: <strong class="texto"><?= e((string)$starLink['desktop_percentage']); ?>%</strong></span>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="flex-column center p30 gap10 text-center">
        <span class="color-secondary x30"><?= svg("trophy", "x30"); ?></span>
        <p class="m0 bold600 texto">Aún no se determina tu Enlace Estrella</p>
        <p class="m0 x13 color-secondary max-wpx450">Cuando tus enlaces reciban clics de los visitantes, el botón con mayor interacción se destacará automáticamente aquí.</p>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Vista Bloqueada (Free Teaser) -->
    <div class="premium-blur-container w100">
      <div class="premium-blur-content flex-column gap10 w100 pt5">
        <div class="p15 br15 back-card-graphic shadow-card-graphic flex-row center-between">
          <div class="flex-column gap4">
            <span class="star-link-badge">★ Enlace Estrella</span>
            <strong class="x16 bold700 texto">Mi Curso Online / Tienda Oficial</strong>
            <span class="x12 color-secondary">https://ejemplo.com/tienda</span>
          </div>
          <div class="flex-row gap15">
            <div class="flex-column center-end">
              <span class="x22 bold700 color-success">148</span>
              <span class="x11 color-secondary">clics este mes</span>
            </div>
            <div class="flex-column center-end">
              <span class="x22 bold700 texto">18.5%</span>
              <span class="x11 color-secondary">tasa CTR</span>
            </div>
          </div>
        </div>
      </div>

      <div class="premium-lock-overlay">
        <span class="color-warning x30 mb6"><?= svg("lock", "x30"); ?></span>
        <h4 class="m0 x17 bold700 texto mb4">Descubre cuál es tu botón más exitoso</h4>
        <p class="m0 color-secondary x13 max-wpx450 mb15">Identifica con precisión qué enlace de tu perfil convierte mejor y genera más ventas o clics.</p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x12"); ?>
          Desbloquear Enlace Estrella
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
