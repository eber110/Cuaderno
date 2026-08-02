<?php
  /** 
   * @var mixed $stats 
   * @var mixed $card 
   * @var mixed $user
   * @var mixed $uri
   */
  $summary            = $stats["summary"] ?? ['total_views' => 0, 'unique_views' => 0, 'total_clicks' => 0, 'ctr' => 0];
  $allTimeSummary     = $stats["allTimeSummary"] ?? ['total_views' => 0, 'total_clicks' => 0];
  $viewsByDayOfMonth  = $stats["viewsByDayOfMonth"] ?? [];
  $viewsByWeekOfMonth = $stats["viewsByWeekOfMonth"] ?? [];
  $topLinks           = $stats["topLinks"] ?? [];
  $devices            = $stats["devices"] ?? [];
  $browsers           = $stats["browsers"] ?? [];
  $countries          = $stats["countries"] ?? [];
  $referrers          = $stats["referrers"] ?? [];
  $topDays            = $stats["topDays"] ?? [];
  $topHours           = $stats["topHours"] ?? [];
  $recommendation     = $stats["recommendation"] ?? ['bestDay' => 'Lunes', 'bestHour' => '18:00 - 19:00', 'bestDayTotal' => 0, 'bestHourTotal' => 0];
  $socialStats        = $stats["socialStats"] ?? [];
  $recentViews        = $stats["recentViews"] ?? [];
  $userProfile        = !empty($user) ? $user : ($card["profile"] ?? "user");

  // Si no hay días u horas calculadas, habilitar datos de muestra (Sample Mode)
  $isSample = empty($topDays) && empty($topHours);

  if ($isSample) {
    $summary = ['total_views' => 29, 'unique_views' => 29, 'total_clicks' => 29, 'ctr' => 100.0];
    $allTimeSummary = ['total_views' => 480, 'total_clicks' => 310];

    $viewsByDayOfMonth = [
      ['date_str' => date('Y-m-01'), 'day_num' => '01', 'total' => 4, 'total_views' => 4, 'unique_views' => 4, 'total_clicks' => 3],
      ['date_str' => date('Y-m-05'), 'day_num' => '05', 'total' => 8, 'total_views' => 8, 'unique_views' => 8, 'total_clicks' => 6],
      ['date_str' => date('Y-m-10'), 'day_num' => '10', 'total' => 12, 'total_views' => 12, 'unique_views' => 12, 'total_clicks' => 9],
      ['date_str' => date('Y-m-15'), 'day_num' => '15', 'total' => 5, 'total_views' => 5, 'unique_views' => 5, 'total_clicks' => 4]
    ];

    $viewsByWeekOfMonth = [
      ['week_label' => 'Semana 1', 'total' => 35, 'unique_total' => 30],
      ['week_label' => 'Semana 2', 'total' => 58, 'unique_total' => 50],
      ['week_label' => 'Semana 3', 'total' => 72, 'unique_total' => 65],
      ['week_label' => 'Semana 4', 'total' => 45, 'unique_total' => 40]
    ];

    if (empty($topLinks)) {
      $topLinks = [
        ['link_id' => 'whatsapp',  'link_name' => 'WhatsApp Contacto', 'total' => 45],
        ['link_id' => 'instagram', 'link_name' => 'Instagram Perfil',  'total' => 30],
        ['link_id' => 'tienda',    'link_name' => 'Tienda Online',     'total' => 25]
      ];
    }

    $topDays = [
      ["day_num" => "1", "day_name" => "Lunes",     "total" => 48],
      ["day_num" => "3", "day_name" => "Miércoles", "total" => 36],
      ["day_num" => "5", "day_name" => "Viernes",   "total" => 29],
      ["day_num" => "6", "day_name" => "Sábado",    "total" => 22],
      ["day_num" => "0", "day_name" => "Domingo",   "total" => 15]
    ];

    $topHours = [
      ["hour_num" => "18", "label" => "18:00 - 19:00", "total" => 42],
      ["hour_num" => "19", "label" => "19:00 - 20:00", "total" => 35],
      ["hour_num" => "12", "label" => "12:00 - 13:00", "total" => 28],
      ["hour_num" => "20", "label" => "20:00 - 21:00", "total" => 24],
      ["hour_num" => "14", "label" => "14:00 - 15:00", "total" => 21]
    ];

    $recommendation = [
      "bestDay"       => "Lunes",
      "bestHour"      => "18:00 - 19:00",
      "bestDayTotal"  => 48,
      "bestHourTotal" => 42
    ];

    if (empty($devices)) {
      $devices = [
        ['device_type' => 'mobile',  'total' => 142],
        ['device_type' => 'desktop', 'total' => 88]
      ];
    }
    if (empty($browsers)) {
      $browsers = [
        ['browser' => 'Chrome',        'total' => 95],
        ['browser' => 'Instagram App', 'total' => 64],
        ['browser' => 'Safari',        'total' => 45],
        ['browser' => 'TikTok App',    'total' => 26]
      ];
    }
    if (empty($countries)) {
      $countries = [
        ['country_name' => 'Chile',  'country_code' => 'CL', 'total' => 173],
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

  $totalRrssClicks    = $stats["totalRrssClicks"] ?? 0;
  $rrssLinks          = $stats["rrssLinks"] ?? [];

  if ($isSample && empty($rrssLinks)) {
    $totalRrssClicks = 28;
    $rrssLinks = [
      ['link_id' => 'github',   'link_name' => 'GitHub (Red Social)',   'total' => 15],
      ['link_id' => 'linkedin', 'link_name' => 'LinkedIn (Red Social)', 'total' => 8],
      ['link_id' => 'x',        'link_name' => 'X / Twitter (Red)',     'total' => 5]
    ];
  }

  $data = [
    'isSample'           => $isSample,
    'userProfile'        => $userProfile,
    'summary'            => $summary,
    'allTimeSummary'     => $allTimeSummary,
    'viewsByDayOfMonth'  => $viewsByDayOfMonth,
    'viewsByWeekOfMonth' => $viewsByWeekOfMonth,
    'topLinks'           => $topLinks,
    'totalRrssClicks'    => $totalRrssClicks,
    'rrssLinks'          => $rrssLinks,
    'recommendation'     => $recommendation,
    'socialStats'        => $socialStats,
    'topDays'            => $topDays,
    'topHours'           => $topHours,
    'devices'            => $devices,
    'browsers'           => $browsers,
    'countries'          => $countries,
    'referrers'          => $referrers,
    'recentViews'        => $recentViews
  ];
?>

<div class="flex-column gap20 w100 textb">

  <!-- 1. Encabezado de la Vista -->
  <?php _part("Dashboard.statisticsHeader", $data); ?>

  <!-- 2. Recuento: Métricas del Mes Actual con Modal de Desglose Integrado -->
  <?php _part("Dashboard.generalSummaryCard", $data); ?>

  <!-- 3. Card Destacada de Recomendación de Horario para Publicar -->
  <?php _part("Dashboard.scheduleRecommendationCard", $data); ?>

  <!-- 4. Rendimiento y Horarios por Red Social (Calculado por Referrer) -->
  <?php _part("Dashboard.socialNetworksCard", $data); ?>

  <!-- 5. Días Más Visitados y Horarios Más Concurridos -->
  <?php _part("Dashboard.peakTrafficCard", $data); ?>

  <!-- 6. Desglose por Dispositivos y Navegadores (In-App) -->
  <?php _part("Dashboard.devicesBrowsersCard", $data); ?>

  <!-- 7. Ubicaciones Principales (Países) y Fuentes de Tráfico (Referrers) -->
  <?php _part("Dashboard.locationsSourcesCard", $data); ?>

</div>
