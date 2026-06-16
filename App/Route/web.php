<?php

use App\Controllers\HomeControllers;
use App\Controllers\LoginControllers;
use App\Controllers\UserControllers;
use App\Middleware\AuthMiddleware;
use App\Middleware\DashboardMiddleware;
use Base\Module\ResponseModule;
use Base\Module\Session;
use Core\Route;

#este grupo de paginas son solo para el usuario logueado
Route::prefix("/configuracion")->middleware([DashboardMiddleware::class])->group(function(){

  Route::get("/:user", function($user){
    //$userSession = Session::session_data("username");
    //if ($userSession !== $user) {
    //  return ResponseModule::redirect("/{$userSession}", "No puedes acceder a otra cuenta");
    //}
    echo "Hola ". $user;
  });

});

#estas paginas no las puedo ver si estoy logueado (No las debe ver el usuario logueado)
Route::middleware([AuthMiddleware::class])->group(function(){

  //HomeControllers: pagina de entrada de la aplicación
  Route::get("/", [HomeControllers::class, "home"]);
  
  //LoginController: métodos de registro y validación de ingreso de usuarios
  Route::get("/ingresar", [LoginControllers::class, "login"]);
  Route::post("/ingresar", [LoginControllers::class, "processLogin"]);
  Route::get("/registrar", [LoginControllers::class, "register"]);
  #estas rutas chequean el usuario y el email para registrar los usuarios
  Route::post("/registrar/check-username", [LoginControllers::class, "checkUsername"]);
  Route::post("/registrar/check-email", [LoginControllers::class, "checkEmail"]);

});

//LoginController: métodos de registro y validación de ingreso de usuarios
Route::get("/salir", [LoginControllers::class, "exitApp"]);

//UserControllers: Datos y personalización de los datos de usuario
Route::get("/:user", [UserControllers::class, "userPage"]);