<?php

define('ROOT_PATH', str_replace('\\', '/', __DIR__));

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/App/Config/config.php';

// Detección HTTPS robusta considerando Proxies / Cloudflare / Load Balancers
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on');

if (defined('ENVIRONMENT') && in_array(strtolower((string)ENVIRONMENT), ['production', 'prod'], true) && defined('FORCE_DOMAIN') && !empty(FORCE_DOMAIN)) {
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    if ($currentHost !== FORCE_DOMAIN) {
        $protocol = $isHttps ? "https://" : "http://";
        header("HTTP/1.1 301 Moved Permanently");
        $safeRequestUri = str_replace(["\r", "\n", "%0d", "%0a", "%0D", "%0A"], '', (string)($_SERVER['REQUEST_URI'] ?? '/'));
        $safeRequestUri = '/' . ltrim($safeRequestUri, '/');
        header("Location: " . $protocol . FORCE_DOMAIN . $safeRequestUri);
        exit();
    }
}

require_once __DIR__ . '/Bootstrap/App.php';

\Core\Route::run();