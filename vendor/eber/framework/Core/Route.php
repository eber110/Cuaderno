<?php

namespace Core;

use Exception;
use Core\ErrorHandler;

/**
 * Clase Route
 *
 * Gestiona las rutas de la aplicación y despacha las solicitudes
 * al controlador y acción correspondientes.
 */
class Route
{

  // Almacena todas las rutas registradas.
  private static array $routes = [];

  /**
   * Obtiene todas las rutas registradas.
   *
   * @return array
   */
  public static function getRoutes(): array
  {
    return self::$routes;
  }

  // Almacena los middlewares globales que se aplicarán a todas las rutas.
  private static array $globalMiddlewares = [];
  // Prefijo actual para las rutas (útil para agrupar rutas, ej. /admin)
  private static string $currentPrefix = '';
  // Grupo actual de middlewares
  private static array $currentMiddlewareGroup = [];
  // NUEVO: Almacena temporalmente los middlewares a excluir para la siguiente ruta.
  private static array $excludeMiddlewareForNextRoute = [];
  // NUEVO: Almacena temporalmente los middlewares pendientes para el siguiente grupo.
  private static array $pendingMiddlewares = [];
  // NUEVO: Almacena temporalmente el prefijo pendiente para el siguiente grupo.
  private static string $pendingPrefix = '';

  // Constantes para los métodos HTTP para evitar errores tipográficos.
  private const METHOD_GET = 'GET';
  private const METHOD_POST = 'POST';
  private const METHOD_PUT = 'PUT';
  private const METHOD_PATCH = 'PATCH';
  private const METHOD_DELETE = 'DELETE';

  /**
   * Agrega una ruta para el método GET.
   *
   * @param string $uri La URI de la ruta. Puede contener parámetros dinámicos (ej. /usuarios/:id).
   * @param array|callable|string $action El controlador y método a ejecutar, o una función anónima.
   * @param array $middlewares Middlewares específicos para esta ruta.
   */
  public static function get(string $uri, array|callable|string $action, array $middlewares = []): void
  {
    self::addRoute(self::METHOD_GET, $uri, $action, $middlewares);
  }

  /**
   * Agrega una ruta para el método POST.
   *
   * @param string $uri
   * @param array|callable|string $action
   * @param array $middlewares
   */
  public static function post(string $uri, array|callable|string $action, array $middlewares = []): void
  {
    self::addRoute(self::METHOD_POST, $uri, $action, $middlewares);
  }

  /**
   * Agrega una ruta para el método PUT.
   *
   * @param string $uri
   * @param array|callable|string $action
   * @param array $middlewares
   */
  public static function put(string $uri, array|callable|string $action, array $middlewares = []): void
  {
    self::addRoute(self::METHOD_PUT, $uri, $action, $middlewares);
  }

  /**
   * Agrega una ruta para el método PATCH.
   *
   * @param string $uri
   * @param array|callable|string $action
   * @param array $middlewares
   */
  public static function patch(string $uri, array|callable|string $action, array $middlewares = []): void
  {
    self::addRoute(self::METHOD_PATCH, $uri, $action, $middlewares);
  }

  /**
   * Agrega una ruta para el método DELETE.
   *
   * @param string $uri
   * @param array|callable|string $action
   * @param array $middlewares
   */
  public static function delete(string $uri, array|callable|string $action, array $middlewares = []): void
  {
    self::addRoute(self::METHOD_DELETE, $uri, $action, $middlewares);
  }

  /**
   * Método interno para agregar una ruta al array de rutas.
   * MODIFICADO: Ahora también gestiona la exclusión de middlewares.
   *
   * @param string $method Método HTTP (GET, POST, etc.).
   * @param string $uri URI de la ruta.
   * @param array|callable|string $action Acción a ejecutar (controlador/método o Closure o string).
   * @param array $routeSpecificMiddlewares Middlewares específicos de la ruta.
   */
  private static function addRoute(string $method, string $uri, array|callable|string $action, array $routeSpecificMiddlewares): void
  {
    $processedUri = self::$currentPrefix . '/' . ltrim($uri, '/');
    $processedUri = preg_replace('#(?<!:)//+#', '/', $processedUri);
    $processedUri = rtrim($processedUri, '/');

    if ($processedUri === '' || $processedUri === '/') {
      $processedUri = '/';
    } else {
      $processedUri = '/' . ltrim($processedUri, '/');
    }
    $processedUri = preg_replace('#(?<!:)//+#', '/', $processedUri);

    self::$routes[] = [
      'method'              => $method,
      'uri'                 => $processedUri,
      'action'              => $action,
      'middlewares'         => array_merge(self::$currentMiddlewareGroup, $routeSpecificMiddlewares),
      // MODIFICADO: Se añade la clave para guardar las exclusiones.
      'exclude_middlewares' => self::$excludeMiddlewareForNextRoute
    ];

    // MODIFICADO: Se limpia la propiedad para que no afecte a las siguientes rutas.
    self::$excludeMiddlewareForNextRoute = [];
  }

  /**
   * Agrupa rutas bajo un prefijo común y/o middlewares.
   *
   * @param callable $callback Función que define las rutas dentro del grupo.
   */
  public static function group(callable $callback): void
  {
    // Guardar el estado ANTES de aplicar los middlewares y prefijos pendientes
    $previousPrefix = self::$currentPrefix;
    $previousMiddlewareGroup = self::$currentMiddlewareGroup;

    // Aplicar el prefijo pendiente SOLO para este grupo
    if (self::$pendingPrefix !== '') {
      self::$currentPrefix .= '/' . trim(self::$pendingPrefix, '/');
      self::$pendingPrefix = ''; // Limpiar el pendiente
    }

    // Aplicar los middlewares pendientes SOLO para este grupo
    self::$currentMiddlewareGroup = array_merge(self::$currentMiddlewareGroup, self::$pendingMiddlewares);
    self::$pendingMiddlewares = []; // Limpiar los pendientes

    call_user_func($callback);

    // Restaurar al estado anterior (sin los middlewares ni prefijos del grupo)
    self::$currentPrefix = $previousPrefix;
    self::$currentMiddlewareGroup = $previousMiddlewareGroup;
  }

  /**
   * Establece un prefijo para un grupo de rutas.
   *
   * @param string $prefix El prefijo para las URIs.
   * @return static Retorna la instancia de la clase para encadenamiento.
   */
  public static function prefix(string $prefix): static
  {
    // CAMBIO: Guardar en pendingPrefix en lugar de aplicar inmediatamente
    // Esto permite que group() controle cuándo se aplica
    self::$pendingPrefix .= '/' . trim($prefix, '/');
    return new static();
  }

  /**
   * Establece middlewares para un grupo de rutas.
   *
   * @param array $middlewares Array de nombres de clases de middleware.
   * @return static Retorna la instancia de la clase para encadenamiento.
   */
  public static function middleware(array $middlewares): static
  {
    // CAMBIO: Guardar en pendingMiddlewares en lugar de aplicar inmediatamente
    // Esto permite que group() controle cuándo se aplican
    self::$pendingMiddlewares = array_merge(self::$pendingMiddlewares, $middlewares);
    return new static();
  }

  /**
   * NUEVO: Especifica middlewares a excluir para la siguiente ruta que se defina.
   *
   * @param array $middlewares Array de nombres de clases de middleware a excluir.
   * @return static Retorna la instancia de la clase para encadenamiento.
   */
  public static function except(array $middlewares): static
  {
    self::$excludeMiddlewareForNextRoute = $middlewares;
    return new static();
  }

  /**
   * Agrega middlewares globales que se aplicarán a todas las rutas.
   * @param array $middlewares Array de nombres de clases de middleware.
   */
  public static function addGlobalMiddleware(array $middlewares): void
  {
    self::$globalMiddlewares = array_merge(self::$globalMiddlewares, $middlewares);
  }

  /**
   * Procesa la solicitud actual, encuentra la ruta coincidente y ejecuta la acción.
   * MODIFICADO: Ahora aplica la lógica de exclusión de middlewares.
   *
   * @return mixed El resultado de la acción del controlador.
   */
  public static function run()
  {
    // Cargar rutas del proyecto si no se han registrado previamente
    if (empty(self::$routes) && defined('ROOT_PATH')) {
      $routeDir = ROOT_PATH . '/App/Route';
      if (is_dir($routeDir) && class_exists('\\Core\\ConfigLoader\\RouteLoader')) {
        \Core\ConfigLoader\RouteLoader::load($routeDir);
      }
    }

    // Ejecutar CORS al inicio de la solicitud
    if (class_exists('\Base\Module\CorsModule')) {
      \Base\Module\CorsModule::handle();
    }

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = preg_replace('#(?<!:)//+#', '/', $uri);
    $uri = trim($uri, '/');
    $uri = ($uri === '') ? '/' : '/' . $uri;

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === self::METHOD_POST && isset($_POST['_method'])) {
      $submittedMethod = strtoupper($_POST['_method']);
      if (in_array($submittedMethod, [self::METHOD_PUT, self::METHOD_PATCH, self::METHOD_DELETE])) {
        $method = $submittedMethod;
      }
    }

    $matchedRoute = null;
    $params = [];
    $allowedMethodsForUri = [];

    foreach (self::$routes as $route) {
      if (self::matchUri($uri, $route['uri'], $params)) {
        if ($route['method'] === $method || ($method === 'HEAD' && $route['method'] === self::METHOD_GET)) {
          $matchedRoute = $route;
          break;
        }
        $allowedMethodsForUri[] = $route['method'];
      }
    }

    if ($matchedRoute) {
      try {
        // MODIFICADO: Lógica para combinar y excluir middlewares.
        $allMiddlewares = array_merge(self::$globalMiddlewares, $matchedRoute['middlewares']);
        $excludedMiddlewares = $matchedRoute['exclude_middlewares'] ?? [];
        $middlewaresToRun = array_diff($allMiddlewares, $excludedMiddlewares);

        $next = function ($requestData) use ($matchedRoute, $params) {
          return self::executeAction($matchedRoute['action'], $params, $requestData);
        };

        foreach (array_reverse($middlewaresToRun) as $middlewareClass) {
          if (!class_exists($middlewareClass)) {
            throw new Exception("Clase de Middleware no encontrada: {$middlewareClass}");
          }
          $middlewareInstance = new $middlewareClass();
          if (!method_exists($middlewareInstance, 'handle')) {
            throw new Exception("Método 'handle' no encontrado en el Middleware: {$middlewareClass}");
          }
          $next = function ($requestData) use ($middlewareInstance, $next) {
            return $middlewareInstance->handle($requestData, $next);
          };
        }

        $requestData = ($method === self::METHOD_GET) ? $_GET : self::getInputData();
        return $next($requestData);
      } catch (Exception $e) {
        // En un entorno real, aquí registrarías el error y mostrarías una página amigable.
        //http_response_code(500);
        //echo "<h1>Error 500: Error Interno del Servidor</h1>";
        // Descomenta la siguiente línea solo en desarrollo para ver el detalle del error.
        // echo "<p>" . $e->getMessage() . "</p>";
        ErrorHandler::handle_code(500, 500, "Error Interno del Servidor");
      }
    } else {
      if (!empty($allowedMethodsForUri)) {
        //http_response_code(405);
        //header('Allow: ' . implode(', ', array_unique($allowedMethodsForUri)));
        //echo "<h1>Error 405: Método no permitido</h1>";
        ErrorHandler::handle_code(405, 405, "Método no permitido");
      } else {
        //http_response_code(404);
        //echo "<h1 style='color: red;'>Error 404: Página no encontrada</h1>";
        ErrorHandler::handle_code(404, 404, "Página no encontrada");
      }
    }

    return null;
  }

  /**
   * Ejecuta la acción de la ruta (controlador/método o Closure o string).
   *
   * @param array|callable|string $action
   * @param array $params Parámetros extraídos de la URI.
   * @param mixed $requestData Datos de la solicitud (GET, POST, JSON, etc.).
   * @return mixed
   * @throws Exception Si el controlador o la acción no son válidos.
   */
  private static function executeAction(array|callable|string $action, array $params, mixed $requestData)
  {
      if (is_callable($action)) {
        return call_user_func_array($action, array_merge($params, [$requestData]));
      } elseif (is_array($action) && count($action) === 2) {
        $controllerClass = $action[0];
        $methodName = $action[1];

        if (!class_exists($controllerClass)) {
          throw new Exception("Controlador no encontrado: {$controllerClass}");
        }
        $controllerInstance = new $controllerClass();
        if (!method_exists($controllerInstance, $methodName)) {
          throw new Exception("Método '{$methodName}' no encontrado en el controlador {$controllerClass}");
        }
        return call_user_func_array([$controllerInstance, $methodName], array_merge($params, [$requestData]));
      } elseif (is_string($action) && str_contains($action, '@')) {
        list($controllerClass, $methodName) = explode('@', $action);
        $controllerClass = trim($controllerClass);
        $methodName = trim($methodName);

        if (!class_exists($controllerClass)) {
          $namespacedClass = 'App\\Controllers\\' . $controllerClass;
          if (class_exists($namespacedClass)) {
            $controllerClass = $namespacedClass;
          } else {
            throw new Exception("Controlador no encontrado: {$controllerClass}");
          }
        }
        $controllerInstance = new $controllerClass();
        if (!method_exists($controllerInstance, $methodName)) {
          throw new Exception("Método '{$methodName}' no encontrado en el controlador {$controllerClass}");
        }
        return call_user_func_array([$controllerInstance, $methodName], array_merge($params, [$requestData]));
      } else {
        throw new Exception("Acción de ruta no válida.");
      }
  }

  /**
   * Compara la URI solicitada con la URI de una ruta definida.
   *
   * @param string $currentUri La URI actual de la solicitud.
   * @param string $routeUri La URI de la ruta definida en el sistema.
   * @param array &$params Array donde se almacenarán los valores de los parámetros.
   * @return bool True si las URIs coinciden, false en caso contrario.
   */
  private static function matchUri(string $currentUri, string $routeUri, array &$params): bool
  {
    $params = [];
    $currentUriSegments = explode('/', trim($currentUri, '/'));
    $routeUriSegments = explode('/', trim($routeUri, '/'));

    if ($currentUri === '/' && $routeUri === '/') {
      return true;
    }
    if (count($currentUriSegments) !== count($routeUriSegments)) {
      return false;
    }

    foreach ($routeUriSegments as $key => $routeSegment) {
      if (str_starts_with($routeSegment, ':')) {
        if (isset($currentUriSegments[$key])) {
          $params[] = $currentUriSegments[$key];
        } else {
          return false;
        }
      } elseif (!isset($currentUriSegments[$key]) || $routeSegment !== $currentUriSegments[$key]) {
        return false;
      }
    }

    return true;
  }

  /**
   * Obtiene los datos de entrada crudos de la solicitud (para PUT, PATCH, DELETE).
   *
   * @return array|null Los datos decodificados o null si no hay datos.
   */
  public static function getInputData(): ?array
  {
    // Si $_POST no está vacío, se retorna directamente (aplica para urlencoded y multipart/form-data)
    if (!empty($_POST)) {
      return $_POST;
    }

    $dataJS = file_get_contents('php://input');
    if ($dataJS === false || $dataJS === '') {
      return null;
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
      $decodedData = json_decode($dataJS, true);
      return is_array($decodedData) ? $decodedData : null;
    }

    $parsedData = [];
    parse_str($dataJS, $parsedData);
    return $parsedData;
  }

  /**
   * Limpia todas las rutas definidas. Útil para testing.
   */
  public static function reset(): void
  {
    self::$routes = [];
    self::$globalMiddlewares = [];
    self::$currentPrefix = '';
    self::$currentMiddlewareGroup = [];
    self::$excludeMiddlewareForNextRoute = [];
    self::$pendingMiddlewares = [];
    self::$pendingPrefix = '';
  }
}
