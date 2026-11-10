<?php

use Core\Route;

/**
 * Definición de rutas del proyecto.
 */

Route::get('/', function () {
  return function_exists('__') ? e(__('global.welcome')) : 'Bienvenido a mi aplicación';
});
