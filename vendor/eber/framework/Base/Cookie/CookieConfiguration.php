<?php

use Base\Module\CookieModule;

// Intentar cargar la configuración de cookies del proyecto local (App/Config/CookieConfiguration.php)
$localCookieConfig = null;
if (defined('ROOT_PATH')) {
    $localCookieConfig = ROOT_PATH . '/App/Config/CookieConfiguration.php';
} else {
    // Fallback por estructura de directorios o directorio de trabajo
    $dirFallback = dirname(__DIR__, 5) . '/App/Config/CookieConfiguration.php';
    if (file_exists($dirFallback)) {
        $localCookieConfig = $dirFallback;
    } else {
        $cwdFallback = getcwd() . '/App/Config/CookieConfiguration.php';
        if (file_exists($cwdFallback)) {
            $localCookieConfig = $cwdFallback;
        }
    }
}

if ($localCookieConfig && file_exists($localCookieConfig)) {
    require_once $localCookieConfig;
} elseif (php_sapi_name() !== 'cli') {
    // Si no existe configuración en el proyecto local, se cargan las cookies por defecto del framework
    $expired = defined('TIME_DAY') ? TIME_DAY : 86400;
    $cookieVisit = CookieModule::set("visitReg", ["expired" => $expired]);
}
