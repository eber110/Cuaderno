<?php

/**
 * Generador interactivo y CLI para crear nuevos proyectos basados en Eber Framework.
 * 
 * Uso interactivo:
 *   frame new
 *   composer new-project
 *   php Base/ScriptComposer/NewProject.php
 * 
 * Uso con parámetros:
 *   frame new mi-proyecto [directorio_destino]
 *   php Base/ScriptComposer/NewProject.php mi-proyecto [directorio_destino]
 */

if (php_sapi_name() !== 'cli') {
  die("Este script solo puede ejecutarse desde la interfaz de comandos (CLI).\n");
}

class NewProject
{
  private string $frameworkPath;
  private $stdin;

  public function __construct()
  {
    $this->frameworkPath = str_replace('\\', '/', dirname(__DIR__, 2));

    // Configurar STDIN compatible con entornos Windows y Composer run-script
    $this->stdin = STDIN;
    if (getenv('APP_TEST_ENV') !== '1') {
      if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $conin = @fopen('\\\\.\\CONIN$', 'r');
        if ($conin) {
          $this->stdin = $conin;
        }
      } else {
        if (file_exists('/dev/tty') && is_readable('/dev/tty')) {
          $tty = @fopen('/dev/tty', 'r');
          if ($tty) {
            $this->stdin = $tty;
          }
        }
      }
    }
  }

  public function run(array $args): void
  {
    echo "\n======================================================\n";
    echo "🚀 FRAME — GENERADOR DE NUEVOS PROYECTOS\n";
    echo "======================================================\n\n";

    // Si se pasa 'new' o 'new-project' como primer argumento (ej: frame new tienda)
    if (isset($args[1]) && in_array(strtolower($args[1]), ['new', 'new-project'], true)) {
      array_splice($args, 1, 1);
    }

    if (isset($args[1]) && in_array(strtolower($args[1]), ['--help', '-h', 'help'], true)) {
      echo "Uso:\n";
      echo "  frame new                     (Modo interactivo)\n";
      echo "  frame new <nombre>            (Crea en directorio por defecto)\n";
      echo "  frame new <nombre> <ruta>     (Crea en la ruta indicada, ej: Google Drive)\n\n";
      exit(0);
    }

    $projectName = $args[1] ?? null;
    $targetDir = $args[2] ?? null;

    // 1. Solicitar nombre del proyecto
    if (empty($projectName)) {
      echo "📦 Ingrese el nombre del proyecto (ej: cuaderno, mi-tienda, panel-admin):\n> ";
      $projectName = trim((string)fgets($this->stdin));
    }

    if (empty($projectName)) {
      echo "❌ Error: El nombre del proyecto no puede estar vacío.\n";
      exit(1);
    }

    // Normalizar nombre: convertir a slug seguro (minúsculas, guiones)
    $projectSlug = strtolower(preg_replace('/[^a-zA-Z0-9_\-\.]/', '-', trim($projectName)));
    $composerPackageName = str_contains($projectSlug, '/') ? $projectSlug : "eber/{$projectSlug}";

    // 2. Solicitar directorio de destino
    $parentDir = dirname($this->frameworkPath);
    $defaultTargetDir = str_replace('\\', '/', $parentDir . '/' . $projectSlug);

    if (empty($targetDir)) {
      echo "\n📍 Ruta del directorio donde se creará el proyecto\n";
      echo "   [Enter para usar por defecto: {$defaultTargetDir}]:\n> ";
      $inputDir = trim((string)fgets($this->stdin));
      $targetDir = !empty($inputDir) ? $inputDir : $defaultTargetDir;
    }

    // Normalizar ruta destino
    $targetDir = str_replace('\\', '/', rtrim($targetDir, '/\\'));

    // Si es ruta relativa, resolverla respecto al directorio de ejecución actual
    if (!preg_match('/^[a-zA-Z]:\//', $targetDir) && !str_starts_with($targetDir, '/')) {
      $targetDir = str_replace('\\', '/', getcwd()) . '/' . $targetDir;
    }

    // Validar si es el mismo directorio del framework
    if (realpath($targetDir) === realpath($this->frameworkPath)) {
      echo "❌ Error: El directorio destino no puede ser el mismo directorio del framework.\n";
      exit(1);
    }

    // Comprobar si el directorio existe y tiene archivos
    if (is_dir($targetDir)) {
      $files = array_diff(scandir($targetDir) ?: [], ['.', '..']);
      if (!empty($files)) {
        echo "\n⚠️  El directorio '{$targetDir}' ya existe y no está vacío.\n";
        echo "   ¿Desea continuar e instalar de todas formas? (s/N): ";
        $confirm = strtolower(trim((string)fgets($this->stdin)));
        if ($confirm !== 's' && $confirm !== 'si' && $confirm !== 'y' && $confirm !== 'yes') {
          echo "⏹️ Operación cancelada por el usuario.\n";
          exit(0);
        }
      }
    } else {
      if (!@mkdir($targetDir, 0755, true)) {
        echo "❌ Error: No se pudo crear el directorio '{$targetDir}'. Verifique los permisos.\n";
        exit(1);
      }
    }

    $realTarget = str_replace('\\', '/', realpath($targetDir) ?: $targetDir);

    echo "\n------------------------------------------------------\n";
    echo "📋 Configuración del nuevo proyecto:\n";
    echo "   • Paquete Composer: {$composerPackageName}\n";
    echo "   • Ubicación:        {$realTarget}\n";
    echo "   • Framework origen: {$this->frameworkPath}\n";
    echo "------------------------------------------------------\n\n";

    // 3. Generar contenido del composer.json
    $composerData = [
      'name' => $composerPackageName,
      'description' => 'Proyecto web dinámico creado con Eber Framework',
      'repositories' => [
        [
          'type' => 'path',
          'url' => $this->frameworkPath,
          'options' => [
            'symlink' => false
          ]
        ]
      ],
      'require' => [
        'eber/framework' => 'dev-main',
        'geoip2/geoip2' => '*'
      ],
      'autoload' => [
        'psr-4' => [
          'App\\' => 'App/'
        ]
      ],
      'scripts' => [
        'post-install-cmd' => '@php vendor/eber/framework/Base/ScriptComposer/InitAppStructure.php',
        'post-update-cmd' => '@php vendor/eber/framework/Base/ScriptComposer/InitAppStructure.php',
        'link' => '@php vendor/eber/framework/Base/ScriptComposer/LinkProject.php',
        'frame' => '@php vendor/eber/framework/Base/ScriptComposer/FrameCli.php',
        'package' => '@php vendor/eber/framework/Base/ScriptComposer/DeployPackage.php package',
        'deploy' => '@php vendor/eber/framework/Base/ScriptComposer/DeployPackage.php deploy',
        'min-script' => '@php vendor/eber/framework/Base/ScriptComposer/MinJsCss.php',
        'create-table-mysql' => '@php vendor/eber/framework/Base/ScriptComposer/ConfigInitBdMySql.php',
        'create-table-pgsql' => '@php vendor/eber/framework/Base/ScriptComposer/ConfigInitBdPostgreSql.php',
        'install-font' => '@php vendor/eber/framework/Base/ScriptComposer/InstallFont.php',
        'reset-font' => '@php vendor/eber/framework/Base/ScriptComposer/ResetFont.php',
        'update-geoip' => '@php vendor/eber/framework/Base/ScriptComposer/UpdateGeoIp.php',
        'scan-routes' => '@php vendor/eber/framework/Base/ScriptComposer/ScanRoutes.php',
        'key:rotate' => '@php vendor/eber/framework/Base/ScriptComposer/RotateKeys.php',
        'convert-image' => '@php vendor/eber/framework/Base/ScriptComposer/ConvertImage.php',
        'make:controller' => '@php vendor/eber/framework/Base/ScriptComposer/MakeCli.php controller',
        'make:model' => '@php vendor/eber/framework/Base/ScriptComposer/MakeCli.php model',
        'make:component' => '@php vendor/eber/framework/Base/ScriptComposer/MakeCli.php component',
        'make:middleware' => '@php vendor/eber/framework/Base/ScriptComposer/MakeCli.php middleware',
        'db-maintenance' => '@php App/Console/DatabaseMaintenanceScript.php',
        'setup-db' => '@php -r "require \'vendor/autoload.php\'; require \'App/Config/config.php\'; print_r(\\App\\DatabaseComponent\\SQLiteSetup::setupTables());"'
      ]
    ];

    $composerJsonFile = $realTarget . '/composer.json';
    $jsonContent = json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

    echo "📝 Generando composer.json en {$composerJsonFile}...\n";
    if (file_put_contents($composerJsonFile, $jsonContent) === false) {
      echo "❌ Error al escribir composer.json en '{$composerJsonFile}'.\n";
      exit(1);
    }
    echo "✅ composer.json creado exitosamente.\n\n";

    // 4. Ejecutar composer install dentro del nuevo directorio
    echo "⏳ Ejecutando 'composer install' para inicializar la estructura y dependencias...\n";
    echo "--------------------------------------------------------------------\n";

    $originalCwd = getcwd();
    chdir($realTarget);

    // Ejecutar composer install
    passthru('composer install', $installStatus);

    if ($installStatus !== 0) {
      echo "\n⚠️  'composer install' finalizó con código de retorno {$installStatus}.\n";
      chdir($originalCwd);
      exit($installStatus);
    }

    // 5. Compilar assets JIT iniciales con min-script
    echo "\n🎨 Compilando assets iniciales (JIT CSS / JS)...\n";
    passthru('composer min-script', $minStatus);

    chdir($originalCwd);

    echo "\n======================================================\n";
    echo "🎉 ¡PROYECTO '{$projectSlug}' CREADO Y CONFIGURADO CON ÉXITO!\n";
    echo "======================================================\n\n";
    echo "👉 Próximos pasos:\n";
    echo "   1. Accede al directorio de tu proyecto:\n";
    echo "      cd \"{$realTarget}\"\n\n";
    echo "   2. Configura tu entorno en el archivo .env (base de datos, URL, etc.)\n\n";
    echo "   3. Inicia el servidor de desarrollo local:\n";
    echo "      php -S localhost:8000\n\n";
    echo "   4. Generadores disponibles en tu nuevo proyecto:\n";
    echo "      composer make:controller MiControlador\n";
    echo "      composer make:model MiModelo\n";
    echo "      composer make:component MiComponente\n";
    echo "      composer min-script\n\n";
    echo "🚀 ¡A programar!\n";
  }
}

// Iniciar ejecución
$creator = new NewProject();
$creator->run($argv);
