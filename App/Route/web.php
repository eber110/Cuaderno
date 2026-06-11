<?php

use App\Controllers\HomeControllers;
use App\Controllers\LoginControllers;
use App\Controllers\UserControllers;
use Core\Route;

//HomeControllers: pagina de entrada de la aplicación
Route::get("/", [HomeControllers::class, "home"]);

//LoginController: métodos de registro y validación de ingreso de usuarios
Route::get("/ingresar", [LoginControllers::class, "login"]);
Route::post("ingresar", [LoginControllers::class, "processLogin"]);
Route::get("/registrar", [LoginControllers::class, "register"]);
Route::post("/registrar", [LoginControllers::class, "processRegister"]);

//UserControllers: Datos y personalización de los datos de usuario
Route::get("/:user", [UserControllers::class, "userPage"]);