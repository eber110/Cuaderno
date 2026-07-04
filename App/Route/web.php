<?php

use App\Controllers\HomeControllers;
use App\Controllers\LoginControllers;
use App\Controllers\UserControllers;
use App\Middleware\AuthMiddleware;
use App\Middleware\DashboardMiddleware;
use App\Models\UserModels;
use Base\Module\LogModule;
use Base\Module\ResponseModule;
use Base\Module\Session;
use Core\Route;

#este grupo de paginas son solo para el usuario logueado
Route::prefix("/panel")->middleware([DashboardMiddleware::class])->group(function(){

  Route::get("/:user", function($user){

    print "<a href='/salir'><h1>Salir</h1></a>";
    //$userSession = Session::session_data("username");
    //if ($userSession !== $user) {
    //  return ResponseModule::redirect("/{$userSession}", "No puedes acceder a otra cuenta");
    //}

    $log = LogModule::readLogLines("/Cache/UserData/{$user}.json");

    if ($log) {
      print "<a href='/{$user}'><h1>Ir al perfil de {$user}</h1></a>";
    }

    //$userData = new UserModels();
    //$userData = $userData->dataUser($user);

    //$existsUser = new UserModels;
    //$existsUser = $existsUser->userExists($user);

    //var_dump($existsUser);
    //var_dump($userData);
    var_dump($_SESSION ?? null);

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
  Route::post("/registrar", [LoginControllers::class, "processRegister"]);
  #estas rutas chequean el usuario y el email para registrar los usuarios
  Route::post("/registrar/check-username", [LoginControllers::class, "checkUsername"]);
  Route::post("/registrar/check-email", [LoginControllers::class, "checkEmail"]);

});

//LoginController: métodos de registro y validación de ingreso de usuarios
Route::get("/salir", [LoginControllers::class, "exitApp"]);

//UserControllers: Datos y personalización de los datos de usuario
Route::get("/:user", [UserControllers::class, "userPage"]);