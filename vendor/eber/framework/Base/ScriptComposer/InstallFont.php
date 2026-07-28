<?php

/**
 * Script interactivo para instalar fuentes en el proyecto.
 * Copia el archivo físico y genera la configuración CSS.
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la interfaz de comandos (CLI).\n");
}

// Intentar cargar la ruta base del proyecto
$basePath = getcwd();

// Obtener el recurso de entrada correcto (evadiendo redirecciones en composer run-script)
$stdin = STDIN;
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $conin = @fopen('\\\\.\\CONIN$', 'r');
    if ($conin) {
        $stdin = $conin;
    }
} else {
    if (file_exists('/dev/tty') && is_readable('/dev/tty')) {
        $tty = @fopen('/dev/tty', 'r');
        if ($tty) {
            $stdin = $tty;
        }
    }
}

echo "==================================================\n";
echo "🔤 INSTALADOR DE FUENTES PERSONALIZADAS DEL FRAMEWORK\n";
echo "==================================================\n\n";

// 1. Solicitar ruta del archivo de fuente
echo "1. Introduce la ruta absoluta o relativa de la fuente (.woff2, .woff, .ttf, .otf):\n> ";
$sourcePath = trim(fgets($stdin));

if (!file_exists($sourcePath)) {
    die("❌ Error: El archivo especificado no existe en la ruta: '{$sourcePath}'\n");
}

$ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
$validExtensions = ['woff2', 'woff', 'ttf', 'otf'];
if (!in_array($ext, $validExtensions)) {
    die("❌ Error: Extensión no soportada. Debe ser uno de: " . implode(', ', $validExtensions) . "\n");
}

// 2. Solicitar el peso de la fuente
echo "\n2. Introduce el peso de la fuente (ej: 400 para Regular, 700 para Bold, o presiona Enter para usar 400):\n> ";
$fontWeightInput = trim(fgets($stdin));
$fontWeight = empty($fontWeightInput) ? 400 : (int)$fontWeightInput;

// 3. Solicitar nombre de la clase CSS
$fileName = pathinfo($sourcePath, PATHINFO_FILENAME);
// Limpiar el nombre del archivo para sugerir una clase limpia (ej: OpenSans-Regular -> opensans)
$suggestedClass = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('-', $fileName)[0]));
echo "\n3. Introduce el nombre de la clase CSS para invocar esta fuente (ej: font-{$suggestedClass}, o presiona Enter para usar 'font-{$suggestedClass}'):\n> ";
$fontClassInput = trim(fgets($stdin));
$fontClass = empty($fontClassInput) ? "font-{$suggestedClass}" : trim($fontClassInput);
// Asegurar que comience con un punto si el usuario no lo puso
$fontClassSelector = $fontClass;
if (strpos($fontClassSelector, '.') !== 0) {
    $fontClassSelector = '.' . $fontClassSelector;
}

// 4. Copiar la fuente física
$destFontsDir = $basePath . '/App/Rsc/Fonts';
if (!is_dir($destFontsDir) && is_dir($basePath . '/App/Rsc/Font')) {
    $destFontsDir = $basePath . '/App/Rsc/Font';
}
if (!is_dir($destFontsDir)) {
    mkdir($destFontsDir, 0755, true);
}

$fontsFolderBasename = basename($destFontsDir);

// Crear una subcarpeta organizada para la fuente basada en el nombre sugerido
$fontFamily = str_replace('-', ' ', explode('-', $fileName)[0]);
$fontFamilyClean = ucwords($fontFamily);
$subDirName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fontFamily));
$targetFolder = $destFontsDir . '/' . $subDirName;

if (!is_dir($targetFolder)) {
    mkdir($targetFolder, 0755, true);
}

$targetFileName = basename($sourcePath);
$destPath = $targetFolder . '/' . $targetFileName;

if (@copy($sourcePath, $destPath)) {
    echo "✅ Archivo de fuente copiado a: App/Rsc/{$fontsFolderBasename}/{$subDirName}/{$targetFileName}\n";
} else {
    die("❌ Error: No se pudo copiar el archivo de fuente.\n");
}

// NUEVO: Intentar comprimir a WOFF2 usando el nuevo módulo y procesar las antiguas
require_once __DIR__ . '/../Exec/FontCompressor.php';
\Base\Exec\FontCompressor::processExistingFonts($destFontsDir);

// Comprobar si la fuente recién subida se convirtió a WOFF2
$woff2DestPath = preg_replace('/\.(ttf|otf)$/i', '.woff2', $destPath);
if (file_exists($woff2DestPath)) {
    $targetFileName = basename($woff2DestPath);
    $ext = 'woff2';
}

// 5. Crear o actualizar font-project.css en App/Public/Css
$destCssDir = $basePath . '/App/Public/Css';
if (!is_dir($destCssDir)) {
    mkdir($destCssDir, 0755, true);
}

$cssFilePath = $destCssDir . '/font-project.css';

// Obtener la ruta relativa de la fuente para el CSS
$cssFontUrl = "/App/Rsc/{$fontsFolderBasename}/{$subDirName}/{$targetFileName}";

$format = $ext;
if ($ext === 'ttf') $format = 'truetype';
if ($ext === 'otf') $format = 'opentype';
if ($ext === 'woff2') $format = 'woff2';
if ($ext === 'woff') $format = 'woff';

$cssContent = "\n/* ==========================================================================\n";
$cssContent .= "   🔤 FUENTE: {$fontFamilyClean} (Peso: {$fontWeight})\n";
$cssContent .= "   ========================================================================== */\n\n";
$cssContent .= "@font-face {\n";
$cssContent .= "  font-family: '{$fontFamilyClean}';\n";
$cssContent .= "  src: url('{$cssFontUrl}') format('{$format}');\n";
$cssContent .= "  font-weight: {$fontWeight};\n";
$cssContent .= "  font-style: normal;\n";
$cssContent .= "  font-display: swap;\n";
$cssContent .= "}\n\n";
$cssContent .= "{$fontClassSelector} {\n";
$cssContent .= "  font-family: '{$fontFamilyClean}' !important;\n";
$cssContent .= "  font-weight: {$fontWeight} !important;\n";
$cssContent .= "}\n";

if (file_put_contents($cssFilePath, $cssContent, FILE_APPEND)) {
    echo "✅ Estilos agregados en: App/Public/Css/font-project.css\n";
} else {
    echo "⚠️ Advertencia: No se pudo actualizar el archivo font-project.css\n";
}

// 6. Invocar al script composer min-script para compilar todo
echo "\n📦 Compilando recursos CSS y JS con min-script...\n";

// Ejecutar min-script usando composer
$minScriptCommand = "composer run-script min-script";
exec($minScriptCommand, $output, $returnCode);

if ($returnCode === 0) {
    echo implode("\n", $output) . "\n";
    echo "✅ ¡Recursos compilados y fuente completamente lista para usarse!\n";
} else {
    // Si no tiene composer global o falla, intentar ejecutar la ruta directa
    echo "⚠️  Intento con composer falló. Ejecutando MinJsCss.php de forma directa...\n";
    $directCommand = "php Base/ScriptComposer/MinJsCss.php";
    if (file_exists("vendor/eber/framework/Base/ScriptComposer/MinJsCss.php")) {
        $directCommand = "php vendor/eber/framework/Base/ScriptComposer/MinJsCss.php";
    }
    exec($directCommand, $outputDirect, $returnCodeDirect);
    if ($returnCodeDirect === 0) {
        echo implode("\n", $outputDirect) . "\n";
        echo "✅ ¡Recursos compilados de forma directa!\n";
    } else {
        echo "❌ Error al compilar los recursos. Por favor ejecuta 'composer run-script min-script' de forma manual.\n";
    }
}

echo "\n✨ ¡Instalación finalizada! Ahora puedes usar la clase '{$fontClass}' en tus selectores HTML/CSS.\n";
