<?php

/**
 * Configuración de la aplicación.
 * Este archivo carga las variables de entorno y la configuración del framework.
 */

use Dotenv\Dotenv;

// Ruta base del proyecto
$basePath = dirname(__DIR__, 2);

// Cargar configuración compilada + Vault cifrado si no existe .env, o .env en desarrollo/legacy
if (
    !file_exists($basePath . '/.env')
    && class_exists(\Base\Module\Security\ConfigVault::class)
    && \Base\Module\Security\ConfigVault::hasCompiledProduction($basePath)
) {
    \Base\Module\Security\ConfigVault::boot($basePath);
} elseif (file_exists($basePath . '/.env')) {
    $dotenv = Dotenv::createImmutable($basePath);
    $dotenv->load();
}
// Si no existe en la raíz, intentar desde el framework
elseif (file_exists($basePath . '/vendor/eber/framework/.env')) {
    $dotenv = Dotenv::createImmutable($basePath . '/vendor/eber/framework');
    $dotenv->load();
}

// Cargar configuración del framework
if (file_exists($basePath . '/vendor/eber/framework/config.php')) {
    require_once $basePath . '/vendor/eber/framework/config.php';
} elseif (file_exists($basePath . '/config.php')) {
    require_once $basePath . '/config.php';
}
