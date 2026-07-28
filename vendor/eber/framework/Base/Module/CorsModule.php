<?php

namespace Base\Module;

/**
 * Módulo para manejo de CORS (Cross-Origin Resource Sharing).
 * 
 * Permite configurar y aplicar políticas de CORS de forma dinámica
 * y diferenciada para múltiples endpoints o microservicios.
 */
class CorsModule
{
  /**
   * Aplica la configuración de CORS a la respuesta HTTP actual.
   * Carga la configuración desde el proyecto local y evalúa el patrón de la ruta.
   * 
   * @return void
   */
  public static function handle(): void
  {
    $config = self::loadConfig();

    if (empty($config) || !($config['enabled'] ?? true)) {
      return;
    }

    // Obtener la URI de la solicitud actual
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $requestUri = trim($requestUri, '/');
    if ($requestUri === '') {
      $requestUri = '/';
    }

    // Buscar el primer patrón que coincida con la solicitud
    $activeConfig = null;
    $paths = $config['paths'] ?? [];
    
    foreach ($paths as $pattern => $settings) {
      if (self::matchesPattern($pattern, $requestUri)) {
        $activeConfig = $settings;
        break;
      }
    }

    // Si no coincide con ningún patrón, no aplicamos cabeceras
    if ($activeConfig === null) {
      return;
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = $activeConfig['allowed_origins'] ?? ['*'];

    // Validar origen
    $selectedOrigin = '*';
    if (in_array('*', $allowedOrigins)) {
      $selectedOrigin = '*';
    } elseif (in_array($origin, $allowedOrigins)) {
      $selectedOrigin = $origin;
    } else {
      // Si el origen no está permitido y es una preflight, denegar
      if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(403);
        exit;
      }
      return;
    }

    // Cabeceras básicas de CORS
    header('Access-Control-Allow-Origin: ' . $selectedOrigin);

    if ($selectedOrigin !== '*' && ($activeConfig['supports_credentials'] ?? false)) {
      header('Access-Control-Allow-Credentials: true');
    }

    if (!empty($activeConfig['exposed_headers'] ?? [])) {
      header('Access-Control-Expose-Headers: ' . implode(', ', $activeConfig['exposed_headers']));
    }

    // Si es una petición OPTIONS (preflight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      $allowedMethods = $activeConfig['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
      $allowedHeaders = $activeConfig['allowed_headers'] ?? ['Content-Type', 'Authorization', 'X-Requested-With'];
      
      header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
      header('Access-Control-Allow-Headers: ' . implode(', ', $allowedHeaders));
      
      if (isset($activeConfig['max_age']) && $activeConfig['max_age'] > 0) {
        header('Access-Control-Max-Age: ' . (int)$activeConfig['max_age']);
      }
      
      http_response_code(204);
      exit;
    }
  }

  /**
   * Compara un patrón con comodín '*' con una ruta dada.
   * 
   * @param string $pattern Patrón de ruta (ej: 'api/*' o '*').
   * @param string $path Ruta de solicitud actual (ej: 'api/usuarios').
   * @return bool True si coincide.
   */
  private static function matchesPattern(string $pattern, string $path): bool
  {
    $pattern = trim($pattern, '/');
    $path = trim($path, '/');

    if ($pattern === '*') {
      return true;
    }

    if ($pattern === '') {
      return $path === '';
    }

    // Convertir comodín '*' a regex
    $regex = '^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$';
    return (bool) preg_match('#' . $regex . '#i', $path);
  }

  /**
   * Carga la configuración de CORS del proyecto local o retorna los valores por defecto.
   * 
   * @return array
   */
  private static function loadConfig(): array
  {
    $localConfigPath = null;
    if (defined('ROOT_PATH')) {
      $localConfigPath = ROOT_PATH . '/App/Config/CorsConfiguration.php';
    } else {
      $dirFallback = dirname(__DIR__, 5) . '/App/Config/CorsConfiguration.php';
      if (file_exists($dirFallback)) {
        $localConfigPath = $dirFallback;
      } else {
        $cwdFallback = getcwd() . '/App/Config/CorsConfiguration.php';
        if (file_exists($cwdFallback)) {
          $localConfigPath = $cwdFallback;
        }
      }
    }

    if ($localConfigPath && file_exists($localConfigPath)) {
      $config = require $localConfigPath;
      if (is_array($config)) {
        return $config;
      }
    }

    // Configuración predeterminada de fallback
    return [
      'enabled' => true,
      'paths' => [
        '*' => [
          'allowed_origins' => ['*'],
          'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
          'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
          'exposed_headers' => [],
          'supports_credentials' => false,
          'max_age' => 86400,
        ]
      ]
    ];
  }
}
