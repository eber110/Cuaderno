<?php

namespace Base\Module;

/**
 * LogModule - Módulo premium para la gestión y creación de logs en formato JSON.
 * 
 * Permite guardar registros estructurados en archivos JSON de forma dinámica
 * soportando nombres basados en fechas, marcas de tiempo UNIX, o archivos únicos.
 * Ideal para depuración, auditoría, o sistemas de colas de procesamiento.
 * 
 * @example
 * // Uso Estático
 * LogModule::simpleLog([
 *   "dir" => "/Logs/error",
 *   "name" => "log de errores",
 *   "timestamp" => true,
 *   "duration" => "daily",
 *   "content" => ["message" => "Error de conexión", "code" => 500]
 * ]);
 * 
 * // Uso Instanciado
 * (new LogModule)->simpleLog($config);
 */
class LogModule
{
  /**
   * Registra un log simple en formato JSON basado en la configuración entregada.
   * 
   * @param array $config {
   *   @var string $dir Directorio relativo o absoluto donde se guardará el archivo.
   *   @var string $name Nombre base del archivo de log.
   *   @var bool $timestamp Si es true, añade la marca de tiempo UNIX al nombre del archivo.
   *   @var string $duration Si es 'daily', añade la fecha actual (dd-mm-yyyy) al nombre del archivo.
   *   @var array|object $content Contenido del log que será codificado a JSON.
   * }
   * @return bool True si el log se escribió con éxito, false en caso contrario.
   */
  public static function simpleLog(array $config = []): bool
  {
    if (empty($config['dir']) || empty($config['name'])) {
      return false;
    }

    // 1. Normalizar y resolver el directorio
    $fullPath = self::resolveFilePath($config['dir']);

    // 2. Asegurar que el directorio existe
    if (!is_dir($fullPath)) {
      if (!@mkdir($fullPath, 0755, true)) {
        error_log("LogModule: No se pudo crear el directorio de logs: {$fullPath}");
        return false;
      }
    }

    // 3. Sanitizar el nombre del archivo
    $sanitizedName = self::sanitizeFilename($config['name']);

    // 4. Determinar el nombre final del archivo según las reglas de precedencia
    if (!empty($config['timestamp'])) {
      $filename = $sanitizedName . '_' . time() . '.json';
    } elseif (($config['duration'] ?? '') === 'daily') {
      $filename = $sanitizedName . '_' . date('d-m-Y') . '.json';
    } else {
      $filename = $sanitizedName . '.json';
    }

    // 5. Preparar la línea de contenido en formato JSON Lines
    $content = $config['content'] ?? [];
    $jsonLine = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

    // 6. Escribir y asegurar bloqueo de archivo en concurrencia
    $filePath = rtrim($fullPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    return @file_put_contents($filePath, $jsonLine, FILE_APPEND | LOCK_EX) !== false;
  }

  /**
   * Obtiene todos los archivos de log en el directorio especificado que coincidan con el nombre base.
   * Útil para recuperar logs acumulados en sistemas de colas.
   * 
   * @param array $config Configuración con 'dir' y 'name'.
   * @return array Lista de rutas completas de archivos de log encontrados.
   */
  public static function getLogFiles(array $config): array
  {
    if (empty($config['dir']) || empty($config['name'])) {
      return [];
    }

    $fullPath = self::resolveFilePath($config['dir']);

    if (!is_dir($fullPath)) {
      return [];
    }

    $sanitizedName = self::sanitizeFilename($config['name']);
    $pattern = rtrim($fullPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $sanitizedName . '*.json';
    return glob($pattern) ?: [];
  }

  /**
   * Lee un archivo de log (formato JSON Lines) y devuelve sus registros decodificados.
   * Soporta tanto rutas completas absolutas como rutas relativas respecto a ROOT_PATH.
   * 
   * @param string $filePath Ruta del archivo de log.
   * @return array Lista de registros decodificados.
   */
  public static function readLogLines(string $filePath): array
  {
    $resolvedPath = self::resolveFilePath($filePath);

    if (!file_exists($resolvedPath) || !is_readable($resolvedPath)) {
      return [];
    }

    $lines = file($resolvedPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
      return [];
    }

    $records = [];
    foreach ($lines as $line) {
      $decoded = json_decode($line, true);
      if ($decoded !== null) {
        $records[] = $decoded;
      }
    }

    return $records;
  }

  /**
   * Elimina un archivo de log.
   * Soporta tanto rutas completas absolutas como rutas relativas respecto a ROOT_PATH.
   * 
   * @param string $filePath Ruta del archivo a eliminar.
   * @return bool True si se eliminó correctamente, false en caso contrario.
   */
  public static function deleteLog(string $filePath): bool
  {
    $resolvedPath = self::resolveFilePath($filePath);

    if (file_exists($resolvedPath)) {
      return @unlink($resolvedPath);
    }
    return false;
  }

  /**
   * Renombra o mueve un archivo de log.
   * Soporta tanto rutas completas absolutas como rutas relativas respecto a ROOT_PATH.
   * Si $newPathOrName es solo un nombre de archivo (sin diagonales), se mantendrá en el mismo directorio de origen.
   * 
   * @param string $filePath Ruta del archivo de origen.
   * @param string $newPathOrName Nueva ruta o nuevo nombre del archivo.
   * @return bool True si se renombró correctamente, false en caso contrario.
   */
  public static function renameLog(string $filePath, string $newPathOrName): bool
  {
    $resolvedSource = self::resolveFilePath($filePath);

    if (!file_exists($resolvedSource)) {
      return false;
    }

    if (str_contains($newPathOrName, '/') || str_contains($newPathOrName, '\\')) {
      $resolvedTarget = self::resolveFilePath($newPathOrName);
    } else {
      $dir = dirname($resolvedSource);
      $resolvedTarget = $dir . DIRECTORY_SEPARATOR . $newPathOrName;
    }

    $targetDir = dirname($resolvedTarget);
    if (!is_dir($targetDir)) {
      if (!@mkdir($targetDir, 0755, true)) {
        return false;
      }
    }

    return @rename($resolvedSource, $resolvedTarget);
  }

  /**
   * Resuelve una ruta (absoluta o relativa) a una ruta absoluta del sistema de archivos.
   * Si es relativa (empieza con / o no), se resuelve respecto a la constante ROOT_PATH.
   * 
   * @param string $path Ruta a resolver.
   * @return string Ruta absoluta normalizada.
   */
  private static function resolveFilePath(string $path): string
  {
    $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($path));
    $isAbsolute = false;

    if (DIRECTORY_SEPARATOR === '\\') {
      if (preg_match('/^[a-zA-Z]:/', $path)) {
        $isAbsolute = true;
      }
    } else {
      if (str_starts_with($path, '/')) {
        $isAbsolute = true;
      }
    }

    if ($isAbsolute) {
      return $path;
    }

    return rtrim(ROOT_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
  }

  /**
   * Sanitiza el nombre de un archivo de log para evitar vulnerabilidades de ruta o caracteres extraños.
   * 
   * @param string $name Nombre a sanitizar.
   * @return string Nombre sanitizado.
   */
  private static function sanitizeFilename(string $name): string
  {
    $name = mb_strtolower(trim($name), 'UTF-8');
    $name = str_replace(' ', '_', $name);
    $name = preg_replace('/[^a-z0-9_\-]/', '', $name);
    return $name ?: 'log';
  }
}
