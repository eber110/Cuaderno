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

  public function __construct()
  {
    $this->basePath = str_replace('\\', '/', getcwd());
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
  private function getProductionExcludePaths(bool $includeConfig, bool $includeVaultKey): array
  {
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

    if (!$includeConfig) {
      $excludes[] = 'App/Config/compiled.config.php';
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
    $minScript = file_exists($this->basePath . '/vendor/eber/framework/Base/ScriptComposer/MinJsCss.php')
      ? $this->basePath . '/vendor/eber/framework/Base/ScriptComposer/MinJsCss.php'
      : $this->frameworkPath . '/Base/ScriptComposer/MinJsCss.php';
    if (file_exists($minScript)) {
      @passthru(PHP_BINARY . ' ' . escapeshellarg($minScript));
    }

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
    @passthru('composer min-script');

    // 3. Recompilar configuración y Vault si se solicitó --config o --rotate-key
    if ($includeConfig && class_exists(\Base\Studio\Services\ConfigCompilerService::class)) {
      echo "🔐 Recompilando configuración de producción y Vault AEAD...\n";
      $compileRes = \Base\Studio\Services\ConfigCompilerService::compile($this->basePath, null, $rotateKey);
      if (empty($compileRes['success'])) {
        echo "❌ Error compilando configuración: " . ($compileRes['message'] ?? 'desconocido') . "\n";
        exit(1);
      }
      echo "   ✓ Vault actualizado ({$compileRes['cipher']})\n";
    }

    // 4. Crear ZIP temporal de actualización
    $tempZip = sys_get_temp_dir() . '/frame_update_' . uniqid() . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      echo "❌ Error al crear paquete temporal de despliegue.\n";
      exit(1);
    }

    // Elementos a incluir en la actualización
    $updateItems = [
      'App',
      'Bootstrap',
      'index.php',
      '.htaccess',
      'providers.json',
    ];

    if ($includeVendor) {
      $updateItems[] = 'vendor';
    }

    $excludes = $this->getProductionExcludePaths($includeConfig, $rotateKey);

    $fileCount = 0;
    foreach ($updateItems as $item) {
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

    $zipKb = round(filesize($tempZip) / 1024, 2);
    echo "\n📦 Paquete ligero generado: {$zipKb} KB ({$fileCount} archivos).\n";
    echo "🛡️  PROTECCIÓN ACTIVA: Database/, Uploads/, .env, Base/Studio/, Docs/ y librerías inactivas excluidos.\n";
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
   * Agrega un directorio recursivamente al ZIP excluyendo patrones especificados.
   */
  private function addDirectoryToZip(ZipArchive $zip, string $dir, string $zipDir, array $excludePaths = []): int
  {
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

      // Comprobar si coincide con exclusiones
      $isExcluded = false;
      foreach ($excludePaths as $ex) {
        if (str_starts_with($relativePath, $ex)) {
          $isExcluded = true;
          break;
        }
      }

      if ($isExcluded) {
        continue;
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
    return <<<'PHP'
<?php

/**
 * RECEPTOR DE DESPLIEGUE — EBER FRAMEWORK
 *
 * Este archivo recibe y descomprime los despliegues de 'frame deploy'.
 * Solo acepta peticiones POST autenticadas con DEPLOY_TOKEN y valida
 * integridad, anti-ZipSlip, anti-symlinks y lista de IPs permitidas.
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
if ($expectedToken === '' || $expectedToken === '###DEPLOY_TOKEN###' || strlen($expectedToken) < 16) {
  http_response_code(403);
  echo json_encode(['error' => 'Receptor de despliegue no configurado correctamente.']);
  exit;
}

// 5. Rate limiting atómico contra fuerza bruta sobre DEPLOY_TOKEN
$rlFile = sys_get_temp_dir() . '/frame_deploy_rl_' . sha1(__FILE__) . '.json';
$now = time();
$rlWindow = 900;
$rlMaxAttempts = 5;

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

  http_response_code(403);
  echo json_encode(['error' => 'Acceso no autorizado. Token de despliegue inválido o ausente.']);
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
    $zip->close();
    @unlink($zipPath);
    http_response_code(413);
    echo json_encode(['error' => 'Paquete ZIP rechazado: archivo individual excede tamaño máximo permitido.']);
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
  if (preg_match('#^(Database/|Uploads/|App/Public/Uploads/|App/Safety/|App/Config/\.studio\..*\.local\.json$|Logs/|Cache/|Docs/|tests/|Base/Studio/|Base/ScriptComposer/|vendor/eber/framework/(Docs|tests|Base/Studio|Base/ScriptComposer)/|\.git/|\.env(\.|$)|deploy\.php$|Base/Views/themeStudio\.php$|Base/Control/ThemeStudioControl\.php$|vendor/eber/framework/Base/Views/themeStudio\.php$|vendor/eber/framework/Base/Control/ThemeStudioControl\.php$)#i', $name)) {
    continue;
  }

  $allowedFiles[] = $name;
}

if (!empty($allowedFiles)) {
  $zip->extractTo(__DIR__, $allowedFiles);
}
$zip->close();
@unlink($zipPath);

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
    return str_replace('###DEPLOY_TOKEN###', $token, $template);
  }
}

// Iniciar ejecución si se llama directamente desde CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
  $deployer = new DeployPackage();
  $deployer->run($_SERVER['argv'] ?? []);
}
