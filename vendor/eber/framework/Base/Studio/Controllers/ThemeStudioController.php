<?php

namespace Base\Studio\Controllers;

use Base\Control\Control;
use Base\Studio\StudioKernel;

use Base\Module\FormUiModule;
use Base\Module\GraphicsModule;
use Base\Module\JitCssModule;
use Base\Module\SecurityModule;
use Base\Module\ThemeModule;

/**
 * Controlador de Theme Studio (Herramienta Visual Interactiva de Frame).
 *
 * Permite diseñar, previsualizar en tiempo real y persistir paletas cromáticas,
 * tipografías y estilos de layout sin escribir CSS manualmente.
 * Disponible únicamente bajo entornos de desarrollo (local/dev/testing).
 */
class ThemeStudioController extends Control
{
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
   * Lista canónica de los 16 componentes de formulario soportados por FormUiModule.
   */
  public const INPUT_TYPES = FormUiModule::COMPONENT_TYPES;

  /**
   * Registra las rutas de desarrollo para Theme Studio si aún no han sido registradas.
   */
  public static function registerRoutes(): void
  {
    StudioKernel::registerRoutes();
  }

  /**
   * Verifica si la ejecución actual se encuentra en un entorno de desarrollo seguro (fail-closed).
   *
   * @return bool True solo si ENVIRONMENT está definido explícitamente como local/dev/testing.
   */
  public static function isDevEnvironment(): bool
  {
    return StudioKernel::isDevEnvironment();
  }

  /**
   * Valida entorno dev, origen loopback en HTTP y Content-Type + CSRF en mutaciones.
   */
  private function guardStudioRequest(bool $isMutation = false): bool
  {
    return StudioKernel::guardRequest($this, $isMutation);
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
    if (!$this->guardStudioRequest(false)) {
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
    $inputsAssets = self::getInputsStudioAssets($basePath);
    $initialIconsCatalog = self::getIconsCatalog($basePath);
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

    $initialInputsGlobal = isset($config['inputs']) && is_array($config['inputs'])
      ? $config['inputs']
      : ThemeModule::getDefaultInputsConfig();
    $initialInputsLocalMap = isset($initialInputsGlobal['localOverrides']) && is_array($initialInputsGlobal['localOverrides'])
      ? $initialInputsGlobal['localOverrides']
      : [];
    $initialInputsRender = self::renderInputPayload([
      'type' => 'all',
      'globalConfig' => $initialInputsGlobal,
      'localParamsByType' => $initialInputsLocalMap,
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
      'inputsCss' => $inputsAssets['css'],
      'inputsJs' => $inputsAssets['js'],
      'jitCatalog' => self::getJitClassesCatalog(),
      'initialChartsHtmlMap' => $initialChartsRender['charts'] ?? [],
      'initialChartsSampleData' => $initialChartsRender['sampleData'] ?? [],
      'initialInputsHtmlMap' => $initialInputsRender['inputs'] ?? [],
      'initialJitPreviewCss' => $initialInputsRender['jitPreviewCss'] ?? '',
      'initialIconsCatalog' => $initialIconsCatalog,
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
    if (!$this->guardStudioRequest(true)) {
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
    if (!$this->guardStudioRequest(true)) {
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
    if (!$this->guardStudioRequest(true)) {
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
    if (!$this->guardStudioRequest(true)) {
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
   * Recopila los assets autocontenidos de CSS y JS de componentes UI de formulario
   * para el laboratorio interactivo (UI / Input Studio) dentro de Theme Studio.
   *
   * @param string|null $basePath Directorio base del proyecto (opcional).
   * @return array{css: string, js: string}
   */
  public static function getInputsStudioAssets(?string $basePath = null): array
  {
    $base = ThemeModule::resolveBasePath($basePath);
    $candidates = [
      $base . '/Resources',
      $base . '/vendor/eber/framework/Resources',
    ];
    if (defined('FRAMEWORK_PATH')) {
      $candidates[] = rtrim(str_replace('\\', '/', (string)FRAMEWORK_PATH), '/') . '/Resources';
    }

    $resDir = null;
    foreach ($candidates as $dir) {
      if (is_dir($dir) && is_file($dir . '/Css/formComponents.css') && is_file($dir . '/Js/uiFormUtils.js')) {
        $resDir = $dir;
        break;
      }
    }

    if ($resDir === null) {
      return ['css' => '', 'js' => ''];
    }

    $cssParts = [
      ".selectAndCropImage, .loadImgModern, .file-dropzone { display: none; }",
      (string)@file_get_contents($resDir . '/Css/formComponents.css'),
    ];
    $css = str_ireplace('</style', '<\/style', implode("\n\n", $cssParts));

    $componentFiles = [
      'autoSubmitForm.js' => 'autoSubmitForm',
      'multiStepForm.js' => 'multiStepForm',
      'cropImage.js' => 'cropImage',
      'loadImgModern.js' => 'loadImgModern',
      'formComponents.js' => 'formComponents',
      'dropdown.js' => 'dropdown',
      'repeat_camp.js' => 'repeat_camp',
      'textLimiter.js' => 'textLimiter',
      'datePicker.js' => 'datePicker',
      'timePicker.js' => 'timePicker',
      'rangeSlider.js' => 'rangeSlider',
      'passwordInput.js' => 'passwordInput',
      'otpCodeInput.js' => 'otpCodeInput',
      'tagsInput.js' => 'tagsInput',
      'stepperInput.js' => 'stepperInput',
      'ratingInput.js' => 'ratingInput',
      'fileDropzone.js' => 'fileDropzone',
    ];

    $bundleParts = [
      "(function() {\n\"use strict\";",
      "window.__FmeStudioInits = window.__FmeStudioInits || {};",
    ];

    $utilsPath = $resDir . '/Js/uiFormUtils.js';
    if (is_file($utilsPath)) {
      $utilsCode = str_ireplace('</script', '<\/script', (string)@file_get_contents($utilsPath));
      $bundleParts[] = "// --- uiFormUtils.js ---\n" . trim($utilsCode);
    }

    // FloatingScroll: exponer únicamente la clase FloatingScroll sin activar instancia global sobre window
    $floatingPath = $resDir . '/Js/Components/floatingScroll.js';
    if (is_file($floatingPath)) {
      $fCode = (string)@file_get_contents($floatingPath);
      $fCode = (string)preg_replace('/^\s*import\s+[\s\S]*?from\s+["\'][^"\']+["\'];?\s*$/m', '', $fCode);
      $fCode = (string)preg_replace('/^\s*export\s+\{[\s\S]*?\};?\s*$/m', '', $fCode);
      $fCode = (string)preg_replace('/^\s*export\s+(?=(?:async\s+)?function\b|const\b|let\b|var\b|class\b)/m', '', $fCode);
      $fCode = str_ireplace('</script', '<\/script', $fCode);
      $bundleParts[] = "// --- floatingScroll.js (class-only para contenedores) ---\n(function() {\n" . trim($fCode) . "\nif (typeof FloatingScroll === 'function') { window.FloatingScroll = FloatingScroll; }\n})();";
    }

    foreach ($componentFiles as $file => $fnName) {
      $filePath = $resDir . '/Js/Components/' . $file;
      if (!is_file($filePath)) {
        continue;
      }
      $code = (string)@file_get_contents($filePath);
      $code = (string)preg_replace('/^\s*import\s+[\s\S]*?from\s+["\'][^"\']+["\'];?\s*$/m', '', $code);
      $code = (string)preg_replace('/^\s*export\s+\{[\s\S]*?\};?\s*$/m', '', $code);
      $code = (string)preg_replace('/^\s*export\s+(?=(?:async\s+)?function\b|const\b|let\b|var\b|class\b)/m', '', $code);
      $code = str_ireplace('</script', '<\/script', $code);
      $bundleParts[] = "// --- {$file} ---\n(function() {\n" . trim($code) . "\nif (typeof {$fnName} === 'function') { window.__FmeStudioInits['{$fnName}'] = {$fnName}; }\n})();";
    }

    $bundleParts[] = <<<'JS'
window.initFormUiComponents = function() {
  var inits = window.__FmeStudioInits || {};
  Object.keys(inits).forEach(function(fn) {
    try { inits[fn](); } catch (err) { console.warn('Input Studio init error [' + fn + ']:', err); }
  });
  try {
    document.dispatchEvent(new Event('previewUpdated'));
  } catch (_) {}
};
})();
JS;

    return [
      'css' => $css,
      'js' => implode("\n\n", $bundleParts),
    ];
  }

  /**
   * Devuelve el catálogo curado de clases JIT válidas en LibraryCssJit para el asistente visual.
   *
   * @return array<string, array{label: string, classes: array<int, string>}>
   */
  public static function getJitClassesCatalog(): array
  {
    return [
      'grid' => [
        'label' => 'Grilla & Columnas',
        'classes' => ['grid', 'col-desk-1', 'col-desk-2', 'col-desk-3', 'col-desk-4', 'col-mid-2', 'col-sml-1', 'span-desk-2', 'span-desk-3', 'span-mid-2', 'gap10', 'gap15', 'gap20'],
      ],
      'flex' => [
        'label' => 'Flex & Alineación',
        'classes' => ['flex-row', 'flex-column', 'wrap', 'center-center', 'center-between', 'center-end', 'center-start', 'gap5'],
      ],
      'dimensions' => [
        'label' => 'Ancho, Alto & Espaciado',
        'classes' => ['w100', 'w50', 'w-mid-100', 'w-sml-100', 'wpx-max400', 'wpx-max600', 'wpx-max800', 'hpx140', 'p10', 'p15', 'p20', 'm10', 'mb10'],
      ],
      'surface' => [
        'label' => 'Superficies, Bordes & Sombras',
        'classes' => ['back1', 'back2', 'back3', 'back6', 'back8', 'color1', 'color2', 'border2', 'border-none', 'br10', 'br15', 'br20', 'shadow1', 'shadow2'],
      ],
      'typography' => [
        'label' => 'Tipografía & Estado',
        'classes' => ['x12', 'x14', 'x16', 'x18', 'bold600', 'bold700', 'uppercase', 'text-center', 'color-success', 'color-danger'],
      ],
      'interaction' => [
        'label' => 'Interacción & Accesibilidad',
        'classes' => ['focus-ring2', 'textareaResize', 'touch-target'],
      ],
    ];
  }

  /**
   * Extrae clases candidatas desde estructuras de configuración de inputs/formularios y compila su CSS JIT en memoria.
   *
   * @param array<int, array<string, mixed>> $sources Arreglos con propiedades de clases JIT.
   * @return string CSS JIT compilado listo para <style id="tsJitLivePreviewCss">.
   */
  private static function compileJitFromSources(array $sources): string
  {
    $classKeys = [
      'formClass',
      'gridClass',
      'stepClass',
      'wrapperClass',
      'class',
      'labelClass',
      'helperClass',
      'uiTrigger',
      'uiSurface',
      'uiBtnPrimary',
      'uiBtnSecondary',
      'uiAccent',
      'uiTrack',
      'boxImage',
      'boxBtnImage',
      'styleBox',
      'styleColor',
      'dataNote',
    ];
    $words = [];
    foreach ($sources as $src) {
      if (!is_array($src)) {
        continue;
      }
      foreach ($classKeys as $k) {
        if (!empty($src[$k]) && is_string($src[$k])) {
          foreach (preg_split('/\s+/', trim($src[$k])) ?: [] as $tok) {
            if ($tok !== '') {
              $words[$tok] = $tok;
            }
          }
        }
      }
    }
    return !empty($words) ? JitCssModule::compileWords(array_values($words)) : '';
  }

  /**
   * Limpia y normaliza parámetros globales/locales de FormUiModule enviados desde Input Studio.
   *
   * @param array<string, mixed> $rawParams Parámetros crudos desde el cliente.
   * @return array<string, mixed>
   */
  private static function cleanInputParams(array $rawParams): array
  {
    $boolKeys = [
      'mobileSheet',
      'wrapContainer',
      'multiline',
      'inlineVars',
      'includeGlobalInCode',
      'focusOutline',
      'required',
      'disabled',
      'readonly',
      'autoSubmit',
      'noAutoSubmit',
      'processAutoSubmit',
      'hideScrollbar',
      'noPreview',
      'allowRotate',
      'dropzone',
      'showAlpha',
      'checked',
      'animated',
      'searchable',
      'clearable',
      'showTime',
      'showPresets',
      'showTooltip',
      'showLimits',
      'showStrength',
      'allowHalf',
      'showValue',
      'mask',
      'multiple',
    ];
    $numKeys = [
      'borderWidth',
      'radius',
      'touchSize',
      'fontSize',
      'placeholderSize',
      'secondaryFontSize',
      'gap',
      'fieldGap',
      'boxPadding',
      'thumbSize',
      'scrollbarWidth',
      'chipRadius',
      'starSize',
      'dropzoneMinHeight',
      'dropzoneIconSize',
      'sliderTrackHeight',
      'sliderThumbSize',
      'otpCellSize',
      'maxHeight',
      'blur',
      'transition',
      'maxFiles',
      'maxSizeMb',
      'step',
      'min',
      'max',
      'length',
      'maxTags',
      'rows',
      'limit',
      'minlength',
      'maxlength',
    ];
    $floatKeys = ['maxSizeMb', 'min', 'max', 'step'];

    $clean = [];
    foreach ($rawParams as $key => $val) {
      if (!is_string($key) || !preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
        continue;
      }
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
        $clean[$key] = (!in_array($key, $floatKeys, true) || floor($f) == $f) ? (int)$f : $f;
        continue;
      }
      if (is_string($val)) {
        $clean[$key] = trim($val);
      } else {
        $clean[$key] = $val;
      }
    }

    return $clean;
  }

  /**
   * Construye el snippet PHP equivalente para `_input()` y `FormUiModule::configStyle()`.
   *
   * @param string $type Tipo de componente.
   * @param array<string, mixed> $globalConfig Configuración global.
   * @param array<string, mixed> $localParams Parámetros locales del componente.
   * @return array{phpSnippet: string, globalSnippet: string}
   */
  private static function buildInputPhpSnippets(string $type, array $globalConfig, array $localParams): array
  {
    $exportArr = static function (array $arr, string $indent = '  '): string {
      if (empty($arr)) {
        return '[]';
      }
      $lines = ['['];
      foreach ($arr as $k => $v) {
        $kStr = "'" . addslashes((string)$k) . "'";
        if (is_bool($v)) {
          $vStr = $v ? 'true' : 'false';
        } elseif (is_int($v) || is_float($v)) {
          $vStr = (string)$v;
        } elseif (is_array($v)) {
          $vStr = var_export($v, true);
        } else {
          $vStr = "'" . addslashes((string)$v) . "'";
        }
        $lines[] = "{$indent}{$kStr} => {$vStr},";
      }
      $lines[] = ']';
      return implode("\n", $lines);
    };

    $defaults = FormUiModule::getDefaultConfig();
    $diffGlobal = [];
    foreach ($globalConfig as $k => $v) {
      if ($k === 'localOverrides') {
        continue;
      }
      if (!array_key_exists($k, $defaults) || $defaults[$k] !== $v) {
        $diffGlobal[$k] = $v;
      }
    }

    $phpParams = $localParams;
    $includeGlobal = !empty($phpParams['includeGlobalInCode']);
    unset($phpParams['multiline'], $phpParams['previewId'], $phpParams['ignoreThemeLocal'], $phpParams['inlineVars'], $phpParams['includeGlobalInCode']);

    if ($includeGlobal && !empty($diffGlobal)) {
      $styleDiff = $diffGlobal;
      unset($styleDiff['preset']);
      $phpParams = array_merge($styleDiff, $phpParams);
    }

    if (array_key_exists('wrapContainer', $phpParams) && $phpParams['wrapContainer'] === false) {
      unset($phpParams['wrapContainer']);
    }

    $phpSnippet = empty($phpParams)
      ? "<?= uiInput('{$type}') ?>"
      : "<?= uiInput('{$type}', " . $exportArr($phpParams, '  ') . ") ?>";

    $targetGlobal = !empty($diffGlobal) ? $diffGlobal : $globalConfig;
    unset($targetGlobal['localOverrides']);

    $globalSnippet = "<?php\nuse Base\\Module\\FormUiModule;\n\nFormUiModule::configStyle(" . $exportArr($targetGlobal, '  ') . ");\n";

    return [
      'phpSnippet' => $phpSnippet,
      'globalSnippet' => $globalSnippet,
    ];
  }

  /**
   * Renderiza uno o los 16 componentes de formulario de FormUiModule desde Input Studio.
   *
   * @param array<string, mixed> $data Payload con type, globalConfig, localParams, localParamsByType y codeOptions.
   * @return array{success: bool, type?: string, html?: string, inputs?: array, htmlSnippet?: string, phpSnippet?: string, globalSnippet?: string, message?: string}
   */
  public static function renderInputPayload(array $data): array
  {
    $type = trim((string)($data['type'] ?? 'all'));
    if ($type !== 'all' && !in_array($type, self::INPUT_TYPES, true)) {
      return [
        'success' => false,
        'message' => "Tipo de componente no soportado: {$type}",
      ];
    }

    $rawGlobal = is_array($data['globalConfig'] ?? null) ? $data['globalConfig'] : [];
    unset($rawGlobal['localOverrides']);
    $globalConfig = self::cleanInputParams($rawGlobal);
    $localParams = is_array($data['localParams'] ?? null) ? self::cleanInputParams($data['localParams']) : [];
    $localParamsByType = is_array($data['localParamsByType'] ?? null) ? $data['localParamsByType'] : [];
    $rawCodeOptions = is_array($data['codeOptions'] ?? null) ? $data['codeOptions'] : [];
    $codeOptions = !empty($rawCodeOptions) ? self::cleanInputParams($rawCodeOptions) : [];
    $hideLabelExplicit = array_key_exists('label', $rawCodeOptions) && (string)$rawCodeOptions['label'] === '';
    $activeInput = trim((string)($data['activeInput'] ?? 'cropImage'));
    if (!in_array($activeInput, self::INPUT_TYPES, true)) {
      $activeInput = 'cropImage';
    }

    FormUiModule::resetConfig();
    try {
      FormUiModule::configStyle($globalConfig);

      if ($type === 'all') {
        $inputs = [];
        $activeSnippetParams = [];

        foreach (self::INPUT_TYPES as $inputType) {
          $typeLocal = is_array($localParamsByType[$inputType] ?? null)
            ? self::cleanInputParams($localParamsByType[$inputType])
            : [];
          if ($inputType === $activeInput && !empty($localParams)) {
            $typeLocal = array_merge($typeLocal, $localParams);
          }

          $previewParams = array_merge([
            'previewId' => 'studio_preview_' . $inputType,
            'ignoreThemeLocal' => true,
            'inlineVars' => !empty($typeLocal),
          ], $typeLocal);
          $inputs[$inputType] = FormUiModule::render($inputType, $previewParams);

          if ($inputType === $activeInput) {
            $activeSnippetParams = $typeLocal;
          }
        }

        $snippetParams = array_merge($activeSnippetParams, $codeOptions);
        if ($hideLabelExplicit) {
          unset($snippetParams['label']);
        }
        $phpSnippets = self::buildInputPhpSnippets($activeInput, $globalConfig, $snippetParams);
        $htmlSnippetParams = array_merge($snippetParams, ['ignoreThemeLocal' => true]);
        $jitSources = array_merge([$localParams, $codeOptions, ['class' => 'ui-input-component']], array_values($localParamsByType));
        $jitPreviewCss = self::compileJitFromSources($jitSources);

        return [
          'success' => true,
          'type' => 'all',
          'inputs' => $inputs,
          'htmlSnippet' => FormUiModule::buildHtmlSnippet($activeInput, $htmlSnippetParams),
          'phpSnippet' => $phpSnippets['phpSnippet'],
          'globalSnippet' => $phpSnippets['globalSnippet'],
          'jitPreviewCss' => $jitPreviewCss,
        ];
      }

      $typeLocalFromMap = is_array($localParamsByType[$type] ?? null)
        ? self::cleanInputParams($localParamsByType[$type])
        : [];
      $mergedLocal = array_merge($typeLocalFromMap, $localParams);
      $previewParams = array_merge([
        'previewId' => 'studio_preview_' . $type,
        'ignoreThemeLocal' => true,
        'inlineVars' => !empty($mergedLocal),
      ], $mergedLocal);
      $snippetParams = array_merge($mergedLocal, $codeOptions);
      if ($hideLabelExplicit) {
        unset($snippetParams['label']);
      }
      $phpSnippets = self::buildInputPhpSnippets($type, $globalConfig, $snippetParams);
      $htmlSnippetParams = array_merge($snippetParams, ['ignoreThemeLocal' => true]);
      $jitPreviewCss = self::compileJitFromSources([$mergedLocal, $codeOptions]);

      return [
        'success' => true,
        'type' => $type,
        'html' => FormUiModule::render($type, $previewParams),
        'htmlSnippet' => FormUiModule::buildHtmlSnippet($type, $htmlSnippetParams),
        'phpSnippet' => $phpSnippets['phpSnippet'],
        'globalSnippet' => $phpSnippets['globalSnippet'],
        'jitPreviewCss' => $jitPreviewCss,
      ];
    } finally {
      FormUiModule::resetConfig();
    }
  }

  /**
   * Construye y renderiza un formulario completo (simple o multi-paso) para el Diseñador Visual de Theme Studio.
   *
   * @param array<string, mixed> $data Payload con globalConfig, formConfig y stepsOrFields.
   * @return array<string, mixed>
   */
  public static function renderFormPayload(array $data): array
  {
    $rawGlobal = is_array($data['globalConfig'] ?? null) ? $data['globalConfig'] : [];
    unset($rawGlobal['localOverrides']);
    $globalConfig = self::cleanInputParams($rawGlobal);

    $rawFormCfg = is_array($data['formConfig'] ?? null) ? $data['formConfig'] : [];
    $formConfig = self::cleanInputParams($rawFormCfg);
    foreach (['autoSubmit', 'fetchPreview', 'csrf', 'showSubmitButton', 'multiStep', 'stepProgress', 'inlineVars'] as $boolKey) {
      if (array_key_exists($boolKey, $rawFormCfg)) {
        $formConfig[$boolKey] = filter_var($rawFormCfg[$boolKey], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool)$rawFormCfg[$boolKey];
      }
    }
    if (!empty($rawFormCfg['stepsMeta']) && is_array($rawFormCfg['stepsMeta'])) {
      $cleanMeta = [];
      foreach ($rawFormCfg['stepsMeta'] as $stMeta) {
        if (!is_array($stMeta)) {
          continue;
        }
        $cleanMeta[] = [
          'title'       => trim((string)($stMeta['title'] ?? '')),
          'description' => trim((string)($stMeta['description'] ?? '')),
          'stepClass'   => trim((string)($stMeta['stepClass'] ?? '')),
          'validateUrl' => trim((string)($stMeta['validateUrl'] ?? '')),
        ];
      }
      $formConfig['stepsMeta'] = $cleanMeta;
    }

    $rawStepsOrFields = is_array($data['stepsOrFields'] ?? null) ? $data['stepsOrFields'] : [];
    $stepsOrFields = [];
    $jitSources = [$formConfig];

    foreach ($rawStepsOrFields as $item) {
      if (!is_array($item)) {
        continue;
      }
      if (isset($item['fields']) && is_array($item['fields'])) {
        $stepClean = [
          'title'       => trim((string)($item['title'] ?? '')),
          'description' => trim((string)($item['description'] ?? '')),
          'stepClass'   => trim((string)($item['stepClass'] ?? '')),
          'validateUrl' => trim((string)($item['validateUrl'] ?? '')),
          'fields'      => [],
        ];
        $jitSources[] = $stepClean;
        foreach ($item['fields'] as $fld) {
          if (!is_array($fld)) {
            continue;
          }
          $fType = trim((string)($fld['type'] ?? 'textInput'));
          if (!in_array($fType, self::INPUT_TYPES, true)) {
            continue;
          }
          $fClean = self::cleanInputParams($fld);
          $fClean['type'] = $fType;
          $jitSources[] = $fClean;
          $stepClean['fields'][] = $fClean;
        }
        $stepsOrFields[] = $stepClean;
      } else {
        $fType = trim((string)($item['type'] ?? 'textInput'));
        if (!in_array($fType, self::INPUT_TYPES, true)) {
          continue;
        }
        $fClean = self::cleanInputParams($item);
        $fClean['type'] = $fType;
        if (isset($item['step']) && is_numeric($item['step'])) {
          $fClean['step'] = max(0, (int)$item['step']);
        }
        $jitSources[] = $fClean;
        $stepsOrFields[] = $fClean;
      }
    }

    if (!empty($formConfig['stepsMeta'])) {
      foreach ($formConfig['stepsMeta'] as $sm) {
        $jitSources[] = $sm;
      }
    }

    // Export PHP helpers
    $exportPhp = static function (mixed $val, int $depth = 0) use (&$exportPhp): string {
      $pad = str_repeat('  ', $depth);
      $innerPad = str_repeat('  ', $depth + 1);
      if (is_array($val)) {
        if (empty($val)) {
          return '[]';
        }
        $isAssoc = array_keys($val) !== range(0, count($val) - 1);
        $lines = ['['];
        foreach ($val as $k => $v) {
          $vExp = $exportPhp($v, $depth + 1);
          if ($isAssoc) {
            $kExp = "'" . addslashes((string)$k) . "'";
            $lines[] = "{$innerPad}{$kExp} => {$vExp},";
          } else {
            $lines[] = "{$innerPad}{$vExp},";
          }
        }
        $lines[] = "{$pad}]";
        return implode("\n", $lines);
      }
      if (is_bool($val)) {
        return $val ? 'true' : 'false';
      }
      if (is_int($val) || is_float($val)) {
        return (string)$val;
      }
      return "'" . addslashes((string)$val) . "'";
    };

    FormUiModule::resetConfig();
    try {
      FormUiModule::configStyle($globalConfig);

      $previewConfig = array_merge($formConfig, ['studioPreview' => true]);
      $previewHtml = FormUiModule::renderForm($previewConfig, $stepsOrFields);

      $exportConfig = $formConfig;
      unset($exportConfig['studioPreview']);
      $htmlSnippet = FormUiModule::renderForm($exportConfig, $stepsOrFields);

      $defaults = FormUiModule::getDefaultConfig();
      $diffGlobal = [];
      foreach ($globalConfig as $k => $v) {
        if ($k === 'localOverrides') {
          continue;
        }
        if (!array_key_exists($k, $defaults) || $defaults[$k] !== $v) {
          $diffGlobal[$k] = $v;
        }
      }
      $targetGlobal = !empty($diffGlobal) ? $diffGlobal : $globalConfig;
      unset($targetGlobal['localOverrides']);

      $phpFormSnippet = "<?= uiForm(\n  " . $exportPhp($exportConfig, 1) . ",\n  " . $exportPhp($stepsOrFields, 1) . "\n) ?>";
      $globalSnippet = "<?php\nuse Base\\Module\\FormUiModule;\n\nFormUiModule::configStyle(" . $exportPhp($targetGlobal, 0) . ");\n";

      // Snippet alternativo campo a campo usando uiInput()
      $individualLines = ["<form action=\"" . htmlspecialchars((string)($exportConfig['action'] ?? ''), ENT_QUOTES, 'UTF-8') . "\" method=\"" . strtolower((string)($exportConfig['method'] ?? 'post')) . "\" class=\"ui-form" . (!empty($exportConfig['autoSubmit']) ? ' auto-submit' : '') . (!empty($exportConfig['formClass']) ? ' ' . htmlspecialchars((string)$exportConfig['formClass'], ENT_QUOTES, 'UTF-8') : '') . "\">"];
      if (strtoupper((string)($exportConfig['method'] ?? 'POST')) === 'POST' && (!array_key_exists('csrf', $exportConfig) || !empty($exportConfig['csrf']))) {
        $individualLines[] = "  <?= csrf_field() ?>";
      }
      $flatExportFields = [];
      foreach ($stepsOrFields as $stItem) {
        if (isset($stItem['fields']) && is_array($stItem['fields'])) {
          foreach ($stItem['fields'] as $sf) {
            $flatExportFields[] = $sf;
          }
        } else {
          $flatExportFields[] = $stItem;
        }
      }
      foreach ($flatExportFields as $ff) {
        $ft = (string)($ff['type'] ?? 'textInput');
        $fp = $ff;
        unset($fp['type'], $fp['step']);
        $individualLines[] = empty($fp)
          ? "  <?= uiInput('{$ft}') ?>"
          : "  <?= uiInput('{$ft}', " . $exportPhp($fp, 1) . ") ?>";
      }
      $individualLines[] = "</form>";

      $jitPreviewCss = self::compileJitFromSources($jitSources);

      return [
        'success' => true,
        'html' => $previewHtml,
        'htmlSnippet' => $htmlSnippet,
        'phpFormSnippet' => $phpFormSnippet,
        'phpFieldsSnippet' => implode("\n\n", $individualLines),
        'globalSnippet' => $globalSnippet,
        'jitPreviewCss' => $jitPreviewCss,
      ];
    } finally {
      FormUiModule::resetConfig();
    }
  }

  /**
   * Endpoint API para renderizar un formulario completo en vivo desde el Diseñador Visual (POST /_frame/theme-studio/render-form).
   */
  public function renderForm(): void
  {
    if (!$this->guardStudioRequest(true)) {
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

    $result = self::renderFormPayload($data);
    $status = !empty($result['success']) ? 200 : 400;
    $this->json($result, $status);
  }

  /**
   * Endpoint API para renderizar componentes UI en vivo desde Input Studio (POST /_frame/theme-studio/render-input).
   */
  public function renderInput(): void
  {
    if (!$this->guardStudioRequest(true)) {
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

    $result = self::renderInputPayload($data);
    $status = !empty($result['success']) ? 200 : 400;
    $this->json($result, $status);
  }

  /**
   * Escanea los directorios de iconos (/Ico) del proyecto y del framework,
   * agrupando todos los archivos SVG por carpeta y generando su vista previa y código PHP.
   *
   * @param string|null $basePath Directorio base del proyecto (opcional)
   * @return array{totalIcons: int, totalFolders: int, primaryDir: string, folders: array<int, array>}
   */
  public static function getIconsCatalog(?string $basePath = null): array
  {
    if (!function_exists('renderSvgFile')) {
      $svgHelper = dirname(__DIR__) . '/Helpers/Svg.php';
      if (is_file($svgHelper)) {
        require_once $svgHelper;
      }
    }

    $base = ThemeModule::resolveBasePath($basePath);
    $appIcoDir = $base . '/App/Rsc/Ico';
    $vendorIcoDir = $base . '/vendor/eber/framework/Resources/Ico';
    $localResIcoDir = $base . '/Resources/Ico';

    $sources = [];
    if (is_dir($appIcoDir)) {
      $sources[] = ['dir' => $appIcoDir, 'label' => 'App/Rsc/Ico'];
    }
    if (is_dir($vendorIcoDir)) {
      $sources[] = ['dir' => $vendorIcoDir, 'label' => 'Resources/Ico'];
    } elseif (is_dir($localResIcoDir)) {
      $sources[] = ['dir' => $localResIcoDir, 'label' => 'Resources/Ico'];
    } elseif ($basePath === null && defined('FRAMEWORK_PATH')) {
      $fwResIco = rtrim(str_replace('\\', '/', (string)FRAMEWORK_PATH), '/') . '/Resources/Ico';
      if (is_dir($fwResIco)) {
        $sources[] = ['dir' => $fwResIco, 'label' => 'Resources/Ico'];
      }
    }

    $primaryDir = is_dir($appIcoDir) ? 'App/Rsc/Ico' : 'Resources/Ico';

    $foldersMap = [
      '' => [
        'key' => '',
        'label' => 'Raíz (/Ico)',
        'path' => $primaryDir . '/',
        'icons' => [],
      ],
    ];
    $folderKeyLookup = [];
    $seenIcons = [];

    foreach ($sources as $src) {
      $baseDirNorm = rtrim(str_replace('\\', '/', $src['dir']), '/');
      if (!is_dir($baseDirNorm)) {
        continue;
      }

      $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($baseDirNorm, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST
      );

      foreach ($iterator as $entry) {
        $entryPath = str_replace('\\', '/', $entry->getPathname());
        if (!str_starts_with($entryPath, $baseDirNorm . '/')) {
          continue;
        }
        $relPath = substr($entryPath, strlen($baseDirNorm) + 1);
        if ($relPath === '') {
          continue;
        }

        // Ignorar archivos o directorios ocultos (ej. .gitkeep, .DS_Store)
        $segments = explode('/', $relPath);
        $hasHiddenSegment = false;
        foreach ($segments as $seg) {
          if ($seg === '' || str_starts_with($seg, '.')) {
            $hasHiddenSegment = true;
            break;
          }
        }
        if ($hasHiddenSegment) {
          continue;
        }

        if ($entry->isDir()) {
          $folderKey = trim($relPath, '/');
          $folderLower = mb_strtolower($folderKey, 'UTF-8');
          if (!isset($folderKeyLookup[$folderLower])) {
            $folderKeyLookup[$folderLower] = $folderKey;
            $foldersMap[$folderKey] = [
              'key' => $folderKey,
              'label' => $folderKey,
              'path' => $primaryDir . '/' . $folderKey . '/',
              'icons' => [],
            ];
          }
          continue;
        }

        if (!$entry->isFile() || strtolower($entry->getExtension()) !== 'svg') {
          continue;
        }

        $relNoExt = substr($relPath, 0, -4);
        $iconLower = mb_strtolower($relNoExt, 'UTF-8');
        if (isset($seenIcons[$iconLower])) {
          continue;
        }
        $seenIcons[$iconLower] = true;

        $rawFolder = str_contains($relNoExt, '/') ? dirname($relNoExt) : '';
        if ($rawFolder === '.' || $rawFolder === '/') {
          $rawFolder = '';
        }

        $canonicalFolder = '';
        if ($rawFolder !== '') {
          $folderLower = mb_strtolower($rawFolder, 'UTF-8');
          if (!isset($folderKeyLookup[$folderLower])) {
            $folderKeyLookup[$folderLower] = $rawFolder;
            $foldersMap[$rawFolder] = [
              'key' => $rawFolder,
              'label' => $rawFolder,
              'path' => $primaryDir . '/' . $rawFolder . '/',
              'icons' => [],
            ];
          }
          $canonicalFolder = $folderKeyLookup[$folderLower];
        }

        $svgHtml = function_exists('renderSvgFile') ? renderSvgFile($entryPath) : '?!';
        $isValid = ($svgHtml !== '?!');
        $viewBox = '0 0 24 24';
        if ($isValid && preg_match('/viewBox="([^"]+)"/', $svgHtml, $vbMatch)) {
          $viewBox = $vbMatch[1];
        }

        $foldersMap[$canonicalFolder]['icons'][] = [
          'name' => $relNoExt,
          'filename' => pathinfo($relPath, PATHINFO_FILENAME),
          'folder' => $canonicalFolder,
          'phpCode' => 'svg("' . $relNoExt . '");',
          'source' => $src['label'],
          'sizeBytes' => (int)$entry->getSize(),
          'viewBox' => $viewBox,
          'valid' => $isValid,
          'svgHtml' => $isValid ? $svgHtml : '',
        ];
      }
    }

    // Ordenar carpetas: raíz primero, luego subcarpetas alfabéticamente
    $subfolderKeys = array_values(array_filter(array_keys($foldersMap), static fn(string $k): bool => $k !== ''));
    usort($subfolderKeys, 'strnatcasecmp');
    $orderedKeys = array_merge([''], $subfolderKeys);

    $folders = [];
    $totalIcons = 0;

    foreach ($orderedKeys as $key) {
      $folderData = $foldersMap[$key];
      usort($folderData['icons'], static fn(array $a, array $b): int => strnatcasecmp($a['filename'], $b['filename']));
      $count = count($folderData['icons']);
      $totalIcons += $count;
      $folderData['count'] = $count;
      $folders[] = $folderData;
    }

    return [
      'totalIcons' => $totalIcons,
      'totalFolders' => count($folders),
      'primaryDir' => $primaryDir,
      'folders' => $folders,
    ];
  }

  /**
   * Devuelve el catálogo completo de iconos SVG agrupados por carpeta en formato JSON.
   */
  public function icons(): void
  {
    if (!$this->guardStudioRequest(false)) {
      return;
    }

    $this->json([
      'success' => true,
      'catalog' => self::getIconsCatalog()
    ]);
  }

  /**
   * Devuelve la lista de fuentes en formato JSON.
   */
  public function fonts(): void
  {
    if (!$this->guardStudioRequest(false)) {
      return;
    }

    $this->json([
      'success' => true,
      'fonts' => self::getInstalledFonts()
    ]);
  }
}
