<?php
namespace Base\Exec;

class FontCompressor
{
    /**
     * Comprime una fuente TTF o OTF a WOFF2 utilizando el ejecutable oficial woff2_compress.
     *
     * @param string $sourcePath Ruta de la fuente original (.ttf o .otf)
     * @param string $destPath Ruta de destino (.woff2)
     * @return bool True si tuvo éxito, False en caso contrario
     */
    public static function convertToWoff2($sourcePath, $destPath)
    {
        if (!file_exists($sourcePath)) {
            echo "❌ Error: La fuente original no existe ({$sourcePath})\n";
            return false;
        }

        // Intentar usar el binario oficial del sistema (woff2_compress)
        $hasWoff2Compress = false;
        $hasTtf2Woff2 = false;

        if (function_exists('exec')) {
            $cmdCheck = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
            
            exec("$cmdCheck woff2_compress 2>nul", $out, $codeWoff2);
            if ($codeWoff2 === 0) $hasWoff2Compress = true;

            exec("$cmdCheck ttf2woff2 2>nul", $out, $codeTtf2);
            if ($codeTtf2 === 0) $hasTtf2Woff2 = true;
        }

        if (!$hasWoff2Compress && !$hasTtf2Woff2) {
            echo "❌ ERROR FATAL: No se encontró un compresor WOFF2 en tu PC.\n";
            echo "   Opciones para solucionar esto:\n";
            echo "   👉 Ejecuta en consola: npm install -g ttf2woff2 (Recomendado)\n";
            echo "   👉 O instala woff2_compress manualmente.\n";
            return false;
        }

        if ($hasWoff2Compress) {
            echo "🔄 Comprimiendo con el binario woff2_compress...\n";
            $cmd = "woff2_compress " . escapeshellarg($sourcePath);
            exec($cmd, $out, $code);
            
            $expectedOutput = preg_replace('/\.(ttf|otf)$/i', '.woff2', $sourcePath);
            if ($expectedOutput !== $destPath && file_exists($expectedOutput)) {
                rename($expectedOutput, $destPath);
            }
        } else if ($hasTtf2Woff2) {
            echo "🔄 Comprimiendo con ttf2woff2 (NodeJS)...\n";
            // ttf2woff2 lee de stdin y escribe en stdout
            $cmd = "ttf2woff2 < " . escapeshellarg($sourcePath) . " > " . escapeshellarg($destPath);
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // En cmd de windows a veces las redirecciones con escapeshellarg fallan si tienen comillas dobles externas,
                // pero ttf2woff2 en Windows normalmente funciona bien con cmd /c
                $cmd = 'cmd /c "' . $cmd . '"';
            }
            exec($cmd, $out, $code);
        }
        
        if (file_exists($destPath) && filesize($destPath) > 0) {
            $size = filesize($destPath);
            echo "✅ Compresión exitosa: " . basename($destPath) . " (" . number_format($size / 1024, 2) . " KB)\n";
            // Eliminar el archivo original para no dejar residuos pesados
            @unlink($sourcePath);
            return true;
        } else {
            echo "❌ Error: Falló la compresión. El ejecutable 'woff2_compress' no produjo el archivo esperado.\n";
            return false;
        }
    }

    /**
     * Escanea el directorio de fuentes y comprime cualquier TTF/OTF que no tenga su versión WOFF2.
     * 
     * @param string $fontsDir Directorio raíz de fuentes (App/Rsc/Fonts)
     */
    public static function processExistingFonts($fontsDir)
    {
        if (!is_dir($fontsDir)) {
            return;
        }

        echo "\n🔍 Escaneando directorio de fuentes en busca de archivos para comprimir a WOFF2...\n";

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fontsDir));
        $count = 0;
        $convertedFonts = [];

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if ($ext === 'ttf' || $ext === 'otf') {
                    $sourcePath = $file->getPathname();
                    $destPath = preg_replace('/\.(ttf|otf)$/i', '.woff2', $sourcePath);

                    if (!file_exists($destPath)) {
                        echo "⚙️  Convirtiendo: " . basename($sourcePath) . "\n";
                        if (self::convertToWoff2($sourcePath, $destPath)) {
                            $count++;
                            $convertedFonts[] = basename($sourcePath);
                        }
                    } else {
                        // Si ya existe la versión woff2, la consideramos como convertida para actualizar CSS
                        $convertedFonts[] = basename($sourcePath);
                    }
                }
            }
        }

        if ($count > 0) {
            echo "✅ Se comprimieron $count fuente(s) a formato WOFF2.\n";
        } else {
            echo "ℹ️ No se encontraron fuentes nuevas para comprimir o todas ya están en WOFF2.\n";
        }

        // Actualizar font-project.css para apuntar a woff2
        if (!empty($convertedFonts)) {
            $cssDir = dirname($fontsDir, 2) . '/Public/Css';
            $cssFile = $cssDir . '/font-project.css';
            if (file_exists($cssFile)) {
                $cssContent = file_get_contents($cssFile);
                $changed = false;
                foreach ($convertedFonts as $fontFile) {
                    $fontBase = pathinfo($fontFile, PATHINFO_FILENAME);
                    // Reemplazar .ttf y .otf por .woff2 en src: url(...)
                    $pattern = "/url\('([^']+)\/" . preg_quote($fontBase, '/') . "\.(ttf|otf)'\)\s+format\('(truetype|opentype)'\)/i";
                    $replacement = "url('$1/{$fontBase}.woff2') format('woff2')";
                    
                    $newContent = preg_replace($pattern, $replacement, $cssContent);
                    if ($newContent !== null && $newContent !== $cssContent) {
                        $cssContent = $newContent;
                        $changed = true;
                    }
                }
                if ($changed) {
                    file_put_contents($cssFile, $cssContent);
                    echo "✅ Se actualizaron las referencias en font-project.css a WOFF2.\n";
                }
            }
        }
    }
}
