<?php

// Definir la raíz del proyecto antes que nada
define('ROOT_PATH', str_replace('\\', '/', __DIR__));

// Incluir el autoLoader de Composer como primer paso
require_once __DIR__ . '/vendor/autoload.php';

// Cargar variables de entorno del repositorio si existe .env
if (file_exists(__DIR__ . '/.env') && class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
}

// Cargar configuraciones en orden
require_once __DIR__ . '/config.php';     // Primero config.php (incluye constantes de tiempo)

// Detección HTTPS validada (solo confía en cabeceras X-Forwarded-* desde TRUSTED_PROXIES)
$isHttps = class_exists(\Base\Module\SecurityModule::class)
    ? \Base\Module\SecurityModule::isHttpsRequest()
    : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443));

// Enforce Domain Logic (Solo en Producción, fail-closed vía SecurityModule)
$isProd = class_exists(\Base\Module\SecurityModule::class)
    ? \Base\Module\SecurityModule::isProduction()
    : (defined('ENVIRONMENT') && in_array(strtolower((string)ENVIRONMENT), ['production', 'prod'], true));
if ($isProd && defined('FORCE_DOMAIN') && !empty(FORCE_DOMAIN)) {
  $currentHost = $_SERVER['HTTP_HOST'] ?? '';
  if ($currentHost !== FORCE_DOMAIN) {
    $protocol = $isHttps ? "https://" : "http://";
    $safeRequestUri = str_replace(["\r", "\n", "%0d", "%0a", "%0D", "%0A"], '', (string)($_SERVER['REQUEST_URI'] ?? '/'));
    $safeRequestUri = '/' . ltrim($safeRequestUri, '/');
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $protocol . FORCE_DOMAIN . $safeRequestUri);
    exit();
  }
}

// Cargar bootstrap (providers, servicios globales)
require_once __DIR__ . '/Bootstrap/App.php';

// Iniciar el enrutador
\Core\Route::run();
