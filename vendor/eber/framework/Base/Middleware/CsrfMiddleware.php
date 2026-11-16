<?php

namespace Base\Middleware;

use Base\Module\ResponseModule;
use Base\Module\SecurityModule;
use Core\ErrorHandler;

/**
 * Middleware global del núcleo para protección CSRF automática.
 *
 * Se aplica automáticamente cuando CSRF_PROTECTION está habilitado en configuración.
 * Exige un token CSRF válido para solicitudes que modifican estado (POST, PUT, PATCH, DELETE),
 * incluyendo solicitudes simuladas vía `_method` o cuerpos JSON.
 */
class CsrfMiddleware
{
  /**
   * Métodos HTTP que alteran estado y requieren token CSRF válido.
   */
  private const STATE_CHANGING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

  /**
   * Valida el token CSRF antes de despachar la acción del controlador.
   *
   * @param mixed $requestData Datos de la solicitud actual ($_POST, JSON, etc.).
   * @param callable $next Siguiente middleware o acción final de la ruta.
   * @return mixed
   */
  public function handle($requestData, callable $next)
  {
    $csrfActive = defined('CSRF_PROTECTION')
      ? (CSRF_PROTECTION === true || CSRF_PROTECTION === 'true' || CSRF_PROTECTION === 1 || CSRF_PROTECTION === '1')
      : filter_var($_ENV['CSRF_PROTECTION'] ?? $_SERVER['CSRF_PROTECTION'] ?? true, FILTER_VALIDATE_BOOLEAN);

    if (!$csrfActive) {
      return $next($requestData);
    }

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method === 'POST' && isset($_POST['_method'])) {
      $override = strtoupper(trim((string)$_POST['_method']));
      if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
        $method = $override;
      }
    }

    if (!in_array($method, self::STATE_CHANGING_METHODS, true)) {
      return $next($requestData);
    }

    $bodyData = is_array($requestData) ? $requestData : null;
    if (SecurityModule::verifyCsrf(false, $method, $bodyData)) {
      return $next($requestData);
    }

    $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
    SecurityModule::logIntrusion("Fallo de verificación CSRF en {$method} {$uri}");

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
      || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
      || str_contains((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json');

    if ($isAjax) {
      ResponseModule::json([
        'status' => 'error',
        'success' => false,
        'message' => 'Token CSRF inválido, ausente o expirado.'
      ], 403);
    }

    ErrorHandler::handleCode(
      403,
      403,
      function_exists('__') ? __('global.errors.403_csrf', [], 'Token CSRF inválido o expirado. Por favor, recargue la página.') : 'Token CSRF inválido o expirado. Por favor, recargue la página.'
    );
    return null;
  }
}
