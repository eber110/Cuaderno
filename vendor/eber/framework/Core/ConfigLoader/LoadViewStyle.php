<?php

namespace Core\ConfigLoader;

class LoadViewStyle
{
  /**
   * Convierte una ruta de filesystem a una URL relativa.
   */
  private function fsPathToUrl(string $fsPath): string
  {
    return str_replace(ROOT_PATH, '', str_replace('\\', '/', $fsPath));
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

  public function ruteFont($ruteFont = null, $param = '')
  {
    $foundFonts = [];
    
    // 1. Escanear fuentes declaradas explícitamente en font-project.css si existe
    $fontProjectCss = ROOT_PATH . '/App/Public/Css/font-project.css';
    if (file_exists($fontProjectCss)) {
      $cssContent = file_get_contents($fontProjectCss);
      if (preg_match_all('/url\([\'"]?([^\'")]+)[\'"]?\)/i', $cssContent, $matches)) {
        foreach ($matches[1] as $fontUrl) {
          $fontPathClean = '/' . ltrim(parse_url($fontUrl, PHP_URL_PATH), '/');
          $ext = strtolower(pathinfo($fontPathClean, PATHINFO_EXTENSION));
          $foundFonts[] = [
            'path' => $fontPathClean,
            'name' => strtolower(basename($fontPathClean)),
            'ext'  => $ext,
            'isCustom' => true
          ];
        }
      }
    }

    // 2. Escanear el directorio físico de fuentes
    $fontsDirs = [
      ROOT_PATH . '/App/Rsc/Fonts',
      ROOT_PATH . '/App/Rsc/Font'
    ];

    $validExtensions = ['woff2', 'woff', 'ttf', 'otf'];

    foreach ($fontsDirs as $dir) {
      if (is_dir($dir)) {
        try {
          $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
          );
          foreach ($iterator as $file) {
            $ext = strtolower($file->getExtension());
            if ($file->isFile() && in_array($ext, $validExtensions)) {
              $realPath = str_replace('\\', '/', $file->getRealPath());
              $rootPathClean = str_replace('\\', '/', ROOT_PATH);
              $relativePath = '/' . ltrim(str_replace($rootPathClean, '', $realPath), '/');
              $foundFonts[] = [
                'path' => $relativePath,
                'name' => strtolower($file->getFilename()),
                'ext'  => $ext,
                'isCustom' => (strpos($relativePath, 'Alumini-sans') === false && strpos($relativePath, 'Roboto-condensed') === false)
              ];
            }
          }
        } catch (\Exception $e) {
          // Ignorar de forma segura cualquier error de escaneo
        }
      }
    }

    // Si hay fuentes personalizadas del proyecto, darles prioridad sobre las fuentes por defecto
    $customFonts = array_filter($foundFonts, fn($f) => $f['isCustom']);
    $targetFonts = !empty($customFonts) ? array_values($customFonts) : $foundFonts;

    // Preferir .woff2 cuando existan múltiples formatos del mismo archivo
    $groupedByName = [];
    foreach ($targetFonts as $font) {
      $baseName = pathinfo($font['name'], PATHINFO_FILENAME);
      if (!isset($groupedByName[$baseName]) || $font['ext'] === 'woff2') {
        $groupedByName[$baseName] = $font;
      }
    }

    $fontsToPreload = array_values(array_map(fn($f) => $f['path'], $groupedByName));

    // Filtrado inteligente para evitar congestión de red (Core Web Vitals Boost)
    if (count($fontsToPreload) > 5) {
      $criticalPreloads = [];
      foreach ($fontsToPreload as $fontPath) {
        $lowercaseName = strtolower(basename($fontPath));
        if (
          (strpos($lowercaseName, 'regular') !== false || 
           strpos($lowercaseName, 'normal') !== false || 
           strpos($lowercaseName, 'medium') !== false || 
           strpos($lowercaseName, 'bold') !== false || 
           strpos($lowercaseName, '400') !== false || 
           strpos($lowercaseName, '500') !== false || 
           strpos($lowercaseName, '700') !== false) && 
          strpos($lowercaseName, 'italic') === false
        ) {
          $criticalPreloads[] = $fontPath;
        }
      }

      if (empty($criticalPreloads)) {
        foreach ($fontsToPreload as $fontPath) {
          $lowercaseName = strtolower(basename($fontPath));
          if (strpos($lowercaseName, 'italic') === false) {
            $criticalPreloads[] = $fontPath;
          }
        }
      }

      $fontsToPreload = array_slice(!empty($criticalPreloads) ? $criticalPreloads : $fontsToPreload, 0, 5);
    }

    // Fallback inteligente si no hay fuentes encontradas en el proyecto
    if (empty($fontsToPreload)) {
      $fontsToPreload = [
        '/App/Rsc/Fonts/Roboto-condensed/Roboto_Condensed-Regular.woff2',
        '/App/Rsc/Fonts/Alumini-sans/AlumniSansSC-Regular.woff2',
      ];
    }

    foreach ($fontsToPreload as $fontPath) {
      $fullPath = ROOT_PATH . '/' . ltrim($fontPath, '/');
      if (file_exists($fullPath)) {
        $mimeType = $this->getFontMimeType($fontPath);
        print '<link rel="preload" href="' . URL . ltrim($fontPath, '/') . '" as="font" type="' . $mimeType . '" crossorigin>';
      }
    }

    print "<script>\n";
    print "if ('fonts' in document) {\n";
    print "  document.fonts.ready.then(function() {\n";
    print "    document.documentElement.classList.add('fonts-loaded');\n";
    print "  });\n";
    print "}\n";
    print "</script>\n";
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

  public function library($rute, $param = null)
  {
    $js = '<script src="' . DOMAIN . ltrim(URL_RESOURCE, '/') . 'Library/' . $rute . '.js' .
      ($param ? ' ' . $param : '') . '"></script>';
    print $js;
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
