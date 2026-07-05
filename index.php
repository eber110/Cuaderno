<?php

define('ROOT_PATH', str_replace('\\', '/', __DIR__));

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/App/Config/config.php';

if (defined('ENVIRONMENT') && ENVIRONMENT === 'production' && defined('FORCE_DOMAIN') && !empty(FORCE_DOMAIN)) {
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    if ($currentHost !== FORCE_DOMAIN) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $protocol . FORCE_DOMAIN . $_SERVER['REQUEST_URI']);
        exit();
    }
}

require_once __DIR__ . '/Bootstrap/App.php';

\Core\Route::run();