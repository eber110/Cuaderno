<?php
require 'vendor/autoload.php';
use Base\Module\MinifyModule;
$css = ".hem5 { min-height: 5em; } \n .hem-5 { min-height: 5em; }";
echo MinifyModule::minifyCssFromContent($css);
