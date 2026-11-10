<?php

/**
 * Eber Framework - CLI Runner Central (Frame CLI)
 * 
 * Despacha todos los comandos de la CLI del framework hacia sus respectivos
 * scripts en Base/ScriptComposer, tanto desde CMD como desde PowerShell o Composer.
 * 
 * Uso:
 *   frame <comando> [argumentos] [opciones]
 */

if (php_sapi_name() !== 'cli') {
  die("Este script solo puede ejecutarse desde la interfaz de comandos (CLI).\n");
}

class FrameCli
{
  private string $frameworkPath;
  private string $workingPath;

  /**
   * Definición de comandos disponibles, scripts correspondientes,
   * argumentos predeterminados, descripción y categorías.
   */
  private array $commands = [
    // --- Proyecto y Estructura ---
    'new' => [
      'script'   => 'NewProject.php',
      'desc'     => 'Crea un nuevo proyecto basado en el framework',
      'category' => 'Proyecto y Estructura',
      'usage'    => 'frame new <nombre> [directorio]',
      'aliases'  => ['new-project'],
    ],
    'init' => [
      'script'   => 'InitAppStructure.php',
      'desc'     => 'Inicializa o actualiza la estructura de directorios y recursos de /App',
      'category' => 'Proyecto y Estructura',
      'usage'    => 'frame init',
      'aliases'  => ['init-app'],
    ],
    'link' => [
      'script'   => 'LinkProject.php',
      'desc'     => 'Vincula o actualiza la ruta del framework en el composer.json del proyecto',
      'category' => 'Proyecto y Estructura',
      'usage'    => 'frame link',
      'aliases'  => ['relink'],
    ],

    // --- Despliegue y Producción ---
    'package' => [
      'script'   => 'DeployPackage.php',
      'prepend'  => ['package'],
      'desc'     => 'Genera el archivo ZIP de despliegue inicial completo (para cPanel/Hosting)',
      'category' => 'Despliegue y Producción',
      'usage'    => 'frame package',
      'aliases'  => [],
    ],
    'deploy' => [
      'script'   => 'DeployPackage.php',
      'prepend'  => ['deploy'],
      'desc'     => 'Despliega cambios al servidor web vía HTTP POST a deploy.php',
      'category' => 'Despliegue y Producción',
      'usage'    => 'frame deploy [--all]',
      'aliases'  => [],
    ],

    // --- Optimización y Assets ---
    'theme:build' => [
      'script'   => 'BuildTheme.php',
      'desc'     => 'Compila theme.json a App/Public/Css/_configTheme.css',
      'category' => 'Optimización y Assets',
      'usage'    => 'frame theme:build [--force]',
      'aliases'  => ['theme', 'build-theme'],
    ],
    'theme:studio' => [
      'script'   => 'OpenStudio.php',
      'desc'     => 'Abre o muestra la URL de Theme Studio en entorno local',
      'category' => 'Optimización y Assets',
      'usage'    => 'frame theme:studio [--port=8000]',
      'aliases'  => ['studio'],
    ],
    'min' => [
      'script'   => 'MinJsCss.php',
      'desc'     => 'Compila CSS JIT y minifica CSS/JS a App/Public/Min',
      'category' => 'Optimización y Assets',
      'usage'    => 'frame min',
      'aliases'  => ['min-script', 'build'],
    ],
    'image:convert' => [
      'script'   => 'ConvertImage.php',
      'desc'     => 'Conversor interactivo de imágenes (GIF animado, WebP, AVIF, PNG, JPG)',
      'category' => 'Optimización y Assets',
      'usage'    => 'frame image:convert',
      'aliases'  => ['convert-image', 'convert'],
    ],

    // --- Tipografía y Fuentes ---
    'font:install' => [
      'script'   => 'InstallFont.php',
      'desc'     => 'Instalador interactivo de fuentes tipográficas (.woff2, .ttf, .otf)',
      'category' => 'Tipografía y Fuentes',
      'usage'    => 'frame font:install',
      'aliases'  => ['install-font', 'font:add'],
    ],
    'font:reset' => [
      'script'   => 'ResetFont.php',
      'desc'     => 'Gestor y reseteador de fuentes instaladas en el proyecto',
      'category' => 'Tipografía y Fuentes',
      'usage'    => 'frame font:reset',
      'aliases'  => ['reset-font', 'font:remove'],
    ],

    // --- Base de Datos ---
    'db:mysql' => [
      'script'   => 'ConfigInitBdMySql.php',
      'desc'     => 'Inicializa tablas y esquemas en base de datos MySQL / MariaDB',
      'category' => 'Base de Datos',
      'usage'    => 'frame db:mysql',
      'aliases'  => ['create-table-mysql', 'db-mysql'],
    ],
    'db:pgsql' => [
      'script'   => 'ConfigInitBdPostgreSql.php',
      'desc'     => 'Inicializa tablas y esquemas en base de datos PostgreSQL',
      'category' => 'Base de Datos',
      'usage'    => 'frame db:pgsql',
      'aliases'  => ['create-table-pgsql', 'db-pgsql'],
    ],

    // --- Seguridad y Servicios ---
    'routes:scan' => [
      'script'   => 'ScanRoutes.php',
      'desc'     => 'Escanea y cachea las rutas de la aplicación en routes_security.json',
      'category' => 'Seguridad y Servicios',
      'usage'    => 'frame routes:scan',
      'aliases'  => ['scan-routes', 'routes'],
    ],
    'geoip:update' => [
      'script'   => 'UpdateGeoIp.php',
      'desc'     => 'Descarga y actualiza la base de datos local MaxMind GeoLite2-City.mmdb',
      'category' => 'Seguridad y Servicios',
      'usage'    => 'frame geoip:update',
      'aliases'  => ['update-geoip', 'geoip'],
    ],
    'key:rotate' => [
      'script'   => 'RotateKeys.php',
      'desc'     => 'Rota la semilla de seguridad (SEED) y el token de despliegue (DEPLOY_TOKEN)',
      'category' => 'Seguridad y Servicios',
      'usage'    => 'frame key:rotate [--seed] [--token] [--force]',
      'aliases'  => ['key:generate', 'rotate:keys', 'rotate-seed'],
    ],

    // --- Generadores (make) ---
    'make:controller' => [
      'script'   => 'MakeCli.php',
      'prepend'  => ['controller'],
      'desc'     => 'Crea un nuevo controlador en App/Controllers/',
      'category' => 'Generadores (make)',
      'usage'    => 'frame make:controller [Nombre]',
      'aliases'  => [],
    ],
    'make:model' => [
      'script'   => 'MakeCli.php',
      'prepend'  => ['model'],
      'desc'     => 'Crea un nuevo modelo en App/Models/',
      'category' => 'Generadores (make)',
      'usage'    => 'frame make:model [Nombre]',
      'aliases'  => [],
    ],
    'make:component' => [
      'script'   => 'MakeCli.php',
      'prepend'  => ['component'],
      'desc'     => 'Crea un nuevo componente en App/Components/',
      'category' => 'Generadores (make)',
      'usage'    => 'frame make:component [Nombre]',
      'aliases'  => [],
    ],
    'make:middleware' => [
      'script'   => 'MakeCli.php',
      'prepend'  => ['middleware'],
      'desc'     => 'Crea un nuevo middleware en App/Middleware/',
      'category' => 'Generadores (make)',
      'usage'    => 'frame make:middleware [Nombre]',
      'aliases'  => [],
    ],
    'make' => [
      'script'   => 'MakeCli.php',
      'desc'     => 'Asistente interactivo o generador rápido: make <tipo> [Nombre]',
      'category' => 'Generadores (make)',
      'usage'    => 'frame make [tipo] [Nombre]',
      'aliases'  => [],
    ],

    // --- Idiomas (i18n) ---
    'lang:make' => [
      'script'   => 'LangCli.php',
      'prepend'  => ['make'],
      'desc'     => 'Crea un nuevo idioma en App/Lang/<locale>/ clonando claves del idioma base',
      'category' => 'Idiomas (i18n)',
      'usage'    => 'frame lang:make <locale> [--auto]',
      'aliases'  => [],
    ],
    'lang:check' => [
      'script'   => 'LangCli.php',
      'prepend'  => ['check'],
      'desc'     => 'Compara todos los idiomas contra el idioma por defecto y reporta claves faltantes',
      'category' => 'Idiomas (i18n)',
      'usage'    => 'frame lang:check',
      'aliases'  => [],
    ],
    'lang:scan' => [
      'script'   => 'LangCli.php',
      'prepend'  => ['scan'],
      'desc'     => 'Escanea vistas y segmentos buscando claves __() que no existan en diccionarios',
      'category' => 'Idiomas (i18n)',
      'usage'    => 'frame lang:scan',
      'aliases'  => [],
    ],
    'lang:export' => [
      'script'   => 'LangCli.php',
      'prepend'  => ['export'],
      'desc'     => 'Exporta todos los diccionarios de idioma a un archivo JSON o CSV',
      'category' => 'Idiomas (i18n)',
      'usage'    => 'frame lang:export [json|csv] [archivo]',
      'aliases'  => [],
    ],
    'lang:import' => [
      'script'   => 'LangCli.php',
      'prepend'  => ['import'],
      'desc'     => 'Importa traducciones desde un archivo JSON o CSV hacia App/Lang/',
      'category' => 'Idiomas (i18n)',
      'usage'    => 'frame lang:import <archivo>',
      'aliases'  => [],
    ],
  ];

  public function __construct()
  {
    $this->frameworkPath = str_replace('\\', '/', dirname(__DIR__, 2));
    $this->workingPath   = str_replace('\\', '/', getcwd());
  }

  public static function run(array $argv): void
  {
    $cli = new self();
    $exitCode = $cli->execute($argv);
    exit($exitCode);
  }

  public function execute(array $argv): int
  {
    // Quitar el nombre del script ejecutor ($argv[0])
    array_shift($argv);

    $command = strtolower(trim((string)($argv[0] ?? '')));

    // Sin argumentos o petición explícita de ayuda
    if (empty($command) || in_array($command, ['help', '--help', '-h', 'list'], true)) {
      $this->showHelp();
      return 0;
    }

    // Consulta de versión
    if (in_array($command, ['version', '--version', '-v'], true)) {
      $this->showVersion();
      return 0;
    }

    // Quitar el comando principal de la lista de argumentos para pasar el resto
    array_shift($argv);

    // Resolver comandos canónicos y aliases
    $targetConfig = $this->resolveCommand($command);

    if ($targetConfig === null) {
      echo "\n\033[31m❌ Error: Comando no reconocido: \"{$command}\"\033[0m\n\n";
      echo "Ejecuta \033[36mframe help\033[0m para ver la lista completa de comandos disponibles.\n\n";
      return 1;
    }

    $scriptFile = $this->findScriptPath($targetConfig['script']);
    if ($scriptFile === null || !file_exists($scriptFile)) {
      echo "\n\033[31m❌ Error: No se encontró el script asociado \"{$targetConfig['script']}\".\033[0m\n\n";
      return 1;
    }

    // Preparar argumentos a pasar al script
    $finalArgs = [];
    if (!empty($targetConfig['prepend'])) {
      $finalArgs = array_merge($finalArgs, $targetConfig['prepend']);
    }
    $finalArgs = array_merge($finalArgs, $argv);

    // Sincronizar automáticamente vendor/eber/framework si el proyecto usa copia local (symlink: false)
    $this->syncProjectVendorFramework();

    return $this->dispatch($scriptFile, $finalArgs);
  }

  /**
   * Sincroniza los archivos del núcleo del framework (Base, Core, Resources, config.php)
   * hacia vendor/eber/framework del proyecto actual cuando se trabaja sin symlinks en Windows.
   */
  private function syncProjectVendorFramework(): void
  {
    $vendorFramework = $this->workingPath . '/vendor/eber/framework';
    if ($this->workingPath === $this->frameworkPath || !is_dir($vendorFramework) || is_link($vendorFramework)) {
      return;
    }

    $realSource = realpath($this->frameworkPath);
    $realTarget = realpath($vendorFramework);
    if ($realSource && $realTarget && $realSource === $realTarget) {
      return;
    }

    $dirsToSync = ['Base', 'Core', 'Resources'];
    foreach ($dirsToSync as $dir) {
      $srcDir = $this->frameworkPath . '/' . $dir;
      $dstDir = $vendorFramework . '/' . $dir;
      if (is_dir($srcDir)) {
        $this->syncDirectory($srcDir, $dstDir);
      }
    }

    $filesToSync = ['config.php', 'AGENTS.md', 'AGENTS_PROJECTS.md'];
    foreach ($filesToSync as $file) {
      $srcFile = $this->frameworkPath . '/' . $file;
      $dstFile = $vendorFramework . '/' . $file;
      if (is_file($srcFile)) {
        if (!is_file($dstFile) || filesize($srcFile) !== filesize($dstFile) || filemtime($srcFile) > filemtime($dstFile)) {
          @copy($srcFile, $dstFile);
        }
      }
    }
  }

  /**
   * Copia recursivamente los archivos nuevos o modificados de un directorio origen a destino.
   */
  private function syncDirectory(string $src, string $dst): void
  {
    if (!is_dir($dst)) {
      @mkdir($dst, 0755, true);
    }
    $items = @scandir($src);
    if (!is_array($items)) {
      return;
    }
    foreach ($items as $item) {
      if ($item === '.' || $item === '..') {
        continue;
      }
      $srcPath = $src . '/' . $item;
      $dstPath = $dst . '/' . $item;
      if (is_dir($srcPath)) {
        $this->syncDirectory($srcPath, $dstPath);
      } elseif (is_file($srcPath)) {
        if (!is_file($dstPath) || filesize($srcPath) !== filesize($dstPath) || filemtime($srcPath) > filemtime($dstPath)) {
          @copy($srcPath, $dstPath);
        }
      }
    }
  }

  /**
   * Resuelve el nombre del comando contra el mapa de comandos o sus aliases.
   */
  private function resolveCommand(string $command): ?array
  {
    if (isset($this->commands[$command])) {
      return $this->commands[$command];
    }

    foreach ($this->commands as $key => $config) {
      if (!empty($config['aliases']) && in_array($command, $config['aliases'], true)) {
        return $config;
      }
    }

    return null;
  }

  /**
   * Busca la ubicación del script PHP (prioriza la ruta canónica del framework global,
   * y luego recurre a vendor/eber/framework del proyecto local si fuera necesario).
   */
  private function findScriptPath(string $scriptName): ?string
  {
    $globalFrameworkScript = $this->frameworkPath . '/Base/ScriptComposer/' . $scriptName;
    if (file_exists($globalFrameworkScript)) {
      return $globalFrameworkScript;
    }

    $localProjectScript = $this->workingPath . '/vendor/eber/framework/Base/ScriptComposer/' . $scriptName;
    if (file_exists($localProjectScript)) {
      return $localProjectScript;
    }

    return null;
  }

  /**
   * Despacha el comando en un proceso PHP secundario independiente con STDIN/STDOUT directos.
   */
  private function dispatch(string $scriptFile, array $args): int
  {
    $phpBinary = PHP_BINARY ?: 'php';

    $cmd = array_merge([$phpBinary, $scriptFile], $args);

    $descriptors = [
      0 => STDIN,
      1 => STDOUT,
      2 => STDERR
    ];

    $process = proc_open($cmd, $descriptors, $pipes, $this->workingPath);

    if (is_resource($process)) {
      return proc_close($process);
    }

    echo "\n\033[31m❌ Error: No se pudo iniciar el proceso PHP para {$scriptFile}.\033[0m\n";
    return 1;
  }

  /**
   * Muestra el menú de ayuda formateado y organizado por secciones.
   */
  private function showHelp(): void
  {
    echo "\n";
    echo "\033[1;36m======================================================\033[0m\n";
    echo "\033[1;32m 🚀 EBER FRAMEWORK CLI — HERRAMIENTAS Y SCRIPTS\033[0m\n";
    echo "\033[1;36m======================================================\033[0m\n";
    echo "Uso:\n";
    echo "  \033[33mframe <comando> [argumentos] [opciones]\033[0m\n\n";

    // Agrupar comandos por categoría
    $categories = [];
    foreach ($this->commands as $cmdName => $info) {
      $categories[$info['category']][$cmdName] = $info;
    }

    foreach ($categories as $catName => $items) {
      echo "\033[1;35m" . strtoupper($catName) . ":\033[0m\n";
      foreach ($items as $name => $item) {
        $aliasesStr = !empty($item['aliases']) ? ' (alias: ' . implode(', ', $item['aliases']) . ')' : '';
        printf("  \033[32m%-22s\033[0m %s\033[90m%s\033[0m\n", $name, $item['desc'], $aliasesStr);
      }
      echo "\n";
    }

    echo "\033[1;35mINFORMACIÓN Y AYUDA:\033[0m\n";
    printf("  \033[32m%-22s\033[0m %s\n", "help", "Muestra esta ayuda completa de comandos");
    printf("  \033[32m%-22s\033[0m %s\n", "version", "Muestra la versión instalada del framework");
    echo "\n";
    echo "\033[1;36m======================================================\033[0m\n\n";
  }

  /**
   * Muestra la versión del framework.
   */
  private function showVersion(): void
  {
    $version = 'dev-main';
    $composerJson = $this->frameworkPath . '/composer.json';
    if (file_exists($composerJson)) {
      $data = json_decode((string)file_get_contents($composerJson), true);
      if (!empty($data['version'])) {
        $version = $data['version'];
      }
    }

    echo "\n\033[1;32mEber Framework CLI\033[0m versión \033[33m{$version}\033[0m\n\n";
  }
}

// Iniciar ejecución
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
  FrameCli::run($_SERVER['argv'] ?? []);
}
