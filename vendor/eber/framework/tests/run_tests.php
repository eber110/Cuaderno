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
