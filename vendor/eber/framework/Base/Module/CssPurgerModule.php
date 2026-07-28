<?php

namespace Base\Module;

/**
 * CSS Purger Module
 * 
 * Escanea archivos PHP y JS para extraer clases CSS usadas,
 * luego genera un CSS optimizado con solo las clases necesarias.
 */
class CssPurgerModule
{
  /**
   * Directorios a escanear para encontrar clases CSS usadas
   */
  private static array $scanDirectories = [
    ROOT_PATH . '/App/Segment/',
    ROOT_PATH . '/App/Views/',
    ROOT_PATH . '/App/Rsc/Js/',
    ROOT_PATH . '/App/Public/Js/',
    ROOT_PATH . '/Resources/Js/'
  ];

  /**
   * Extensiones de archivo a escanear
   */
  private static array $scanExtensions = ['php', 'js'];

  /**
   * Directorios con archivos CSS fuente
   */
  private static array $cssDirectories = [
    ROOT_PATH . '/App/Rsc/Css/',
    ROOT_PATH . '/Resources/Css/'
  ];

  /**
   * Patrones regex para extraer clases CSS de diferentes contextos
   */
  private static array $classPatterns = [
    // HTML class="..." o class='...'
    '/class\s*=\s*["\']([^"\']+)["\']/i',
    // JavaScript classList.add('clase') o classList.add("clase")
    '/classList\.(?:add|remove|toggle|contains)\s*\(\s*["\']([^"\']+)["\']\s*\)/i',
    // JavaScript classList.add('clase1', 'clase2')
    '/classList\.add\s*\(([^)]+)\)/i',
    // JavaScript className = 'clases'
    '/className\s*=\s*["\']([^"\']+)["\']/i',
    // JavaScript className += ' clase'
    '/className\s*\+=\s*["\']([^"\']+)["\']/i',
    // Template literals: `clase ${var} otra`
    '/`([^`]*)`/i',
  ];

  /**
   * Clases que siempre deben incluirse (críticas para el funcionamiento)
   */
  private static array $safelistClasses = [
    // Estados dinámicos comunes
    'hidden',
    'visible',
    'active',
    'inactive',
    'open',
    'closed',
    'loading',
    'loaded',
    'error',
    'success',
    'warning',
    // Clases de JS que podrían generarse dinámicamente
    'fonts-loaded',
    'fonts-fallback',
    // Responsive helpers
    'no-phone',
    'no-tablet',
    'no-desk',
    'no-phone-css',
    'no-tablet-css',
    'no-desk-css',
  ];

  /**
   * Patrones regex de clases a incluir siempre (safelist con wildcards)
   * Estos patrones preservan clases numéricas generadas y sus variantes responsive
   */
  private static array $safelistPatterns = [
    // Clases que terminan en -loaded
    '/.*-loaded$/',

    // Containers
    '/^container(-xl)?(-desk|-mid|-sml)?$/',

    // Grid columns: col-1 a col-12, col-mid-1, col-sml-1, etc.
    '/^col(-mid|-sml)?-\d+$/',

    // Grid spans: span-1 a span-12, span-mid-1, span-sml-1, etc.  
    '/^span(-mid|-sml)?-\d+$/',

    // Widths: w0-w100, w-mid-0, w-sml-0, wpx0-wpx500
    '/^w(?:-(desk|mid|sml))?-?\d+$/',
    '/^wpx(?:-(desk|mid|sml))?-?\d+$/',
    '/^wem(?:-(desk|mid|sml))?-?\d+$/',

    // Heights: h0-h100, hem0-hem100, hpx0-hpx1000, variantes responsive
    '/^h(?:-(desk|mid|sml))?-?\d+$/',
    '/^h(-max)?(-min)?-d?vh(?:-(desk|mid|sml))?$/',
    '/^hem(?:-(desk|mid|sml))?-?\d+$/',
    '/^hpx(?:-(desk|mid|sml))?-?\d+$/',

    // Gaps: gap0-gap100
    '/^gap\d+$/',

    // Positions: top, bottom, left, right con % y px
    '/^(top|bottom|left|right)(px)?\d+$/',

    // Font sizes: x1-x100, xt1-xt100 (tablet), xp1-xp100 (phone)
    '/^x(?:-(desk|mid|sml))?-?\d+$/',
    '/^xem(?:-(desk|mid|sml))?-?\d+$/',

    // Font weights: bold100-bold900, variantes responsive
    '/^bold\d+(-mid|-sml)?$/',

    // Border radius, z-index, margin, padding
    '/^z(?:-n)?(?:-(desk|mid|sml))?-?\d+$/',
    '/^br(tl|tr|bl|br)?(?:-(desk|mid|sml))?-\d+$/',
    '/^br(tl|tr|bl|br)?\d+$/',
    '/^m(t|r|b|l)?(?:-(desk|mid|sml))?-\d+$/',
    '/^m(t|r|b|l)?\d+$/',
    '/^p(t|r|b|l)?(?:-(desk|mid|sml))?-\d+$/',
    '/^p(t|r|b|l)?\d+$/',

    // Padding y margin: p, pt, pb, pl, pr, m, mt, mb, ml, mr con números
    '/^(p|pt|pb|pl|pr|m|mt|mb|ml|mr)\d+$/',

    // Border radius: br, brp con números  
    '/^brp?\d+$/',

    // Colors: color1-color8, back1-back8 y sus variantes hover
    '/^(color|back)\d+(-hover)?$/',

    // Line heights
    '/^line-h\d+$/',
  ];

  /**
   * Purga los archivos CSS y genera un archivo optimizado
   * 
   * @param string $outputFile Ruta del archivo de salida
   * @param bool $verbose Mostrar información de debug
   * @return array Estadísticas de la purga
   */
  public static function purge(string $outputFile = ROOT_PATH . '/App/Public/Min/Css/css.purged.css', bool $verbose = true): array
  {
    $stats = [
      'files_scanned' => 0,
      'classes_found' => 0,
      'css_rules_original' => 0,
      'css_rules_kept' => 0,
      'original_size' => 0,
      'purged_size' => 0,
    ];

    if ($verbose) {
      echo "🔍 CSS Purger - Iniciando análisis...\n\n";
    }

    // 1. Extraer todas las clases usadas en los archivos
    $usedClasses = self::extractUsedClasses($verbose);
    $stats['classes_found'] = count($usedClasses);

    if ($verbose) {
      echo "\n📋 Clases únicas encontradas: " . count($usedClasses) . "\n\n";
    }

    // 2. Añadir clases de la safelist
    $usedClasses = array_merge($usedClasses, self::$safelistClasses);
    $usedClasses = array_unique($usedClasses);

    // 3. Procesar archivos CSS y filtrar reglas
    $purgedCss = self::processCssFiles($usedClasses, $stats, $verbose);

    // 4. Guardar resultado
    file_put_contents($outputFile, $purgedCss);
    $stats['purged_size'] = strlen($purgedCss);

    if ($verbose) {
      echo "\n✅ CSS Purger completado!\n";
      echo "   📊 Reglas originales: " . $stats['css_rules_original'] . "\n";
      echo "   📊 Reglas mantenidas: " . $stats['css_rules_kept'] . "\n";
      echo "   📊 Tamaño original: " . round($stats['original_size'] / 1024, 2) . " KB\n";
      echo "   📊 Tamaño purgado: " . round($stats['purged_size'] / 1024, 2) . " KB\n";
      echo "   📊 Reducción: " . round((1 - $stats['purged_size'] / max($stats['original_size'], 1)) * 100, 1) . "%\n";
      echo "   📁 Archivo: $outputFile\n\n";
    }

    return $stats;
  }

  /**
   * Extrae todas las clases CSS usadas de los archivos escaneados
   */
  private static function extractUsedClasses(bool $verbose): array
  {
    $allClasses = [];
    $filesScanned = 0;

    foreach (self::$scanDirectories as $dir) {
      if (!is_dir($dir)) {
        if ($verbose) {
          echo "⚠️  Directorio no encontrado: $dir\n";
        }
        continue;
      }

      $files = self::getFilesRecursive($dir, self::$scanExtensions);

      foreach ($files as $file) {
        $content = file_get_contents($file);
        $classes = self::extractClassesFromContent($content);
        $allClasses = array_merge($allClasses, $classes);
        $filesScanned++;

        if ($verbose && count($classes) > 0) {
          echo "   ✓ " . basename($file) . " (" . count($classes) . " clases)\n";
        }
      }
    }

    if ($verbose) {
      echo "\n📁 Archivos escaneados: $filesScanned\n";
    }

    return array_unique($allClasses);
  }

  /**
   * Extrae clases CSS del contenido de un archivo
   */
  private static function extractClassesFromContent(string $content): array
  {
    $classes = [];

    foreach (self::$classPatterns as $pattern) {
      if (preg_match_all($pattern, $content, $matches)) {
        foreach ($matches[1] as $match) {
          // Separar por espacios para obtener clases individuales
          $individualClasses = preg_split('/\s+/', trim($match));
          foreach ($individualClasses as $class) {
            // Limpiar y validar el nombre de clase
            $class = trim($class, "\"' \t\n\r");
            // Filtrar clases válidas (alfanumérico, guiones, guiones bajos)
            if (preg_match('/^[a-zA-Z_-][a-zA-Z0-9_-]*$/', $class)) {
              $classes[] = $class;
            }
          }
        }
      }
    }

    return $classes;
  }

  /**
   * Procesa los archivos CSS y filtra las reglas no usadas
   */
  private static function processCssFiles(array $usedClasses, array &$stats, bool $verbose): string
  {
    $outputCss = '';

    foreach (self::$cssDirectories as $dir) {
      if (!is_dir($dir)) continue;

      $files = self::getFilesRecursive($dir, ['css']);
      sort($files); // Ordenar alfabéticamente

      foreach ($files as $file) {
        $content = file_get_contents($file);
        $stats['original_size'] += strlen($content);

        $purgedContent = self::purgeCssContent($content, $usedClasses, $stats);
        $outputCss .= "/* " . basename($file) . " */\n" . $purgedContent . "\n";

        if ($verbose) {
          echo "   📄 Procesando: " . basename($file) . "\n";
        }
      }
    }

    return $outputCss;
  }

  /**
   * Purga el contenido CSS manteniendo solo las reglas usadas
   */
  private static function purgeCssContent(string $css, array $usedClasses, array &$stats): string
  {
    $output = '';

    // Primero, extraer y mantener todas las reglas @font-face, @keyframes, :root, etc.
    $criticalRules = '';

    // Extraer @font-face
    if (preg_match_all('/@font-face\s*\{[^}]+\}/s', $css, $fontFaces)) {
      foreach ($fontFaces[0] as $fontFace) {
        $criticalRules .= $fontFace . "\n";
        $stats['css_rules_original']++;
        $stats['css_rules_kept']++;
      }
    }

    // Extraer @keyframes
    if (preg_match_all('/@(-webkit-)?keyframes\s+[^{]+\{(?:[^{}]*\{[^}]*\})*[^}]*\}/s', $css, $keyframes)) {
      foreach ($keyframes[0] as $keyframe) {
        $criticalRules .= $keyframe . "\n";
        $stats['css_rules_original']++;
        $stats['css_rules_kept']++;
      }
    }

    // Extraer :root
    if (preg_match_all('/:root\s*\{[^}]+\}/s', $css, $roots)) {
      foreach ($roots[0] as $root) {
        $criticalRules .= $root . "\n";
        $stats['css_rules_original']++;
        $stats['css_rules_kept']++;
      }
    }

    // Extraer selectores de atributo de tema (ej: [data-theme="dark"])
    if (preg_match_all('/\[data-theme[^\]]*\]\s*\{[^}]+\}/s', $css, $themeSelectors)) {
      foreach ($themeSelectors[0] as $themeSelector) {
        $criticalRules .= $themeSelector . "\n";
        $stats['css_rules_original']++;
        $stats['css_rules_kept']++;
      }
    }

    // Extraer @media queries
    if (preg_match_all('/@media[^{]+\{((?:[^{}]*\{[^}]*\})*[^}]*)\}/s', $css, $mediaQueries, PREG_SET_ORDER)) {
      foreach ($mediaQueries as $mediaQuery) {
        $mediaContent = $mediaQuery[1];
        $purgedMedia = self::purgeRules($mediaContent, $usedClasses, $stats);
        if (!empty(trim($purgedMedia))) {
          $mediaHeader = substr($mediaQuery[0], 0, strpos($mediaQuery[0], '{') + 1);
          $criticalRules .= $mediaHeader . "\n" . $purgedMedia . "}\n";
        }
      }
    }

    // Procesar reglas CSS normales (fuera de @media)
    $cssWithoutSpecials = $css;
    $cssWithoutSpecials = preg_replace('/@font-face\s*\{[^}]+\}/s', '', $cssWithoutSpecials);
    $cssWithoutSpecials = preg_replace('/@(-webkit-)?keyframes\s+[^{]+\{(?:[^{}]*\{[^}]*\})*[^}]*\}/s', '', $cssWithoutSpecials);
    $cssWithoutSpecials = preg_replace('/:root\s*\{[^}]+\}/s', '', $cssWithoutSpecials);
    $cssWithoutSpecials = preg_replace('/@media[^{]+\{((?:[^{}]*\{[^}]*\})*[^}]*)\}/s', '', $cssWithoutSpecials);

    $purgedNormal = self::purgeRules($cssWithoutSpecials, $usedClasses, $stats);

    return $criticalRules . $purgedNormal;
  }

  /**
   * Purga reglas CSS individuales
   */
  private static function purgeRules(string $css, array $usedClasses, array &$stats): string
  {
    $output = '';

    // Dividir por reglas (selector { propiedades })
    if (preg_match_all('/([^{]+)\{([^}]*)\}/s', $css, $rules, PREG_SET_ORDER)) {
      foreach ($rules as $rule) {
        $selector = trim($rule[1]);
        $properties = trim($rule[2]);
        $stats['css_rules_original']++;

        // Verificar si el selector debe mantenerse
        if (self::selectorIsUsed($selector, $usedClasses)) {
          $output .= $selector . " { " . $properties . " }\n";
          $stats['css_rules_kept']++;
        }
      }
    }

    return $output;
  }

  /**
   * Verifica si un selector CSS debe mantenerse
   */
  private static function selectorIsUsed(string $selector, array $usedClasses): bool
  {
    // Mantener selectores de elementos (html, body, *, p, h1, etc.)
    if (preg_match('/^[a-zA-Z*][a-zA-Z0-9]*(\s*,\s*[a-zA-Z*][a-zA-Z0-9]*)*\s*$/', $selector)) {
      return true;
    }

    // Mantener selectores pseudo-elementos y pseudo-clases globales
    if (preg_match('/^:/', $selector) || preg_match('/^\*/', $selector)) {
      return true;
    }

    // Extraer nombres de clase del selector
    if (preg_match_all('/\.([a-zA-Z_-][a-zA-Z0-9_-]*)/', $selector, $matches)) {
      foreach ($matches[1] as $className) {
        // Verificar si la clase está en la lista de usadas
        if (in_array($className, $usedClasses)) {
          return true;
        }

        // Verificar patrones de safelist
        foreach (self::$safelistPatterns as $pattern) {
          if (preg_match($pattern, $className)) {
            return true;
          }
        }
      }
    }

    // Si no tiene clases, probablemente es un selector de elemento
    if (!str_contains($selector, '.')) {
      return true;
    }

    return false;
  }

  /**
   * Obtiene archivos recursivamente de un directorio
   */
  private static function getFilesRecursive(string $dir, array $extensions): array
  {
    $files = [];
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $ext = strtolower($file->getExtension());
        if (in_array($ext, $extensions)) {
          $files[] = $file->getPathname();
        }
      }
    }

    return $files;
  }

  /**
   * Añade clases a la safelist
   */
  public static function addToSafelist(array $classes): void
  {
    self::$safelistClasses = array_merge(self::$safelistClasses, $classes);
  }

  /**
   * Añade patrones regex a la safelist
   */
  public static function addSafelistPatterns(array $patterns): void
  {
    self::$safelistPatterns = array_merge(self::$safelistPatterns, $patterns);
  }

  /**
   * Configura los directorios a escanear
   */
  public static function setScanDirectories(array $directories): void
  {
    self::$scanDirectories = $directories;
  }

  /**
   * Configura los directorios de CSS fuente
   */
  public static function setCssDirectories(array $directories): void
  {
    self::$cssDirectories = $directories;
  }

  /**
   * Método de conveniencia que purga y minifica en un solo paso
   */
  public static function purgeAndMinify(
    string $outputFile = ROOT_PATH . '/App/Public/Min/Css/css.min.css',
    bool $verbose = true
  ): array {
    // Primero purgar
    $tempFile = ROOT_PATH . '/App/Public/Min/Css/css.purged.temp.css';
    $stats = self::purge($tempFile, $verbose);

    // Luego minificar usando minifyModule
    if (class_exists('\\Base\\module\\minifyModule')) {
      $purgedContent = file_get_contents($tempFile);
      $minified = MinifyModule::minifyCssFromContent($purgedContent);
      file_put_contents($outputFile, $minified);

      $stats['minified_size'] = strlen($minified);

      if ($verbose) {
        echo "🗜️  Minificado: " . round($stats['minified_size'] / 1024, 2) . " KB\n";
        echo "   Reducción total: " . round((1 - $stats['minified_size'] / max($stats['original_size'], 1)) * 100, 1) . "%\n";
      }

      // Limpiar archivo temporal
      @unlink($tempFile);
    }

    return $stats;
  }
}
