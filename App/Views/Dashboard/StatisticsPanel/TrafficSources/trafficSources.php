<?php
  use Base\Module\GraphicsModule;

  /**
   * Vista de Fuentes de Tráfico (Dashboard.StatisticsPanel.TrafficSources.trafficSources)
   *
   * Muestra las plataformas y redes sociales desde las que llegan las visitas:
   * - Plan Premium: Desglose completo con GraphicsModule::horizontalBars, plataforma principal y chips resumen.
   * - Plan Free: Teaser desenfocado con candado de suscripción.
   *
   * @var array $referrers Array con 'platforms' y 'top_platform'
   * @var bool  $isPremium Booleano que indica si el creador cuenta con suscripción activa
   */

  $referrers   = is_array($referrers ?? null) ? $referrers : (is_array($stats["referrers"] ?? null) ? $stats["referrers"] : []);
  $platforms   = is_array($referrers["platforms"] ?? null) ? $referrers["platforms"] : [];
  $topPlatform = $referrers["top_platform"] ?? null;
  $isPremium   = (bool)($isPremium ?? false);

  // Preparar datos para las barras horizontales de fuentes de tráfico (Top 6)
  $platformBars = [];
  foreach (array_slice($platforms, 0, 6) as $p) {
    $platformBars[] = [
      'label'   => $p['name'],
      'value'   => (float)$p['percentage'],
      'display' => $p['count'] . ' visitas (' . $p['percentage'] . '%)'
    ];
  }
?>

<div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
  <div class="flex-row center-between wrap gap10 w100">
    <div class="flex-row center-start gap10">
      <span class="flex-row center-center color-secondary x20">
        <?= svg("share-node", "x20"); ?>
      </span>
      <div class="flex-column gap2">
        <p class="m0 bold600 texto">Fuentes de Tráfico (Referrers)</p>
        <p class="m0 x13 color-secondary">Plataformas y redes sociales desde las que tus visitantes descubren tu página.</p>
      </div>
    </div>

    <?php if (!$isPremium): ?>
      <span class="premium-lock-badge">
        <?= svg("lock", "x10"); ?> Premium
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isPremium): ?>
    <!-- Vista Desbloqueada (Premium): Fuentes de Tráfico con GraphicsModule -->
    <?php if (!empty($platformBars)): ?>
      <?php if ($topPlatform): ?>
        <div class="p12 pl15 pr15 br12 back-card-graphic shadow-card-graphic flex-row center-start gap10 mb5">
          <span class="color-success x20"><?= svg("trophy", "x20"); ?></span>
          <p class="m0 x13 texto">
            Tu principal fuente de tráfico es <strong class="bold600"><?= e($topPlatform['name']); ?></strong>, representando el <strong class="color-success bold600"><?= e((string)$topPlatform['percentage']); ?>%</strong> de tus visitas.
          </p>
        </div>
      <?php endif; ?>

      <div class="flex-column center-center gap10 w100">
        <div class="w-mid-90 wpx-desk-650">
          <?= GraphicsModule::horizontalBars([
            'bars'        => $platformBars,
            'width'       => 550,
            'color'       => 'texto',
            'valueSuffix' => '%',
            'showAxisY'   => true,
            'showAxisX'   => true
          ]); ?>
        </div>

        <!-- Chips de resumen de plataformas detectadas -->
        <div class="flex-row wrap gap10 pt10" style="border-top: 1px solid rgba(128,128,128,0.12);">
          <?php foreach ($platforms as $p): ?>
            <div class="flex-row center-between gap10 back-card-graphic shadow-card-graphic p5 pl12 pr12 br10 hover-scale-soft">
              <span class="flex-row center-start gap6">
                <span class="color-secondary"><?= svg($p['icon'] ?? 'link', 'x16'); ?></span>
                <strong class="texto bold500 x13"><?= e($p['name']); ?></strong>
              </span>
              <span class="color-secondary x12"><?= e((string)$p['count']); ?> (<?= e((string)$p['percentage']); ?>%)</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="flex-column center p30 gap10 text-center">
        <span class="color-secondary x30"><?= svg("link", "x30"); ?></span>
        <p class="m0 bold600 texto">Sin datos de origen registrados</p>
        <p class="m0 x13 color-secondary">Comparte tu enlace en tu bio de Instagram, TikTok o WhatsApp para empezar a registrar el canal de origen.</p>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Vista Bloqueada (Free Teaser) con GraphicsModule -->
    <div class="premium-blur-container w100">
      <div class="flex-column center-center gap10 w100">
        <div class="w-mid-100 wpx-desk-550">
          <?= GraphicsModule::horizontalBars([
            'bars' => [
              ['label' => 'Instagram', 'value' => 55.0, 'display' => '55%'],
              ['label' => 'TikTok', 'value' => 25.0, 'display' => '25%'],
              ['label' => 'WhatsApp', 'value' => 12.0, 'display' => '12%'],
              ['label' => 'Directo', 'value' => 8.0, 'display' => '8%'],
            ],
            'width'       => 550,
            'color'       => 'texto',
            'valueSuffix' => '%'
          ]); ?>
        </div>
      </div>

      <div class="premium-lock-overlay">
        <span class="color-warning x28 mb6"><?= svg("lock", "x28"); ?></span>
        <h4 class="m0 x16 bold700 texto mb4">¿De qué redes sociales llega tu público?</h4>
        <p class="m0 color-secondary x13 max-wpx450 mb15">Identifica si tus visitas provienen de Instagram, TikTok, WhatsApp o búsquedas directas para enfocar tus campañas.</p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x12"); ?>
          Desbloquear Fuentes de Tráfico
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
