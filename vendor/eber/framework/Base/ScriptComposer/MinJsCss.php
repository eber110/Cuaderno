<?php

if (php_sapi_name() !== 'cli') {
  http_response_code(403);
  exit("Acceso denegado: este script solo puede ejecutarse por CLI.\n");
}

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
use Base\Module\ThemeModule;

$composerJsonPath = ROOT_PATH . '/composer.json';
$isFrameworkRepo = false;
if (file_exists($composerJsonPath)) {
    $composerData = json_decode((string)file_get_contents($composerJsonPath), true);
    if (($composerData['name'] ?? '') === 'eber/framework') {
        $isFrameworkRepo = true;
    }
}

if ($isFrameworkRepo && !is_dir(ROOT_PATH . '/App')) {
    echo "ℹ️  Repositorio del framework ('eber/framework') detectado sin carpeta /App.\n";
    echo "   'frame min' compila los assets de proyectos derivados con estructura /App.\n";
    exit(0);
}

$cssPaths = [];

// Compilación automática de theme.json si existe y está modificado o si _configTheme.css no existe
if (class_exists('\\Base\\Module\\ThemeModule')) {
    $themeBuild = ThemeModule::build(ROOT_PATH);
    if (!empty($themeBuild['updated'])) {
        echo "🎨 Tema sincronizado: App/Public/Css/_configTheme.css actualizado desde theme.json\n";
    }
}

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

// Sincronizar automáticamente librería Charts en App/Rsc/Library/Charts desde Resources/Library/Charts
$appChartsDir = ROOT_PATH . '/App/Rsc/Library/Charts';
$srcChartsDir = is_dir(ROOT_PATH . '/vendor/eber/framework/Resources/Library/Charts')
    ? ROOT_PATH . '/vendor/eber/framework/Resources/Library/Charts'
    : ROOT_PATH . '/Resources/Library/Charts';
if (is_dir($appChartsDir) && is_dir($srcChartsDir)) {
    $chartFiles = scandir($srcChartsDir) ?: [];
    $syncedCharts = 0;
    foreach ($chartFiles as $chartFile) {
        if ($chartFile === '.' || $chartFile === '..') {
            continue;
        }
        $srcFile = $srcChartsDir . '/' . $chartFile;
        $destFile = $appChartsDir . '/' . $chartFile;
        if (is_file($srcFile) && (!is_file($destFile) || md5_file($srcFile) !== md5_file($destFile))) {
            if (@copy($srcFile, $destFile)) {
                $syncedCharts++;
            }
        }
    }
    if ($syncedCharts > 0) {
        echo "📊 Charts sincronizado: {$syncedCharts} archivo(s) actualizado(s) en App/Rsc/Library/Charts\n";
    }
}

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