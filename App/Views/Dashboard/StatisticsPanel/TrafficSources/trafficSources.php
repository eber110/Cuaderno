<?php
  use Base\Module\GraphicsModule;
  use App\Models\StatisticsModels;

  /**
   * Vista de Fuentes de Tráfico (Dashboard.StatisticsPanel.TrafficSources.trafficSources)
   *
   * @var array|null $referrers   Array con 'platforms' y 'top_platform'
   * @var array|null $stats       Estructura analítica global
   * @var bool|null  $isPremium   Booleano que indica si el creador cuenta con suscripción activa
   * @var bool|null  $canViewSubs Booleano que indica si las suscripciones están publicadas
   */

  $statsList   = isset($stats) && is_array($stats) ? $stats : [];
  $referrers   = isset($referrers) && is_array($referrers) 
    ? $referrers 
    : (isset($statsList["referrers"]) && is_array($statsList["referrers"]) ? $statsList["referrers"] : []);
  $platforms   = isset($referrers["platforms"]) && is_array($referrers["platforms"]) ? $referrers["platforms"] : [];
  $topPlatform = isset($referrers["top_platform"]) && is_array($referrers["top_platform"]) ? $referrers["top_platform"] : null;

  $canViewSubs = isset($canViewSubs) ? (bool)$canViewSubs : StatisticsModels::canViewSubscriptions();
  $rawPremium  = isset($isPremium) ? (bool)$isPremium : false;
  $isPremium   = !$canViewSubs ? true : $rawPremium;

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

<div class="back-card-graphic shadow-card-graphic stats-card flex-column gap18 w100">
  <div class="flex-row center-between wrap gap12 w100">
    <div class="flex-row top-start gap12">
      <span class="stats-icon-box">
        <?= svg("share-node", "x18"); ?>
      </span>
      <div class="ui-field-header">
        <p class="m0 bold500 texto">Fuentes de Tráfico (Referrers)</p>
        <p class="m0 color-secondary">Plataformas y redes sociales desde las que tus visitantes descubren tu página.</p>
      </div>
    </div>

    <?php if (!$isPremium && $canViewSubs): ?>
      <span class="premium-lock-badge">
        <?= svg("lock", "x12"); ?> Premium
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isPremium): ?>
    <!-- Vista Desbloqueada (Premium): Fuentes de Tráfico con GraphicsModule -->
    <?php if (!empty($platformBars)): ?>
      <?php if ($topPlatform): ?>
        <div class="stats-subcard flex-row center-start gap12 w100">
          <span class="stats-icon-box"><?= svg("trophy", "x18"); ?></span>
          <p class="m0 color-secondary">
            Tu principal fuente de tráfico es <strong class="texto bold500"><?= e($topPlatform['name']); ?></strong>, representando el <strong class="texto bold500"><?= e((string)$topPlatform['percentage']); ?>%</strong> de tus visitas.
          </p>
        </div>
      <?php endif; ?>

      <div class="flex-column gap18 w100">
        <div class="stats-chart-box w100">
          <div class="w100 wpx-max-650 mx-auto">
            <?= GraphicsModule::horizontalBars([
              'bars'          => $platformBars,
              'width'         => 650,
              'barHeight'     => 16,
              'axisLabelSize' => 18,
              'color'         => 'color1',
              'valueSuffix'   => '%',
              'showAxisY'     => true,
              'showAxisX'     => true
            ]); ?>
          </div>
        </div>

        <!-- Chips de resumen de plataformas detectadas -->
        <div class="flex-row wrap gap10 pt14 stats-divider w100">
          <?php foreach ($platforms as $p): ?>
            <div class="stats-chip hover-scale-soft">
              <span class="flex-row center-start gap6">
                <span class="flex-row center-center texto x16 flex-shrink-0"><?= svg($p['icon'] ?? 'link', 'x16'); ?></span>
                <strong class="texto bold500"><?= e($p['name']); ?></strong>
              </span>
              <span class="color-secondary"><?= e((string)$p['count']); ?> (<?= e((string)$p['percentage']); ?>%)</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="stats-subcard flex-column center p30 gap10 text-center">
        <span class="stats-icon-box"><?= svg("link", "x20"); ?></span>
        <p class="m0 bold500 texto">Sin datos de origen registrados</p>
        <p class="m0 color-secondary">Comparte tu enlace en tu bio de Instagram, TikTok o WhatsApp para empezar a registrar el canal de origen.</p>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Vista Bloqueada (Free Teaser) con GraphicsModule -->
    <div class="premium-blur-container w100">
      <div class="premium-blur-content stats-chart-box flex-column gap10 w100">
        <div class="w100 wpx-max-650 mx-auto">
          <?= GraphicsModule::horizontalBars([
            'bars' => [
              ['label' => 'Instagram', 'value' => 55.0, 'display' => '55%'],
              ['label' => 'TikTok', 'value' => 25.0, 'display' => '25%'],
              ['label' => 'WhatsApp', 'value' => 12.0, 'display' => '12%'],
              ['label' => 'Directo', 'value' => 8.0, 'display' => '8%'],
            ],
            'width'         => 650,
            'barHeight'     => 16,
            'axisLabelSize' => 18,
            'color'         => 'color1',
            'valueSuffix'   => '%'
          ]); ?>
        </div>
      </div>

      <div class="premium-lock-overlay">
        <span class="stats-icon-box mb10"><?= svg("lock", "x20"); ?></span>
        <h4 class="m0 bold500 texto mb6">¿De qué redes sociales llega tu público?</h4>
        <p class="m0 color-secondary wpx-max-450 mb15">Identifica si tus visitas provienen de Instagram, TikTok, WhatsApp o búsquedas directas para enfocar tus campañas.</p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x14"); ?>
          Desbloquear Fuentes de Tráfico
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
