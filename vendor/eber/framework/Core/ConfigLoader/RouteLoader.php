<?php

namespace Core\ConfigLoader;

/**
 * RouteLoader
 * 
 * Clase encargada de cargar dinámicamente todos los archivos de rutas
 * dentro de un directorio especificado de forma recursiva.
 * 
 * Características:
 * - Carga recursiva de archivos PHP en subdirectorios
 * - Permite definir prioridades de carga (qué archivos cargar primero)
 * - Permite excluir archivos específicos
 * - Reutilizable en cualquier proyecto
 * 
 * Uso básico:
 *   RouteLoader::load(__DIR__ . '/../../route');
 * 
 * Uso con prioridades:
 *   RouteLoader::load(__DIR__ . '/../../route', ['route.php', 'web.php']);
 * 
 * Uso con exclusiones:
 *   RouteLoader::load(__DIR__ . '/../../route', ['route.php'], ['test.php']);
 */
class RouteLoader
{
  /**
   * Carga todos los archivos de rutas de un directorio.
   *
   * @param string $directory Ruta absoluta al directorio de rutas
   * @param array $priorityFiles Archivos a cargar primero (en orden)
   * @param array $excludedFiles Archivos a excluir de la carga
   * @return void
   */
  public static function load(
    string $directory,
    array $priorityFiles = ['route.php', 'web.php'],
    array $excludedFiles = []
  ): void {
    if (!is_dir($directory)) {
      return;
    }

    self::loadDirectory($directory, $priorityFiles, $excludedFiles);
  }

  /**
   * Carga recursivamente todos los archivos PHP de un directorio.
   *
   * @param string $directory Directorio a escanear
   * @param array $priorityFiles Archivos prioritarios
   * @param array $excludedFiles Archivos a excluir
   * @return void
   */
  private static function loadDirectory(
    string $directory,
    array $priorityFiles,
    array $excludedFiles
  ): void {
    // Obtener todos los archivos PHP del directorio
    $files = glob($directory . '/*.php');

    if ($files === false) {
      return;
    }

    // Ordenar archivos: prioritarios primero, luego el resto alfabéticamente
    $orderedFiles = self::orderFiles($directory, $files, $priorityFiles, $excludedFiles);

    // Cargar todos los archivos en orden
    foreach ($orderedFiles as $file) {
      require_once $file;
    }

    // Procesar subdirectorios recursivamente
    $subdirectories = glob($directory . '/*', GLOB_ONLYDIR);

    if ($subdirectories === false) {
      return;
    }

    foreach ($subdirectories as $subdirectory) {
      // En subdirectorios no aplicamos prioridades especiales
      self::loadDirectory($subdirectory, [], $excludedFiles);
    }
  }

  /**
   * Ordena los archivos según prioridad.
   *
   * @param string $directory Directorio base
   * @param array $files Archivos encontrados
   * @param array $priorityFiles Archivos prioritarios
   * @param array $excludedFiles Archivos a excluir
   * @return array Archivos ordenados
   */
  private static function orderFiles(
    string $directory,
    array $files,
    array $priorityFiles,
    array $excludedFiles
  ): array {
    $orderedFiles = [];

    // Primero agregar archivos prioritarios en orden
    foreach ($priorityFiles as $priorityFile) {
      $fullPath = $directory . '/' . $priorityFile;
      if (in_array($fullPath, $files) && !in_array($priorityFile, $excludedFiles)) {
        $orderedFiles[] = $fullPath;
      }
    }

    // Luego agregar el resto alfabéticamente
    sort($files);
    foreach ($files as $file) {
      $filename = basename($file);

      // Saltar archivos excluidos y los ya agregados
      if (in_array($filename, $excludedFiles) || in_array($file, $orderedFiles)) {
        continue;
      }

      $orderedFiles[] = $file;
    }

    return $orderedFiles;
  }
}
