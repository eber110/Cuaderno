<?php

// Determinar la raíz del proyecto de forma dinámica
if (!defined('ROOT_PATH')) {
    $rootPath = str_replace('\\', '/', getcwd());
    define('ROOT_PATH', $rootPath);
}

// Cargar variables de entorno desde la raíz del proyecto
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->safeLoad();
