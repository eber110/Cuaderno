<?php

/**
 * Suite de Pruebas Automatizadas — Eber Framework
 * 
 * Verifica las optimizaciones de arquitectura de las Fases 1, 2 y 3:
 * 1. Desacoplamiento de Builder y Conexion (Composición).
 * 2. ImgProcessModule independiente sin herencia de Builder.
 * 3. AnalyticsModule desacoplado con soporte genérico y retrocompatible.
 * 4. Zero-Config en SecurityModule para tablas SQL seguras.
 * 5. Control (viewClean, json, layouts y limpieza de deprecated).
 * 6. Session y AuthModule agnóstico de esquemas.
 * 7. SeoModule (personSchema, schemaJson).
 * 8. GeminiModule (cliente universal).
 * 9. GeoLocation (resolución híbrida).
 * 10. CLI MakeCli (generadores de código).
 * 11. Scaffolding en InitAppStructure y Resources/Segment.
 */

define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)));

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Base/ScriptComposer/MakeCli.php';
require_once __DIR__ . '/../Base/ScriptComposer/InitAppStructure.php';

class TestRunner
{
  private int $passed = 0;
  private int $failed = 0;
  private array $errors = [];

  public function runTest(string $name, callable $callback): void
  {
    echo "🧪 Probando: {$name}... ";
    try {
      $callback();
      echo "\033[32m[OK]\033[0m\n";
      $this->passed++;
    } catch (\Throwable $e) {
      echo "\033[31m[FALLÓ]\033[0m\n";
      echo "   ⚠️  Error: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine() . "\n";
      $this->failed++;
      $this->errors[] = ['test' => $name, 'error' => $e->getMessage()];
    }
  }

  public function assert(bool $condition, string $message = 'La aserción falló'): void
  {
    if (!$condition) {
      throw new \Exception($message);
    }
  }

  public function summary(): bool
  {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "📊 RESUMEN DE PRUEBAS DEL FRAMEWORK:\n";
    echo "   ✅ Exitosas: {$this->passed}\n";
    echo "   ❌ Fallidas: {$this->failed}\n";
    echo str_repeat('=', 60) . "\n";

    if ($this->failed > 0) {
      echo "\n❌ Pruebas con fallos:\n";
      foreach ($this->errors as $err) {
        echo " - {$err['test']}: {$err['error']}\n";
      }
      return false;
    }

    echo "\n✨ ¡Todas las pruebas del framework pasaron satisfactoriamente!\n";
    return true;
  }
}

$runner = new TestRunner();

// =========================================================================
// 1. BUILDER & CONEXION (COMPOSICIÓN)
// =========================================================================
$runner->runTest("Builder - Desacoplamiento de Conexion (Composición)", function () use ($runner) {
  $reflection = new \ReflectionClass(\Base\Builder\Builder::class);
  $runner->assert(
    $reflection->getParentClass() === false,
    "Builder no debe heredar de ninguna clase (no debe hacer 'extends Conexion')"
  );

  // Inyección de PDO en memoria para probar Builder
  $sqlitePdo = new \PDO('sqlite::memory:');
  $sqlitePdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
  $sqlitePdo->exec("CREATE TABLE test_users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, active INTEGER)");

  $builder = new \Base\Builder\Builder('test_users', $sqlitePdo);

  // Insert
  $id = $builder->insert(['name' => 'John Doe', 'email' => 'john@example.com', 'active' => 1]);
  $runner->assert($id == 1, "Insert debe retornar el ID generado");

  // Select con where y first
  $builder2 = new \Base\Builder\Builder('test_users', $sqlitePdo);
  $res = $builder2->where('email', 'john@example.com')->first();
  $row = $res[0] ?? $res;
  $runner->assert($row !== null && $row['name'] === 'John Doe', "first() debe retornar la fila insertada");

  // Count y exists
  $builder3 = new \Base\Builder\Builder('test_users', $sqlitePdo);
  $count = $builder3->where('active', 1)->count();
  $runner->assert($count === 1, "count() debe retornar 1");

  $builder4 = new \Base\Builder\Builder('test_users', $sqlitePdo);
  $exists = $builder4->where('name', 'John Doe')->exists();
  $runner->assert($exists === true, "exists() debe retornar true");

  // Update
  $builder5 = new \Base\Builder\Builder('test_users', $sqlitePdo);
  $builder5->update('id', 1, ['name' => 'Jane Doe']);

  $builder6 = new \Base\Builder\Builder('test_users', $sqlitePdo);
  $updRes = $builder6->where('id', 1)->first();
  $updatedRow = $updRes[0] ?? $updRes;
  $runner->assert($updatedRow['name'] === 'Jane Doe', "El nombre debe estar actualizado a Jane Doe");

  // get() fluido
  $builder7 = new \Base\Builder\Builder('test_users', $sqlitePdo);
  $rows = $builder7->select('*')->get();
  $runner->assert(is_array($rows) && count($rows) === 1, "get() debe retornar todos los registros");

  // Transacciones en Builder vía PDO inyectado
  $builder8 = new \Base\Builder\Builder('test_users', $sqlitePdo);
  $builder8->beginTransaction();
  $builder8->insert(['name' => 'Rollback User', 'email' => 'rb@example.com', 'active' => 0]);
  $builder8->rollback();

  $builder9 = new \Base\Builder\Builder('test_users', $sqlitePdo);
  $rbCount = $builder9->where('email', 'rb@example.com')->count();
  $runner->assert($rbCount === 0, "El usuario insertado dentro del rollback no debe existir");

  // Verificar que Conexion::setConnection permite a Builder usar la conexión global sin inyección directa
  \Core\Conexion::setConnection($sqlitePdo);
  $runner->assert(\Core\Conexion::getPdo() === $sqlitePdo, "Conexion::getPdo() debe retornar el PDO inyectado");

  $builderDefault = new \Base\Builder\Builder('test_users');
  $defaultCount = $builderDefault->where('name', 'Jane Doe')->count();
  $runner->assert($defaultCount === 1, "Builder instanciado sin PDO debe obtener la conexión global de Conexion::getPdo()");

  // Reset de conexión y validación de excepción amigable en lugar de Fatal Error on null
  \Core\Conexion::resetConnection();
  $runner->assert(\Core\Conexion::getLastError() === null, "resetConnection debe limpiar el último error");

  // Prueba con conexión nula para validar PDOException informativa
  $builderSinPdo = new class('test_users') extends \Base\Builder\Builder {
    public function pdo_conexion(): ?\PDO { return null; }
  };

  $exLanzada = false;
  try {
    $builderSinPdo->first();
  } catch (\PDOException $e) {
    $exLanzada = str_contains($e->getMessage(), 'No hay conexión activa');
  } catch (\Throwable $t) {
    $exLanzada = false;
  }
  $runner->assert($exLanzada === true, "Builder debe arrojar PDOException informativa si no hay conexión en lugar de un Fatal Error on null");

  // Restaurar conexión para siguientes pruebas
  \Core\Conexion::resetConnection();
});

// =========================================================================
// 2. IMGPROCESSMODULE (DESACOPLADO DE BUILDER)
// =========================================================================
$runner->runTest("ImgProcessModule - Desacoplado de Builder", function () use ($runner) {
  $reflection = new \ReflectionClass(\Base\Module\ImgProcessModule::class);
  $runner->assert(
    $reflection->getParentClass() === false,
    "ImgProcessModule no debe heredar de Builder"
  );

  $imgModule = new \Base\Module\ImgProcessModule();
  $runner->assert(is_object($imgModule), "ImgProcessModule debe instanciarse correctamente sin requerir conexión a BD");
  $runner->assert(method_exists($imgModule, 'record_img_disk'), "ImgProcessModule debe tener el método record_img_disk");
  $runner->assert(method_exists($imgModule, 'save_img_disk'), "ImgProcessModule debe tener el método save_img_disk");
  $runner->assert(method_exists($imgModule, 'createThumbnail'), "ImgProcessModule debe tener el método createThumbnail");
});

// =========================================================================
// 3. ANALYTICS MODULE (DESACOPLADO Y RETROCOMPATIBLE)
// =========================================================================
$runner->runTest("AnalyticsModule - SQLite desacoplado y retrocompatibilidad", function () use ($runner) {
  $tempDb = sys_get_temp_dir() . '/test_analytics_' . uniqid() . '.sqlite';
  $analytics = new \Base\Module\AnalyticsModule($tempDb);

  // Probar nuevo sistema de eventos genéricos
  $loggedEvent = $analytics->logEvent('page_view', 'post_100', ['referrer' => 'google.com']);
  $runner->assert($loggedEvent === true, "logEvent() debe registrar eventos correctamente");

  $eventSummary = $analytics->getEventSummary('page_view');
  $runner->assert(isset($eventSummary['total_events']) && $eventSummary['total_events'] >= 1, "getEventSummary() debe contabilizar el evento");

  // Probar métodos retrocompatibles usados por Cuaderno
  $loggedProfile = $analytics->logProfileView('profile_999', [
    'ip_address' => '127.0.0.1',
    'device_type' => 'Desktop',
    'browser' => 'Chrome',
    'country_code' => 'CL',
    'referrer' => 'Direct'
  ]);
  $runner->assert($loggedProfile === true, "logProfileView() debe funcionar para retrocompatibilidad con Cuaderno");

  $loggedClick = $analytics->logLinkClick('profile_999', 'link_123', ['country_code' => 'CL']);
  $runner->assert($loggedClick === true, "logLinkClick() debe funcionar para retrocompatibilidad con Cuaderno");

  $summary = $analytics->getProfileSummary('profile_999');
  $runner->assert(isset($summary['total_views']) && $summary['total_views'] >= 1, "getProfileSummary() debe retornar vistas");
  $runner->assert(isset($summary['total_clicks']) && $summary['total_clicks'] >= 1, "getProfileSummary() debe retornar clicks");

  if (file_exists($tempDb)) {
    @unlink($tempDb);
  }
});

// =========================================================================
// 4. SECURITY MODULE (ZERO-CONFIG SQL TABLE WHITELIST)
// =========================================================================
$runner->runTest("SecurityModule - Zero-Config de tablas SQL", function () use ($runner) {
  // Nombres válidos y seguros deben permitirse automáticamente
  $runner->assert(\Base\Module\SecurityModule::isAllowedTable('users') === true, "users debe ser permitida");
  $runner->assert(\Base\Module\SecurityModule::isAllowedTable('custom_ecommerce_invoices_2026') === true, "custom_ecommerce_invoices_2026 debe ser permitida");
  $runner->assert(\Base\Module\SecurityModule::validateTableName('products') === 'products', "validateTableName debe retornar el nombre normalizado");

  // Inyecciones SQL maliciosas deben rechazarse
  $runner->assert(\Base\Module\SecurityModule::isAllowedTable('users; DROP TABLE users') === false, "Inyección SQL con punto y coma debe ser rechazada");
  $runner->assert(\Base\Module\SecurityModule::isAllowedTable('users --') === false, "Inyección SQL con comentarios debe ser rechazada");
  $runner->assert(\Base\Module\SecurityModule::isAllowedTable('admin\' OR \'1\'=\'1') === false, "Inyección SQL con comillas debe ser rechazada");

  // addAllowedTable debe ejecutarse sin errores
  \Base\Module\SecurityModule::addAllowedTable('new_custom_table');
  $runner->assert(\Base\Module\SecurityModule::isAllowedTable('new_custom_table') === true, "addAllowedTable debe seguir funcionando");
});

// =========================================================================
// 5. SESSION & AUTHMODULE (AGNOSTIC AUTH)
// =========================================================================
$runner->runTest("Session & AuthModule - Autenticación desacoplada y hashing seguro", function () use ($runner) {
  // Password hashing y verificación
  $password = 'Secret123!';
  $hash = \Base\Module\AuthModule::hashPassword($password);
  $runner->assert(is_string($hash) && strlen($hash) > 20, "hashPassword debe generar un hash seguro");
  $runner->assert(\Base\Module\AuthModule::verifyPassword($password, $hash) === true, "verifyPassword debe validar la contraseña correcta");
  $runner->assert(\Base\Module\AuthModule::verifyPassword('wrong_password', $hash) === false, "verifyPassword debe rechazar contraseñas incorrectas");

  // Resolver de roles desacoplado en Session
  \Base\Module\Session::setRoleResolver(function ($user, $sessionData) {
    if (($sessionData['email'] ?? '') === 'admin@example.com') {
      return ['role_id' => 1, 'role_name' => 'superadmin'];
    }
    return ['role_id' => 2, 'role_name' => 'member'];
  });

  $adminRole = \Base\Module\Session::role(['email' => 'admin@example.com']);
  $runner->assert($adminRole['role_name'] === 'superadmin', "Resolver de roles personalizado debe devolver superadmin");

  $userRole = \Base\Module\Session::role(['email' => 'user@example.com']);
  $runner->assert($userRole['role_name'] === 'member', "Resolver de roles personalizado debe devolver member");

  // Fallback seguro sin resolver
  \Base\Module\Session::setRoleResolver(null);
  $fallbackRole = \Base\Module\Session::role();
  $runner->assert($fallbackRole['role_name'] === 'guest', "Sin resolver ni sesión, el rol por defecto debe ser guest");
});

// =========================================================================
// 6. CONTROL (VIEWCLEAN, JSON, REDIRECT & LAYOUTS)
// =========================================================================
$runner->runTest("Control - Métodos viewClean, json, redirect y layouts", function () use ($runner) {
  // Verificar existencia de métodos modernos
  $reflection = new \ReflectionClass(\Base\Control\Control::class);
  $runner->assert($reflection->hasMethod('viewClean'), "Control debe contar con viewClean()");
  $runner->assert($reflection->hasMethod('json'), "Control debe contar con json()");
  $runner->assert($reflection->hasMethod('redirect'), "Control debe contar con redirect()");

  // Crear un controlador concreto de prueba
  $controller = new class extends \Base\Control\Control {
    public function testViewClean(string $viewPath, array $data = []): string
    {
      return $this->viewClean($viewPath, $data);
    }
  };

  // Crear vista temporal para testear
  $tempViewDir = ROUTE_VIEW . 'TestDummy';
  if (!is_dir($tempViewDir)) {
    mkdir($tempViewDir, 0755, true);
  }
  $tempViewFile = $tempViewDir . '/dummy.php';
  file_put_contents($tempViewFile, '<?php echo "Hola " . ($name ?? "Mundo"); ?>');

  $cleanOutput = $controller->testViewClean('TestDummy.dummy', ['name' => 'Eber']);
  $runner->assert(trim($cleanOutput) === 'Hola Eber', "viewClean debe renderizar la vista sin doctype ni layout");

  // Limpiar archivo temporal
  @unlink($tempViewFile);
  @rmdir($tempViewDir);
});

// =========================================================================
// 7. SEOMODULE (SCHEMAS Y METAS)
// =========================================================================
$runner->runTest("SeoModule - Estructura JSON-LD y personSchema", function () use ($runner) {
  $personSchema = \Base\Module\SeoModule::personSchema([
    'name' => 'Juan Perez',
    'url' => 'https://juanperez.com',
    'jobTitle' => 'Arquitecto de Software',
    'knowsAbout' => ['PHP', 'Architecture']
  ]);

  $runner->assert(str_contains($personSchema, '<script type="application/ld+json">'), "personSchema debe envolver el script JSON-LD");
  $runner->assert(str_contains($personSchema, '"name": "Juan Perez"'), "personSchema debe incluir el nombre");
  $runner->assert(str_contains($personSchema, '"jobTitle": "Arquitecto de Software"'), "personSchema debe incluir jobTitle");
});

// =========================================================================
// 8. GEMINIMODULE (UNIVERSAL AI CLIENT)
// =========================================================================
$runner->runTest("GeminiModule - Cliente universal de IA", function () use ($runner) {
  $reflection = new \ReflectionClass(\Base\Module\GeminiModule::class);
  $runner->assert($reflection->hasMethod('generate'), "GeminiModule debe contar con método generate()");
  $runner->assert($reflection->hasMethod('prompt'), "GeminiModule debe contar con método estático prompt()");
  $runner->assert($reflection->hasMethod('generateSummary'), "GeminiModule debe conservar generateSummary() por retrocompatibilidad");
});

// =========================================================================
// 9. GEOLOCATION (RESOLVER HÍBRIDO)
// =========================================================================
$runner->runTest("GeoLocation - Servicio híbrido de resolución geográfica", function () use ($runner) {
  // Consulta de IP local/privada debe retornar datos por defecto seguros
  $result = \Base\Module\GeoLocation::resolve('127.0.0.1');
  $runner->assert(is_array($result), "resolve() debe retornar un array");
  $runner->assert(isset($result['country_code']), "El resultado debe contener country_code");
  $runner->assert(isset($result['city_name']), "El resultado debe contener city_name");
});

// =========================================================================
// 10. CLI MAKE GENERATORS
// =========================================================================
$runner->runTest("MakeCli - Generadores de código CLI", function () use ($runner) {
  $runner->assert(class_exists('MakeCli'), "MakeCli debe existir");

  // Probar generación de controlador temporal
  $dummyController = 'PruebaTest';
  $controllerPath = ROUTE_CONTROLLER . $dummyController . 'Controllers.php';

  \MakeCli::make('controller', $dummyController);
  $runner->assert(file_exists($controllerPath), "make:controller debe crear el archivo del controlador");
  $content = file_get_contents($controllerPath);
  $runner->assert(str_contains($content, "class {$dummyController}Controllers extends Control"), "El controlador generado debe extender Control");
  @unlink($controllerPath);

  // Probar generación de modelo temporal
  $dummyModel = 'PruebaTest';
  $modelPath = ROUTE_MODEL . $dummyModel . 'Models.php';

  \MakeCli::make('model', $dummyModel);
  $runner->assert(file_exists($modelPath), "make:model debe crear el archivo del modelo");
  $modelContent = file_get_contents($modelPath);
  $runner->assert(str_contains($modelContent, "class {$dummyModel}Models extends Builder"), "El modelo generado debe extender Builder");
  @unlink($modelPath);
});

// =========================================================================
// 11. SCAFFOLDING EN INITAPPSTRUCTURE & RESOURCES/SEGMENT
// =========================================================================
$runner->runTest("InitAppStructure & Resources/Segment - Plantillas canónicas", function () use ($runner) {
  $resourcesDir = ROOT_PATH . '/Resources/Segment';
  $runner->assert(is_dir($resourcesDir), "Resources/Segment debe existir en el framework");
  $runner->assert(file_exists($resourcesDir . '/Template/header.php'), "Resources/Segment/Template/header.php debe existir");
  $runner->assert(file_exists($resourcesDir . '/Template/footer.php'), "Resources/Segment/Template/footer.php debe existir");
  $runner->assert(file_exists($resourcesDir . '/Template/pagination.php'), "Resources/Segment/Template/pagination.php debe existir");
  $runner->assert(file_exists($resourcesDir . '/Form/login.php'), "Resources/Segment/Form/login.php debe existir");
  $runner->assert(file_exists($resourcesDir . '/Form/register.php'), "Resources/Segment/Form/register.php debe existir");
  $runner->assert(file_exists($resourcesDir . '/Menu/navbar.php'), "Resources/Segment/Menu/navbar.php debe existir");

  // Probar ejecución segura de InitAppStructure en el repo
  $init = new \InitAppStructure();
  ob_start();
  $result = $init->create();
  ob_end_clean();
  $runner->assert($result === true, "InitAppStructure::create() debe ejecutarse sin errores");
});

// =========================================================================
// FINALIZAR Y SALIDA
// =========================================================================
$success = $runner->summary();
exit($success ? 0 : 1);
