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

// Cargar CSS con prioridad alta
$load_style->ruteCss(ROOT_PATH . '/App/Public/Min/Css/', 'fetchpriority="high"');

// Cargar el loader unificado con defer (ejecuta funciones después de que el DOM esté listo)
$load_style->ruteStyle(ROOT_PATH . '/vendor/eber/framework/Resources/JsLoad/', 'type="module" defer');
