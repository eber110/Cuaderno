<?php

namespace App\Middleware;

use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use App\Models\VisitModels;

/**
 * Clase VisitMiddleware
 * 
 * Middleware enfocado exclusivamente en filtrar peticiones HTTP y recursos estáticos,
 * delegando el procesamiento de visitas al modelo VisitModels.
 */
class VisitMiddleware implements MiddlewareInterface {

  /**
   * Maneja la interceptación de la petición HTTP.
   *
   * @param mixed $requestData Datos de la petición.
   * @param callable $next Siguiente middleware en la cadena.
   * @return mixed Respuesta enviada por el siguiente middleware.
   */
  public function handle($requestData, callable $next) {

    try {
      // 1. Obtener la URI de la petición
      $uri = parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH);

      // 2. Si la petición es para un archivo de recurso estático, ignorarla de inmediato
      $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
      $staticExtensions = ["css", "js", "png", "jpg", "jpeg", "gif", "webp", "svg", "ico", "woff", "woff2", "ttf", "eot", "map", "json"];
      
      if (!empty($extension) && in_array($extension, $staticExtensions)) {
        return $next($requestData);
      }

      $segments = explode("/", trim($uri, "/"));
      $visitedUser = $segments[0] ?? "";

      // Rutas y carpetas del sistema excluidas de ser registradas como perfiles de usuario
      $excluded = [
        "robots.txt", "sitemap.xml", "llms.txt",
        "ingresar", "registrar", "salir", "panel",
        "op", "uploads", "app", "favicon.ico", "css", "js", "img", "rsc", "cache", "vendor"
      ];

      if (!empty($visitedUser) && !in_array(mb_strtolower($visitedUser, "UTF-8"), $excluded)) {
        $visitedUserClean = mb_strtolower($visitedUser, "UTF-8");

        // Validar si el usuario está logueado y visitando su propio perfil
        $isOwnProfile = false;
        if (\Base\Module\Session::session_active()) {
          $sessionUser = \Base\Module\Session::session_data("username");
          if (!empty($sessionUser) && mb_strtolower($sessionUser, "UTF-8") === $visitedUserClean) {
            $isOwnProfile = true;
          }
        }

        if (!$isOwnProfile) {
          // Delegar el procesamiento y registro de la visita al modelo VisitModels
          VisitModels::processVisit($visitedUser);
        }
      }
    } catch (\Throwable $e) {
      error_log("VisitMiddleware error: " . $e->getMessage());
    }

    return $next($requestData);
  }

}
