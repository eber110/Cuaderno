<?php

use App\Controllers\Dashboard\DashboardControllers;
use App\Controllers\HomeControllers;
use App\Controllers\LoginControllers;
use App\Controllers\UserControllers;
use App\Middleware\AuthMiddleware;
use App\Middleware\DashboardMiddleware;
use Base\Module\ImgProcessModule;
use Core\Route;

#este grupo de paginas son solo para el usuario logueado
Route::prefix("/panel/:user")->middleware([DashboardMiddleware::class])->group(function(){

  Route::get("/", [DashboardControllers::class, "panel"]);

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

//UserControllers: Datos y personalización de los datos de usuario
Route::get("/:user", [UserControllers::class, "userPage"]);

Route::get("/op/image", function(){
  // Optimizamos las imágenes de /App/Public/Img/ y las guardamos en /App/Public/Img/Optimized/ en formato WebP a un máximo de 50 KB
  $result = ImgProcessModule::optimizeDirectoryImages(
    ROOT_PATH . '/App/Public/Img/', 
    ROOT_PATH . '/App/Public/Img/Optimized/', 
    50, 
    'webp', 
    false
  );
  header('Content-Type: application/json');
  echo json_encode($result, JSON_PRETTY_PRINT);
  exit;
});