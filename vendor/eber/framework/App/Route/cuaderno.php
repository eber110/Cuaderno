<?php

use Core\Route;
use App\Controllers\CuadernoController;

/**
 * Definición de rutas del módulo Cuaderno de Notas.
 */
Route::get('/cuaderno', [CuadernoController::class, 'index']);
Route::post('/cuaderno/guardar', [CuadernoController::class, 'guardar']);
Route::get('/cuaderno/favorita/{id}', [CuadernoController::class, 'toggleFavorite']);
Route::get('/cuaderno/fijar/{id}', [CuadernoController::class, 'togglePin']);
Route::get('/cuaderno/eliminar/{id}', [CuadernoController::class, 'eliminar']);
