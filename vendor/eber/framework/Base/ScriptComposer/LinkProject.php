<?php

/**
 * Eber Framework - Vinculador de Repositorio Local (Frame Link)
 * 
 * Enlaza o actualiza automáticamente la ruta del framework en el archivo composer.json
 * del proyecto actual, útil si el framework se mueve de carpeta o disco.
 * 
 * Uso:
 *   frame link
 */

if (php_sapi_name() !== 'cli') {
  die("Este script solo puede ejecutarse desde la interfaz de comandos (CLI).\n");
}

class LinkProject
{
  private string $workingPath;
  private string $frameworkPath;

  public function __construct()
  {
    $this->workingPath   = str_replace('\\', '/', getcwd());
    $this->frameworkPath = str_replace('\\', '/', dirname(__DIR__, 2));
  }

  public function run(): void
  {
    echo "\n======================================================\n";
    echo "🔗 FRAME — VINCULADOR DE PROYECTO CON EL FRAMEWORK\n";
    echo "======================================================\n\n";

    if ($this->workingPath === $this->frameworkPath) {
      echo "ℹ️  Estás en la carpeta raíz del framework mismo. No es necesario vincular.\n\n";
      return;
    }

    $composerFile = $this->workingPath . '/composer.json';
    if (!file_exists($composerFile)) {
      echo "❌ Error: No se encontró 'composer.json' en este directorio.\n";
      echo "   Directorio: {$this->workingPath}\n";
      exit(1);
    }

    $content = (string)file_get_contents($composerFile);
    $data = json_decode($content, true);

    if (!is_array($data)) {
      echo "❌ Error: El archivo 'composer.json' contiene errores de sintaxis JSON.\n";
      exit(1);
    }

    $updated = false;
    if (isset($data['repositories']) && is_array($data['repositories'])) {
      foreach ($data['repositories'] as &$repo) {
        if (($repo['type'] ?? '') === 'path') {
          $oldUrl = $repo['url'] ?? '';
          $repo['url'] = $this->frameworkPath;
          $updated = true;
          echo "🔄 Repositorio local actualizado en composer.json:\n";
          echo "   • Anterior: {$oldUrl}\n";
          echo "   • Nuevo:    {$this->frameworkPath}\n\n";
        }
      }
    }

    if (!$updated) {
      $data['repositories'][] = [
        'type'    => 'path',
        'url'     => $this->frameworkPath,
        'options' => [
          'symlink' => false
        ]
      ];
      echo "➕ Repositorio local del framework añadido a composer.json:\n";
      echo "   • Ubicación: {$this->frameworkPath}\n\n";
    }

    file_put_contents(
      $composerFile,
      json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
    );

    echo "✅ ¡Proyecto vinculado con éxito!\n";
    echo "   Ahora puedes ejecutar 'composer update eber/framework' sin problemas de ruta.\n\n";
  }
}

// Iniciar ejecución
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
  $linker = new LinkProject();
  $linker->run();
}
