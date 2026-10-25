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

<div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
  <div class="flex-row center-between wrap gap10 w100">
    <div class="flex-row center-start gap10">
      <span class="flex-row center-center color-secondary x20">
        <?= svg("globe", "x20"); ?>
      </span>
      <div class="flex-column gap2">
        <p class="m0 bold600 texto">Geolocalización de Audiencia</p>
        <p class="m0 x13 color-secondary">Distribución geográfica de las visitas a tu perfil analizadas por MaxMind GeoIP.</p>
      </div>
    </div>

    <?php if (!$isPremium && $canViewSubs): ?>
      <span class="premium-lock-badge">
        <?= svg("lock", "x10"); ?> Premium
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isPremium): ?>
    <!-- Vista Desbloqueada (Premium): Países con GraphicsModule::horizontalBars -->
    <?php if (!empty($countryBars)): ?>
      <div class="flex-column center-center gap10 w100">
        <div class="w-mid-90 wpx-desk-650">
          <?= GraphicsModule::horizontalBars([
            'bars'        => $countryBars,
            'width'       => 650,
            'valueSuffix' => '%',
            'showAxisY'   => true,
            'showAxisX'   => true
          ]); ?>
        </div>

        <?php if (!empty($cities)): ?>
          <div class="pt15 mt10 flex-column gap12 w100" style="border-top: 1px solid rgba(128,128,128,0.15);">
            <p class="m0 bold600 x14 color-secondary">Principales Ciudades detectadas</p>
            <div class="flex-row wrap gap10">
              <?php foreach ($cities as $ct): ?>
                <div class="flex-row center-between gap10 back-card-graphic shadow-card-graphic hover-scale-soft p5 pl12 pr12 br10">
                  <span class="flex-row center-start gap6">
                    <span><?= $countryFlagEmoji($ct['country_code']); ?></span>
                    <strong class="texto bold500"><?= e($ct['city_name']); ?></strong>
                  </span>
                  <span class="color-secondary x12"><?= e((string)$ct['count']); ?> (<?= e((string)$ct['percentage']); ?>%)</span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="flex-column center p30 gap10 text-center">
        <span class="color-secondary x30"><?= svg("globe", "x30"); ?></span>
        <p class="m0 bold600 texto">Sin registros de ubicación aún</p>
        <p class="m0 x13 color-secondary">A medida que recibas visitas públicas se procesarán automáticamente sus países y ciudades.</p>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Vista Free: País #1 Desbloqueado + Resto Bloqueado -->
    <?php if (!empty($countries)): ?>
      <div class="flex-column gap10 w100 pt5">
        <div class="p15 br15 back-card-graphic shadow-card-graphic flex-column gap10">
          <div class="flex-row center-between wrap gap8">
            <div class="flex-row center-start gap8">
              <span class="x22 leading-none"><?= $countryFlagEmoji($countries[0]['country_code']); ?></span>
              <div>
                <strong class="texto bold600"><?= e($countries[0]['country_name']); ?></strong>
                <span class="x11 color-success bold600 ml5">Tu país principal #1</span>
              </div>
            </div>
            <div class="flex-row center-end gap10">
              <span class="bold600 texto"><?= e((string)$countries[0]['count']); ?> visitas</span>
              <span class="x12 color-secondary bold500"><?= e((string)$countries[0]['percentage']); ?>%</span>
            </div>
          </div>
          <div class="w100 max-wpx600">
            <?= GraphicsModule::horizontalBars([
              'bars' => [
                [
                  'label'   => $countries[0]['country_name'],
                  'value'   => (float)$countries[0]['percentage'],
                  'display' => $countries[0]['count'] . ' visitas (' . $countries[0]['percentage'] . '%)'
                ]
              ],
              'color'       => 'texto',
              'valueSuffix' => '%',
              'maxVal'      => 100,
              'barHeight'   => 12
            ]); ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="premium-blur-container w100 mt10">
      <div class="premium-blur-content flex-column gap12 w100 pt5">
        <div class="w100 max-wpx650 mx-auto">
          <?= GraphicsModule::horizontalBars([
            'bars' => [
              ['label' => 'España', 'value' => 35.0, 'display' => '35%'],
              ['label' => 'México', 'value' => 25.0, 'display' => '25%'],
              ['label' => 'Argentina', 'value' => 18.0, 'display' => '18%'],
              ['label' => 'Colombia', 'value' => 12.0, 'display' => '12%'],
              ['label' => 'Chile', 'value' => 10.0, 'display' => '10%'],
            ],
            'width'       => 650,
            'color'       => 'texto',
            'valueSuffix' => '%'
          ]); ?>
        </div>
      </div>

      <div class="premium-lock-overlay">
        <span class="color-warning x28 mb6"><?= svg("lock", "x28"); ?></span>
        <h4 class="m0 x16 bold700 texto mb4">Ranking Completo de Países y Ciudades</h4>
        <p class="m0 color-secondary x13 max-wpx450 mb15">Desbloquea el mapa analítico detallado con todas las naciones y ciudades de donde provienen tus visitantes.</p>
        <a href="/planes" class="btn-premium-unlock">
          <?= svg("lock", "x12"); ?>
          Desbloquear con Premium
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>
