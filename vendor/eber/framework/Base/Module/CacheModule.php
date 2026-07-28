<?php

namespace Base\Module;

/**
 * Módulo de caché con soporte para archivo y memoria.
 * 
 * Proporciona un sistema de caché con múltiples capas:
 * - Caché en memoria (runtime): Más rápido, solo dura el request actual
 * - Caché en archivo: Persistente entre requests
 * 
 * @example
 * // Guardar en caché
 * CacheModule::set('user_data', $userData, 3600); // 1 hora
 * 
 * // Obtener de caché (busca primero en memoria, luego en archivo)
 * $data = CacheModule::get('user_data');
 * 
 * // Solo caché en memoria (ultra rápido, solo este request)
 * CacheModule::setMemory('temp_data', $data);
 * 
 * // Eliminar entrada
 * CacheModule::forget('user_data');
 */
class CacheModule
{
  /**
   * Ruta del directorio de caché de archivos.
   */
  private static ?string $cachePath = null;

  /**
   * Caché en memoria (runtime).
   * Acceso más rápido, pero solo persiste durante el request.
   */
  private static array $memoryCache = [];

  /**
   * Estadísticas de uso del caché.
   */
  private static array $stats = [
    'hits' => 0,
    'misses' => 0,
    'memory_hits' => 0,
    'file_hits' => 0
  ];

  // =========================================================================
  // CACHÉ EN MEMORIA (RUNTIME)
  // =========================================================================

  /**
   * Obtiene un valor del caché en memoria.
   * 
   * @param string $key Clave del caché.
   * @param mixed $default Valor por defecto si no existe.
   * @return mixed Valor cacheado o el valor por defecto.
   */
  public static function getMemory(string $key, mixed $default = null): mixed
  {
    if (!isset(self::$memoryCache[$key])) {
      return $default;
    }

    $item = self::$memoryCache[$key];

    // Verificar expiración
    if ($item['expires'] !== 0 && $item['expires'] <= time()) {
      unset(self::$memoryCache[$key]);
      return $default;
    }

    self::$stats['memory_hits']++;
    return $item['data'];
  }

  /**
   * Guarda un valor en el caché en memoria.
   * 
   * @param string $key Clave del caché.
   * @param mixed $value Valor a cachear.
   * @param int $ttl Tiempo de vida en segundos (0 = sin expiración).
   */
  public static function setMemory(string $key, mixed $value, int $ttl = 0): void
  {
    self::$memoryCache[$key] = [
      'expires' => $ttl > 0 ? time() + $ttl : 0,
      'data' => $value
    ];
  }

  /**
   * Verifica si existe una clave en el caché en memoria.
   * 
   * @param string $key Clave del caché.
   * @return bool True si existe y es válido.
   */
  public static function hasMemory(string $key): bool
  {
    return self::getMemory($key) !== null;
  }

  /**
   * Elimina una entrada del caché en memoria.
   * 
   * @param string $key Clave del caché.
   */
  public static function forgetMemory(string $key): void
  {
    unset(self::$memoryCache[$key]);
  }

  /**
   * Limpia todo el caché en memoria.
   */
  public static function flushMemory(): void
  {
    self::$memoryCache = [];
  }

  // =========================================================================
  // CACHÉ EN ARCHIVO (PERSISTENTE)
  // =========================================================================

  /**
   * Obtiene la ruta del directorio de caché.
   * 
   * @return string Ruta del directorio de caché.
   */
  private static function getCachePath(): string
  {
    if (self::$cachePath === null) {
      self::$cachePath = ROOT_PATH . '/Cache/';

      if (!is_dir(self::$cachePath)) {
        mkdir(self::$cachePath, 0755, true);
      }
    }

    return self::$cachePath;
  }

  /**
   * Genera el nombre de archivo para una clave.
   * 
   * @param string $key Clave del caché.
   * @return string Ruta completa del archivo.
   */
  private static function getFilename(string $key): string
  {
    return self::getCachePath() . md5($key) . '.cache';
  }

  /**
   * Obtiene un valor del caché (busca en memoria primero, luego en archivo).
   * 
   * @param string $key Clave del caché.
   * @param mixed $default Valor por defecto si no existe o expiró.
   * @return mixed Valor cacheado o el valor por defecto.
   */
  public static function get(string $key, mixed $default = null): mixed
  {
    // Verificar caché deshabilitado
    if (defined('USE_CACHE') && !USE_CACHE) {
      self::$stats['misses']++;
      return $default;
    }

    // Primero buscar en memoria (más rápido)
    $memoryValue = self::getMemory($key);
    if ($memoryValue !== null) {
      self::$stats['hits']++;
      return $memoryValue;
    }

    // Luego buscar en archivo
    $filename = self::getFilename($key);

    if (!file_exists($filename)) {
      self::$stats['misses']++;
      return $default;
    }

    $content = @unserialize(file_get_contents($filename));

    if ($content === false) {
      @unlink($filename);
      self::$stats['misses']++;
      return $default;
    }

    // Verificar expiración
    if ($content['expires'] !== 0 && $content['expires'] <= time()) {
      @unlink($filename);
      self::$stats['misses']++;
      return $default;
    }

    // Guardar en memoria para acceso más rápido en el mismo request
    self::setMemory($key, $content['data'], $content['expires'] - time());

    self::$stats['hits']++;
    self::$stats['file_hits']++;
    return $content['data'];
  }

  /**
   * Guarda un valor en el caché (memoria y archivo).
   * 
   * @param string $key Clave del caché.
   * @param mixed $value Valor a cachear.
   * @param int $ttl Tiempo de vida en segundos (0 = sin expiración).
   * @return bool True si se guardó correctamente.
   */
  public static function set(string $key, mixed $value, int $ttl = 3600): bool
  {
    // Siempre guardar en memoria
    self::setMemory($key, $value, $ttl);

    // Verificar caché deshabilitado para archivos
    if (defined('USE_CACHE') && !USE_CACHE) {
      return true;
    }

    $content = [
      'expires' => $ttl > 0 ? time() + $ttl : 0,
      'data' => $value
    ];

    return file_put_contents(
      self::getFilename($key),
      serialize($content),
      LOCK_EX
    ) !== false;
  }

  /**
   * Verifica si existe una clave en el caché y no ha expirado.
   * 
   * @param string $key Clave del caché.
   * @return bool True si existe y es válido.
   */
  public static function has(string $key): bool
  {
    return self::get($key) !== null;
  }

  /**
   * Elimina una entrada del caché (memoria y archivo).
   * 
   * @param string $key Clave del caché.
   * @return bool True si se eliminó de archivo correctamente.
   */
  public static function forget(string $key): bool
  {
    // Eliminar de memoria
    self::forgetMemory($key);

    // Eliminar de archivo
    $filename = self::getFilename($key);

    if (file_exists($filename)) {
      return @unlink($filename);
    }

    return false;
  }

  /**
   * Limpia todo el caché (memoria y archivos).
   * 
   * @return int Número de archivos eliminados.
   */
  public static function flush(): int
  {
    // Limpiar memoria
    self::flushMemory();

    // Limpiar archivos
    $path = self::getCachePath();
    $count = 0;

    $files = glob($path . '*.cache');

    foreach ($files as $file) {
      if (@unlink($file)) {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Obtiene o crea un valor en caché.
   * 
   * @param string $key Clave del caché.
   * @param callable $callback Función para generar el valor.
   * @param int $ttl Tiempo de vida en segundos.
   * @return mixed Valor del caché o generado.
   */
  public static function remember(string $key, callable $callback, int $ttl = 3600): mixed
  {
    $value = self::get($key);

    if ($value !== null) {
      return $value;
    }

    $value = $callback();
    self::set($key, $value, $ttl);

    return $value;
  }

  /**
   * Obtiene las estadísticas del caché.
   * 
   * @return array Estadísticas de uso.
   */
  public static function getStats(): array
  {
    $total = self::$stats['hits'] + self::$stats['misses'];
    $hitRate = $total > 0 ? round((self::$stats['hits'] / $total) * 100, 2) : 0;

    return array_merge(self::$stats, [
      'total_requests' => $total,
      'hit_rate' => $hitRate . '%',
      'memory_cache_size' => count(self::$memoryCache)
    ]);
  }

  /**
   * Limpia entradas expiradas del caché de archivos.
   * 
   * @return int Número de archivos expirados eliminados.
   */
  public static function cleanup(): int
  {
    $path = self::getCachePath();
    $count = 0;

    $files = glob($path . '*.cache');

    foreach ($files as $file) {
      $content = @unserialize(file_get_contents($file));

      if ($content === false) {
        @unlink($file);
        $count++;
        continue;
      }

      if ($content['expires'] !== 0 && $content['expires'] <= time()) {
        @unlink($file);
        $count++;
      }
    }

    return $count;
  }
}
