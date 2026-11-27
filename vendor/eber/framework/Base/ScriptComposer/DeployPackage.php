<?php

/**
 * Gestor de Despliegue y Empaquetado para Eber Framework.
 * 
 * Comandos:
 *   frame package                 -> Genera el ZIP completo inicial para cPanel/Hosting.
 *   frame deploy                  -> Despliega código actualizado vía HTTP POST a deploy.php.
 *   frame deploy --all            -> Despliega código y vendor/ vía HTTP POST.
 */

if (php_sapi_name() !== 'cli') {
  die("Este script solo puede ejecutarse desde la interfaz de comandos (CLI).\n");
}

class DeployPackage
{
  /**
   * Rutas de herramientas de diseño (Theme Studio) y documentación excluidas de producción.
   *
   * @var array<int, string>
   */
  public const EXCLUDED_STUDIO_PATHS = [
    'Base/Studio/',
    'Base/ScriptComposer/',
    'Base/Views/themeStudio.php',
    'Base/Control/ThemeStudioControl.php',
    'Docs/',
    'tests/',
    'vendor/eber/framework/Base/Studio/',
    'vendor/eber/framework/Base/ScriptComposer/',
    'vendor/eber/framework/Base/Views/themeStudio.php',
    'vendor/eber/framework/Base/Control/ThemeStudioControl.php',
    'vendor/eber/framework/Docs/',
    'vendor/eber/framework/tests/',
  ];

  /**
   * Respuestas explícitas aceptadas para confirmar un despliegue a producción.
   *
   * @var array<int, string>
   */
  public const DEPLOY_CONFIRMATION_ANSWERS = ['yes', 'y', 'Yes', 'YES'];

  /**
   * Archivos específicos dentro de directorios excluidos que deben conservarse en producción
   * (ej. font-project.css como catálogo de fuentes instaladas para selectores runtime).
   *
   * @var array<int, string>
   */
  public const PRODUCTION_INCLUDE_OVERRIDES = [
    'App/Public/Css/font-project.css',
  ];

  private string $basePath;
  private string $projectName;
  private string $frameworkPath;

  /**
   * Valida si la respuesta ingresada por el usuario confirma explícitamente el despliegue.
   *
   * @param string $input Texto ingresado por consola.
   * @return bool
   */
  public static function isDeployConfirmed(string $input): bool
  {
    return in_array(trim($input), self::DEPLOY_CONFIRMATION_ANSWERS, true);
  }

  /**
   * Determina si una ruta relativa pertenece a Theme Studio o Docs y debe excluirse de producción.
   *
   * @param string $relativePath Ruta relativa dentro del paquete o proyecto.
   * @return bool
   */
  public static function isExcludedStudioPath(string $relativePath): bool
  {
    $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');
    if ($normalized === '') {
      return false;
    }
    foreach (self::EXCLUDED_STUDIO_PATHS as $excluded) {
      $cleanExcluded = rtrim($excluded, '/');
      if ($normalized === $cleanExcluded || str_starts_with($normalized, $cleanExcluded . '/')) {
        return true;
      }
    }
    return false;
  }

  public function __construct(?string $basePath = null)
  {
    $this->basePath = rtrim(str_replace('\\', '/', $basePath ?? (string)getcwd()), '/');
    $this->projectName = basename($this->basePath);
    $this->frameworkPath = str_replace('\\', '/', dirname(__DIR__, 2));

    // Cargar autoloader de Composer si está disponible (para CaBundle y utilidades)
    $autoload = file_exists($this->basePath . '/vendor/autoload.php')
      ? $this->basePath . '/vendor/autoload.php'
      : $this->frameworkPath . '/vendor/autoload.php';
    if (file_exists($autoload)) {
      require_once $autoload;
    }

    // Si hay composer.json, intentar leer el nombre del paquete
    $composerJson = $this->basePath . '/composer.json';
    if (file_exists($composerJson)) {
      $data = json_decode((string)file_get_contents($composerJson), true);
      if (!empty($data['name'])) {
        $parts = explode('/', $data['name']);
        $this->projectName = end($parts);
      }
    }
  }

  public function run(array $args): void
  {
    require_once __DIR__ . '/FrameworkGuard.php';
    \Base\ScriptComposer\FrameworkGuard::abortIfFrameworkRepo("'frame package/deploy'", $this->basePath);

    $action = strtolower($args[1] ?? 'package');

    if ($action === 'deploy') {
      $includeVendor = in_array('--all', $args, true);
      $allowInsecureSsl = in_array('--allow-insecure-ssl', $args, true);
      $includeConfig = in_array('--config', $args, true);
      $rotateKey = in_array('--rotate-key', $args, true);
      $this->runDeploy($includeVendor, $allowInsecureSsl, $includeConfig, $rotateKey);
    } elseif ($action === 'package' || $action === 'init') {
      $this->runPackage();
    } else {
      echo "Comando no reconocido. Uso: frame package | frame deploy [--all] [--config] [--rotate-key] [--allow-insecure-ssl]\n";
      exit(1);
    }
  }

  /**
   * Obtiene la lista de librerías activas desde App/Config/loadLibraryJsConfiguration.php.
   *
   * @return array<int, string>
   */
  private function getActiveLibraries(): array
  {
    $libConfig = $this->basePath . '/App/Config/loadLibraryJsConfiguration.php';
    if (!is_file($libConfig)) {
      return [];
    }
    $loaded = require $libConfig;
    if (!is_array($loaded)) {
      return [];
    }
    $active = [];
    foreach ($loaded as $key => $val) {
      if (is_string($val) && $val !== '') {
        $active[] = $val;
      } elseif (is_string($key) && ($val === true || is_array($val))) {
        $active[] = $key;
      }
    }
    return array_values(array_unique($active));
  }

  /**
   * Construye la lista de rutas excluidas para mantener el paquete de producción ultra ligero.
   *
   * @return array<int, string>
   */
  private function getProductionExcludePaths(
    bool $includeConfig,
    bool $includeVaultKey,
    bool $includeCompiledConfig = false
  ): array {
    $excludes = [
      'App/Public/Uploads',
      'App/Public/Css',
      'App/Public/Js',
      'App/Config/theme.json',
      'App/Config/.studio.production.local.json',
      'App/Config/.gitignore',
      'App/Safety/.gitignore',
      'vendor/eber/framework/Resources/Css',
      'vendor/eber/framework/Resources/Js',
      'vendor/eber/framework/Resources/Ico',
      'vendor/eber/framework/Resources/Library',
      'vendor/eber/framework/Resources/Img',
    ];

    if (!$includeConfig && !$includeCompiledConfig) {
      $excludes[] = 'App/Config/compiled.config.php';
    }
    if (!$includeConfig) {
      $excludes[] = 'App/Config/secrets.vault.php';
    }
    if (!$includeVaultKey) {
      $excludes[] = 'App/Safety/vault.key.php';
    }

    // Podar librerías inactivas dentro de App/Rsc/Library/
    $libDir = $this->basePath . '/App/Rsc/Library';
    if (is_dir($libDir)) {
      $activeLibs = $this->getActiveLibraries();
      foreach (scandir($libDir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
          continue;
        }
        $full = $libDir . '/' . $entry;
        if (is_dir($full) && !in_array($entry, $activeLibs, true)) {
          $excludes[] = 'App/Rsc/Library/' . $entry;
        }
      }
    }

    return $excludes;
  }

  /**
   * Genera el ZIP completo inicial para subir una única vez al hosting (cPanel).
   * Incluye App (podado), Bootstrap, vendor, Database (SQLite), Uploads, configuración compilada + Vault cifrado y deploy.php.
   */
  public function runPackage(): void
  {
    echo "\n======================================================\n";
    echo "📦 FRAME — GENERADOR DE PRIMER DESPLIEGUE (ZIP)\n";
    echo "======================================================\n\n";

    echo "🔍 Proyecto detectado: {$this->projectName}\n";
    echo "📁 Directorio local:   {$this->basePath}\n\n";

    // 1. Compilar assets locales antes de empaquetar
    echo "🎨 Compilando assets JIT (min-script)...\n";
    $this->runMinScript();

    // 2. Asegurar existencia de deploy.php con token secreto
    $deployToken = $this->ensureDeployReceiverFile();

    // 3. Asegurar estructura de carpetas persistentes (Database con SQLite, Uploads)
    $this->ensurePersistentDirectories();

    // 4. Asegurar que .env tenga SEED y DEPLOY_TOKEN generados en local
    $this->ensureProjectEnv($deployToken);

    // 5. Compilar configuración no sensible y Vault cifrado AEAD para producción
    $hasCompiledVault = false;
    if (class_exists(\Base\Studio\Services\ConfigCompilerService::class)) {
      echo "🔐 Compilando configuración de producción y Vault AEAD...\n";
      $compileRes = \Base\Studio\Services\ConfigCompilerService::compile($this->basePath);
      if (!empty($compileRes['success'])) {
        $hasCompiledVault = true;
        echo "   ✓ Algoritmo: {$compileRes['cipher']} ({$compileRes['envCount']} vars + {$compileRes['secretsCount']} secretos cifrados)\n";
      } else {
        echo "   ⚠️  No se pudo compilar el Vault (" . ($compileRes['message'] ?? 'error') . "). Se incluirá .env como respaldo.\n";
      }
    }

    // 6. Crear carpeta de salida 'dist/'
    $distDir = $this->basePath . '/dist';
    if (!is_dir($distDir)) {
      @mkdir($distDir, 0755, true);
    }

    $zipFileName = "{$this->projectName}-primer-despliegue.zip";
    $zipFilePath = "{$distDir}/{$zipFileName}";

    if (file_exists($zipFilePath)) {
      echo "♻️  Se detectó un archivo ZIP previo ('dist/{$zipFileName}'). Eliminando versión antigua para generar un paquete nuevo y actualizado...\n";
      @unlink($zipFilePath);
    }

    echo "\n⏳ Empaquetando proyecto ligero en 'dist/{$zipFileName}'...\n";
    echo "🛡️  Excluyendo Base/Studio/, Docs/, tests/, ScriptComposer/, fuentes sin minificar y librerías inactivas...\n";

    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      echo "❌ Error: No se pudo crear el archivo ZIP en {$zipFilePath}\n";
      exit(1);
    }

    // Crear carpetas vacías requeridas en el ZIP para que el servidor tenga la estructura lista
    $zip->addEmptyDir('Database');
    $zip->addEmptyDir('Uploads');
    $zip->addEmptyDir('Cache');
    $zip->addEmptyDir('Cache/Views');
    $zip->addEmptyDir('Logs');
    $zip->addEmptyDir('Logs/UserLog');
    $zip->addEmptyDir('Logs/VisitLog');

    // Archivos y carpetas a incluir en el primer despliegue
    $includeItems = [
      'App',
      'Bootstrap',
      'Database',
      'Uploads',
      'vendor',
      'index.php',
      '.htaccess',
      'providers.json',
      'deploy.php'
    ];

    if (!$hasCompiledVault) {
      $includeItems[] = '.env';
      $includeItems[] = 'jsConfig.json';
    }

    // Opcionales si existen en la raíz
    if (file_exists($this->basePath . '/config.php')) {
      $includeItems[] = 'config.php';
    }
    if (file_exists($this->basePath . '/composer.json')) {
      $includeItems[] = 'composer.json';
    }

    $excludes = $this->getProductionExcludePaths(true, true);

    $fileCount = 0;
    foreach ($includeItems as $item) {
      if (self::isExcludedStudioPath($item)) {
        continue;
      }
      $fullPath = $this->basePath . '/' . $item;
      if (is_dir($fullPath)) {
        $fileCount += $this->addDirectoryToZip($zip, $fullPath, $item, $excludes);
      } elseif (is_file($fullPath)) {
        $zip->addFile($fullPath, $item);
        $fileCount++;
      }
    }

    $zip->close();

    $sizeMb = round(filesize($zipFilePath) / (1024 * 1024), 2);

    echo "\n======================================================\n";
    echo "🎉 ¡ZIP INICIAL GENERADO CON ÉXITO!\n";
    echo "======================================================\n";
    echo "📦 Archivo:     dist/{$zipFileName}\n";
    echo "📊 Tamaño:      {$sizeMb} MB ({$fileCount} archivos)\n";
    echo "🔑 Token Deploy: {$deployToken}\n";
    echo "🗄️  Base de Datos: Incluida en Database/\n";
    if ($hasCompiledVault) {
      echo "🔐 Configuración: Compilada + Vault cifrado en App/Config/ (sin .env en texto plano)\n";
    } else {
      echo "📄 Configuración: Archivo .env incluido en la raíz\n";
    }
    echo "======================================================\n\n";

    echo "👉 PASOS PARA EL SERVIDOR (Una única vez):\n";
    echo "   1. Sube 'dist/{$zipFileName}' a la carpeta de tu proyecto en public_html.\n";
    echo "   2. Descomprímelo en el servidor (incluye tu configuración compilada, Vault cifrado y Database/).\n";
    echo "   3. Agrega en tu archivo local '.env' la URL de tu sitio para futuros deploys:\n";
    echo "      DEPLOY_URL=https://tudominio.com/deploy.php\n\n";
    echo "✨ A partir de ahora, solo ejecutarás 'frame deploy' (o 'frame deploy --config' si editas variables).\n";
  }

  /**
   * Despliega actualizaciones continuas de código hacia el servidor sin tocar Database/ ni Uploads/.
   */
  public function runDeploy(
    bool $includeVendor = false,
    bool $allowInsecureSsl = false,
    bool $includeConfig = false,
    bool $rotateKey = false
  ): void {
    echo "\n======================================================\n";
    echo "🚀 FRAME — DESPLIEGUE RÁPIDO DE CÓDIGO\n";
    echo "======================================================\n\n";

    if ($rotateKey) {
      $includeConfig = true;
    }

    // 1. Leer configuración desde .env local o deploy.json
    $config = $this->loadDeployConfig();

    if (empty($config['url']) || empty($config['token'])) {
      echo "❌ Error: Configuración de despliegue no encontrada.\n";
      echo "   Debes definir en tu archivo .env local:\n";
      echo "   DEPLOY_URL=https://tudominio.com/deploy.php\n";
      echo "   DEPLOY_TOKEN=tu_token_secreto\n\n";
      exit(1);
    }

    $deployUrl = $config['url'];
    if (!str_ends_with(strtolower($deployUrl), '.php')) {
      $deployUrl = rtrim($deployUrl, '/') . '/deploy.php';
    }
    $deployToken = $config['token'];

    echo "🎯 Servidor destino: {$deployUrl}\n";
    echo "📦 Modo:            " . ($includeVendor ? "Completo (Código + vendor/)" : "Ligero (Solo código compilado App/)") . ($includeConfig ? " + Config/Vault" : "") . ($rotateKey ? " + Rotación Master Key" : "") . "\n\n";

    if (!$this->confirmDeploy()) {
      echo "\n🛑 Despliegue cancelado. No se compiló ni envió ningún cambio al servidor.\n\n";
      return;
    }
    echo "\n";

    // 2. Compilar assets locales
    echo "🎨 Compilando assets JIT (min-script)...\n";
    $this->runMinScript();

    // 3. Recompilar configuración y Vault si se solicitó --config o --rotate-key,
    //    o sincronizar jsConfig y librerías en compiled.config.php si ya existe.
    $includeCompiledConfig = false;
    if ($includeConfig && class_exists(\Base\Studio\Services\ConfigCompilerService::class)) {
      echo "🔐 Recompilando configuración de producción y Vault AEAD...\n";
      $compileRes = \Base\Studio\Services\ConfigCompilerService::compile($this->basePath, null, $rotateKey);
      if (empty($compileRes['success'])) {
        echo "❌ Error compilando configuración: " . ($compileRes['message'] ?? 'desconocido') . "\n";
        exit(1);
      }
      $includeCompiledConfig = true;
      echo "   ✓ Vault actualizado ({$compileRes['cipher']})\n";
    } else {
      $includeCompiledConfig = $this->syncCompiledJsForDeploy(false);
    }

    // 4. Crear ZIP temporal de actualización
    $tempZip = sys_get_temp_dir() . '/frame_update_' . uniqid() . '.zip';
    try {
      $fileCount = $this->createUpdateZip($tempZip, $includeVendor, $includeConfig, $rotateKey, $includeCompiledConfig);
    } catch (\RuntimeException $e) {
      echo "❌ Error al crear paquete temporal de despliegue.\n";
      exit(1);
    }

    $zipKb = round(filesize($tempZip) / 1024, 2);
    echo "\n📦 Paquete ligero generado: {$zipKb} KB ({$fileCount} archivos).\n";
    echo "🛡️  PROTECCIÓN ACTIVA: Database/, Uploads/, *.mmdb (GeoIP), .env, Base/Studio/, Docs/ y librerías inactivas excluidos.\n";

    // Pre-validación local contra el límite anti-ZipBomb de deploy.php (32 MB por archivo individual)
    $oversizedEntries = [];
    $inspectZip = new ZipArchive();
    if ($inspectZip->open($tempZip) === true) {
      $maxSingle = 32 * 1024 * 1024;
      for ($i = 0; $i < $inspectZip->numFiles; $i++) {
        $st = $inspectZip->statIndex($i);
        $entrySize = (int)($st['size'] ?? 0);
        if ($entrySize > $maxSingle) {
          $oversizedEntries[] = ($st['name'] ?? '?') . ' (' . round($entrySize / (1024 * 1024), 2) . ' MB)';
        }
      }
      $inspectZip->close();
    }
    if (!empty($oversizedEntries)) {
      @unlink($tempZip);
      echo "\n❌ Despliegue abortado antes de transferir: los siguientes archivos exceden el límite de 32 MB por archivo:\n";
      foreach ($oversizedEntries as $ov) {
        echo "   • {$ov}\n";
      }
      echo "\n💡 Sugerencia: excluye archivos pesados o sincroniza GeoIP con 'frame remote:geoip'.\n\n";
      exit(1);
    }

    echo "\n⏳ Transfiriendo y actualizando servidor...\n";

    // 5. Enviar vía cURL por HTTP POST
    $response = $this->sendPackage($deployUrl, $deployToken, $tempZip, $allowInsecureSsl, $rotateKey);
    @unlink($tempZip);

    if ($response['success']) {
      echo "\n======================================================\n";
      echo "🎉 ¡DESPLIEGUE COMPLETADO CON ÉXITO!\n";
      echo "======================================================\n";
      echo "⏱️  Tiempo/Respuesta del servidor: " . ($response['message'] ?? 'OK') . "\n";
      echo "📁 Archivos actualizados:         " . ($response['extracted_files'] ?? $fileCount) . "\n\n";
    } else {
      echo "\n❌ Falló el despliegue en el servidor:\n";
      echo "   " . ($response['error'] ?? 'Error desconocido') . "\n\n";
      exit(1);
    }
  }

  /**
   * Ejecuta MinJsCss.php del proyecto (o framework) para compilar JIT y minificar CSS/JS.
   */
  private function runMinScript(): void
  {
    $minScript = file_exists($this->basePath . '/vendor/eber/framework/Base/ScriptComposer/MinJsCss.php')
      ? $this->basePath . '/vendor/eber/framework/Base/ScriptComposer/MinJsCss.php'
      : $this->frameworkPath . '/Base/ScriptComposer/MinJsCss.php';

    if (file_exists($minScript)) {
      @passthru(PHP_BINARY . ' ' . escapeshellarg($minScript));
      return;
    }

    @passthru('composer min-script');
  }

  /**
   * Sincroniza jsConfig.json y loadLibraryJsConfiguration.php dentro de compiled.config.php
   * si el proyecto utiliza configuración compilada, para que los cambios de módulos JS suban
   * en 'frame deploy' sin requerir --config.
   *
   * @param bool $includeConfig Si ya se recompiló la configuración completa con --config.
   * @return bool True si compiled.config.php debe incluirse en el paquete de actualización.
   */
  public function syncCompiledJsForDeploy(bool $includeConfig = false): bool
  {
    if ($includeConfig) {
      return true;
    }

    if (class_exists(\Base\Studio\Services\ConfigCompilerService::class)) {
      $syncRes = \Base\Studio\Services\ConfigCompilerService::syncJsConfig($this->basePath);
      if (!empty($syncRes['exists']) && !empty($syncRes['success'])) {
        if (!empty($syncRes['updated'])) {
          echo "🔄 Módulos JS y librerías sincronizados en App/Config/compiled.config.php\n";
        }
        return true;
      }
    }

    return false;
  }

  /**
   * Genera el archivo ZIP temporal de actualización para 'frame deploy'.
   *
   * @param string $zipPath Ruta de salida del ZIP.
   * @param bool $includeVendor Si se debe incluir vendor/.
   * @param bool $includeConfig Si se incluyen secretos compilados (--config).
   * @param bool $rotateKey Si se incluye App/Safety/vault.key.php (--rotate-key).
   * @param bool $includeCompiledConfig Si se incluye App/Config/compiled.config.php (sincronización JS).
   * @return int Cantidad de archivos agregados al ZIP.
   * @throws \RuntimeException Si no se puede abrir o crear el archivo ZIP.
   */
  public function createUpdateZip(
    string $zipPath,
    bool $includeVendor = false,
    bool $includeConfig = false,
    bool $rotateKey = false,
    bool $includeCompiledConfig = false
  ): int {
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      throw new \RuntimeException("No se pudo crear el paquete ZIP en {$zipPath}");
    }

    $updateItems = [
      'App',
      'Bootstrap',
      'index.php',
      '.htaccess',
      'providers.json',
      'jsConfig.json',
    ];

    if ($includeVendor) {
      $updateItems[] = 'vendor';
    }

    $excludes = $this->getProductionExcludePaths($includeConfig, $rotateKey, $includeCompiledConfig);
    $excludes[] = 'App/DatabaseComponent/GeoLite2-City';
    $excludeExtensions = ['mmdb', 'sqlite', 'sqlite3', 'sqlite-wal', 'sqlite-shm'];

    $fileCount = 0;
    foreach ($updateItems as $item) {
      if (self::isExcludedStudioPath($item)) {
        continue;
      }
      $fullPath = $this->basePath . '/' . $item;
      if (is_dir($fullPath)) {
        $fileCount += $this->addDirectoryToZip($zip, $fullPath, $item, $excludes, self::PRODUCTION_INCLUDE_OVERRIDES, $excludeExtensions);
      } elseif (is_file($fullPath)) {
        $zip->addFile($fullPath, $item);
        $fileCount++;
      }
    }

    $zip->close();

    return $fileCount;
  }

  /**
   * Solicita confirmación interactiva explícita antes de compilar y desplegar a producción.
   *
   * @param resource|null $inputStream Flujo de entrada opcional (por defecto STDIN).
   * @return bool
   */
  private function confirmDeploy($inputStream = null): bool
  {
    echo "⚠️  ATENCIÓN: Estás a punto de desplegar cambios al servidor de producción.\n";
    echo "   Asegúrate de haber terminado tus cambios, variables y pruebas locales.\n";
    echo "¿Confirmas el despliegue? Escribe [yes / y / Yes / YES] para continuar (cualquier otra entrada cancela): ";

    $openedStream = false;
    $stream = $inputStream;
    if (!is_resource($stream)) {
      if (defined('STDIN')) {
        $stream = STDIN;
      } else {
        $stream = @fopen('php://stdin', 'r');
        $openedStream = is_resource($stream);
      }
    }

    if (!is_resource($stream)) {
      return false;
    }

    $rawAnswer = fgets($stream);
    if ($openedStream) {
      fclose($stream);
    }

    $answer = $rawAnswer === false ? '' : $rawAnswer;
    return self::isDeployConfirmed($answer);
  }

  /**
   * Asegura que exista el archivo deploy.php en el proyecto y retorna su token.
   */
  private function ensureDeployReceiverFile(): string
  {
    $deployFile = $this->basePath . '/deploy.php';
    $config = $this->loadDeployConfig();
    $token = !empty($config['token']) ? $config['token'] : bin2hex(random_bytes(16));

    if (!file_exists($deployFile)) {
      $template = $this->getDeployReceiverTemplate($token);
      file_put_contents($deployFile, $template);
      echo "✅ Archivo 'deploy.php' creado con token generado.\n";
    } else {
      // Si ya existe, leer el token del archivo si es posible
      $content = (string)file_get_contents($deployFile);
      if (preg_match('/\$expectedToken\s*=\s*\'([^\']+)\'/', $content, $m)) {
        if (!empty($m[1]) && $m[1] !== '###DEPLOY_TOKEN###') {
          $token = $m[1];
          echo "🔒 Token de despliegue existente conservado desde 'deploy.php'.\n";
        } else {
          // Token vacío o placeholder, reescribirlo con token seguro
          $template = $this->getDeployReceiverTemplate($token);
          file_put_contents($deployFile, $template);
          echo "🔧 Archivo 'deploy.php' configurado con token seguro.\n";
        }
      } else {
        $template = $this->getDeployReceiverTemplate($token);
        file_put_contents($deployFile, $template);
      }
    }

    return $token;
  }

  /**
   * Asegura que Database/ y Uploads/ existan con sus permisos.
   */
  private function ensurePersistentDirectories(): void
  {
    $dirs = [
      $this->basePath . '/Database',
      $this->basePath . '/Uploads',
      $this->basePath . '/App/Public/Uploads',
    ];

    foreach ($dirs as $dir) {
      if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
      }
    }

    // Asegurar que exista una base de datos SQLite si el directorio está vacío
    $databaseDir = $this->basePath . '/Database';
    $sqliteFiles = glob($databaseDir . '/*.sqlite*') ?: [];
    if (empty($sqliteFiles)) {
      $defaultDb = $databaseDir . '/' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $this->projectName)) . '.sqlite';
      @touch($defaultDb);
      @chmod($defaultDb, 0664);
      echo "🗄️  Base de datos SQLite preparada: Database/" . basename($defaultDb) . "\n";
    }
  }

  /**
   * Asegura que el archivo .env exista y contenga SEED y DEPLOY_TOKEN válidos.
   */
  private function ensureProjectEnv(string $token): void
  {
    $envFile = $this->basePath . '/.env';
    $envExample = $this->basePath . '/.env.example';
    $frameworkEnvExample = __DIR__ . '/../../.env.example';

    if (!file_exists($envFile)) {
      if (file_exists($envExample)) {
        @copy($envExample, $envFile);
      } elseif (file_exists($frameworkEnvExample)) {
        @copy($frameworkEnvExample, $envFile);
      } else {
        file_put_contents($envFile, "APP_ENV=development\n");
      }
    }

    $envContent = (string)file_get_contents($envFile);
    $modified = false;

    // 1. Generar SEED si está vacía
    if (!str_contains($envContent, 'SEED=')) {
      $seed = bin2hex(random_bytes(32));
      $envContent = "SEED=\"{$seed}\"\n" . $envContent;
      $modified = true;
      echo "🔑 Semilla de seguridad (SEED) configurada en .env\n";
    } elseif (preg_match('/^SEED\s*=\s*["\']?["\']?\s*$/m', $envContent)) {
      $seed = bin2hex(random_bytes(32));
      $envContent = preg_replace('/^SEED\s*=\s*["\']?["\']?\s*$/m', 'SEED="' . $seed . '"', $envContent);
      $modified = true;
      echo "🔑 Semilla de seguridad (SEED) configurada en .env\n";
    } else {
      echo "🔒 Semilla de seguridad (SEED) existente conservada.\n";
    }

    // 2. Configurar DEPLOY_TOKEN
    if (!str_contains($envContent, 'DEPLOY_TOKEN')) {
      $envContent .= "\n# ==================== DEPLOYMENT ====================\nDEPLOY_URL=\"\"\nDEPLOY_TOKEN=\"{$token}\"\n";
      $modified = true;
      echo "🚀 Clave de despliegue (DEPLOY_TOKEN) configurada en .env\n";
    } elseif (preg_match('/^DEPLOY_TOKEN\s*=\s*["\']?["\']?\s*$/m', $envContent)) {
      $envContent = preg_replace('/^DEPLOY_TOKEN\s*=\s*["\']?["\']?\s*$/m', 'DEPLOY_TOKEN="' . $token . '"', $envContent);
      $modified = true;
      echo "🚀 Clave de despliegue (DEPLOY_TOKEN) configurada en .env\n";
    } else {
      echo "🔒 Token de despliegue (DEPLOY_TOKEN) existente conservado.\n";
    }

    if ($modified) {
      file_put_contents($envFile, $envContent);
    }
  }

  /**
   * Carga la configuración de despliegue desde .env o deploy.json.
   */
  private function loadDeployConfig(): array
  {
    $config = ['url' => '', 'token' => ''];

    // 1. Probar con .env
    $envFile = $this->basePath . '/.env';
    if (file_exists($envFile)) {
      $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      foreach ($lines as $line) {
        $line = trim($line);
        if (str_starts_with($line, '#')) continue;
        if (str_starts_with($line, 'DEPLOY_URL=')) {
          $config['url'] = trim(substr($line, 11), " \"'");
        }
        if (str_starts_with($line, 'DEPLOY_TOKEN=')) {
          $config['token'] = trim(substr($line, 13), " \"'");
        }
      }
    }

    // 2. Probar con deploy.json
    $jsonFile = $this->basePath . '/deploy.json';
    if (file_exists($jsonFile)) {
      $data = json_decode((string)file_get_contents($jsonFile), true);
      if (!empty($data['url'])) $config['url'] = $data['url'];
      if (!empty($data['token'])) $config['token'] = $data['token'];
    }

    return $config;
  }

  /**
   * Envía el paquete ZIP al servidor mediante cURL con validación TLS estricta por defecto.
   */
  private function sendPackage(string $url, string $token, string $zipPath, bool $allowInsecureSsl = false, bool $rotateKey = false): array
  {
    if (!str_ends_with(strtolower($url), '.php')) {
      $url = rtrim($url, '/') . '/deploy.php';
    }

    if (str_starts_with(strtolower($url), 'http://')) {
      echo "⚠️  ADVERTENCIA: Estás enviando un paquete y DEPLOY_TOKEN por HTTP sin cifrado ({$url}). Usa HTTPS en producción.\n";
    }

    $postData = [
      'token'   => $token,
      'package' => new CURLFile($zipPath, 'application/zip', basename($zipPath))
    ];

    $timestamp = (string)time();
    $zipSha256 = (string)(@hash_file('sha256', $zipPath) ?: '');
    $hmacSignature = $zipSha256 !== ''
      ? hash_hmac('sha256', $timestamp . '|' . $zipSha256, $token)
      : '';

    // Detectar CA bundle de certificados (Composer / Mozilla) para Windows
    $caBundle = null;
    if (class_exists('Composer\CaBundle\CaBundle')) {
      $caBundle = Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
    } elseif (file_exists($this->frameworkPath . '/vendor/composer/ca-bundle/res/cacert.pem')) {
      $caBundle = $this->frameworkPath . '/vendor/composer/ca-bundle/res/cacert.pem';
    } elseif (file_exists($this->basePath . '/vendor/composer/ca-bundle/res/cacert.pem')) {
      $caBundle = $this->basePath . '/vendor/composer/ca-bundle/res/cacert.pem';
    }

    // Validación TLS estricta por defecto (sin degradación silenciosa ante fallos de certificado)
    $verifySsl = !$allowInsecureSsl;
    if ($allowInsecureSsl) {
      echo "⚠️  ADVERTENCIA DE SEGURIDAD: --allow-insecure-ssl activo. Validación de certificado TLS deshabilitada bajo responsabilidad del operador.\n";
    }

    $response = $this->executeCurl($url, $token, $postData, $verifySsl, $caBundle, $timestamp, $zipSha256, $hmacSignature, $rotateKey);

    if (!$response['success'] && $verifySsl && isset($response['error']) && str_contains(strtolower($response['error']), 'certificate')) {
      $response['error'] .= "\n   💡 Sugerencia: Configura 'curl.cainfo' en php.ini o instala composer/ca-bundle. "
        . "No se deshabilita TLS automáticamente para prevenir ataques MITM. "
        . "Solo en entornos locales/controlados puedes usar --allow-insecure-ssl.";
    }

    return $response;
  }

  private function executeCurl(
    string $url,
    string $token,
    array $postData,
    bool $verifySsl = true,
    ?string $caBundle = null,
    string $timestamp = '',
    string $zipSha256 = '',
    string $hmacSignature = '',
    bool $rotateKey = false
  ): array {
    $ch = curl_init();

    $headers = [
      'X-Deploy-Token: ' . $token,
      'Accept: application/json',
    ];
    if ($rotateKey) {
      $headers[] = 'X-Deploy-Rotate-Key: 1';
    }
    if ($timestamp !== '' && $zipSha256 !== '' && $hmacSignature !== '') {
      $headers[] = 'X-Deploy-Timestamp: ' . $timestamp;
      $headers[] = 'X-Deploy-Sha256: ' . $zipSha256;
      $headers[] = 'X-Deploy-Signature: ' . $hmacSignature;
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'EberFrame-Deploy/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);

    if (defined('CURLPROTO_HTTPS') && defined('CURLPROTO_HTTP')) {
      curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
    }

    if ($verifySsl) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
      if ($caBundle && file_exists($caBundle)) {
        curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
      }
    } else {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
      return ['success' => false, 'error' => "Error de conexión cURL: {$curlError}"];
    }

    $json = json_decode((string)$raw, true);
    if ($httpCode === 200 && is_array($json) && !empty($json['success'])) {
      return $json;
    }

    return [
      'success' => false,
      'error' => "Servidor respondió con código HTTP {$httpCode}: " . ($json['error'] ?? substr((string)$raw, 0, 300))
    ];
  }

  /**
   * Agrega un directorio recursivamente al ZIP excluyendo patrones especificados
   * y respetando excepciones puntuales de archivos requeridos en producción.
   */
  private function addDirectoryToZip(
    ZipArchive $zip,
    string $dir,
    string $zipDir,
    array $excludePaths = [],
    array $includeOverrides = self::PRODUCTION_INCLUDE_OVERRIDES,
    array $excludeExtensions = []
  ): int {
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
      $filePath = str_replace('\\', '/', $file->getPathname());
      $relativePath = $zipDir . '/' . ltrim(substr($filePath, strlen($dir)), '/');

      if (self::isExcludedStudioPath($relativePath)) {
        continue;
      }

      if (str_contains($relativePath, '/.git/') || str_starts_with($relativePath, '.git/') || str_ends_with($relativePath, '/.git')) {
        continue;
      }

      if (!empty($excludeExtensions) && $file->isFile()) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext !== '' && in_array($ext, $excludeExtensions, true)) {
          continue;
        }
      }

      // Comprobar si coincide con exclusiones
      $isExcluded = false;
      foreach ($excludePaths as $ex) {
        if (str_starts_with($relativePath, $ex)) {
          $isExcluded = true;
          break;
        }
      }

      if ($isExcluded) {
        $isOverrideIncluded = false;
        foreach ($includeOverrides as $inc) {
          if ($relativePath === $inc) {
            $isOverrideIncluded = true;
            break;
          }
          if ($file->isDir() && str_starts_with($inc, $relativePath . '/') && is_file($this->basePath . '/' . $inc)) {
            $isOverrideIncluded = true;
            break;
          }
        }
        if (!$isOverrideIncluded) {
          continue;
        }
      }

      if ($file->isDir()) {
        $zip->addEmptyDir($relativePath);
      } elseif ($file->isFile()) {
        $zip->addFile($filePath, $relativePath);
        $count++;
      }
    }

    return $count;
  }

  /**
   * Retorna el contenido del archivo deploy.php que se colocará en el servidor.
   */
  public static function getDeployReceiverTemplate(string $token): string
  {
    $template = <<<'PHP'
<?php

/**
 * RECEPTOR DE DESPLIEGUE Y ADMINISTRACIÓN REMOTA — EBER FRAMEWORK
 *
 * Este archivo recibe y descomprime los despliegues de 'frame deploy' y
 * ejecuta operaciones administrativas autorizadas ('frame remote:*') mediante
 * lista blanca cerrada, firma HMAC-SHA256 (DEPLOY_TOKEN + SEED) y anti-replay.
 */

// 1. No exponer banner ni confirmar existencia en métodos distintos a POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(404);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'Not found']);
  exit;
}

// 2. Obtener configuración desde Vault AEAD, .env o token embebido (compatible con frame key:rotate)
$expectedToken = '###DEPLOY_TOKEN###';
$expectedSeed = '';
$configuredDbDatabase = '';
$deployEnabled = true;
$allowedIps = [];

$vaultFile = __DIR__ . '/App/Config/secrets.vault.php';
$keyFile = __DIR__ . '/App/Safety/vault.key.php';
if (is_file($vaultFile)) {
  if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', str_replace('\\', '/', __DIR__));
  }
  $vaultEnvelope = @require $vaultFile;
  $masterKeyHex = getenv('FRAME_MASTER_KEY') ?: '';
  if ($masterKeyHex === '' && is_file($keyFile)) {
    $keyData = @require $keyFile;
    $masterKeyHex = is_array($keyData) ? (string)($keyData['key'] ?? '') : (is_string($keyData) ? $keyData : '');
  }
  $masterKeyHex = strtolower(trim($masterKeyHex));
  if (is_array($vaultEnvelope) && preg_match('/^[a-f0-9]{64}$/', $masterKeyHex)) {
    $keyBin = @hex2bin($masterKeyHex);
    $cipher = (string)($vaultEnvelope['cipher'] ?? '');
    $nonce = base64_decode((string)($vaultEnvelope['nonce'] ?? ''), true);
    $ciphertext = base64_decode((string)($vaultEnvelope['ciphertext'] ?? ''), true);
    $aad = 'eber-frame-vault-v1';
    $plain = false;
    if ($keyBin !== false && $nonce !== false && $ciphertext !== false) {
      if ($cipher === 'xchacha20-poly1305' && function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt')) {
        $plain = @sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, $aad, $nonce, $keyBin);
      } elseif ($cipher === 'aes-256-gcm') {
        $tag = base64_decode((string)($vaultEnvelope['tag'] ?? ''), true);
        if ($tag !== false) {
          $plain = @openssl_decrypt($ciphertext, 'aes-256-gcm', $keyBin, OPENSSL_RAW_DATA, $nonce, $tag, $aad);
        }
      }
    }
    if (is_string($plain) && $plain !== '') {
      $vaultSecrets = json_decode($plain, true);
      if (is_array($vaultSecrets)) {
        if (!empty($vaultSecrets['DEPLOY_TOKEN'])) {
          $expectedToken = (string)$vaultSecrets['DEPLOY_TOKEN'];
        }
        if (!empty($vaultSecrets['SEED'])) {
          $expectedSeed = (string)$vaultSecrets['SEED'];
        }
        if (!empty($vaultSecrets['DB_DATABASE'])) {
          $configuredDbDatabase = (string)$vaultSecrets['DB_DATABASE'];
        }
        if (array_key_exists('DEPLOY_ENABLED', $vaultSecrets)) {
          $deployEnabled = filter_var($vaultSecrets['DEPLOY_ENABLED'], FILTER_VALIDATE_BOOLEAN);
        }
        if (!empty($vaultSecrets['DEPLOY_ALLOWED_IPS'])) {
          $allowedIps = array_values(array_filter(array_map('trim', explode(',', (string)$vaultSecrets['DEPLOY_ALLOWED_IPS']))));
        }
      }
    }
  }
}

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
  $envLines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
  foreach ($envLines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
      continue;
    }
    if (str_starts_with($line, 'DEPLOY_TOKEN=')) {
      $val = trim(substr($line, 13), " \"'");
      if ($val !== '') {
        $expectedToken = $val;
      }
    } elseif (str_starts_with($line, 'SEED=')) {
      $val = trim(substr($line, 5), " \"'");
      if ($val !== '') {
        $expectedSeed = $val;
      }
    } elseif (str_starts_with($line, 'DB_DATABASE=')) {
      $val = trim(substr($line, 12), " \"'");
      if ($val !== '') {
        $configuredDbDatabase = $val;
      }
    } elseif (str_starts_with($line, 'DEPLOY_ENABLED=')) {
      $val = strtolower(trim(substr($line, 15), " \"'"));
      $deployEnabled = !in_array($val, ['false', '0', 'off', 'no'], true);
    } elseif (str_starts_with($line, 'DEPLOY_ALLOWED_IPS=')) {
      $val = trim(substr($line, 19), " \"'");
      if ($val !== '') {
        $allowedIps = array_values(array_filter(array_map('trim', explode(',', $val))));
      }
    }
  }
}

header('Content-Type: application/json; charset=utf-8');

if (!$deployEnabled) {
  http_response_code(403);
  echo json_encode(['error' => 'Despliegue deshabilitado por configuración (DEPLOY_ENABLED=false).']);
  exit;
}

// 3. Validar IP origen si DEPLOY_ALLOWED_IPS está definido
$clientIp = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
if (!empty($allowedIps)) {
  $ipAllowed = false;
  $ipBin = @inet_pton($clientIp);
  foreach ($allowedIps as $rule) {
    if ($rule === $clientIp) {
      $ipAllowed = true;
      break;
    }
    if (str_contains($rule, '/') && $ipBin !== false) {
      [$net, $mask] = explode('/', $rule, 2);
      $netBin = @inet_pton($net);
      if ($netBin !== false && strlen($netBin) === strlen($ipBin) && ctype_digit($mask)) {
        $bits = (int)$mask;
        $maxBits = strlen($ipBin) * 8;
        if ($bits >= 0 && $bits <= $maxBits) {
          $bytes = intdiv($bits, 8);
          $rem = $bits % 8;
          $maskBin = str_repeat("\xff", $bytes);
          if ($rem > 0) {
            $maskBin .= chr((0xff << (8 - $rem)) & 0xff);
          }
          $maskBin = str_pad($maskBin, strlen($ipBin), "\x00");
          if (($ipBin & $maskBin) === ($netBin & $maskBin)) {
            $ipAllowed = true;
            break;
          }
        }
      }
    }
  }
  if (!$ipAllowed) {
    http_response_code(403);
    echo json_encode(['error' => 'IP no autorizada para despliegue.']);
    exit;
  }
}

// 4. Fail-closed si no existe token válido configurado
if ($expectedToken === '' || $expectedToken === ('###' . 'DEPLOY_TOKEN' . '###') || strlen($expectedToken) < 16) {
  http_response_code(403);
  echo json_encode(['error' => 'Receptor de despliegue no configurado correctamente.']);
  exit;
}

// 5. Rate limiting atómico contra fuerza bruta sobre DEPLOY_TOKEN / HMAC
$rlFile = sys_get_temp_dir() . '/frame_deploy_rl_' . sha1(__FILE__) . '.json';
$now = time();
$rlWindow = 900;
$rlMaxAttempts = 5;

$recordFailedAttempt = static function () use ($rlFile, $clientIp, $now, $rlWindow): void {
  $rlFp = @fopen($rlFile, 'c+');
  if ($rlFp && @flock($rlFp, LOCK_EX)) {
    $rawRl = stream_get_contents($rlFp);
    $rlData = json_decode((string)$rawRl, true);
    if (!is_array($rlData)) {
      $rlData = [];
    }
    $attempts = array_values(array_filter(
      (array)($rlData[$clientIp] ?? []),
      fn($ts) => is_int($ts) && ($now - $ts) < $rlWindow
    ));
    $attempts[] = $now;
    $rlData[$clientIp] = $attempts;
    ftruncate($rlFp, 0);
    rewind($rlFp);
    fwrite($rlFp, json_encode($rlData));
    fflush($rlFp);
    flock($rlFp, LOCK_UN);
    fclose($rlFp);
  }
};

$clearRateLimit = static function () use ($rlFile, $clientIp): void {
  $rlFp = @fopen($rlFile, 'c+');
  if ($rlFp && @flock($rlFp, LOCK_EX)) {
    $rawRl = stream_get_contents($rlFp);
    $rlData = json_decode((string)$rawRl, true);
    if (is_array($rlData) && isset($rlData[$clientIp])) {
      unset($rlData[$clientIp]);
      ftruncate($rlFp, 0);
      rewind($rlFp);
      fwrite($rlFp, json_encode($rlData));
      fflush($rlFp);
    }
    flock($rlFp, LOCK_UN);
    fclose($rlFp);
  }
};

$isRateLimited = false;
$rlFp = @fopen($rlFile, 'c+');
if ($rlFp && @flock($rlFp, LOCK_EX)) {
  $rawRl = stream_get_contents($rlFp);
  $rlData = json_decode((string)$rawRl, true);
  if (!is_array($rlData)) {
    $rlData = [];
  }
  $attempts = array_values(array_filter(
    (array)($rlData[$clientIp] ?? []),
    fn($ts) => is_int($ts) && ($now - $ts) < $rlWindow
  ));
  if (count($attempts) >= $rlMaxAttempts) {
    $isRateLimited = true;
  }
  $rlData[$clientIp] = $attempts;
  ftruncate($rlFp, 0);
  rewind($rlFp);
  fwrite($rlFp, json_encode($rlData));
  fflush($rlFp);
  flock($rlFp, LOCK_UN);
  fclose($rlFp);
}

if ($isRateLimited) {
  http_response_code(429);
  echo json_encode(['error' => 'Demasiados intentos de despliegue fallidos. Espera unos minutos.']);
  exit;
}

// 6. Validar token de seguridad (timing-attack safe)
$receivedToken = (string)($_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? $_POST['token'] ?? '');
if ($receivedToken === '' || !hash_equals($expectedToken, $receivedToken)) {
  $recordFailedAttempt();
  http_response_code(403);
  echo json_encode(['error' => 'Acceso no autorizado. Token de despliegue inválido o ausente.']);
  exit;
}

// 6.5 Dispatcher de operaciones remotas administrativas ('frame remote:*')
$remoteAction = trim((string)($_SERVER['HTTP_X_DEPLOY_ACTION'] ?? ''));
if ($remoteAction !== '') {
  if ($expectedSeed === '' || strlen($expectedSeed) < 16) {
    http_response_code(403);
    echo json_encode(['error' => 'Receptor remoto sin SEED configurada en producción (.env o Vault).']);
    exit;
  }

  $hdrTs  = trim((string)($_SERVER['HTTP_X_DEPLOY_TIMESTAMP'] ?? ''));
  $hdrSha = strtolower(trim((string)($_SERVER['HTTP_X_DEPLOY_SHA256'] ?? '')));
  $hdrSig = strtolower(trim((string)($_SERVER['HTTP_X_DEPLOY_SIGNATURE'] ?? '')));

  if ($hdrTs === '' || $hdrSha === '' || $hdrSig === '') {
    $recordFailedAttempt();
    http_response_code(403);
    echo json_encode(['error' => 'Cabeceras criptográficas HMAC obligatorias ausentes.']);
    exit;
  }

  if (!ctype_digit($hdrTs) || abs(time() - (int)$hdrTs) > 300) {
    $recordFailedAttempt();
    http_response_code(403);
    echo json_encode(['error' => 'Firma remota expirada o timestamp inválido.']);
    exit;
  }

  $payload = [];
  if ($remoteAction === 'geoip_chunk') {
    if (!empty($_FILES['package']['tmp_name']) && is_uploaded_file($_FILES['package']['tmp_name'])) {
      $actualPayloadSha = (string)@hash_file('sha256', (string)$_FILES['package']['tmp_name']);
      if ($actualPayloadSha === '') {
        http_response_code(400);
        echo json_encode(['error' => 'No se pudo calcular el hash del paquete GeoIP recibido.']);
        exit;
      }
    } elseif (!empty($_FILES['chunk']['tmp_name']) && is_uploaded_file($_FILES['chunk']['tmp_name'])) {
      $chunkTmp = (string)$_FILES['chunk']['tmp_name'];
      $uploadIdRaw = (string)($_POST['upload_id'] ?? '');
      $chunkIndexRaw = (string)($_POST['chunk_index'] ?? '');
      $chunkTotalRaw = (string)($_POST['chunk_total'] ?? '');
      $actualChunkSha = (string)@hash_file('sha256', $chunkTmp);
      if ($actualChunkSha === '') {
        http_response_code(400);
        echo json_encode(['error' => 'No se pudo calcular el hash del fragmento recibido.']);
        exit;
      }
      $actualPayloadSha = hash('sha256', $uploadIdRaw . '|' . $chunkIndexRaw . '|' . $chunkTotalRaw . '|' . $actualChunkSha);
    } else {
      $rawBody = (string)file_get_contents('php://input');
      if ($rawBody === '') {
        http_response_code(400);
        echo json_encode(['error' => 'No se recibió fragmento GeoIP válido.']);
        exit;
      }
      $actualPayloadSha = hash('sha256', $rawBody);
      $decodedPayload = json_decode($rawBody, true);
      if (!is_array($decodedPayload)) {
        http_response_code(400);
        echo json_encode(['error' => 'Payload JSON de fragmento GeoIP inválido.']);
        exit;
      }
      $payload = $decodedPayload;
    }
  } else {
    $rawBody = (string)file_get_contents('php://input');
    $actualPayloadSha = hash('sha256', $rawBody);
    if ($rawBody !== '') {
      $decodedPayload = json_decode($rawBody, true);
      if (!is_array($decodedPayload)) {
        http_response_code(400);
        echo json_encode(['error' => 'Payload JSON inválido.']);
        exit;
      }
      $payload = $decodedPayload;
    }
  }

  if (!hash_equals($hdrSha, strtolower($actualPayloadSha))) {
    $recordFailedAttempt();
    http_response_code(403);
    echo json_encode(['error' => 'Integridad del payload remoto comprometida (SHA-256 no coincide).']);
    exit;
  }

  $expectedSig = hash_hmac('sha256', $hdrTs . '|' . $remoteAction . '|' . $actualPayloadSha, $expectedToken . '|' . $expectedSeed);
  if (!hash_equals($expectedSig, $hdrSig)) {
    $recordFailedAttempt();
    http_response_code(403);
    echo json_encode(['error' => 'Firma HMAC remota inválida.']);
    exit;
  }

  $clearRateLimit();

  $resolveSqlitePath = static function () use ($configuredDbDatabase): ?string {
    $dbDirReal = realpath(__DIR__ . '/Database');
    if ($dbDirReal === false || !is_dir($dbDirReal)) {
      return null;
    }
    $dbDirNorm = rtrim(str_replace('\\', '/', $dbDirReal), '/');
    if ($configuredDbDatabase !== '') {
      $rel = ltrim(str_replace('\\', '/', $configuredDbDatabase), '/');
      $candidates = [__DIR__ . '/' . $rel, $dbDirNorm . '/' . basename($rel)];
      foreach ($candidates as $cand) {
        $real = realpath($cand);
        if ($real !== false && is_file($real)) {
          $realNorm = str_replace('\\', '/', $real);
          if (str_starts_with($realNorm, $dbDirNorm . '/')) {
            return $realNorm;
          }
        }
      }
    }
    $files = glob($dbDirNorm . '/*.sqlite') ?: [];
    if (empty($files)) {
      $files = glob($dbDirNorm . '/*.sqlite3') ?: [];
    }
    if (empty($files)) {
      $files = glob($dbDirNorm . '/*.db') ?: [];
    }
    foreach ($files as $f) {
      $real = realpath($f);
      if ($real !== false && is_file($real)) {
        $realNorm = str_replace('\\', '/', $real);
        if (str_starts_with($realNorm, $dbDirNorm . '/')) {
          return $realNorm;
        }
      }
    }
    return null;
  };

  $resolveLogFile = static function (string $relPath): array {
    $logsReal = realpath(__DIR__ . '/Logs');
    if ($logsReal === false || !is_dir($logsReal)) {
      return ['status' => 404, 'error' => 'Directorio Logs/ no encontrado.'];
    }
    $logsNorm = rtrim(str_replace('\\', '/', $logsReal), '/');
    $clean = trim($relPath);
    if (
      $clean === '' ||
      str_contains($clean, "\0") ||
      str_contains($clean, '\\') ||
      str_starts_with($clean, '/')
    ) {
      return ['status' => 403, 'error' => 'Ruta de log no permitida.'];
    }
    $hasDrive = preg_match("/^[a-zA-Z]:/", $clean) === 1;
    $hasTraversal = preg_match("#(^|/)\\.\\.?(/|$)#", $clean) === 1;
    if ($hasDrive || $hasTraversal) {
      return ['status' => 403, 'error' => 'Acceso denegado: intento de path traversal detectado.'];
    }
    $target = realpath($logsNorm . '/' . $clean);
    if ($target === false || !is_file($target)) {
      return ['status' => 404, 'error' => 'Archivo de log no encontrado.'];
    }
    $targetNorm = str_replace('\\', '/', $target);
    if (!str_starts_with($targetNorm, $logsNorm . '/')) {
      return ['status' => 403, 'error' => 'Acceso denegado fuera del directorio Logs/.'];
    }
    $base = basename($targetNorm);
    if ($base === '.gitkeep' || $base === '.htaccess') {
      return ['status' => 403, 'error' => 'Archivo de sistema protegido.'];
    }
    return ['status' => 200, 'path' => $targetNorm, 'relative' => substr($targetNorm, strlen($logsNorm) + 1)];
  };

  $removeDirRecursive = static function (string $dirPath) use (&$removeDirRecursive): void {
    if (!is_dir($dirPath)) {
      return;
    }
    foreach (scandir($dirPath) ?: [] as $entry) {
      if ($entry === '.' || $entry === '..') {
        continue;
      }
      $full = $dirPath . '/' . $entry;
      if (is_dir($full) && !is_link($full)) {
        $removeDirRecursive($full);
      } else {
        @unlink($full);
      }
    }
    @rmdir($dirPath);
  };

  $handleRemoteAction = static function () use (
    $remoteAction,
    $payload,
    $resolveSqlitePath,
    $resolveLogFile,
    $removeDirRecursive
  ): void {
    switch ($remoteAction) {
      case 'health':
        $sqlitePath = $resolveSqlitePath();
        $sqliteInfo = ['exists' => false, 'path' => null, 'size_bytes' => 0, 'mtime' => null, 'journal_mode' => null];
        if ($sqlitePath !== null) {
          $sqliteInfo['exists'] = true;
          $sqliteInfo['path'] = 'Database/' . basename($sqlitePath);
          $sqliteInfo['size_bytes'] = (int)@filesize($sqlitePath);
          $sqliteInfo['mtime'] = date('c', (int)@filemtime($sqlitePath));
          if (extension_loaded('pdo_sqlite')) {
            try {
              $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
              $pdo->exec('PRAGMA busy_timeout = 5000');
              $sqliteInfo['journal_mode'] = (string)$pdo->query('PRAGMA journal_mode')->fetchColumn();
              $pdo = null;
            } catch (Throwable $e) {
              $sqliteInfo['journal_mode'] = 'error: ' . $e->getMessage();
            }
          }
        }

        $mmdbPath = __DIR__ . '/App/DatabaseComponent/GeoLite2-City.mmdb';
        $geoipInfo = [
          'exists' => is_file($mmdbPath),
          'path' => 'App/DatabaseComponent/GeoLite2-City.mmdb',
          'size_bytes' => is_file($mmdbPath) ? (int)@filesize($mmdbPath) : 0,
          'mtime' => is_file($mmdbPath) ? date('c', (int)@filemtime($mmdbPath)) : null,
        ];

        $writableDirs = [];
        foreach (['Database', 'Cache/Views', 'Logs', 'Uploads', 'App/DatabaseComponent'] as $relDir) {
          $abs = __DIR__ . '/' . $relDir;
          $writableDirs[$relDir] = [
            'exists' => is_dir($abs),
            'writable' => is_dir($abs) && is_writable($abs),
          ];
        }

        $exts = [];
        foreach (['pdo_sqlite', 'sodium', 'gd', 'imagick', 'zip', 'zlib', 'curl', 'Zend OPcache'] as $extName) {
          $exts[$extName] = extension_loaded($extName);
        }

        echo json_encode([
          'success' => true,
          'php_version' => PHP_VERSION,
          'sapi' => PHP_SAPI,
          'os' => PHP_OS_FAMILY,
          'server_time' => date('c'),
          'extensions' => $exts,
          'limits' => [
            'upload_max_filesize' => (string)ini_get('upload_max_filesize'),
            'post_max_size' => (string)ini_get('post_max_size'),
            'memory_limit' => (string)ini_get('memory_limit'),
            'max_execution_time' => (string)ini_get('max_execution_time'),
          ],
          'disk' => [
            'free_bytes' => @disk_free_space(__DIR__),
            'total_bytes' => @disk_total_space(__DIR__),
          ],
          'writable' => $writableDirs,
          'sqlite' => $sqliteInfo,
          'geoip' => $geoipInfo,
        ]);
        return;

      case 'geoip_chunk':
        $uploadId = '';
        $chunkIdxRaw = '';
        $chunkTotalRaw = '';
        $clientChunkSha = '';
        $chunkBytes = null;
        $chunkTmpFile = null;

        if (!empty($_FILES['package']['tmp_name']) && is_uploaded_file($_FILES['package']['tmp_name'])) {
          $pkgTmp = (string)$_FILES['package']['tmp_name'];
          if (!class_exists('ZipArchive')) {
            @unlink($pkgTmp);
            http_response_code(500);
            echo json_encode(['error' => 'ZipArchive no está disponible en el servidor.']);
            return;
          }
          $z = new ZipArchive();
          if ($z->open($pkgTmp) !== true) {
            @unlink($pkgTmp);
            http_response_code(422);
            echo json_encode(['error' => 'Paquete ZIP de fragmento GeoIP inválido o dañado.']);
            return;
          }
          $metaRaw = $z->getFromName('meta.json');
          $dataRaw = $z->getFromName('chunk.dat');
          $z->close();
          @unlink($pkgTmp);

          $meta = is_string($metaRaw) ? json_decode($metaRaw, true) : null;
          if (!is_array($meta) || !is_string($dataRaw)) {
            http_response_code(400);
            echo json_encode(['error' => 'Estructura interna del ZIP de fragmento GeoIP inválida.']);
            return;
          }
          $uploadId = (string)($meta['upload_id'] ?? '');
          $chunkIdxRaw = (string)($meta['chunk_index'] ?? '');
          $chunkTotalRaw = (string)($meta['chunk_total'] ?? '');
          $clientChunkSha = strtolower(trim((string)($meta['chunk_sha256'] ?? '')));
          $chunkBytes = $dataRaw;
        } elseif (isset($payload['chunk_b64'])) {
          $uploadId = (string)($payload['upload_id'] ?? '');
          $chunkIdxRaw = (string)($payload['chunk_index'] ?? '');
          $chunkTotalRaw = (string)($payload['chunk_total'] ?? '');
          $clientChunkSha = strtolower(trim((string)($payload['chunk_sha256'] ?? '')));
          $decoded = base64_decode((string)$payload['chunk_b64'], true);
          if ($decoded === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Contenido Base64 de fragmento GeoIP inválido.']);
            return;
          }
          $chunkBytes = $decoded;
        } else {
          $uploadId = (string)($_POST['upload_id'] ?? '');
          $chunkIdxRaw = (string)($_POST['chunk_index'] ?? '');
          $chunkTotalRaw = (string)($_POST['chunk_total'] ?? '');
          $clientChunkSha = strtolower(trim((string)($_POST['chunk_sha256'] ?? '')));
          $chunkTmpFile = (string)($_FILES['chunk']['tmp_name'] ?? '');
        }

        $validUploadId = preg_match("/^[a-f0-9]{16,64}$/", $uploadId) === 1;
        if (!$validUploadId || !ctype_digit($chunkIdxRaw) || !ctype_digit($chunkTotalRaw)) {
          http_response_code(400);
          echo json_encode(['error' => 'Parámetros de fragmento GeoIP inválidos.']);
          return;
        }

        $chunkIndex = (int)$chunkIdxRaw;
        $chunkTotal = (int)$chunkTotalRaw;
        if ($chunkTotal < 1 || $chunkTotal > 1024 || $chunkIndex < 0 || $chunkIndex >= $chunkTotal) {
          http_response_code(400);
          echo json_encode(['error' => 'Índices de fragmento GeoIP fuera de rango.']);
          return;
        }

        if ($chunkBytes !== null) {
          $chunkSize = strlen($chunkBytes);
          if ($chunkSize <= 0 || $chunkSize > 16 * 1024 * 1024) {
            http_response_code(413);
            echo json_encode(['error' => 'Tamaño de fragmento GeoIP inválido o excede 16 MB.']);
            return;
          }
          $actualChunkSha = strtolower(hash('sha256', $chunkBytes));
        } else {
          $chunkSize = (int)@filesize((string)$chunkTmpFile);
          if ($chunkSize <= 0 || $chunkSize > 16 * 1024 * 1024) {
            http_response_code(413);
            echo json_encode(['error' => 'Tamaño de fragmento GeoIP inválido o excede 16 MB.']);
            return;
          }
          $actualChunkSha = strtolower((string)@hash_file('sha256', (string)$chunkTmpFile));
        }

        if ($clientChunkSha !== '' && !hash_equals($clientChunkSha, $actualChunkSha)) {
          http_response_code(422);
          echo json_encode(['error' => 'El SHA-256 del fragmento GeoIP no coincide.']);
          return;
        }

        // Limpiar cargas GeoIP temporales abandonadas (> 2 horas)
        $tmpRoot = rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/');
        foreach (glob($tmpRoot . '/frame_geoip_*') ?: [] as $staleDir) {
          if (is_dir($staleDir) && (time() - (int)@filemtime($staleDir)) > 7200) {
            $removeDirRecursive($staleDir);
          }
        }

        $chunkDir = $tmpRoot . '/frame_geoip_' . sha1(__FILE__) . '_' . $uploadId;
        if (!is_dir($chunkDir) && !@mkdir($chunkDir, 0755, true)) {
          http_response_code(500);
          echo json_encode(['error' => 'No se pudo crear directorio temporal para fragmentos GeoIP.']);
          return;
        }

        $partFile = $chunkDir . '/chunk_' . $chunkIndex . '.part';
        if ($chunkBytes !== null) {
          if (@file_put_contents($partFile, $chunkBytes, LOCK_EX) === false) {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo almacenar el fragmento GeoIP en el servidor.']);
            return;
          }
        } elseif (!@move_uploaded_file((string)$chunkTmpFile, $partFile)) {
          http_response_code(500);
          echo json_encode(['error' => 'No se pudo almacenar el fragmento GeoIP en el servidor.']);
          return;
        }

        echo json_encode([
          'success' => true,
          'upload_id' => $uploadId,
          'chunk_index' => $chunkIndex,
          'chunk_total' => $chunkTotal,
          'bytes' => (int)@filesize($partFile),
          'chunk_sha256' => $actualChunkSha,
        ]);
        return;

      case 'geoip_commit':
        $uploadId = (string)($payload['upload_id'] ?? '');
        $chunkTotal = (int)($payload['chunk_total'] ?? 0);
        $expectedGzSha = strtolower(trim((string)($payload['gz_sha256'] ?? '')));
        $expectedMmdbSha = strtolower(trim((string)($payload['mmdb_sha256'] ?? '')));

        $validUploadId = preg_match("/^[a-f0-9]{16,64}$/", $uploadId) === 1;
        $validGzSha = preg_match("/^[a-f0-9]{64}$/", $expectedGzSha) === 1;
        if (!$validUploadId || $chunkTotal < 1 || $chunkTotal > 1024 || !$validGzSha) {
          http_response_code(400);
          echo json_encode(['error' => 'Parámetros de confirmación GeoIP inválidos.']);
          return;
        }

        $tmpRoot = rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/');
        $chunkDir = $tmpRoot . '/frame_geoip_' . sha1(__FILE__) . '_' . $uploadId;
        if (!is_dir($chunkDir)) {
          http_response_code(404);
          echo json_encode(['error' => 'Sesión de subida GeoIP no encontrada o expirada.']);
          return;
        }

        $assembledGz = $chunkDir . '/assembled.mmdb.gz';
        $tmpMmdb = null;
        try {
          $outFp = @fopen($assembledGz, 'wb');
          if (!$outFp) {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo ensamblar archivo comprimido GeoIP.']);
            return;
          }

          $gzHashCtx = hash_init('sha256');
          for ($i = 0; $i < $chunkTotal; $i++) {
            $partFile = $chunkDir . '/chunk_' . $i . '.part';
            if (!is_file($partFile)) {
              fclose($outFp);
              http_response_code(422);
              echo json_encode(['error' => "Falta el fragmento GeoIP #{$i}."]);
              return;
            }
            $inFp = @fopen($partFile, 'rb');
            if (!$inFp) {
              fclose($outFp);
              http_response_code(500);
              echo json_encode(['error' => "No se pudo leer el fragmento GeoIP #{$i}."]);
              return;
            }
            while (!feof($inFp)) {
              $buf = fread($inFp, 65536);
              if ($buf !== false && $buf !== '') {
                fwrite($outFp, $buf);
                hash_update($gzHashCtx, $buf);
              }
            }
            fclose($inFp);
          }
          fclose($outFp);

          $actualGzSha = hash_final($gzHashCtx);
          if (!hash_equals($expectedGzSha, $actualGzSha)) {
            http_response_code(422);
            echo json_encode(['error' => 'Integridad del paquete .gz comprometida (SHA-256 no coincide).']);
            return;
          }

          if (!function_exists('gzopen')) {
            http_response_code(500);
            echo json_encode(['error' => 'La extensión zlib no está habilitada en el servidor.']);
            return;
          }

          $geoDir = __DIR__ . '/App/DatabaseComponent';
          if (!is_dir($geoDir) && !@mkdir($geoDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo crear App/DatabaseComponent/ en el servidor.']);
            return;
          }

          $tmpMmdb = $geoDir . '/GeoLite2-City.mmdb.tmp.' . bin2hex(random_bytes(4));
          $gzFp = @gzopen($assembledGz, 'rb');
          $mmdbFp = @fopen($tmpMmdb, 'wb');
          if (!$gzFp || !$mmdbFp) {
            if ($gzFp) gzclose($gzFp);
            if ($mmdbFp) fclose($mmdbFp);
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo descomprimir el archivo GeoIP.']);
            return;
          }

          $maxMmdbBytes = 250 * 1024 * 1024;
          $written = 0;
          $mmdbHashCtx = hash_init('sha256');
          while (!gzeof($gzFp)) {
            $chunk = gzread($gzFp, 65536);
            if ($chunk === false) {
              break;
            }
            if ($chunk !== '') {
              $written += strlen($chunk);
              if ($written > $maxMmdbBytes) {
                gzclose($gzFp);
                fclose($mmdbFp);
                @unlink($tmpMmdb);
                http_response_code(413);
                echo json_encode(['error' => 'El archivo descomprimido GeoIP excede el límite seguro (250 MB).']);
                return;
              }
              fwrite($mmdbFp, $chunk);
              hash_update($mmdbHashCtx, $chunk);
            }
          }
          gzclose($gzFp);
          fclose($mmdbFp);

          $actualMmdbSha = hash_final($mmdbHashCtx);
          if ($expectedMmdbSha !== '' && !hash_equals($expectedMmdbSha, $actualMmdbSha)) {
            @unlink($tmpMmdb);
            http_response_code(422);
            echo json_encode(['error' => 'El SHA-256 del archivo .mmdb descomprimido no coincide.']);
            return;
          }

          // Verificar firma de metadatos binaria estándar de MaxMind MMDB en los últimos 128 KB
          $checkFp = @fopen($tmpMmdb, 'rb');
          $hasMaxMindMarker = false;
          if ($checkFp) {
            $totalSize = (int)@filesize($tmpMmdb);
            $seekPos = max(0, $totalSize - 131072);
            fseek($checkFp, $seekPos);
            $tailBytes = (string)fread($checkFp, 131072);
            fclose($checkFp);
            $hasMaxMindMarker = str_contains($tailBytes, "\xab\xcd\xefMaxMind.com");
          }
          if (!$hasMaxMindMarker) {
            @unlink($tmpMmdb);
            http_response_code(422);
            echo json_encode(['error' => 'El archivo recibido no tiene una firma binaria MaxMind (.mmdb) válida.']);
            return;
          }

          $finalMmdb = $geoDir . '/GeoLite2-City.mmdb';
          @chmod($tmpMmdb, 0644);
          if (!@rename($tmpMmdb, $finalMmdb)) {
            if (!@copy($tmpMmdb, $finalMmdb)) {
              @unlink($tmpMmdb);
              http_response_code(500);
              echo json_encode(['error' => 'No se pudo reemplazar App/DatabaseComponent/GeoLite2-City.mmdb.']);
              return;
            }
            @unlink($tmpMmdb);
          }

          echo json_encode([
            'success' => true,
            'path' => 'App/DatabaseComponent/GeoLite2-City.mmdb',
            'size_bytes' => (int)@filesize($finalMmdb),
            'sha256' => $actualMmdbSha,
          ]);
        } finally {
          if (is_string($tmpMmdb) && is_file($tmpMmdb)) {
            @unlink($tmpMmdb);
          }
          $removeDirRecursive($chunkDir);
        }
        return;

      case 'db_info':
        $sqlitePath = $resolveSqlitePath();
        if ($sqlitePath === null) {
          http_response_code(404);
          echo json_encode(['error' => 'No se encontró ningún archivo SQLite en Database/.']);
          return;
        }
        if (!extension_loaded('pdo_sqlite')) {
          http_response_code(500);
          echo json_encode(['error' => 'La extensión pdo_sqlite no está disponible en el servidor.']);
          return;
        }

        $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $journalMode = (string)$pdo->query('PRAGMA journal_mode')->fetchColumn();

        $tablesRows = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
        $hasSequence = (bool)$pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'sqlite_sequence'")->fetchColumn();
        $seqMap = [];
        if ($hasSequence) {
          foreach ($pdo->query('SELECT name, seq FROM sqlite_sequence')->fetchAll(PDO::FETCH_ASSOC) as $seqRow) {
            $seqMap[(string)$seqRow['name']] = (int)$seqRow['seq'];
          }
        }

        $tables = [];
        foreach ($tablesRows as $tblName) {
          $tbl = (string)$tblName;
          $safeTbl = '"' . str_replace('"', '""', $tbl) . '"';
          $cnt = (int)$pdo->query("SELECT COUNT(*) FROM {$safeTbl}")->fetchColumn();
          $tables[] = [
            'name' => $tbl,
            'rows' => $cnt,
            'sequence' => $seqMap[$tbl] ?? null,
          ];
        }
        $pdo = null;

        echo json_encode([
          'success' => true,
          'database' => basename($sqlitePath),
          'size_bytes' => (int)@filesize($sqlitePath),
          'journal_mode' => $journalMode,
          'tables' => $tables,
        ]);
        return;

      case 'db_pull':
        $sqlitePath = $resolveSqlitePath();
        if ($sqlitePath === null) {
          http_response_code(404);
          echo json_encode(['error' => 'No se encontró ningún archivo SQLite en Database/.']);
          return;
        }
        if (!extension_loaded('pdo_sqlite') || !function_exists('gzopen')) {
          http_response_code(500);
          echo json_encode(['error' => 'Se requieren pdo_sqlite y zlib en el servidor para exportar la BD.']);
          return;
        }

        $tmpDir = rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/');
        $tmpSnap = $tmpDir . '/frame_pull_' . bin2hex(random_bytes(8)) . '.sqlite';
        $tmpGz = $tmpSnap . '.gz';
        register_shutdown_function(static function () use ($tmpSnap, $tmpGz): void {
          if (is_file($tmpSnap)) @unlink($tmpSnap);
          if (is_file($tmpGz)) @unlink($tmpGz);
        });

        try {
          $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
          $pdo->exec('PRAGMA busy_timeout = 5000');
          $pdo->exec('VACUUM INTO ' . $pdo->quote($tmpSnap));
          $pdo = null;
        } catch (Throwable $e) {
          http_response_code(500);
          echo json_encode(['error' => 'Error generando snapshot consistente SQLite: ' . $e->getMessage()]);
          return;
        }

        $inFp = @fopen($tmpSnap, 'rb');
        $gzFp = @gzopen($tmpGz, 'wb6');
        if (!$inFp || !$gzFp) {
          if ($inFp) fclose($inFp);
          if ($gzFp) gzclose($gzFp);
          http_response_code(500);
          echo json_encode(['error' => 'No se pudo comprimir el snapshot SQLite.']);
          return;
        }
        while (!feof($inFp)) {
          $chunk = fread($inFp, 65536);
          if ($chunk !== false && $chunk !== '') {
            gzwrite($gzFp, $chunk);
          }
        }
        fclose($inFp);
        gzclose($gzFp);
        @unlink($tmpSnap);

        $gzSha = (string)@hash_file('sha256', $tmpGz);
        $gzSize = (int)@filesize($tmpGz);
        $downloadName = pathinfo($sqlitePath, PATHINFO_FILENAME) . '_' . date('Ymd_His') . '.sqlite.gz';

        while (ob_get_level() > 0) {
          @ob_end_clean();
        }
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . $gzSize);
        header('X-Backup-Sha256: ' . $gzSha);
        header('X-Backup-Database: ' . basename($sqlitePath));
        readfile($tmpGz);
        @unlink($tmpGz);
        exit;

      case 'db_truncate':
        $sqlitePath = $resolveSqlitePath();
        if ($sqlitePath === null) {
          http_response_code(404);
          echo json_encode(['error' => 'No se encontró ningún archivo SQLite en Database/.']);
          return;
        }
        if (!extension_loaded('pdo_sqlite')) {
          http_response_code(500);
          echo json_encode(['error' => 'La extensión pdo_sqlite no está disponible en el servidor.']);
          return;
        }

        $reqTables = $payload['tables'] ?? [];
        if (!is_array($reqTables) || empty($reqTables)) {
          http_response_code(400);
          echo json_encode(['error' => 'Debes indicar al menos una tabla para truncar.']);
          return;
        }

        $cleanTables = [];
        foreach ($reqTables as $rawTbl) {
          if (!is_string($rawTbl)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre de tabla inválido.']);
            return;
          }
          $tbl = trim($rawTbl);
          $validTbl = preg_match("/^[a-zA-Z_][a-zA-Z0-9_]*$/", $tbl) === 1;
          if (!$validTbl || stripos($tbl, 'sqlite_') === 0) {
            http_response_code(400);
            echo json_encode(['error' => "Nombre de tabla no permitido o protegido: {$tbl}"]);
            return;
          }
          if (!in_array($tbl, $cleanTables, true)) {
            $cleanTables[] = $tbl;
          }
        }

        $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA busy_timeout = 5000');

        $existingTables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($cleanTables as $tbl) {
          if (!in_array($tbl, $existingTables, true)) {
            $pdo = null;
            http_response_code(400);
            echo json_encode(['error' => "La tabla '{$tbl}' no existe en la base de datos."]);
            return;
          }
        }

        $hasSequence = (bool)$pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'sqlite_sequence'")->fetchColumn();
        $summary = [];

        try {
          $pdo->exec('BEGIN IMMEDIATE');
          $seqStmt = $hasSequence ? $pdo->prepare('DELETE FROM sqlite_sequence WHERE name = ?') : null;
          foreach ($cleanTables as $tbl) {
            $safeTbl = '"' . str_replace('"', '""', $tbl) . '"';
            $beforeRows = (int)$pdo->query("SELECT COUNT(*) FROM {$safeTbl}")->fetchColumn();
            $pdo->exec("DELETE FROM {$safeTbl}");
            if ($seqStmt !== null) {
              $seqStmt->execute([$tbl]);
            }
            $summary[$tbl] = [
              'deleted_rows' => $beforeRows,
              'autoincrement_reset' => $hasSequence,
            ];
          }
          $pdo->exec('COMMIT');
        } catch (Throwable $e) {
          if ($pdo->inTransaction()) {
            $pdo->exec('ROLLBACK');
          }
          $pdo = null;
          http_response_code(500);
          echo json_encode(['error' => 'Error truncando tablas: ' . $e->getMessage()]);
          return;
        }

        $vacuumed = false;
        try {
          $pdo->exec('VACUUM');
          $vacuumed = true;
        } catch (Throwable $e) {
          $vacuumed = false;
        }
        $pdo = null;

        echo json_encode([
          'success' => true,
          'database' => basename($sqlitePath),
          'truncated' => $summary,
          'vacuum' => $vacuumed,
        ]);
        return;

      case 'cache_clear':
        $viewCache = __DIR__ . '/Cache/Views';
        $removed = 0;
        if (is_dir($viewCache)) {
          $cleanCacheDir = static function (string $dir, bool $isRoot) use (&$cleanCacheDir, &$removed): void {
            foreach (scandir($dir) ?: [] as $entry) {
              if ($entry === '.' || $entry === '..' || ($isRoot && $entry === '.gitkeep')) {
                continue;
              }
              $full = $dir . '/' . $entry;
              if (is_dir($full) && !is_link($full)) {
                $cleanCacheDir($full, false);
                @rmdir($full);
              } elseif (is_file($full) || is_link($full)) {
                if (@unlink($full)) {
                  $removed++;
                }
              }
            }
          };
          $cleanCacheDir($viewCache, true);
        }
        $opcacheCleared = function_exists('opcache_reset') ? @opcache_reset() : false;

        echo json_encode([
          'success' => true,
          'removed_files' => $removed,
          'opcache_reset' => (bool)$opcacheCleared,
        ]);
        return;

      case 'logs_list':
        $logsReal = realpath(__DIR__ . '/Logs');
        $files = [];
        if ($logsReal !== false && is_dir($logsReal)) {
          $logsNorm = rtrim(str_replace('\\', '/', $logsReal), '/');
          $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($logsReal, RecursiveDirectoryIterator::SKIP_DOTS)
          );
          foreach ($it as $entry) {
            if (!$entry->isFile()) {
              continue;
            }
            $base = $entry->getFilename();
            if ($base === '.gitkeep' || $base === '.htaccess') {
              continue;
            }
            $fullNorm = str_replace('\\', '/', (string)$entry->getRealPath());
            if (!str_starts_with($fullNorm, $logsNorm . '/')) {
              continue;
            }
            $files[] = [
              'file' => substr($fullNorm, strlen($logsNorm) + 1),
              'size_bytes' => (int)$entry->getSize(),
              'mtime' => date('c', (int)$entry->getMTime()),
            ];
          }
          usort($files, static fn(array $a, array $b): int => strcmp($a['file'], $b['file']));
        }
        echo json_encode(['success' => true, 'files' => $files]);
        return;

      case 'logs_tail':
        $relFile = (string)($payload['file'] ?? '');
        $linesCount = (int)($payload['lines'] ?? 100);
        if ($linesCount < 1) $linesCount = 1;
        if ($linesCount > 5000) $linesCount = 5000;

        $resolved = $resolveLogFile($relFile);
        if ($resolved['status'] !== 200) {
          http_response_code($resolved['status']);
          echo json_encode(['error' => $resolved['error']]);
          return;
        }

        $logPath = $resolved['path'];
        $ring = [];
        $totalLines = 0;
        $fp = @fopen($logPath, 'rb');
        if ($fp) {
          while (($line = fgets($fp)) !== false) {
            $totalLines++;
            $ring[] = rtrim($line, "\r\n");
            if (count($ring) > $linesCount) {
              array_shift($ring);
            }
          }
          fclose($fp);
        }

        echo json_encode([
          'success' => true,
          'file' => $resolved['relative'],
          'size_bytes' => (int)@filesize($logPath),
          'lines_requested' => $linesCount,
          'total_lines' => $totalLines,
          'lines' => $ring,
        ]);
        return;

      case 'logs_clear':
        $targetFiles = $payload['files'] ?? [];
        $pathsToClear = [];
        if (is_array($targetFiles) && !empty($targetFiles)) {
          foreach ($targetFiles as $tf) {
            if (!is_string($tf)) {
              http_response_code(400);
              echo json_encode(['error' => 'Nombre de archivo de log inválido.']);
              return;
            }
            $resolved = $resolveLogFile($tf);
            if ($resolved['status'] !== 200) {
              http_response_code($resolved['status']);
              echo json_encode(['error' => $resolved['error']]);
              return;
            }
            $pathsToClear[$resolved['relative']] = $resolved['path'];
          }
        } else {
          $logsReal = realpath(__DIR__ . '/Logs');
          if ($logsReal !== false && is_dir($logsReal)) {
            $logsNorm = rtrim(str_replace('\\', '/', $logsReal), '/');
            $it = new RecursiveIteratorIterator(
              new RecursiveDirectoryIterator($logsReal, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($it as $entry) {
              if (!$entry->isFile()) {
                continue;
              }
              $base = $entry->getFilename();
              if ($base === '.gitkeep' || $base === '.htaccess') {
                continue;
              }
              $fullNorm = str_replace('\\', '/', (string)$entry->getRealPath());
              if (str_starts_with($fullNorm, $logsNorm . '/')) {
                $rel = substr($fullNorm, strlen($logsNorm) + 1);
                $pathsToClear[$rel] = $fullNorm;
              }
            }
          }
        }

        $cleared = [];
        foreach ($pathsToClear as $rel => $abs) {
          $prevBytes = (int)@filesize($abs);
          $fp = @fopen($abs, 'c+b');
          if ($fp && @flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            $cleared[] = ['file' => $rel, 'freed_bytes' => $prevBytes];
          } elseif ($fp) {
            fclose($fp);
          }
        }

        echo json_encode([
          'success' => true,
          'cleared_count' => count($cleared),
          'cleared' => $cleared,
        ]);
        return;

      default:
        http_response_code(400);
        echo json_encode(['error' => "Acción remota no reconocida: {$remoteAction}"]);
        return;
    }
  };

  $handleRemoteAction();
  exit;
}

// 7. Validar archivo ZIP recibido
if (empty($_FILES['package']['tmp_name']) || !is_uploaded_file($_FILES['package']['tmp_name'])) {
  http_response_code(400);
  echo json_encode(['error' => 'No se recibió ningún paquete ZIP para desplegar.']);
  exit;
}

$zipPath = $_FILES['package']['tmp_name'];

// 8. Validar firma HMAC + anti-replay si el cliente envió cabeceras firmadas
$hdrTs  = (string)($_SERVER['HTTP_X_DEPLOY_TIMESTAMP'] ?? '');
$hdrSha = (string)($_SERVER['HTTP_X_DEPLOY_SHA256'] ?? '');
$hdrSig = (string)($_SERVER['HTTP_X_DEPLOY_SIGNATURE'] ?? '');
if ($hdrTs !== '' || $hdrSha !== '' || $hdrSig !== '') {
  if (!ctype_digit($hdrTs) || abs(time() - (int)$hdrTs) > 300) {
    http_response_code(403);
    echo json_encode(['error' => 'Firma de despliegue expirada o timestamp inválido.']);
    exit;
  }
  $actualSha = (string)@hash_file('sha256', $zipPath);
  if ($actualSha === '' || !hash_equals(strtolower($hdrSha), strtolower($actualSha))) {
    http_response_code(403);
    echo json_encode(['error' => 'Integridad del paquete ZIP comprometida (SHA-256 no coincide).']);
    exit;
  }
  $expectedSig = hash_hmac('sha256', $hdrTs . '|' . $actualSha, $expectedToken);
  if (!hash_equals($expectedSig, $hdrSig)) {
    http_response_code(403);
    echo json_encode(['error' => 'Firma HMAC de despliegue inválida.']);
    exit;
  }
}

$startTime = microtime(true);
$zip = new ZipArchive();

if ($zip->open($zipPath) !== true) {
  http_response_code(422);
  echo json_encode(['error' => 'El archivo recibido no es un ZIP válido o está dañado.']);
  exit;
}

// 9. PROTECCIÓN CRÍTICA ANTI-ZIPSLIP, ANTI-ZIPBOMB, ANTI-SYMLINKS Y DATOS PROTEGIDOS
$maxEntries = 15000;
$maxTotalUncompressed = 256 * 1024 * 1024; // 256 MB
$maxSingleFileBytes = 32 * 1024 * 1024;    // 32 MB

if ($zip->numFiles > $maxEntries) {
  $zip->close();
  @unlink($zipPath);
  http_response_code(413);
  echo json_encode(['error' => 'Paquete ZIP excede el máximo de entradas permitidas.']);
  exit;
}

$allowedFiles = [];
$totalUncompressed = 0;

$allowRotateKey = (($_SERVER['HTTP_X_DEPLOY_ROTATE_KEY'] ?? '') === '1')
  && $hdrTs !== '' && $hdrSha !== '' && $hdrSig !== '';

for ($i = 0; $i < $zip->numFiles; $i++) {
  $stat = $zip->statIndex($i);
  $name = (string)($stat['name'] ?? '');
  $size = (int)($stat['size'] ?? 0);

  // Rechazar por completo rutas maliciosas (traversal, backslashes, unidades Windows, rutas absolutas o byte nulo)
  if (
    $name === '' ||
    str_contains($name, "\0") ||
    str_contains($name, '\\') ||
    str_starts_with($name, '/') ||
    preg_match('/^[a-zA-Z]:/', $name) ||
    preg_match('#(^|/)\.\.?(/|$)#', $name)
  ) {
    $zip->close();
    @unlink($zipPath);
    http_response_code(422);
    echo json_encode(['error' => 'Paquete ZIP rechazado: entrada con path traversal o ruta ilegal detectada.']);
    exit;
  }

  // Bloquear enlaces simbólicos Unix dentro del ZIP
  $opsys = 0;
  $attr = 0;
  if ($zip->getExternalAttributesIndex($i, $opsys, $attr)) {
    if ($opsys === ZipArchive::OPSYS_UNIX && ((($attr >> 16) & 0170000) === 0120000)) {
      $zip->close();
      @unlink($zipPath);
      http_response_code(422);
      echo json_encode(['error' => 'Paquete ZIP rechazado: los enlaces simbólicos no están permitidos.']);
      exit;
    }
  }

  if ($size < 0 || $size > $maxSingleFileBytes) {
    $sizeMb = round($size / (1024 * 1024), 2);
    $zip->close();
    @unlink($zipPath);
    http_response_code(413);
    echo json_encode(['error' => "Paquete ZIP rechazado: archivo individual excede tamaño máximo permitido (32 MB): {$name} ({$sizeMb} MB)."]);
    exit;
  }

  $totalUncompressed += $size;
  if ($totalUncompressed > $maxTotalUncompressed) {
    $zip->close();
    @unlink($zipPath);
    http_response_code(413);
    echo json_encode(['error' => 'Paquete ZIP rechazado: tamaño descomprimido excede el límite seguro.']);
    exit;
  }

  // NUNCA sobrescribir datos de usuario, secretos, logs, Studio, ScriptComposer, Docs ni el propio deploy.php
  $isVaultKeyEntry = ($name === 'App/Safety/vault.key.php');
  if ($isVaultKeyEntry && $allowRotateKey) {
    $allowedFiles[] = $name;
    continue;
  }
  if (preg_match('#^(Database/|Uploads/|App/Public/Uploads/|App/DatabaseComponent/.*\.mmdb$|App/Safety/|App/Config/\.studio\..*\.local\.json$|Logs/|Cache/|Docs/|tests/|Base/Studio/|Base/ScriptComposer/|vendor/eber/framework/(Docs|tests|Base/Studio|Base/ScriptComposer)/|\.git/|\.env(\.|$)|deploy\.php$|Base/Views/themeStudio\.php$|Base/Control/ThemeStudioControl\.php$|vendor/eber/framework/Base/Views/themeStudio\.php$|vendor/eber/framework/Base/Control/ThemeStudioControl\.php$)#i', $name)) {
    continue;
  }

  $allowedFiles[] = $name;
}

if (!empty($allowedFiles)) {
  $zip->extractTo(__DIR__, $allowedFiles);
}
$zip->close();
@unlink($zipPath);

// Invalidar OPcache de archivos PHP actualizados (compiled.config.php, configuraciones, controladores)
if (function_exists('opcache_invalidate')) {
  foreach ($allowedFiles as $extractedRel) {
    if (str_ends_with(strtolower($extractedRel), '.php')) {
      @opcache_invalidate(__DIR__ . '/' . $extractedRel, true);
    }
  }
}

// Limpiar directorios y archivos residuales de Frame Studio / Docs / ScriptComposer en producción
$removeRecursive = static function (string $targetPath) use (&$removeRecursive): void {
  if (is_file($targetPath) || is_link($targetPath)) {
    @unlink($targetPath);
    return;
  }
  if (!is_dir($targetPath)) {
    return;
  }
  foreach (scandir($targetPath) ?: [] as $item) {
    if ($item === '.' || $item === '..') {
      continue;
    }
    $removeRecursive($targetPath . '/' . $item);
  }
  @rmdir($targetPath);
};

foreach ([
  'Base/Studio',
  'Base/ScriptComposer',
  'Base/Views/themeStudio.php',
  'Base/Control/ThemeStudioControl.php',
  'vendor/eber/framework/Base/Studio',
  'vendor/eber/framework/Base/ScriptComposer',
  'vendor/eber/framework/Base/Views/themeStudio.php',
  'vendor/eber/framework/Base/Control/ThemeStudioControl.php',
] as $studioRelPath) {
  $removeRecursive(__DIR__ . '/' . $studioRelPath);
}

// Limpiar contador de intentos fallidos tras despliegue exitoso
$clearRateLimit();

// 10. Limpiar caché de vistas si existe
$viewCache = __DIR__ . '/Cache/Views';
if (is_dir($viewCache)) {
  $cachedFiles = glob($viewCache . '/*') ?: [];
  foreach ($cachedFiles as $cf) {
    if (is_file($cf)) @unlink($cf);
  }
}

// 11. Respuesta de éxito
$duration = round(microtime(true) - $startTime, 2);
echo json_encode([
  'success'         => true,
  'message'         => "Despliegue completado con éxito en {$duration}s",
  'extracted_files' => count($allowedFiles),
  'duration'        => "{$duration}s"
]);
PHP;
    return str_replace('###DEPLOY_TOKEN###', str_replace("'", "\\'", $token), $template);
  }
}

// Iniciar ejecución si se llama directamente desde CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
  $deployer = new DeployPackage();
  $deployer->run($_SERVER['argv'] ?? []);
}
