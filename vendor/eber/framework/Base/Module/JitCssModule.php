<?php

namespace Base\Module;

use Base\LibraryCssJit\JitRuleInterface;

class JitCssModule
{
    /**
     * Extrae las palabras de los archivos indicados y genera el CSS al vuelo
     * utilizando las clases de reglas en Base/LibraryCssJit.
     *
     * @param array $scanDirs Directorios a escanear (App, Base, etc)
     * @param string $outPath Archivo donde se guardará el CSS temporal generado
     * @param bool $verbose 
     */
    public static function generateJitCss(array $scanDirs, string $outPath, bool $verbose = true): array
    {
        $words = [];
        if ($verbose) {
            echo "🔍 Analizando uso de clases para JIT CSS Compiler...\n";
        }

        // Búsqueda recursiva
        foreach ($scanDirs as $dir) {
            $scanFiles = array_merge(
                MinifyModule::getDirectoryFilesRecursive($dir, 'php'),
                MinifyModule::getDirectoryFilesRecursive($dir, 'js'),
                MinifyModule::getDirectoryFilesRecursive($dir, 'html')
            );
            foreach ($scanFiles as $scanFile) {
                $content = file_get_contents($scanFile);
                if (preg_match_all('/[a-zA-Z0-9_-]+/', $content, $matches)) {
                    foreach ($matches[0] as $word) {
                        $words[$word] = true;
                    }
                }
            }
        }

        $uniqueWordsCount = count($words);
        if ($verbose) {
            echo "   ✓ Se encontraron {$uniqueWordsCount} palabras únicas.\n";
        }

        // Cargar reglas dinámicamente
        $rules = self::loadRules();
        
        if ($verbose) {
            echo "   ✓ " . count($rules) . " librerías JIT cargadas.\n";
        }

        $generatedCss = self::processWords(array_keys($words), $rules);
        
        file_put_contents($outPath, $generatedCss);

        if ($verbose) {
            $size = round(strlen($generatedCss) / 1024, 2);
            echo "✅ JIT CSS generado: {$outPath} ({$size} KB)\n\n";
        }

        return $words;
    }

    /**
     * Analiza font-project.css y detecta qué fuentes personalizadas están siendo utilizadas
     * en el proyecto según las palabras/clases extraídas por JIT.
     * Genera App/Config/preloadFonts.json con la lista de fuentes a precargar.
     *
     * @param array $words Diccionario de palabras detectadas en el escaneo
     * @param string $fontCssPath Ruta al archivo font-project.css
     * @param string $outJsonPath Ruta al archivo preloadFonts.json
     * @param bool $verbose
     */
    public static function generatePreloadFontsConfig(array $words, string $fontCssPath, string $outJsonPath, bool $verbose = true): array
    {
        $activeFonts = [];

        if (file_exists($fontCssPath)) {
            $cssContent = file_get_contents($fontCssPath);
            
            // Separar por bloques de @font-face
            $blocks = explode('@font-face', $cssContent);
            
            foreach ($blocks as $block) {
                if (empty(trim($block))) {
                    continue;
                }
                
                // Extraer la url de la fuente dentro de src: url(...)
                if (preg_match('/url\([\'"]?([^\'")]+)[\'"]?\)/i', $block, $urlMatches)) {
                    $fontUrl = $urlMatches[1];
                    
                    // Extraer los nombres de las clases CSS definidas en este bloque (ej: .googleFlex400 -> googleFlex400)
                    preg_match_all('/\.([a-zA-Z0-9_-]+)\s*\{/i', $block, $classMatches);
                    $classes = $classMatches[1] ?? [];
                    
                    // Extraer nombre de la font-family si existe
                    $fontFamilyWords = [];
                    if (preg_match('/font-family:\s*[\'"]?([^\'";]+)[\'"]?/i', $block, $familyMatches)) {
                        $familyClean = trim($familyMatches[1]);
                        $fontFamilyWords = preg_split('/[\s_-]+/', $familyClean);
                    }

                    $isUsed = false;
                    foreach ($classes as $class) {
                        if (isset($words[$class])) {
                            $isUsed = true;
                            break;
                        }
                    }

                    if (!$isUsed) {
                        foreach ($fontFamilyWords as $fWord) {
                            if (!empty($fWord) && isset($words[$fWord])) {
                                $isUsed = true;
                                break;
                            }
                        }
                    }

                    if ($isUsed && !in_array($fontUrl, $activeFonts, true)) {
                        $activeFonts[] = $fontUrl;
                    }
                }
            }
        }

        $dir = dirname($outJsonPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        file_put_contents($outJsonPath, json_encode($activeFonts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($verbose) {
            $count = count($activeFonts);
            if ($count > 0) {
                echo "🔤 Precarga de fuentes configurada: {$count} fuente(s) activa(s) en App/Config/preloadFonts.json\n";
                foreach ($activeFonts as $f) {
                    echo "   ✓ " . basename($f) . "\n";
                }
                echo "\n";
            } else {
                echo "🔤 Precarga de fuentes: 0 fuentes personalizadas activas (usando fuente del sistema).\n\n";
            }
        }

        return $activeFonts;
    }

    /**
     * Escanea el directorio Base/LibraryCssJit e instancia todas las clases que implementen JitRuleInterface
     */
    private static function loadRules(): array
    {
        $rules = [];
        $dir = __DIR__ . '/../LibraryCssJit';
        if (!is_dir($dir)) {
            return $rules;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $className = pathinfo($file, PATHINFO_FILENAME);
                if ($className === 'JitRuleInterface' || $className === 'JitRuleBase') {
                    continue; // Skip interface and base class
                }
                
                $fullClass = "\\Base\\LibraryCssJit\\{$className}";
                if (class_exists($fullClass)) {
                    $instance = new $fullClass();
                    if ($instance instanceof JitRuleInterface) {
                        $rules[] = $instance;
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Pasa cada palabra por las librerías JIT cargadas
     */
    private static function processWords(array $words, array $rules): string
    {
        $css = "/* Generado automáticamente por JitCssModule (Modular) */\n";

        $baseCss = [];
        $mediaCss = [];
        foreach ($rules as $rule) {
            $baseCss[get_class($rule)] = '';
            $mediaCss[get_class($rule)] = '';
        }

        // Ordenar palabras alfabéticamente para evitar inconsistencias de orden
        sort($words);

        foreach ($words as $word) {
            foreach ($rules as $rule) {
                $generated = $rule->processWord($word);
                if ($generated !== null) {
                    if (strpos($generated, '@media') !== false) {
                        $mediaCss[get_class($rule)] .= $generated;
                    } else {
                        $baseCss[get_class($rule)] .= $generated;
                    }
                    break; // Ya fue procesada por esta librería
                }
            }
        }

        foreach ($baseCss as $ruleCss) {
            $css .= $ruleCss;
        }
        $css .= "\n/* --- Media Queries --- */\n";
        foreach ($mediaCss as $ruleCss) {
            $css .= $ruleCss;
        }

        return $css;
    }
}
