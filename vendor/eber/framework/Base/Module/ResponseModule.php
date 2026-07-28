<?php

namespace Base\Module;

/**
 * Módulo para manejo de respuestas HTTP.
 * 
 * Proporciona utilidades para:
 * - Respuestas JSON
 * - Redirecciones con mensajes
 * - Manejo de errores de URL
 * 
 * @example
 * // Respuesta JSON
 * ResponseModule::json(['success' => true, 'data' => $result]);
 * 
 * // Redirección con mensaje
 * ResponseModule::redirect("/Dashboard", 'Guardado correctamente', 0);
 */
class ResponseModule
{
  /**
   * Envía una respuesta JSON.
   * 
   * @param mixed $data Datos a convertir a JSON.
   * @param int $status Código de estado HTTP.
   * @param array $headers Cabeceras adicionales.
   * @return never
   */
  public static function json(mixed $data, int $status = 200, array $headers = []): never
  {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    foreach ($headers as $key => $value) {
      header("$key: $value");
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
  }

  /**
   * Redirecciona a una URL con mensaje opcional.
   * 
   * @param string $route Ruta destino.
   * @param string|null $message Mensaje a mostrar.
   * @param int|null $type Tipo de mensaje: 0=success, 1=warning, 2=danger, null=error.
   * @return never
   */
  public static function redirect(string $route, ?string $message = null, ?int $type = null): never
  {
    $typeMap = [
      0 => 'success',
      1 => 'warning',
      2 => 'danger',
    ];

    $typeError = $typeMap[$type] ?? 'error';

    // Limpiar query string existente
    if (strpos($route, '?') !== false) {
      $route = substr($route, 0, strpos($route, '?'));
    }

    // Normalizar ruta para evitar barras dobles
    if (str_starts_with($route, 'http://') || str_starts_with($route, 'https://')) {
      $route = preg_replace('#(?<!:)//+#', '/', $route);
    } else {
      $route = '/' . ltrim($route, '/');
      $route = preg_replace('#(?<!:)//+#', '/', $route);
    }

    if ($message === null) {
      header('Location: ' . $route);
    } else {
      header('Location: ' . $route . '?' . $typeError . '=' . urlencode($message));
    }

    exit;
  }

  /**
   * Muestra errores pasados por URL.
   * 
   * @param string|null $cssClass Clase CSS para el contenedor.
   * @return void
   */
  public static function showError(?string $cssClass = null): void
  {
    $types = ['error', 'success', 'warning', 'danger'];

    foreach ($types as $type) {
      if (isset($_GET[$type])) {
        $message = htmlspecialchars($_GET[$type], ENT_QUOTES, 'UTF-8');

        if ($cssClass === null) {
          echo $message;
        } else {
          echo '<div class="' . $cssClass . ' ' . $type . '">' . $message . '</div>';
        }

        return;
      }
    }
  }

  /**
   * Establece cabeceras para prevenir caché.
   * 
   * @return void
   */
  public static function noCache(): void
  {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
  }

  /**
   * Establece cabeceras CORS.
   * 
   * @param string $origin Origen permitido (* para todos).
   * @param array $methods Métodos permitidos.
   * @param array $headers Cabeceras permitidas.
   * @return void
   */
  public static function cors(
    string $origin = '*',
    array $methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    array $headers = ['Content-Type', 'Authorization']
  ): void {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: ' . implode(', ', $methods));
    header('Access-Control-Allow-Headers: ' . implode(', ', $headers));

    // Manejar preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      http_response_code(204);
      exit;
    }
  }

  /**
   * Envía una respuesta de texto plano.
   * 
   * @param string $content Contenido a enviar.
   * @param int $status Código de estado HTTP.
   * @return never
   */
  public static function text(string $content, int $status = 200): never
  {
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $content;
    exit;
  }

  /**
   * Envía una respuesta de error JSON.
   * 
   * @param string $message Mensaje de error.
   * @param int $status Código de estado HTTP.
   * @param array $details Detalles adicionales.
   * @return never
   */
  public static function error(string $message, int $status = 400, array $details = []): never
  {
    self::json([
      'success' => false,
      'error' => $message,
      'details' => $details
    ], $status);
  }

  /**
   * Envía una respuesta de éxito JSON.
   * 
   * @param mixed $data Datos a enviar.
   * @param string|null $message Mensaje de éxito.
   * @return never
   */
  public static function success(mixed $data = null, ?string $message = null): never
  {
    $response = ['success' => true];

    if ($message !== null) {
      $response['message'] = $message;
    }

    if ($data !== null) {
      $response['data'] = $data;
    }

    self::json($response);
  }
  /**
   * Envía una respuesta JSON inmediatamente y continúa la ejecución del script.
   * Ideal para procesar tareas pesadas en segundo plano.
   * 
   * @param mixed $data Datos a enviar.
   * @param int $status Código de estado HTTP.
   * @return void
   */
  public static function sendAndContinue(mixed $data, int $status = 200): void
  {
    // Limpiamos buffers anteriores si existen
    if (ob_get_level() > 0) {
      ob_end_clean();
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    // Renderizamos el JSON
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);

    // Forzamos el envío y cierre de conexión
    if (function_exists('fastcgi_finish_request')) {
      echo $json;
      fastcgi_finish_request();
    } else {
      // Fallback para Apache/CGI
      $size = strlen($json);
      header("Content-Length: $size");
      header('Connection: close');

      ob_start();
      echo $json; // Aseguramos que esté en el buffer
      ob_end_flush();
      flush();
    }
  }
  /**
   * Envía contenido (HTML, Texto, etc) inmediatamente y continúa la ejecución del script.
   * 
   * @param string $content Contenido a enviar.
   * @param string $contentType Tipo de contenido MIME (default: text/html).
   * @param int $status Código de estado HTTP.
   * @return void
   */
  public static function sendContent(string $content, string $contentType = 'text/html', int $status = 200): void
  {
    // Limpiamos buffers anteriores si existen
    while (ob_get_level() > 0) {
      ob_end_clean();
    }

    http_response_code($status);
    header("Content-Type: $contentType; charset=utf-8");

    // Forzamos el envío y cierre de conexión
    if (function_exists('fastcgi_finish_request')) {
      echo $content;
      fastcgi_finish_request();
    } else {
      // Fallback para Apache/CGI
      // Calculamos tamaño ANTES de enviar nada
      $size = strlen($content);
      header("Content-Length: $size");
      header('Connection: close');

      // Enviamos el contenido y forzamos el flush
      ob_start();
      echo $content;
      ob_end_flush();
      flush();
    }
  }
}
