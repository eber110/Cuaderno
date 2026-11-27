<?php

/**
 * Gestor de Operaciones Remotas en Producción para Eber Framework (frame remote:*).
 *
 * Permite ejecutar diagnósticos, sincronización de GeoIP por fragmentos, respaldos
 * consistentes SQLite, truncado seguro con backup previo, limpieza de caché e
 * inspección/vaciado de logs sobre deploy.php mediante firma HMAC-SHA256.
 */

if (php_sapi_name() !== 'cli') {
  die("Este script solo puede ejecutarse desde la interfaz de comandos (CLI).\n");
}

class RemoteCli
{
  /**
   * Respuestas explícitas aceptadas para confirmar operaciones remotas.
   *
   * @var array<int, string>
   */
  public const CONFIRMATION_ANSWERS = ['yes', 'y', 'Yes', 'YES'];

  /**
   * Tamaño de fragmento por defecto para subida GeoIP ZIP-empaquetada (2 MB).
   */
  public const GEOIP_CHUNK_BYTES = 2097152;

  private string $basePath;
  private string $projectName;
  private string $frameworkPath;

  /**
   * Valida si la respuesta ingresada confirma explícitamente la operación.
   *
   * @param string $input Texto ingresado por consola.
   * @return bool
   */
  public static function isActionConfirmed(string $input): bool
  {
    return in_array(trim($input), self::CONFIRMATION_ANSWERS, true);
  }

  /**
   * Limpia espacios y comillas simples o dobles envolventes en rutas ingresadas por CLI.
   *
   * @param string $raw Ruta cruda ingresada por el usuario.
   * @return string
   */
  public static function sanitizeInputPath(string $raw): string
  {
    return trim($raw, " \t\n\r\0\x0B\"'");
  }

  /**
   * Construye las cabeceras HMAC-SHA256 firmadas para acciones remotas en deploy.php.
   *
   * @param string   $action        Nombre de la acción en lista blanca.
   * @param string   $payloadSha256 Hash SHA-256 del cuerpo/parámetros.
   * @param string   $token         DEPLOY_TOKEN del proyecto.
   * @param string   $seed          SEED criptográfica del proyecto.
   * @param int|null $timestamp     Marca de tiempo UNIX opcional.
   * @return array<string, string>
   */
  public static function buildSignatureHeaders(
    string $action,
    string $payloadSha256,
    string $token,
    string $seed,
    ?int $timestamp = null
  ): array {
    $ts = (string)($timestamp ?? time());
    $shaLower = strtolower($payloadSha256);
    $signature = hash_hmac('sha256', $ts . '|' . $action . '|' . $shaLower, $token . '|' . $seed);

    return [
      'X-Deploy-Token'     => $token,
      'X-Deploy-Action'    => $action,
      'X-Deploy-Timestamp' => $ts,
      'X-Deploy-Sha256'    => $shaLower,
      'X-Deploy-Signature' => $signature,
    ];
  }

  public function __construct(?string $basePath = null)
  {
    $this->basePath = rtrim(str_replace('\\', '/', $basePath ?? (string)getcwd()), '/');
    $this->projectName = basename($this->basePath);
    $this->frameworkPath = str_replace('\\', '/', dirname(__DIR__, 2));

    $autoload = file_exists($this->basePath . '/vendor/autoload.php')
      ? $this->basePath . '/vendor/autoload.php'
      : $this->frameworkPath . '/vendor/autoload.php';
    if (file_exists($autoload)) {
      require_once $autoload;
    }

    $composerJson = $this->basePath . '/composer.json';
    if (is_file($composerJson)) {
      $data = json_decode((string)@file_get_contents($composerJson), true);
      if (is_array($data) && !empty($data['name'])) {
        $parts = explode('/', (string)$data['name']);
        $this->projectName = (string)end($parts);
      }
    }
  }

  /**
   * Punto de entrada principal de los subcomandos remote:*.
   *
   * @param array<int, string> $args Argumentos CLI ($argv).
   * @return void
   */
  public function run(array $args): void
  {
    require_once __DIR__ . '/FrameworkGuard.php';
    \Base\ScriptComposer\FrameworkGuard::abortIfFrameworkRepo("'frame remote:*'", $this->basePath);

    $rawAction = strtolower(trim((string)($args[1] ?? '')));
    if (str_starts_with($rawAction, 'remote:')) {
      $rawAction = substr($rawAction, 7);
    }

    if ($rawAction === '' || in_array($rawAction, ['help', '--help', '-h'], true)) {
      $this->showUsage();
      return;
    }

    $restArgs = array_slice($args, 2);
    $parsed = $this->parseOptions($restArgs);
    $opts = $parsed['options'];
    $pos = $parsed['positional'];

    $allowInsecureHttp = !empty($opts['allow-insecure-http']);
    $allowInsecureSsl = !empty($opts['allow-insecure-ssl']);

    if ($rawAction === 'sync-receiver') {
      $this->handleSyncReceiver();
      return;
    }

    $allowedActions = [
      'health',
      'geoip',
      'db-info',
      'db-pull',
      'db-truncate',
      'cache-clear',
      'logs',
      'logs-clear',
    ];
    if (!in_array($rawAction, $allowedActions, true)) {
      echo "\n❌ Acción remota desconocida: '{$rawAction}'.\n";
      $this->showUsage();
      exit(1);
    }

    $config = $this->loadConfig();
    $this->assertRemoteConfig($config, $allowInsecureHttp);

    if ($allowInsecureHttp && str_starts_with(strtolower($config['url']), 'http://')) {
      echo "⚠️  ADVERTENCIA DE SEGURIDAD: --allow-insecure-http activo sobre {$config['url']}. Úsalo únicamente en pruebas locales.\n";
    }
    if ($allowInsecureSsl) {
      echo "⚠️  ADVERTENCIA DE SEGURIDAD: --allow-insecure-ssl activo. Validación de certificado TLS deshabilitada.\n";
    }

    switch ($rawAction) {
      case 'health':
        $this->handleHealth($config, $allowInsecureSsl);
        break;
      case 'geoip':
        $fileOpt = isset($opts['file']) && is_string($opts['file']) ? self::sanitizeInputPath($opts['file']) : null;
        $transportOpt = isset($opts['transport']) && is_string($opts['transport']) ? strtolower(trim($opts['transport'])) : null;
        $this->handleGeoip($config, $fileOpt, $allowInsecureSsl, $transportOpt);
        break;
      case 'db-info':
        $this->handleDbInfo($config, $allowInsecureSsl);
        break;
      case 'db-pull':
        $outOpt = isset($opts['out']) && is_string($opts['out']) ? self::sanitizeInputPath($opts['out']) : null;
        $this->handleDbPull($config, $outOpt, $allowInsecureSsl);
        break;
      case 'db-truncate':
        $tablesOpt = isset($opts['tables']) && is_string($opts['tables']) ? $opts['tables'] : null;
        $this->handleDbTruncate($config, $tablesOpt, $allowInsecureSsl);
        break;
      case 'cache-clear':
        $this->handleCacheClear($config, $allowInsecureSsl);
        break;
      case 'logs':
        $fileArg = isset($pos[0]) ? self::sanitizeInputPath((string)$pos[0]) : null;
        $linesArg = isset($pos[1]) && ctype_digit((string)$pos[1]) ? (int)$pos[1] : 100;
        if (isset($opts['lines']) && ctype_digit((string)$opts['lines'])) {
          $linesArg = (int)$opts['lines'];
        }
        $this->handleLogs($config, $fileArg, $linesArg, $allowInsecureSsl);
        break;
      case 'logs-clear':
        $fileArg = isset($pos[0]) ? self::sanitizeInputPath((string)$pos[0]) : null;
        if (isset($opts['file']) && is_string($opts['file'])) {
          $fileArg = self::sanitizeInputPath($opts['file']);
        }
        $this->handleLogsClear($config, $fileArg, $allowInsecureSsl);
        break;
    }
  }

  /**
   * Parsea flags --clave=valor, --clave valor y argumentos posicionales.
   *
   * @param array<int, string> $args
   * @return array{options: array<string, string|bool>, positional: array<int, string>}
   */
  private function parseOptions(array $args): array
  {
    $options = [];
    $positional = [];
    $count = count($args);

    for ($i = 0; $i < $count; $i++) {
      $arg = (string)$args[$i];
      if (str_starts_with($arg, '--')) {
        $eqPos = strpos($arg, '=');
        if ($eqPos !== false) {
          $key = substr($arg, 2, $eqPos - 2);
          $val = substr($arg, $eqPos + 1);
          $options[$key] = $val;
        } else {
          $key = substr($arg, 2);
          if (in_array($key, ['file', 'out', 'tables', 'lines', 'transport'], true) && isset($args[$i + 1]) && !str_starts_with((string)$args[$i + 1], '--')) {
            $options[$key] = (string)$args[++$i];
          } else {
            $options[$key] = true;
          }
        }
      } else {
        $positional[] = $arg;
      }
    }

    return ['options' => $options, 'positional' => $positional];
  }

  /**
   * Lee DEPLOY_URL, DEPLOY_TOKEN, SEED y MAXMIND_LICENSE_KEY desde .env y deploy.json.
   *
   * @return array{url: string, token: string, seed: string, maxmindKey: string}
   */
  private function loadConfig(): array
  {
    $config = [
      'url'        => '',
      'token'      => '',
      'seed'       => '',
      'maxmindKey' => '',
    ];

    $envFile = $this->basePath . '/.env';
    if (is_file($envFile)) {
      $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
      foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
          continue;
        }
        if (str_starts_with($line, 'DEPLOY_URL=')) {
          $config['url'] = trim(substr($line, 11), " \"'");
        } elseif (str_starts_with($line, 'DEPLOY_TOKEN=')) {
          $config['token'] = trim(substr($line, 13), " \"'");
        } elseif (str_starts_with($line, 'SEED=')) {
          $config['seed'] = trim(substr($line, 5), " \"'");
        } elseif (str_starts_with($line, 'MAXMIND_LICENSE_KEY=')) {
          $config['maxmindKey'] = trim(substr($line, 20), " \"'");
        }
      }
    }

    $jsonFile = $this->basePath . '/deploy.json';
    if (is_file($jsonFile)) {
      $data = json_decode((string)@file_get_contents($jsonFile), true);
      if (is_array($data)) {
        if (!empty($data['url'])) {
          $config['url'] = (string)$data['url'];
        }
        if (!empty($data['token'])) {
          $config['token'] = (string)$data['token'];
        }
      }
    }

    if ($config['url'] !== '' && !str_ends_with(strtolower($config['url']), '.php')) {
      $config['url'] = rtrim($config['url'], '/') . '/deploy.php';
    }

    return $config;
  }

  /**
   * Valida que DEPLOY_URL (HTTPS obligatorio por defecto), DEPLOY_TOKEN y SEED estén listos.
   *
   * @param array<string, string> $config
   * @param bool                  $allowInsecureHttp
   * @return void
   */
  private function assertRemoteConfig(array $config, bool $allowInsecureHttp): void
  {
    if ($config['url'] === '' || $config['token'] === '' || $config['seed'] === '') {
      echo "\n❌ Error: Faltan credenciales remotas en tu archivo .env local.\n";
      echo "   Debes configurar:\n";
      echo "   DEPLOY_URL=\"https://tudominio.com/deploy.php\"\n";
      echo "   DEPLOY_TOKEN=\"tu_token_secreto\"\n";
      echo "   SEED=\"tu_semilla_de_64_caracteres\"\n\n";
      exit(1);
    }

    if (strlen($config['token']) < 16 || strlen($config['seed']) < 16) {
      echo "\n❌ Error: DEPLOY_TOKEN o SEED no cumplen la longitud mínima segura (16+ caracteres).\n\n";
      exit(1);
    }

    $lowerUrl = strtolower($config['url']);
    if (str_starts_with($lowerUrl, 'http://') && !$allowInsecureHttp) {
      echo "\n❌ Bloqueado por seguridad: las operaciones remotas requieren HTTPS obligatorio ({$config['url']}).\n";
      echo "   Actualiza DEPLOY_URL a https://... o usa --allow-insecure-http únicamente en pruebas locales.\n\n";
      exit(1);
    }

    if (!str_starts_with($lowerUrl, 'https://') && !str_starts_with($lowerUrl, 'http://')) {
      echo "\n❌ Error: DEPLOY_URL inválida ('{$config['url']}'). Debe comenzar con https://.\n\n";
      exit(1);
    }
  }

  /**
   * Solicita confirmación interactiva estándar (yes / y / Yes / YES) antes de conectar.
   *
   * @param string $summary Descripción de la acción a autorizar.
   * @param string $targetUrl URL de destino.
   * @return bool
   */
  private function confirmRemoteOperation(string $summary, string $targetUrl): bool
  {
    echo "\n⚠️  OPERACIÓN REMOTA EN PRODUCCIÓN\n";
    echo "   Destino: {$targetUrl}\n";
    echo "   Acción:  {$summary}\n";
    echo "¿Confirmas ejecutar esta operación? Escribe [yes / y / Yes / YES] para continuar (cualquier otra entrada cancela): ";

    $raw = fgets(STDIN);
    $answer = $raw === false ? '' : $raw;
    if (!self::isActionConfirmed($answer)) {
      echo "\n🛑 Operación cancelada. No se realizó ninguna conexión al servidor.\n\n";
      return false;
    }

    return true;
  }

  /**
   * Ejecuta frame remote:health.
   */
  private function handleHealth(array $config, bool $allowInsecureSsl): void
  {
    if (!$this->confirmRemoteOperation('Diagnóstico de salud del servidor remoto (health)', $config['url'])) {
      return;
    }

    echo "\n⏳ Consultando salud del servidor...\n";
    $res = $this->sendJsonAction($config, 'health', [], $allowInsecureSsl);
    $this->assertSuccessResponse($res);

    echo "\n======================================================\n";
    echo "🩺 SALUD DEL SERVIDOR REMOTO\n";
    echo "======================================================\n";
    echo "🐘 PHP:          " . ($res['php_version'] ?? '?') . " (" . ($res['sapi'] ?? '?') . " / " . ($res['os'] ?? '?') . ")\n";
    echo "🕒 Hora Server:  " . ($res['server_time'] ?? '?') . "\n";

    $limits = (array)($res['limits'] ?? []);
    echo "⚙️  Límites:      upload_max_filesize=" . ($limits['upload_max_filesize'] ?? '?')
      . ", post_max_size=" . ($limits['post_max_size'] ?? '?')
      . ", memory_limit=" . ($limits['memory_limit'] ?? '?')
      . ", max_execution_time=" . ($limits['max_execution_time'] ?? '?') . "s\n";

    $disk = (array)($res['disk'] ?? []);
    $freeBytes = $disk['free_bytes'] ?? null;
    $totalBytes = $disk['total_bytes'] ?? null;
    if (is_numeric($freeBytes) && is_numeric($totalBytes)) {
      echo "💾 Disco:        " . $this->formatBytes((int)$freeBytes) . " libres de " . $this->formatBytes((int)$totalBytes) . "\n";
    }

    echo "\n📦 Extensiones:\n";
    foreach ((array)($res['extensions'] ?? []) as $ext => $loaded) {
      echo "   " . ($loaded ? '✅' : '❌') . " {$ext}\n";
    }

    echo "\n📁 Permisos de escritura:\n";
    foreach ((array)($res['writable'] ?? []) as $dir => $info) {
      $exists = !empty($info['exists']);
      $writable = !empty($info['writable']);
      $status = !$exists ? '❌ no existe' : ($writable ? '✅ escribible' : '⚠️ sin permiso de escritura');
      echo "   • {$dir}: {$status}\n";
    }

    $sqlite = (array)($res['sqlite'] ?? []);
    echo "\n🗄️  Base de datos SQLite:\n";
    if (!empty($sqlite['exists'])) {
      echo "   ✅ {$sqlite['path']} (" . $this->formatBytes((int)($sqlite['size_bytes'] ?? 0)) . ", journal=" . ($sqlite['journal_mode'] ?? '?') . ", mod=" . ($sqlite['mtime'] ?? '?') . ")\n";
    } else {
      echo "   ⚠️  No se detectó archivo SQLite en Database/\n";
    }

    $geoip = (array)($res['geoip'] ?? []);
    echo "\n🌍 GeoIP (MaxMind):\n";
    if (!empty($geoip['exists'])) {
      echo "   ✅ {$geoip['path']} (" . $this->formatBytes((int)($geoip['size_bytes'] ?? 0)) . ", mod=" . ($geoip['mtime'] ?? '?') . ")\n";
    } else {
      echo "   ⚠️  No instalado en {$geoip['path']}\n";
    }
    echo "\n";
  }

  /**
   * Ejecuta frame remote:geoip [--file=ruta] [--transport=zip|json].
   */
  private function handleGeoip(array $config, ?string $localFile, bool $allowInsecureSsl, ?string $transportOpt = null): void
  {
    if ($transportOpt !== null && !in_array($transportOpt, ['zip', 'json'], true)) {
      echo "\n❌ Error: Transporte inválido '{$transportOpt}'. Usa --transport=zip o --transport=json.\n\n";
      exit(1);
    }

    $modeLabel = $localFile !== null
      ? "Subir archivo local '{$localFile}' a App/DatabaseComponent/GeoLite2-City.mmdb"
      : 'Descargar GeoLite2-City.mmdb en local (MaxMind) y subirlo por fragmentos al servidor';

    if (!$this->confirmRemoteOperation($modeLabel, $config['url'])) {
      return;
    }

    ini_set('memory_limit', '512M');
    $tmpWork = rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/') . '/frame_remote_geoip_' . bin2hex(random_bytes(6));
    @mkdir($tmpWork, 0755, true);

    try {
      $mmdbSource = '';
      if ($localFile !== null) {
        if ($localFile === '' || !is_file($localFile)) {
          echo "\n❌ Error: El archivo especificado no existe: '{$localFile}'.\n\n";
          exit(1);
        }
        $mmdbSource = $localFile;
        echo "\n📄 Usando archivo local: {$mmdbSource}\n";
      } else {
        if ($config['maxmindKey'] === '') {
          echo "\n❌ Error: Falta MAXMIND_LICENSE_KEY en .env (o usa --file=ruta/a/GeoLite2-City.mmdb).\n\n";
          exit(1);
        }
        echo "\n🌍 Descargando GeoLite2-City desde MaxMind en la PC local...\n";
        $tarGz = $tmpWork . '/GeoLite2-City.tar.gz';
        $tarFile = $tmpWork . '/GeoLite2-City.tar';
        $extractDir = $tmpWork . '/extract';

        $url = 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key='
          . urlencode($config['maxmindKey']) . '&suffix=tar.gz';
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'header' => "User-Agent: EberFrame-CLI/1.0\r\n", 'timeout' => 120]]);
        $rawTarGz = @file_get_contents($url, false, $ctx);
        if ($rawTarGz === false || $rawTarGz === '') {
          echo "❌ Error: No se pudo descargar GeoLite2-City desde MaxMind. Verifica MAXMIND_LICENSE_KEY.\n\n";
          exit(1);
        }
        file_put_contents($tarGz, $rawTarGz);
        unset($rawTarGz);

        $phar = new \PharData($tarGz);
        $phar->decompress();
        $tar = new \PharData($tarFile);
        @mkdir($extractDir, 0755, true);
        $tar->extractTo($extractDir, null, true);

        $found = '';
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractDir, \RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($it as $entry) {
          if ($entry->isFile() && strtolower($entry->getExtension()) === 'mmdb') {
            $found = $entry->getRealPath() ?: '';
            break;
          }
        }
        if ($found === '' || !is_file($found)) {
          echo "❌ Error: No se encontró un archivo .mmdb dentro del paquete descargado.\n\n";
          exit(1);
        }
        $mmdbSource = $found;
        echo "   ✓ Extraído correctamente (" . $this->formatBytes((int)filesize($mmdbSource)) . ")\n";
      }

      // Validar cabecera binaria MaxMind en los últimos 128 KB antes de comprimir y subir
      $fpCheck = @fopen($mmdbSource, 'rb');
      $validMaxMind = false;
      if ($fpCheck) {
        $fsize = (int)filesize($mmdbSource);
        fseek($fpCheck, max(0, $fsize - 131072));
        $tail = (string)fread($fpCheck, 131072);
        fclose($fpCheck);
        $validMaxMind = str_contains($tail, "\xab\xcd\xefMaxMind.com");
      }
      if (!$validMaxMind) {
        echo "\n❌ Error: El archivo '{$mmdbSource}' no contiene una firma binaria MaxMind (.mmdb) válida.\n\n";
        exit(1);
      }

      if (!function_exists('gzopen')) {
        echo "\n❌ Error: Se requiere la extensión zlib en PHP local para comprimir GeoIP.\n\n";
        exit(1);
      }

      $mmdbSha = (string)hash_file('sha256', $mmdbSource);
      $gzPath = $tmpWork . '/GeoLite2-City.mmdb.gz';
      echo "🗜️  Comprimiendo .mmdb a .gz...\n";
      $inFp = @fopen($mmdbSource, 'rb');
      $gzFp = @gzopen($gzPath, 'wb6');
      if (!$inFp || !$gzFp) {
        if ($inFp) fclose($inFp);
        if ($gzFp) gzclose($gzFp);
        echo "❌ Error al comprimir el archivo GeoIP.\n\n";
        exit(1);
      }
      while (!feof($inFp)) {
        $buf = fread($inFp, 65536);
        if ($buf !== false && $buf !== '') {
          gzwrite($gzFp, $buf);
        }
      }
      fclose($inFp);
      gzclose($gzFp);

      $gzSha = (string)hash_file('sha256', $gzPath);
      $profiles = $transportOpt === 'json'
        ? [
            ['mode' => 'json', 'bytes' => 524288, 'label' => 'JSON Base64 (512 KB)'],
            ['mode' => 'json', 'bytes' => 65536, 'label' => 'JSON Base64 (64 KB)'],
          ]
        : ($transportOpt === 'zip'
          ? [
              ['mode' => 'zip', 'bytes' => self::GEOIP_CHUNK_BYTES, 'label' => 'ZIP (2 MB)'],
            ]
          : [
              ['mode' => 'zip', 'bytes' => self::GEOIP_CHUNK_BYTES, 'label' => 'ZIP (2 MB)'],
              ['mode' => 'json', 'bytes' => 524288, 'label' => 'JSON Base64 (512 KB)'],
              ['mode' => 'json', 'bytes' => 65536, 'label' => 'JSON Base64 (64 KB)'],
            ]);

      $uploadResult = null;
      $profileCount = count($profiles);
      foreach ($profiles as $pIdx => $profile) {
        $attempt = $this->uploadGeoipWithProfile(
          $config,
          $gzPath,
          $tmpWork,
          $profile['mode'],
          $profile['bytes'],
          $profile['label'],
          $allowInsecureSsl
        );

        if (!empty($attempt['ok'])) {
          $uploadResult = $attempt;
          break;
        }

        $httpCode = (int)($attempt['http_code'] ?? 0);
        $hasFallback = ($pIdx + 1) < $profileCount;
        if ($hasFallback && in_array($httpCode, [406, 413], true)) {
          $nextLabel = $profiles[$pIdx + 1]['label'];
          echo "⚠️  Servidor bloqueó/limitó fragmento inicial en {$profile['label']} (HTTP {$httpCode}). Reintentando automáticamente con {$nextLabel}...\n";
          continue;
        }

        $this->assertSuccessResponse($attempt);
      }

      if ($uploadResult === null) {
        echo "\n❌ Error en operación remota:\n   No se pudo completar la transferencia de fragmentos GeoIP.\n\n";
        exit(1);
      }

      echo "🔐 Verificando SHA-256 y aplicando reemplazo atómico en el servidor...\n";
      $commitRes = $this->sendJsonAction($config, 'geoip_commit', [
        'upload_id'   => $uploadResult['upload_id'],
        'chunk_total' => $uploadResult['chunk_total'],
        'gz_sha256'   => $gzSha,
        'mmdb_sha256' => $mmdbSha,
      ], $allowInsecureSsl);
      $this->assertSuccessResponse($commitRes);

      echo "\n✅ GeoLite2-City.mmdb actualizado en producción: "
        . ($commitRes['path'] ?? 'App/DatabaseComponent/GeoLite2-City.mmdb')
        . " (" . $this->formatBytes((int)($commitRes['size_bytes'] ?? 0)) . ")\n\n";
    } finally {
      $this->removeDirectory($tmpWork);
    }
  }

  /**
   * Transfiere un archivo .gz comprimido fragmentado usando el transporte indicado ('zip' o 'json').
   *
   * @return array<string, mixed>
   */
  private function uploadGeoipWithProfile(
    array $config,
    string $gzPath,
    string $tmpWork,
    string $mode,
    int $chunkBytes,
    string $label,
    bool $allowInsecureSsl
  ): array {
    $gzSize = (int)filesize($gzPath);
    $chunkTotal = max(1, (int)ceil($gzSize / $chunkBytes));
    $uploadId = bin2hex(random_bytes(16));

    echo "🚀 Subiendo " . $this->formatBytes($gzSize) . " en {$chunkTotal} fragmento(s) [{$label}]...\n";

    $readFp = @fopen($gzPath, 'rb');
    if (!$readFp) {
      return ['success' => false, 'ok' => false, 'http_code' => 0, 'error' => 'Error leyendo archivo comprimido temporal.'];
    }

    for ($idx = 0; $idx < $chunkTotal; $idx++) {
      $data = fread($readFp, $chunkBytes);
      if ($data === false || $data === '') {
        fclose($readFp);
        return ['success' => false, 'ok' => false, 'http_code' => 0, 'error' => "Error leyendo fragmento #{$idx}."];
      }
      $chunkSha = hash('sha256', $data);

      if ($mode === 'zip') {
        $chunkRes = $this->sendGeoipChunkZip($config, $uploadId, $idx, $chunkTotal, $data, $chunkSha, $tmpWork, $allowInsecureSsl);
      } else {
        $chunkRes = $this->sendJsonAction($config, 'geoip_chunk', [
          'upload_id'    => $uploadId,
          'chunk_index'  => $idx,
          'chunk_total'  => $chunkTotal,
          'chunk_sha256' => $chunkSha,
          'chunk_b64'    => base64_encode($data),
        ], $allowInsecureSsl);
      }

      if (empty($chunkRes['success'])) {
        fclose($readFp);
        if ($idx === 0 && in_array((int)($chunkRes['http_code'] ?? 0), [406, 413], true)) {
          return [
            'ok'        => false,
            'success'   => false,
            'http_code' => (int)$chunkRes['http_code'],
            'error'     => $chunkRes['error'] ?? 'Error subiendo fragmento #0',
          ];
        }
        $this->assertSuccessResponse($chunkRes);
      }

      $pct = round((($idx + 1) / $chunkTotal) * 100);
      echo "   ✓ Fragmento " . ($idx + 1) . "/{$chunkTotal} ({$pct}%)\n";
    }
    fclose($readFp);

    return [
      'ok'          => true,
      'upload_id'   => $uploadId,
      'chunk_total' => $chunkTotal,
    ];
  }

  /**
   * Ejecuta frame remote:db-info.
   */
  private function handleDbInfo(array $config, bool $allowInsecureSsl): void
  {
    if (!$this->confirmRemoteOperation('Consultar tablas y métricas de SQLite en producción (db-info)', $config['url'])) {
      return;
    }

    echo "\n⏳ Consultando esquema SQLite remoto...\n";
    $res = $this->sendJsonAction($config, 'db_info', [], $allowInsecureSsl);
    $this->assertSuccessResponse($res);

    $this->renderDbInfo($res);
  }

  /**
   * Imprime la tabla formateada de db_info.
   *
   * @param array<string, mixed> $res
   */
  private function renderDbInfo(array $res): void
  {
    echo "\n======================================================\n";
    echo "🗄️  BASE DE DATOS REMOTA: " . ($res['database'] ?? '?') . "\n";
    echo "======================================================\n";
    echo "📊 Tamaño:  " . $this->formatBytes((int)($res['size_bytes'] ?? 0)) . "\n";
    echo "📓 Journal: " . ($res['journal_mode'] ?? '?') . "\n\n";

    $tables = (array)($res['tables'] ?? []);
    if (empty($tables)) {
      echo "   (La base de datos no contiene tablas de usuario)\n\n";
      return;
    }

    printf("  %-4s %-28s %12s %12s\n", '#', 'Tabla', 'Filas', 'Seq AutoInc');
    echo "  " . str_repeat('-', 60) . "\n";
    foreach ($tables as $idx => $tbl) {
      $seq = isset($tbl['sequence']) && $tbl['sequence'] !== null ? (string)$tbl['sequence'] : '-';
      printf("  %-4d %-28s %12s %12s\n", $idx + 1, (string)$tbl['name'], number_format((int)$tbl['rows']), $seq);
    }
    echo "\n";
  }

  /**
   * Ejecuta frame remote:db-pull [--out=ruta].
   */
  private function handleDbPull(array $config, ?string $outPath, bool $allowInsecureSsl): void
  {
    if (!$this->confirmRemoteOperation('Generar snapshot consistente (VACUUM INTO) y descargar respaldo SQLite', $config['url'])) {
      return;
    }

    $destPath = $this->resolvePullDestination($outPath, false);
    $result = $this->downloadConsistentBackup($config, $destPath, $allowInsecureSsl);

    echo "\n✅ Respaldo SQLite descargado y verificado:\n";
    echo "   📁 Archivo: {$result['path']}\n";
    echo "   📊 Tamaño:  " . $this->formatBytes((int)$result['bytes']) . "\n";
    echo "   🔐 SHA-256: {$result['sha256']}\n\n";
  }

  /**
   * Ejecuta frame remote:db-truncate [--tables=t1,t2].
   */
  private function handleDbTruncate(array $config, ?string $tablesOpt, bool $allowInsecureSsl): void
  {
    if (!$this->confirmRemoteOperation('VACIAR tablas de SQLite en producción con respaldo previo obligatorio (db-truncate)', $config['url'])) {
      return;
    }

    echo "\n⏳ Consultando tablas actuales en producción...\n";
    $infoRes = $this->sendJsonAction($config, 'db_info', [], $allowInsecureSsl);
    $this->assertSuccessResponse($infoRes);

    $available = [];
    foreach ((array)($infoRes['tables'] ?? []) as $row) {
      $available[(string)$row['name']] = (int)$row['rows'];
    }

    if (empty($available)) {
      echo "\nℹ️  La base de datos no tiene tablas para truncar.\n\n";
      return;
    }

    $selected = [];
    if ($tablesOpt !== null && trim($tablesOpt) !== '') {
      foreach (explode(',', $tablesOpt) as $rawName) {
        $name = trim($rawName);
        if ($name !== '' && !in_array($name, $selected, true)) {
          $selected[] = $name;
        }
      }
    } else {
      $this->renderDbInfo($infoRes);
      echo "Escribe los nombres o números de las tablas a truncar separados por coma (ej: visits,sessions o 1,2):\n> ";
      $rawChoice = trim((string)fgets(STDIN));
      if ($rawChoice === '') {
        echo "\n🛑 Operación cancelada. No se seleccionó ninguna tabla.\n\n";
        return;
      }
      $indexedNames = array_keys($available);
      foreach (explode(',', $rawChoice) as $token) {
        $t = trim($token);
        if ($t === '') {
          continue;
        }
        if (ctype_digit($t)) {
          $idx = (int)$t - 1;
          if (!isset($indexedNames[$idx])) {
            echo "\n❌ Error: Índice de tabla inválido '{$t}'.\n\n";
            exit(1);
          }
          $t = $indexedNames[$idx];
        }
        if (!in_array($t, $selected, true)) {
          $selected[] = $t;
        }
      }
    }

    if (empty($selected)) {
      echo "\n🛑 Operación cancelada.\n\n";
      return;
    }

    foreach ($selected as $tbl) {
      if (stripos($tbl, 'sqlite_') === 0 || !array_key_exists($tbl, $available)) {
        echo "\n❌ Error: La tabla '{$tbl}' no existe o está protegida.\n\n";
        exit(1);
      }
    }

    // Doble confirmación destructiva exigiendo los nombres exactos de las tablas seleccionadas
    echo "\n🚨 DOBLE CONFIRMACIÓN DESTRUCTIVA REQUERIDA\n";
    echo "   Se eliminarán TODAS las filas y se reiniciará AUTOINCREMENT a 1 en:\n";
    foreach ($selected as $tbl) {
      echo "   • {$tbl} ({$available[$tbl]} filas actuales)\n";
    }
    $expectedConfirmStr = implode(',', $selected);
    echo "\nPara confirmar, escribe exactamente los nombres de las tablas ({$expectedConfirmStr}):\n> ";
    $typedConfirm = trim((string)fgets(STDIN));
    $typedList = array_values(array_filter(array_map('trim', explode(',', $typedConfirm)), fn(string $v): bool => $v !== ''));
    if ($typedList !== $selected) {
      echo "\n🛑 Confirmación no coincide. Operación abortada sin modificar la base de datos.\n\n";
      return;
    }

    // Respaldo automático obligatorio y verificado antes de borrar
    $backupPath = $this->resolvePullDestination(null, true);
    echo "\n💾 Descargando respaldo de seguridad previo a '{$backupPath}'...\n";
    $backupRes = $this->downloadConsistentBackup($config, $backupPath, $allowInsecureSsl);
    echo "   ✓ Respaldo verificado (SHA-256: {$backupRes['sha256']}, " . $this->formatBytes((int)$backupRes['bytes']) . ")\n";

    echo "🧹 Truncando tablas seleccionadas y ejecutando VACUUM en producción...\n";
    $truncRes = $this->sendJsonAction($config, 'db_truncate', ['tables' => $selected], $allowInsecureSsl);
    $this->assertSuccessResponse($truncRes);

    echo "\n✅ Tablas truncadas con éxito en " . ($truncRes['database'] ?? 'SQLite') . ":\n";
    foreach ((array)($truncRes['truncated'] ?? []) as $tbl => $info) {
      $resetTxt = !empty($info['autoincrement_reset']) ? 'AUTOINCREMENT reiniciado a 1' : 'sin sqlite_sequence';
      echo "   • {$tbl}: " . (int)($info['deleted_rows'] ?? 0) . " filas eliminadas ({$resetTxt})\n";
    }
    echo "   • VACUUM: " . (!empty($truncRes['vacuum']) ? 'completado' : 'omitido') . "\n\n";
  }

  /**
   * Ejecuta frame remote:cache-clear.
   */
  private function handleCacheClear(array $config, bool $allowInsecureSsl): void
  {
    if (!$this->confirmRemoteOperation('Limpiar Cache/Views/* e invalidar OPcache en producción', $config['url'])) {
      return;
    }

    echo "\n⏳ Limpiando caché remoto...\n";
    $res = $this->sendJsonAction($config, 'cache_clear', [], $allowInsecureSsl);
    $this->assertSuccessResponse($res);

    echo "\n✅ Caché limpiado en producción:\n";
    echo "   • Archivos de vistas eliminados: " . (int)($res['removed_files'] ?? 0) . "\n";
    echo "   • OPcache reseteado: " . (!empty($res['opcache_reset']) ? 'Sí' : 'No disponible/omitido') . "\n\n";
  }

  /**
   * Ejecuta frame remote:logs [archivo] [lineas].
   */
  private function handleLogs(array $config, ?string $file, int $lines, bool $allowInsecureSsl): void
  {
    $desc = $file !== null && $file !== ''
      ? "Leer últimas {$lines} líneas de Logs/{$file}"
      : 'Listar archivos disponibles en Logs/';

    if (!$this->confirmRemoteOperation($desc, $config['url'])) {
      return;
    }

    if ($file === null || $file === '') {
      echo "\n⏳ Listando archivos de log...\n";
      $res = $this->sendJsonAction($config, 'logs_list', [], $allowInsecureSsl);
      $this->assertSuccessResponse($res);

      $files = (array)($res['files'] ?? []);
      echo "\n======================================================\n";
      echo "📜 ARCHIVOS EN Logs/ (" . count($files) . ")\n";
      echo "======================================================\n";
      if (empty($files)) {
        echo "   (No hay archivos de log)\n\n";
        return;
      }
      foreach ($files as $entry) {
        printf("  %-42s %10s  %s\n", (string)$entry['file'], $this->formatBytes((int)$entry['size_bytes']), (string)$entry['mtime']);
      }
      echo "\n💡 Usa: frame remote:logs <archivo> [lineas]\n\n";
      return;
    }

    echo "\n⏳ Descargando últimas {$lines} líneas de Logs/{$file}...\n";
    $res = $this->sendJsonAction($config, 'logs_tail', ['file' => $file, 'lines' => $lines], $allowInsecureSsl);
    $this->assertSuccessResponse($res);

    echo "\n======================================================\n";
    echo "📜 Logs/" . ($res['file'] ?? $file) . " (mostrando " . count((array)($res['lines'] ?? [])) . " de " . (int)($res['total_lines'] ?? 0) . " líneas)\n";
    echo "======================================================\n";
    foreach ((array)($res['lines'] ?? []) as $line) {
      echo $line . "\n";
    }
    echo "\n";
  }

  /**
   * Ejecuta frame remote:logs-clear [archivo].
   */
  private function handleLogsClear(array $config, ?string $specificFile, bool $allowInsecureSsl): void
  {
    $desc = $specificFile !== null && $specificFile !== ''
      ? "Vaciar archivo Logs/{$specificFile} en producción"
      : 'Vaciar TODOS los archivos dentro de Logs/ en producción (conservando carpetas)';

    if (!$this->confirmRemoteOperation($desc, $config['url'])) {
      return;
    }

    echo "\n⏳ Inspeccionando archivos actuales en Logs/...\n";
    $listRes = $this->sendJsonAction($config, 'logs_list', [], $allowInsecureSsl);
    $this->assertSuccessResponse($listRes);

    $allFiles = (array)($listRes['files'] ?? []);
    $targets = [];
    $totalBytes = 0;

    foreach ($allFiles as $entry) {
      $rel = (string)($entry['file'] ?? '');
      if ($specificFile !== null && $specificFile !== '') {
        if ($rel === $specificFile) {
          $targets[] = $entry;
          $totalBytes += (int)($entry['size_bytes'] ?? 0);
        }
      } else {
        $targets[] = $entry;
        $totalBytes += (int)($entry['size_bytes'] ?? 0);
      }
    }

    if (empty($targets)) {
      if ($specificFile !== null && $specificFile !== '') {
        echo "\n❌ Error: No se encontró el archivo 'Logs/{$specificFile}' en el servidor.\n\n";
        exit(1);
      }
      echo "\nℹ️  No hay archivos de log para vaciar.\n\n";
      return;
    }

    echo "\n🚨 SEGUNDA CONFIRMACIÓN DESTRUCTIVA REQUERIDA\n";
    echo "   Se vaciarán a 0 bytes los siguientes archivos (" . $this->formatBytes($totalBytes) . " totales):\n";
    foreach ($targets as $entry) {
      echo "   • Logs/" . $entry['file'] . " (" . $this->formatBytes((int)$entry['size_bytes']) . ")\n";
    }
    echo "¿Confirmas vaciar definitivamente estos logs? Escribe [yes / y / Yes / YES]: ";
    $secondRaw = fgets(STDIN);
    if (!self::isActionConfirmed($secondRaw === false ? '' : $secondRaw)) {
      echo "\n🛑 Operación cancelada. No se modificó ningún archivo de log.\n\n";
      return;
    }

    $targetNames = array_map(fn(array $e): string => (string)$e['file'], $targets);
    $clearRes = $this->sendJsonAction($config, 'logs_clear', ['files' => $targetNames], $allowInsecureSsl);
    $this->assertSuccessResponse($clearRes);

    echo "\n✅ Logs vaciados en producción (" . (int)($clearRes['cleared_count'] ?? 0) . " archivos):\n";
    foreach ((array)($clearRes['cleared'] ?? []) as $item) {
      echo "   • Logs/" . $item['file'] . " (liberados " . $this->formatBytes((int)($item['freed_bytes'] ?? 0)) . ")\n";
    }
    echo "\n";
  }

  /**
   * Ejecuta frame remote:sync-receiver: regenera deploy.php local conservando DEPLOY_TOKEN.
   */
  private function handleSyncReceiver(): void
  {
    $deployFile = $this->basePath . '/deploy.php';
    if (!$this->confirmRemoteOperation('Regenerar deploy.php local con soporte para frame remote:* conservando DEPLOY_TOKEN', $deployFile)) {
      return;
    }

    require_once __DIR__ . '/DeployPackage.php';

    $config = $this->loadConfig();
    $token = $config['token'];

    if (is_file($deployFile)) {
      $content = (string)@file_get_contents($deployFile);
      if (preg_match('/\$expectedToken\s*=\s*\'([^\']+)\'/', $content, $m)) {
        if (!empty($m[1]) && $m[1] !== '###DEPLOY_TOKEN###') {
          $token = $m[1];
        }
      }
    }

    if ($token === '' || strlen($token) < 16) {
      $token = bin2hex(random_bytes(16));
    }

    $template = \DeployPackage::getDeployReceiverTemplate($token);
    if (@file_put_contents($deployFile, $template) === false) {
      echo "\n❌ Error: No se pudo escribir '{$deployFile}'.\n\n";
      exit(1);
    }

    echo "\n✅ Archivo 'deploy.php' regenerado correctamente conservando DEPLOY_TOKEN.\n";
    echo "👉 Paso único: sube este 'deploy.php' actualizado por cPanel/FTP a la raíz de tu hosting.\n";
    echo "   Asegúrate también de que producción tenga tu SEED (en App/Config/secrets.vault.php o .env).\n\n";
  }

  /**
   * Resuelve ruta de salida para backups locales en backups/ (creando la carpeta si falta).
   */
  private function resolvePullDestination(?string $customOut, bool $isPreTruncate): string
  {
    $safeProject = strtolower((string)preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->projectName));
    if ($safeProject === '') {
      $safeProject = 'project';
    }
    $stamp = date('Ymd_His');

    if ($customOut !== null && $customOut !== '') {
      $norm = str_replace('\\', '/', $customOut);
      if (!preg_match('/^[a-zA-Z]:\//', $norm) && !str_starts_with($norm, '/')) {
        $norm = $this->basePath . '/' . ltrim($norm, '/');
      }
      if (is_dir($norm) || str_ends_with($norm, '/')) {
        $dir = rtrim($norm, '/');
        $fileName = $safeProject . '_' . $stamp . '.sqlite.gz';
        $dest = $dir . '/' . $fileName;
      } else {
        $dest = $norm;
      }
    } else {
      $suffix = $isPreTruncate ? '_pre_truncate_' : '_';
      $dest = $this->basePath . '/backups/' . $safeProject . $suffix . $stamp . '.sqlite.gz';
    }

    $parent = dirname($dest);
    if (!is_dir($parent) && !@mkdir($parent, 0755, true)) {
      echo "\n❌ Error: No se pudo crear el directorio de respaldo '{$parent}'.\n\n";
      exit(1);
    }

    return $dest;
  }

  /**
   * Descarga por streaming un snapshot consistente de SQLite y valida su SHA-256.
   *
   * @return array{path: string, bytes: int, sha256: string}
   */
  private function downloadConsistentBackup(array $config, string $destPath, bool $allowInsecureSsl): array
  {
    $payloadJson = '{}';
    $payloadSha = hash('sha256', $payloadJson);
    $headersAssoc = self::buildSignatureHeaders('db_pull', $payloadSha, $config['token'], $config['seed']);
    $headers = [];
    foreach ($headersAssoc as $k => $v) {
      $headers[] = $k . ': ' . $v;
    }
    $headers[] = 'Content-Type: application/json';

    $partPath = $destPath . '.part.' . bin2hex(random_bytes(4));
    $fp = @fopen($partPath, 'wb');
    if (!$fp) {
      echo "\n❌ Error: No se pudo crear archivo temporal de descarga en '{$partPath}'.\n\n";
      exit(1);
    }

    $respHeaders = [];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'EberFrame-Remote/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function ($chHandle, string $headerLine) use (&$respHeaders): int {
      $len = strlen($headerLine);
      $parts = explode(':', $headerLine, 2);
      if (count($parts) === 2) {
        $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
      }
      return $len;
    });

    $this->applyTlsOptions($ch, !$allowInsecureSsl);
    $ok = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    fclose($fp);

    if ($ok === false) {
      @unlink($partPath);
      echo "\n❌ Error de conexión cURL descargando respaldo: {$curlErr}\n\n";
      exit(1);
    }

    if ($httpCode !== 200) {
      $errRaw = (string)@file_get_contents($partPath);
      @unlink($partPath);
      $errJson = json_decode($errRaw, true);
      $msg = is_array($errJson) && !empty($errJson['error']) ? $errJson['error'] : substr($errRaw, 0, 300);
      echo "\n❌ El servidor respondió HTTP {$httpCode} al generar respaldo: {$msg}\n\n";
      exit(1);
    }

    $expectedSha = strtolower((string)($respHeaders['x-backup-sha256'] ?? ''));
    $actualSha = strtolower((string)@hash_file('sha256', $partPath));
    if ($expectedSha === '' || !hash_equals($expectedSha, $actualSha)) {
      @unlink($partPath);
      echo "\n❌ Error de integridad: el SHA-256 del respaldo descargado no coincide con X-Backup-Sha256.\n\n";
      exit(1);
    }

    if (!@rename($partPath, $destPath)) {
      if (!@copy($partPath, $destPath)) {
        @unlink($partPath);
        echo "\n❌ Error al mover el respaldo verificado hacia '{$destPath}'.\n\n";
        exit(1);
      }
      @unlink($partPath);
    }

    return [
      'path'   => $destPath,
      'bytes'  => (int)@filesize($destPath),
      'sha256' => $actualSha,
    ];
  }

  /**
   * Envía una acción remota JSON firmada con HMAC-SHA256.
   *
   * @param array<string, mixed> $payload
   * @return array<string, mixed>
   */
  private function sendJsonAction(array $config, string $action, array $payload, bool $allowInsecureSsl): array
  {
    $rawBody = empty($payload) ? '{}' : (string)json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $payloadSha = hash('sha256', $rawBody);
    $headersAssoc = self::buildSignatureHeaders($action, $payloadSha, $config['token'], $config['seed']);
    $headers = [
      'Accept: application/json',
      'Content-Type: application/json',
    ];
    foreach ($headersAssoc as $k => $v) {
      $headers[] = $k . ': ' . $v;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'EberFrame-Remote/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    $this->applyTlsOptions($ch, !$allowInsecureSsl);

    return $this->executeAndDecodeCurl($ch);
  }

  /**
   * Envía un fragmento binario GeoIP empaquetado en ZIP (idéntico en formato a frame deploy)
   * para evitar bloqueos WAF/ModSecurity contra archivos binarios en cPanel.
   *
   * @return array<string, mixed>
   */
  private function sendGeoipChunkZip(
    array $config,
    string $uploadId,
    int $chunkIndex,
    int $chunkTotal,
    string $chunkData,
    string $chunkSha,
    string $tmpWork,
    bool $allowInsecureSsl
  ): array {
    $zipTmp = $tmpWork . "/geoip_chunk_{$chunkIndex}.zip";
    $zip = new \ZipArchive();
    if ($zip->open($zipTmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
      return ['success' => false, 'http_code' => 0, 'error' => "No se pudo crear ZIP temporal para fragmento #{$chunkIndex}."];
    }

    $metaJson = (string)json_encode([
      'upload_id'    => $uploadId,
      'chunk_index'  => $chunkIndex,
      'chunk_total'  => $chunkTotal,
      'chunk_sha256' => $chunkSha,
    ], JSON_UNESCAPED_SLASHES);

    $zip->addFromString('meta.json', $metaJson);
    $zip->addFromString('chunk.dat', $chunkData);
    if (method_exists($zip, 'setCompressionName')) {
      @$zip->setCompressionName('chunk.dat', \ZipArchive::CM_STORE);
    }
    $zip->close();

    $zipSha = (string)@hash_file('sha256', $zipTmp);
    $headersAssoc = self::buildSignatureHeaders('geoip_chunk', $zipSha, $config['token'], $config['seed']);
    $headers = ['Accept: application/json'];
    foreach ($headersAssoc as $k => $v) {
      $headers[] = $k . ': ' . $v;
    }

    $postData = [
      'token'   => $config['token'],
      'package' => new \CURLFile($zipTmp, 'application/zip', basename($zipTmp)),
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'EberFrame-Deploy/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    $this->applyTlsOptions($ch, !$allowInsecureSsl);

    $result = $this->executeAndDecodeCurl($ch);
    @unlink($zipTmp);
    return $result;
  }

  /**
   * Envía un fragmento binario GeoIP vía multipart legacy con firma HMAC-SHA256.
   *
   * @return array<string, mixed>
   */
  private function sendGeoipChunk(
    array $config,
    string $uploadId,
    int $chunkIndex,
    int $chunkTotal,
    string $partFile,
    string $chunkSha,
    bool $allowInsecureSsl
  ): array {
    $payloadSha = hash('sha256', $uploadId . '|' . $chunkIndex . '|' . $chunkTotal . '|' . $chunkSha);
    $headersAssoc = self::buildSignatureHeaders('geoip_chunk', $payloadSha, $config['token'], $config['seed']);
    $headers = ['Accept: application/json'];
    foreach ($headersAssoc as $k => $v) {
      $headers[] = $k . ': ' . $v;
    }

    $postData = [
      'upload_id'    => $uploadId,
      'chunk_index'  => (string)$chunkIndex,
      'chunk_total'  => (string)$chunkTotal,
      'chunk_sha256' => $chunkSha,
      'chunk'        => new \CURLFile($partFile, 'application/gzip', basename($partFile)),
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'EberFrame-Remote/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    $this->applyTlsOptions($ch, !$allowInsecureSsl);

    return $this->executeAndDecodeCurl($ch);
  }

  /**
   * Aplica opciones TLS estrictas (con CaBundle) o inseguras si se pidió --allow-insecure-ssl.
   */
  private function applyTlsOptions($ch, bool $verifySsl): void
  {
    if (defined('CURLPROTO_HTTPS') && defined('CURLPROTO_HTTP')) {
      curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
    }

    if (!$verifySsl) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      return;
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $caBundle = null;
    if (class_exists('Composer\\CaBundle\\CaBundle')) {
      $caBundle = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
    } elseif (is_file($this->frameworkPath . '/vendor/composer/ca-bundle/res/cacert.pem')) {
      $caBundle = $this->frameworkPath . '/vendor/composer/ca-bundle/res/cacert.pem';
    } elseif (is_file($this->basePath . '/vendor/composer/ca-bundle/res/cacert.pem')) {
      $caBundle = $this->basePath . '/vendor/composer/ca-bundle/res/cacert.pem';
    }
    if ($caBundle && is_file($caBundle)) {
      curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
    }
  }

  /**
   * Ejecuta cURL y decodifica la respuesta JSON remota.
   *
   * @return array<string, mixed>
   */
  private function executeAndDecodeCurl($ch): array
  {
    $raw = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
      return ['success' => false, 'http_code' => $httpCode, 'error' => "Error de conexión cURL: {$curlError}"];
    }

    $json = json_decode((string)$raw, true);
    if ($httpCode === 200 && is_array($json) && !empty($json['success'])) {
      $json['http_code'] = $httpCode;
      return $json;
    }

    return [
      'success'   => false,
      'http_code' => $httpCode,
      'error'     => "Servidor respondió HTTP {$httpCode}: " . ($json['error'] ?? substr((string)$raw, 0, 300)),
    ];
  }

  /**
   * Aborta con mensaje legible si la respuesta remota falló.
   */
  private function assertSuccessResponse(array $res): void
  {
    if (!empty($res['success'])) {
      return;
    }
    echo "\n❌ Error en operación remota:\n   " . ($res['error'] ?? 'Error desconocido') . "\n\n";
    exit(1);
  }

  /**
   * Formatea bytes en unidades legibles.
   */
  private function formatBytes(int $bytes): string
  {
    if ($bytes < 1024) {
      return $bytes . ' B';
    }
    if ($bytes < 1048576) {
      return round($bytes / 1024, 2) . ' KB';
    }
    if ($bytes < 1073741824) {
      return round($bytes / 1048576, 2) . ' MB';
    }
    return round($bytes / 1073741824, 2) . ' GB';
  }

  /**
   * Elimina recursivamente un directorio temporal local.
   */
  private function removeDirectory(string $dir): void
  {
    if (!is_dir($dir)) {
      return;
    }
    foreach (scandir($dir) ?: [] as $item) {
      if ($item === '.' || $item === '..') {
        continue;
      }
      $path = $dir . '/' . $item;
      if (is_dir($path) && !is_link($path)) {
        $this->removeDirectory($path);
      } else {
        @unlink($path);
      }
    }
    @rmdir($dir);
  }

  /**
   * Imprime la ayuda de subcomandos remote:*.
   */
  private function showUsage(): void
  {
    echo "\nUso: frame remote:<subcomando> [argumentos] [opciones]\n\n";
    echo "Subcomandos disponibles:\n";
    echo "  health                          Diagnóstico del servidor remoto (PHP, extensiones, disco, SQLite, GeoIP)\n";
    echo "  geoip [--file=ruta]             Descarga/sube GeoLite2-City.mmdb por fragmentos verificando SHA-256\n";
    echo "  db-info                         Lista tablas, filas y modo journal de SQLite en producción\n";
    echo "  db-pull [--out=ruta]            Descarga snapshot consistente (VACUUM INTO) hacia backups/\n";
    echo "  db-truncate [--tables=t1,t2]    Respalda y vacía tablas seleccionadas (reinicia AUTOINCREMENT a 1)\n";
    echo "  cache-clear                     Limpia Cache/Views/* e invalida OPcache en producción\n";
    echo "  logs [archivo] [lineas]         Lista logs o muestra últimas N líneas dentro de Logs/\n";
    echo "  logs-clear [archivo]            Vacía archivos dentro de Logs/ conservando subcarpetas\n";
    echo "  sync-receiver                   Regenera deploy.php local conservando DEPLOY_TOKEN\n\n";
    echo "Opciones globales de red:\n";
    echo "  --allow-insecure-http           Permite URL http:// únicamente en pruebas locales\n";
    echo "  --allow-insecure-ssl            Omite verificación de certificado TLS\n\n";
  }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
  $remoteCli = new RemoteCli();
  $remoteCli->run($_SERVER['argv'] ?? []);
}
