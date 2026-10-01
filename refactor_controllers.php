<?php

$files = [
    'App/Controllers/DesignControllers.php',
    'App/Controllers/LemonSqueezyControllers.php',
    'App/Controllers/Dashboard/DashboardControllers.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    $content = preg_replace('/\\$_SESSION\["user"\]\s*\?\?\s*\[\]/', '\Base\Module\Session::user_session_show() ?: []', $content);
    $content = preg_replace('/\\$_SESSION\["user"\]\s*\?\?\s*false/', '\Base\Module\Session::user_session_show() ?: false', $content);
    $content = preg_replace('/\\$_GET\["([^"]+)"\]\s*\?\?\s*\\$_POST\["([^"]+)"\]\s*\?\?\s*(.*?)(?=\))/', '\Base\Module\SecurityModule::get("$1", \Base\Module\SecurityModule::post("$2", $3))', $content);
    $content = preg_replace('/\\$_SERVER\[\'HTTP_CF_CONNECTING_IP\'\]\s*\?\?\s*\\$_SERVER\[\'HTTP_X_FORWARDED_FOR\'\]\s*\?\?\s*\\$_SERVER\[\'REMOTE_ADDR\'\]\s*\?\?\s*\'127\.0\.0\.1\'/', '\Base\Module\VisitModule::getClientIp()', $content);
    
    $content = str_replace('$_GET ?? []', '\Base\Module\SecurityModule::sanitizeArray($_GET ?? [])', $content);
    $content = str_replace('$_POST ?? []', '\Base\Module\SecurityModule::sanitizeArray($_POST ?? [])', $content);
    $content = str_replace('$_SERVER["REQUEST_METHOD"]', '\Base\Module\SecurityModule::sanitize($_SERVER["REQUEST_METHOD"] ?? "GET")', $content);
    $content = str_replace('$_SERVER["HTTP_ACCEPT"]', '\Base\Module\SecurityModule::sanitize($_SERVER["HTTP_ACCEPT"] ?? "")', $content);

    $content = preg_replace('/\\$_SERVER\["HTTP_X_SIGNATURE"\]\s*\?\?\s*\\$_SERVER\["HTTP_X_LEMON_SQUEEZY_SIGNATURE"\]\s*\?\?\s*""/', '\Base\Module\SecurityModule::sanitize($_SERVER["HTTP_X_SIGNATURE"] ?? $_SERVER["HTTP_X_LEMON_SQUEEZY_SIGNATURE"] ?? "")', $content);

    file_put_contents($file, $content);
}

// Fix models
$models = [
    'App/Models/VisitModels.php',
    'App/Models/LemonSqueezyModels.php',
    'App/Models/DesignModels.php'
];

foreach ($models as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $content = str_replace('$_SERVER["REMOTE_ADDR"]', '\Base\Module\VisitModule::getClientIp()', $content);
    $content = preg_replace('/\\$_SESSION\["premium"\]\s*\?\?\s*\\$_SESSION\["user"\]\["premium"\]\s*\?\?\s*null/', '\Base\Module\Session::session_data("premium") ?? (\Base\Module\Session::user_session_show()["premium"] ?? null)', $content);
    file_put_contents($file, $content);
}

// Fix middlewares
$middlewares = [
    'App/Middleware/CsrfMiddleware.php',
    'App/Middleware/DashboardMiddleware.php',
    'App/Middleware/VisitMiddleware.php'
];

foreach ($middlewares as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $content = str_replace('$_SERVER["REQUEST_URI"]', '\Base\Module\SecurityModule::sanitize($_SERVER["REQUEST_URI"] ?? "")', $content);
    $content = str_replace('$_SERVER[\'REQUEST_METHOD\']', '\Base\Module\SecurityModule::sanitize($_SERVER["REQUEST_METHOD"] ?? "GET")', $content);
    $content = str_replace('$_SERVER[\'REQUEST_URI\']', '\Base\Module\SecurityModule::sanitize($_SERVER["REQUEST_URI"] ?? "")', $content);
    file_put_contents($file, $content);
}

echo "Done\n";
