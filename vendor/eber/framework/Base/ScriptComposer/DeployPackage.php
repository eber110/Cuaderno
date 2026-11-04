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
  private string $basePath;
  private string $projectName;
  private string $frameworkPath;

  public function __construct()
  {
    $this->basePath = str_replace('\\', '/', getcwd());
    $this->projectName = basename($this->basePath);
    $this->frameworkPath = str_replace('\\', '/', dirname(__DIR__, 2));

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
      $this->runDeploy($includeVendor);
    } elseif ($action === 'package' || $action === 'init') {
      $this->runPackage();
    } else {
      echo "Comando no reconocido. Uso: frame package | frame deploy [--all]\n";
      exit(1);
    }
  }

  /**
   * Genera el ZIP completo inicial para subir una única vez al hosting (cPanel).
   * Incluye App, Bootstrap, vendor, Database (SQLite), Uploads, deploy.php y .env.production.
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
    if (file_exists($this->basePath . '/App/Public/Min')) {
      @passthru('composer min-script');
    }

    // 2. Asegurar existencia de deploy.php con token secreto
    $deployToken = $this->ensureDeployReceiverFile();

    // 3. Asegurar estructura de carpetas persistentes (Database con SQLite, Uploads)
    $this->ensurePersistentDirectories();

    // 4. Asegurar que .env tenga SEED y DEPLOY_TOKEN generados
    $this->ensureProjectEnv($deployToken);

    // 5. Crear carpeta de salida 'dist/'
    $distDir = $this->basePath . '/dist';
    if (!is_dir($distDir)) {
      @mkdir($distDir, 0755, true);
    }

    $zipFileName = "{$this->projectName}-primer-despliegue.zip";
    $zipFilePath = "{$distDir}/{$zipFileName}";

    if (file_exists($zipFilePath)) {
      @unlink($zipFilePath);
    }

    echo "\n⏳ Empaquetando proyecto completo en 'dist/{$zipFileName}'...\n";

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
      '.env',
      'index.php',
      '.htaccess',
      'providers.json',
      'jsConfig.json',
      'deploy.php'
    ];

    // Opcionales si existen en la raíz
    if (file_exists($this->basePath . '/config.php')) {
      $includeItems[] = 'config.php';
    }
    if (file_exists($this->basePath . '/composer.json')) {
      $includeItems[] = 'composer.json';
    }

    $fileCount = 0;
    foreach ($includeItems as $item) {
      $fullPath = $this->basePath . '/' . $item;
      if (is_dir($fullPath)) {
        $fileCount += $this->addDirectoryToZip($zip, $fullPath, $item);
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
    echo "📄 Configuración: Archivo .env incluido en la raíz\n";
    echo "======================================================\n\n";

    echo "👉 PASOS PARA EL SERVIDOR (Una única vez):\n";
    echo "   1. Sube 'dist/{$zipFileName}' a la carpeta de tu proyecto en cPanel.\n";
    echo "   2. Descomprímelo en el servidor (incluye tu .env y Database/ con SQLite).\n";
    echo "   3. En el servidor, abre '.env' y cambia APP_ENV=\"development\" a \"production\".\n";
    echo "   4. Agrega en tu archivo local '.env' la URL de tu sitio para futuros deploys:\n";
    echo "      DEPLOY_URL=https://tudominio.com/deploy.php\n\n";
    echo "✨ A partir de ahora, solo ejecutarás 'frame deploy' para actualizar código.\n";
  }

  /**
   * Despliega actualizaciones continuas de código hacia el servidor sin tocar Database/ ni Uploads/.
   */
  public function runDeploy(bool $includeVendor = false): void
  {
    echo "\n======================================================\n";
    echo "🚀 FRAME — DESPLIEGUE RÁPIDO DE CÓDIGO\n";
    echo "======================================================\n\n";

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
    $deployToken = $config['token'];

    echo "🎯 Servidor destino: {$deployUrl}\n";
    echo "📦 Modo:            " . ($includeVendor ? "Completo (Código + vendor/)" : "Rápido (Solo código App/)") . "\n\n";

    // 2. Compilar assets locales
    echo "🎨 Compilando assets JIT (min-script)...\n";
    @passthru('composer min-script');

    // 3. Crear ZIP temporal de actualización
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
      'jsConfig.json'
    ];

    if ($includeVendor) {
      $updateItems[] = 'vendor';
    }

    $fileCount = 0;
    foreach ($updateItems as $item) {
      $fullPath = $this->basePath . '/' . $item;
      if (is_dir($fullPath)) {
        // Excluir uploads que puedan estar dentro de App/Public/Uploads
        $fileCount += $this->addDirectoryToZip($zip, $fullPath, $item, ['App/Public/Uploads']);
      } elseif (is_file($fullPath)) {
        $zip->addFile($fullPath, $item);
        $fileCount++;
      }
    }

    $zip->close();

    $zipKb = round(filesize($tempZip) / 1024, 2);
    echo "\n📦 Paquete ligero generado: {$zipKb} KB ({$fileCount} archivos).\n";
    echo "🛡️  PROTECCIÓN ACTIVA: Database/, Uploads/ y .env NO se tocan en el servidor.\n";
    echo "\n⏳ Transfiriendo y actualizando servidor...\n";

    // 4. Enviar vía cURL por HTTP POST
    $response = $this->sendPackage($deployUrl, $deployToken, $tempZip);
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
        } else {
          // Token vacío o placeholder, reescribirlo con token seguro
          $template = $this->getDeployReceiverTemplate($token);
          file_put_contents($deployFile, $template);
          echo "🔧 Archivo 'deploy.php' actualizado con nuevo token seguro.\n";
        }
      } else {
        $template = $this->getDeployReceiverTemplate($token);
        file_put_contents($deployFile, $template);
      }
      echo "ℹ️  Archivo 'deploy.php' configurado con token.\n";
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
   * Envía el paquete ZIP al servidor mediante cURL.
   */
  private function sendPackage(string $url, string $token, string $zipPath): array
  {
    $ch = curl_init();

    $postData = [
      'package' => new CURLFile($zipPath, 'application/zip', basename($zipPath))
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'X-Deploy-Token: ' . $token,
      'User-Agent: FrameDeployer/1.0'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);

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
 * Solo acepta peticiones POST con el token de seguridad autorizado.
 */

// 1. Obtener token esperado (desde .env o token embebido)
$expectedToken = '###DEPLOY_TOKEN###';

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
  $envLines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
  foreach ($envLines as $line) {
    $line = trim($line);
    if (str_starts_with($line, 'DEPLOY_TOKEN=')) {
      $val = trim(substr($line, 13), " \"'");
      if (!empty($val)) {
        $expectedToken = $val;
      }
      break;
    }
  }
}

// 2. Si la petición es GET, mostrar estado protegido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'service' => 'Frame Deploy Receiver',
    'status'  => 'active',
    'message' => 'Endpoint protegido listo para recibir despliegues POST.'
  ]);
  exit;
}

// 3. Validar token de seguridad (timing attack safe)
$receivedToken = $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? $_POST['token'] ?? '';
if (empty($receivedToken) || !hash_equals($expectedToken, $receivedToken)) {
  http_response_code(403);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'Acceso no autorizado. Token de despliegue inválido o ausente.']);
  exit;
}

// 4. Validar archivo ZIP recibido
if (empty($_FILES['package']['tmp_name']) || !is_uploaded_file($_FILES['package']['tmp_name'])) {
  http_response_code(400);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'No se recibió ningún paquete ZIP para desplegar.']);
  exit;
}

$startTime = microtime(true);
$zipPath = $_FILES['package']['tmp_name'];
$zip = new ZipArchive();

if ($zip->open($zipPath) !== true) {
  http_response_code(422);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'El archivo recibido no es un ZIP válido o está dañado.']);
  exit;
}

// 5. PROTECCIÓN CRÍTICA DE DATOS:
// Aunque el cliente excluye estas carpetas, el servidor valida estrictamente que ninguna
// entrada pueda sobrescribir Database/, Uploads/ ni .env en producción.
$allowedFiles = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
  $stat = $zip->statIndex($i);
  $name = $stat['name'];

  // Bloquear path traversal
  if (str_contains($name, '../') || str_contains($name, '..\\')) {
    continue;
  }

  // NUNCA sobrescribir datos de usuario ni configuración
  if (preg_match('#^(Database/|Uploads/|\.env(\.|$))#i', $name)) {
    continue;
  }

  $allowedFiles[] = $name;
}

if (!empty($allowedFiles)) {
  $zip->extractTo(__DIR__, $allowedFiles);
}
$zip->close();
@unlink($zipPath);

// 6. Limpiar caché de vistas si existe
$viewCache = __DIR__ . '/Cache/Views';
if (is_dir($viewCache)) {
  $cachedFiles = glob($viewCache . '/*') ?: [];
  foreach ($cachedFiles as $cf) {
    if (is_file($cf)) @unlink($cf);
  }
}

// 7. Respuesta de éxito
$duration = round(microtime(true) - $startTime, 2);
header('Content-Type: application/json; charset=utf-8');
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

// Iniciar ejecución
$deployer = new DeployPackage();
$deployer->run($argv);
