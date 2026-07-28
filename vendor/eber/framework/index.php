<?php

$_SERVER["POST_ID"] = null;
//$_SERVER['REMOTE_ADDR'] = "179.60.66.196";
//$_SERVER['REMOTE_ADDR'] = "181.42.18.176";//ejemplo de ip para pruebas 179.60.66.196

// Definir la raíz del proyecto antes que nada
define('ROOT_PATH', str_replace('\\', '/', __DIR__));

// Incluir el autoLoader de Composer como primer paso
require_once __DIR__ . '/vendor/autoload.php';

// Cargar configuraciones en orden
require_once __DIR__ . '/config.php';     // Primero config.php (incluye constantes de tiempo)

// Enforce Domain Logic (Solo en Producción)
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production' && defined('FORCE_DOMAIN') && !empty(FORCE_DOMAIN)) {
  $currentHost = $_SERVER['HTTP_HOST'] ?? '';
  if ($currentHost !== FORCE_DOMAIN) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $protocol . FORCE_DOMAIN . $_SERVER['REQUEST_URI']);
    exit();
  }
}

// Cargar bootstrap (providers, servicios globales)
require_once __DIR__ . '/Bootstrap/App.php';

// Iniciar el enrutador
\Core\Route::run();
