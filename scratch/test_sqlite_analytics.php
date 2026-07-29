<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Base\Module\AnalyticsModule;
use Base\Module\MovilDetectorModule;

define('ROOT_PATH', dirname(__DIR__));

echo "Testing AnalyticsModule SQLite initialization...\n";

// Set User-Agent simulating Instagram App on iPhone
MovilDetectorModule::setUserAgent("Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Instagram 289.0.0.25.109");

$deviceType = MovilDetectorModule::getDeviceType();
$os         = MovilDetectorModule::getOS();
$browser    = MovilDetectorModule::getBrowser();

echo "Device Type: {$deviceType}\n";
echo "OS: {$os}\n";
echo "Browser: {$browser}\n";

$insertedView = AnalyticsModule::logProfileView('eber', [
    'ip_address'   => '179.60.66.196',
    'country_code' => 'CL',
    'country_name' => 'Chile',
    'city_name'    => 'Santiago',
    'device_type'  => $deviceType,
    'os'           => $os,
    'browser'      => $browser,
    'referrer'     => 'https://instagram.com'
]);

echo "Inserted Profile View: " . ($insertedView ? "SUCCESS" : "FAILED") . "\n";

$insertedClick = AnalyticsModule::logLinkClick('eber', 'link_1', [
    'country_code' => 'CL',
    'device_type'  => $deviceType
]);

echo "Inserted Link Click: " . ($insertedClick ? "SUCCESS" : "FAILED") . "\n";

$summary = AnalyticsModule::getProfileSummary('eber');
echo "Summary: " . json_encode($summary) . "\n";
