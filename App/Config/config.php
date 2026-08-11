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

// Configuración de Lemon Squeezy
if (!defined('LEMON_SQUEEZY_API_KEY')) {
    define('LEMON_SQUEEZY_API_KEY', $_ENV['LEMON_SQUEEZY_API_KEY'] ?? getenv('LEMON_SQUEEZY_API_KEY') ?: '');
}
if (!defined('LEMON_SQUEEZY_STORE_ID')) {
    define('LEMON_SQUEEZY_STORE_ID', $_ENV['LEMON_SQUEEZY_STORE_ID'] ?? getenv('LEMON_SQUEEZY_STORE_ID') ?: '');
}
if (!defined('LEMON_SQUEEZY_WEBHOOK_SECRET')) {
    define('LEMON_SQUEEZY_WEBHOOK_SECRET', $_ENV['LEMON_SQUEEZY_WEBHOOK_SECRET'] ?? getenv('LEMON_SQUEEZY_WEBHOOK_SECRET') ?: '');
}
if (!defined('LEMON_SQUEEZY_MODE')) {
    define('LEMON_SQUEEZY_MODE', $_ENV['LEMON_SQUEEZY_MODE'] ?? getenv('LEMON_SQUEEZY_MODE') ?: 'test');
}
if (!defined('LEMON_SQUEEZY_BUY_URL')) {
    define('LEMON_SQUEEZY_BUY_URL', $_ENV['LEMON_SQUEEZY_BUY_URL'] ?? getenv('LEMON_SQUEEZY_BUY_URL') ?: 'https://clikhub.lemonsqueezy.com/checkout/buy/e2ba4ce6-2307-4d5e-b965-47b519aca9de');
}


// Cargar configuración del framework
require_once $basePath . '/vendor/eber/framework/config.php';