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
    public static function generateJitCss(array $scanDirs, string $outPath, bool $verbose = true): void
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
