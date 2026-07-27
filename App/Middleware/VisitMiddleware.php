<?php

namespace App\Middleware;

use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use Base\Module\CookieModule;
use Base\Module\LogModule;
use Base\Module\VisitModule;

class VisitMiddleware implements MiddlewareInterface {

  public function handle($requestData, callable $next) {

    // 1. Obtener la URI
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

    // 2. Si la petición es para un archivo de recurso estático (css, js, imágenes, fuentes), ignorarla de inmediato
    $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'map', 'json'];
    
    if (!empty($extension) && in_array($extension, $staticExtensions)) {
      return $next($requestData);
    }

    $segments = explode('/', trim($uri, '/'));
    $visitedUser = $segments[0] ?? '';

    // Rutas y carpetas del sistema excluidas de ser registradas como perfiles de usuario
    $excluded = [
      'robots.txt', 'sitemap.xml', 'llms.txt',
      'ingresar', 'registrar', 'salir', 'panel',
      'op', 'uploads', 'app', 'favicon.ico', 'css', 'js', 'img', 'rsc', 'cache', 'vendor'
    ];

    if (!empty($visitedUser) && !in_array(mb_strtolower($visitedUser, 'UTF-8'), $excluded)) {
      $visitedUserClean = mb_strtolower($visitedUser, 'UTF-8');

      // 3. Inicializar el módulo de visitas con la cookie 'Visit_registration'
      VisitModule::initSession("Visit_registration");

      // 4. Crear clave de cookie de control por perfil visitado durante 1 hora (3600 seg)
      $cookieKey = "Visit_registration_" . $visitedUserClean;

      if (!CookieModule::exists($cookieKey)) {
        // Establecer la cookie de control para evitar visitas duplicadas al mismo perfil en 1 hora
        CookieModule::set($cookieKey, [
          "value" => (string)time(),
          "expired" => 3600,
          "path" => "/",
          "httponly" => true,
          "samesite" => "Lax"
        ]);

        // Actualizar la cookie general Visit_registration por 1 hora
        CookieModule::set("Visit_registration", [
          "value" => (string)time(),
          "expired" => 3600,
          "path" => "/",
          "httponly" => true,
          "samesite" => "Lax"
        ]);

        // 5. Extraer información geográfica e IP de la visita
        $location = VisitModule::getLocation() ?? [];
        $ip = VisitModule::getClientIp() ?? '127.0.0.1';

        $visitContent = [
          "visited_user" => $visitedUserClean,
          "ip" => $ip,
          "country" => $location['pais'] ?? '',
          "city" => $location['ciudad'] ?? '',
          "region" => $location['region'] ?? '',
          "codigo" => $location['codigo'] ?? '',
          "user_agent" => $_SERVER['HTTP_USER_AGENT'] ?? '',
          "referer" => $_SERVER['HTTP_REFERER'] ?? '',
          "visited_at" => date('Y-m-d H:i:s'),
          "timestamp" => time()
        ];

        // 6. Registrar la visita en Cache/Visits/visit_register.json
        $cacheDir = defined('ROOT_PATH') ? ROOT_PATH . "/Cache/Visits" : getcwd() . "/Cache/Visits";

        LogModule::simpleLog([
          "dir" => $cacheDir,
          "name" => "visit_register",
          "content" => $visitContent
        ]);
      }
    }

    return $next($requestData);
  }

}
