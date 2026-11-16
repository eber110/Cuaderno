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

    // 1. Normalizar y resolver el directorio confinado dentro de ROOT_PATH/Logs
    $fullPath = self::resolveFilePath($config['dir'], true);
    if ($fullPath === null) {
      error_log("LogModule [Security]: Ruta fuera de Logs/ rechazada: " . (string)$config['dir']);
      return false;
    }

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

    $fullPath = self::resolveFilePath($config['dir'], true);
    if ($fullPath === null || !is_dir($fullPath)) {
      return [];
    }

    $sanitizedName = self::sanitizeFilename($config['name']);
    $pattern = rtrim($fullPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $sanitizedName . '*.json';
    return glob($pattern) ?: [];
  }

  /**
   * Lee un archivo de log (formato JSON Lines) y devuelve sus registros decodificados.
   * Valida estrictamente que la ruta pertenezca a ROOT_PATH/Logs.
   * 
   * @param string $filePath Ruta del archivo de log.
   * @return array Lista de registros decodificados.
   */
  public static function readLogLines(string $filePath): array
  {
    $resolvedPath = self::resolveFilePath($filePath);
    if ($resolvedPath === null || !file_exists($resolvedPath) || !is_readable($resolvedPath)) {
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
   * Elimina un archivo de log confinado en ROOT_PATH/Logs.
   * 
   * @param string $filePath Ruta del archivo a eliminar.
   * @return bool True si se eliminó correctamente, false en caso contrario.
   */
  public static function deleteLog(string $filePath): bool
  {
    $resolvedPath = self::resolveFilePath($filePath);
    if ($resolvedPath !== null && file_exists($resolvedPath)) {
      return @unlink($resolvedPath);
    }
    return false;
  }

  /**
   * Renombra o mueve un archivo de log dentro de ROOT_PATH/Logs.
   * 
   * @param string $filePath Ruta del archivo de origen.
   * @param string $newPathOrName Nueva ruta o nuevo nombre del archivo.
   * @return bool True si se renombró correctamente, false en caso contrario.
   */
  public static function renameLog(string $filePath, string $newPathOrName): bool
  {
    $resolvedSource = self::resolveFilePath($filePath);
    if ($resolvedSource === null || !file_exists($resolvedSource)) {
      return false;
    }

    if (str_contains($newPathOrName, '/') || str_contains($newPathOrName, '\\')) {
      $resolvedTarget = self::resolveFilePath($newPathOrName);
      if ($resolvedTarget === null) {
        return false;
      }
    } else {
      $cleanName = basename(trim(str_replace(["\0", '..'], '', $newPathOrName)));
      if ($cleanName === '' || $cleanName === '.' || $cleanName === '..') {
        return false;
      }
      $dir = dirname($resolvedSource);
      $resolvedTarget = $dir . DIRECTORY_SEPARATOR . $cleanName;
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
   * Resuelve una ruta y verifica confinamiento estricto dentro de ROOT_PATH/Logs
   * previniendo ataques de Path Traversal y lectura/eliminación arbitraria.
   * 
   * @param string $path Ruta a resolver.
   * @param bool $allowNewDirectory Si permite directorios aún inexistentes bajo Logs/.
   * @return string|null Ruta absoluta validada o null si intenta salir de Logs/.
   */
  private static function resolveFilePath(string $path, bool $allowNewDirectory = false): ?string
  {
    $clean = trim($path);
    if ($clean === '' || str_contains($clean, "\0") || preg_match('/^[a-zA-Z0-9+.-]+:\/\//', $clean)) {
      return null;
    }

    $normalized = str_replace(['\\', '/'], '/', $clean);
    $segments = explode('/', $normalized);
    foreach ($segments as $seg) {
      if ($seg === '..') {
        return null;
      }
    }

    $root = defined('ROOT_PATH') ? ROOT_PATH : getcwd();
    $logsBase = rtrim(str_replace('\\', '/', (string)$root), '/') . '/Logs';
    if (!is_dir($logsBase)) {
      @mkdir($logsBase, 0755, true);
    }
    $realLogsBase = realpath($logsBase);
    if ($realLogsBase === false) {
      return null;
    }
    $realLogsNorm = rtrim(str_replace('\\', '/', $realLogsBase), '/');

    $isAbsolute = (DIRECTORY_SEPARATOR === '\\')
      ? (bool)preg_match('/^[a-zA-Z]:\//', $normalized)
      : str_starts_with($normalized, '/');

    // Interpretar "/Logs/foo" o "/foo" relativo a ROOT_PATH cuando no apunta ya a $realLogsNorm
    if (str_starts_with($normalized, $realLogsNorm . '/') || $normalized === $realLogsNorm) {
      $candidate = $normalized;
    } elseif ($isAbsolute) {
      // En Unix una ruta /Logs/... se resuelve respecto a ROOT_PATH si no existe como ruta bajo Logs reales
      $trimmed = ltrim($normalized, '/');
      if (str_starts_with($trimmed, 'Logs/') || $trimmed === 'Logs') {
        $candidate = rtrim(str_replace('\\', '/', (string)$root), '/') . '/' . $trimmed;
      } else {
        return null;
      }
    } else {
      $trimmed = ltrim($normalized, '/');
      if (str_starts_with($trimmed, 'Logs/') || $trimmed === 'Logs') {
        $candidate = rtrim(str_replace('\\', '/', (string)$root), '/') . '/' . $trimmed;
      } else {
        $candidate = $realLogsNorm . '/' . $trimmed;
      }
    }

    // Verificar confinamiento por realpath()
    $candidateNative = str_replace('/', DIRECTORY_SEPARATOR, $candidate);
    if (file_exists($candidateNative)) {
      $realCandidate = realpath($candidateNative);
      if ($realCandidate === false) {
        return null;
      }
      $realCandNorm = str_replace('\\', '/', $realCandidate);
      return ($realCandNorm === $realLogsNorm || str_starts_with($realCandNorm, $realLogsNorm . '/'))
        ? $realCandidate
        : null;
    }

    // Para archivos o subdirectorios nuevos: verificar el ancestro existente más cercano
    $probe = $candidateNative;
    $pendingSegments = [];
    while ($probe !== '' && !file_exists($probe)) {
      $parent = dirname($probe);
      if ($parent === $probe) {
        return null;
      }
      array_unshift($pendingSegments, basename($probe));
      $probe = $parent;
    }

    $realAncestor = realpath($probe);
    if ($realAncestor === false) {
      return null;
    }
    $realAncNorm = rtrim(str_replace('\\', '/', $realAncestor), '/');
    if ($realAncNorm !== $realLogsNorm && !str_starts_with($realAncNorm, $realLogsNorm . '/')) {
      return null;
    }

    return $candidateNative;
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
