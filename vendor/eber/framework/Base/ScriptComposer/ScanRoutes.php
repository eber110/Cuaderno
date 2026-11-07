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

// En el repositorio del framework no existe ni se genera /App
$composerJsonPath = $basePath . '/composer.json';
if (file_exists($composerJsonPath)) {
  $composerData = json_decode((string)file_get_contents($composerJsonPath), true);
  if (($composerData['name'] ?? '') === 'eber/framework') {
    echo "ℹ️  Repositorio del framework ('eber/framework') detectado.\n";
    echo "   No se escanean rutas de /App en el repositorio del framework (solo en proyectos derivados).\n";
    exit(0);
  }
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
