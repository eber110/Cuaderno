<?php

require 'vendor/autoload.php';

use Base\Module\MinifyModule;
use Base\Module\JitCssModule;

$cssPaths = [];

// Resolver rutas de resources del framework dinámicamente primero (para que se carguen al inicio y el proyecto pueda sobrescribirlos)
if (is_dir(ROOT_PATH . '/vendor/eber/framework/Resources/Css')) {
    $cssPaths[] = ROOT_PATH . '/vendor/eber/framework/Resources/Css';
} elseif (is_dir(ROOT_PATH . '/Resources/Css')) {
    $cssPaths[] = ROOT_PATH . '/Resources/Css';
}

$cssPaths[] = ROOT_PATH . '/App/Public/Css/';

$jsPaths = [ROOT_PATH . '/App/Public/Js/'];

// Resolver rutas de resources del framework dinámicamente
if (is_dir(ROOT_PATH . '/vendor/eber/framework/Resources/Js')) {
    $jsPaths[] = ROOT_PATH . '/vendor/eber/framework/Resources/Js';
} elseif (is_dir(ROOT_PATH . '/Resources/Js')) {
    $jsPaths[] = ROOT_PATH . '/Resources/Js';
}

$purgePaths = [
    ROOT_PATH . '/App',
    ROOT_PATH . '/Base',
    ROOT_PATH . '/Core',
    ROOT_PATH . '/Resources',
];

if (is_dir(ROOT_PATH . '/vendor/eber/framework/App')) {
    $purgePaths[] = ROOT_PATH . '/vendor/eber/framework/App';
}
if (is_dir(ROOT_PATH . '/vendor/eber/framework/Base')) {
    $purgePaths[] = ROOT_PATH . '/vendor/eber/framework/Base';
}
if (is_dir(ROOT_PATH . '/vendor/eber/framework/Core')) {
    $purgePaths[] = ROOT_PATH . '/vendor/eber/framework/Core';
}
if (is_dir(ROOT_PATH . '/vendor/eber/framework/Resources')) {
    $purgePaths[] = ROOT_PATH . '/vendor/eber/framework/Resources';
}

$jitOutPath = ROOT_PATH . '/App/Public/Css/jit-compiled.css';
JitCssModule::generateJitCss($purgePaths, $jitOutPath);
$cssPaths[] = $jitOutPath;

// Flujo original de minificación
MinifyModule::minifyCss(
    $cssPaths,
    ROOT_PATH . '/App/Public/Min/Css/css.min.css'
);

MinifyModule::minifyJs(
    $jsPaths,
    ROOT_PATH . '/App/Public/Min/Js/js.min.js'
);

echo "✅ Minificación completada\n";