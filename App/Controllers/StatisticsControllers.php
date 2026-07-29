<?php

namespace App\Controllers;

use Base\Control\Control;
use Base\Module\AnalyticsModule;
use Base\Module\InteractionModule;
use Base\Module\MovilDetectorModule;
use Base\Module\ResponseModule;

class StatisticsControllers extends Control {

  /**
   * Obtiene la estructura completa de analíticas y estadísticas para un usuario desde SQLite.
   */
  public static function getStatsData(string $user): array {
    $user = mb_strtolower($user, 'UTF-8');
    
    // Resumen general (Totales, Únicas, Clics, CTR)
    $summary = AnalyticsModule::getProfileSummary($user);
    
    $pdo = AnalyticsModule::getPdo();

    // 1. Desglose por tipo de dispositivo (mobile, tablet, desktop)
    $stmtDevices = $pdo->prepare("SELECT device_type, COUNT(*) as total FROM profile_views WHERE profile_id = :user GROUP BY device_type ORDER BY total DESC");
    $stmtDevices->execute([':user' => $user]);
    $devices = $stmtDevices->fetchAll() ?: [];

    // 2. Desglose por navegador (incluyendo In-App Browsers)
    $stmtBrowsers = $pdo->prepare("SELECT browser, COUNT(*) as total FROM profile_views WHERE profile_id = :user GROUP BY browser ORDER BY total DESC LIMIT 5");
    $stmtBrowsers->execute([':user' => $user]);
    $browsers = $stmtBrowsers->fetchAll() ?: [];

    // 3. Desglose por país
    $stmtCountries = $pdo->prepare("SELECT country_name, country_code, COUNT(*) as total FROM profile_views WHERE profile_id = :user GROUP BY country_name ORDER BY total DESC LIMIT 5");
    $stmtCountries->execute([':user' => $user]);
    $countries = $stmtCountries->fetchAll() ?: [];

    // 4. Desglose por fuente de tráfico (Referrers)
    $stmtReferrers = $pdo->prepare("SELECT referrer, COUNT(*) as total FROM profile_views WHERE profile_id = :user GROUP BY referrer ORDER BY total DESC LIMIT 5");
    $stmtReferrers->execute([':user' => $user]);
    $referrers = $stmtReferrers->fetchAll() ?: [];

    // 5. Clics por enlace
    $stmtClicks = $pdo->prepare("SELECT link_id, COUNT(*) as total FROM link_clicks WHERE profile_id = :user GROUP BY link_id ORDER BY total DESC LIMIT 10");
    $stmtClicks->execute([':user' => $user]);
    $clicksByLink = $stmtClicks->fetchAll() ?: [];

    // 6. Últimos registros recientes para depuración
    $stmtRecentViews = $pdo->prepare("SELECT ip_address, country_name, device_type, os, browser, referrer, created_at FROM profile_views WHERE profile_id = :user ORDER BY id DESC LIMIT 5");
    $stmtRecentViews->execute([':user' => $user]);
    $recentViews = $stmtRecentViews->fetchAll() ?: [];

    return [
      "summary"      => $summary,
      "devices"      => $devices,
      "browsers"     => $browsers,
      "countries"    => $countries,
      "referrers"    => $referrers,
      "clicksByLink" => $clicksByLink,
      "recentViews"  => $recentViews
    ];
  }

  /**
   * Endpoint de depuración para simular e generar registros de prueba en SQLite.
   */
  public function generateTestData(string $user) {
    $user = mb_strtolower($user, 'UTF-8');
    
    $samples = [
      ['device' => 'mobile',  'os' => 'iOS',     'browser' => 'Instagram App', 'country' => 'Chile',  'code' => 'CL', 'city' => 'Santiago',        'ref' => 'https://instagram.com'],
      ['device' => 'mobile',  'os' => 'Android', 'browser' => 'TikTok App',    'country' => 'Chile',  'code' => 'CL', 'city' => 'Valparaíso',      'ref' => 'https://tiktok.com'],
      ['device' => 'desktop', 'os' => 'Windows', 'browser' => 'Chrome',        'country' => 'México', 'code' => 'MX', 'city' => 'Ciudad de México', 'ref' => 'https://google.com'],
      ['device' => 'desktop', 'os' => 'macOS',   'browser' => 'Safari',        'country' => 'España', 'code' => 'ES', 'city' => 'Madrid',           'ref' => '']
    ];

    $sample = $samples[array_rand($samples)];

    // Insertar visita de prueba
    AnalyticsModule::logProfileView($user, [
      'ip_address'   => rand(170, 200) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255),
      'country_code' => $sample['code'],
      'country_name' => $sample['country'],
      'city_name'    => $sample['city'],
      'device_type'  => $sample['device'],
      'os'           => $sample['os'],
      'browser'      => $sample['browser'],
      'referrer'     => $sample['ref']
    ]);

    // Insertar clic de prueba
    AnalyticsModule::logLinkClick($user, 'link_' . rand(1, 4), [
      'country_code' => $sample['code'],
      'device_type'  => $sample['device']
    ]);

    ResponseModule::redirect("/panel/{$user}");
  }

}