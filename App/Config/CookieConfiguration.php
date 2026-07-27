<?php

use Base\Module\CookieModule;

/**
 * Creación de cookies del sitio.
 * En esta sección se configuran todas las cookies del sitio 
 * para que estén disponibles al inicio de la aplicación.
 */
$cookieVisit = CookieModule::set("Visit_registration", ["expired" => 3600]);