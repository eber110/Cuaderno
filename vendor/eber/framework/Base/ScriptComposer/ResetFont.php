<?php

/**
 * Script interactivo para listar, desinstalar y resetear fuentes del proyecto.
 * Elimina los archivos físicos del disco y actualiza font-project.css y preloadFonts.json.
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la interfaz de comandos (CLI).\n");
}

// Ruta base del proyecto donde se ejecuta el comando
$basePath = getcwd();

// Obtener el recurso de entrada correcto para CLI interactivo
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
echo "🗑️  GESTOR Y RESETEADOR DE FUENTES DEL PROYECTO\n";
echo "==================================================\n\n";

$fontsDir = $basePath . '/App/Rsc/Fonts';
if (!is_dir($fontsDir) && is_dir($basePath . '/App/Rsc/Font')) {
    $fontsDir = $basePath . '/App/Rsc/Font';
}

$cssFilePath = $basePath . '/App/Public/Css/font-project.css';

// 1. Analizar fuentes instaladas desde font-project.css y disco
$installedEntries = [];

if (file_exists($cssFilePath)) {
    // Normalizar saltos de línea
    $cssContent = str_replace("\r\n", "\n", file_get_contents($cssFilePath));
    
    // Extraer bloques completos de fuentes (comentario opcional + @font-face + clases de utilidad)
    $pattern = '/(?:\/\*[\s\S]*?\*\/\s*)?@font-face\s*\{[\s\S]*?\}(?:\s*\.[a-zA-Z0-9_-]+\s*\{[\s\S]*?\})*/i';
    preg_match_all($pattern, $cssContent, $matches);
    $rawBlocks = $matches[0] ?? [];
    
    foreach ($rawBlocks as $block) {
        $blockTrim = trim($block);
        if (empty($blockTrim)) {
            continue;
        }
        
        // Extraer nombre de la familia
        $family = 'Desconocida';
        if (preg_match('/font-family:\s*[\'"]?([^\'";]+)[\'"]?/i', $blockTrim, $mFamily)) {
            $family = trim($mFamily[1]);
        }
        
        // Extraer peso
        $weight = '400';
        if (preg_match('/font-weight:\s*([^;]+);/i', $blockTrim, $mWeight)) {
            $weight = trim($mWeight[1]);
        }
        
        // Extraer URL / ruta del archivo
        $fileUrl = '';
        $diskPath = '';
        if (preg_match('/url\([\'"]?([^\'")]+)[\'"]?\)/i', $blockTrim, $mUrl)) {
            $fileUrl = trim($mUrl[1]);
            // Convertir URL a ruta en disco
            $relativeClean = ltrim($fileUrl, '/\\');
            $diskPath = $basePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativeClean);
        }
        
        $installedEntries[] = [
            'family'   => $family,
            'weight'   => $weight,
            'url'      => $fileUrl,
            'diskPath' => $diskPath,
            'rawBlock' => $block,
        ];
    }
}

// Si no hay entradas en el CSS pero hay archivos en la carpeta de fuentes, listarlos también
if (is_dir($fontsDir)) {
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fontsDir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['woff2', 'woff', 'ttf', 'otf'])) {
                $filePath = $file->getPathname();
                // Verificar si ya está en las entradas del CSS
                $alreadyListed = false;
                foreach ($installedEntries as $entry) {
                    if (!empty($entry['diskPath']) && realpath($entry['diskPath']) === realpath($filePath)) {
                        $alreadyListed = true;
                        break;
                    }
                }
                if (!$alreadyListed) {
                    $installedEntries[] = [
                        'family'   => pathinfo($filePath, PATHINFO_FILENAME),
                        'weight'   => 'Archivo huérfano',
                        'url'      => str_replace([$basePath, '\\'], ['', '/'], $filePath),
                        'diskPath' => $filePath,
                        'rawBlock' => '',
                    ];
                }
            }
        }
    }
}

if (empty($installedEntries)) {
    echo "ℹ️  No hay fuentes personalizadas instaladas en este proyecto.\n";
    echo "   (El proyecto está utilizando la fuente sans-serif por defecto del sistema).\n\n";
    exit(0);
}

// 2. Mostrar la lista con índices
echo "Fuentes instaladas actualmente:\n\n";
foreach ($installedEntries as $index => $item) {
    $num = $index + 1;
    $fileName = !empty($item['diskPath']) ? basename($item['diskPath']) : basename($item['url']);
    $isVar = (strpos($item['weight'], ' ') !== false);
    $typeLabel = $isVar ? " [Variable Font]" : "";
    echo "  [{$num}] {$item['family']} (Peso: {$item['weight']}){$typeLabel}\n";
    echo "      📁 {$fileName} ({$item['url']})\n\n";
}

echo "--------------------------------------------------\n";
echo "Opciones:\n";
echo "  👉 Escribe el número del índice (ej: 1) para borrar esa fuente.\n";
echo "  👉 Escribe varios índices separados por coma (ej: 1, 2) para borrar varias.\n";
echo "  👉 Escribe 'all' o 'todos' para resetear y BORRAR TODAS las fuentes.\n";
echo "  👉 Presiona Enter o 'q' para cancelar.\n";
echo "--------------------------------------------------\n> ";

$selection = trim(fgets($stdin));

if (empty($selection) || strtolower($selection) === 'q') {
    echo "❌ Operación cancelada por el usuario.\n";
    exit(0);
}

// 3. Determinar qué entradas eliminar
$toDelete = [];

if (strtolower($selection) === 'all' || strtolower($selection) === 'todos') {
    $toDelete = array_keys($installedEntries);
} else {
    $parts = explode(',', $selection);
    foreach ($parts as $part) {
        $idx = (int)trim($part) - 1;
        if (isset($installedEntries[$idx])) {
            $toDelete[] = $idx;
        } else {
            echo "⚠️ Índice no válido ignorado: " . trim($part) . "\n";
        }
    }
}

if (empty($toDelete)) {
    echo "❌ No se seleccionó ninguna fuente válida para eliminar.\n";
    exit(0);
}

$toDelete = array_unique($toDelete);

echo "\n🗑️  Eliminando " . count($toDelete) . " fuente(s)...\n";

$deletedBlocks = [];
$foldersToCheck = [];

foreach ($toDelete as $idx) {
    $entry = $installedEntries[$idx];
    
    // 1. Eliminar archivo físico
    if (!empty($entry['diskPath']) && file_exists($entry['diskPath'])) {
        $folder = dirname($entry['diskPath']);
        if (@unlink($entry['diskPath'])) {
            echo "   ✓ Archivo borrado: " . basename($entry['diskPath']) . "\n";
            $foldersToCheck[$folder] = true;
        } else {
            echo "   ❌ No se pudo borrar el archivo: {$entry['diskPath']}\n";
        }
    }
    
    // 2. Marcar bloque para remover de font-project.css
    if (!empty($entry['rawBlock'])) {
        $deletedBlocks[] = $entry['rawBlock'];
    }
}

// Limpiar carpetas vacías de fuentes
foreach (array_keys($foldersToCheck) as $folder) {
    if (is_dir($folder)) {
        $files = array_diff(scandir($folder), ['.', '..']);
        if (empty($files) && realpath($folder) !== realpath($fontsDir)) {
            @rmdir($folder);
            echo "   ✓ Carpeta vacía eliminada: " . basename($folder) . "\n";
        }
    }
}

// 4. Actualizar font-project.css
if (file_exists($cssFilePath)) {
    if (count($toDelete) === count($installedEntries)) {
        // Se borraron todas las fuentes: dejar el archivo vacío con cabecera
        $emptyCss = "/* ==========================================================================\n";
        $emptyCss .= "   🔤 FUENTES PERSONALIZADAS DEL PROYECTO\n";
        $emptyCss .= "   ========================================================================== */\n";
        file_put_contents($cssFilePath, $emptyCss);
        echo "\n✅ font-project.css fue reseteado.\n";
    } else {
        $currentCss = str_replace("\r\n", "\n", file_get_contents($cssFilePath));
        foreach ($deletedBlocks as $blockToRemove) {
            $currentCss = str_replace($blockToRemove, '', $currentCss);
        }
        // Limpiar saltos de línea redundantes
        $currentCss = preg_replace("/\n{3,}/", "\n\n", trim($currentCss)) . "\n";
        file_put_contents($cssFilePath, $currentCss);
        echo "\n✅ font-project.css fue actualizado.\n";
    }
}

// 5. Recompilar recursos con min-script
echo "\n📦 Recompilando recursos del proyecto con min-script...\n";

// Ejecutar min-script usando composer
$minScriptCommand = "composer run-script min-script";
if (function_exists('passthru')) {
    passthru($minScriptCommand);
} else {
    exec($minScriptCommand, $output, $returnCode);
    echo implode("\n", $output) . "\n";
}

echo "\n✨ ¡Fuentes desinstaladas y reseteadas exitosamente!\n";
