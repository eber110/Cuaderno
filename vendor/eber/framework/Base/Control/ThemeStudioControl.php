<?php

namespace Base\Control;

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
    \Core\Route::post('/_frame/theme-studio/set-active', [self::class, 'setActive']);
    \Core\Route::post('/_frame/theme-studio/delete-theme', [self::class, 'deleteThemeEndpoint']);
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
      'rawJson' => json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
      'rawFullJson' => json_encode($fullConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
      'rawDefaultJson' => json_encode($defaultCssConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);

    require $viewPath;
  }

  /**
   * Endpoint API para guardar y compilar el tema vía POST.
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

    // Guardar en App/Config/theme.json (soporta configuración completa o actualización de tema individual)
    $saved = ThemeModule::saveConfig($data);
    if (!$saved) {
      $this->json([
        'success' => false,
        'message' => 'No se pudo escribir en App/Config/theme.json. Verifique permisos de escritura.'
      ], 500);
      return;
    }

    // Compilar a App/Public/Css/_configTheme.css
    $compiled = ThemeModule::compile();

    // Sincronizar JIT y regenerar CSS minificado si el proyecto cuenta con estructura de assets
    $this->triggerAssetMinification();

    $basePath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $fullConfig = ThemeModule::loadFullConfig();
    $activeSlug = ThemeModule::resolveActiveThemeSlug($fullConfig);

    $this->json([
      'success' => true,
      'message' => '¡Tema guardado, clases utilitarias condicionales generadas y compilado en _configTheme.css!',
      'path' => $basePath . '/App/Public/Css/_configTheme.css',
      'fullConfig' => $fullConfig,
      'activeThemeSlug' => $activeSlug,
      'updated' => $compiled['updated'] ?? true
    ]);
  }

  /**
   * Establece el tema activo (o 'auto' para calendario) vía POST.
   */
  public function setActive(): void
  {
    if (!self::isDevEnvironment()) {
      $this->json(['success' => false, 'message' => 'No autorizado'], 403);
      return;
    }

    $rawInput = (string)file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: [];
    $slug = trim((string)($data['slug'] ?? 'default'));

    $updated = ThemeModule::setActiveTheme($slug);
    if (!$updated) {
      $this->json(['success' => false, 'message' => 'No se pudo activar el tema especificado.'], 400);
      return;
    }

    ThemeModule::compile();
    $this->triggerAssetMinification();

    $fullConfig = ThemeModule::loadFullConfig();
    $activeSlug = ThemeModule::resolveActiveThemeSlug($fullConfig);

    $this->json([
      'success' => true,
      'message' => "Tema activo establecido en '{$slug}'.",
      'fullConfig' => $fullConfig,
      'activeThemeSlug' => $activeSlug,
    ]);
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
      try {
        \Base\Module\JitCssModule::syncThemeConfigVariables($cssThemePath);

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
        \Base\Module\JitCssModule::generateJitCss($purgePaths, $jitOutPath);
        $cssPaths[] = $jitOutPath;

        \Base\Module\MinifyModule::minifyCss(
          $cssPaths,
          $basePath . '/App/Public/Min/Css/css.min.css'
        );
      } catch (\Throwable $e) {
        // Continuar sin bloquear la respuesta
      }
    }
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
