<?php

use Core\ConfigLoader\LoadViewStyle;

$load_style = new LoadViewStyle();

// Incrustar configuración JS unificada
$load_style->inlineJsConfig('jsConfig.json');

// Precargar fuentes críticas (solo Regular weight para inicio rápido)
$load_style->ruteFont();

// Carga automática de librerías configuradas en App/Config/loadLibraryJsConfiguration.php
$load_style->loadLibraries();

// Precargar el bundle principal de JavaScript modular en paralelo
$load_style->modulePreloadJs();

// Cargar CSS directamente en <style> (inline) o mediante <link> con prioridad alta según configuración
if (LoadViewStyle::isInlineCss()) {
  $load_style->inlineCss(ROOT_PATH . '/App/Public/Min/Css/');
} else {
  $load_style->ruteCss(ROOT_PATH . '/App/Public/Min/Css/', 'fetchpriority="high"');
}

// Cargar el loader unificado con defer desde App/Rsc/JsLoad/ (ruta pública fuera de vendor/)
// En el repositorio del framework ('eber/framework') se sirve directo desde Resources/JsLoad/ para no generar /App
$isFrameworkRepo = false;
$composerPath = ROOT_PATH . '/composer.json';
if (is_file($composerPath)) {
  $composerJson = json_decode((string)@file_get_contents($composerPath), true);
  if (($composerJson['name'] ?? '') === 'eber/framework') {
    $isFrameworkRepo = true;
  }
}

$frameworkJsLoadDir = ROOT_PATH . '/Resources/JsLoad/';
if ($isFrameworkRepo && is_file($frameworkJsLoadDir . 'loader.js')) {
  $load_style->ruteStyle($frameworkJsLoadDir, 'type="module" defer');
} else {
  $appJsLoadDir = ROOT_PATH . '/App/Rsc/JsLoad/';
  $appLoaderFile = $appJsLoadDir . 'loader.js';
  if (!is_file($appLoaderFile)) {
    $srcCandidates = [
      ROOT_PATH . '/vendor/eber/framework/Resources/JsLoad/loader.js',
      (defined('FRAMEWORK_PATH') ? rtrim(str_replace('\\', '/', FRAMEWORK_PATH), '/') : dirname(__DIR__, 2)) . '/Resources/JsLoad/loader.js',
    ];
    foreach ($srcCandidates as $srcLoader) {
      if (is_file($srcLoader)) {
        if (!is_dir($appJsLoadDir)) {
          @mkdir($appJsLoadDir, 0755, true);
        }
        @copy($srcLoader, $appLoaderFile);
        break;
      }
    }
  }
  $load_style->ruteStyle($appJsLoadDir, 'type="module" defer');
}
