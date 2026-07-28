<?php

/**
 * Carga de rutas de la aplicación
 * 
 * Este archivo utiliza RouteLoader para cargar todos los archivos
 * de rutas de forma dinámica y recursiva.
 */

use Core\ConfigLoader\RouteLoader;

// Cargar todas las rutas del directorio /Route
RouteLoader::load(ROOT_PATH . '/App/Route');
