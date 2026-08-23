<?php

/**
 * Script interactivo para convertir imágenes entre formatos soportados
 * (GIF animado a WebP animado, PNG a WebP, JPG a AVIF, etc.).
 */

if (php_sapi_name() !== 'cli') {
  die("Este script solo puede ejecutarse desde la interfaz de comandos (CLI).\n");
}

// Buscar el autoloader de Composer en el proyecto o framework
if (file_exists(getcwd() . '/vendor/autoload.php')) {
  require_once getcwd() . '/vendor/autoload.php';
} else {
  require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
}

$basePath = str_replace('\\', '/', getcwd());
if (!defined('ROOT_PATH')) {
  define('ROOT_PATH', $basePath);
}

use Base\Module\ImgProcessModule;

// Obtener el recurso de entrada correcto (evadiendo redirecciones en composer run-script)
$stdin = STDIN;
if (getenv('APP_TEST_ENV') !== '1') {
  $isTty = function_exists('stream_isatty') ? stream_isatty(STDIN) : true;
  if ($isTty) {
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
}

echo "\n==================================================\n";
echo "🖼️  CONVERSOR DE IMÁGENES DEL FRAMEWORK\n";
echo "==================================================\n\n";

// Helper para limpiar rutas ingresadas por el usuario (comillas, espacios)
function cleanPath(string $path): string
{
  $path = trim($path);
  // Eliminar comillas simples o dobles iniciales/finales
  $path = trim($path, "\"'`");
  return str_replace('\\', '/', $path);
}

// Helper para formatear bytes a KB / MB
function formatBytes(int $bytes): string
{
  if ($bytes >= 1048576) {
    return number_format($bytes / 1048576, 2) . ' MB';
  } elseif ($bytes >= 1024) {
    return number_format($bytes / 1024, 2) . ' KB';
  }
  return $bytes . ' bytes';
}

// --------------------------------------------------------------------------
// 1. SOLICITAR RUTA DE LA IMAGEN DE ENTRADA
// --------------------------------------------------------------------------
$sourceInput = $argv[1] ?? '';

if (empty($sourceInput)) {
  echo "1. Introduce la ruta de la imagen de entrada (ej: App/Public/Img/animacion.gif o C:/ruta/foto.png):\n> ";
  $sourceInput = trim(fgets($stdin));
}

$sourcePath = cleanPath($sourceInput);

// Si es ruta relativa, resolver respecto a ROOT_PATH
if (!preg_match('#^([a-zA-Z]:|/)#', $sourcePath)) {
  $resolvedPath = ROOT_PATH . '/' . ltrim($sourcePath, '/');
  if (file_exists($resolvedPath)) {
    $sourcePath = $resolvedPath;
  }
}

if (empty($sourcePath) || !file_exists($sourcePath) || !is_file($sourcePath)) {
  die("\n❌ Error: El archivo de imagen especificado no existe: '{$sourceInput}'\n\n");
}

$sourceSize = filesize($sourcePath);
$sourceExt = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
$isGif = ($sourceExt === 'gif');
$isAnimated = $isGif && ImgProcessModule::isAnimatedGif($sourcePath);

echo "\n   ✓ Imagen encontrada: " . basename($sourcePath) . "\n";
echo "   ✓ Formato original: " . strtoupper($sourceExt) . ($isAnimated ? ' (GIF Animado detectado 🎬)' : '') . "\n";
echo "   ✓ Tamaño original: " . formatBytes($sourceSize) . "\n\n";

if ($isAnimated) {
  echo "   ✨ ¡GIF Animado detectado! Se generará un WebP Animado conservando todos los fotogramas, retardos y transparencias.\n\n";
}

// --------------------------------------------------------------------------
// 2. SOLICITAR FORMATO DE SALIDA (SOLO DISPONIBLES)
// --------------------------------------------------------------------------
$availableFormats = ImgProcessModule::getAvailableOutputFormats();

if (empty($availableFormats)) {
  die("❌ Error: No hay drivers gráficos compatibles disponibles (GD o Imagick).\n\n");
}

echo "2. Formatos de salida disponibles en tu entorno:\n";
$formatKeys = array_keys($availableFormats);
$index = 1;
$formatMap = [];

foreach ($availableFormats as $fmtKey => $fmtDesc) {
  $formatMap[$index] = $fmtKey;
  echo "   [{$index}] {$fmtKey} - {$fmtDesc}\n";
  $index++;
}

$outputFormatArg = $argv[2] ?? '';
$selectedFormat = '';

if (!empty($outputFormatArg)) {
  $cleanFmt = strtolower(trim($outputFormatArg));
  if (isset($availableFormats[$cleanFmt])) {
    $selectedFormat = $cleanFmt;
  }
}

if (empty($selectedFormat)) {
  echo "\nSelecciona el formato de salida [1-" . count($formatKeys) . "] o escribe el nombre (por defecto: webp):\n> ";
  $fmtInput = trim(fgets($stdin));
  $fmtInput = strtolower(trim($fmtInput, "\"'`"));

  if (empty($fmtInput)) {
    $selectedFormat = isset($availableFormats['webp']) ? 'webp' : $formatKeys[0];
  } elseif (is_numeric($fmtInput) && isset($formatMap[(int)$fmtInput])) {
    $selectedFormat = $formatMap[(int)$fmtInput];
  } elseif (isset($availableFormats[$fmtInput])) {
    $selectedFormat = $fmtInput;
  } elseif ($fmtInput === 'jpeg' && isset($availableFormats['jpg'])) {
    $selectedFormat = 'jpg';
  } else {
    echo "⚠️  Formato no válido o no disponible. Usando por defecto: webp\n";
    $selectedFormat = isset($availableFormats['webp']) ? 'webp' : $formatKeys[0];
  }
}

echo "   ✓ Formato seleccionado: " . strtoupper($selectedFormat) . "\n\n";

// --------------------------------------------------------------------------
// 3. SOLICITAR RUTA DE SALIDA (DEFAULT: MISMA RUTA DE ENTRADA)
// --------------------------------------------------------------------------
$sourceDir = pathinfo($sourcePath, PATHINFO_DIRNAME);
$sourceFilename = pathinfo($sourcePath, PATHINFO_FILENAME);

$destInput = $argv[3] ?? '';

if (empty($destInput)) {
  echo "3. Introduce la ruta de salida (o presiona Enter para usar la misma ruta de la imagen de entrada):\n> ";
  $destInput = trim(fgets($stdin));
}

$destInputClean = cleanPath($destInput);

if (empty($destInputClean)) {
  // Misma ruta y nombre pero con la nueva extensión
  $destPath = $sourceDir . '/' . $sourceFilename . '.' . $selectedFormat;
  
  // Si la ruta y extensión fuesen idénticas (ej: convertir webp a webp sin cambiar nombre), evitar colisión
  if (realpath($destPath) === realpath($sourcePath)) {
    $destPath = $sourceDir . '/' . $sourceFilename . '_converted.' . $selectedFormat;
  }
} elseif (is_dir($destInputClean) || str_ends_with($destInputClean, '/')) {
  // Es un directorio de destino
  $destPath = rtrim($destInputClean, '/') . '/' . $sourceFilename . '.' . $selectedFormat;
} else {
  // Es una ruta con nombre de archivo
  // Si no tiene extensión, agregarla
  $inputExt = strtolower(pathinfo($destInputClean, PATHINFO_EXTENSION));
  if (empty($inputExt)) {
    $destPath = $destInputClean . '.' . $selectedFormat;
  } else {
    $destPath = $destInputClean;
  }

  // Si es ruta relativa, resolver respecto a ROOT_PATH
  if (!preg_match('#^([a-zA-Z]:|/)#', $destPath)) {
    $destPath = ROOT_PATH . '/' . ltrim($destPath, '/');
  }
}

echo "   ✓ Ruta de salida: {$destPath}\n\n";

// --------------------------------------------------------------------------
// 4. OPCIONES DE OPTIMIZACIÓN (SI ES GIF ANIMADO)
// --------------------------------------------------------------------------
$frameStep = 1;
$quality = 80;

if ($isAnimated && $selectedFormat === 'webp') {
  echo "4. Opciones de optimización de fotogramas (FPS / Reducción de peso):\n";
  echo "   [1] 100% fotogramas (Máxima fluidez, mayor tamaño)\n";
  echo "   [2] 1 de cada 2 fotogramas (Recomendado: reduce ~50% de peso, excelente fluidez)\n";
  echo "   [3] 1 de cada 3 fotogramas (Ligero: reduce ~67% de peso)\n";
  echo "   [4] 1 de cada 4 fotogramas (Ultra liviano: reduce ~75% de peso)\n\n";

  $stepArg = $argv[4] ?? '';
  if (!empty($stepArg) && is_numeric($stepArg)) {
    $frameStep = max(1, (int)$stepArg);
  } else {
    echo "Selecciona el nivel de optimización [1-4] (por defecto: 2 - Recomendado):\n> ";
    $stepInput = trim(fgets($stdin));
    $stepInput = trim($stepInput, "\"'`");
    if (empty($stepInput)) {
      $frameStep = 2; // Por defecto fluido y optimizado
    } elseif (is_numeric($stepInput) && (int)$stepInput >= 1 && (int)$stepInput <= 4) {
      $frameStep = (int)$stepInput;
    } else {
      $frameStep = 2;
    }
  }

  echo "   ✓ Nivel de fotogramas: " . ($frameStep === 1 ? '100% de fotogramas' : "1 de cada {$frameStep} fotogramas (acumulando retardos para velocidad exacta)") . "\n\n";
}

// --------------------------------------------------------------------------
// 5. EJECUTAR CONVERSIÓN
// --------------------------------------------------------------------------
echo "⚙️  Convirtiendo imagen...\n";

$startTime = microtime(true);
$success = ImgProcessModule::convertImage($sourcePath, $destPath, $selectedFormat, $quality, null, null, $frameStep);
$duration = round((microtime(true) - $startTime) * 1000, 2);

if ($success && file_exists($destPath)) {
  $destSize = filesize($destPath);
  $diff = $sourceSize - $destSize;
  $percent = $sourceSize > 0 ? round(($diff / $sourceSize) * 100, 2) : 0;

  echo "\n==================================================\n";
  echo "✅ ¡CONVERSIÓN COMPLETADA CON ÉXITO!\n";
  echo "==================================================\n";
  echo "📁 Archivo origen:  {$sourcePath} (" . formatBytes($sourceSize) . ")\n";
  echo "🎯 Archivo destino: {$destPath} (" . formatBytes($destSize) . ")\n";
  if ($diff > 0) {
    echo "📉 Reducción:       -" . formatBytes($diff) . " ({$percent}% más liviano)\n";
  } elseif ($diff < 0) {
    echo "📈 Tamaño:          +" . formatBytes(abs($diff)) . " (" . abs($percent) . "%)\n";
  } else {
    echo "⚖️  Tamaño:          Sin variación significativa\n";
  }
  echo "⏱️  Tiempo:          {$duration} ms\n";
  echo "==================================================\n\n";
} else {
  echo "\n❌ Error: No se pudo completar la conversión de la imagen.\n\n";
  exit(1);
}
