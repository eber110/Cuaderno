<?php
require 'vendor/autoload.php';
use Base\Module\CssPurgerModule;
use Base\Module\JitCssModule;

file_put_contents('scratch/dummy.html', 'class="hem5 hem-5 hem20 hem-20"');
JitCssModule::generateJitCss(['scratch'], 'scratch/dummy.css', true);

// Setup Purger
CssPurgerModule::setScanDirectories(['scratch']);
CssPurgerModule::setCssDirectories(['scratch']);
CssPurgerModule::purge('scratch/dummy_purged.css', true);

echo "\n--- PURGED CSS ---\n";
echo file_get_contents('scratch/dummy_purged.css');
