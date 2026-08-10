<?php

// Buscar el autoloader de Composer en el proyecto o framework
if (file_exists(getcwd() . '/vendor/autoload.php')) {
  require_once getcwd() . '/vendor/autoload.php';
} else {
  require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
}

$basePath = str_replace('\\', '/', getcwd());
if (!defined('ROOT_PATH')) {
  define('ROOT_PATH', $basePath);
}

use Core\ConfigLoader\RouteLoader;
use Base\Module\Security\RouteScanner;

echo "🔍 Escaneando rutas de la aplicación en /App/Route...\n";

// Cargar todas las rutas registradas en App/Route del proyecto actual
$routeDir = $basePath . '/App/Route';
if (is_dir($routeDir)) {
  RouteLoader::load($routeDir);
}

$outputPath = $basePath . '/App/Safety/routes_security.json';
$map = RouteScanner::scanAndSaveRoutes($outputPath);

$staticCount = count($map['static_routes'] ?? []);
$dynamicCount = count($map['dynamic_routes'] ?? []);
$total = $map['total_routes'] ?? 0;

echo "✅ Mapa de rutas escaneado exitosamente:\n";
echo "   - Directorio de rutas: {$routeDir}\n";
echo "   - Total de rutas: {$total}\n";
echo "   - Rutas estáticas: {$staticCount}\n";
echo "   - Rutas dinámicas (con variables a proteger en controlador): {$dynamicCount}\n";
echo "   - Archivo generado: App/Safety/routes_security.json\n";
