<?php
  /** 
   * @var mixed $stats 
   * @var mixed $card 
   */
  $summary        = $stats["summary"] ?? ['total_views' => 0, 'unique_views' => 0, 'total_clicks' => 0, 'ctr' => 0];
  $devices        = $stats["devices"] ?? [];
  $browsers       = $stats["browsers"] ?? [];
  $countries      = $stats["countries"] ?? [];
  $referrers      = $stats["referrers"] ?? [];
  $topDays        = $stats["topDays"] ?? [];
  $topHours       = $stats["topHours"] ?? [];
  $recommendation = $stats["recommendation"] ?? ['bestDay' => 'Lunes', 'bestHour' => '18:00 - 19:00', 'bestDayTotal' => 0, 'bestHourTotal' => 0];
  $socialStats    = $stats["socialStats"] ?? [];
  $recentViews    = $stats["recentViews"] ?? [];
  $userProfile    = $card["profile"] ?? "user";

  // Si no hay días u horas calculadas, habilitar datos de muestra (Sample Mode)
  $isSample = empty($topDays) && empty($topHours);

  if ($isSample) {
    $summary = ['total_views' => 150, 'unique_views' => 98, 'total_clicks' => 42, 'ctr' => 28.0];
    $topDays = [
      ["day_num" => "1", "day_name" => "Lunes",     "total" => 48],
      ["day_num" => "3", "day_name" => "Miércoles", "total" => 36],
      ["day_num" => "5", "day_name" => "Viernes",   "total" => 29],
      ["day_num" => "6", "day_name" => "Sábado",    "total" => 22],
      ["day_num" => "0", "day_name" => "Domingo",   "total" => 15]
    ];
    $topHours = [
      ["hour_num" => "18", "label" => "18:00 - 19:00", "total" => 34],
      ["hour_num" => "20", "label" => "20:00 - 21:00", "total" => 28],
      ["hour_num" => "14", "label" => "14:00 - 15:00", "total" => 21],
      ["hour_num" => "12", "label" => "12:00 - 13:00", "total" => 16],
      ["hour_num" => "09", "label" => "09:00 - 10:00", "total" => 11]
    ];
    $recommendation = [
      'bestDay'       => 'Lunes',
      'bestHour'      => '18:00 - 19:00',
      'bestDayTotal'  => 48,
      'bestHourTotal' => 34
    ];
    if (empty($socialStats)) {
      $socialStats = [
        ['key' => 'instagram', 'name' => 'Instagram',       'icon' => 'instagram',   'total' => 74, 'bestDay' => 'Miércoles', 'bestHour' => '20:00 - 21:00'],
        ['key' => 'tiktok',    'name' => 'TikTok',          'icon' => 'tiktok',      'total' => 41, 'bestDay' => 'Viernes',   'bestHour' => '22:00 - 23:00'],
        ['key' => 'facebook',  'name' => 'Facebook',        'icon' => 'facebook',    'total' => 28, 'bestDay' => 'Domingo',   'bestHour' => '15:00 - 16:00'],
        ['key' => 'direct',    'name' => 'Tráfico Directo', 'icon' => 'globe-solid', 'total' => 35, 'bestDay' => 'Lunes',     'bestHour' => '12:00 - 13:00']
      ];
    }
    if (empty($devices)) {
      $devices = [
        ['device_type' => 'mobile',  'total' => 105],
        ['device_type' => 'desktop', 'total' => 38],
        ['device_type' => 'tablet',  'total' => 7]
      ];
    }
    if (empty($browsers)) {
      $browsers = [
        ['browser' => 'Instagram App', 'total' => 62],
        ['browser' => 'Chrome',        'total' => 45],
        ['browser' => 'Safari',        'total' => 28],
        ['browser' => 'TikTok App',    'total' => 15]
      ];
    }
    if (empty($countries)) {
      $countries = [
        ['country_name' => 'Chile',  'country_code' => 'CL', 'total' => 95],
        ['country_name' => 'México', 'country_code' => 'MX', 'total' => 32],
        ['country_name' => 'España', 'country_code' => 'ES', 'total' => 23]
      ];
    }
    if (empty($referrers)) {
      $referrers = [
        ['referrer' => 'https://instagram.com', 'total' => 74],
        ['referrer' => 'https://tiktok.com',    'total' => 41],
        ['referrer' => 'Tráfico Directo',       'total' => 35]
      ];
    }
  }

  $data = [
    'isSample'       => $isSample,
    'userProfile'    => $userProfile,
    'summary'        => $summary,
    'recommendation' => $recommendation,
    'socialStats'    => $socialStats,
    'topDays'        => $topDays,
    'topHours'       => $topHours,
    'devices'        => $devices,
    'browsers'       => $browsers,
    'countries'      => $countries,
    'referrers'      => $referrers,
    'recentViews'    => $recentViews
  ];
?>

<div class="flex-column gap20 w100 textb">

  <!-- 1. Encabezado de la Vista -->
  <?php _part("Dashboard.statisticsHeader", $data); ?>

  <!-- 2. Recuento: Métricas Generales (Total Visitas, Visitas Únicas, Clics y CTR) -->
  <?php _part("Dashboard.generalSummaryCard", $data); ?>

  <!-- 3. Card Destacada de Recomendación de Horario para Publicar -->
  <?php _part("Dashboard.scheduleRecommendationCard", $data); ?>

  <!-- 4. Rendimiento y Horarios por Red Social (Calculado por Referrer) -->
  <?php _part("Dashboard.socialNetworksCard", $data); ?>

  <!-- 5. Días Más Visitados y Horarios Más Concurridos -->
  <?php _part("Dashboard.peakTrafficCard", $data); ?>

  <!-- 6. Desglose por Dispositivos y Navegadores (In-App) -->
  <?php _part("Dashboard.devicesBrowsersCard", $data); ?>

  <!-- 7. Ubicación Geográfica y Fuentes de Tráfico -->
  <?php _part("Dashboard.locationReferrersCard", $data); ?>

  <!-- 8. Registros Recientes en SQLite (Log Debug) -->
  <?php _part("Dashboard.recentLogCard", $data); ?>

</div>
