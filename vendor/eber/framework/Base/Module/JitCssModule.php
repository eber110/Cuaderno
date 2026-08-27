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

        // Búsqueda recursiva en plantillas y código fuente (PHP, JS, HTML)
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
            
            // Construir diccionario insensible a mayúsculas/minúsculas para máxima robustez
            $lowerWords = [];
            foreach ($words as $word => $val) {
                $lowerWords[strtolower($word)] = true;
            }

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
                    
                    // Extraer candidatos para el nombre de la font-family si existe
                    $fontFamilyCandidates = [];
                    if (preg_match('/font-family:\s*[\'"]?([^\'";]+)[\'"]?/i', $block, $familyMatches)) {
                        $familyClean = trim($familyMatches[1]);
                        $fontFamilyCandidates[] = $familyClean;
                        $fontFamilyCandidates[] = strtolower($familyClean);
                        $fontFamilyCandidates[] = preg_replace('/[^a-zA-Z0-9]/', '', $familyClean);
                        $subParts = preg_split('/[\s_-]+/', $familyClean);
                        foreach ($subParts as $sp) {
                            if (!empty($sp)) {
                                $fontFamilyCandidates[] = $sp;
                            }
                        }
                    }

                    // Extraer font-weight para identificar si es variable o estática
                    $fontWeight = '';
                    if (preg_match('/font-weight:\s*([^;]+);/i', $block, $weightMatches)) {
                        $fontWeight = trim($weightMatches[1]);
                    }
                    $isVariable = (strpos($fontWeight, ' ') !== false || $fontWeight === '100 900');
                    $isBaseRegularWeight = ($fontWeight === '400' || strtolower($fontWeight) === 'normal');

                    // 1. Verificar si la clase específica (ej. googleFlex200, googleFlex700) está en uso explícito
                    $isUsedClass = false;
                    foreach ($classes as $class) {
                        // Omitir la clase base genérica (ej. googleFlex) para no marcar todos los pesos como clase específica
                        if (isset($familyClean) && (strtolower($class) === strtolower($familyClean) || strtolower($class) === strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $familyClean)))) {
                            continue;
                        }
                        if (isset($words[$class]) || isset($lowerWords[strtolower($class)])) {
                            $isUsedClass = true;
                            break;
                        }
                    }

                    // 2. Verificar si se invocó la familia globalmente (ej. en variables CSS como --font o la clase base de la fuente)
                    $isUsedFamily = false;
                    foreach ($fontFamilyCandidates as $fCandidate) {
                        if (!empty($fCandidate) && (isset($words[$fCandidate]) || isset($lowerWords[strtolower($fCandidate)]))) {
                            $isUsedFamily = true;
                            break;
                        }
                    }

                    $isBoldWeight = ($fontWeight === '700' || strtolower($fontWeight) === 'bold');
                    $isBoldInUse = isset($words['bold']) || isset($words['bold700']) || isset($words['b700']) || isset($lowerWords['bold']) || isset($lowerWords['bold700']);

                    // Decisión de precarga para optimización máxima de red:
                    // 1. Si es Fuente Variable: precargar el archivo único (contiene todos los pesos 100-900 en un solo archivo).
                    // 2. Si son Fuentes Estáticas (por peso): precargar el peso base regular (400 / normal) y bold (700) si se detecta su uso,
                    //    o cualquier peso cuya clase específica esté en uso en las vistas (evitando saltos secuenciales en la ruta crítica).
                    $shouldPreload = false;
                    if ($isVariable && $isUsedFamily) {
                        $shouldPreload = true;
                    } elseif (!$isVariable && $isUsedFamily && $isBaseRegularWeight) {
                        $shouldPreload = true;
                    } elseif (!$isVariable && $isUsedFamily && $isBoldWeight && $isBoldInUse) {
                        $shouldPreload = true;
                    } elseif (!$isVariable && $isUsedClass) {
                        $shouldPreload = true;
                    }

                    if ($shouldPreload && !in_array($fontUrl, $activeFonts, true)) {
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
