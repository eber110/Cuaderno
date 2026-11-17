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
 * 11. Inicialización de estructura en InitAppStructure y recursos nativos.
 */

define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)));

if (!defined('ENVIRONMENT')) {
  $_ENV['APP_ENV'] = 'testing';
  $_SERVER['APP_ENV'] = 'testing';
}

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
  $testPdo = new \PDO("sqlite:" . $tempDb);
  $testPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
  \Base\Module\AnalyticsModule::setPdo($testPdo);

  // Probar nuevo sistema de eventos genéricos
  $loggedEvent = \Base\Module\AnalyticsModule::logEvent('page_view', 'post_100', ['referrer' => 'google.com']);
  $runner->assert($loggedEvent === true, "logEvent() debe registrar eventos correctamente");

  $eventSummary = \Base\Module\AnalyticsModule::getEventSummary('page_view');
  $runner->assert(isset($eventSummary['total_events']) && $eventSummary['total_events'] >= 1, "getEventSummary() debe contabilizar el evento");

  // Probar métodos retrocompatibles de analíticas de perfiles
  $loggedProfile = \Base\Module\AnalyticsModule::logProfileView('profile_999', [
    'ip_address' => '127.0.0.1',
    'device_type' => 'Desktop',
    'browser' => 'Chrome',
    'country_code' => 'CL',
    'referrer' => 'Direct'
  ]);
  $runner->assert($loggedProfile === true, "logProfileView() debe funcionar por retrocompatibilidad");

  $loggedClick = \Base\Module\AnalyticsModule::logLinkClick('profile_999', 'link_123', ['country_code' => 'CL']);
  $runner->assert($loggedClick === true, "logLinkClick() debe funcionar por retrocompatibilidad");

  $summary = \Base\Module\AnalyticsModule::getProfileSummary('profile_999');
  $runner->assert(isset($summary['total_views']) && $summary['total_views'] >= 1, "getProfileSummary() debe retornar vistas");
  $runner->assert(isset($summary['total_clicks']) && $summary['total_clicks'] >= 1, "getProfileSummary() debe retornar clicks");

  // Reset y eliminación de archivo temporal
  \Base\Module\AnalyticsModule::setPdo(null);
  $testPdo = null;
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

  // Limpiar archivo temporal y carpetas generadas para no dejar /App en el framework
  @unlink($tempViewFile);
  @rmdir($tempViewDir);
  if (defined('ROUTE_VIEW') && is_dir(ROUTE_VIEW)) {
    @rmdir(ROUTE_VIEW);
  }
  if (is_dir(ROOT_PATH . '/App')) {
    @rmdir(ROOT_PATH . '/App');
  }
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

  // OpenGraph tamaños recomendados por defecto (1200x630)
  $ogDefault = \Base\Module\SeoModule::openGraph(['image' => 'https://example.com/test.jpg']);
  $runner->assert(str_contains($ogDefault, '<meta property="og:image:width" content="1200" />'), "openGraph por defecto debe tener ancho 1200");
  $runner->assert(str_contains($ogDefault, '<meta property="og:image:height" content="630" />'), "openGraph por defecto debe tener alto 630");

  // OpenGraph respetando tamaños personalizados
  $ogCustom = \Base\Module\SeoModule::openGraph([
    'image' => 'https://example.com/test.jpg',
    'image_width' => 800,
    'image_height' => 450
  ]);
  $runner->assert(str_contains($ogCustom, '<meta property="og:image:width" content="800" />'), "openGraph personalizado debe respetar ancho 800");
  $runner->assert(str_contains($ogCustom, '<meta property="og:image:height" content="450" />'), "openGraph personalizado debe respetar alto 450");
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

  // Probar en un sandbox temporal aislado para no generar /App en el framework
  $sandbox = str_replace('\\', '/', sys_get_temp_dir() . '/frame_make_sandbox_' . uniqid());
  @mkdir($sandbox, 0755, true);

  // Probar generación de controlador temporal
  $dummyController = 'PruebaTest';
  $controllerPath = $sandbox . '/App/Controllers/' . $dummyController . 'Controllers.php';

  \MakeCli::make('controller', $dummyController, $sandbox);
  $runner->assert(file_exists($controllerPath), "make:controller debe crear el archivo del controlador en sandbox");
  $content = file_get_contents($controllerPath);
  $runner->assert(str_contains($content, "class {$dummyController}Controllers extends Control"), "El controlador generado debe extender Control");

  // Probar generación de modelo temporal
  $dummyModel = 'PruebaTest';
  $modelPath = $sandbox . '/App/Models/' . $dummyModel . 'Models.php';

  \MakeCli::make('model', $dummyModel, $sandbox);
  $runner->assert(file_exists($modelPath), "make:model debe crear el archivo del modelo en sandbox");
  $modelContent = file_get_contents($modelPath);
  $runner->assert(str_contains($modelContent, "class {$dummyModel}Models extends Builder"), "El modelo generado debe extender Builder");

  // Limpiar sandbox temporal
  $cleanSandbox = function (string $dirPath) use (&$cleanSandbox): void {
    if (!is_dir($dirPath)) return;
    foreach (scandir($dirPath) ?: [] as $item) {
      if ($item === '.' || $item === '..') continue;
      $target = $dirPath . '/' . $item;
      is_dir($target) ? $cleanSandbox($target) : @unlink($target);
    }
    @rmdir($dirPath);
  };
  $cleanSandbox($sandbox);
});

// =========================================================================
// 11. INITAPPSTRUCTURE & RECURSOS NATIVOS
// =========================================================================
$runner->runTest("InitAppStructure - Reconstrucción completa de /App desde /Resources", function () use ($runner) {
  $chartsDir = ROOT_PATH . '/Resources/Library/Charts';
  $runner->assert(is_dir($chartsDir), "Resources/Library/Charts debe existir en el framework");
  $runner->assert(file_exists($chartsDir . '/charts.js'), "Resources/Library/Charts/charts.js debe existir");
  $runner->assert(file_exists($chartsDir . '/charts.css'), "Resources/Library/Charts/charts.css debe existir");
  $runner->assert(file_exists(ROOT_PATH . '/Resources/Config/config.php'), "Resources/Config/config.php debe existir");

  // 1. Probar que en el repositorio del framework no se genera /App
  $fwInit = new \InitAppStructure();
  ob_start();
  $fwResult = $fwInit->create();
  ob_end_clean();
  $runner->assert($fwResult === true, "InitAppStructure::create() en el repo del framework debe retornar true sin errores");
  $runner->assert(!is_dir(ROOT_PATH . '/App'), "InitAppStructure no debe crear /App en el repositorio del framework");

  // 2. Probar reconstrucción completa de /App en un sandbox temporal de proyecto derivado
  $sandbox = str_replace('\\', '/', sys_get_temp_dir() . '/frame_init_sandbox_' . uniqid());
  @mkdir($sandbox, 0755, true);

  $init = new \InitAppStructure($sandbox);
  ob_start();
  $result = $init->create();
  ob_end_clean();
  $runner->assert($result === true, "InitAppStructure::create() debe ejecutarse sin errores en sandbox");

  // Verificar directorios vacíos de Segment y archivos base copiados desde Resources
  $runner->assert(is_dir($sandbox . '/App/Segment/Form'), "App/Segment/Form debe crearse");
  $runner->assert(is_dir($sandbox . '/App/Segment/Menu'), "App/Segment/Menu debe crearse");
  $runner->assert(is_dir($sandbox . '/App/Segment/Template'), "App/Segment/Template debe crearse");
  $runner->assert(file_exists($sandbox . '/App/Config/config.php'), "App/Config/config.php debe copiarse desde Resources");
  $runner->assert(file_exists($sandbox . '/App/Config/CookieConfiguration.php'), "App/Config/CookieConfiguration.php debe copiarse desde Resources");
  $runner->assert(file_exists($sandbox . '/App/Middleware/MiddlewareInterface/MiddlewareInterface.php'), "MiddlewareInterface.php debe copiarse desde Resources");
  $runner->assert(file_exists($sandbox . '/App/Public/Css/_configTheme.css'), "_configTheme.css debe copiarse desde Resources");
  $runner->assert(file_exists($sandbox . '/App/Route/web.php'), "App/Route/web.php debe copiarse desde Resources");

  // Limpiar sandbox temporal
  $cleanSandbox = function (string $dirPath) use (&$cleanSandbox): void {
    if (!is_dir($dirPath)) return;
    foreach (scandir($dirPath) ?: [] as $item) {
      if ($item === '.' || $item === '..') continue;
      $target = $dirPath . '/' . $item;
      is_dir($target) ? $cleanSandbox($target) : @unlink($target);
    }
    @rmdir($dirPath);
  };
  $cleanSandbox($sandbox);
});

// =========================================================================
// 12. JIT CSS - CONTAINERRULE
// =========================================================================
$runner->runTest("Jit CSS - ContainerRule (anchos responsivos 90%, 96%, 95%)", function () use ($runner) {
  $rule = new \Base\LibraryCssJit\ContainerRule();

  $containerCss = $rule->processWord('container');
  $runner->assert(str_contains($containerCss, '.container { max-width: 90%;'), "El contenedor base .container debe tener max-width: 90%");

  $deskCss = $rule->processWord('container-desk');
  $runner->assert(str_contains($deskCss, '.container-desk { max-width: 90%;') && str_contains($deskCss, '@media screen and (min-width: 993px)'), "container-desk debe tener max-width: 90% con media query desk");

  $midCss = $rule->processWord('container-mid');
  $runner->assert(str_contains($midCss, '.container-mid { max-width: 96%;') && str_contains($midCss, '@media screen and (min-width: 577px) and (max-width: 992px)'), "container-mid debe tener max-width: 96% con media query mid");

  $smlCss = $rule->processWord('container-sml');
  $runner->assert(str_contains($smlCss, '.container-sml { max-width: 95%;') && str_contains($smlCss, '@media screen and (max-width: 576px)'), "container-sml debe tener max-width: 95% con media query sml");
});

// =========================================================================
// 13. JIT CSS - BORDERRULE
// =========================================================================
$runner->runTest("Jit CSS - BorderRule (bordes .border1..N, .border-b-1..N y direccionales ligados a _configTheme.css)", function () use ($runner) {
  $rule = new \Base\LibraryCssJit\BorderRule();

  $b1 = $rule->processWord('border1');
  $runner->assert(str_contains($b1, '.border1 { border: 1px solid var(--back-color); }'), "border1 debe usar var(--back-color)");

  $b2 = $rule->processWord('border2');
  $runner->assert(str_contains($b2, '.border2 { border: 1px solid var(--back-color2); }'), "border2 debe usar var(--back-color2)");

  $b6 = $rule->processWord('border6');
  $runner->assert(str_contains($b6, '.border6 { border: 1px solid var(--back-color6); }'), "border6 debe usar var(--back-color6)");

  $b8 = $rule->processWord('border8');
  $runner->assert(str_contains($b8, '.border8 { border: 1px solid var(--back-color8); }'), "border8 debe usar var(--back-color8)");

  $bb1 = $rule->processWord('border-b-1');
  $runner->assert(str_contains($bb1, '.border-b-1 { border-bottom: 1px solid var(--back-color); }'), "border-b-1 debe usar border-bottom con var(--back-color)");

  $bb3 = $rule->processWord('border-b-3');
  $runner->assert(str_contains($bb3, '.border-b-3 { border-bottom: 1px solid var(--back-color3); }'), "border-b-3 debe usar border-bottom con var(--back-color3)");

  $bt2 = $rule->processWord('border-t-2');
  $runner->assert(str_contains($bt2, '.border-t-2 { border-top: 1px solid var(--back-color2); }'), "border-t-2 debe usar border-top");

  $bbSml = $rule->processWord('border-b-1-sml');
  $runner->assert(str_contains($bbSml, '@media screen and (max-width: 576px)') && str_contains($bbSml, 'border-bottom: 1px solid var(--back-color);'), "border-b-1-sml debe incluir media query móvil");

  $bw2 = $rule->processWord('border-w2');
  $runner->assert(str_contains($bw2, '.border-w2 { border-width: 2px !important; }'), "border-w2 debe definir border-width: 2px");
});

// =========================================================================
// 14. JIT CSS - TEXTSTYLERULE
// =========================================================================
$runner->runTest("Jit CSS - TextStyleRule (stroke, gradientes multidireccionales, sombras y efectos)", function () use ($runner) {
  $rule = new \Base\LibraryCssJit\TextStyleRule();

  // 1. Text Stroke sin fondo
  $stroke1 = $rule->processWord('text-stroke1');
  $runner->assert(str_contains($stroke1, '-webkit-text-stroke: 1px var(--back-color);') && str_contains($stroke1, 'color: transparent;'), "text-stroke1 debe tener borde de color y color transparente");

  $strokeW2 = $rule->processWord('text-stroke1-w2');
  $runner->assert(str_contains($strokeW2, '-webkit-text-stroke: 2px var(--back-color);'), "text-stroke1-w2 debe permitir ancho de trazo");

  // 1b. Text Stroke con relleno y orden de pintura optimizado (paint-order)
  $strokeFill = $rule->processWord('text-stroke-fill1-w2');
  $runner->assert(str_contains($strokeFill, '-webkit-text-stroke: 2px var(--back-color);') && str_contains($strokeFill, 'paint-order: stroke fill;'), "text-stroke-fill1-w2 debe incluir paint-order: stroke fill");

  // 1c. Text Outline unificado
  $outline = $rule->processWord('text-outline1-w2');
  $runner->assert(str_contains($outline, 'text-shadow:') && str_contains($outline, 'var(--back-color)'), "text-outline1-w2 debe generar sombra de contorno exterior");

  // 2. Text Gradient
  $gradR = $rule->processWord('text-gradient-r-1-2');
  $runner->assert(str_contains($gradR, 'linear-gradient(to right, var(--back-color), var(--back-color2))') && str_contains($gradR, '-webkit-background-clip: text;'), "text-gradient-r-1-2 debe ser degradado de izquierda a derecha clip text");

  $gradCenter = $rule->processWord('text-gradient-center-1-3');
  $runner->assert(str_contains($gradCenter, 'radial-gradient(circle, var(--back-color), var(--back-color3))'), "text-gradient-center debe ser gradiente radial");

  $gradB = $rule->processWord('text-gradient-b-1');
  $runner->assert(str_contains($gradB, 'linear-gradient(to bottom, var(--back-color),'), "text-gradient-b-1 debe calcular degradado vertical");

  // 3. Sombras de texto (opacidad negra y colores temáticos)
  $shadowOp = $rule->processWord('text-shadow-op40');
  $runner->assert(str_contains($shadowOp, 'rgba(0, 0, 0, 0.4)'), "text-shadow-op40 debe tener opacidad 0.4");

  $shadowColor = $rule->processWord('text-shadow2-lg');
  $runner->assert(str_contains($shadowColor, 'var(--back-color2)'), "text-shadow2-lg debe usar var(--back-color2)");

  // 4. Efectos sugeridos (Glow, 3D, Shimmer, Blur)
  $glow = $rule->processWord('text-glow1');
  $runner->assert(str_contains($glow, '0 0 5px var(--back-color)'), "text-glow1 debe generar efecto neón");

  $d3 = $rule->processWord('text-3d-1');
  $runner->assert(str_contains($d3, 'color-mix(in srgb, var(--back-color)'), "text-3d-1 debe generar sombreado 3D temático");

  $shimmer = $rule->processWord('text-shimmer2');
  $runner->assert(str_contains($shimmer, 'linear-gradient(90deg, var(--back-color2)'), "text-shimmer2 debe generar gradiente metálico");

  $blur = $rule->processWord('text-blur');
  $runner->assert(str_contains($blur, 'filter: blur(4px)'), "text-blur debe desenfocar texto");

  // 5. Dirección, espejo y rotación de texto (con propiedades CSS independientes para convivir con animaciones)
  $mirror = $rule->processWord('text-mirror');
  $runner->assert(str_contains($mirror, 'scale: -1 1;'), "text-mirror debe aplicar propiedad CSS scale: -1 1");

  $mirrorY = $rule->processWord('text-mirror-y');
  $runner->assert(str_contains($mirrorY, 'scale: 1 -1;'), "text-mirror-y debe aplicar scale: 1 -1");

  $inverted = $rule->processWord('text-inverted');
  $runner->assert(str_contains($inverted, 'rotate: 180deg;'), "text-inverted debe aplicar rotate: 180deg");
  $runner->assert(str_contains($inverted, '--text-rotate: 180deg;'), "text-inverted debe definir variable --text-rotate: 180deg");

  $rot90 = $rule->processWord('text-rotate-90');
  $runner->assert(str_contains($rot90, 'rotate: 90deg;'), "text-rotate-90 debe aplicar rotate: 90deg");
  $runner->assert(str_contains($rot90, '--text-rotate: 90deg;'), "text-rotate-90 debe definir variable --text-rotate: 90deg");

  $rot180 = $rule->processWord('text-rotate-180');
  $runner->assert(str_contains($rot180, 'rotate: 180deg;'), "text-rotate-180 debe aplicar rotate: 180deg");

  $rotN90 = $rule->processWord('text-rotate-n90');
  $runner->assert(str_contains($rotN90, 'rotate: -90deg;'), "text-rotate-n90 debe aplicar propiedad CSS independiente rotate: -90deg");
  $runner->assert(str_contains($rotN90, '--text-rotate: -90deg;'), "text-rotate-n90 debe definir variable --text-rotate: -90deg");

  $normal = $rule->processWord('text-normal');
  $runner->assert(str_contains($normal, 'rotate: 0deg !important;'), "text-normal debe resetear rotate");
  $runner->assert(str_contains($normal, 'scale: 1 1 !important;'), "text-normal debe resetear scale");

  $vertical = $rule->processWord('text-vertical');
  $runner->assert(str_contains($vertical, 'writing-mode: vertical-rl;'), "text-vertical debe activar modo de escritura vertical-rl");

  $upright = $rule->processWord('text-upright');
  $runner->assert(str_contains($upright, 'text-orientation: upright;'), "text-upright debe apilar caracteres verticalmente de pie");
});

// =========================================================================
// 15. JIT CSS - PROVISIONSRULE (W-AUTO, W-FIT, H-AUTO, H-FIT)
// =========================================================================
$runner->runTest("Jit CSS - ProvisionsRule (w-auto, w-fit, h-auto, h-fit y responsive)", function () use ($runner) {
  $rule = new \Base\LibraryCssJit\ProvisionsRule();

  $wAuto = $rule->processWord('w-auto');
  $runner->assert(str_contains($wAuto, 'width: auto !important;'), "w-auto debe aplicar width: auto !important");

  $wFit = $rule->processWord('w-fit');
  $runner->assert(str_contains($wFit, 'width: fit-content !important;'), "w-fit debe aplicar width: fit-content !important");

  $wFitDesk = $rule->processWord('w-fit-desk');
  $runner->assert(str_contains($wFitDesk, '@media screen and (min-width: 993px)') && str_contains($wFitDesk, 'width: fit-content !important;'), "w-fit-desk debe envolverse en media query desk");

  $hAuto = $rule->processWord('h-auto');
  $runner->assert(str_contains($hAuto, 'height: auto !important;'), "h-auto debe aplicar height: auto !important");

  $hFit = $rule->processWord('h-fit');
  $runner->assert(str_contains($hFit, 'height: fit-content !important;'), "h-fit debe aplicar height: fit-content !important");

  $hFitMid = $rule->processWord('h-fit-mid');
  $runner->assert(str_contains($hFitMid, '@media screen and (min-width: 577px)') && str_contains($hFitMid, 'height: fit-content !important;'), "h-fit-mid debe envolverse en media query mid");
});

// =========================================================================
// 16. THEME ENGINE & THEMEMODULE (FASE 1)
// =========================================================================
$runner->runTest("Theme Engine - ThemeModule (esquema JSON, colores, HSL y compilación CSS)", function () use ($runner) {
  // 1. Carga y normalización de esquema
  $defaultConfig = \Base\Module\ThemeModule::getDefaultConfig();
  $runner->assert(isset($defaultConfig['layout']['font']), "Configuración default debe incluir layout.font");
  $runner->assert(count($defaultConfig['light']['colors']) === 3, "Paleta clara debe tener 3 colores por defecto");
  $runner->assert($defaultConfig['dark']['auto'] === true, "dark.auto debe ser true por defecto");

  $templatePath = ROOT_PATH . '/Resources/Config/theme.json';
  $runner->assert(file_exists($templatePath), "Resources/Config/theme.json debe existir como plantilla");
  $loaded = \Base\Module\ThemeModule::loadConfig($templatePath);
  $runner->assert(!empty($loaded['light']['body']), "loadConfig debe cargar light.body");
  $runner->assert(isset($loaded['status']['hoverSuccess']), "loadConfig debe garantizar hoverSuccess");

  // 2. Conversiones HSL y RGB
  $testHex = '#FF4400';
  [$r, $g, $b] = \Base\Module\ThemeModule::hexToRgb($testHex);
  $runner->assert($r === 255 && $g === 68 && $b === 0, "hexToRgb debe descomponer #FF4400 correctamente");
  [$h, $s, $l] = \Base\Module\ThemeModule::rgbToHsl($r, $g, $b);
  $runner->assert($h >= 15 && $h <= 17, "rgbToHsl debe calcular un tono ~16 grados para naranja");
  [$backR, $backG, $backB] = \Base\Module\ThemeModule::hslToRgb($h, $s, $l);
  $runner->assert(abs($backR - $r) <= 1 && abs($backG - $g) <= 1 && abs($backB - $b) <= 1, "hslToRgb debe revertir a RGB con fidelidad");
  $reHex = \Base\Module\ThemeModule::rgbToHex($backR, $backG, $backB);
  $runner->assert(strtoupper($reHex) === strtoupper($testHex), "rgbToHex debe devolver el hex original");

  // 3. Generación automática de modo oscuro
  $darkPalette = \Base\Module\ThemeModule::generateDarkPalette($loaded['light']['colors']);
  $runner->assert(count($darkPalette) === count($loaded['light']['colors']), "generateDarkPalette debe devolver la misma cantidad de colores");
  
  // Comprobar que un color muy claro (#FFC7B3) se vuelve oscuro en dark palette
  [$lightR, $lightG, $lightB] = \Base\Module\ThemeModule::hexToRgb('#FFC7B3');
  [$darkR, $darkG, $darkB] = \Base\Module\ThemeModule::hexToRgb($darkPalette[0]);
  [, , $lightL] = \Base\Module\ThemeModule::rgbToHsl($lightR, $lightG, $lightB);
  [, , $darkL] = \Base\Module\ThemeModule::rgbToHsl($darkR, $darkG, $darkB);
  $runner->assert($darkL < $lightL, "El primer color en dark palette debe ser más oscuro que el original claro");

  // 4. Compilación a CSS con sincronización segura
  $existingCss = <<<CSS
:root {
  --animate-duration: 750ms;
  --custom-var-test: #abcdef;
}
[data-theme="dark"] {
  --custom-dark-test: 20px;
}
.test-trailing { display: block; }
CSS;

  $compiled = \Base\Module\ThemeModule::compileToCss($loaded, $existingCss);
  $runner->assert(str_contains($compiled, ':root {'), "CSS debe incluir bloque :root");
  $runner->assert(str_contains($compiled, '[data-theme="dark"] {'), "CSS debe incluir bloque [data-theme='dark']");
  $runner->assert(str_contains($compiled, '--back-color: #FFC7B3;'), "CSS debe contener --back-color");
  $runner->assert(str_contains($compiled, '--back-color-hover: oklch('), "CSS debe contener hovers oklch");
  $runner->assert(str_contains($compiled, '--animate-duration: 750ms;'), "CSS debe preservar la duración personalizada de animación");
  $runner->assert(str_contains($compiled, '--custom-var-test: #abcdef;'), "CSS debe preservar variables personalizadas en :root");
  $runner->assert(str_contains($compiled, '--custom-dark-test: 20px;'), "CSS debe preservar variables personalizadas en [data-theme='dark']");
  $runner->assert(str_contains($compiled, '.test-trailing { display: block; }'), "CSS debe preservar reglas CSS finales");

  // 5. Test de build a disco y soporte de Coloristia
  $coloristiaSnippet = <<<CSS
/* Paleta generada en Coloristia */
:root {
  --color-base: #11EEB3;
  --color-step-1: #102820;
  --color-step-2: #11372B;
  --color-step-3: #114636;
}
CSS;
  $parsedColoristia = \Base\Module\ThemeModule::parseColorsFromInput($coloristiaSnippet);
  $runner->assert(count($parsedColoristia) === 3 && $parsedColoristia[0] === '#102820', "parseColorsFromInput debe parsear steps de Coloristia en orden");

  // 6. Test de Multi-tema, Programación por Calendario y Utilidades Condicionales (Fase 4)
  $fullDef = \Base\Module\ThemeModule::getDefaultFullConfig();
  $runner->assert(isset($fullDef['activeTheme']) && $fullDef['activeTheme'] === 'default', "getDefaultFullConfig debe tener activeTheme='default'");
  $runner->assert(isset($fullDef['themes']['default']), "getDefaultFullConfig debe incluir el tema 'default'");

  // Comprobar programación por calendario estacional (Año Nuevo 12-28 a 01-05 cruzando fin de año)
  $nySched = [
    'enabled' => true,
    'recurring' => true,
    'start' => '12-28',
    'end' => '01-05'
  ];
  $runner->assert(\Base\Module\ThemeModule::isScheduleActive($nySched, new \DateTimeImmutable('2026-12-31')), "isScheduleActive debe ser true el 31 de Diciembre");
  $runner->assert(\Base\Module\ThemeModule::isScheduleActive($nySched, new \DateTimeImmutable('2027-01-02')), "isScheduleActive debe ser true el 2 de Enero");
  $runner->assert(!\Base\Module\ThemeModule::isScheduleActive($nySched, new \DateTimeImmutable('2026-06-15')), "isScheduleActive debe ser false el 15 de Junio");

  // Comprobar resolución automática de tema y retorno a 'default' tras vencimiento
  $testThemesConfig = [
    'activeTheme' => 'default',
    'themes' => [
      'default' => \Base\Module\ThemeModule::getDefaultConfig('default', 'Predeterminado'),
      'new-year' => array_merge(\Base\Module\ThemeModule::getDefaultConfig('new-year', 'Año Nuevo'), ['schedule' => $nySched])
    ]
  ];
  $activeInSeason = \Base\Module\ThemeModule::resolveActiveThemeSlug($testThemesConfig, null, new \DateTimeImmutable('2026-12-30'));
  $runner->assert($activeInSeason === 'new-year', "resolveActiveThemeSlug debe resolver 'new-year' cuando está dentro de la fecha programada");
  
  $activeOutOfSeason = \Base\Module\ThemeModule::resolveActiveThemeSlug($testThemesConfig, null, new \DateTimeImmutable('2026-03-10'));
  $runner->assert($activeOutOfSeason === 'default', "resolveActiveThemeSlug debe volver automáticamente a 'default' cuando termina la fecha");

  // Comprobar compilación de utilidades condicionales en CSS
  $compiledMulti = \Base\Module\ThemeModule::compileToCss($testThemesConfig, null, $testThemesConfig['themes']);
  $runner->assert(str_contains($compiledMulti, '--theme-active:'), "CSS compilado debe incluir la variable global --theme-active en :root");
  $runner->assert(str_contains($compiledMulti, '.only-theme-new-year') && str_contains($compiledMulti, '.no-theme-new-year'), "CSS debe incluir utilidades .only-theme-new-year y .no-theme-new-year");
  $runner->assert(str_contains($compiledMulti, 'data-theme-name="new-year"'), "CSS debe incluir selectores por atributo data-theme-name");
  $runner->assert(str_contains($compiledMulti, 'body.theme-new-year'), "CSS debe incluir selectores por clase de body");
  $runner->assert(function_exists('theme_name') && is_string(theme_name()), "Helper global theme_name() debe existir y devolver un string");

  $tempDir = ROOT_PATH . '/tests/temp_theme_test';
  @mkdir($tempDir . '/App/Config', 0755, true);
  @mkdir($tempDir . '/App/Public/Css', 0755, true);

  $jsonFile = $tempDir . '/App/Config/theme.json';
  \Base\Module\ThemeModule::saveConfig($loaded, $jsonFile);
  $runner->assert(file_exists($jsonFile), "saveConfig debe escribir theme.json en disco");

  $buildRes = \Base\Module\ThemeModule::build($tempDir, true);
  $runner->assert($buildRes['success'] === true, "ThemeModule::build debe retornar success=true");
  $runner->assert(file_exists($tempDir . '/App/Public/Css/_configTheme.css'), "build debe generar _configTheme.css");

  // Verificar timestamps y needsRebuild
  $runner->assert(!\Base\Module\ThemeModule::needsRebuild($tempDir), "needsRebuild debe retornar false tras un build");
  
  // Modificar theme.json (avanzar timestamp) y comprobar needsRebuild
  touch($jsonFile, time() + 10);
  $runner->assert(\Base\Module\ThemeModule::needsRebuild($tempDir), "needsRebuild debe ser true cuando theme.json es más reciente");

  // Limpiar directorio temporal de prueba
  @unlink($jsonFile);
  @unlink($tempDir . '/App/Public/Css/_configTheme.css');
  @rmdir($tempDir . '/App/Public/Css');
  @rmdir($tempDir . '/App/Config');
  @rmdir($tempDir . '/App/Public');
  @rmdir($tempDir . '/App');
  @rmdir($tempDir);
});

// =========================================================================
// 17. THEME STUDIO - CONTROLADOR, ENTORNO Y VISTAS (FASE 2)
// =========================================================================
$runner->runTest("Theme Studio - Controlador, seguridad de entorno y registro de rutas", function () use ($runner) {
  // 1. Verificación de entorno de desarrollo
  $runner->assert(\Base\Control\ThemeStudioControl::isDevEnvironment(), "isDevEnvironment debe ser true en entorno de pruebas/desarrollo");

  // 2. Registro de rutas de desarrollo
  \Base\Control\ThemeStudioControl::registerRoutes();
  $routes = \Core\Route::getRoutes();

  $foundGet = false;
  $foundPost = false;
  $foundLoadToSite = false;
  $foundSetActive = false;
  $foundDelete = false;
  foreach ($routes as $route) {
    if ($route['uri'] === '/_frame/theme-studio' && $route['method'] === 'GET') {
      $foundGet = true;
    }
    if ($route['uri'] === '/_frame/theme-studio/save' && $route['method'] === 'POST') {
      $foundPost = true;
    }
    if ($route['uri'] === '/_frame/theme-studio/load-to-site' && $route['method'] === 'POST') {
      $foundLoadToSite = true;
    }
    if ($route['uri'] === '/_frame/theme-studio/set-active' && $route['method'] === 'POST') {
      $foundSetActive = true;
    }
    if ($route['uri'] === '/_frame/theme-studio/delete-theme' && $route['method'] === 'POST') {
      $foundDelete = true;
    }
  }
  $runner->assert($foundGet, "Debe registrarse la ruta GET /_frame/theme-studio");
  $runner->assert($foundPost, "Debe registrarse la ruta POST /_frame/theme-studio/save");
  $runner->assert($foundLoadToSite, "Debe registrarse la ruta POST /_frame/theme-studio/load-to-site");
  $runner->assert($foundSetActive, "Debe registrarse la ruta POST /_frame/theme-studio/set-active");
  $runner->assert($foundDelete, "Debe registrarse la ruta POST /_frame/theme-studio/delete-theme");

  // 3. Fuentes instaladas
  $fonts = \Base\Control\ThemeStudioControl::getInstalledFonts();
  $runner->assert(is_array($fonts) && count($fonts) >= 3, "getInstalledFonts debe devolver una lista de fuentes con al menos los stacks del sistema");
  $runner->assert(!empty($fonts[0]['name']) && !empty($fonts[0]['value']), "Cada entrada de fuente debe incluir 'name' y 'value'");

  // 4. Existencia y estructura de la vista
  $viewPath = ROOT_PATH . '/Base/Views/themeStudio.php';
  $runner->assert(file_exists($viewPath), "Base/Views/themeStudio.php debe existir");
  $viewContent = (string)file_get_contents($viewPath);
  $runner->assert(str_contains($viewContent, 'Frame Theme Studio'), "La vista debe incluir la cabecera de Frame Theme Studio");
  $runner->assert(str_contains($viewContent, 'batchInput'), "La vista debe incluir el área de Pegado Rápido");
  $runner->assert(str_contains($viewContent, 'darkBatchInput'), "La vista debe incluir el área de Pegado Rápido para Modo Oscuro (Coloristia)");
  $runner->assert(str_contains($viewContent, 'parseColorsFromInput'), "La vista debe incluir el parser universal de Coloristia");
  $runner->assert(str_contains($viewContent, 'btnAddColor'), "La vista debe incluir el botón para agregar colores a la paleta");
  $runner->assert(str_contains($viewContent, 'removeColorSlot'), "La vista debe incluir la función para eliminar colores de la paleta");
  $runner->assert(!str_contains($viewContent, 'harmonyType'), "La vista no debe incluir el generador armónico");
  $runner->assert(str_contains($viewContent, 'id="sandbox"'), "La vista debe incluir el contenedor sandbox de previsualización interactiva");
  // 5. Verificación de Vista Real del Proyecto en Vivo y Diagnóstico (Fase 3)
  $projectRoutes = \Base\Control\ThemeStudioControl::getProjectRoutes();
  $runner->assert(is_array($projectRoutes) && !empty($projectRoutes) && $projectRoutes[0]['uri'] === '/', "getProjectRoutes debe devolver las rutas del proyecto con '/' como vista principal");
  $runner->assert(str_contains($viewContent, 'id="projectPreviewFrame"'), "La vista debe incluir el iframe #projectPreviewFrame para renderizar las vistas reales del proyecto");
  $runner->assert(str_contains($viewContent, 'projectRouteSelect') && str_contains($viewContent, 'projectRouteInput'), "La vista debe incluir el selector y campo de rutas de App/Route");
  $runner->assert(str_contains($viewContent, 'injectLiveThemeIntoIframe') && str_contains($viewContent, 'buildLiveThemeCssString'), "La vista debe inyectar y sobrescribir las variables de _configTheme.css en vivo dentro del iframe del proyecto");
  $runner->assert(str_contains($viewContent, 'getContrastRatio') && str_contains($viewContent, 'getWcagRating'), "La vista debe incluir el motor matemático de evaluación de contraste WCAG 2.1");
  $runner->assert(str_contains($viewContent, 'tab-wcag') && str_contains($viewContent, 'wcagMatrixContainer'), "La vista debe incluir la pestaña y contenedor de la matriz WCAG en vivo");
  $runner->assert(str_contains($viewContent, 'renderGradientsShowcase') && str_contains($viewContent, 'tab-gradients'), "La vista debe incluir el showcase y pestaña de gradientes armónicos de Coloristia");
  // 6. Verificación de Múltiples Temas Guardados, Selector y Modal (Fase 4)
  $runner->assert(str_contains($viewContent, 'id="themeSelector"'), "La vista debe incluir el selector de temas guardados en la cabecera");
  $runner->assert(str_contains($viewContent, 'tab-themes') && str_contains($viewContent, 'themesListContainer'), "La vista debe incluir la pestaña de gestión de temas y su contenedor");
  $runner->assert(str_contains($viewContent, 'id="modalSaveTheme"'), "La vista debe incluir el modal para guardar paletas como nuevo tema");
  $runner->assert(str_contains($viewContent, 'submitSaveNewTheme') && str_contains($viewContent, 'saveCurrentThemeSchedule'), "La vista debe incluir las funciones JS para persistir nuevos temas y programaciones");
  $runner->assert(str_contains($viewContent, 'loadThemeToSite'), "La vista debe incluir la función JS loadThemeToSite para cargar y aplicar un tema al sitio real");
  $runner->assert(str_contains($viewContent, 'id="btnSave"') && str_contains($viewContent, '💾 Guardar'), "El botón principal debe ser '💾 Guardar' para modo aislado");
  $runner->assert(str_contains($viewContent, 'data-theme-name'), "La vista debe inyectar el atributo data-theme-name en vivo en el iframe");

  // 7. Verificación de Selector de Fechas (Date Picker) y FloatingScroll en Preview (Fase 5)
  $runner->assert(str_contains($viewContent, 'id="tsDatePickerPopover"'), "La vista debe incluir el popover del selector de fechas (#tsDatePickerPopover)");
  $runner->assert(str_contains($viewContent, 'openDatePicker') && str_contains($viewContent, 'applySchedulePreset'), "La vista debe incluir las funciones JS del calendario interactivo y presets rápidos");
  $runner->assert(str_contains($viewContent, 'setupIframeFloatingScroll') && str_contains($viewContent, 'PreviewFloatingScroll'), "La vista debe incluir el motor floatingScroll para la barra de previsualización sin alterar el ancho");

  $floatingJsPath = ROOT_PATH . '/Resources/Js/Components/floatingScroll.js';
  $runner->assert(file_exists($floatingJsPath), "Resources/Js/Components/floatingScroll.js debe existir");
  $floatingJsContent = (string)file_get_contents($floatingJsPath);
  $runner->assert(str_contains($floatingJsContent, 'targetWindow') && str_contains($floatingJsContent, 'targetDocument'), "floatingScroll.js debe soportar targetWindow y targetDocument para previsualizaciones e iframes");
});

// =========================================================================
// 18. CHART STUDIO - RENDERIZADO, PERSISTENCIA Y GENERADOR DE GRÁFICOS
// =========================================================================
$runner->runTest("Chart Studio - Renderizado de 12 gráficos, presets, persistencia y UI", function () use ($runner) {
  // 1. Verificar ruta POST /_frame/theme-studio/render-chart
  \Base\Control\ThemeStudioControl::registerRoutes();
  $routes = \Core\Route::getRoutes();
  $foundRenderChart = false;
  foreach ($routes as $route) {
    if (($route['uri'] ?? '') === '/_frame/theme-studio/render-chart' && ($route['method'] ?? '') === 'POST') {
      $foundRenderChart = true;
      break;
    }
  }
  $runner->assert($foundRenderChart, "Debe registrarse la ruta POST /_frame/theme-studio/render-chart");

  // 2. Verificar bundle autocontenido CSS/JS de Charts
  $assets = \Base\Control\ThemeStudioControl::getChartsLibraryAssets(ROOT_PATH);
  $runner->assert(!empty($assets['css']) && str_contains($assets['css'], '.mono-chart-svg'), "getChartsLibraryAssets debe cargar charts.css");
  $runner->assert(!empty($assets['js']) && str_contains($assets['js'], 'window.initCharts'), "getChartsLibraryAssets debe generar el bundle JS con window.initCharts");

  // 3. Renderizar vista 'all' con los 12 gráficos
  $allRes = \Base\Control\ThemeStudioControl::renderChartPayload([
    'type' => 'all',
    'globalConfig' => [
      'color' => 'color1',
      'colorLabel' => 'textw',
      'axisLabel' => 'texto',
      'axisLabelSize' => 14,
      'barWidth' => 25,
      'yLabelPosition' => '[between],[50, 40, start]',
      'transition' => 450,
      'tooltip' => true,
    ],
    'datasetPreset' => 'standard',
  ]);
  $runner->assert(!empty($allRes['success']), "renderChartPayload('all') debe retornar success=true");
  $runner->assert(isset($allRes['charts']) && count($allRes['charts']) === 12, "renderChartPayload('all') debe renderizar exactamente los 12 tipos de gráficos");
  foreach (\Base\Control\ThemeStudioControl::CHART_TYPES as $chartType) {
    $runner->assert(!empty($allRes['charts'][$chartType]), "Debe renderizarse el gráfico '{$chartType}'");
  }

  // 4. Verificar render individual y aplicación exacta de barWidth, axisLabelSize, yLabelPosition y tooltip
  $hbarRes = \Base\Control\ThemeStudioControl::renderChartPayload([
    'type' => 'horizontalBars',
    'globalConfig' => [
      'color' => 'color2',
      'axisLabel' => 'texto',
      'axisLabelSize' => 14,
      'barWidth' => 25,
      'yLabelPosition' => '[between],[50, 40, start]',
      'tooltip' => false,
    ],
    'datasetPreset' => 'long-labels',
  ]);
  $runner->assert(!empty($hbarRes['success']) && !empty($hbarRes['html']), "horizontalBars debe renderizarse correctamente");
  $hbarHtml = $hbarRes['html'];
  $runner->assert(str_contains($hbarHtml, 'height: 25.00px'), "horizontalBars debe respetar barWidth=25");
  $runner->assert(str_contains($hbarHtml, 'font-size: 14px'), "horizontalBars debe respetar axisLabelSize=14");
  $runner->assert(str_contains($hbarHtml, 'justify-content: space-between'), "horizontalBars debe aplicar row='between' de yLabelPosition");
  $runner->assert(str_contains($hbarHtml, 'flex: 0 0 50%') && str_contains($hbarHtml, 'flex: 0 0 40%'), "horizontalBars debe aplicar reparto 50% / 40%");
  $runner->assert(str_contains($hbarHtml, 'data-tooltip="false"'), "horizontalBars debe reflejar tooltip=false");
  $runner->assert(str_contains($hbarHtml, 'Autenticación y Control de Acceso'), "Preset long-labels debe inyectar etiquetas largas");

  // Verificar presets compact y dense
  $compactData = \Base\Control\ThemeStudioControl::getPresetDataset('horizontalBars', 'compact');
  $denseData = \Base\Control\ThemeStudioControl::getPresetDataset('horizontalBars', 'dense');
  $runner->assert(count($compactData['bars']) === 3, "Preset compact de horizontalBars debe tener 3 ítems");
  $runner->assert(count($denseData['bars']) >= 8, "Preset dense de horizontalBars debe tener al menos 8 ítems");

  // Verificar tipo inválido
  $badRes = \Base\Control\ThemeStudioControl::renderChartPayload(['type' => 'graficoInexistente']);
  $runner->assert(empty($badRes['success']), "renderChartPayload debe rechazar tipos desconocidos");

  // 5. Persistencia de configuración charts en ThemeModule y theme.json
  $defCfg = \Base\Module\ThemeModule::getDefaultConfig();
  $runner->assert(isset($defCfg['charts']) && $defCfg['charts']['color'] === 'texto', "getDefaultConfig debe incluir la sección charts con defaults");

  $tempChartsDir = ROOT_PATH . '/tests/temp_chart_theme_test';
  @mkdir($tempChartsDir . '/App/Config', 0755, true);
  $tempJson = $tempChartsDir . '/App/Config/theme.json';

  $customTheme = \Base\Module\ThemeModule::getDefaultConfig('default', 'Predeterminado');
  $customTheme['charts'] = [
    'color' => 'color1',
    'colorLabel' => 'textw',
    'axisLabel' => 'texto',
    'axisLabelSize' => 14,
    'transition' => 600,
    'tooltip' => false,
    'barWidth' => 25,
    'yLabelPosition' => '[between],[50, 40, start]',
    'localOverrides' => [
      'bulletTarget' => ['targetColor' => '#38bdf8'],
    ],
  ];

  $saved = \Base\Module\ThemeModule::saveConfig($customTheme, $tempJson);
  $runner->assert($saved === true, "saveConfig debe guardar theme.json con configuración de charts");

  $reloaded = \Base\Module\ThemeModule::loadConfig($tempJson);
  $runner->assert(isset($reloaded['charts']), "loadConfig debe conservar la sección charts");
  $runner->assert($reloaded['charts']['color'] === 'color1', "charts.color debe persistirse");
  $runner->assert((int)$reloaded['charts']['barWidth'] === 25, "charts.barWidth debe persistirse");
  $runner->assert($reloaded['charts']['yLabelPosition'] === '[between],[50, 40, start]', "charts.yLabelPosition debe persistirse");
  $runner->assert($reloaded['charts']['tooltip'] === false, "charts.tooltip=false debe persistirse");
  $runner->assert(($reloaded['charts']['localOverrides']['bulletTarget']['targetColor'] ?? '') === '#38bdf8', "charts.localOverrides debe persistirse");

  // Verificar que compileToCss sigue funcionando sin afectar _configTheme.css
  $cssOut = \Base\Module\ThemeModule::compileToCss($reloaded);
  $runner->assert(str_contains($cssOut, ':root {') && !str_contains($cssOut, 'localOverrides'), "compileToCss no debe filtrar claves JSON de charts al CSS");

  @unlink($tempJson);
  @rmdir($tempChartsDir . '/App/Config');
  @rmdir($tempChartsDir . '/App');
  @rmdir($tempChartsDir);

  // 6. Verificar elementos clave de Chart Studio en Base/Views/themeStudio.php
  $viewContent = (string)file_get_contents(ROOT_PATH . '/Base/Views/themeStudio.php');
  $runner->assert(str_contains($viewContent, 'id="tab-charts"'), "themeStudio.php debe incluir la pestaña #tab-charts");
  $runner->assert(str_contains($viewContent, 'id="chartSandboxView"'), "themeStudio.php debe incluir el escenario #chartSandboxView");
  $runner->assert(str_contains($viewContent, 'setStageMode('), "themeStudio.php debe incluir setStageMode para alternar Sitio / Gráficos");
  $runner->assert(str_contains($viewContent, 'serializeYLabelPosition') && str_contains($viewContent, 'csYPosRowGrid'), "themeStudio.php debe incluir el constructor visual de yLabelPosition");
  $runner->assert(str_contains($viewContent, 'updateChartCodeOutput') && str_contains($viewContent, '_chart(') && str_contains($viewContent, 'GraphicsModule') && str_contains($viewContent, 'configStyle('), "themeStudio.php debe incluir el generador de código PHP Copy & Paste");
});

// =========================================================================
// 19. ICON STUDIO - GALERÍA SVG POR CARPETAS, ESCANEO DINÁMICO Y HELPER svg()
// =========================================================================
$runner->runTest("Icon Studio - Galería SVG por carpetas, escaneo dinámico y helper svg()", function () use ($runner) {
  // 1. Verificar ruta GET /_frame/theme-studio/icons
  \Base\Control\ThemeStudioControl::registerRoutes();
  $routes = \Core\Route::getRoutes();
  $foundIconsRoute = false;
  foreach ($routes as $route) {
    if (($route['uri'] ?? '') === '/_frame/theme-studio/icons' && ($route['method'] ?? '') === 'GET') {
      $foundIconsRoute = true;
      break;
    }
  }
  $runner->assert($foundIconsRoute, "Debe registrarse la ruta GET /_frame/theme-studio/icons");

  // 2. Verificar catálogo base en Resources/Ico/ separado por carpetas (Raíz, Custom, Separator)
  $baseCatalog = \Base\Control\ThemeStudioControl::getIconsCatalog(ROOT_PATH);
  $runner->assert($baseCatalog['totalIcons'] >= 142, "El catálogo base debe detectar al menos 142 iconos SVG");
  $folderMap = [];
  foreach ($baseCatalog['folders'] as $f) {
    $folderMap[$f['key']] = $f;
  }
  $runner->assert(isset($folderMap['']), "Debe existir la sección raíz (/Ico)");
  $runner->assert(isset($folderMap['Custom']), "Debe existir la sección de carpeta Custom");
  $runner->assert(isset($folderMap['Separator']) && $folderMap['Separator']['count'] >= 20, "Debe existir la sección de carpeta Separator con sus SVG");

  $foundAsterisk = false;
  foreach ($folderMap['']['icons'] as $ico) {
    if ($ico['name'] === 'asterisk') {
      $foundAsterisk = true;
      $runner->assert($ico['phpCode'] === 'svg("asterisk");', "El código PHP copiable para asterisk debe ser svg(\"asterisk\");");
      $runner->assert(str_contains($ico['svgHtml'], '<svg') && str_contains($ico['svgHtml'], '<path'), "asterisk debe incluir su markup <svg> renderizado");
      break;
    }
  }
  $runner->assert($foundAsterisk, "El catálogo raíz debe incluir el icono asterisk");

  // 3. Verificar resolución de helper svg() en raíz, subcarpetas con mayúsculas y bloqueo de traversal
  $rootSvg = svg('asterisk');
  $sepSvg = svg('Separator/fire-solid-full');
  $traversalSvg = svg('../composer.json');
  $runner->assert($rootSvg !== '?!' && str_contains($rootSvg, '<svg'), "svg('asterisk') debe renderizar un SVG válido");
  $runner->assert($sepSvg !== '?!' && str_contains($sepSvg, '<svg'), "svg('Separator/fire-solid-full') debe resolver la subcarpeta Separator");
  $runner->assert($traversalSvg === '?!', "svg() debe rechazar rutas con '..'");

  // 4. Verificar escaneo dinámico al agregar nuevos SVG y carpetas en App/Rsc/Ico/
  $tempIcoBase = ROOT_PATH . '/tests/temp_ico_gallery_test';
  @mkdir($tempIcoBase . '/App/Rsc/Ico/Custom', 0755, true);
  @mkdir($tempIcoBase . '/App/Rsc/Ico/Separator', 0755, true);
  @mkdir($tempIcoBase . '/App/Rsc/Ico/Brand', 0755, true);

  file_put_contents(
    $tempIcoBase . '/App/Rsc/Ico/root-demo.svg',
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 4h16v16H4z"/></svg>'
  );
  file_put_contents(
    $tempIcoBase . '/App/Rsc/Ico/Custom/outline-ring.svg',
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>'
  );
  file_put_contents(
    $tempIcoBase . '/App/Rsc/Ico/Separator/wave.svg',
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2 12h20"/></svg>'
  );
  file_put_contents(
    $tempIcoBase . '/App/Rsc/Ico/Brand/badge.svg',
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="4"/></svg>'
  );

  $dynCatalog1 = \Base\Control\ThemeStudioControl::getIconsCatalog($tempIcoBase);
  $runner->assert($dynCatalog1['totalIcons'] === 4, "El catálogo dinámico inicial debe contar 4 iconos en el proyecto temporal");
  $runner->assert($dynCatalog1['totalFolders'] === 4, "El catálogo dinámico debe detectar 4 secciones (Raíz, Brand, Custom, Separator)");

  $dynFolders = [];
  foreach ($dynCatalog1['folders'] as $f) {
    $dynFolders[$f['key']] = $f;
  }
  $runner->assert($dynFolders['Custom']['count'] === 1 && $dynFolders['Custom']['icons'][0]['phpCode'] === 'svg("Custom/outline-ring");', "Custom debe listar su icono con phpCode svg(\"Custom/outline-ring\");");
  $runner->assert(str_contains($dynFolders['Custom']['icons'][0]['svgHtml'], '<circle') && str_contains($dynFolders['Custom']['icons'][0]['svgHtml'], 'fill="none"'), "renderSvgFile debe soportar iconos outline y figuras <circle> en Custom");
  $runner->assert($dynFolders['Brand']['count'] === 1 && str_contains($dynFolders['Brand']['icons'][0]['svgHtml'], '<rect'), "Nueva carpeta Brand debe ser detectada y soportar <rect>");

  // Agregar un nuevo SVG dinámicamente y comprobar reflejo inmediato
  file_put_contents(
    $tempIcoBase . '/App/Rsc/Ico/Custom/second-icon.svg',
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2L2 22h20z"/></svg>'
  );
  $dynCatalog2 = \Base\Control\ThemeStudioControl::getIconsCatalog($tempIcoBase);
  $runner->assert($dynCatalog2['totalIcons'] === 5, "Al agregar un nuevo SVG en Custom debe reflejarse inmediatamente (5 iconos)");

  // Limpiar directorio temporal
  $cleanTemp = function (string $dir) use (&$cleanTemp): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) ?: [] as $entry) {
      if ($entry === '.' || $entry === '..') continue;
      $p = $dir . '/' . $entry;
      is_dir($p) ? $cleanTemp($p) : @unlink($p);
    }
    @rmdir($dir);
  };
  $cleanTemp($tempIcoBase);

  // 5. Verificar elementos de Icon Studio en Base/Views/themeStudio.php
  $viewContent = (string)file_get_contents(ROOT_PATH . '/Base/Views/themeStudio.php');
  $runner->assert(str_contains($viewContent, 'id="tab-icons"') && str_contains($viewContent, 'id="tabBtnIcons"'), "themeStudio.php debe incluir la pestaña #tab-icons");
  $runner->assert(str_contains($viewContent, 'id="btnStageIcons"') && str_contains($viewContent, 'id="iconSandboxView"'), "themeStudio.php debe incluir el conmutador y escenario #iconSandboxView");
  $runner->assert(str_contains($viewContent, 'id="isFoldersContainer"'), "themeStudio.php debe incluir el contenedor de grillas por carpeta #isFoldersContainer");
  $runner->assert(str_contains($viewContent, 'initIconStudio') && str_contains($viewContent, 'refreshIconsCatalog') && str_contains($viewContent, 'buildIconCodeSnippet'), "themeStudio.php debe incluir el motor JS de Icon Studio");
});

// Helper interno para ejecutar pruebas de regresión de seguridad en subproceso PHP aislado
$runSecSubprocess = static function (string $phpCode): array {
  $tmpScript = tempnam(sys_get_temp_dir(), 'frame_sec_test_');
  if ($tmpScript === false) {
    return ['code' => 1, 'output' => 'No se pudo crear script temporal'];
  }
  $scriptPath = $tmpScript . '.php';
  @rename($tmpScript, $scriptPath);
  file_put_contents($scriptPath, "<?php\n" . $phpCode);
  $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($scriptPath) . ' 2>&1';
  $out = [];
  $exitCode = 1;
  exec($cmd, $out, $exitCode);
  @unlink($scriptPath);
  return ['code' => $exitCode, 'output' => implode("\n", $out)];
};

// =========================================================================
// 20. SEGURIDAD (SEC-01 / SEC-15 / SEC-16) - ENTORNO FAIL-CLOSED Y BACKDOOR
// =========================================================================
$runner->runTest("Seguridad SEC-01/15/16 - Entorno fail-closed, HandlerError sin backdoor y display_errors", function () use ($runner, $runSecSubprocess) {
  $rootLit = var_export(ROOT_PATH, true);

  foreach (['', 'production ', 'staging', 'INVALIDO'] as $envVal) {
    $envLit = var_export($envVal, true);
    $res = $runSecSubprocess(<<<PHP
\$_ENV = ['APP_ENV' => {$envLit}];
\$_SERVER['APP_ENV'] = {$envLit};
putenv('APP_ENV=' . {$envLit});
@ini_set('display_errors', '1');
define('ROOT_PATH', {$rootLit});
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/config.php';
echo json_encode([
  'env' => ENVIRONMENT,
  'isProd' => \\Base\\Module\\SecurityModule::isProduction(),
  'isDev' => \\Base\\Module\\SecurityModule::isDevelopment(),
  'studioDev' => \\Base\\Control\\ThemeStudioControl::isDevEnvironment(),
  'errDev' => \\Core\\ErrorHandler::isDev(),
  'displayErrors' => (string)ini_get('display_errors'),
]);
PHP);
    $data = json_decode(trim($res['output']), true);
    $runner->assert(is_array($data), "Subproceso fail-closed para APP_ENV='{$envVal}' debe responder JSON válido: " . $res['output']);
    $runner->assert(($data['env'] ?? '') === 'production', "APP_ENV='{$envVal}' debe resolver ENVIRONMENT='production' fail-closed");
    $runner->assert(($data['isProd'] ?? false) === true && ($data['isDev'] ?? true) === false, "isProduction() debe ser true e isDevelopment() false para '{$envVal}'");
    $runner->assert(($data['studioDev'] ?? true) === false && ($data['errDev'] ?? true) === false, "ThemeStudioControl y ErrorHandler deben ser false para '{$envVal}'");
    $runner->assert(($data['displayErrors'] ?? '1') === '0', "configureErrorHandling() debe forzar display_errors=0 fuera de desarrollo");
  }

  // Verificar retrocompatibilidad con APP_ENV="DEV"
  $resDev = $runSecSubprocess(<<<PHP
\$_ENV = [];
\$_SERVER['APP_ENV'] = 'DEV';
putenv('APP_ENV=DEV');
define('ROOT_PATH', {$rootLit});
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/config.php';
echo json_encode([
  'env' => ENVIRONMENT,
  'isDev' => \\Base\\Module\\SecurityModule::isDevelopment(),
]);
PHP);
  $devData = json_decode(trim($resDev['output']), true);
  $runner->assert(($devData['env'] ?? '') === 'development' && ($devData['isDev'] ?? false) === true, "APP_ENV='DEV' debe ser reconocido como development");

  // Verificar eliminación del backdoor 'eber' y escape XSS en HandlerError.php en producción
  $resErr = $runSecSubprocess(<<<PHP
\$_ENV = ['APP_ENV' => 'production'];
\$_SERVER['APP_ENV'] = 'production';
define('ROOT_PATH', {$rootLit});
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/config.php';
\$_SESSION['user_session'] = ['username' => 'eber', 'role_id' => 2];
\$num_error = 1052;
\$msg = ['', 'Err', '<script>alert(1)</script>', 'javascript:alert(1)', '<b>SQL_SECRET</b>'];
\$dbDiagnostic = ['engine' => 'sqlite', 'path' => '/var/www/Database/secreta.sqlite', 'error' => 'PDO_SECRET_TRACE'];
ob_start();
require ROOT_PATH . '/Base/Error/HandlerError.php';
echo (string)ob_get_clean();
PHP);
  $errOut = $resErr['output'];
  $runner->assert(!str_contains($errOut, 'eber'), "HandlerError.php no debe mencionar ni privilegiar el usuario 'eber'");
  $runner->assert(!str_contains($errOut, 'secreta.sqlite') && !str_contains($errOut, 'PDO_SECRET_TRACE'), "Usuario 'eber' no admin en producción no debe ver diagnóstico BD");
  $runner->assert(!str_contains($errOut, '<script>alert(1)</script>'), "HandlerError.php debe escapar HTML potencialmente malicioso en \$msg");
});

// =========================================================================
// 21. SEGURIDAD (SEC-02 / SEC-03) - THEME STUDIO, /_frame Y CSS INJECTION
// =========================================================================
$runner->runTest("Seguridad SEC-02/03 - Theme Studio 404 en prod, vista directa y sanitización CSS", function () use ($runner, $runSecSubprocess) {
  $rootLit = var_export(ROOT_PATH, true);

  // 1. Inclusión directa de themeStudio.php sin ROOT_PATH
  $resView = $runSecSubprocess(<<<PHP
register_shutdown_function(function () {
  echo '|HTTP_CODE:' . http_response_code();
});
require {$rootLit} . '/Base/Views/themeStudio.php';
PHP);
  $runner->assert(str_contains($resView['output'], '|HTTP_CODE:404') && !str_contains($resView['output'], '<html'), "Base/Views/themeStudio.php sin ROOT_PATH debe terminar con HTTP 404 sin contenido");

  // 2. En producción, Route no registra /_frame/* y ThemeStudioControl::save() responde 404 JSON
  $resProdRoute = $runSecSubprocess(<<<PHP
\$_ENV = ['APP_ENV' => 'production'];
\$_SERVER['APP_ENV'] = 'production';
define('ROOT_PATH', {$rootLit});
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/config.php';
\\Core\\Route::reset();
\\Base\\Control\\ThemeStudioControl::registerRoutes();
\$routes = \\Core\\Route::getRoutes();
\$hasFrame = false;
foreach (\$routes as \$r) {
  if (str_contains(\$r['uri'] ?? '', '_frame')) {
    \$hasFrame = true;
  }
}
echo json_encode(['hasFrame' => \$hasFrame]);
PHP);
  $prodRouteData = json_decode(trim($resProdRoute['output']), true);
  $runner->assert(isset($prodRouteData['hasFrame']) && $prodRouteData['hasFrame'] === false, "ThemeStudioControl::registerRoutes() no debe registrar rutas /_frame en producción");

  // 3. Sanitización de valores CSS en ThemeModule
  $badCssVal = \Base\Module\ThemeModule::sanitizeCssValue("#ff0000; } body { background: url(javascript:alert(1)) } </style><script>", '#112233');
  $runner->assert(!str_contains($badCssVal, '}') && !str_contains($badCssVal, '</style') && !str_contains($badCssVal, 'javascript:'), "ThemeModule::sanitizeCssValue debe neutralizar rupturas de regla CSS y </style>");
  $exprCss = \Base\Module\ThemeModule::sanitizeCssValue("expression(alert(1))", '#000000');
  $runner->assert(!str_contains(strtolower($exprCss), 'expression('), "ThemeModule::sanitizeCssValue debe rechazar expression()");
});

// =========================================================================
// 22. SEGURIDAD (SEC-12 / SEC-11 / SEC-06) - UPLOADS, EXTR_SKIP, LOGS Y CONFVIEW
// =========================================================================
$runner->runTest("Seguridad SEC-12/11/06 - Control::file() MIME real/SVG, EXTR_SKIP, LogModule y SeoModule/confView", function () use ($runner) {
  $ctrl = new \Base\Control\Control();
  $tmpDir = ROOT_PATH . '/tests/tmp_sec_uploads';
  @mkdir($tmpDir, 0755, true);

  // 1. Subida con MIME falsificado en cabecera cliente ($file['type'] = 'image/png') pero cuerpo HTML/PHP
  $fakePng = $tmpDir . '/payload.png';
  file_put_contents($fakePng, "<?php echo 'hack'; ?><html><body><script>alert(1)</script></body></html>");
  $resSpoof = $ctrl->file([
    'name' => 'avatar.png',
    'type' => 'image/png',
    'tmp_name' => $fakePng,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($fakePng),
  ], 'Uploads/sec_test', ['image/png']);
  $runner->assert(isset($resSpoof['error']), "Control::file() debe rechazar MIME falsificado si finfo no confirma image/png");

  // 2. Subida SVG bloqueada incluso si se intenta incluir
  $svgFile = $tmpDir . '/xss.svg';
  file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>');
  $resSvg = $ctrl->file([
    'name' => 'icon.svg',
    'type' => 'image/svg+xml',
    'tmp_name' => $svgFile,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($svgFile),
  ], 'Uploads/sec_test', ['image/svg+xml', 'image/png']);
  $runner->assert(isset($resSvg['error']), "Control::file() debe bloquear archivos SVG activos siempre");

  // 3. Subida PNG válida genera nombre aleatorio y crea .htaccess protector
  $validPng = $tmpDir . '/real.png';
  $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
  file_put_contents($validPng, $pngBytes);
  $resOk = $ctrl->file([
    'name' => 'Mi Foto Original.png',
    'type' => 'image/png',
    'tmp_name' => $validPng,
    'error' => UPLOAD_ERR_OK,
    'size' => strlen($pngBytes),
  ], 'Uploads/sec_test', ['image/png']);
  $runner->assert(!empty($resOk['success']), "PNG válido debe subirse correctamente en CLI");
  $runner->assert(($resOk['filename'] ?? '') !== 'Mi Foto Original.png', "Control::file() debe aleatorizar el nombre final");
  $runner->assert(file_exists(ROOT_PATH . '/Uploads/sec_test/.htaccess'), "Control::file() debe crear .htaccess restrictivo en el destino");

  // 4. Control::viewClean() aislado con EXTR_SKIP (no sobrescribe $templatePath ni $filePath)
  @mkdir(ROOT_PATH . '/App/Views/SecTest', 0755, true);
  file_put_contents(ROOT_PATH . '/App/Views/SecTest/isolated.php', 'VISTA_SEGURA:<?= e($val ?? "") ?>');
  $viewOut = $ctrl->viewClean('SecTest.isolated', [
    'templatePath' => ROOT_PATH . '/composer.json',
    'filePath' => ROOT_PATH . '/composer.json',
    'val' => 'OK_AISLADO',
  ]);
  $runner->assert(str_contains($viewOut, 'VISTA_SEGURA:OK_AISLADO') && !str_contains($viewOut, 'eber/framework'), "viewClean() debe aislar extract(EXTR_SKIP) frente a colisión de templatePath/filePath");

  // 5. Part::resolvePath y LogModule confinados
  $runner->assert(\resolvePath('../composer.json', ROUTE_VIEW) === null, "Part resolvePath() debe rechazar traversal '..'");
  $runner->assert(\Base\Module\LogModule::simpleLog(['dir' => '../../App', 'name' => 'evil', 'content' => ['a' => 1]]) === false, "LogModule::simpleLog() debe rechazar salidas fuera de Logs/");
  $runner->assert(\Base\Module\LogModule::readLogLines('../../composer.json') === [], "LogModule::readLogLines() debe rechazar rutas traversal fuera de Logs/");

  // 6. Escape JSON-LD y OpenGraph en SeoModule
  $jsonLd = \Base\Module\SeoModule::schemaJson(['name' => '</script><script>alert(1)</script>']);
  $runner->assert(!str_contains($jsonLd, '</script><script>alert(1)'), "SeoModule::schemaJson() debe codificar etiquetas HTML con JSON_HEX_TAG");
  $ogHtml = \Base\Module\SeoModule::openGraph(['title' => 'Hola" onmouseover="alert(1)', 'image' => 'https://example.com/a.png" onerror="x']);
  $runner->assert(!str_contains($ogHtml, '" onmouseover="'), "SeoModule::openGraph() debe escapar atributos con htmlspecialchars");

  // Limpiar artefactos temporales locales de este test
  @unlink($fakePng);
  @unlink($svgFile);
  @unlink($validPng);
  @rmdir($tmpDir);
});

// =========================================================================
// 23. SEGURIDAD (SEC-10) - BUILDER Y BUILDERSQLITE ANTI-SQLI Y ALLOWED_TABLES
// =========================================================================
$runner->runTest("Seguridad SEC-10 - Validación estructural en Builder / BuilderSqlite y ALLOWED_TABLES", function () use ($runner, $runSecSubprocess) {
  $pdo = new \PDO('sqlite::memory:');
  $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
  $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, role TEXT)');
  $pdo->exec("INSERT INTO users (name, role) VALUES ('Alice', 'user')");

  $threw = static function (callable $fn): bool {
    try {
      $fn();
      return false;
    } catch (\InvalidArgumentException|\Throwable $e) {
      return true;
    }
  };

  // Identificador de tabla malicioso
  $runner->assert($threw(fn() => new \Base\Builder\Builder('users; DROP TABLE users--', $pdo)), "Builder debe rechazar SQLi en nombre de tabla");

  // Clave de columna maliciosa en insert()/update()
  $runner->assert($threw(fn() => (new \Base\Builder\Builder('users', $pdo))->insert(['name = "x"; DROP TABLE users--' => 'hack'])), "Builder::insert() debe rechazar claves de columna con SQLi");
  $runner->assert($threw(fn() => (new \Base\Builder\Builder('users', $pdo))->update('id; --', 1, ['name' => 'Bob'])), "Builder::update() debe rechazar columna filtro inválida");

  // Operador inválido en where()
  $runner->assert($threw(fn() => (new \Base\Builder\Builder('users', $pdo))->where('id', '= 1 OR 1=1 --', 1)), "Builder::where() debe rechazar operadores fuera de lista blanca");

  // Proyección Select maliciosa vs agregación legítima
  $runner->assert($threw(fn() => (new \Base\Builder\Builder('users', $pdo))->select('id, (SELECT 1) AS x; DROP TABLE users')->get()), "Builder::select() debe rechazar comentarios/stacked queries");
  $aggRes = (new \Base\Builder\Builder('users', $pdo))->select('COUNT(DISTINCT id) AS total')->get();
  $runner->assert((int)($aggRes[0]['total'] ?? 0) === 1, "Builder::select() debe conservar compatibilidad con COUNT(DISTINCT col) AS alias");

  // limit() seguro ante strings no numéricos
  $runner->assert($threw(fn() => (new \Base\Builder\Builder('users', $pdo))->select('*')->limit('1; DROP TABLE users')), "Builder::limit() debe rechazar valores no numéricos");

  // fillable() filtra campos no permitidos contra mass-assignment
  $bFill = (new \Base\Builder\Builder('users', $pdo))->fillable(['name']);
  $bFill->insert(['name' => 'Carol', 'role' => 'admin']);
  $carol = (new \Base\Builder\Builder('users', $pdo))->where('name', 'Carol')->first();
  $runner->assert($carol && ($carol['role'] ?? null) === null, "fillable() debe ignorar columnas no autorizadas como 'role'");

  // BuilderSqlite limit() inválido
  $sqliteFile = ROOT_PATH . '/tests/sec_builder.sqlite';
  $sqliteDb = new \PDO('sqlite:' . $sqliteFile);
  $sqliteDb->exec('CREATE TABLE IF NOT EXISTS items (id INTEGER PRIMARY KEY, title TEXT)');
  $bSqlite = new \Base\Builder\BuilderSqlite('items');
  $runner->assert($threw(fn() => $bSqlite->limit('1 UNION SELECT 1')), "BuilderSqlite::limit() debe rechazar strings no numéricos");
  $bSqlite = null;
  $sqliteDb = null;
  @unlink($sqliteFile);

  // ALLOWED_TABLES whitelist estricta opt-in (subproceso)
  $rootLit = var_export(ROOT_PATH, true);
  $resWhitelist = $runSecSubprocess(<<<PHP
define('ROOT_PATH', {$rootLit});
define('ALLOWED_TABLES', ['users']);
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/config.php';
\$pdo = new PDO('sqlite::memory:');
try {
  new \\Base\\Builder\\Builder('posts', \$pdo);
  echo 'NO_EXCEPTION';
} catch (\\Throwable \$e) {
  echo 'THREW:' . \$e->getMessage();
}
PHP);
  $runner->assert(str_starts_with(trim($resWhitelist['output']), 'THREW:'), "ALLOWED_TABLES definido debe rechazar tablas fuera de la whitelist ('posts')");
});

// =========================================================================
// 24. SEGURIDAD (SEC-14 / SEC-15) - CSRF GLOBAL, JWT AUTHMODULE Y PLAINTEXT
// =========================================================================
$runner->runTest("Seguridad SEC-14/15 - CSRF en POST/PUT/PATCH/DELETE/_method y AuthModule JWT", function () use ($runner, $runSecSubprocess) {
  $token = \Base\Module\SecurityModule::getCsrfToken();
  $runner->assert(strlen($token) >= 32, "getCsrfToken() debe generar un token criptográfico");

  // Sin token, POST/PUT/PATCH/DELETE y POST + _method=PUT deben fallar
  $_POST = [];
  $_SERVER['HTTP_X_CSRF_TOKEN'] = '';
  $runner->assert(\Base\Module\SecurityModule::verifyCsrf(false, 'POST', []) === false, "verifyCsrf() debe fallar en POST sin token");
  $runner->assert(\Base\Module\SecurityModule::verifyCsrf(false, 'DELETE', []) === false, "verifyCsrf() debe fallar en DELETE sin token");
  $runner->assert(\Base\Module\SecurityModule::verifyCsrf(false, 'POST', ['_method' => 'PUT']) === false, "verifyCsrf() debe fallar en POST + _method=PUT sin token");

  // Con token válido en _token o header HTTP_X_CSRF_TOKEN debe aprobar
  $runner->assert(\Base\Module\SecurityModule::verifyCsrf(false, 'POST', ['_token' => $token]) === true, "verifyCsrf() debe aprobar _token válido");
  $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
  $runner->assert(\Base\Module\SecurityModule::verifyCsrf(false, 'PATCH', []) === true, "verifyCsrf() debe aprobar PATCH con X-CSRF-TOKEN válido");
  unset($_SERVER['HTTP_X_CSRF_TOKEN']);

  // Route::withoutCsrf() debe marcar exención explícita en la ruta y CsrfMiddleware respetarla
  \Core\Route::reset();
  \Core\Route::withoutCsrf()->post('/webhook-test', static function (): string {
    return 'HOOK_OK';
  });
  \Core\Route::post('/form-test', static function (): string {
    return 'FORM_OK';
  });
  $routes = \Core\Route::getRoutes();
  $hookExempt = false;
  $formExempt = false;
  foreach ($routes as $r) {
    if ($r['uri'] === '/webhook-test') {
      $hookExempt = in_array(\Base\Middleware\CsrfMiddleware::class, $r['exclude_middlewares'] ?? [], true);
    }
    if ($r['uri'] === '/form-test') {
      $formExempt = in_array(\Base\Middleware\CsrfMiddleware::class, $r['exclude_middlewares'] ?? [], true);
    }
  }
  $runner->assert($hookExempt === true && $formExempt === false, "Route::withoutCsrf() debe excluir CsrfMiddleware únicamente de esa ruta");
  \Core\Route::reset();

  // JWT AuthModule + rechazo de contraseñas en texto plano + tolerancia SEED ausente
  $rootLit = var_export(ROOT_PATH, true);
  $resJwt = $runSecSubprocess(<<<PHP
\$_ENV = ['APP_ENV' => 'testing', 'SEED' => '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef'];
\$_SERVER['APP_ENV'] = 'testing';
\$_SERVER['SEED'] = \$_ENV['SEED'];
define('ROOT_PATH', {$rootLit});
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/config.php';

// 1. Builder::login() rechaza contraseña en texto plano
\$pdo = new PDO('sqlite::memory:');
\$pdo->exec('CREATE TABLE accounts (id INTEGER PRIMARY KEY, username TEXT, password TEXT, role INTEGER, status TEXT)');
\$pdo->exec("INSERT INTO accounts (id, username, password, role, status) VALUES (1, 'plainuser', '123456', 2, 'active')");
\$hashed = password_hash('SecretPass123!', PASSWORD_BCRYPT);
\$stmt = \$pdo->prepare("INSERT INTO accounts (id, username, password, role, status) VALUES (2, 'hashuser', ?, 2, 'active')");
\$stmt->execute([\$hashed]);

\$bPlain = new \\Base\\Builder\\Builder('accounts', \$pdo);
\$plainAttempt = \$bPlain->login('password', '123456', 'plainuser', ['username'], []);
\$bHash = new \\Base\\Builder\\Builder('accounts', \$pdo);
\$hashAttempt = \$bHash->login('password', 'SecretPass123!', 'hashuser', ['username'], []);

// 2. AuthModule::login() genera JWT compacto sin password y valida con validateJWT()
\\Base\\Module\\AuthModule::login(['id' => 2, 'username' => 'hashuser', 'password' => \$hashed, 'role' => 2, 'status' => 'active']);
\$sessionUser = \\Base\\Module\\AuthModule::user();
\$jwtCookie = \$_COOKIE['auth_token'] ?? '';
\$claims = \$jwtCookie !== '' ? \\Base\\Module\\TokenModule::validateJWT(\$jwtCookie) : false;

// 3. Simular nueva petición solo con cookie auth_token + comprobar restauración por Session::start()
\$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
  session_write_close();
}
\\Base\\Module\\Session::start();
\$restoredActive = \\Base\\Module\\Session::session_active();

echo json_encode([
  'plainRejected' => is_array(\$plainAttempt) && (\$plainAttempt[0] ?? true) === false,
  'hashAccepted' => is_array(\$hashAttempt) && (\$hashAttempt[0] ?? false) === true,
  'noPassInSession' => is_array(\$sessionUser) && !array_key_exists('password', \$sessionUser),
  'jwtHasStatus' => is_array(\$claims) && (\$claims['status'] ?? '') === 'active' && !array_key_exists('password', \$claims),
  'restoredActive' => \$restoredActive,
]);
PHP);
  $jwtData = json_decode(trim($resJwt['output']), true);
  $runner->assert(is_array($jwtData), "Subproceso AuthModule/JWT debe retornar JSON válido: " . $resJwt['output']);
  $runner->assert(($jwtData['plainRejected'] ?? false) === true, "Builder::login() debe rechazar contraseñas en texto plano");
  $runner->assert(($jwtData['hashAccepted'] ?? false) === true, "Builder::login() debe aceptar password_hash válido");
  $runner->assert(($jwtData['noPassInSession'] ?? false) === true && ($jwtData['jwtHasStatus'] ?? false) === true, "Sesión y JWT no deben exponer 'password' y sí alinear 'status'");
  $runner->assert(($jwtData['restoredActive'] ?? false) === true, "Session::start() debe validar cookie JWT generada por AuthModule::login()");

  // Token corrupto con SEED ausente no debe provocar fatal en Session::start()
  $resNoSeed = $runSecSubprocess(<<<PHP
\$_ENV = ['APP_ENV' => 'production', 'SEED' => ''];
\$_SERVER['APP_ENV'] = 'production';
\$_COOKIE['auth_token'] = 'header.payload.invalid_sig';
define('ROOT_PATH', {$rootLit});
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/config.php';
\\Base\\Module\\Session::start();
echo 'SURVIVED';
PHP);
  $runner->assert(str_contains($resNoSeed['output'], 'SURVIVED'), "Cookie JWT corrupta con SEED vacío no debe lanzar error fatal 500");
});

// =========================================================================
// 25. SEGURIDAD (SEC-07 / SEC-08 / SEC-09 / SEC-13) - XSS, REDIRECTS, DD Y PERMISOS
// =========================================================================
$runner->runTest("Seguridad SEC-07/08/09/13 - TextModule, Redirects /\\, dd() prod y ausencia de 0777", function () use ($runner, $runSecSubprocess) {
  // 1. TextModule::clean() decodifica entidades ANTES de eliminar scripts/atributos
  $cleaned = \Base\Module\TextModule::clean('&lt;script&gt;alert(1)&lt;/script&gt;<p onclick="evil()">Hola</p>');
  $runner->assert(!str_contains(strtolower($cleaned), '<script') && !str_contains(strtolower($cleaned), 'onclick') && str_contains($cleaned, 'Hola'), "TextModule::clean() debe neutralizar etiquetas codificadas y atributos de evento");

  // 2. sanitizeRichHtml conserva etiqueta permitida pero elimina atributos peligrosos y <script>
  $rich = \Base\Module\SecurityModule::sanitizeRichHtml('<p onclick="alert(1)" style="position:fixed">Texto</p><script>x()</script><a href="javascript:alert(1)">Link</a>', '<p><a>');
  $runner->assert(!str_contains($rich, '<script') && !str_contains($rich, 'onclick') && !str_contains($rich, 'style=') && !str_contains($rich, 'javascript:'), "sanitizeRichHtml() debe limpiar scripts, on*, style y javascript:");

  // 3. sanitizeRedirectUrl bloquea /\, //, URLs externas y CRLF
  $runner->assert(\Base\Module\SecurityModule::sanitizeRedirectUrl('/\\evil.example') === '/', "sanitizeRedirectUrl debe bloquear /\\");
  $runner->assert(\Base\Module\SecurityModule::sanitizeRedirectUrl('%2f%5cevil.example') === '/', "sanitizeRedirectUrl debe bloquear %2f%5c");
  $runner->assert(\Base\Module\SecurityModule::sanitizeRedirectUrl('//evil.example/a') === '/', "sanitizeRedirectUrl debe bloquear protocol-relative //");
  $runner->assert(\Base\Module\SecurityModule::sanitizeRedirectUrl('https://evil.example/phish') === '/', "sanitizeRedirectUrl debe bloquear dominios externos");
  $runner->assert(!str_contains(\Base\Module\SecurityModule::sanitizeRedirectUrl("/ok\r\nSet-Cookie: a=b"), "\r"), "sanitizeRedirectUrl debe eliminar CRLF");
  $runner->assert(\Base\Module\SecurityModule::sanitizeRedirectUrl('/panel/perfil?tab=2') === '/panel/perfil?tab=2', "sanitizeRedirectUrl debe conservar rutas locales seguras");

  // 4. dd() y debugComment() sin salida en producción
  $rootLit = var_export(ROOT_PATH, true);
  $resDd = $runSecSubprocess(<<<PHP
\$_ENV = ['APP_ENV' => 'production'];
\$_SERVER['APP_ENV'] = 'production';
define('ROOT_PATH', {$rootLit});
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/config.php';
ob_start();
dd(['db_pass' => 'secret'], false);
debugComment('hidden', 'dbg');
echo ob_get_clean();
PHP);
  $runner->assert(trim($resDd['output']) === '', "dd() y debugComment() no deben imprimir nada en producción");

  // 5. Verificar ausencia de umask(0) y 0777 en Base/Module/ y Base/Exec/
  $hasPermIssue = false;
  foreach ([ROOT_PATH . '/Base/Module', ROOT_PATH . '/Base/Exec'] as $scanDir) {
    $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($scanDir, \RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($it as $file) {
      if ($file->getExtension() !== 'php') continue;
      $src = (string)file_get_contents($file->getPathname());
      if (str_contains($src, 'umask(0)') || preg_match('/\b0777\b/', $src)) {
        $hasPermIssue = true;
      }
    }
  }
  $runner->assert(!$hasPermIssue, "Base/Module y Base/Exec no deben contener umask(0) ni permisos 0777");
});

// =========================================================================
// 26. SEGURIDAD (SEC-17 / SEC-18 / SEC-19 / SEC-04) - SSRF, PROXY CIDR, JSON ATOMICO Y DEPLOY
// =========================================================================
$runner->runTest("Seguridad SEC-17/18/19/04 - SafeHttpClient IPv6, Proxy CIDR, concurrencia flock y deploy.php", function () use ($runner, $runSecSubprocess) {
  // 1. SafeHttpClient::isPublicIp y validateUrlAndResolve
  $runner->assert(\Base\Module\Security\SafeHttpClient::isPublicIp('127.0.0.1') === false, "Loopback IPv4 debe ser bloqueado");
  $runner->assert(\Base\Module\Security\SafeHttpClient::isPublicIp('169.254.169.254') === false, "Cloud Metadata IPv4 debe ser bloqueado");
  $runner->assert(\Base\Module\Security\SafeHttpClient::isPublicIp('100.64.0.1') === false, "CGNAT IPv4 debe ser bloqueado");
  $runner->assert(\Base\Module\Security\SafeHttpClient::isPublicIp('::1') === false, "Loopback IPv6 ::1 debe ser bloqueado");
  $runner->assert(\Base\Module\Security\SafeHttpClient::isPublicIp('fc00::1') === false, "ULA IPv6 fc00::/7 debe ser bloqueado");
  $runner->assert(\Base\Module\Security\SafeHttpClient::isPublicIp('fe80::1') === false, "Link-local IPv6 fe80::/10 debe ser bloqueado");
  $runner->assert(\Base\Module\Security\SafeHttpClient::isPublicIp('::ffff:127.0.0.1') === false, "IPv4-mapped IPv6 ::ffff:127.0.0.1 debe ser bloqueado");
  $runner->assert(\Base\Module\Security\SafeHttpClient::isPublicIp('8.8.8.8') === true, "IPv4 pública 8.8.8.8 debe ser aceptada");

  $ssrfMapped = \Base\Module\Security\SafeHttpClient::validateUrlAndResolve('http://[::ffff:127.0.0.1]/admin');
  $ssrfScheme = \Base\Module\Security\SafeHttpClient::validateUrlAndResolve('file:///etc/passwd');
  $runner->assert(empty($ssrfMapped['ok']) && empty($ssrfScheme['ok']), "SafeHttpClient debe bloquear esquema file:// e IP mapeada privada");

  // 2. TRUSTED_PROXIES CIDR real (sin prefix spoofing) y X-Forwarded-Proto bloqueado sin proxy confiable
  $rootLit = var_export(ROOT_PATH, true);
  $resProxy = $runSecSubprocess(<<<PHP
\$_ENV = ['APP_ENV' => 'production', 'TRUSTED_PROXIES' => '10.0.0.0/24'];
\$_SERVER['APP_ENV'] = 'production';
\$_SERVER['TRUSTED_PROXIES'] = '10.0.0.0/24';
define('ROOT_PATH', {$rootLit});
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/config.php';

// Caso A: IP externa spoofeando X-Forwarded-*
\$_SERVER['REMOTE_ADDR'] = '203.0.113.99';
\$_SERVER['HTTP_X_FORWARDED_FOR'] = '8.8.8.8';
\$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
\$ipA = \\Base\\Module\\SecurityModule::resolveClientIp();
\$httpsA = \\Base\\Module\\SecurityModule::isHttpsRequest();

// Caso B: IP fuera de 10.0.0.0/24 si TRUSTED_PROXIES fuera 10.0.0.2/32 (verificar CIDR 10.0.0.0/29)
\$match29False = \\Base\\Module\\SecurityModule::isIpInCidr('10.0.0.25', '10.0.0.0/29');
\$match29True  = \\Base\\Module\\SecurityModule::isIpInCidr('10.0.0.5', '10.0.0.0/29');

// Caso C: Proxy confiable dentro de 10.0.0.0/24 sí resuelve cliente real y HTTPS
\$_SERVER['REMOTE_ADDR'] = '10.0.0.5';
\$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.10, 10.0.0.5';
\$ipC = \\Base\\Module\\SecurityModule::resolveClientIp();
\$httpsC = \\Base\\Module\\SecurityModule::isHttpsRequest();

echo json_encode([
  'ipA' => \$ipA,
  'httpsA' => \$httpsA,
  'match29False' => \$match29False,
  'match29True' => \$match29True,
  'ipC' => \$ipC,
  'httpsC' => \$httpsC,
]);
PHP);
  $proxyData = json_decode(trim($resProxy['output']), true);
  $runner->assert(($proxyData['ipA'] ?? '') === '203.0.113.99' && ($proxyData['httpsA'] ?? true) === false, "Cliente no confiable no puede falsificar IP ni HTTPS");
  $runner->assert(($proxyData['match29False'] ?? true) === false && ($proxyData['match29True'] ?? false) === true, "isIpInCidr debe validar máscara CIDR binaria sin coincidencia de prefijo string");
  $runner->assert(($proxyData['ipC'] ?? '') === '198.51.100.10' && ($proxyData['httpsC'] ?? false) === true, "Proxy dentro de TRUSTED_PROXIES debe resolver la IP real y HTTPS");

  // 3. Concurrencia/integridad JSON atómica en AccessBlocker y AntiScraper
  for ($i = 0; $i < 25; $i++) {
    \Base\Module\Security\AccessBlocker::recordIntrusionAttempt("198.51.100." . ($i % 5), 'Test intento', 100, 3600);
    \Base\Module\Security\AntiScraper::checkScrapingRateLimit("198.51.100." . ($i % 5), 200);
  }
  $blockerJson = json_decode((string)@file_get_contents(\Base\Module\Security\AccessBlocker::getStorageFilePath()), true);
  $scraperJson = json_decode((string)@file_get_contents(\Base\Module\Security\AntiScraper::getRateStoragePath()), true);
  $runner->assert(is_array($blockerJson) && is_array($scraperJson), "AccessBlocker y AntiScraper deben preservar JSON íntegro tras escrituras repetidas");

  // 4. Plantilla deploy.php oculta huella en GET y contiene guardias ZipSlip/Symlinks
  if (!class_exists('DeployPackage', false)) {
    require_once ROOT_PATH . '/Base/ScriptComposer/DeployPackage.php';
  }
  $tpl = \DeployPackage::getDeployReceiverTemplate('abcdef0123456789abcdef0123456789');
  $runner->assert(!str_contains($tpl, 'Frame Deploy Receiver'), "deploy.php no debe exponer banner identificativo");
  $runner->assert(str_contains($tpl, 'DEPLOY_ENABLED') && str_contains($tpl, 'OPSYS_UNIX') && str_contains($tpl, 'DEPLOY_ALLOWED_IPS'), "deploy.php debe validar DEPLOY_ENABLED, symlinks Unix y lista de IPs");

  $tmpDeploy = tempnam(sys_get_temp_dir(), 'frame_deploy_tpl_') . '.php';
  file_put_contents($tmpDeploy, $tpl);
  $tmpDeployLit = var_export($tmpDeploy, true);
  $resGetDeploy = $runSecSubprocess(<<<PHP
\$_SERVER['REQUEST_METHOD'] = 'GET';
register_shutdown_function(function () {
  echo '|HTTP:' . http_response_code();
});
require {$tmpDeployLit};
PHP);
  @unlink($tmpDeploy);
  $runner->assert(str_contains($resGetDeploy['output'], '|HTTP:404') && str_contains($resGetDeploy['output'], 'Not found'), "GET /deploy.php debe responder 404 genérico");

  // 5. LoadStyle carga loader.js desde ruta pública App/Rsc/JsLoad/ y nunca desde /vendor/
  $tmpProj = ROOT_PATH . '/tests/tmp_loadstyle_test';
  @mkdir($tmpProj . '/App/Public/Min/Js', 0755, true);
  @mkdir($tmpProj . '/App/Public/Min/Css', 0755, true);
  file_put_contents($tmpProj . '/jsConfig.json', '{"functions":{}}');
  $tmpProjLit = var_export(str_replace('\\', '/', $tmpProj), true);
  $rootLit = var_export(ROOT_PATH, true);
  $resLoader = $runSecSubprocess(<<<PHP
\$_ENV = ['APP_ENV' => 'production'];
\$_SERVER['APP_ENV'] = 'production';
define('ROOT_PATH', {$tmpProjLit});
require_once {$rootLit} . '/vendor/autoload.php';
require_once {$rootLit} . '/config.php';
ob_start();
require {$rootLit} . '/Core/ConfigLoader/LoadStyle.php';
echo ob_get_clean();
PHP);
  $cleanLoadDir = function (string $d) use (&$cleanLoadDir): void {
    if (!is_dir($d)) return;
    foreach (scandir($d) ?: [] as $e) {
      if ($e === '.' || $e === '..') continue;
      $p = $d . '/' . $e;
      is_dir($p) ? $cleanLoadDir($p) : @unlink($p);
    }
    @rmdir($d);
  };
  $runner->assert(str_contains($resLoader['output'], '/App/Rsc/JsLoad/loader.js?v=') && !str_contains($resLoader['output'], '/vendor/'), "LoadStyle debe servir loader.js desde /App/Rsc/JsLoad/ y nunca desde /vendor/");

  // 6. LoadViewStyle::library() publica librerías desde vendor/ hacia App/Rsc/Library/ sin emitir URLs a /vendor/
  @mkdir($tmpProj . '/vendor/eber/framework/Resources/Library/DemoLib', 0755, true);
  file_put_contents($tmpProj . '/vendor/eber/framework/Resources/Library/DemoLib/demoLib.js', 'console.log("ok");');
  file_put_contents($tmpProj . '/vendor/eber/framework/Resources/Library/singleLib.js', 'console.log("single");');
  $resLibs = $runSecSubprocess(<<<PHP
\$_ENV = ['APP_ENV' => 'production'];
\$_SERVER['APP_ENV'] = 'production';
define('ROOT_PATH', {$tmpProjLit});
require_once {$rootLit} . '/vendor/autoload.php';
require_once {$rootLit} . '/config.php';
\$lvs = new \\Core\\ConfigLoader\\LoadViewStyle();
ob_start();
\$lvs->library('DemoLib');
\$lvs->library('singleLib');
echo ob_get_clean();
PHP);
  $runner->assert(
    str_contains($resLibs['output'], '/App/Rsc/Library/DemoLib/demoLib.js') &&
    str_contains($resLibs['output'], '/App/Rsc/Library/singleLib.js') &&
    is_file($tmpProj . '/App/Rsc/Library/DemoLib/demoLib.js') &&
    is_file($tmpProj . '/App/Rsc/Library/singleLib.js') &&
    !str_contains($resLibs['output'], '/vendor/'),
    "LoadViewStyle::library() debe autopublicar librerías de vendor/ hacia App/Rsc/Library/ y nunca emitir URLs /vendor/"
  );
  $cleanLoadDir($tmpProj);

  // 7. AccessBlocker no debe auto-bloquear loopback localhost en desarrollo ni ante falsos positivos repetidos
  $runner->assert(
    \Base\Module\Security\AccessBlocker::recordIntrusionAttempt('::1', 'Dev test', 1, 60) === false &&
    \Base\Module\Security\AccessBlocker::recordIntrusionAttempt('127.0.0.1', 'Dev test', 1, 60) === false,
    "AccessBlocker::recordIntrusionAttempt() no debe auto-bloquear 127.0.0.1 ni ::1 en desarrollo"
  );

  // 8. config.php con valores vacíos "" en .env conserva defaults seguros (CSRF_PROTECTION=true, timeouts/dimensiones válidas)
  $rootLit = var_export(ROOT_PATH, true);
  $resEmptyEnv = $runSecSubprocess(<<<PHP
\$_ENV = [
  'APP_ENV' => '',
  'CSRF_PROTECTION' => '',
  'DB_POOLING' => '',
  'DB_CONNECTION' => '',
  'MAX_IMAGE_WIDTH' => '',
  'MAX_IMAGE_HEIGHT' => '',
  'MAX_IMAGE_KB' => '0',
  'QUALITY_STEP' => '',
  'GEO_API_PRIMARY' => '',
  'GEO_REQUEST_TIMEOUT' => '',
  'IMAGE_FORMAT' => '',
];
define('ROOT_PATH', {$rootLit});
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/config.php';
echo json_encode([
  'csrf' => CSRF_PROTECTION,
  'pooling' => DB_POOLING,
  'driver' => DB_DRIVER,
  'maxW' => MAX_IMAGE_WIDTH,
  'maxKb' => MAX_IMAGE_KB,
  'step' => QUALITY_STEP,
  'geoPrimary' => GEO_API_PRIMARY,
  'geoTimeout' => GEO_REQUEST_TIMEOUT,
  'fmt' => OUTPUT_FORMAT,
  'cookieSecureHttp' => (string)ini_get('session.cookie_secure'),
]);
PHP);
  $emptyData = json_decode(trim($resEmptyEnv['output']), true);
  $runner->assert(
    is_array($emptyData) &&
    $emptyData['csrf'] === true &&
    $emptyData['pooling'] === true &&
    $emptyData['driver'] === 'mysql' &&
    $emptyData['maxW'] === 1920 &&
    $emptyData['maxKb'] === 0 &&
    $emptyData['step'] === 5 &&
    $emptyData['geoPrimary'] === 'https://ip.guide/' &&
    $emptyData['geoTimeout'] === 5 &&
    $emptyData['fmt'] === 'webp' &&
    $emptyData['cookieSecureHttp'] === '0',
    "config.php debe preservar defaults seguros aunque .env tenga cadenas vacías ''"
  );
});

// =========================================================================
// 26. FORM UI MODULE, THEME INPUTS, JIT RULES E INPUT STUDIO
// =========================================================================
$runner->runTest("FormUiModule - Renderizado de los 16 componentes, tokens, compatibilidad legacy y XSS", function () use ($runner) {
  \Base\Module\FormUiModule::resetConfig();

  // 1. Verificar constantes y configuración global/reset
  $runner->assert(count(\Base\Module\FormUiModule::COMPONENT_TYPES) === 16, "FormUiModule debe definir exactamente 16 tipos de componentes");

  \Base\Module\FormUiModule::configStyle([
    'btnPrimaryBg' => '#1E40AF',
    'btnPrimaryText' => 'textw',
    'radius' => 20,
    'touchSize' => 48,
  ]);
  $cfg = \Base\Module\FormUiModule::getGlobalConfig();
  $runner->assert($cfg['btnPrimaryBg'] === '#1E40AF' && (int)$cfg['radius'] === 20 && (int)$cfg['touchSize'] === 48, "FormUiModule::configStyle debe actualizar propiedades globales");

  // 2. Resolución segura de tokens de color (y bloqueo de inyección)
  $tokBack = \Base\Module\FormUiModule::resolveColorToken('back6');
  $runner->assert($tokBack['cssValue'] === 'var(--back-color6, #d3d3d3)', "resolveColorToken debe resolver back6 a CSS var");
  $tokHex = \Base\Module\FormUiModule::resolveColorToken('#FF5500');
  $runner->assert($tokHex['cssValue'] === '#FF5500', "resolveColorToken debe aceptar #HEX válidos");
  $tokBad = \Base\Module\FormUiModule::resolveColorToken('red; } body { background: url(javascript:alert(1)) }');
  $runner->assert($tokBad['cssValue'] === 'transparent' && !str_contains($tokBad['cssValue'], 'javascript:'), "resolveColorToken debe rechazar payloads maliciosos");

  // 3. Renderizado de los 16 componentes sin errores y con clase base .ui-input-component
  foreach (\Base\Module\FormUiModule::COMPONENT_TYPES as $compType) {
    $html = \Base\Module\FormUiModule::render($compType);
    $runner->assert(
      $html !== '' && !str_contains($html, 'not supported') && str_contains($html, 'ui-input-component'),
      "FormUiModule::render('{$compType}') debe generar markup válido"
    );
  }

  // 4. Separación Save vs Cancel + Retrocompatibilidad clásica + Escape XSS
  $cropHtml = \Base\Module\FormUiModule::buildHtmlSnippet('cropImage', [
    'name' => 'avatar" onclick="alert(1)',
    'label' => 'Foto <script>x</script>',
    'wrapContainer' => true,
    'boxImage' => 'back1 p10',
    'boxBtnImage' => 'p10 br10',
    'uiBtnPrimary' => 'back6 textw',
    'uiBtnSecondary' => 'back2 texto',
    'inlineVars' => true,
  ]);
  $runner->assert(str_contains($cropHtml, '<div class="br15 p15 ui-field-card">') && str_contains($cropHtml, '<label'), "buildHtmlSnippet debe soportar wrapContainer y label");
  $runner->assert(str_contains($cropHtml, 'box-image="back1 p10"') && str_contains($cropHtml, 'box-btn-image="p10 br10"'), "buildHtmlSnippet debe preservar atributos legacy");
  $runner->assert(str_contains($cropHtml, 'ui-btn-primary="back6 textw"') && str_contains($cropHtml, 'ui-btn-secondary="back2 texto"'), "buildHtmlSnippet debe separar zonas Guardar y Cancelar");
  $runner->assert(str_contains($cropHtml, '--ui-btn-primary-bg: #1E40AF') && str_contains($cropHtml, '--ui-touch-size: 48px'), "buildHtmlSnippet debe emitir variables --ui-* inline cuando se solicita");
  $runner->assert(!str_contains($cropHtml, '<script>') && !str_contains($cropHtml, '" onclick="alert(1)"'), "buildHtmlSnippet debe escapar atributos y etiquetas XSS");

  // 5. Helpers globales _inputToString() e _input()
  $helperStr = _inputToString('datePicker', ['name' => 'fecha_cita', 'mode' => 'range', 'showPresets' => true]);
  $runner->assert(str_contains($helperStr, 'class="date-picker ui-input-component"') && str_contains($helperStr, 'data-mode="range"'), "_inputToString debe delegar a FormUiModule");
  ob_start();
  _input('stepperInput', ['name' => 'qty', 'min' => 1, 'max' => 10]);
  $echoOut = ob_get_clean();
  $runner->assert(str_contains($echoOut, 'class="stepper-input ui-input-component"'), "_input debe imprimir HTML directo");

  \Base\Module\FormUiModule::resetConfig();
});

$runner->runTest("ThemeModule (inputs) + JIT Rules (InputRule y StylesRule touch)", function () use ($runner) {
  // 1. Persistencia y compilación de inputs en ThemeModule
  $defInputs = \Base\Module\ThemeModule::getDefaultInputsConfig();
  $runner->assert(isset($defInputs['btnPrimaryBg'], $defInputs['btnSecondaryBg'], $defInputs['touchSize']), "ThemeModule::getDefaultInputsConfig debe exponer tokens de inputs");

  $defTheme = \Base\Module\ThemeModule::getDefaultConfig();
  $runner->assert(isset($defTheme['inputs']) && (int)$defTheme['inputs']['radius'] === 15, "getDefaultConfig debe incluir sección inputs");

  $customTheme = \Base\Module\ThemeModule::getDefaultConfig('default', 'Predeterminado');
  $customTheme['inputs']['preset'] = 'pill';
  $customTheme['inputs']['radius'] = 24;
  $customTheme['inputs']['touchSize'] = 48;
  $customTheme['inputs']['btnPrimaryBg'] = '#0F766E';
  $customTheme['inputs']['localOverrides'] = ['cropImage' => ['cropShape' => 'circle']];

  $tmpDir = ROOT_PATH . '/tests/temp_ui_theme_test';
  @mkdir($tmpDir, 0777, true);
  $tmpJson = $tmpDir . '/theme.json';
  $runner->assert(\Base\Module\ThemeModule::saveConfig($customTheme, $tmpJson) === true, "ThemeModule::saveConfig debe guardar inputs");

  $reloaded = \Base\Module\ThemeModule::loadConfig($tmpJson);
  $runner->assert((int)$reloaded['inputs']['radius'] === 24 && $reloaded['inputs']['btnPrimaryBg'] === '#0F766E', "loadConfig debe persistir theme.inputs");
  $runner->assert(($reloaded['inputs']['localOverrides']['cropImage']['cropShape'] ?? '') === 'circle', "loadConfig debe preservar localOverrides de inputs");

  $compiledCss = \Base\Module\ThemeModule::compileToCss($reloaded);
  $runner->assert(str_contains($compiledCss, '--ui-radius: 24px;') && str_contains($compiledCss, '--ui-btn-primary-bg: #0F766E;'), "compileToCss debe emitir variables --ui-* en _configTheme.css");
  $runner->assert(!str_contains($compiledCss, 'cropShape') && !str_contains($compiledCss, 'localOverrides'), "compileToCss no debe filtrar claves JSON de localOverrides al CSS");

  // Recompilar sobre CSS previo no debe duplicar --ui-* ni --theme-active
  $recompiledCss = \Base\Module\ThemeModule::compileToCss($reloaded, $compiledCss);
  $runner->assert(
    substr_count($recompiledCss, '--ui-radius:') === 1 &&
    substr_count($recompiledCss, '--theme-active:') === 1,
    "compileToCss sobre CSS existente no debe duplicar --ui-* ni --theme-active"
  );

  @unlink($tmpJson);
  @rmdir($tmpDir);

  // 2. Nuevas reglas JIT: InputRule y StylesRule touch
  $inputRule = new \Base\LibraryCssJit\InputRule();
  $stylesRule = new \Base\LibraryCssJit\StylesRule();

  $runner->assert(str_contains((string)$inputRule->processWord('accent2'), 'accent-color: var(--back-color2'), "InputRule debe compilar accent2");
  $runner->assert(str_contains((string)$inputRule->processWord('focus-ring3'), ':focus-visible'), "InputRule debe compilar focus-ring3");
  $runner->assert((string)$inputRule->processWord('ui-radius-20') === ".ui-radius-20 { --ui-radius: 20px !important; }\n", "InputRule debe compilar ui-radius-20");
  $runner->assert((string)$inputRule->processWord('ui-touch-48') === ".ui-touch-48 { --ui-touch-size: 48px !important; }\n", "InputRule debe compilar ui-touch-48");

  $runner->assert(str_contains((string)$stylesRule->processWord('touch-target'), 'min-height: var(--ui-touch-size, 44px)'), "StylesRule debe compilar touch-target con --ui-touch-size");
  $runner->assert(str_contains((string)$stylesRule->processWord('touch-none'), 'touch-action: none'), "StylesRule debe compilar touch-none");
  $runner->assert(str_contains((string)$stylesRule->processWord('touch-manipulation'), 'touch-action: manipulation'), "StylesRule debe compilar touch-manipulation");
  $runner->assert(str_contains((string)$stylesRule->processWord('tap-transparent'), '-webkit-tap-highlight-color: transparent'), "StylesRule debe compilar tap-transparent");
});

$runner->runTest("Input Studio + Recursos JS/CSS - Assets autocontenidos, renderInputPayload y sin ES imports en Resources/Js", function () use ($runner) {
  // 1. Assets autocontenidos para Theme Studio
  $assets = \Base\Control\ThemeStudioControl::getInputsStudioAssets(ROOT_PATH);
  $runner->assert(!empty($assets['css']) && str_contains($assets['css'], '.ui-dp-popover') && str_contains($assets['css'], '--ui-btn-primary-bg'), "getInputsStudioAssets debe cargar CSS de formComponents con tokens --ui-*");
  $runner->assert(!empty($assets['js']) && str_contains($assets['js'], 'window.FmeUi') && str_contains($assets['js'], 'window.initFormUiComponents'), "getInputsStudioAssets debe incluir uiFormUtils e inicializador de studio");
  $runner->assert(!preg_match('/^\s*export\s+function/m', $assets['js']), "getInputsStudioAssets debe limpiar keywords export para IIFE");

  // 2. Endpoint lógico renderInputPayload (todos + individual + HTML-first snippet)
  $allPayload = \Base\Control\ThemeStudioControl::renderInputPayload([
    'type' => 'all',
    'activeInput' => 'datePicker',
    'globalConfig' => ['radius' => 18, 'btnPrimaryBg' => '#2563EB'],
    'codeOptions' => ['wrapContainer' => true, 'label' => 'Fecha', 'inlineVars' => true],
  ]);
  $runner->assert(!empty($allPayload['success']) && count($allPayload['inputs'] ?? []) === 16, "renderInputPayload('all') debe devolver los 16 componentes");
  $runner->assert(str_contains($allPayload['htmlSnippet'] ?? '', 'class="date-picker ui-input-component"'), "renderInputPayload debe devolver htmlSnippet del activeInput");
  $runner->assert(str_contains($allPayload['htmlSnippet'] ?? '', '--ui-radius: 18px'), "renderInputPayload debe aplicar globalConfig al htmlSnippet");

  // Verificación de supresión explícita de label cuando usuario desmarca Incluir <label>
  $noLabelPayload = \Base\Control\ThemeStudioControl::renderInputPayload([
    'type' => 'colorPicker',
    'localParams' => ['label' => 'Color guardado'],
    'codeOptions' => ['wrapContainer' => false, 'label' => ''],
  ]);
  $runner->assert(!empty($noLabelPayload['success']) && !str_contains($noLabelPayload['htmlSnippet'] ?? '', '<label'), "codeOptions.label vacío explícito debe omitir <label> en htmlSnippet");

  // 3. Presencia en vista ThemeStudio (con pestaña HTML Completo activa primero)
  $studioView = (string)file_get_contents(ROOT_PATH . '/Base/Views/themeStudio.php');
  $runner->assert(
    str_contains($studioView, 'id="inputSandboxView"') &&
    str_contains($studioView, 'id="tab-inputs"') &&
    str_contains($studioView, 'class="cs-code-tab active" id="iuCodeTab_html"') &&
    str_contains($studioView, 'id="ts-inputs-core-css"') &&
    str_contains($studioView, 'id="ts-inputs-core-js"') &&
    str_contains($studioView, 'id="iuInlinePreviewHost"') &&
    str_contains($studioView, 'renderFocusedHiddenPreview') &&
    str_contains($studioView, 'toggleInputTouchSim'),
    "themeStudio.php debe incluir UI / Input Studio con tab HTML Completo activa primero, vista inline de popovers/modales ocultos y simulador touch"
  );

  // 3.5 Todos los componentes con modal/popover/preview oculto deben soportar vista previa inline en Enfoque
  $runner->assert(str_contains($assets['css'], '.is-inline-preview'), "formComponents.css debe incluir reglas .is-inline-preview para vista en vivo al enfocar");
  $runner->assert(
    !str_contains($assets['css'], ':where(.ui-btn') &&
    !str_contains($assets['css'], ':where(.crop-zoom-btn)') &&
    !str_contains($assets['css'], ':where(.js-crop-img-mask)') &&
    str_contains($assets['css'], 'button.ui-btn-primary') &&
    str_contains($assets['css'], 'button.ui-btn-secondary'),
    "formComponents.css debe usar especificidad suficiente en botones/inputs para no perder ante _config-normalize.css"
  );
  $utilsSrc = (string)file_get_contents(ROOT_PATH . '/Resources/Js/uiFormUtils.js');
  $runner->assert(
    str_contains($utilsSrc, "closest('[style*=\"--ui-\"]')") && str_contains($utilsSrc, "closest('.ui-field-card')"),
    "uiFormUtils::copyUiVars debe propagar variables --ui-* entre wrapper .ui-field-card, input y portales"
  );
  foreach (['cropImage.js', 'formComponents.js', 'datePicker.js', 'timePicker.js', 'dropdown.js', 'loadImgModern.js', 'fileDropzone.js'] as $inlineCompFile) {
    $compSrc = (string)file_get_contents(ROOT_PATH . '/Resources/Js/Components/' . $inlineCompFile);
    $runner->assert(
      str_contains($compSrc, 'fme:renderInlinePreview') && str_contains($compSrc, 'is-inline-preview'),
      "Componente {$inlineCompFile} debe soportar fme:renderInlinePreview e .is-inline-preview sin afectar su apertura normal"
    );
  }

  // 4. Ningún archivo en Resources/Js o Resources/Js/Components debe tener `import ... from` (rompe MinifyModule)
  $jsFiles = array_merge(
    glob(ROOT_PATH . '/Resources/Js/*.js') ?: [],
    glob(ROOT_PATH . '/Resources/Js/Components/*.js') ?: []
  );
  $runner->assert(count($jsFiles) >= 16, "Resources/Js debe contener utilidades y componentes");
  foreach ($jsFiles as $jsFile) {
    $src = (string)file_get_contents($jsFile);
    $runner->assert(
      !preg_match('/^\s*import\s+[\s\S]*?from\s+["\']/m', $src),
      "Archivo JS no debe contener imports ES entre módulos para compatibilidad MinifyModule: " . basename($jsFile)
    );
  }

  // 5. jsConfig.json incluye uiFormUtils.js en priority y todos los componentes nuevos en functions.defer
  $jsCfg = json_decode((string)file_get_contents(ROOT_PATH . '/jsConfig.json'), true);
  $runner->assert(in_array('uiFormUtils.js', $jsCfg['priority'] ?? [], true), "jsConfig.json priority debe incluir uiFormUtils.js");
  foreach (['datePicker', 'timePicker', 'rangeSlider', 'passwordInput', 'otpCodeInput', 'tagsInput', 'stepperInput', 'ratingInput', 'fileDropzone'] as $fn) {
    $runner->assert(array_key_exists($fn, $jsCfg['functions']['defer'] ?? []), "jsConfig.json functions.defer debe registrar {$fn}");
  }
});

// Limpiar /App y carpetas de proyecto en el repositorio del framework
$deleteDir = function (string $dirPath) use (&$deleteDir): void {
  if (!is_dir($dirPath)) return;
  foreach (scandir($dirPath) ?: [] as $item) {
    if ($item === '.' || $item === '..') continue;
    $target = $dirPath . '/' . $item;
    is_dir($target) ? $deleteDir($target) : @unlink($target);
  }
  @rmdir($dirPath);
};
foreach (['App', 'Cache', 'Database', 'Logs', 'Uploads'] as $dirToClean) {
  $target = ROOT_PATH . '/' . $dirToClean;
  if (is_dir($target)) {
    $deleteDir($target);
  }
}

// =========================================================================
// FINALIZAR Y SALIDA
// =========================================================================
$success = $runner->summary();
exit($success ? 0 : 1);
