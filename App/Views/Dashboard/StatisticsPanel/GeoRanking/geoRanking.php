<?php
  use Base\Module\GraphicsModule;
  use App\Models\StatisticsModels;

  /**
   * Vista de Geolocalización de Audiencia (Dashboard.StatisticsPanel.GeoRanking.geoRanking)
   *
   * @var array|null $geo         Array con listas de 'countries' y 'cities'
   * @var array|null $stats       Estructura analítica global
   * @var bool|null  $isPremium   Booleano que indica si el creador cuenta con suscripción activa
   * @var bool|null  $canViewSubs Booleano que indica si las suscripciones están publicadas
   */

  $statsList   = isset($stats) && is_array($stats) ? $stats : [];
  $geo         = isset($geo) && is_array($geo) 
    ? $geo 
    : (isset($statsList["geo"]) && is_array($statsList["geo"]) ? $statsList["geo"] : []);
  $countries   = isset($geo["countries"]) && is_array($geo["countries"]) ? $geo["countries"] : [];
  $cities      = isset($geo["cities"]) && is_array($geo["cities"]) ? $geo["cities"] : [];

  $canViewSubs = isset($canViewSubs) ? (bool)$canViewSubs : StatisticsModels::canViewSubscriptions();
  $rawPremium  = isset($isPremium) ? (bool)$isPremium : false;
  $isPremium   = !$canViewSubs ? true : $rawPremium;

  // Helper local para convertir código ISO de 2 letras en emoji de bandera
  $countryFlagEmoji = function (string $code): string {
    $c = strtoupper(trim($code));
    if (strlen($c) === 2 && ctype_alpha($c)) {
      return mb_chr(0x1F1E6 + ord($c[0]) - ord('A')) . mb_chr(0x1F1E6 + ord($c[1]) - ord('A'));
    }
    return "🌐";
  };

  // Preparar datos para las barras horizontales de países (Top 6)
  $countryBars = [];
  foreach (array_slice($countries, 0, 6) as $c) {
    $cName = $c['country_name'] . ' (' . $c['country_code'] . ')';
    $countryBars[] = [
      'label'   => $cName,
      'value'   => (float)$c['percentage'],
      'display' => $c['count'] . ' visitas (' . $c['percentage'] . '%)'
    ];
  }
?>

<div class="back-card-graphic shadow-card-graphic stats-card flex-column gap18 w100">
  <div class="flex-row center-between wrap gap12 w100">
    <div class="flex-row top-start gap12">
      <span class="stats-icon-box">
        <?= svg("globe", "x18"); ?>
      </span>
      <div class="ui-field-header">
        <p class="m0 bold500 texto">Geolocalización de Audiencia</p>
        <p class="m0 color-secondary">Distribución geográfica de las visitas a tu perfil analizadas por MaxMind GeoIP.</p>
      </div>
    </div>

    <?php if (!$isPremium && $canViewSubs): ?>
      <span class="premium-lock-badge">
        <?= svg("lock", "x12"); ?> Premium
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isPremium): ?>
    <!-- Vista Desbloqueada (Premium): Países con GraphicsModule::horizontalBars -->
    <?php if (!empty($countryBars)): ?>
      <div class="flex-column gap18 w100">
        <div class="stats-chart-box w100">
          <div class="w100 wpx-max-650 mx-auto">
            <?= GraphicsModule::horizontalBars([
              'bars'          => $countryBars,
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

        <?php if (!empty($cities)): ?>
          <div class="pt16 flex-column gap12 w100 stats-divider">
            <p class="m0 bold500 texto">Principales ciudades detectadas</p>
            <div class="flex-row wrap gap10">
              <?php foreach ($cities as $ct): ?>
                <div class="stats-chip hover-scale-soft">
                  <span class="flex-row center-start gap6">
                    <span><?= $countryFlagEmoji($ct['country_code']); ?></span>
                    <strong class="texto bold500"><?= e($ct['city_name']); ?></strong>
                  </span>
                  <span class="color-secondary"><?= e((string)$ct['count']); ?> (<?= e((string)$ct['percentage']); ?>%)</span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="stats-subcard flex-column center p30 gap10 text-center">
        <span class="stats-icon-box"><?= svg("globe", "x20"); ?></span>
        <p class="m0 bold500 texto">Sin registros de ubicación aún</p>
        <p class="m0 color-secondary">A medida que recibas visitas públicas se procesarán automáticamente sus países y ciudades.</p>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Vista Free: País #1 Desbloqueado + Resto Bloqueado -->
    <?php if (!empty($countries)): ?>
      <div class="flex-column gap10 w100">
        <div class="stats-subcard flex-column gap12">
          <div class="flex-row center-between wrap gap10">
            <div class="flex-row center-start gap10">
              <span class="x22 line-h1"><?= $countryFlagEmoji($countries[0]['country_code']); ?></span>
              <div class="ui-field-header">
                <strong class="texto bold500"><?= e($countries[0]['country_name']); ?></strong>
                <span class="color-success bold500">Tu país principal #1</span>
              </div>
            </div>
            <div class="flex-row center-end gap10">
              <span class="bold500 texto"><?= e((string)$countries[0]['count']); ?> visitas</span>
              <span class="stats-pill-badge"><?= e((string)$countries[0]['percentage']); ?>%</span>
            </div>
          </div>
          <div class="w100 wpx-max-600">
            <?= GraphicsModule::horizontalBars([
              'bars' => [
                [
                  'label'   => $countries[0]['country_name'],
                  'value'   => (float)$countries[0]['percentage'],
                  'display' => $countries[0]['count'] . ' visitas (' . $countries[0]['percentage'] . '%)'
                ]
              ],
              'barHeight'     => 16,
              'axisLabelSize' => 18,
              'color'         => 'color1',
              'valueSuffix'   => '%',
              'maxVal'        => 100
            ]); ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="premium-blur-container w100">
      <div class="premium-blur-content stats-chart-box flex-column gap12 w100">
        <div class="w100 wpx-max-650 mx-auto">
          <?= GraphicsModule::horizontalBars([
            'bars' => [
              ['label' => 'España', 'value' => 35.0, 'display' => '35%'],
              ['label' => 'México', 'value' => 25.0, 'display' => '25%'],
              ['label' => 'Argentina', 'value' => 18.0, 'display' => '18%'],
              ['label' => 'Colombia', 'value' => 12.0, 'display' => '12%'],
              ['label' => 'Chile', 'value' => 10.0, 'display' => '10%'],
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
        <h4 class="m0 bold500 texto mb6">Ranking Completo de Países y Ciudades</h4>
        <p class="m0 color-secondary wpx-max-450 mb15">Desbloquea el mapa analítico detallado con todas las naciones y ciudades de donde provienen tus visitantes.</p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x14"); ?>
          Desbloquear con Premium
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
