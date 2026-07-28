<?php

namespace Base\Module;

use MatthiasMullie\Minify;
use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use MatthiasMullie\Minify\Exceptions;

class MinifyModule
{

  /**
   * Obtiene los archivos de un directorio de forma recursiva
   * @param string $dir Directorio a escanear
   * @param string $extension Extensión de archivos a buscar (opcional)
   * @return array Lista de archivos con rutas completas
   */
  public static function getDirectoryFilesRecursive(string $dir, string $extension = ''): array
  {
    if (!is_dir($dir)) {
      return [];
    }

    $files = [];
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
      if ($file->isFile()) {
        // Si se especifica extensión, filtrar por ella
        if ($extension === '' || $file->getExtension() === $extension) {
          $files[] = $file->getPathname();
        }
      }
    }

    return $files;
  }

  /**
   * Ordena los archivos según el archivo de prioridad jsConfig.json
   * @param array $files Lista de archivos con rutas completas
   * @param string $orderFile Ruta al archivo de configuración de orden
   * @return array Lista ordenada de archivos
   */
  private static function sortFilesByPriority(array $files, ?string $orderFile = null): array
  {
    if ($orderFile === null) {
      $orderFile = ROOT_PATH . '/jsConfig.json';
    }
    // Si no existe el archivo de orden, devolver los archivos sin modificar
    if (!file_exists($orderFile)) {
      return $files;
    }

    $config = json_decode(file_get_contents($orderFile), true);

    if (!$config) {
      return $files;
    }

    $priority = $config['priority'] ?? [];
    $exclude = $config['exclude'] ?? [];

    $deferModules = array_keys($config['functions']['defer'] ?? []);
    $asyncModules = array_keys($config['functions']['async'] ?? []);

    // Construir lista de archivos permitidos
    $allowedFiles = $priority;
    foreach (array_merge($deferModules, $asyncModules) as $mod) {
        $filename = $mod . '.js';
        if (!in_array($filename, $allowedFiles)) {
            $allowedFiles[] = $filename;
        }
    }

    $priorityFiles = [];
    $remainingFiles = [];

    foreach ($files as $file) {
      $basename = basename($file);

      // Filtrado estricto: solo incluir archivos listados en jsConfig.json
      if (!in_array($basename, $allowedFiles)) {
          continue;
      }

      if (in_array($basename, $exclude)) {
          continue;
      }

      $priorityIndex = array_search($basename, $priority);

      if ($priorityIndex !== false) {
        // Guardar solo la primera instancia encontrada (permite sobrescribir framework con App)
        if (!isset($priorityFiles[$priorityIndex])) {
            $priorityFiles[$priorityIndex] = $file;
        }
      } else {
        // Guardar solo la primera instancia encontrada
        if (!isset($remainingFiles[$basename])) {
            $remainingFiles[$basename] = $file;
        }
      }
    }

    ksort($priorityFiles);
    ksort($remainingFiles);

    return array_merge(array_values($priorityFiles), array_values($remainingFiles));
  }

  /**
   * Minifica archivos CSS de múltiples directorios
   * @param array $dir_css Directorios con archivos CSS
   * @param string $out_minifier Archivo de salida
   * @param bool $verbose Mostrar información de debug
   */
  public static function minifyCss(array $dir_css, string $out_minifier, bool $verbose = true): void
  {
    $new_arch = [];

    foreach ($dir_css as $dir) {
      // Búsqueda recursiva de archivos CSS
      $files = self::getDirectoryFilesRecursive($dir, 'css');
      // Ordenar alfabéticamente dentro de cada directorio
      sort($files);
      $new_arch = array_merge($new_arch, $files);
    }

    if (empty($new_arch)) {
      if ($verbose) {
        echo "⚠️  No se encontraron archivos CSS para minificar.\n";
      }
      return;
    }

    $minifier_css = new \MatthiasMullie\Minify\CSS();

    if ($verbose) {
      echo "📦 Minificando CSS:\n";
    }

    foreach ($new_arch as $file) {
      $minifier_css->add($file);
      if ($verbose) {
        echo "   ✓ " . basename($file) . "\n";
      }
    }

    $minifier_css_out = $minifier_css->minify();
    file_put_contents($out_minifier, $minifier_css_out);

    if ($verbose) {
      $size = round(strlen($minifier_css_out) / 1024, 2);
      echo "✅ CSS minificado: {$out_minifier} ({$size} KB)\n\n";
    }
  }

  /**
   * Minifica archivos JS de múltiples directorios
   * @param array $dir_js Directorios con archivos JS
   * @param string $out_minifier Archivo de salida
   * @param bool $verbose Mostrar información de debug
   */
  public static function minifyJs(array $dir_js, string $out_minifier, bool $verbose = true): void
  {
    $new_arch = [];

    foreach ($dir_js as $dir) {
      // Búsqueda recursiva de archivos JS
      $files = self::getDirectoryFilesRecursive($dir, 'js');
      $new_arch = array_merge($new_arch, $files);
    }

    if (empty($new_arch)) {
      if ($verbose) {
        echo "⚠️  No se encontraron archivos JS para minificar.\n";
      }
      return;
    }

    // Ordenar con prioridad configurable
    $new_arch = self::sortFilesByPriority($new_arch);

    $minifier_js = new \MatthiasMullie\Minify\JS();

    if ($verbose) {
      echo "📦 Minificando JS:\n";
    }

    foreach ($new_arch as $file) {
      $minifier_js->add($file);
      if ($verbose) {
        echo "   ✓ " . basename($file) . "\n";
      }
    }

    $minifier_js_out = $minifier_js->minify();
    file_put_contents($out_minifier, $minifier_js_out);

    if ($verbose) {
      $size = round(strlen($minifier_js_out) / 1024, 2);
      echo "✅ JS minificado: {$out_minifier} ({$size} KB)\n\n";
    }
  }

  /**
   * Minifica CSS desde contenido string
   * @param string $cssContent Contenido CSS
   * @return string CSS minificado
   */
  public static function minifyCssFromContent(string $cssContent): string
  {
    $minifier = new \MatthiasMullie\Minify\CSS();
    $minifier->add($cssContent);
    return $minifier->minify();
  }

  /**
   * Minifica JS desde contenido string
   * @param string $jsContent Contenido JS
   * @return string JS minificado
   */
  public static function minifyJsFromContent(string $jsContent): string
  {
    $minifier = new \MatthiasMullie\Minify\JS();
    $minifier->add($jsContent);
    return $minifier->minify();
  }
}
