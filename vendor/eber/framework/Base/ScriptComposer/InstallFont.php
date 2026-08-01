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
if (getenv('APP_TEST_ENV') !== '1') {
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

require_once __DIR__ . '/../Exec/FontCompressor.php';

// Detectar automáticamente si la fuente es Variable o Estática
$isVariable = \Base\Exec\FontCompressor::isVariableFont($sourcePath);

if ($isVariable) {
    echo "\n🎉 ¡TIPO DE FUENTE DETECTADA: FUENTE VARIABLE (Variable Font)!\n";
    echo "   ✓ Soporta automáticamente todos los pesos (100 al 900) en este único archivo.\n";
    $fontWeight = '100 900';
} else {
    $detectedWeight = \Base\Exec\FontCompressor::detectWeightFromFilename($sourcePath);
    echo "\n📄 TIPO DE FUENTE DETECTADA: FUENTE ESTÁTICA\n";
    echo "2. Introduce el peso de la fuente (detectado: {$detectedWeight}, presiona Enter para usarlo):\n> ";
    $fontWeightInput = trim(fgets($stdin));
    $fontWeight = empty($fontWeightInput) ? $detectedWeight : (int)$fontWeightInput;
}

// 3. Solicitar nombre de la familia tipográfica (font-family)
$fileName = pathinfo($sourcePath, PATHINFO_FILENAME);
// Extraer nombre de la familia sin alterar guiones bajos (ej: GoogleSansFlex_120pt-Bold -> GoogleSansFlex_120pt)
$familyParts = explode('-', $fileName);
$detectedFamily = $familyParts[0];
if (empty($detectedFamily)) {
    $detectedFamily = $fileName;
}

echo "\n3. Introduce el nombre de la familia tipográfica (font-family) (detectado: '{$detectedFamily}', presiona Enter para usarlo):\n> ";
$fontFamilyInput = trim(fgets($stdin));
$fontFamilyName = empty($fontFamilyInput) ? $detectedFamily : trim($fontFamilyInput);

// 4. Solicitar nombre de la clase CSS
// Sugerir clase CSS limpia (ej: GoogleSansFlex_120pt -> googlesansflex120pt)
$suggestedClass = lcfirst(preg_replace('/[^a-zA-Z0-9]/', '', $fontFamilyName));
if (empty($suggestedClass)) {
    $suggestedClass = 'customFont';
}

echo "\n4. Introduce el nombre base de la clase CSS para invocar esta fuente (ej: {$suggestedClass}, o presiona Enter para usar '{$suggestedClass}'):\n> ";
$fontClassInput = trim(fgets($stdin));
$fontClass = empty($fontClassInput) ? $suggestedClass : trim($fontClassInput);
// Quitar punto inicial si lo introdujo
$fontClass = ltrim($fontClass, '.');

// 5. Copiar la fuente física
$destFontsDir = $basePath . '/App/Rsc/Fonts';
if (!is_dir($destFontsDir) && is_dir($basePath . '/App/Rsc/Font')) {
    $destFontsDir = $basePath . '/App/Rsc/Font';
}
if (!is_dir($destFontsDir)) {
    mkdir($destFontsDir, 0755, true);
}

$fontsFolderBasename = basename($destFontsDir);

// Crear una subcarpeta organizada para la fuente basada en el nombre de la familia
$subDirName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $fontFamilyName));
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

// 5.1 Comprimir a WOFF2 automáticamente si la fuente es TTF u OTF
if ($ext === 'ttf' || $ext === 'otf') {
    if (\Base\Exec\FontCompressor::convertToWoff2($destPath, preg_replace('/\.(ttf|otf)$/i', '.woff2', $destPath))) {
        $woff2DestPath = preg_replace('/\.(ttf|otf)$/i', '.woff2', $destPath);
        if (file_exists($woff2DestPath) && filesize($woff2DestPath) > 0) {
            $targetFileName = basename($woff2DestPath);
            $ext = 'woff2';
        }
    }
}

// 6. Crear o actualizar font-project.css en App/Public/Css
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

$currentCss = file_exists($cssFilePath) ? file_get_contents($cssFilePath) : '';
$baseClassExists = (strpos($currentCss, ".{$fontClass} {") !== false || strpos($currentCss, ".{$fontClass}{") !== false);

if ($isVariable) {
    $cssContent = "\n/* ==========================================================================\n";
    $cssContent .= "   🔤 FUENTE VARIABLE: {$fontFamilyName} (Pesos: 100 - 900)\n";
    $cssContent .= "   ========================================================================== */\n\n";
    $cssContent .= "@font-face {\n";
    $cssContent .= "  font-family: '{$fontFamilyName}';\n";
    $cssContent .= "  src: url('{$cssFontUrl}') format('{$format}');\n";
    $cssContent .= "  font-weight: 100 900;\n";
    $cssContent .= "  font-style: normal;\n";
    $cssContent .= "  font-display: swap;\n";
    $cssContent .= "}\n\n";
    if (!$baseClassExists) {
        $cssContent .= ".{$fontClass} {\n";
        $cssContent .= "  font-family: '{$fontFamilyName}' !important;\n";
        $cssContent .= "}\n\n";
    }
    $cssContent .= ".{$fontClass}100 { font-family: '{$fontFamilyName}' !important; font-weight: 100 !important; }\n";
    $cssContent .= ".{$fontClass}200 { font-family: '{$fontFamilyName}' !important; font-weight: 200 !important; }\n";
    $cssContent .= ".{$fontClass}300 { font-family: '{$fontFamilyName}' !important; font-weight: 300 !important; }\n";
    $cssContent .= ".{$fontClass}400 { font-family: '{$fontFamilyName}' !important; font-weight: 400 !important; }\n";
    $cssContent .= ".{$fontClass}500 { font-family: '{$fontFamilyName}' !important; font-weight: 500 !important; }\n";
    $cssContent .= ".{$fontClass}600 { font-family: '{$fontFamilyName}' !important; font-weight: 600 !important; }\n";
    $cssContent .= ".{$fontClass}700 { font-family: '{$fontFamilyName}' !important; font-weight: 700 !important; }\n";
    $cssContent .= ".{$fontClass}800 { font-family: '{$fontFamilyName}' !important; font-weight: 800 !important; }\n";
    $cssContent .= ".{$fontClass}900 { font-family: '{$fontFamilyName}' !important; font-weight: 900 !important; }\n";
} else {
    $cssContent = "\n/* ==========================================================================\n";
    $cssContent .= "   🔤 FUENTE ESTÁTICA: {$fontFamilyName} (Peso: {$fontWeight})\n";
    $cssContent .= "   ========================================================================== */\n\n";
    $cssContent .= "@font-face {\n";
    $cssContent .= "  font-family: '{$fontFamilyName}';\n";
    $cssContent .= "  src: url('{$cssFontUrl}') format('{$format}');\n";
    $cssContent .= "  font-weight: {$fontWeight};\n";
    $cssContent .= "  font-style: normal;\n";
    $cssContent .= "  font-display: swap;\n";
    $cssContent .= "}\n\n";
    if (!$baseClassExists) {
        $cssContent .= ".{$fontClass} {\n";
        $cssContent .= "  font-family: '{$fontFamilyName}' !important;\n";
        $cssContent .= "}\n\n";
    }
    $cssContent .= ".{$fontClass}{$fontWeight} {\n";
    $cssContent .= "  font-family: '{$fontFamilyName}' !important;\n";
    $cssContent .= "  font-weight: {$fontWeight} !important;\n";
    $cssContent .= "}\n";
}

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
