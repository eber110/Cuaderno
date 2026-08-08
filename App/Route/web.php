<?php

use App\Controllers\Dashboard\DashboardControllers;
use App\Controllers\DesignControllers;
use App\Controllers\HomeControllers;
use App\Controllers\LoginControllers;
use App\Controllers\UserControllers;
use App\Middleware\AuthMiddleware;
use App\Middleware\DashboardMiddleware;
use App\Middleware\VisitMiddleware;
use Base\Module\ImgProcessModule;
use Base\Module\SeoModule;
use Core\Route;

#Configuración SEO
Route::get("/robots.txt", function(){ SeoModule::robots([],["llms.txt"]); });
Route::get("/sitemap.xml", function(){ SeoModule::sitemap(["/salir", "/op/image", "/op/check", "/test/2"],['/' => ['priority' => 1.0, 'changefreq' => 'daily']]); });
Route::get("/llms.txt", function(){ SeoModule::llms([
    "title"    => "Mi Proyecto Web",
    "summary"  => "Aplicación web optimizada construida con Eber-Framework (Software propietario).",
    "details"  => "Este sitio proporciona herramientas de gestión y perfil de usuario.",
    'sections' => [
      'Recursos Externos' => [
        [
          "title"       => "Repositorio GitHub",
          "url"         => "https://github.com/eber110/Cuaderno",
          "description" => "Código fuente oficial."
        ]
      ]
    ]
  ])
;});

#este grupo de paginas son solo para el usuario logueado
Route::prefix("/panel/:user")->middleware([DashboardMiddleware::class])->group(function(){

  Route::get("/", [DashboardControllers::class, "panel"]);
  Route::get("/diseno", function(string $user) {
    $userClean = mb_strtolower($user, "UTF-8");
    return \Base\Module\ResponseModule::redirect("/panel/{$userClean}");
  });
  Route::post("/diseno", [DesignControllers::class, "configDesign"]);
  Route::get("/guardar", [DesignControllers::class, "saveDesign"]);
  Route::post("/guardar", [DesignControllers::class, "saveDesign"]);
  Route::get("/simular-datos", [\App\Controllers\StatisticsControllers::class, "generateTestData"]);
  Route::post("/simular-datos", [\App\Controllers\StatisticsControllers::class, "generateTestData"]);

});

#estas paginas no las puedo ver si estoy logueado (No las debe ver el usuario logueado)
Route::middleware([AuthMiddleware::class])->group(function(){

  //HomeControllers: pagina de entrada de la aplicación
  Route::get("/", [HomeControllers::class, "home"]);
  
  //LoginController: métodos de registro y validación de ingreso de usuarios
  Route::get("/ingresar", [LoginControllers::class, "login"]);
  Route::post("/ingresar", [LoginControllers::class, "processLogin"]);
  Route::get("/registrar", [LoginControllers::class, "register"]);
  Route::post("/registrar", [LoginControllers::class, "processRegister"]);
  #estas rutas chequean el usuario y el email para registrar los usuarios
  Route::post("/registrar/check-username", [LoginControllers::class, "checkUsername"]);
  Route::post("/registrar/check-email", [LoginControllers::class, "checkEmail"]);

});

//LoginController: métodos de registro y validación de ingreso de usuarios
Route::get("/salir", [LoginControllers::class, "exitApp"]);

//Proxy global
Route::get("/proxy", function() {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $url = '';
    $matches = '';
    
    // Extraer todo lo que viene después de 'url=' para evitar que parámetros como &t= de LinkedIn se pierdan 
    // si el frontend olvidó hacer encodeURIComponent().
    if (preg_match('/[?&]url=(.*)$/', $requestUri, $matches)) {
        $url = urldecode($matches[1]);
    } else {
        $url = $_GET['url'] ?? '';
    }

    if ($url) {
        \Base\Module\ProxyModule::proxyImage($url, ['licdn.com', 'linkedin.com', 'githubusercontent.com', 'ebersanchez.cl']);
    }
});

//UserControllers: Datos y personalización de los datos de usuario
Route::middleware([VisitMiddleware::class])->group(function(){
  Route::get("/:user", [UserControllers::class, "userPage"]);
});

Route::get("/op/image", function(){
  // Optimizamos las imágenes de /App/Public/Img/ y las guardamos en /App/Public/Img/Optimized/ en formato WebP a un máximo de 50 KB
  $result = ImgProcessModule::optimizeDirectoryImages(
    ROOT_PATH . '/App/Public/Img/Custom/', 
    ROOT_PATH . '/App/Public/Img/Custom/', 
    40, 
    'webp', 
    false
  );
  header('Content-Type: application/json');
  echo json_encode($result, JSON_PRETTY_PRINT);
  exit;
});

Route::get("/op/check", function(){
  header('Content-Type: application/json');
  echo json_encode([
    'extension_loaded_imagick' => extension_loaded('imagick'),
    'class_exists_Imagick' => class_exists('Imagick'),
    'php_version' => PHP_VERSION,
    'ini_path' => php_ini_loaded_file(),
  ], JSON_PRETTY_PRINT);
  exit;
});

Route::post("/op/track-click", function(){
  header('Content-Type: application/json');
  $rawInput = file_get_contents('php://input');
  $input = json_decode($rawInput, true) ?: $_POST;

  $user   = $input['user'] ?? '';
  $linkId = $input['linkId'] ?? '';

  $success = \App\Models\VisitModels::processClick($user, $linkId);
  echo json_encode(['success' => $success]);
  exit;
});

Route::post("/op/active-viewers", function(){
  header('Content-Type: application/json');
  $rawInput = file_get_contents('php://input');
  $input = json_decode($rawInput, true) ?: $_POST;

  $user  = mb_strtolower(trim($input['user'] ?? $_GET['user'] ?? ''), 'UTF-8');
  $token = trim($input['token'] ?? $_GET['token'] ?? '');

  if (empty($user)) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
  }

  if (session_status() === PHP_SESSION_NONE) {
    @session_start();
  }

  if (empty($token)) {
    $token = $_SESSION['viewer_token'] ?? null;
    if (empty($token)) {
      $token = bin2hex(random_bytes(16));
      $_SESSION['viewer_token'] = $token;
    }
  }

  $count = \App\Rsc\Helper\ActiveViewersHelper::registerHeartbeat($user, $token);
  echo json_encode(['success' => true, 'count' => $count, 'token' => $token]);
  exit;
});

Route::get("/op/active-viewers", function(){
  header('Content-Type: application/json');
  $user  = mb_strtolower(trim($_GET['user'] ?? ''), 'UTF-8');
  $token = trim($_GET['token'] ?? '');

  if (empty($user)) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
  }

  if (session_status() === PHP_SESSION_NONE) {
    @session_start();
  }

  if (empty($token)) {
    $token = $_SESSION['viewer_token'] ?? null;
    if (empty($token)) {
      $token = bin2hex(random_bytes(16));
      $_SESSION['viewer_token'] = $token;
    }
  }

  $count = \App\Rsc\Helper\ActiveViewersHelper::registerHeartbeat($user, $token);
  echo json_encode(['success' => true, 'count' => $count, 'token' => $token]);
  exit;
});