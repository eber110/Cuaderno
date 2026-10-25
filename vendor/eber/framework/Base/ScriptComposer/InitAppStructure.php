<?php

/**
 * Script para crear y actualizar la estructura de directorios y archivos base de /App.
 * Se ejecuta automáticamente después de instalar o actualizar con Composer.
 * 
 * Uso en composer.json:
 * "scripts": {
 *   "post-install-cmd": ["@php vendor/eber/framework/Base/ScriptComposer/InitAppStructure.php"],
 *   "post-update-cmd": ["@php vendor/eber/framework/Base/ScriptComposer/InitAppStructure.php"]
 * }
 */

class InitAppStructure
{
  private string $basePath;
  private string $appPath;
  private string $frameworkResources;

  /**
   * Estructura completa de directorios para iniciar cualquier proyecto desde cero
   * sin depender de archivos .gitkeep en el repositorio.
   *
   * @var array<int, string>
   */
  private array $directories = [
    // Estructura principal de /App
    'App',
    'App/Components',
    'App/Config',
    'App/Controllers',
    'App/DatabaseComponent',
    'App/Middleware',
    'App/Middleware/MiddlewareInterface',
    'App/Models',
    'App/Providers',
    'App/Public',
    'App/Public/Css',
    'App/Public/Img',
    'App/Public/Img/Custom',
    'App/Public/Img/Thumbnail',
    'App/Public/Js',
    'App/Public/Min',
    'App/Public/Min/Css',
    'App/Public/Min/Js',
    'App/Public/Uploads',
    'App/Route',
    // Recursos en /App/Rsc (Fonts, Helper, Ico y Library)
    'App/Rsc',
    'App/Rsc/Fonts',
    'App/Rsc/Helper',
    'App/Rsc/Ico',
    'App/Rsc/Ico/Custom',
    'App/Rsc/Ico/Separator',
    'App/Rsc/Library',
    'App/Rsc/Library/Charts',
    'App/Rsc/Library/Gsap',
    // Seguridad de rutas e IPs
    'App/Safety',
    // Segmentos reutilizables del proyecto (vacíos al iniciar)
    'App/Segment',
    'App/Segment/Form',
    'App/Segment/Menu',
    'App/Segment/Template',
    // Vistas del proyecto y vistas de error
    'App/Views',
    'App/errorViews',
    // Directorios de arranque y sistema en la raíz del proyecto
    'Bootstrap',
    'Cache',
    'Cache/Views',
    'Database',
    'Logs',
    'Logs/UserLog',
    'Logs/VisitLog',
    'Uploads',
  ];

  private bool $isFrameworkRepo = false;

  public function __construct()
  {
    $this->basePath = str_replace('\\', '/', getcwd());
    $this->appPath = $this->basePath . '/App';
    $this->frameworkResources = str_replace('\\', '/', dirname(__DIR__, 2) . '/Resources');

    // Cargar autoloader de Composer si está disponible para utilidades como RouteScanner
    if (file_exists($this->basePath . '/vendor/autoload.php')) {
      require_once $this->basePath . '/vendor/autoload.php';
    }

    // Detectar si estamos en el repositorio del framework mismo
    $composerJsonPath = $this->basePath . '/composer.json';
    if (file_exists($composerJsonPath)) {
      $composerData = json_decode((string)file_get_contents($composerJsonPath), true);
      if (($composerData['name'] ?? '') === 'eber/framework') {
        $this->isFrameworkRepo = true;
      }
    }
  }

  /**
   * Crea la estructura de directorios y copia los archivos iniciales desde Resources/
   * verificando siempre su existencia previa para no sobrescribir código del usuario.
   *
   * @return bool
   */
  public function create(): bool
  {
    echo "🔧 Iniciando estructura de directorios de /App...\n\n";

    $created = 0;
    $skipped = 0;

    foreach ($this->directories as $dir) {
      $fullPath = $this->basePath . '/' . $dir;

      if (is_dir($fullPath)) {
        echo "⏭️  Saltado: {$dir} (ya existe)\n";
        $skipped++;
      } else {
        if (@mkdir($fullPath, 0755, true)) {
          echo "✅ Creado: {$dir}\n";
          $created++;
        } else {
          echo "❌ Error: No se pudo crear {$dir}\n";
        }
      }
    }

    // 1. Copiar y sincronizar recursos base (Fonts, Helper, Ico, Library) de Resources/ a App/Rsc/
    $this->copyResources();

    // 2. Copiar archivos iniciales de configuración y estructura desde Resources/ a App/ y Bootstrap/
    $this->createBootstrapFiles();
    $this->createConfigFiles();
    $this->createMiddlewareInterface();
    $this->copyErrorView();
    $this->createThemeConfig();
    $this->createDefaultRouteFile();
    $this->createSafetyFiles();

    // 3. Crear archivos raíz del proyecto si no existen o sincronizar mejoras críticas
    $this->createProvidersFiles();
    $this->createEnvFiles();
    $this->createJsConfig();
    $this->createHtaccess();
    $this->createIndexPhp();
    $this->createGitignore();
    $this->copyAgentsFile();

    echo "\n📊 Resumen:\n";
    echo "   Directorios creados: {$created}\n";
    echo "   Directorios existentes: {$skipped}\n";
    echo "\n✨ ¡Estructura de /App lista!\n";

    return true;
  }

  /**
   * Copia los archivos de Resources/{Fonts,Helper,Ico,Library} al directorio App/Rsc/ del proyecto.
   * Para librerías del framework (Library/Charts, Library/Gsap) e iconos del framework (Ico/),
   * sincroniza automáticamente si el archivo origen ha sido actualizado, garantizando que
   * las correcciones y mejoras del framework se reflejen en los proyectos derivados.
   */
  private function copyResources(): void
  {
    $projectRsc = $this->basePath . '/App/Rsc';

    if (!is_dir($this->frameworkResources)) {
      echo "⚠️  Directorio Resources del framework no encontrado: {$this->frameworkResources}\n";
      return;
    }

    if (!is_dir($projectRsc)) {
      @mkdir($projectRsc, 0755, true);
    }

    $copied = 0;
    $skipped = 0;
    $updated = 0;

    foreach (['Fonts', 'Helper', 'Ico', 'Library'] as $folder) {
      $src = $this->frameworkResources . '/' . $folder;
      $dest = $projectRsc . '/' . $folder;
      // Permitir actualización automática de recursos provistos por el framework (Library e Ico)
      $updateIfDifferent = ($folder === 'Library' || $folder === 'Ico');
      if (is_dir($src)) {
        $this->copyDirectory($src, $dest, $copied, $skipped, $updated, $updateIfDifferent);
      }
    }

    $detail = "{$copied} copiados, {$updated} actualizados, {$skipped} al día";
    echo "\n📦 Recursos en App/Rsc (Fonts, Helper, Ico y Library): {$detail}\n";
  }

  /**
   * Copia recursivamente un directorio respetando archivos personalizados del usuario
   * e ignorando cualquier archivo .gitkeep.
   *
   * @param string $source Directorio origen
   * @param string $dest Directorio destino
   * @param int $copied Contador de archivos creados
   * @param int $skipped Contador de archivos omitidos
   * @param int $updated Contador de archivos actualizados
   * @param bool $updateIfDifferent Si es true y el archivo origen difiere del destino, lo actualiza
   */
  private function copyDirectory(string $source, string $dest, int &$copied, int &$skipped, int &$updated = 0, bool $updateIfDifferent = false): void
  {
    $dir = opendir($source);
    if (!$dir) {
      return;
    }

    if (!is_dir($dest)) {
      @mkdir($dest, 0755, true);
    }

    while (($file = readdir($dir)) !== false) {
      if ($file === '.' || $file === '..' || $file === '.gitkeep') {
        continue;
      }

      $srcFile = $source . '/' . $file;
      $destFile = $dest . '/' . $file;

      if (is_dir($srcFile)) {
        // No sobrescribir subcarpetas exclusivas del usuario como App/Rsc/Ico/Custom
        $isUserCustomDir = ($file === 'Custom');
        $this->copyDirectory($srcFile, $destFile, $copied, $skipped, $updated, $isUserCustomDir ? false : $updateIfDifferent);
      } else {
        if (file_exists($destFile)) {
          if ($updateIfDifferent && md5_file($srcFile) !== md5_file($destFile)) {
            if (@copy($srcFile, $destFile)) {
              $updated++;
            } else {
              $skipped++;
            }
          } else {
            $skipped++;
          }
        } else {
          if (@copy($srcFile, $destFile)) {
            $copied++;
          }
        }
      }
    }
    closedir($dir);
  }

  /**
   * Copia un archivo individual desde Resources/ hacia el proyecto solo si no existe en destino.
   */
  private function copyFileIfNotExists(string $srcRelativeToResources, string $destRelativeToProject): bool
  {
    $srcPath = $this->frameworkResources . '/' . ltrim($srcRelativeToResources, '/');
    $destPath = $this->basePath . '/' . ltrim($destRelativeToProject, '/');

    if (file_exists($destPath)) {
      echo "⏭️  Saltado: {$destRelativeToProject} (ya existe)\n";
      return true;
    }

    if (!is_dir(dirname($destPath))) {
      @mkdir(dirname($destPath), 0755, true);
    }

    if (file_exists($srcPath) && @copy($srcPath, $destPath)) {
      echo "✅ Creado: {$destRelativeToProject} (desde Resources/{$srcRelativeToResources})\n";
      return true;
    }

    echo "❌ Error: No se pudo copiar {$destRelativeToProject} desde Resources/{$srcRelativeToResources}\n";
    return false;
  }

  /**
   * Inicializa o actualiza Bootstrap/App.php desde Resources/Bootstrap/App.php.
   */
  private function createBootstrapFiles(): void
  {
    $appPhpPath = $this->basePath . '/Bootstrap/App.php';

    if (file_exists($appPhpPath)) {
      $content = (string)file_get_contents($appPhpPath);
      if (str_contains($content, "require_once __DIR__ . '/../providers.json.php';") || str_contains($content, "require_once __DIR__ . '/..//providers.json.php';")) {
        $content = str_replace(
          [
            "\$providers = require_once __DIR__ . '/../providers.json.php';",
            "\$providers = require_once __DIR__ . '/..//providers.json.php';"
          ],
          "\$providers = \\Core\\ConfigLoader\\ProviderLoader::load();",
          $content
        );
        if (file_put_contents($appPhpPath, $content)) {
          echo "🔧 Actualizado: Bootstrap/App.php para usar ProviderLoader\n";
        }
      } else {
        echo "⏭️  Saltado: Bootstrap/App.php (ya existe y está actualizado)\n";
      }
      return;
    }

    $this->copyFileIfNotExists('Bootstrap/App.php', 'Bootstrap/App.php');
  }

  /**
   * Copia los archivos iniciales de configuración desde Resources/Config/ a App/Config/
   * respetando las configuraciones ya existentes en el proyecto.
   */
  private function createConfigFiles(): void
  {
    $oldConfigPath = $this->basePath . '/config.php';
    $newConfigPath = $this->basePath . '/App/Config/config.php';

    if (!is_dir(dirname($newConfigPath))) {
      @mkdir(dirname($newConfigPath), 0755, true);
    }

    // Si en un proyecto derivado existe el antiguo config.php en la raíz, trasladarlo a App/Config/config.php
    if (file_exists($oldConfigPath) && !file_exists($newConfigPath) && !$this->isFrameworkRepo) {
      $configContent = (string)file_get_contents($oldConfigPath);
      $configContent = str_replace(
        ['$basePath = __DIR__;', '$basePath = __DIR__ ;'],
        '$basePath = dirname(__DIR__, 2);',
        $configContent
      );
      if (file_put_contents($newConfigPath, $configContent)) {
        @unlink($oldConfigPath);
        echo "🚚 Trasladado y corregido: config.php a App/Config/config.php\n";
      }
    }

    // Si App/Config/config.php ya existe, validar su basePath y no sobrescribir
    if (file_exists($newConfigPath)) {
      $configContent = (string)file_get_contents($newConfigPath);
      if (str_contains($configContent, '$basePath = __DIR__;')) {
        $configContent = str_replace(
          ['$basePath = __DIR__;', '$basePath = __DIR__ ;'],
          '$basePath = dirname(__DIR__, 2);',
          $configContent
        );
        file_put_contents($newConfigPath, $configContent);
        echo "🔧 Corregido: basePath en App/Config/config.php\n";
      } else {
        echo "⏭️  Saltado: App/Config/config.php (ya existe)\n";
      }

      if (file_exists($oldConfigPath) && !$this->isFrameworkRepo) {
        @unlink($oldConfigPath);
      }
    } else {
      $this->copyFileIfNotExists('Config/config.php', 'App/Config/config.php');
    }

    // Copiar resto de archivos de configuración desde Resources/Config/ si no existen
    $this->copyFileIfNotExists('Config/CookieConfiguration.php', 'App/Config/CookieConfiguration.php');
    $this->copyFileIfNotExists('Config/CorsConfiguration.php', 'App/Config/CorsConfiguration.php');
    $this->copyFileIfNotExists('Config/TokenConfiguration.php', 'App/Config/TokenConfiguration.php');
    $this->copyFileIfNotExists('Config/preloadFonts.json', 'App/Config/preloadFonts.json');

    // loadLibraryJsConfiguration.php (con migración automática a Charts si ya existe)
    $libConfigPath = $this->basePath . '/App/Config/loadLibraryJsConfiguration.php';
    if (file_exists($libConfigPath)) {
      $content = (string)file_get_contents($libConfigPath);
      if (!str_contains($content, "'Charts'") && !str_contains($content, '"Charts"')) {
        if (str_contains($content, "'Gsap',")) {
          $content = str_replace("'Gsap',", "'Gsap',\n    'Charts',", $content);
          file_put_contents($libConfigPath, $content);
          echo "🔧 Actualizado: App/Config/loadLibraryJsConfiguration.php (agregada librería 'Charts')\n";
          return;
        } elseif (str_contains($content, "'Gsap'")) {
          $content = str_replace("'Gsap'", "'Gsap',\n    'Charts'", $content);
          file_put_contents($libConfigPath, $content);
          echo "🔧 Actualizado: App/Config/loadLibraryJsConfiguration.php (agregada librería 'Charts')\n";
          return;
        }
      }
      echo "⏭️  Saltado: App/Config/loadLibraryJsConfiguration.php (ya existe y está actualizado)\n";
    } else {
      $this->copyFileIfNotExists('Config/loadLibraryJsConfiguration.php', 'App/Config/loadLibraryJsConfiguration.php');
    }
  }

  /**
   * Copia MiddlewareInterface.php desde Resources/Middleware/MiddlewareInterface/ si no existe.
   */
  private function createMiddlewareInterface(): void
  {
    $this->copyFileIfNotExists(
      'Middleware/MiddlewareInterface/MiddlewareInterface.php',
      'App/Middleware/MiddlewareInterface/MiddlewareInterface.php'
    );
  }

  /**
   * Copia la plantilla de error desde Base/Error/HandlerError.php a App/errorViews/HandlerError.php.
   */
  private function copyErrorView(): void
  {
    $source = __DIR__ . '/../Error/HandlerError.php';
    $dest = $this->basePath . '/App/errorViews/HandlerError.php';

    if (file_exists($dest)) {
      echo "⏭️  Saltado: App/errorViews/HandlerError.php (ya existe)\n";
      return;
    }

    if (!file_exists($source)) {
      echo "⚠️  No se encontró el template de error en: {$source}\n";
      return;
    }

    if (@copy($source, $dest)) {
      echo "✅ Creado: App/errorViews/HandlerError.php\n";
    } else {
      echo "❌ Error: No se pudo crear App/errorViews/HandlerError.php\n";
    }
  }

  /**
   * Copia _configTheme.css y theme.css desde Resources/Public/Css/ a App/Public/Css/ si no existen.
   * Si _configTheme.css ya existe pero proviene de una versión previa sin variables de animación/hover
   * o derivaciones de modo oscuro, las sincroniza respetando todos los colores personalizados del usuario.
   */
  private function createThemeConfig(): void
  {
    $destThemeConfig = $this->basePath . '/App/Public/Css/_configTheme.css';
    if (file_exists($destThemeConfig)) {
      if (class_exists('\\Base\\Module\\JitCssModule')) {
        if (!\Base\Module\JitCssModule::syncThemeConfigVariables($destThemeConfig, true)) {
          echo "⏭️  Saltado: App/Public/Css/_configTheme.css (ya existe y está actualizado)\n";
        }
      } else {
        echo "⏭️  Saltado: App/Public/Css/_configTheme.css (ya existe)\n";
      }
    } else {
      $this->copyFileIfNotExists('Public/Css/_configTheme.css', 'App/Public/Css/_configTheme.css');
    }

    $this->copyFileIfNotExists('Public/Css/theme.css', 'App/Public/Css/theme.css');
  }

  /**
   * Copia el archivo de rutas inicial desde Resources/Route/web.php a App/Route/web.php
   * solo si el directorio App/Route no tiene ya archivos de rutas definidos.
   */
  private function createDefaultRouteFile(): void
  {
    $routeDir = $this->basePath . '/App/Route';
    $existingPhpRoutes = is_dir($routeDir) ? glob($routeDir . '/*.php') : [];

    if (!empty($existingPhpRoutes)) {
      echo "⏭️  Saltado: App/Route/web.php (el proyecto ya posee archivos de rutas)\n";
      return;
    }

    $this->copyFileIfNotExists('Route/web.php', 'App/Route/web.php');
  }

  /**
   * Inicializa los archivos de seguridad en App/Safety/ desde Resources/Safety/
   * y genera el mapa de rutas en App/Safety/routes_security.json.
   */
  private function createSafetyFiles(): void
  {
    $this->copyFileIfNotExists('Safety/blocked_ips.json', 'App/Safety/blocked_ips.json');
    $this->copyFileIfNotExists('Safety/intrusions.json', 'App/Safety/intrusions.json');

    $configPath = $this->basePath . '/App/Safety/routes_security.json';
    if (class_exists('\\Base\\Module\\Security\\RouteScanner')) {
      $routeDir = $this->basePath . '/App/Route';
      if (is_dir($routeDir) && class_exists('\\Core\\ConfigLoader\\RouteLoader')) {
        \Core\ConfigLoader\RouteLoader::load($routeDir);
      }
      \Base\Module\Security\RouteScanner::scanAndSaveRoutes($configPath);
      echo "✅ Actualizado: App/Safety/routes_security.json (mapa de rutas del proyecto)\n";
    }
  }

  private function createProvidersFiles(): void
  {
    $providersJsonPath = $this->basePath . '/providers.json';
    $providersJsonPhpPath = $this->basePath . '/providers.json.php';

    if (file_exists($providersJsonPhpPath) && !$this->isFrameworkRepo) {
      if (@unlink($providersJsonPhpPath)) {
        echo "🗑️  Eliminado: providers.json.php residual de la raíz\n";
      }
    }

    if (!file_exists($providersJsonPath)) {
      $content = json_encode(['providers' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      if (file_put_contents($providersJsonPath, $content)) {
        echo "✅ Creado: providers.json\n";
      } else {
        echo "❌ Error: No se pudo crear providers.json\n";
      }
    } else {
      echo "⏭️  Saltado: providers.json (ya existe)\n";
    }
  }

  private function createEnvFiles(): void
  {
    $envPath = $this->basePath . '/.env';
    $envExamplePath = $this->basePath . '/.env.example';
    $frameworkEnvExample = __DIR__ . '/../../.env.example';

    if (!file_exists($envPath)) {
      if (file_exists($frameworkEnvExample)) {
        if (@copy($frameworkEnvExample, $envPath)) {
          echo "✅ Creado: .env (desde plantilla del framework)\n";
        } else {
          echo "❌ Error: No se pudo crear .env\n";
        }
      } else {
        echo "⏭️  Saltado: .env (no se encontró plantilla)\n";
      }
    } else {
      echo "⏭️  Saltado: .env (ya existe)\n";
    }

    if (!$this->isFrameworkRepo && file_exists($frameworkEnvExample)) {
      if (@copy($frameworkEnvExample, $envExamplePath)) {
        echo "✅ Actualizado: .env.example (con la plantilla más reciente del framework)\n";
      }
    }
  }

  private function createJsConfig(): void
  {
    $jsConfigPath = $this->basePath . '/jsConfig.json';

    if (file_exists($jsConfigPath)) {
      echo "⏭️  Saltado: jsConfig.json (ya existe)\n";
      return;
    }

    $content = json_encode([
      'priority' => ['aaSvgStore.js', 'component.js', 'config.js', 'querys.js'],
      'exclude' => [],
      'functions' => [
        'defer' => new \stdClass(),
        'async' => new \stdClass()
      ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if (file_put_contents($jsConfigPath, $content)) {
      echo "✅ Creado: jsConfig.json\n";
    } else {
      echo "❌ Error: No se pudo crear jsConfig.json\n";
    }
  }

  /**
   * Crea o sincroniza .htaccess con las cabeceras de seguridad y mitigaciones CVE críticas
   * sin sobrescribir reglas o configuraciones personalizadas del proyecto.
   */
  private function createHtaccess(): void
  {
    $htaccessPath = $this->basePath . '/.htaccess';

    if (file_exists($htaccessPath)) {
      $content = (string)file_get_contents($htaccessPath);
      $updated = $content;
      $changed = false;

      // 1. Inyectar mitigación php_flag session.upload_progress.enabled Off (CVE-2026-109618 / CVE-2026-109619) si falta
      if (!str_contains($updated, 'session.upload_progress.enabled')) {
        $phpFlagBlock = <<<'HTACCESS'
DirectoryIndex index.php

# Mitigación contra CVE-2026-109618 y CVE-2026-109619 (PHP2Shell)
<IfModule mod_php.c>
  php_flag session.upload_progress.enabled Off
</IfModule>
<IfModule mod_php8.c>
  php_flag session.upload_progress.enabled Off
</IfModule>
HTACCESS;
        if (str_contains($updated, 'DirectoryIndex index.php')) {
          $updated = (string)preg_replace('/^DirectoryIndex\s+index\.php\r?$/m', $phpFlagBlock, $updated, 1);
          $changed = true;
        }
      }

      // 2. Inyectar bloqueo RewriteCond contra PHP_SESSION_UPLOAD_PROGRESS (CVE-2026-109619) si falta
      if (!str_contains($updated, 'PHP_SESSION_UPLOAD_PROGRESS')) {
        $rewriteBlock = <<<'HTACCESS'
RewriteEngine On

# Bloquear vectores de inyección de estado de progreso de sesión (CVE-2026-109619)
RewriteCond %{QUERY_STRING} PHP_SESSION_UPLOAD_PROGRESS [NC,OR]
RewriteCond %{HTTP_COOKIE} PHP_SESSION_UPLOAD_PROGRESS [NC]
RewriteRule ^ - [F,L]
HTACCESS;
        if (str_contains($updated, 'RewriteEngine On')) {
          $updated = (string)preg_replace('/^RewriteEngine\s+On\r?$/m', $rewriteBlock, $updated, 1);
          $changed = true;
        }
      }

      if ($changed && file_put_contents($htaccessPath, $updated) !== false) {
        echo "🔧 Actualizado: .htaccess (mitigaciones CVE-2026-109618 y CVE-2026-109619 sincronizadas)\n";
      } else {
        echo "⏭️  Saltado: .htaccess (ya existe y está actualizado)\n";
      }
      return;
    }

    $frameworkHtaccess = __DIR__ . '/../../.htaccess';
    if (file_exists($frameworkHtaccess) && @copy($frameworkHtaccess, $htaccessPath)) {
      echo "✅ Creado: .htaccess\n";
      return;
    }
  }

  /**
   * Crea o actualiza index.php aplicando protecciones de seguridad
   * (como sanitización anti-CRLF en FORCE_DOMAIN) respetando el código existente.
   */
  private function createIndexPhp(): void
  {
    $indexPath = $this->basePath . '/index.php';

    if (file_exists($indexPath)) {
      if ($this->isFrameworkRepo) {
        echo "⏭️  Saltado: index.php (ya existe y estamos en el repositorio del framework)\n";
        return;
      }

      $content = (string)file_get_contents($indexPath);
      $updated = $content;
      $changed = false;

      // 1. Migración de ruta antigua de config.php a App/Config/config.php
      if (str_contains($updated, "require_once __DIR__ . '/config.php';")) {
        $updated = str_replace(
          "require_once __DIR__ . '/config.php';",
          "require_once __DIR__ . '/App/Config/config.php';",
          $updated
        );
        $changed = true;
      }

      // 2. Sanitización anti-CRLF Header Injection en redirección de FORCE_DOMAIN
      if (str_contains($updated, 'FORCE_DOMAIN') && str_contains($updated, '$_SERVER[\'REQUEST_URI\']') && !str_contains($updated, '$safeRequestUri')) {
        $pattern = '/header\(\s*["\']Location:\s*["\']\s*\.\s*\$protocol\s*\.\s*FORCE_DOMAIN\s*\.\s*\$_SERVER\[[\'"]REQUEST_URI[\'"]\]\s*\);/s';
        $replacement = <<<'PHP'
$safeRequestUri = str_replace(["\r", "\n", "%0d", "%0a", "%0D", "%0A"], '', (string)($_SERVER['REQUEST_URI'] ?? '/'));
        $safeRequestUri = '/' . ltrim($safeRequestUri, '/');
        header("Location: " . $protocol . FORCE_DOMAIN . $safeRequestUri);
PHP;
        $replaced = (string)preg_replace($pattern, $replacement, $updated, 1);
        if ($replaced !== $updated) {
          $updated = $replaced;
          $changed = true;
        }
      }

      // 3. Normalización para admitir 'prod' y 'production' en ENVIRONMENT
      if (str_contains($updated, "ENVIRONMENT === 'production'")) {
        $updated = str_replace(
          "ENVIRONMENT === 'production'",
          "in_array(strtolower((string)ENVIRONMENT), ['production', 'prod'], true)",
          $updated
        );
        $changed = true;
      }

      if ($changed && file_put_contents($indexPath, $updated)) {
        echo "🔧 Actualizado: index.php (seguridad y redirecciones sincronizadas)\n";
      } else {
        echo "⏭️  Saltado: index.php (ya existe y está actualizado)\n";
      }
      return;
    }

    $content = <<<'PHP'
<?php

define('ROOT_PATH', str_replace('\\', '/', __DIR__));

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/App/Config/config.php';

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on');

$isProd = defined('ENVIRONMENT') && in_array(strtolower((string)ENVIRONMENT), ['production', 'prod'], true);
if ($isProd && defined('FORCE_DOMAIN') && !empty(FORCE_DOMAIN)) {
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    if ($currentHost !== FORCE_DOMAIN) {
        $protocol = $isHttps ? "https://" : "http://";
        $safeRequestUri = str_replace(["\r", "\n", "%0d", "%0a", "%0D", "%0A"], '', (string)($_SERVER['REQUEST_URI'] ?? '/'));
        $safeRequestUri = '/' . ltrim($safeRequestUri, '/');
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $protocol . FORCE_DOMAIN . $safeRequestUri);
        exit();
    }
}

require_once __DIR__ . '/Bootstrap/App.php';

\Core\Route::run();
PHP;

    if (file_put_contents($indexPath, $content)) {
      echo "✅ Creado: index.php\n";
    } else {
      echo "❌ Error: No se pudo crear index.php\n";
    }
  }

  /**
   * Crea o sincroniza .gitignore asegurando que todas las reglas esenciales
   * (seguridad, base de datos local, temporales y subidas) estén presentes
   * sin alterar las reglas personalizadas del proyecto.
   */
  private function createGitignore(): void
  {
    $gitignorePath = $this->basePath . '/.gitignore';

    $essentialIgnores = [
      '/vendor/',
      '.env',
      '/App/Public/Min/',
      '/Cache/',
      '/Logs/',
      '/Uploads/',
      '/Database/*',
      '*.mmdb',
      '*.sqlite',
      '*.sqlite-wal',
      '*.sqlite-shm',
    ];

    if (file_exists($gitignorePath)) {
      $content = (string)file_get_contents($gitignorePath);
      $missing = [];
      foreach ($essentialIgnores as $pattern) {
        $escaped = preg_quote($pattern, '/');
        if (!preg_match('/(^|\r?\n)\s*' . $escaped . '\s*($|\r?\n)/', $content)) {
          // Si ya existe una variante como /vendor/* o /Uploads/*, considerarlo cubierto
          if ($pattern === '/vendor/' && (str_contains($content, '/vendor/*') || str_contains($content, 'vendor/'))) {
            continue;
          }
          if ($pattern === '/Uploads/' && (str_contains($content, '/Uploads/*') || str_contains($content, 'Uploads/'))) {
            continue;
          }
          $missing[] = $pattern;
        }
      }

      if (!empty($missing)) {
        $append = "\n# Reglas esenciales del framework (sincronizadas por InitAppStructure)\n" . implode("\n", $missing) . "\n";
        if (file_put_contents($gitignorePath, rtrim($content) . "\n" . $append)) {
          echo "🔧 Actualizado: .gitignore (agregadas " . count($missing) . " reglas esenciales faltantes: " . implode(', ', $missing) . ")\n";
        }
      } else {
        echo "⏭️  Saltado: .gitignore (ya existe y está actualizado)\n";
      }
      return;
    }

    $content = <<<'GITIGNORE'
/vendor/
.env
/App/Public/Min/
/Cache/
/Logs/
/Uploads/
/Database/*
*.mmdb
*.sqlite
*.sqlite-wal
*.sqlite-shm
GITIGNORE;

    if (file_put_contents($gitignorePath, $content)) {
      echo "✅ Creado: .gitignore\n";
    } else {
      echo "❌ Error: No se pudo crear .gitignore\n";
    }
  }

  /**
   * Copia o actualiza el AGENTS_PROJECTS.md del framework como AGENTS.md al proyecto.
   * Si AGENTS.md ya existe y no tiene personalizaciones exclusivas del usuario,
   * lo mantiene sincronizado con las últimas convenciones y herramientas del framework.
   */
  private function copyAgentsFile(): void
  {
    $source = __DIR__ . '/../../AGENTS_PROJECTS.md';
    $dest = $this->basePath . '/AGENTS.md';

    if ($this->isFrameworkRepo) {
      echo "⏭️  Saltado: AGENTS.md (repo del framework, usa su propia guía)\n";
      return;
    }

    if (file_exists($dest)) {
      if (file_exists($source)) {
        $srcContent = (string)file_get_contents($source);
        $destContent = (string)file_get_contents($dest);
        if ($srcContent !== $destContent) {
          // Si el usuario no agregó una sección de reglas personalizadas explícitas, sincronizar
          if (!str_contains($destContent, '<!-- CUSTOM_PROJECT_RULES -->')) {
            @copy($source, $dest);
            echo "🔧 Actualizado: AGENTS.md (guía de convenciones sincronizada con el framework)\n";
            return;
          }
        }
      }
      echo "⏭️  Saltado: AGENTS.md (ya existe y está actualizado)\n";
      return;
    }

    if (!file_exists($source)) {
      echo "⚠️  No se encontró el template AGENTS.md en: {$source}\n";
      return;
    }

    if (@copy($source, $dest)) {
      echo "✅ Creado: AGENTS.md (guía de trabajo del proyecto)\n";
    } else {
      echo "❌ Error: No se pudo crear AGENTS.md\n";
    }
  }

  public static function run(): bool
  {
    $init = new self();
    return $init->create();
  }
}

// Ejecutar si se llama directamente desde CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
  InitAppStructure::run();
}
