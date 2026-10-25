<?php

if (file_exists(getcwd() . '/vendor/autoload.php')) {
  require_once getcwd() . '/vendor/autoload.php';
} else {
  require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
}

if (!defined('ROOT_PATH')) {
  define('ROOT_PATH', str_replace('\\', '/', getcwd()));
}

use Base\Module\MinifyModule;
use Base\Module\JitCssModule;

$cssPaths = [];

// Asegurar que App/Public/Css/_configTheme.css exista (copiándolo desde Resources/Public/Css si aún no fue inicializado)
$appCssDir = ROOT_PATH . '/App/Public/Css';
if (!is_dir($appCssDir)) {
    @mkdir($appCssDir, 0755, true);
}
$projectThemeConfig = $appCssDir . '/_configTheme.css';
if (!file_exists($projectThemeConfig)) {
    $frameworkThemeConfig = is_file(ROOT_PATH . '/vendor/eber/framework/Resources/Public/Css/_configTheme.css')
        ? ROOT_PATH . '/vendor/eber/framework/Resources/Public/Css/_configTheme.css'
        : ROOT_PATH . '/Resources/Public/Css/_configTheme.css';
    if (is_file($frameworkThemeConfig)) {
        @copy($frameworkThemeConfig, $projectThemeConfig);
    }
} else {
    JitCssModule::syncThemeConfigVariables($projectThemeConfig);
}

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
$words = JitCssModule::generateJitCss($purgePaths, $jitOutPath);
$cssPaths[] = $jitOutPath;

// Detección y configuración automática de precarga de fuentes activas (JIT Font Preload)
$fontCssPath = ROOT_PATH . '/App/Public/Css/font-project.css';
$preloadJsonPath = ROOT_PATH . '/App/Config/preloadFonts.json';
JitCssModule::generatePreloadFontsConfig($words, $fontCssPath, $preloadJsonPath);

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