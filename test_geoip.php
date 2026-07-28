<?php
require 'vendor/autoload.php';
use Base\Module\GeoIpModule;
echo GeoIpModule::getCountryName('8.8.8.8') . "\n";
echo GeoIpModule::getCountryCode('8.8.8.8') . "\n";
echo GeoIpModule::getCityName('200.74.88.0') . "\n"; // Un IP de Chile
