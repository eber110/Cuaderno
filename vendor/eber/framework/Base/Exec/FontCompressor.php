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

        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $cmdCheck = $isWin ? 'where' : 'which';

        // 1. Probar con binario nativo woff2_compress
        $hasWoff2Compress = false;
        if (function_exists('exec')) {
            exec("$cmdCheck woff2_compress 2>nul", $outWoff2, $codeWoff2);
            if ($codeWoff2 === 0) {
                $hasWoff2Compress = true;
            }
        }

        if ($hasWoff2Compress) {
            echo "🔄 Comprimiendo con el binario woff2_compress...\n";
            $cmd = "woff2_compress " . escapeshellarg($sourcePath);
            exec($cmd, $out, $code);
            
            $expectedOutput = preg_replace('/\.(ttf|otf)$/i', '.woff2', $sourcePath);
            if ($expectedOutput !== $destPath && file_exists($expectedOutput)) {
                rename($expectedOutput, $destPath);
            }
            if (file_exists($destPath) && filesize($destPath) > 0) {
                $size = filesize($destPath);
                echo "✅ Compresión exitosa: " . basename($destPath) . " (" . number_format($size / 1024, 2) . " KB)\n";
                @unlink($sourcePath);
                return true;
            }
        }

        // 2. Probar con Node.js (wawoff2 moderno o ttf2woff2)
        $hasNode = false;
        if (function_exists('exec')) {
            exec("$cmdCheck node 2>nul", $outNode, $codeNode);
            if ($codeNode === 0) {
                $hasNode = true;
            }
        }

        if ($hasNode) {
            echo "🔄 Comprimiendo a WOFF2 con Node.js (WebAssembly)... (Por favor espera unos segundos)\n";
            
            // Script de Node.js que intenta primero wawoff2 (soporta fuentes variables pesadas de varios MB) y luego ttf2woff2
            $nodeScript = "const fs = require('fs');"
                . "let wawoff2;"
                . "try { wawoff2 = require('wawoff2'); }"
                . "catch(e) { try { wawoff2 = require('C:/nvm4w/nodejs/node_modules/wawoff2'); } catch(e2) {} }"
                . "if (wawoff2 && typeof wawoff2.compress === 'function') {"
                . "  const input = fs.readFileSync(process.argv[1]);"
                . "  wawoff2.compress(input).then(out => {"
                . "    fs.writeFileSync(process.argv[2], out);"
                . "    process.exit(0);"
                . "  }).catch(() => process.exit(1));"
                . "} else {"
                . "  let ttf2woff2;"
                . "  try { ttf2woff2 = require('ttf2woff2'); }"
                . "  catch(e) { try { ttf2woff2 = require('C:/nvm4w/nodejs/node_modules/ttf2woff2'); } catch(e2) { process.exit(1); } }"
                . "  const fn = typeof ttf2woff2 === 'function' ? ttf2woff2 : (ttf2woff2.default || ttf2woff2);"
                . "  const input = fs.readFileSync(process.argv[1]);"
                . "  const output = fn(input);"
                . "  fs.writeFileSync(process.argv[2], output);"
                . "}";

            $cmd = "node -e " . escapeshellarg($nodeScript) . " " . escapeshellarg($sourcePath) . " " . escapeshellarg($destPath);
            exec($cmd, $out, $code);

            if (file_exists($destPath) && filesize($destPath) > 0) {
                $size = filesize($destPath);
                echo "✅ Compresión exitosa: " . basename($destPath) . " (" . number_format($size / 1024, 2) . " KB)\n";
                @unlink($sourcePath);
                return true;
            }
        }

        echo "ℹ️ Nota: No se pudo comprimir automáticamente a WOFF2. Se mantendrá el archivo original (.ttf/.otf).\n";
        echo "   (Para máxima optimización web, puedes usar fuentes ya en formato .woff2 o ejecutar: npm install -g wawoff2)\n";
        return false;
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

                    if (!file_exists($destPath) || filesize($destPath) === 0) {
                        if (file_exists($destPath)) {
                            @unlink($destPath);
                        }
                        echo "⚙️  Convirtiendo: " . basename($sourcePath) . "\n";
                        if (self::convertToWoff2($sourcePath, $destPath)) {
                            $count++;
                            $convertedFonts[] = basename($sourcePath);
                        }
                    } else {
                        // Si ya existe la versión woff2 válida, la consideramos como convertida para actualizar CSS
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

    /**
     * Determina si un archivo de fuente (.ttf, .otf, .woff, .woff2) es una Fuente Variable.
     * Inspecciona las tablas OpenType buscando 'fvar' (Font Variations) o 'gvar'.
     *
     * @param string $filePath Ruta del archivo de fuente
     * @return bool True si contiene variaciones, False si es una fuente estática
     */
    public static function isVariableFont(string $filePath): bool
    {
        if (!file_exists($filePath) || filesize($filePath) < 32) {
            return false;
        }

        $handle = @fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        // Leer los primeros 8 KB donde reside el directorio de tablas OpenType / WOFF / WOFF2
        $header = fread($handle, 8192);
        fclose($handle);

        return (strpos($header, 'fvar') !== false || strpos($header, 'gvar') !== false);
    }

    /**
     * Infiere automáticamente el peso numérico de la fuente (100-900) a partir del nombre del archivo.
     *
     * @param string $filename Nombre del archivo o ruta
     * @return int Peso numérico estándar (400 por defecto para Regular)
     */
    public static function detectWeightFromFilename(string $filename): int
    {
        $name = strtolower(pathinfo($filename, PATHINFO_FILENAME));

        if (preg_match('/(thin|hairline|100)/i', $name)) return 100;
        if (preg_match('/(extralight|ultralight|200)/i', $name)) return 200;
        if (preg_match('/(light|300)/i', $name)) return 300;
        if (preg_match('/(semibold|demibold|600)/i', $name)) return 600;
        if (preg_match('/(extrabold|ultrabold|800)/i', $name)) return 800;
        if (preg_match('/(black|heavy|extrablack|900)/i', $name)) return 900;
        if (preg_match('/(bold|700)/i', $name)) return 700;
        if (preg_match('/(medium|500)/i', $name)) return 500;
        if (preg_match('/(regular|normal|book|400)/i', $name)) return 400;

        return 400;
    }
}
