<?php
  use Base\Module\GraphicsModule;

  /**
   * Vista de Estadísticas Generales (Dashboard.StatisticsPanel.General.general)
   * 
   * Muestra las métricas del perfil:
   * 1. Visitas del mes actual (con evaluación de tendencia y Hybrid Spline).
   * 2. Feed «¿Quién te visitó?» (con avatares y perfiles si Premium; teaser desenfocado si Free).
   * 3. Geolocalización (Ranking de países y ciudades con GraphicsModule::horizontalBars).
   * 4. Fuentes de Tráfico / Referrers (Instagram, TikTok, WhatsApp... con GraphicsModule::horizontalBars).
   * 5. Matriz semanal Heatmap 5x7 con GraphicsModule::matrixHeatmap y recomendación inteligente de horarios.
   *
   * @var array  $stats        Estructura completa retornada por StatisticsModels::getStatsData()
   * @var array  $monthlyViews Métricas evaluadas de visitas del mes actual
   * @var bool   $isPremium    Booleano que indica si el creador cuenta con suscripción activa
   * @var array  $card         Datos de diseño y perfil del usuario
   * @var string $user         Nombre de usuario
   * @var array  $uri          Rutas de navegación
   */

  $userProfile = !empty($user) ? $user : ($card["profile"] ?? "user");
  $visitors    = is_array($stats["visitors"] ?? null) ? $stats["visitors"] : [];
  $geo         = is_array($stats["geo"] ?? null) ? $stats["geo"] : [];
  $countries   = is_array($geo["countries"] ?? null) ? $geo["countries"] : [];
  $cities      = is_array($geo["cities"] ?? null) ? $geo["cities"] : [];
  $referrers   = is_array($stats["referrers"] ?? null) ? $stats["referrers"] : [];
  $platforms   = is_array($referrers["platforms"] ?? null) ? $referrers["platforms"] : [];
  $topPlatform = $referrers["top_platform"] ?? null;
  $heatmap     = is_array($stats["heatmap"] ?? null) ? $stats["heatmap"] : [];
  $matrix      = is_array($heatmap["matrix"] ?? null) ? $heatmap["matrix"] : [];
  $maxVisits       = (int)($heatmap["max_visits"] ?? 0);
  $recommendation  = (string)($heatmap["recommendation"] ?? "");
  $visitorCount    = count($visitors);

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

  // Preparar datos para las barras horizontales de fuentes de tráfico (Top 6)
  $platformBars = [];
  foreach (array_slice($platforms, 0, 6) as $p) {
    $platformBars[] = [
      'label'   => $p['name'],
      'value'   => (float)$p['percentage'],
      'display' => $p['count'] . ' visitas (' . $p['percentage'] . '%)'
    ];
  }

  // Agrupación de las 24 horas en las 5 franjas canónicas de GraphicsModule::matrixHeatmap
  $timeSlots = [
    ['label' => '00-06h', 'name' => 'Madrugada (00:00 - 06:00)', 'hours' => [0, 1, 2, 3, 4, 5]],
    ['label' => '06-12h', 'name' => 'Mañana (06:00 - 12:00)',    'hours' => [6, 7, 8, 9, 10, 11]],
    ['label' => '12-15h', 'name' => 'Mediodía (12:00 - 15:00)',  'hours' => [12, 13, 14]],
    ['label' => '15-19h', 'name' => 'Tarde (15:00 - 19:00)',     'hours' => [15, 16, 17, 18]],
    ['label' => '19-24h', 'name' => 'Noche (19:00 - 24:00)',     'hours' => [19, 20, 21, 22, 23]],
  ];
  $daysCols = [
    1 => 'Lun',
    2 => 'Mar',
    3 => 'Mié',
    4 => 'Jue',
    5 => 'Vie',
    6 => 'Sáb',
    0 => 'Dom'
  ];

  // Calcular suma por bloque y determinar intensidad normalizada
  $slotMax = 0;
  $heatmapTotals = [];
  foreach ($timeSlots as $slot) {
    $rowCells = [];
    foreach ($daysCols as $dNum => $dShort) {
      $sum = 0;
      foreach ($slot['hours'] as $h) {
        $sum += (int)($matrix[$dNum][$h] ?? 0);
      }
      if ($sum > $slotMax) {
        $slotMax = $sum;
      }
      $rowCells[$dNum] = $sum;
    }
    $heatmapTotals[] = $rowCells;
  }

  $matrixHeatmapData = [];
  foreach ($heatmapTotals as $rIdx => $rowCells) {
    $row = [];
    $slotInfo = $timeSlots[$rIdx];
    foreach ($daysCols as $dNum => $dShort) {
      $v = $rowCells[$dNum];
      $intensity = $slotMax > 0 ? round($v / $slotMax, 2) : 0.0;
      $row[] = [
        'intensity' => $intensity,
        'val'       => "{$v} " . ($v === 1 ? 'visita' : 'visitas'),
        'label'     => "{$dShort} • {$slotInfo['name']}"
      ];
    }
    $matrixHeatmapData[] = $row;
  }

  GraphicsModule::configStyle([
    "colorLabel" => "texto",
    "axisLabel"  => "texto",
    "barWidth"       => 16,
    "color"      => "color8",
    "transition" => 500,
    'tooltip'    => false
  ]);
?>

<div class="flex-column gap25 w100 texto">

  <!-- ========================================================
       Cabecera de Estadísticas Generales
       ======================================================== -->
  <div class="flex-row center-between wrap gap15 w100">
    <div class="flex-column gap5">
      <h2 class="m0 x24 bold700 texto flex-row center-start gap10">
        <span class="color-secondary"><?= svg("chart", "x24"); ?></span>
        Estadísticas Generales
      </h2>
      <p class="m0 color-secondary">Métricas de rendimiento de tu perfil, visitantes recientes, geolocalización y mejores horarios de publicación.</p>
    </div>

    <div class="flex-row center-center gap10">
      <?php if ($isPremium): ?>
        <span class="premium-lock-badge">
          <?= svg("check-solid", "x12"); ?>
          Plan Premium
        </span>
      <?php else: ?>
        <a href="/planes" class="btn-premium-unlock" title="Desbloquea todas las estadísticas">
          <?= svg("lock", "x12"); ?>
          Mejorar a Premium
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- ========================================================
       1. Visitas del Mes Actual (Tendencia + Hybrid Spline)
       Visible para todos (Free y Premium)
       ======================================================== -->
  <?php
    _part("Dashboard.monthlyViews", [
      "monthly" => $monthlyViews ?? ($stats["monthly_views"] ?? [])
    ]);
  ?>

  <!-- ========================================================
       2. Feed «¿Quién te visitó?»
       Premium: Lista cronológica con avatares, perfiles y fecha relativa
       Free: Teaser desenfocado con candado y conteo de visitantes
       ======================================================== -->
  <div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
    <div class="flex-row center-between wrap gap10 w100">
      <div class="flex-row center-start gap10">
        <span class="flex-row center-center color-secondary x20">
          <?= svg("user", "x20"); ?>
        </span>
        <div class="flex-column gap2">
          <p class="m0 bold600 texto">¿Quién te visitó recientemente?</p>
          <p class="m0 x13 color-secondary">Usuarios registrados de la comunidad que han navegado por tu perfil.</p>
        </div>
      </div>

      <?php if ($isPremium): ?>
        <span class="back-card-graphic shadow-card-graphic p5 pl10 pr10 br10 bold500 x12 color-secondary">
          <?= e((string)$visitorCount); ?> <?= $visitorCount === 1 ? 'visitante' : 'visitantes'; ?>
        </span>
      <?php else: ?>
        <span class="premium-lock-badge">
          <?= svg("lock", "x10"); ?> Premium
        </span>
      <?php endif; ?>
    </div>

    <?php if ($isPremium): ?>
      <!-- Vista Desbloqueada (Premium) -->
      <?php if (!empty($visitors)): ?>
        <div class="flex-column gap10 w100 pt10">
          <?php foreach ($visitors as $v): ?>
            <div class="flex-row center-between wrap gap10 p10 pl15 pr15 br12 back-card-graphic shadow-card-graphic hover-scale-soft">
              <div class="flex-row center-start gap12">
                <img src="<?= eUrl($v['avatar']); ?>" alt="<?= e($v['display_name']); ?>" class="br50 object-cover" width="40" height="40" loading="lazy">
                <div class="flex-column gap2">
                  <a href="<?= eUrl($v['profile_url']); ?>" target="_blank" rel="noopener noreferrer" class="bold600 texto hover-underline leading-none">
                    <?= e($v['display_name']); ?>
                  </a>
                  <span class="x12 color-secondary">@<?= e($v['username']); ?></span>
                </div>
              </div>

              <div class="flex-row center-end gap15">
                <span class="x12 color-secondary flex-row center-start gap4">
                  <?= svg("clock", "x12"); ?>
                  <?= e($v['time_ago']); ?>
                </span>
                <span class="back-card-graphic shadow-card-graphic p4 pl8 pr8 br10 x11 bold600 color-secondary">
                  <?= e((string)$v['visit_count']); ?> <?= $v['visit_count'] === 1 ? 'visita' : 'visitas'; ?>
                </span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="flex-column center p30 gap10 text-center">
          <span class="color-secondary x30"><?= svg("user", "x30"); ?></span>
          <p class="m0 bold600 texto">Aún no hay visitas de usuarios registrados</p>
          <p class="m0 x13 color-secondary max-wpx450">Cuando otros creadores y seguidores con cuenta visiten tu enlace, aparecerán registrados en este feed cronológico.</p>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- Vista Bloqueada (Free Teaser) -->
      <div class="premium-blur-container w100">
        <div class="premium-blur-content flex-column gap10 w100 pt5">
          <div class="flex-row center-between p10 pl15 pr15 br12 back-card-graphic shadow-card-graphic">
            <div class="flex-row center-start gap12">
              <div class="br50 back-card-graphic" style="width:40px; height:40px;"></div>
              <div class="flex-column gap4">
                <span class="bold600 texto">Usuario de la comunidad</span>
                <span class="x12 color-secondary">@usuario_creador</span>
              </div>
            </div>
            <span class="x12 color-secondary">Hace 2 horas</span>
          </div>
          <div class="flex-row center-between p10 pl15 pr15 br12 back-card-graphic shadow-card-graphic">
            <div class="flex-row center-start gap12">
              <div class="br50 back-card-graphic" style="width:40px; height:40px;"></div>
              <div class="flex-column gap4">
                <span class="bold600 texto">Seguidor destacado</span>
                <span class="x12 color-secondary">@seguidor_web</span>
              </div>
            </div>
            <span class="x12 color-secondary">Ayer</span>
          </div>
          <div class="flex-row center-between p10 pl15 pr15 br12 back-card-graphic shadow-card-graphic">
            <div class="flex-row center-start gap12">
              <div class="br50 back-card-graphic" style="width:40px; height:40px;"></div>
              <div class="flex-column gap4">
                <span class="bold600 texto">Visitante recurrente</span>
                <span class="x12 color-secondary">@creador_pro</span>
              </div>
            </div>
            <span class="x12 color-secondary">Hace 3 días</span>
          </div>
        </div>

        <div class="premium-lock-overlay">
          <span class="color-warning x32 mb8"><?= svg("lock", "x32"); ?></span>
          <h4 class="m0 x18 bold700 texto mb5">Descubre quién visita tu perfil</h4>
          <p class="m0 color-secondary x13 max-wpx450 mb15">
            <?php if ($visitorCount > 0): ?>
              Tienes <strong class="texto bold600"><?= e((string)$visitorCount); ?></strong> <?= $visitorCount === 1 ? 'usuario registrado que visitó' : 'usuarios registrados que visitaron'; ?> tu perfil.
            <?php else: ?>
              Conoce en tiempo real la identidad de los creadores y usuarios que revisan tus enlaces.
            <?php endif; ?>
            Desbloquea el feed completo con el Plan Premium.
          </p>
          <a href="/planes" class="btn-premium-unlock">
            <?= svg("lock", "x13"); ?>
            Desbloquear con Premium
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- ========================================================
       3. Geolocalización (Países y Ciudades)
       Premium: Top países con GraphicsModule::horizontalBars y ciudades
       Free: País #1 visible y resto bloqueado
       ======================================================== -->
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

      <?php if (!$isPremium): ?>
        <span class="premium-lock-badge">
          <?= svg("lock", "x10"); ?> Premium
        </span>
      <?php endif; ?>
    </div>

    <?php if ($isPremium): ?>
      <!-- Vista Desbloqueada (Premium): Países con GraphicsModule::horizontalBars -->
      <?php if (!empty($countryBars)): ?>
        <div class="flex-column center-center gap10 w100">
          <div class="w-mid-90 wpx-desk-550">
            <?= GraphicsModule::horizontalBars([
              'bars'        => $countryBars,
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

  <!-- ========================================================
       4. Fuentes de Tráfico / Referrers (Instagram, TikTok, WhatsApp...)
       Premium: Desglose completo con GraphicsModule::horizontalBars
       Free: Teaser desenfocado con candado
       ======================================================== -->
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
          <div class="w-mid-100 wpx-desk-550">
            <?= GraphicsModule::horizontalBars([
              'bars'        => $platformBars,
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

  <!-- ========================================================
       5. Matriz Semanal (Heatmap) con GraphicsModule::matrixHeatmap
       Premium: Matriz interactiva + recomendación de mejor momento para publicar
       Free: Teaser desenfocado con candado
       ======================================================== -->
  <div class="p25 br20 back-card-graphic shadow-card-graphic flex-column gap15 w100">
    <div class="flex-row center-between wrap gap10 w100">
      <div class="flex-row center-start gap10">
        <span class="flex-row center-center color-secondary x20">
          <?= svg("calendar", "x20"); ?>
        </span>
        <div class="flex-column gap2">
          <p class="m0 bold600 texto">Actividad Semanal (Heatmap)</p>
          <p class="m0 x13 color-secondary">Distribución de audiencia por días y franjas horarias para optimizar tus lanzamientos.</p>
        </div>
      </div>

      <?php if (!$isPremium): ?>
        <span class="premium-lock-badge">
          <?= svg("lock", "x10"); ?> Premium
        </span>
      <?php endif; ?>
    </div>

    <?php if ($isPremium): ?>
      <!-- Vista Desbloqueada (Premium): Recomendación + Matriz 5x7 -->
      <?php if (!empty($recommendation)): ?>
        <div class="p15 br15 back-card-graphic shadow-card-graphic flex-row center-start gap12">
          <span class="color-warning x24 flex-shrink-0"><?= svg("lightbulb", "x24"); ?></span>
          <div class="flex-column gap2">
            <span class="bold700 texto x14">Recomendación inteligente</span>
            <p class="m0 x13 color-secondary leading-normal"><?= e($recommendation); ?></p>
          </div>
        </div>
      <?php endif; ?>

      <div class="w100 flex-column center pt10">
        <div class="w100 flex-column gap5">
          <div class="flex-row center-end gap10 pr15 x12">
            <span>Días: <strong>Lun — Dom</strong></span>
            <span>•</span>
            <span>Horas: <strong>00:00 — 24:00</strong></span>
          </div>

          <div class="w100 flex-row center-center">
            <?= GraphicsModule::matrixHeatmap([
              'rows'   => array_column($timeSlots, 'label'),
              'cols'   => array_values($daysCols),
              'matrix' => $matrixHeatmapData,
            ]); ?>
          </div>
        </div>
      </div>

    <?php else: ?>
      <!-- Vista Bloqueada (Free Teaser) con GraphicsModule -->
      <div class="premium-blur-container w100">
        <div class="premium-blur-content flex-column gap10 w100 pt5">
          <div class="p12 br12 back-card-graphic shadow-card-graphic">
            <p class="m0 x13">Recomendación: Tu mayor pico de audiencia ocurre los Miércoles en la tarde.</p>
          </div>
          <div class="w100">
            <?= GraphicsModule::matrixHeatmap([
              'rows'  => ['00-06h', '06-12h', '12-15h', '15-19h', '19-24h'],
              'cols'  => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
              'color' => 'texto'
            ]); ?>
          </div>
        </div>

        <div class="premium-lock-overlay">
          <span class="color-warning x28 mb6"><?= svg("lock", "x28"); ?></span>
          <h4 class="m0 x16 bold700 texto mb4">Encuentra tu Mejor Horario para Publicar</h4>
          <p class="m0 color-secondary x13 max-wpx450 mb15">El Heatmap semanal te revela con exactitud los días y franjas horarias con mayor afluencia de tus seguidores.</p>
          <a href="/planes" class="btn-premium-unlock">
            <?= svg("lock", "x12"); ?>
            Desbloquear Heatmap Semanal
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>
