<?php

namespace App\Middleware;

use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use Base\Module\ResponseModule;
use Base\Module\SecurityModule;

/**
 * Middleware para verificación y protección contra ataques CSRF en peticiones POST.
 */
class CsrfMiddleware implements MiddlewareInterface
{
  /**
   * Rutas excluidas de verificación CSRF.
   * Por ejemplo, webhooks externos firmados por HMAC como Lemon Squeezy o trackers anónimos.
   */
  private const EXCLUDED_ROUTES = [
    '/lemon-squeezy/webhook',
    '/op/track-click',
    '/op/track-view',
    '/op/active-viewers'
  ];

  /**
   * Intercepta la petición para validar el token CSRF si la protección está activa.
   *
   * @param mixed $requestData
   * @param callable $next
   * @return mixed
   */
  public function handle($requestData, callable $next)
  {
    $method = \Base\Module\SecurityModule::sanitize($_SERVER["REQUEST_METHOD"] ?? "GET") ?? 'GET';

    if ($method === 'POST') {
      $uri = parse_url(\Base\Module\SecurityModule::sanitize($_SERVER["REQUEST_URI"] ?? "") ?? '', PHP_URL_PATH) ?? '/';
      $uri = rtrim($uri, '/') ?: '/';

      foreach (self::EXCLUDED_ROUTES as $excluded) {
        if ($uri === $excluded || str_starts_with($uri, $excluded . '/')) {
          return $next($requestData);
        }
      }

      $csrfActive = defined('CSRF_PROTECTION') 
        ? (CSRF_PROTECTION === true || CSRF_PROTECTION === 'true')
        : (($_ENV['CSRF_PROTECTION'] ?? '') === 'true');

      if ($csrfActive && !SecurityModule::verifyCsrf()) {
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
          || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isAjax) {
          return ResponseModule::json([
            'status'  => 'error',
            'message' => 'Token de seguridad (CSRF) inválido o expirado. Por favor, recargue la página.'
          ], 403);
        }

        return ResponseModule::error("Solicitud no válida: Token de seguridad CSRF ausente o expirado.", 403);
      }
    }

    return $next($requestData);
  }
}
