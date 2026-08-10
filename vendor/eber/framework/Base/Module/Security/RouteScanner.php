<?php

namespace Base\Module\Security;

use Core\Route;

/**
 * Escáner y Analizador de Seguridad de Rutas (SRP).
 * 
 * Responsabilidad Única: Inspeccionar las rutas registradas en Core\Route,
 * clasificar rutas estáticas y dinámicas (:var), generar patrones de emparejamiento regex
 * y guardar la configuración en App/Config/routes_security.json de cada proyecto.
 */
class RouteScanner
{
  /**
   * Obtiene la ruta al archivo JSON de rutas de seguridad del proyecto.
   */
  public static function getDefaultConfigPath(): string
  {
    if (defined('ROUTE_SAFETY')) {
      return ROUTE_SAFETY . 'routes_security.json';
    }
    if (defined('ROOT_PATH')) {
      return ROOT_PATH . '/App/Safety/routes_security.json';
    }
    return str_replace('\\', '/', getcwd()) . '/App/Safety/routes_security.json';
  }

  /**
   * Escanea las rutas registradas en la aplicación y genera el mapa estructurado.
   *
   * @param string|null $outputPath Ruta personalizada para guardar el JSON.
   * @return array
   */
  public static function scanAndSaveRoutes(?string $outputPath = null): array
  {
    $allRoutes = Route::getRoutes();
    if (empty($allRoutes)) {
      $routeDir = defined('ROOT_PATH') ? ROOT_PATH . '/App/Route' : getcwd() . '/App/Route';
      if (is_dir($routeDir) && class_exists('\\Core\\ConfigLoader\\RouteLoader')) {
        \Core\ConfigLoader\RouteLoader::load($routeDir);
        $allRoutes = Route::getRoutes();
      }
    }

    $processedMap = [
      'generated_at' => date('Y-m-d H:i:s'),
      'total_routes' => count($allRoutes),
      'static_routes' => [],
      'dynamic_routes' => [],
      'summary_by_method' => []
    ];

    foreach ($allRoutes as $route) {
      $method = strtoupper($route['method'] ?? 'GET');
      $uri = $route['uri'] ?? '/';
      $action = self::formatAction($route['action'] ?? null);
      $middlewares = $route['middlewares'] ?? [];

      if (!isset($processedMap['summary_by_method'][$method])) {
        $processedMap['summary_by_method'][$method] = 0;
      }
      $processedMap['summary_by_method'][$method]++;

      // Detectar si la URI contiene variables (ej. :id, :slug)
      preg_match_all('/:([a-zA-Z0-9_]+)/', $uri, $matches);
      $variables = $matches[1] ?? [];
      $hasVariables = !empty($variables);

      if ($hasVariables) {
        $regexPattern = preg_replace('/:([a-zA-Z0-9_]+)/', '(?P<$1>[^/]+)', $uri);
        $regexPattern = '#^' . $regexPattern . '$#u';

        $processedMap['dynamic_routes'][] = [
          'method' => $method,
          'uri' => $uri,
          'pattern' => $regexPattern,
          'has_variables' => true,
          'variables' => $variables,
          'protect_at_controller' => true,
          'controller_notice' => 'Esta ruta contiene variables dinámicas en la URL. Se debe validar y sanitizar el tipo y formato de los parámetros a nivel de controlador.',
          'action' => $action,
          'middlewares' => $middlewares
        ];
      } else {
        $processedMap['static_routes'][] = [
          'method' => $method,
          'uri' => $uri,
          'has_variables' => false,
          'variables' => [],
          'protect_at_controller' => false,
          'action' => $action,
          'middlewares' => $middlewares
        ];
      }
    }

    $targetFile = $outputPath ?: self::getDefaultConfigPath();
    $dir = dirname($targetFile);
    if (!is_dir($dir)) {
      @mkdir($dir, 0755, true);
    }

    $jsonContent = json_encode($processedMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents($targetFile, $jsonContent);

    return $processedMap;
  }

  /**
   * Carga el mapa de rutas guardado desde App/Config/routes_security.json.
   *
   * @param string|null $configPath
   * @return array
   */
  public static function getSecurityRoutesConfig(?string $configPath = null): array
  {
    $file = $configPath ?: self::getDefaultConfigPath();

    if (!file_exists($file)) {
      return self::scanAndSaveRoutes($file);
    }

    $content = file_get_contents($file);
    $data = json_decode($content, true);

    return is_array($data) ? $data : [];
  }

  /**
   * Compara una URI y método HTTP contra la lista de rutas válidas.
   *
   * @param string $uri
   * @param string $method
   * @return array|null
   */
  public static function matchUriAgainstSecurityConfig(string $uri, string $method = 'GET'): ?array
  {
    $config = self::getSecurityRoutesConfig();
    $method = strtoupper($method);

    $normalizedUri = trim($uri, '/');
    $normalizedUri = ($normalizedUri === '') ? '/' : '/' . $normalizedUri;

    // 1. Estáticas
    if (isset($config['static_routes']) && is_array($config['static_routes'])) {
      foreach ($config['static_routes'] as $route) {
        if (strtoupper($route['method']) === $method && $route['uri'] === $normalizedUri) {
          return $route;
        }
      }
    }

    // 2. Dinámicas
    if (isset($config['dynamic_routes']) && is_array($config['dynamic_routes'])) {
      foreach ($config['dynamic_routes'] as $route) {
        if (strtoupper($route['method']) === $method && !empty($route['pattern'])) {
          if (preg_match($route['pattern'], $normalizedUri)) {
            return $route;
          }
        }
      }
    }

    return null;
  }

  /**
   * Verifica si una URI y método son válidos.
   *
   * @param string $uri
   * @param string $method
   * @return bool
   */
  public static function isPathValid(string $uri, string $method = 'GET'): bool
  {
    return self::matchUriAgainstSecurityConfig($uri, $method) !== null;
  }

  /**
   * Determina si la URI solicitada es una ruta dinámica con variables.
   *
   * @param string $uri
   * @param string $method
   * @return bool
   */
  public static function isDynamicRoute(string $uri, string $method = 'GET'): bool
  {
    $matched = self::matchUriAgainstSecurityConfig($uri, $method);
    return $matched !== null && !empty($matched['protect_at_controller']);
  }

  /**
   * Formatea la acción de la ruta para serialización en JSON.
   */
  private static function formatAction(mixed $action): string
  {
    if (is_string($action)) {
      return $action;
    }
    if (is_array($action) && count($action) === 2) {
      $class = is_object($action[0]) ? get_class($action[0]) : (string)$action[0];
      return $class . '@' . $action[1];
    }
    if ($action instanceof \Closure) {
      return 'Closure';
    }
    return 'Unknown';
  }
}
