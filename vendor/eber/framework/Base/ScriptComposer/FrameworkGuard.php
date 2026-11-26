<?php

namespace Base\ScriptComposer;

/**
 * Guardia para evitar que comandos CLI destinados a proyectos derivados
 * creen la estructura /App dentro del repositorio del framework ('eber/framework').
 */
class FrameworkGuard
{
  /**
   * Determina si el directorio indicado corresponde al repositorio del framework.
   *
   * @param string|null $basePath Ruta base a inspeccionar (por defecto getcwd()).
   * @return bool
   */
  public static function isFrameworkRepo(?string $basePath = null): bool
  {
    $root = rtrim(str_replace('\\', '/', $basePath ?? (string)getcwd()), '/');
    $composerJsonPath = $root . '/composer.json';
    if (!is_file($composerJsonPath)) {
      return false;
    }

    $composerData = json_decode((string)@file_get_contents($composerJsonPath), true);
    return is_array($composerData) && (($composerData['name'] ?? '') === 'eber/framework');
  }

  /**
   * Aborta limpiamente (exit 0) cuando se intenta ejecutar un comando de proyecto
   * directamente dentro del repositorio del framework.
   *
   * @param string      $commandLabel Etiqueta legible del comando ejecutado.
   * @param string|null $basePath     Ruta base a inspeccionar (por defecto getcwd()).
   * @return void
   */
  public static function abortIfFrameworkRepo(string $commandLabel, ?string $basePath = null): void
  {
    if (self::isFrameworkRepo($basePath)) {
      echo "ℹ️  Repositorio del framework ('eber/framework') detectado.\n";
      echo "   {$commandLabel} solo debe ejecutarse en proyectos derivados (con carpeta /App).\n";
      echo "   Para pruebas dentro del framework, ejecuta el comando desde un directorio temporal aislado.\n";
      exit(0);
    }
  }
}
