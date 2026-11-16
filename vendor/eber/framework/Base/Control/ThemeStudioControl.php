<?php

namespace Base\Control;

use Base\Module\GraphicsModule;
use Base\Module\ThemeModule;

/**
 * Controlador de Theme Studio (Herramienta Visual Interactiva de Frame).
 *
 * Permite diseñar, previsualizar en tiempo real y persistir paletas cromáticas,
 * tipografías y estilos de layout sin escribir CSS manualmente.
 * Disponible únicamente bajo entornos de desarrollo (local/dev/testing).
 */
class ThemeStudioControl extends Control
{
  private static bool $routesRegistered = false;

  /**
   * Lista canónica de los 12 gráficos soportados por GraphicsModule.
   */
  public const CHART_TYPES = [
    'arcMeter',
    'stackedTones',
    'tileTreemap',
    'hybridSpline',
    'pillPillars',
    'simpleBars',
    'horizontalBars',
    'bulletTarget',
    'roundedDonut',
    'pyramidStack',
    'splineDynamics',
    'matrixHeatmap',
  ];

  /**
   * Registra las rutas de desarrollo para Theme Studio si aún no han sido registradas.
   */
  public static function registerRoutes(): void
  {
    if (self::$routesRegistered) {
      return;
    }
    self::$routesRegistered = true;

    \Core\Route::get('/_frame/theme-studio', [self::class, 'index']);
    \Core\Route::post('/_frame/theme-studio/save', [self::class, 'save']);
    \Core\Route::post('/_frame/theme-studio/load-to-site', [self::class, 'loadToSite']);
    \Core\Route::post('/_frame/theme-studio/set-active', [self::class, 'setActive']);
    \Core\Route::post('/_frame/theme-studio/delete-theme', [self::class, 'deleteThemeEndpoint']);
    \Core\Route::post('/_frame/theme-studio/render-chart', [self::class, 'renderChart']);
    \Core\Route::get('/_frame/theme-studio/fonts', [self::class, 'fonts']);
  }

  /**
   * Verifica si la ejecución actual se encuentra en un entorno de desarrollo seguro.
   *
   * @return bool True si es local, dev o testing.
   */
  public static function isDevEnvironment(): bool
  {
    if (!defined('ENVIRONMENT')) {
      return true;
    }
    $env = strtolower((string)ENVIRONMENT);
    return in_array($env, ['local', 'development', 'dev', 'testing', 'test'], true);
  }

  /**
   * Obtiene la lista de fuentes instaladas y pilas de fuentes del sistema disponibles.
   *
   * @return array Lista de fuentes con nombre y valor CSS
   */
  public static function getInstalledFonts(): array
  {
    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $fonts = [
      [
        'name' => 'Sistema (System UI Stack)',
        'value' => "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif"
      ],
      [
        'name' => 'Inter / Sans Moderno',
        'value' => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
      ],
      [
        'name' => 'Roboto / Android Stack',
        'value' => "'Roboto', 'Helvetica Neue', Arial, sans-serif"
      ],
      [
        'name' => 'Segoe UI / Windows Stack',
        'value' => "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
      ],
      [
        'name' => 'Serif Editorial (Georgia)',
        'value' => "Georgia, 'Times New Roman', Times, serif"
      ],
      [
        'name' => 'Monoespaciada (Code / Terminal)',
        'value' => "'Fira Code', 'Cascadia Code', Consolas, 'Courier New', monospace"
      ],
    ];

    // Detectar fuentes en font-project.css
    $fontCssPath = $basePath . '/App/Public/Css/font-project.css';
    if (is_file($fontCssPath)) {
      $cssContent = (string)file_get_contents($fontCssPath);
      if (preg_match_all('/font-family\s*:\s*[\'"]?([^\'";}\r\n]+)[\'"]?/i', $cssContent, $matches)) {
        $found = array_unique(array_map('trim', $matches[1]));
        foreach ($found as $fontName) {
          if (!empty($fontName)) {
            array_unshift($fonts, [
              'name' => "⭐ {$fontName} (Instalada en proyecto)",
              'value' => "'{$fontName}', system-ui, sans-serif"
            ]);
          }
        }
      }
    }

    // Detectar fuentes en App/Rsc/Fonts/
    $fontsDir = $basePath . '/App/Rsc/Fonts';
    if (is_dir($fontsDir)) {
      $fontFiles = glob($fontsDir . '/*.{woff2,ttf,otf}', GLOB_BRACE) ?: [];
      foreach ($fontFiles as $file) {
        $fontName = pathinfo($file, PATHINFO_FILENAME);
        $fontNameClean = str_replace(['-', '_'], ' ', $fontName);
        $already = false;
        foreach ($fonts as $f) {
          if (str_contains($f['name'], $fontNameClean)) {
            $already = true;
            break;
          }
        }
        if (!$already) {
          $fonts[] = [
            'name' => "📦 {$fontNameClean} (Archivo en App/Rsc/Fonts)",
            'value' => "'{$fontNameClean}', system-ui, sans-serif"
          ];
        }
      }
    }

    return $fonts;
  }

  /**
   * Obtiene las rutas GET registradas en el proyecto (App/Route) para previsualizar en vivo.
   * Garantiza que "/" sea siempre la primera opción como vista principal del proyecto.
   *
   * @return array<int, array{uri: string, label: string}>
   */
  public static function getProjectRoutes(): array
  {
    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $routeDir = $basePath . '/App/Route';

    $currentRoutes = \Core\Route::getRoutes();
    $hasAppRoutes = false;
    foreach ($currentRoutes as $r) {
      if (!str_starts_with($r['uri'] ?? '', '/_frame/')) {
        $hasAppRoutes = true;
        break;
      }
    }

    if (!$hasAppRoutes && is_dir($routeDir) && class_exists('\\Core\\ConfigLoader\\RouteLoader')) {
      \Core\ConfigLoader\RouteLoader::load($routeDir);
      $currentRoutes = \Core\Route::getRoutes();
    }

    $routes = [
      '/' => [
        'uri' => '/',
        'label' => '/ (Vista Principal)'
      ]
    ];

    $excluded = ['/sitemap.xml', '/robots.txt'];

    foreach ($currentRoutes as $r) {
      $method = strtoupper((string)($r['method'] ?? ''));
      $uri = (string)($r['uri'] ?? '');

      if ($method !== 'GET' || $uri === '' || $uri === '/') {
        continue;
      }
      if (str_starts_with($uri, '/_frame/') || in_array($uri, $excluded, true)) {
        continue;
      }

      if (!isset($routes[$uri])) {
        $routes[$uri] = [
          'uri' => $uri,
          'label' => $uri
        ];
      }
    }

    return array_values($routes);
  }

  /**
   * Renderiza la interfaz gráfica de Theme Studio.
   */
  public function index(): void
  {
    if (!self::isDevEnvironment()) {
      \Core\ErrorHandler::handleCode(403, 403, 'Theme Studio solo está disponible en entorno de desarrollo local.');
      return;
    }

    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $jsonPath = $basePath . '/App/Config/theme.json';

    // Obtener la configuración por defecto leyendo directamente de App/Public/Css/_configTheme.css
    $defaultCssConfig = ThemeModule::extractConfigFromCss(null, $basePath);

    // Cargar configuración completa multi-tema o inicializarla
    if (is_file($jsonPath)) {
      $fullConfig = ThemeModule::loadFullConfig($jsonPath);
    } else {
      $fullConfig = [
        'activeTheme' => 'default',
        'themes' => [
          'default' => array_merge(
            ['name' => 'Predeterminado', 'slug' => 'default', 'schedule' => null],
            $defaultCssConfig
          )
        ]
      ];
    }

    $activeThemeSlug = ThemeModule::resolveActiveThemeSlug($fullConfig);
    $config = $fullConfig['themes'][$activeThemeSlug] ?? $fullConfig['themes']['default'] ?? $defaultCssConfig;
    $config['themeSlug'] = $activeThemeSlug;
    $config['activeTheme'] = $fullConfig['activeTheme'] ?? 'default';

    $fonts = self::getInstalledFonts();
    $projectRoutes = self::getProjectRoutes();
    $chartsAssets = self::getChartsLibraryAssets($basePath);
    $initialChartsGlobal = isset($config['charts']) && is_array($config['charts'])
      ? $config['charts']
      : ThemeModule::getDefaultChartsConfig();
    $initialChartsLocalMap = isset($initialChartsGlobal['localOverrides']) && is_array($initialChartsGlobal['localOverrides'])
      ? $initialChartsGlobal['localOverrides']
      : [];
    $initialChartsRender = self::renderChartPayload([
      'type' => 'all',
      'globalConfig' => $initialChartsGlobal,
      'localParamsByType' => $initialChartsLocalMap,
      'datasetPreset' => 'standard',
    ]);

    $viewPath = dirname(__DIR__) . '/Views/themeStudio.php';
    if (!file_exists($viewPath)) {
      echo '<h1>Error: No se encontró la vista de Theme Studio</h1>';
      return;
    }

    extract([
      'config' => $config,
      'fullConfig' => $fullConfig,
      'allThemes' => $fullConfig['themes'] ?? [],
      'activeThemeSlug' => $activeThemeSlug,
      'defaultConfig' => $defaultCssConfig,
      'fonts' => $fonts,
      'projectRoutes' => $projectRoutes,
      'chartsCss' => $chartsAssets['css'],
      'chartsJs' => $chartsAssets['js'],
      'initialChartsHtmlMap' => $initialChartsRender['charts'] ?? [],
      'initialChartsSampleData' => $initialChartsRender['sampleData'] ?? [],
      'rawJson' => json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
      'rawFullJson' => json_encode($fullConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
      'rawDefaultJson' => json_encode($defaultCssConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);

    require $viewPath;
  }

  /**
   * Endpoint API para guardar el tema y las paletas en theme.json vía POST (modo aislado).
   * Persiste la configuración sin alterar _configTheme.css ni compilar los assets del sitio real.
   */
  public function save(): void
  {
    if (!self::isDevEnvironment()) {
      $this->json([
        'success' => false,
        'message' => 'Acceso restringido: Theme Studio no está disponible en producción.'
      ], 403);
      return;
    }

    $rawInput = (string)file_get_contents('php://input');
    if (trim($rawInput) === '') {
      $this->json([
        'success' => false,
        'message' => 'Cuerpo de la petición vacío.'
      ], 400);
      return;
    }

    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
      $this->json([
        'success' => false,
        'message' => 'Formato JSON no válido.'
      ], 400);
      return;
    }

    // Guardar en App/Config/theme.json de forma aislada
    $saved = ThemeModule::saveConfig($data);
    if (!$saved) {
      $this->json([
        'success' => false,
        'message' => 'No se pudo escribir en App/Config/theme.json. Verifique permisos de escritura.'
      ], 500);
      return;
    }

    // NOTA: No compilamos a _configTheme.css ni minificamos aquí.
    // Theme Studio opera en modo aislado: la previsualización se realiza en el iframe mediante variables en vivo.
    // Para aplicar el tema al sitio web real, se utiliza el botón '🚀 Cargar' en la pestaña Temas.

    $fullConfig = ThemeModule::loadFullConfig();
    $activeSlug = ThemeModule::resolveActiveThemeSlug($fullConfig);

    $this->json([
      'success' => true,
      'message' => 'Tema y paletas guardados en theme.json (modo aislado, sin compilar al sitio web).',
      'fullConfig' => $fullConfig,
      'activeThemeSlug' => $activeSlug,
    ]);
  }

  /**
   * Carga y aplica un tema al sitio web real (compila _configTheme.css y regenera el bundle CSS minificado).
   */
  public function loadToSite(): void
  {
    if (!self::isDevEnvironment()) {
      $this->json(['success' => false, 'message' => 'No autorizado'], 403);
      return;
    }

    $rawInput = (string)file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: [];
    $slug = trim((string)($data['slug'] ?? 'default'));

    // Si viene la configuración completa actualizada desde el editor, persistirla primero
    if (!empty($data['fullConfig']) && is_array($data['fullConfig'])) {
      ThemeModule::saveConfig($data['fullConfig']);
    }

    // Actualizar tema activo
    $updated = ThemeModule::setActiveTheme($slug);
    if (!$updated) {
      $this->json(['success' => false, 'message' => "No se pudo activar el tema '{$slug}'."], 400);
      return;
    }

    // Ahora sí: compilar a App/Public/Css/_configTheme.css
    $compiled = ThemeModule::compile();

    // Sincronizar JIT y regenerar CSS minificado del sitio real
    $this->triggerAssetMinification();

    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $fullConfig = ThemeModule::loadFullConfig();
    $activeSlug = ThemeModule::resolveActiveThemeSlug($fullConfig);

    $this->json([
      'success' => true,
      'message' => "¡Tema '{$slug}' cargado y aplicado con éxito al sitio web!",
      'path' => $basePath . '/App/Public/Css/_configTheme.css',
      'fullConfig' => $fullConfig,
      'activeThemeSlug' => $activeSlug,
      'compiled' => $compiled['updated'] ?? true,
    ]);
  }

  /**
   * Establece el tema activo (o 'auto' para calendario) vía POST y lo compila al sitio.
   */
  public function setActive(): void
  {
    $this->loadToSite();
  }

  /**
   * Elimina un tema guardado vía POST.
   */
  public function deleteThemeEndpoint(): void
  {
    if (!self::isDevEnvironment()) {
      $this->json(['success' => false, 'message' => 'No autorizado'], 403);
      return;
    }

    $rawInput = (string)file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: [];
    $slug = trim((string)($data['slug'] ?? ''));

    if ($slug === '' || $slug === 'default') {
      $this->json(['success' => false, 'message' => 'No se puede eliminar el tema predeterminado.'], 400);
      return;
    }

    $deleted = ThemeModule::deleteTheme($slug);
    if (!$deleted) {
      $this->json(['success' => false, 'message' => 'El tema no existe o no se pudo eliminar.'], 400);
      return;
    }

    ThemeModule::compile();
    $this->triggerAssetMinification();

    $fullConfig = ThemeModule::loadFullConfig();
    $activeSlug = ThemeModule::resolveActiveThemeSlug($fullConfig);

    $this->json([
      'success' => true,
      'message' => "Tema '{$slug}' eliminado con éxito.",
      'fullConfig' => $fullConfig,
      'activeThemeSlug' => $activeSlug,
    ]);
  }

  /**
   * Sincroniza variables JIT y regenera el CSS minificado.
   */
  private function triggerAssetMinification(): void
  {
    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $cssThemePath = $basePath . '/App/Public/Css/_configTheme.css';

    if (is_file($cssThemePath) && class_exists('\\Base\\Module\\JitCssModule') && class_exists('\\Base\\Module\\MinifyModule')) {
      ob_start();
      try {
        if (class_exists('\\Base\\LibraryCssJit\\JitRuleBase')) {
          \Base\LibraryCssJit\JitRuleBase::resetCache();
        }
        \Base\Module\JitCssModule::syncThemeConfigVariables($cssThemePath, false);

        $cssPaths = [];
        if (is_dir($basePath . '/vendor/eber/framework/Resources/Css')) {
          $cssPaths[] = $basePath . '/vendor/eber/framework/Resources/Css';
        } elseif (is_dir($basePath . '/Resources/Css')) {
          $cssPaths[] = $basePath . '/Resources/Css';
        }
        $cssPaths[] = $basePath . '/App/Public/Css/';

        $purgePaths = array_filter([
          $basePath . '/App',
          $basePath . '/Base',
          $basePath . '/Core',
          $basePath . '/Resources',
          $basePath . '/vendor/eber/framework/App',
          $basePath . '/vendor/eber/framework/Base',
          $basePath . '/vendor/eber/framework/Core',
          $basePath . '/vendor/eber/framework/Resources',
        ], 'is_dir');

        $jitOutPath = $basePath . '/App/Public/Css/jit-compiled.css';
        \Base\Module\JitCssModule::generateJitCss($purgePaths, $jitOutPath, false);
        $cssPaths[] = $jitOutPath;

        \Base\Module\MinifyModule::minifyCss(
          $cssPaths,
          $basePath . '/App/Public/Min/Css/css.min.css',
          false
        );
      } catch (\Throwable $e) {
        // Continuar sin bloquear la respuesta
      } finally {
        if (ob_get_level() > 0) {
          ob_end_clean();
        }
      }
    }
  }

  /**
   * Localiza y empaqueta el CSS y el bundle JS autocontenido de Charts (GraphicsModule)
   * para poder ejecutarlos directamente dentro del laboratorio de Theme Studio.
   *
   * @param string|null $basePath Directorio base del proyecto
   * @return array{css: string, js: string}
   */
  public static function getChartsLibraryAssets(?string $basePath = null): array
  {
    $base = ThemeModule::resolveBasePath($basePath);
    $candidates = [
      $base . '/App/Rsc/Library/Charts',
      $base . '/Resources/Library/Charts',
      $base . '/vendor/eber/framework/Resources/Library/Charts',
    ];

    $chartsDir = null;
    foreach ($candidates as $dir) {
      if (is_dir($dir) && is_file($dir . '/charts.css') && is_file($dir . '/charts.js')) {
        $chartsDir = $dir;
        break;
      }
    }

    if ($chartsDir === null) {
      return ['css' => '', 'js' => ''];
    }

    $css = (string)@file_get_contents($chartsDir . '/charts.css');
    $orderedFiles = [
      'arcMeter.js',
      'stackedTones.js',
      'tileTreemap.js',
      'hybridSpline.js',
      'pillPillars.js',
      'simpleBars.js',
      'horizontalBars.js',
      'bulletTarget.js',
      'roundedDonut.js',
      'pyramidStack.js',
      'splineDynamics.js',
      'matrixHeatmap.js',
      'tooltip.js',
      'charts.js',
    ];

    $bundleParts = ["(function() {\n\"use strict\";"];
    foreach ($orderedFiles as $file) {
      $filePath = $chartsDir . '/' . $file;
      if (!is_file($filePath)) {
        continue;
      }
      $code = (string)@file_get_contents($filePath);
      $code = (string)preg_replace('/^\s*import\s+[\s\S]*?from\s+["\'][^"\']+["\'];?\s*$/m', '', $code);
      $code = (string)preg_replace('/^\s*export\s+\{[\s\S]*?\};?\s*$/m', '', $code);
      $code = (string)preg_replace('/^\s*export\s+(?=(?:async\s+)?function\b|const\b|let\b|var\b|class\b)/m', '', $code);
      $code = str_ireplace('</script', '<\/script', $code);
      $bundleParts[] = "// --- {$file} ---\n" . trim($code);
    }
    $bundleParts[] = "})();";

    return [
      'css' => $css,
      'js' => implode("\n\n", $bundleParts),
    ];
  }

  /**
   * Devuelve el conjunto de datos de prueba para un gráfico y preset dados.
   *
   * @param string $type Nombre del método de GraphicsModule
   * @param string $preset Preset ('standard', 'compact', 'dense', 'long-labels')
   * @return array Datos formateados para GraphicsModule::$type()
   */
  public static function getPresetDataset(string $type, string $preset = 'standard'): array
  {
    $preset = in_array($preset, ['standard', 'compact', 'dense', 'long-labels'], true) ? $preset : 'standard';

    switch ($type) {
      case 'arcMeter':
        return match ($preset) {
          'compact' => ['value' => 72, 'label' => 'CPU Load'],
          'dense' => ['value' => 98, 'label' => 'Peak Throughput'],
          'long-labels' => ['value' => 84, 'label' => 'Capacidad de Carga del Servidor'],
          default => ['value' => 90, 'label' => 'Optimal Load'],
        };

      case 'stackedTones':
        return match ($preset) {
          'compact' => [
            'quarters' => [
              ['label' => 'Q1', 'white' => 45, 'mid' => 30, 'dark' => 20, 'total' => 95],
              ['label' => 'Q2', 'white' => 55, 'mid' => 38, 'dark' => 27, 'total' => 120],
              ['label' => 'Q3', 'white' => 68, 'mid' => 45, 'dark' => 32, 'total' => 145],
            ],
          ],
          'dense' => [
            'quarters' => [
              ['label' => 'Ene', 'white' => 30, 'mid' => 20, 'dark' => 15, 'total' => 65],
              ['label' => 'Feb', 'white' => 42, 'mid' => 28, 'dark' => 18, 'total' => 88],
              ['label' => 'Mar', 'white' => 50, 'mid' => 35, 'dark' => 25, 'total' => 110],
              ['label' => 'Abr', 'white' => 48, 'mid' => 30, 'dark' => 20, 'total' => 98],
              ['label' => 'May', 'white' => 62, 'mid' => 42, 'dark' => 30, 'total' => 134],
              ['label' => 'Jun', 'white' => 70, 'mid' => 48, 'dark' => 34, 'total' => 152],
              ['label' => 'Jul', 'white' => 58, 'mid' => 36, 'dark' => 24, 'total' => 118],
              ['label' => 'Ago', 'white' => 66, 'mid' => 44, 'dark' => 31, 'total' => 141],
            ],
          ],
          'long-labels' => [
            'quarters' => [
              ['label' => 'Primer Trimestre', 'white' => 45, 'mid' => 30, 'dark' => 20, 'total' => 95],
              ['label' => 'Segundo Trimestre', 'white' => 50, 'mid' => 40, 'dark' => 30, 'total' => 120],
              ['label' => 'Tercer Trimestre', 'white' => 70, 'mid' => 50, 'dark' => 35, 'total' => 155],
              ['label' => 'Cuarto Trimestre', 'white' => 55, 'mid' => 35, 'dark' => 25, 'total' => 115],
            ],
          ],
          default => [
            'quarters' => [
              ['label' => 'Q1', 'white' => 45, 'mid' => 30, 'dark' => 20, 'total' => 95],
              ['label' => 'Q2', 'white' => 50, 'mid' => 40, 'dark' => 30, 'total' => 120],
              ['label' => 'Q3', 'white' => 70, 'mid' => 50, 'dark' => 35, 'total' => 155],
              ['label' => 'Q4', 'white' => 55, 'mid' => 35, 'dark' => 25, 'total' => 115],
            ],
          ],
        };

      case 'tileTreemap':
        return match ($preset) {
          'compact' => [
            'tiles' => [
              ['label' => 'Core', 'pct' => 55, 'tag' => 'Engine'],
              ['label' => 'UI Layer', 'pct' => 30, 'tag' => 'Views'],
              ['label' => 'API', 'pct' => 15, 'tag' => 'REST'],
            ],
          ],
          'dense' => [
            'tiles' => [
              ['label' => 'Storage', 'pct' => 26, 'tag' => 'NVMe'],
              ['label' => 'Compute', 'pct' => 20, 'tag' => 'vCPU'],
              ['label' => 'Cache', 'pct' => 14, 'tag' => 'Redis'],
              ['label' => 'Network', 'pct' => 12, 'tag' => 'CDN'],
              ['label' => 'Auth', 'pct' => 10, 'tag' => 'OAuth2'],
              ['label' => 'Queue', 'pct' => 8, 'tag' => 'Workers'],
              ['label' => 'Logs', 'pct' => 6, 'tag' => 'Audit'],
              ['label' => 'Search', 'pct' => 4, 'tag' => 'Index'],
            ],
          ],
          'long-labels' => [
            'tiles' => [
              ['label' => 'Almacenamiento Principal', 'pct' => 45, 'tag' => 'Volumen NVMe'],
              ['label' => 'Cómputo Distribuido', 'pct' => 30, 'tag' => 'Nodos Activos'],
              ['label' => 'Red y Balanceo', 'pct' => 15, 'tag' => 'Tráfico Global'],
              ['label' => 'Caché en Memoria', 'pct' => 10, 'tag' => 'Baja Latencia'],
            ],
          ],
          default => [
            'tiles' => [
              ['label' => 'Storage', 'pct' => 45, 'tag' => 'Primary Vol'],
              ['label' => 'Compute', 'pct' => 30, 'tag' => 'vCPU Units'],
              ['label' => 'Network', 'pct' => 15, 'tag' => 'Egress GB'],
              ['label' => 'Cache', 'pct' => 10, 'tag' => 'In-Memory'],
            ],
          ],
        };

      case 'hybridSpline':
        return match ($preset) {
          'compact' => [
            'values' => [48, 82, 65],
            'labels' => ['Ene', 'Feb', 'Mar'],
          ],
          'dense' => [
            'values' => [28, 45, 62, 54, 78, 85, 70, 92, 88, 96],
            'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct'],
          ],
          'long-labels' => [
            'values' => [42, 68, 55, 92, 74],
            'labels' => ['Enero 2026', 'Febrero 2026', 'Marzo 2026', 'Abril 2026', 'Mayo 2026'],
          ],
          default => [
            'values' => [42, 68, 55, 92, 74],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
          ],
        };

      case 'pillPillars':
        return match ($preset) {
          'compact' => [
            'pairs' => [
              ['label' => 'S1', 'v1' => 75, 'v2' => 42],
              ['label' => 'S2', 'v1' => 92, 'v2' => 60],
              ['label' => 'S3', 'v1' => 68, 'v2' => 48],
            ],
          ],
          'dense' => [
            'pairs' => [
              ['label' => 'S1', 'v1' => 65, 'v2' => 38],
              ['label' => 'S2', 'v1' => 78, 'v2' => 50],
              ['label' => 'S3', 'v1' => 84, 'v2' => 62],
              ['label' => 'S4', 'v1' => 58, 'v2' => 35],
              ['label' => 'S5', 'v1' => 90, 'v2' => 68],
              ['label' => 'S6', 'v1' => 74, 'v2' => 49],
              ['label' => 'S7', 'v1' => 82, 'v2' => 55],
              ['label' => 'S8', 'v1' => 95, 'v2' => 72],
            ],
          ],
          'long-labels' => [
            'pairs' => [
              ['label' => 'Semana Uno', 'v1' => 75, 'v2' => 40],
              ['label' => 'Semana Dos', 'v1' => 95, 'v2' => 60],
              ['label' => 'Semana Tres', 'v1' => 60, 'v2' => 30],
              ['label' => 'Semana Cuatro', 'v1' => 88, 'v2' => 52],
            ],
          ],
          default => [
            'pairs' => [
              ['label' => 'W1', 'v1' => 75, 'v2' => 40],
              ['label' => 'W2', 'v1' => 95, 'v2' => 60],
              ['label' => 'W3', 'v1' => 60, 'v2' => 30],
              ['label' => 'W4', 'v1' => 88, 'v2' => 52],
            ],
          ],
        };

      case 'simpleBars':
        return match ($preset) {
          'compact' => [
            'bars' => [
              ['label' => 'Lun', 'value' => 11.5, 'display' => '11:30'],
              ['label' => 'Mié', 'value' => 18.0, 'display' => '18:00'],
              ['label' => 'Vie', 'value' => 21.0, 'display' => '21:00'],
            ],
          ],
          'dense' => [
            'bars' => [
              ['label' => 'D1', 'value' => 6.0, 'display' => '06:00'],
              ['label' => 'D2', 'value' => 9.5, 'display' => '09:30'],
              ['label' => 'D3', 'value' => 13.0, 'display' => '13:00'],
              ['label' => 'D4', 'value' => 16.5, 'display' => '16:30'],
              ['label' => 'D5', 'value' => 19.0, 'display' => '19:00'],
              ['label' => 'D6', 'value' => 12.0, 'display' => '12:00'],
              ['label' => 'D7', 'value' => 15.5, 'display' => '15:30'],
              ['label' => 'D8', 'value' => 22.0, 'display' => '22:00'],
              ['label' => 'D9', 'value' => 17.5, 'display' => '17:30'],
              ['label' => 'D10', 'value' => 20.5, 'display' => '20:30'],
            ],
          ],
          'long-labels' => [
            'bars' => [
              ['label' => 'Lunes Mañana', 'value' => 9.0, 'display' => '09:00'],
              ['label' => 'Martes Tarde', 'value' => 15.0, 'display' => '15:00'],
              ['label' => 'Miércoles Noche', 'value' => 20.0, 'display' => '20:00'],
              ['label' => 'Jueves Día', 'value' => 12.5, 'display' => '12:30'],
              ['label' => 'Viernes Cierre', 'value' => 18.5, 'display' => '18:30'],
            ],
          ],
          default => [
            'bars' => [
              ['label' => 'Lun 12', 'value' => 8.5, 'display' => '08:30'],
              ['label' => 'Mar 13', 'value' => 14.0, 'display' => '14:00'],
              ['label' => 'Mié 14', 'value' => 19.5, 'display' => '19:30'],
              ['label' => 'Jue 15', 'value' => 11.0, 'display' => '11:00'],
              ['label' => 'Vie 16', 'value' => 21.0, 'display' => '21:00'],
              ['label' => 'Sáb 17', 'value' => 16.5, 'display' => '16:30'],
              ['label' => 'Dom 18', 'value' => 13.0, 'display' => '13:00'],
            ],
          ],
        };

      case 'horizontalBars':
        return match ($preset) {
          'compact' => [
            'bars' => [
              ['label' => 'Backend', 'value' => 8.2, 'display' => '8.2 hrs'],
              ['label' => 'Frontend', 'value' => 6.4, 'display' => '6.4 hrs'],
              ['label' => 'DevOps', 'value' => 4.9, 'display' => '4.9 hrs'],
            ],
          ],
          'dense' => [
            'bars' => [
              ['label' => '08:00', 'value' => 4.5, 'display' => '4.5 hrs'],
              ['label' => '10:00', 'value' => 6.2, 'display' => '6.2 hrs'],
              ['label' => '12:00', 'value' => 7.8, 'display' => '7.8 hrs'],
              ['label' => '14:00', 'value' => 8.5, 'display' => '8.5 hrs'],
              ['label' => '16:00', 'value' => 6.8, 'display' => '6.8 hrs'],
              ['label' => '18:00', 'value' => 5.4, 'display' => '5.4 hrs'],
              ['label' => '20:00', 'value' => 3.9, 'display' => '3.9 hrs'],
              ['label' => '22:00', 'value' => 2.6, 'display' => '2.6 hrs'],
              ['label' => '23:00', 'value' => 1.8, 'display' => '1.8 hrs'],
            ],
          ],
          'long-labels' => [
            'bars' => [
              ['label' => 'Autenticación y Control de Acceso', 'value' => 8.5, 'display' => '8.5 hrs'],
              ['label' => 'Procesamiento de Imágenes Batch', 'value' => 6.8, 'display' => '6.8 hrs'],
              ['label' => 'Generación de Reportes Mensuales', 'value' => 9.2, 'display' => '9.2 hrs'],
              ['label' => 'Sincronización de Inventario ERP', 'value' => 5.4, 'display' => '5.4 hrs'],
              ['label' => 'Notificaciones Push en Tiempo Real', 'value' => 7.1, 'display' => '7.1 hrs'],
            ],
          ],
          default => [
            'bars' => [
              ['label' => '08:00', 'value' => 4.5, 'display' => '4.5 hrs'],
              ['label' => '11:00', 'value' => 7.2, 'display' => '7.2 hrs'],
              ['label' => '14:00', 'value' => 8.0, 'display' => '8.0 hrs'],
              ['label' => '17:00', 'value' => 6.0, 'display' => '6.0 hrs'],
              ['label' => '20:00', 'value' => 3.5, 'display' => '3.5 hrs'],
            ],
          ],
        };

      case 'bulletTarget':
        return match ($preset) {
          'compact' => [
            'targets' => [
              ['label' => 'Ventas', 'value' => 88, 'target' => 80, 'suffix' => '%'],
              ['label' => 'Retención', 'value' => 74, 'target' => 82, 'suffix' => '%'],
              ['label' => 'Conversión', 'value' => 91, 'target' => 85, 'suffix' => '%'],
            ],
          ],
          'dense' => [
            'targets' => [
              ['label' => 'CPU', 'value' => 78, 'target' => 75, 'suffix' => '%'],
              ['label' => 'Memoria', 'value' => 64, 'target' => 80, 'suffix' => '%'],
              ['label' => 'Disco', 'value' => 86, 'target' => 85, 'suffix' => '%'],
              ['label' => 'Red', 'value' => 92, 'target' => 88, 'suffix' => '%'],
              ['label' => 'Caché', 'value' => 95, 'target' => 90, 'suffix' => '%'],
              ['label' => 'Cola', 'value' => 58, 'target' => 70, 'suffix' => '%'],
            ],
          ],
          'long-labels' => [
            'targets' => [
              ['label' => 'Rendimiento de Consultas SQL', 'value' => 82, 'target' => 75, 'suffix' => '%'],
              ['label' => 'Latencia Promedio de Respuesta', 'value' => 65, 'target' => 80, 'suffix' => '%'],
              ['label' => 'Disponibilidad del Clúster Web', 'value' => 95, 'target' => 90, 'suffix' => '%'],
            ],
          ],
          default => [
            'targets' => [
              ['label' => 'Throughput', 'value' => 82, 'target' => 75, 'suffix' => '%'],
              ['label' => 'Latency', 'value' => 65, 'target' => 80, 'suffix' => '%'],
              ['label' => 'Uptime', 'value' => 95, 'target' => 90, 'suffix' => '%'],
            ],
          ],
        };

      case 'roundedDonut':
        return match ($preset) {
          'compact' => [
            'items' => [
              ['label' => 'Web', 'value' => 50, 'suffix' => '%'],
              ['label' => 'Móvil', 'value' => 35, 'suffix' => '%'],
              ['label' => 'API', 'value' => 15, 'suffix' => '%'],
            ],
          ],
          'dense' => [
            'items' => [
              ['label' => 'Core', 'value' => 32, 'suffix' => '%'],
              ['label' => 'UI', 'value' => 22, 'suffix' => '%'],
              ['label' => 'Assets', 'value' => 16, 'suffix' => '%'],
              ['label' => 'Auth', 'value' => 12, 'suffix' => '%'],
              ['label' => 'Cache', 'value' => 10, 'suffix' => '%'],
              ['label' => 'Logs', 'value' => 8, 'suffix' => '%'],
            ],
          ],
          'long-labels' => [
            'items' => [
              ['label' => 'Motor Central', 'value' => 45, 'suffix' => '%'],
              ['label' => 'Interfaz Web', 'value' => 28, 'suffix' => '%'],
              ['label' => 'Multimedia', 'value' => 17, 'suffix' => '%'],
              ['label' => 'Complementos', 'value' => 10, 'suffix' => '%'],
            ],
          ],
          default => [
            'items' => [
              ['label' => 'Core Engine', 'value' => 45, 'suffix' => '%'],
              ['label' => 'UI Layer', 'value' => 28, 'suffix' => '%'],
              ['label' => 'Assets', 'value' => 17, 'suffix' => '%'],
              ['label' => 'Other', 'value' => 10, 'suffix' => '%'],
            ],
          ],
        };

      case 'pyramidStack':
        return match ($preset) {
          'compact' => [
            'items' => [
              ['label' => 'Dirección'],
              ['label' => 'Mandos Medios'],
              ['label' => 'Operaciones'],
            ],
          ],
          'dense' => [
            'items' => [
              ['label' => 'Nivel 1'],
              ['label' => 'Nivel 2'],
              ['label' => 'Nivel 3'],
              ['label' => 'Nivel 4'],
              ['label' => 'Nivel 5'],
              ['label' => 'Nivel 6'],
            ],
          ],
          'long-labels' => [
            'items' => [
              ['label' => 'Dirección Ejecutiva'],
              ['label' => 'Gerencia General'],
              ['label' => 'Equipo Senior Dev'],
              ['label' => 'Núcleo Operativo'],
            ],
          ],
          default => [
            'items' => [
              ['label' => 'Executive'],
              ['label' => 'Management'],
              ['label' => 'Senior Staff'],
              ['label' => 'Core Team'],
            ],
          ],
        };

      case 'splineDynamics':
        return match ($preset) {
          'compact' => [
            'primary' => [
              ['label' => 'Jan', 'value' => 30],
              ['label' => 'Feb', 'value' => 62],
              ['label' => 'Mar', 'value' => 84],
            ],
            'secondary' => [
              ['label' => 'Jan', 'value' => 20],
              ['label' => 'Feb', 'value' => 44],
              ['label' => 'Mar', 'value' => 58],
            ],
          ],
          'dense' => [
            'primary' => [
              ['label' => 'Ene', 'value' => 22],
              ['label' => 'Feb', 'value' => 36],
              ['label' => 'Mar', 'value' => 31],
              ['label' => 'Abr', 'value' => 52],
              ['label' => 'May', 'value' => 48],
              ['label' => 'Jun', 'value' => 68],
              ['label' => 'Jul', 'value' => 62],
              ['label' => 'Ago', 'value' => 79],
              ['label' => 'Sep', 'value' => 74],
              ['label' => 'Oct', 'value' => 92],
            ],
            'secondary' => [
              ['label' => 'Ene', 'value' => 15],
              ['label' => 'Feb', 'value' => 25],
              ['label' => 'Mar', 'value' => 24],
              ['label' => 'Abr', 'value' => 39],
              ['label' => 'May', 'value' => 37],
              ['label' => 'Jun', 'value' => 50],
              ['label' => 'Jul', 'value' => 46],
              ['label' => 'Ago', 'value' => 61],
              ['label' => 'Sep', 'value' => 58],
              ['label' => 'Oct', 'value' => 70],
            ],
          ],
          'long-labels' => [
            'primary' => [
              ['label' => 'Trim. 1', 'value' => 28],
              ['label' => 'Trim. 2', 'value' => 52],
              ['label' => 'Trim. 3', 'value' => 64],
              ['label' => 'Trim. 4', 'value' => 84],
              ['label' => 'Proyección', 'value' => 92],
            ],
            'secondary' => [
              ['label' => 'Trim. 1', 'value' => 18],
              ['label' => 'Trim. 2', 'value' => 36],
              ['label' => 'Trim. 3', 'value' => 48],
              ['label' => 'Trim. 4', 'value' => 62],
              ['label' => 'Proyección', 'value' => 70],
            ],
          ],
          default => [
            'primary' => [
              ['label' => 'Jan', 'value' => 25],
              ['label' => 'Feb', 'value' => 45],
              ['label' => 'Mar', 'value' => 38],
              ['label' => 'Apr', 'value' => 65],
              ['label' => 'May', 'value' => 52],
              ['label' => 'Jun', 'value' => 84],
            ],
            'secondary' => [
              ['label' => 'Jan', 'value' => 18],
              ['label' => 'Feb', 'value' => 32],
              ['label' => 'Mar', 'value' => 30],
              ['label' => 'Apr', 'value' => 48],
              ['label' => 'May', 'value' => 41],
              ['label' => 'Jun', 'value' => 60],
            ],
          ],
        };

      case 'matrixHeatmap':
        return match ($preset) {
          'compact' => [
            'rows' => ['Lun', 'Mié', 'Vie'],
            'cols' => ['08h', '10h', '12h', '15h', '18h'],
            'matrix' => [
              [0.0, 0.45, 0.80, 0.55, 0.30],
              [0.60, 0.90, 0.45, 0.0, 0.70],
              [0.35, 0.65, 0.95, 0.80, 0.40],
            ],
          ],
          'dense' => [
            'rows' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            'cols' => ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'S10'],
            'matrix' => [
              [0.0, 0.25, 0.45, 0.70, 0.90, 0.55, 0.40, 0.80, 0.60, 0.30],
              [0.45, 0.60, 0.85, 0.40, 0.0, 0.75, 0.95, 0.50, 0.65, 0.80],
              [0.20, 0.50, 0.65, 0.90, 0.70, 0.40, 0.30, 0.85, 0.95, 0.55],
              [0.75, 0.40, 0.0, 0.60, 0.85, 0.90, 0.55, 0.45, 0.70, 0.80],
              [0.60, 0.80, 0.95, 0.55, 0.45, 0.70, 0.85, 0.0, 0.50, 0.90],
              [0.30, 0.45, 0.60, 0.75, 0.50, 0.35, 0.20, 0.65, 0.80, 0.40],
              [0.0, 0.20, 0.35, 0.50, 0.40, 0.25, 0.15, 0.45, 0.60, 0.30],
            ],
          ],
          'long-labels' => [
            'rows' => ['Lunes Lab.', 'Martes Lab.', 'Miércoles Lab.', 'Jueves Lab.', 'Viernes Lab.'],
            'cols' => ['00-06h', '06-09h', '09-12h', '12-15h', '15-18h', '18-21h', '21-24h'],
            'matrix' => [
              [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
              [0.95, 0.55, 0.55, 0.80, 0.80, 0.45, 0.60],
              [0.0, 0.70, 0.45, 0.45, 0.45, 0.45, 0.45],
              [0.55, 0.45, 0.45, 0.95, 0.60, 0.70, 0.70],
              [0.55, 0.55, 0.70, 0.0, 0.60, 0.95, 0.70],
            ],
          ],
          default => [
            'rows' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
            'cols' => ['Col 1', 'Col 2', 'Col 3', 'Col 4', 'Col 5', 'Col 6', 'Col 7'],
            'matrix' => [
              [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
              [0.95, 0.55, 0.55, 0.80, 0.80, 0.45, 0.60],
              [0.0, 0.70, 0.45, 0.45, 0.45, 0.45, 0.45],
              [0.55, 0.45, 0.45, 0.95, 0.60, 0.70, 0.70],
              [0.55, 0.55, 0.70, 0.0, 0.60, 0.95, 0.70],
            ],
          ],
        };
    }

    return [];
  }

  /**
   * Limpia y normaliza parámetros crudos enviados desde la UI antes de pasarlos a GraphicsModule.
   *
   * @param array $rawParams Parámetros crudos
   * @return array Parámetros limpios
   */
  private static function cleanChartParams(array $rawParams): array
  {
    $boolKeys = [
      'tooltip',
      'showAxisY',
      'showAxisX',
      'showLegend',
      'showCenter',
      'showSecondary',
      'showPoints',
      'showNumber',
      'showLabel',
      'interpolate',
      'showPanel',
      'autoScale',
    ];
    $numKeys = [
      'transition',
      'barWidth',
      'barHeight',
      'pillarWidth',
      'strokeWidth',
      'axisLabelSize',
      'width',
      'height',
      'rx',
      'pillarGap',
      'gapY',
      'minWidth',
      'maxWidth',
      'cellSize',
      'cellGap',
      'tension',
      'startAngle',
      'gap',
      'value',
      'maxVal',
    ];

    $clean = [];
    foreach ($rawParams as $key => $val) {
      if ($val === null || $val === '') {
        continue;
      }
      if (in_array($key, $boolKeys, true)) {
        $parsedBool = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $clean[$key] = $parsedBool ?? (bool)$val;
        continue;
      }
      if (in_array($key, $numKeys, true) && is_numeric($val)) {
        $f = (float)$val;
        $clean[$key] = (floor($f) == $f && !in_array($key, ['tension'], true)) ? (int)$f : $f;
        continue;
      }
      $clean[$key] = $val;
    }

    return $clean;
  }

  /**
   * Renderiza uno o los 12 gráficos de GraphicsModule a partir de una carga útil de configuración.
   *
   * @param array $data Payload con type, globalConfig, localParams, localParamsByType y datasetPreset
   * @return array{success: bool, type?: string, html?: string, charts?: array, sampleData?: array, message?: string}
   */
  public static function renderChartPayload(array $data): array
  {
    $type = trim((string)($data['type'] ?? 'all'));
    if ($type !== 'all' && !in_array($type, self::CHART_TYPES, true)) {
      return [
        'success' => false,
        'message' => "Tipo de gráfico no soportado: {$type}",
      ];
    }

    $rawGlobal = is_array($data['globalConfig'] ?? null) ? $data['globalConfig'] : [];
    unset($rawGlobal['localOverrides']);
    $globalConfig = self::cleanChartParams($rawGlobal);
    $datasetPreset = trim((string)($data['datasetPreset'] ?? 'standard'));
    $localParams = is_array($data['localParams'] ?? null) ? self::cleanChartParams($data['localParams']) : [];
    $localParamsByType = is_array($data['localParamsByType'] ?? null) ? $data['localParamsByType'] : [];

    GraphicsModule::resetConfig();
    try {
      GraphicsModule::configStyle($globalConfig);

      if ($type === 'all') {
        $charts = [];
        $samples = [];
        $activeChart = trim((string)($data['activeChart'] ?? ''));

        foreach (self::CHART_TYPES as $chartType) {
          $sampleData = self::getPresetDataset($chartType, $datasetPreset);
          $chartLocal = is_array($localParamsByType[$chartType] ?? null)
            ? self::cleanChartParams($localParamsByType[$chartType])
            : [];
          if ($activeChart !== '' && $chartType === $activeChart && !empty($localParams)) {
            $chartLocal = array_merge($chartLocal, $localParams);
          }
          $params = array_merge($sampleData, $chartLocal);
          $charts[$chartType] = GraphicsModule::$chartType($params);
          $samples[$chartType] = $sampleData;
        }

        return [
          'success' => true,
          'type' => 'all',
          'charts' => $charts,
          'sampleData' => $samples,
        ];
      }

      $sampleData = self::getPresetDataset($type, $datasetPreset);
      $chartLocalFromMap = is_array($localParamsByType[$type] ?? null)
        ? self::cleanChartParams($localParamsByType[$type])
        : [];
      $mergedLocal = array_merge($chartLocalFromMap, $localParams);
      $params = array_merge($sampleData, $mergedLocal);

      return [
        'success' => true,
        'type' => $type,
        'html' => GraphicsModule::$type($params),
        'sampleData' => $sampleData,
      ];
    } finally {
      GraphicsModule::resetConfig();
    }
  }

  /**
   * Endpoint API para renderizar gráficos en vivo desde Chart Studio (POST /_frame/theme-studio/render-chart).
   */
  public function renderChart(): void
  {
    if (!self::isDevEnvironment()) {
      $this->json([
        'success' => false,
        'message' => 'Acceso restringido: Chart Studio solo está disponible en entorno de desarrollo.'
      ], 403);
      return;
    }

    $rawInput = (string)file_get_contents('php://input');
    $data = [];
    if (trim($rawInput) !== '') {
      $decoded = json_decode($rawInput, true);
      if (!is_array($decoded)) {
        $this->json([
          'success' => false,
          'message' => 'Formato JSON no válido.'
        ], 400);
        return;
      }
      $data = $decoded;
    }

    $result = self::renderChartPayload($data);
    $status = !empty($result['success']) ? 200 : 400;
    $this->json($result, $status);
  }

  /**
   * Devuelve la lista de fuentes en formato JSON.
   */
  public function fonts(): void
  {
    if (!self::isDevEnvironment()) {
      $this->json(['error' => 'No autorizado'], 403);
      return;
    }

    $this->json([
      'success' => true,
      'fonts' => self::getInstalledFonts()
    ]);
  }
}
