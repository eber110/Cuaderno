<?php

namespace Core\ConfigLoader;

class LoadViewStyle
{
  /**
   * Convierte una ruta de filesystem a una URL relativa.
   */
  private function fsPathToUrl(string $fsPath): string
  {
    $normalizedFs = str_replace('\\', '/', $fsPath);
    $normalizedRoot = str_replace('\\', '/', defined('ROOT_PATH') ? ROOT_PATH : '');
    if ($normalizedRoot !== '' && stripos($normalizedFs, $normalizedRoot) === 0) {
      return substr($normalizedFs, strlen($normalizedRoot));
    }
    return str_ireplace($normalizedRoot, '', $normalizedFs);
  }

  private function getDirectoryFiles($dir)
  {
    if (!is_dir($dir)) {
      return [];
    }
    $files = array_diff(scandir($dir), ['.', '..']);
    return array_values($files);
  }

  private function getFontMimeType($file)
  {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimeTypes = [
      'woff2' => 'font/woff2',
      'woff' => 'font/woff',
      'ttf' => 'font/ttf',
      'otf' => 'font/otf',
      'eot' => 'application/vnd.ms-fontobject'
    ];
    return $mimeTypes[$ext] ?? 'font/woff2';
  }

  /**
   * Precarga automáticamente las fuentes personalizadas activas detectadas por JIT
   * desde App/Config/preloadFonts.json.
   * Si no hay fuentes en uso, no inyecta nada (usando tipografía del sistema a máxima velocidad).
   *
   * @param string|null $configPath Ruta opcional personalizada al archivo JSON
   */
  public function ruteFont(?string $configPath = null): void
  {
    $jsonFile = $configPath ?? (ROOT_PATH . '/App/Config/preloadFonts.json');

    if (!file_exists($jsonFile)) {
      return;
    }

    $content = file_get_contents($jsonFile);
    $fonts = json_decode($content, true);

    if (!is_array($fonts) || empty($fonts)) {
      return;
    }

    $preloadedCount = 0;
    foreach ($fonts as $fontUrl) {
      $fontPathClean = '/' . ltrim(parse_url($fontUrl, PHP_URL_PATH), '/');
      $fullPath = ROOT_PATH . $fontPathClean;
      if (file_exists($fullPath)) {
        $mimeType = $this->getFontMimeType($fontPathClean);
        print '<link rel="preload" href="' . URL . ltrim($fontPathClean, '/') . '" as="font" type="' . $mimeType . '" crossorigin>';
        $preloadedCount++;
      }
    }

    if ($preloadedCount > 0) {
      print "<script>\n";
      print "if ('fonts' in document) {\n";
      print "  document.fonts.ready.then(function() {\n";
      print "    document.documentElement.classList.add('fonts-loaded');\n";
      print "  });\n";
      print "}\n";
      print "</script>\n";
    }
  }

  public function ruteCss($ruteCss, $param = '')
  {
    $files = $this->getDirectoryFiles($ruteCss);
    $urlPath = $this->fsPathToUrl($ruteCss);
    foreach ($files as $file) {
      $href = URL . ltrim($urlPath, '/') . $file;
      // Preload inmediato de alta prioridad
      print '<link rel="preload" href="' . $href . '" as="style">';
      print '<link rel="stylesheet" href="' . $href . '" ' . $param . '>';
    }
  }

  public function ruteJs($ruteJs, $param = '')
  {
    $files = $this->getDirectoryFiles($ruteJs);
    $urlPath = $this->fsPathToUrl($ruteJs);
    foreach ($files as $file) {
      print '<script src="' . URL . ltrim($urlPath, '/') . $file . '" ' . $param . '></script>';
    }
  }

  public function ruteStyle($rute, $param = null)
  {
    $files = $this->getDirectoryFiles($rute);
    $urlPath = $this->fsPathToUrl($rute);

    foreach ($files as $file) {
      $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      if ($extension === 'css') {
        print '<link rel="preload" href="' . DOMAIN . ltrim($urlPath, '/') . $file . '" as="style">';
      } elseif ($extension === 'js') {
        print '<link rel="preload" href="' . DOMAIN . ltrim($urlPath, '/') . $file . '" as="script" crossorigin>';
      }
    }

    foreach ($files as $file) {
      $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      if ($extension === 'css') {
        $tag = '<link rel="stylesheet" href="' . DOMAIN . ltrim($urlPath, '/') . $file . '"' .
          ($param ? ' ' . $param : '') . ' fetchpriority="high">';
        print $tag;
      } elseif ($extension === 'js') {
        $tag = '<script src="' . DOMAIN . ltrim($urlPath, '/') . $file . '"' .
          ($param ? ' ' . $param : '') . ' fetchpriority="high"></script>';
        print $tag;
      }
    }
  }

  /**
   * Carga una librería (o conjunto de archivos CSS y JS) desde App/Rsc/Library/{$rute}
   * Escanea automáticamente la carpeta para detectar y cargar archivos .css y .js
   * 
   * @param string $rute Nombre de la carpeta de la librería o archivo.
   * @param string|null $param Atributos adicionales para las etiquetas (ej. 'defer', 'async', etc.)
   */
  public function library(string $rute, ?string $param = null): void
  {
    $ruteClean = trim($rute, '/\\');
    
    // Posibles rutas físicas de la librería
    $searchPaths = [
      ROOT_PATH . '/App/Rsc/Library/' . $ruteClean,
      ROOT_PATH . '/App/Rcs/Library/' . $ruteClean,
      ROOT_PATH . '/vendor/eber/framework/Resources/Library/' . $ruteClean,
    ];

    $libDir = null;
    $urlBase = null;

    foreach ($searchPaths as $path) {
      if (is_dir($path)) {
        $libDir = $path;
        $urlBase = DOMAIN . ltrim($this->fsPathToUrl($path), '/') . '/';
        break;
      }
    }

    // Caso 1: Es una carpeta con archivos
    if ($libDir !== null) {
      $files = $this->getDirectoryFiles($libDir);
      
      $cssFiles = [];
      $jsFiles = [];

      foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === 'css') {
          $cssFiles[] = $file;
        } elseif ($ext === 'js') {
          $jsFiles[] = $file;
        }
      }

      // Excluir CSS redundante en librerías que ya inyectan sus estilos dinámicamente vía JS (ej. ApexCharts)
      $selfStyledLibs = ['apexcharts'];
      if (in_array(strtolower($ruteClean), $selfStyledLibs, true)) {
        $cssFiles = [];
      }

      // Ordenar JS: archivo principal (que coincide con el nombre de la librería o core) primero
      usort($jsFiles, function($a, $b) use ($ruteClean) {
        $aLower = strtolower($a);
        $bLower = strtolower($b);
        $ruteLower = strtolower($ruteClean);

        $aIsCore = (strpos($aLower, $ruteLower) !== false || strpos($aLower, 'core') !== false || strpos($aLower, 'main') !== false);
        $bIsCore = (strpos($bLower, $ruteLower) !== false || strpos($bLower, 'core') !== false || strpos($bLower, 'main') !== false);

        if ($aIsCore && !$bIsCore) return -1;
        if (!$aIsCore && $bIsCore) return 1;
        return strcmp($aLower, $bLower);
      });

      // 1. Inyección de CSS (sin preloads duplicados; el parser del navegador los procesa de inmediato en el <head>)
      foreach ($cssFiles as $css) {
        print '<link rel="stylesheet" href="' . $urlBase . $css . '"' . ($param ? ' ' . $param : '') . ' fetchpriority="high">';
      }

      // 2. Inyección de JS (con defer)
      $jsParam = $param ?? 'defer';
      foreach ($jsFiles as $js) {
        print '<script src="' . $urlBase . $js . '" ' . $jsParam . '></script>';
      }

      return;
    }

    // Caso 2: Es un archivo directo o compatibilidad anterior
    $fileName = pathinfo($ruteClean, PATHINFO_EXTENSION) ? $ruteClean : $ruteClean . '.js';
    $jsParam = $param ?? 'defer';
    $js = '<script src="' . DOMAIN . ltrim(URL_RESOURCE, '/') . 'Library/' . $fileName . '" ' . $jsParam . '></script>';
    print $js;
  }

  /**
   * Carga automáticamente todas las librerías configuradas en loadLibraryJsConfiguration.php
   * 
   * @param string|null $configFile Ruta al archivo de configuración
   */
  public function loadLibraries(?string $configFile = null): void
  {
    if ($configFile === null) {
      $configFile = ROOT_PATH . '/App/Config/loadLibraryJsConfiguration.php';
    }

    if (!file_exists($configFile)) {
      return;
    }

    $libraries = require $configFile;

    if (!is_array($libraries)) {
      return;
    }

    foreach ($libraries as $key => $value) {
      if (is_string($value)) {
        // Formato simple de lista: ['Gsap', 'ApexCharts']
        $this->library($value);
      } elseif (is_array($value)) {
        // Formato asociativo o con parámetros: ['Gsap' => ['param' => 'defer']]
        $libName = is_string($key) ? $key : ($value['name'] ?? null);
        $param = $value['param'] ?? ($value['attr'] ?? null);
        if ($libName) {
          $this->library($libName, $param);
        }
      } elseif (is_string($key) && $value === true) {
        // Formato booleano: ['Gsap' => true, 'ApexCharts' => false]
        $this->library($key);
      }
    }
  }

  public function cdn_load($cdn)
  {
    print $cdn;
  }

  /**
   * Carga la biblioteca GSAP y plugins opcionales
   */
  public function gsapLoad(array $plugins = [], bool $preload = true): void
  {
    $basePath = ROUTE_RESOURCE . 'Library/Gsap/';
    $urlBase = ltrim(URL_RESOURCE, '/') . 'Library/Gsap/';
    $gsapCore = $basePath . 'gsap.min.js';

    if ($preload) {
      print '<link rel="preload" href="' . DOMAIN . $urlBase . 'gsap.min.js" as="script">';
      foreach ($plugins as $plugin) {
        $pluginFile = $basePath . $plugin . '.min.js';
        if (file_exists($pluginFile)) {
          print '<link rel="preload" href="' . DOMAIN . $urlBase . $plugin . '.min.js" as="script">';
        }
      }
    }

    print '<script src="' . DOMAIN . $urlBase . 'gsap.min.js" defer></script>';

    foreach ($plugins as $plugin) {
      $pluginFile = $basePath . $plugin . '.min.js';
      if (file_exists($pluginFile)) {
        print '<script src="' . DOMAIN . $urlBase . $plugin . '.min.js" defer></script>';
      }
    }

    if (!empty($plugins)) {
      print "<script>";
      print "document.addEventListener('DOMContentLoaded', function() {";
      print "  if (typeof gsap !== 'undefined') {";
      foreach ($plugins as $plugin) {
        print "    if (typeof {$plugin} !== 'undefined') gsap.registerPlugin({$plugin});";
      }
      print "  }";
      print "});";
      print "</script>";
    }
  }

  /**
   * Carga e incrusta la configuración JSON unificada directamente en el HTML
   */
  public function inlineJsConfig(?string $configFile = null)
  {
    if ($configFile === null) {
      $configFile = ROOT_PATH . '/jsConfig.json';
    }

    if (!file_exists($configFile)) {
      echo "<!-- Error: $configFile no encontrado -->";
      return;
    }

    $config = file_get_contents($configFile);
    print "<script>\n";
    print "window.jsConfig = " . $config . ";\n";
    print "</script>\n";
  }

  /**
   * @deprecated Usar inlineJsConfig() en su lugar
   */
  public function inlineJsonConfig($asyncFile, $deferFile)
  {
    if (file_exists(ROOT_PATH . '/jsConfig.json')) {
      $this->inlineJsConfig(ROOT_PATH . '/jsConfig.json');
      return;
    }

    print "<script>\n";
    print "window.asyncConfig = " . file_get_contents($asyncFile) . ";\n";
    print "window.deferConfig = " . file_get_contents($deferFile) . ";\n";
    print "</script>\n";
  }

  /**
   * Inyecta la directiva modulepreload para el JS minificado principal,
   * eliminando el delay en el descubrimiento por import de loader.js.
   */
  public function modulePreloadJs(string $dir = '')
  {
    if (empty($dir)) {
      $dir = ROOT_PATH . '/App/Public/Min/Js/';
    }

    if (is_dir($dir)) {
      $files = $this->getDirectoryFiles($dir);
      $urlPath = $this->fsPathToUrl($dir);
      foreach ($files as $file) {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'js') {
          print '<link rel="modulepreload" href="' . DOMAIN . ltrim($urlPath, '/') . $file . '" crossorigin>';
        }
      }
    }
  }
}
