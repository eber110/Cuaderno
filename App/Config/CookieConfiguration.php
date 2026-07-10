<?php

use Base\Module\CookieModule;

/**
 * Creación de cookies del sitio.
 * En esta sección se configura todas las cookies del sitio 
 * para que estén disponibles al inicio de la aplicación.
 */
$cookie = CookieModule::set("NombreDeLaCookie", ["expired" => TIME_YEAR]);