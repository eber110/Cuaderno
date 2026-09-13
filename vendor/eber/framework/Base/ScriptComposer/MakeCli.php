<?php

/**
 * Generador interactivo y CLI para controladores, modelos, componentes y middlewares.
 * 
 * Uso vía Composer o CLI:
 * php Base/ScriptComposer/MakeCli.php controller NombreController
 * php Base/ScriptComposer/MakeCli.php model NombreModel
 * php Base/ScriptComposer/MakeCli.php component NombreComponent
 * php Base/ScriptComposer/MakeCli.php middleware NombreMiddleware
 */

class MakeCli
{
  private string $basePath;

  public function __construct()
  {
    $this->basePath = getcwd();
  }

  public static function make(string $type, string $name): void
  {
    $cli = new self();
    $cli->run(['cli', $type, $name]);
  }

  public function run(array $args): void
  {
    $type = strtolower($args[1] ?? '');
    $name = $args[2] ?? null;

    if (empty($type)) {
      echo "🛠️  Generadores CLI disponibles:\n";
      echo "   - controller [Nombre]   -> Crea un Controlador en App/Controllers/\n";
      echo "   - model [Nombre]        -> Crea un Modelo en App/Models/\n";
      echo "   - component [Nombre]    -> Crea un Componente en App/Components/\n";
      echo "   - middleware [Nombre]   -> Crea un Middleware en App/Middleware/\n\n";
      echo "Ingrese el tipo a generar: ";
      $type = strtolower(trim((string)fgets(STDIN)));
    }

    if (empty($name)) {
      echo "Ingrese el nombre de la clase (ej: User): ";
      $name = trim((string)fgets(STDIN));
    }

    if (empty($name)) {
      echo "❌ Error: El nombre no puede estar vacío.\n";
      exit(1);
    }

    // Normalizar nombre a PascalCase
    $className = ucfirst(trim($name));

    switch ($type) {
      case 'controller':
        $this->makeController($className);
        break;
      case 'model':
        $this->makeModel($className);
        break;
      case 'component':
        $this->makeComponent($className);
        break;
      case 'middleware':
        $this->makeMiddleware($className);
        break;
      default:
        echo "❌ Error: Tipo desconocido '{$type}'. Use controller, model, component o middleware.\n";
        exit(1);
    }
  }

  private function makeController(string $name): void
  {
    if (!str_ends_with($name, 'Controllers') && !str_ends_with($name, 'Controller')) {
      $className = $name . 'Controllers';
    } else {
      $className = $name;
    }

    $dir = $this->basePath . '/App/Controllers';
    $this->ensureDir($dir);
    $filePath = $dir . '/' . $className . '.php';

    if (file_exists($filePath)) {
      echo "⚠️  El archivo ya existe: {$filePath}\n";
      return;
    }

    $content = <<<PHP
<?php

namespace App\Controllers;

use Base\Control\Control;

class {$className} extends Control
{
  /**
   * Acción principal del controlador.
   */
  public function index()
  {
    \$data = [
      'title' => '{$name}'
    ];

    return \$this->view('{$name}.index', \$data);
  }
}

PHP;

    file_put_contents($filePath, $content);
    echo "✅ Controlador creado con éxito: App/Controllers/{$className}.php\n";
  }

  private function makeModel(string $name): void
  {
    if (!str_ends_with($name, 'Models') && !str_ends_with($name, 'Model')) {
      $className = $name . 'Models';
    } else {
      $className = $name;
    }

    $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace(['Model', 'Models'], '', $className))) . 's';

    $dir = $this->basePath . '/App/Models';
    $this->ensureDir($dir);
    $filePath = $dir . '/' . $className . '.php';

    if (file_exists($filePath)) {
      echo "⚠️  El archivo ya existe: {$filePath}\n";
      return;
    }

    $content = <<<PHP
<?php

namespace App\Models;

use Base\Builder\Builder;

class {$className} extends Builder
{
  protected \$table = "{$tableName}";

  /**
   * Obtiene todos los registros activos del modelo.
   */
  public function getAll(): array
  {
    return \$this->select('*')->order('id', 'DESC')->get();
  }
}

PHP;

    file_put_contents($filePath, $content);
    echo "✅ Modelo creado con éxito: App/Models/{$className}.php (tabla: '{$tableName}')\n";
  }

  private function makeComponent(string $name): void
  {
    $className = $name;
    $dir = $this->basePath . '/App/Components';
    $this->ensureDir($dir);
    $filePath = $dir . '/' . $className . '.php';

    if (file_exists($filePath)) {
      echo "⚠️  El archivo ya existe: {$filePath}\n";
      return;
    }

    $content = <<<PHP
<?php

namespace App\Components;

class {$className}
{
  /**
   * Renderiza el componente HTML.
   *
   * @param array \$props Propiedades pasadas al componente
   * @return string
   */
  public static function render(array \$props = []): string
  {
    ob_start();
    ?>
    <div class="component-{$name}">
      <!-- Componente {$name} -->
    </div>
    <?php
    return (string)ob_get_clean();
  }
}

PHP;

    file_put_contents($filePath, $content);
    echo "✅ Componente creado con éxito: App/Components/{$className}.php\n";
  }

  private function makeMiddleware(string $name): void
  {
    if (!str_ends_with($name, 'Middleware')) {
      $className = $name . 'Middleware';
    } else {
      $className = $name;
    }

    $dir = $this->basePath . '/App/Middleware';
    $this->ensureDir($dir);
    $filePath = $dir . '/' . $className . '.php';

    if (file_exists($filePath)) {
      echo "⚠️  El archivo ya existe: {$filePath}\n";
      return;
    }

    $content = <<<PHP
<?php

namespace App\Middleware;

class {$className}
{
  /**
   * Maneja una solicitud entrante.
   *
   * @param callable \$next Callback hacia el siguiente middleware o controlador
   * @return mixed
   */
  public function handle(callable \$next)
  {
    // Lógica previa del middleware (ej: verificar permisos o sesión)

    return \$next();
  }
}

PHP;

    file_put_contents($filePath, $content);
    echo "✅ Middleware creado con éxito: App/Middleware/{$className}.php\n";
  }

  private function ensureDir(string $dir): void
  {
    if (!is_dir($dir)) {
      @mkdir($dir, 0755, true);
    }
  }
}

// Ejecución directa por CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
  $cli = new MakeCli();
  $cli->run($_SERVER['argv'] ?? []);
}
