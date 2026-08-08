<?php

/**
 * Configuración de la aplicación.
 * Este archivo carga las variables de entorno y la configuración del framework.
 */

use Dotenv\Dotenv;

// Ruta base del proyecto
$basePath = dirname(__DIR__, 2);

// Intentar cargar .env desde la raíz del proyecto
if (file_exists($basePath . '/.env')) {
    $dotenv = Dotenv::createImmutable($basePath);
    $dotenv->load();
}
// Si no existe en la raíz, intentar desde el framework
elseif (file_exists($basePath . '/vendor/eber/framework/.env')) {
    $dotenv = Dotenv::createImmutable($basePath . '/vendor/eber/framework');
    $dotenv->load();
}

// Configuración de timeouts de geolocalización para evitar bloqueos si la API externa no responde
if (!defined('GEO_CONNECT_TIMEOUT')) {
    define('GEO_CONNECT_TIMEOUT', 1);
}
if (!defined('GEO_REQUEST_TIMEOUT')) {
    define('GEO_REQUEST_TIMEOUT', 1);
}

// Cargar configuración del framework
require_once $basePath . '/vendor/eber/framework/config.php';