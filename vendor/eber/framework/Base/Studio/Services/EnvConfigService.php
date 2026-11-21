<?php

namespace Base\Studio\Services;

/**
 * Servicio de lectura, edición segura y pruebas de perfiles de configuración (.env y perfil producción local).
 */
class EnvConfigService
{
  public const PRODUCTION_DRAFT_REL = 'App/Config/.studio.production.local.json';

  private static function resolveBasePath(?string $basePath): string
  {
    $raw = $basePath ?: (defined('ROOT_PATH') ? ROOT_PATH : getcwd());
    return rtrim(str_replace('\\', '/', (string)$raw), '/');
  }

  /**
   * Parsea un archivo .env preservando claves y valores sin ejecutar código.
   *
   * @return array<string, string>
   */
  public static function readEnvFile(string $envPath): array
  {
    if (!is_file($envPath)) {
      return [];
    }

    $lines = @file($envPath, FILE_IGNORE_NEW_LINES) ?: [];
    $vars = [];

    foreach ($lines as $line) {
      $trimmed = trim($line);
      if ($trimmed === '' || str_starts_with($trimmed, '#')) {
        continue;
      }
      $eqPos = strpos($trimmed, '=');
      if ($eqPos === false) {
        continue;
      }
      $key = trim(substr($trimmed, 0, $eqPos));
      if ($key === '' || !preg_match('/^[A-Z0-9_]+$/i', $key)) {
        continue;
      }
      $val = trim(substr($trimmed, $eqPos + 1));
      if (strlen($val) >= 2) {
        $first = $val[0];
        $last = $val[strlen($val) - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
          $val = substr($val, 1, -1);
          if ($first === '"') {
            $val = str_replace(['\"', '\\\\'], ['"', '\\'], $val);
          }
        }
      }
      $vars[$key] = $val;
    }

    return $vars;
  }

  /**
   * Escribe valores en .env preservando comentarios, orden y variables personalizadas existentes.
   */
  public static function writeEnvFile(string $envPath, array $values): bool
  {
    $dir = dirname($envPath);
    if (!is_dir($dir)) {
      @mkdir($dir, 0755, true);
    }

    $existingLines = is_file($envPath)
      ? (@file($envPath, FILE_IGNORE_NEW_LINES) ?: [])
      : [];

    $formatVal = static function (mixed $val): string {
      if (is_bool($val)) {
        return $val ? 'true' : 'false';
      }
      if (is_array($val)) {
        $val = implode(',', $val);
      }
      $str = str_replace(["\r", "\n", "\0"], '', (string)$val);
      $escaped = str_replace(['\\', '"'], ['\\\\', '\"'], $str);
      return '"' . $escaped . '"';
    };

    $written = [];
    $outLines = [];

    foreach ($existingLines as $line) {
      $trimmed = trim($line);
      if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
        $outLines[] = $line;
        continue;
      }
      $eqPos = strpos($trimmed, '=');
      $key = trim(substr($trimmed, 0, $eqPos));
      if (array_key_exists($key, $values)) {
        $outLines[] = $key . '=' . $formatVal($values[$key]);
        $written[$key] = true;
      } else {
        $outLines[] = $line;
      }
    }

    $pending = [];
    foreach ($values as $key => $val) {
      if (!isset($written[$key])) {
        $pending[] = $key . '=' . $formatVal($val);
      }
    }

    if (!empty($pending)) {
      if (!empty($outLines) && end($outLines) !== '') {
        $outLines[] = '';
      }
      $outLines[] = '# ==================== CONFIGURADO EN FRAME STUDIO ====================';
      foreach ($pending as $pLine) {
        $outLines[] = $pLine;
      }
    }

    $finalContent = implode("\n", $outLines) . "\n";
    return @file_put_contents($envPath, $finalContent, LOCK_EX) !== false;
  }

  /**
   * Lee todos los campos del esquema para un perfil ('local' o 'production').
   *
   * @return array<string, mixed>
   */
  public static function readProfile(string $profile = 'local', ?string $basePath = null): array
  {
    $root = self::resolveBasePath($basePath);
    $fields = ConfigSchema::getFields();
    $envPath = is_file($root . '/.env') ? $root . '/.env' : $root . '/.env.example';
    $envVars = self::readEnvFile($envPath);

    $draft = [];
    $draftPath = $root . '/' . self::PRODUCTION_DRAFT_REL;
    if ($profile === 'production' && is_file($draftPath)) {
      $decoded = json_decode((string)@file_get_contents($draftPath), true);
      if (is_array($decoded)) {
        $draft = $decoded;
      }
    }

    $result = [];
    foreach ($fields as $spec) {
      $key = $spec['key'];
      $raw = $spec['default'];

      if ($profile === 'production' && array_key_exists($key, $draft)) {
        $raw = $draft[$key];
      } elseif (array_key_exists($key, $envVars)) {
        $raw = $envVars[$key];
      } elseif ($spec['target'] === 'const' && defined($key)) {
        $raw = constant($key);
      }

      if ($spec['type'] === 'bool') {
        $result[$key] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
      } elseif ($spec['type'] === 'int') {
        $result[$key] = is_numeric($raw) ? (int)$raw : (int)$spec['default'];
      } elseif ($spec['type'] === 'list') {
        if (is_array($raw)) {
          $result[$key] = array_values($raw);
        } else {
          $result[$key] = array_values(array_filter(array_map('trim', explode(',', (string)$raw))));
        }
      } else {
        $result[$key] = (string)$raw;
      }
    }

    if ($profile === 'production') {
      $result['APP_ENV'] = 'production';
    }

    return $result;
  }

  /**
   * Valida y guarda un perfil ('local' en .env / App/Config o 'production' en borrador local gitignored).
   */
  public static function saveProfile(string $profile, array $rawValues, ?string $basePath = null): array
  {
    $root = self::resolveBasePath($basePath);
    $profile = ($profile === 'production') ? 'production' : 'local';

    $validation = ConfigSchema::validate($rawValues, $profile);
    if (!empty($validation['errors'])) {
      return [
        'success' => false,
        'message' => 'Existen errores de validación en los campos.',
        'errors' => $validation['errors'],
      ];
    }

    $clean = $validation['values'];

    if ($profile === 'local') {
      $fieldsMap = ConfigSchema::getFieldsMap();
      $envToSave = [];
      foreach ($clean as $k => $v) {
        if (($fieldsMap[$k]['target'] ?? 'env') === 'env') {
          $envToSave[$k] = $v;
        }
      }

      $envPath = $root . '/.env';
      if (!is_file($envPath) && is_file($root . '/.env.example')) {
        @copy($root . '/.env.example', $envPath);
      }

      if (!self::writeEnvFile($envPath, $envToSave)) {
        return ['success' => false, 'message' => 'No se pudo escribir el archivo .env local.'];
      }

      return [
        'success' => true,
        'profile' => 'local',
        'message' => 'Perfil local (.env) actualizado correctamente.',
        'blockedKeys' => $validation['blocked'],
      ];
    }

    // Perfil producción: guardar en App/Config/.studio.production.local.json (gitignored y excluido de deploy)
    $draftPath = $root . '/' . self::PRODUCTION_DRAFT_REL;
    $configDir = dirname($draftPath);
    if (!is_dir($configDir)) {
      @mkdir($configDir, 0755, true);
    }

    ConfigCompilerService::ensureSensitiveGitignores($root);

    $existing = is_file($draftPath)
      ? (json_decode((string)@file_get_contents($draftPath), true) ?: [])
      : [];

    $merged = array_merge($existing, $clean);
    $merged['APP_ENV'] = 'production';
    unset($merged['DEPLOY_URL']);

    $json = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    if (@file_put_contents($draftPath, $json, LOCK_EX) === false) {
      return ['success' => false, 'message' => 'No se pudo guardar el borrador de producción local.'];
    }

    return [
      'success' => true,
      'profile' => 'production',
      'message' => 'Perfil de producción guardado localmente (listo para compilar al Vault).',
      'blockedKeys' => $validation['blocked'],
    ];
  }

  /**
   * Genera claves criptográficas aleatorias seguras.
   */
  public static function generateCryptoKey(string $type): string
  {
    if ($type === 'token') {
      return bin2hex(random_bytes(16));
    }
    return bin2hex(random_bytes(32));
  }

  /**
   * Prueba una conexión PDO con timeout corto sin almacenar ni exponer la contraseña.
   */
  public static function testDatabaseConnection(array $dbConfig, ?string $basePath = null): array
  {
    $root = self::resolveBasePath($basePath);
    $driver = strtolower(trim((string)($dbConfig['DB_CONNECTION'] ?? 'mysql')));
    $host = trim((string)($dbConfig['DB_HOST'] ?? '127.0.0.1'));
    $port = trim((string)($dbConfig['DB_PORT'] ?? ''));
    $dbName = trim((string)($dbConfig['DB_DATABASE'] ?? ''));
    $user = (string)($dbConfig['DB_USERNAME'] ?? 'root');
    $pass = (string)($dbConfig['DB_PASSWORD'] ?? '');
    $charset = trim((string)($dbConfig['DB_CHARSET'] ?? 'utf8mb4'));

    try {
      if ($driver === 'sqlite') {
        if ($dbName === '') {
          return ['success' => false, 'message' => 'Indica la ruta del archivo SQLite (ej: Database/app.sqlite).'];
        }
        $sqlitePath = str_starts_with($dbName, '/') || preg_match('/^[a-zA-Z]:[\\\\\/]/', $dbName)
          ? $dbName
          : $root . '/' . ltrim(str_replace('\\', '/', $dbName), '/');
        $dsn = 'sqlite:' . $sqlitePath;
        $pdo = new \PDO($dsn);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->query('SELECT 1');
        return ['success' => true, 'message' => 'Conexión SQLite exitosa.'];
      }

      if ($dbName === '') {
        return ['success' => false, 'message' => 'El nombre de la base de datos no puede estar vacío.'];
      }

      if ($driver === 'pgsql') {
        $portStr = $port !== '' ? ';port=' . (int)$port : ';port=5432';
        $dsn = "pgsql:host={$host}{$portStr};dbname={$dbName}";
      } else {
        $portStr = $port !== '' ? ';port=' . (int)$port : ';port=3306';
        $dsn = "mysql:host={$host}{$portStr};dbname={$dbName};charset={$charset}";
      }

      $pdo = new \PDO($dsn, $user, $pass, [
        \PDO::ATTR_TIMEOUT => 3,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      ]);
      $pdo->query('SELECT 1');

      return ['success' => true, 'message' => "Conexión {$driver} verificada correctamente."];
    } catch (\Throwable $e) {
      return ['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()];
    }
  }
}
