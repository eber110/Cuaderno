<?php

namespace App\Middleware;

use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use Base\Module\CookieModule;
use Base\Module\LogModule;
use Base\Module\VisitModule;

class VisitMiddleware implements MiddlewareInterface {

  public function handle($requestData, callable $next) {

    // 1. Obtener la URI y el nombre del usuario o perfil visitado
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));
    $visitedUser = $segments[0] ?? '';

    // Lista de rutas del sistema excluidas de ser registradas como visitas a perfiles
    $excluded = [
      'robots.txt', 'sitemap.xml', 'llms.txt',
      'ingresar', 'registrar', 'salir', 'panel',
      'op', 'uploads', 'app', 'favicon.ico'
    ];

    if (!empty($visitedUser) && !in_array(mb_strtolower($visitedUser, 'UTF-8'), $excluded)) {
      $visitedUserClean = mb_strtolower($visitedUser, 'UTF-8');

      // 2. Inicializar el módulo de visitas con la cookie 'Visit_registration'
      VisitModule::initSession("Visit_registration");

      // 3. Crear una clave de cookie de control única por perfil visitado durante 1 hora (3600 seg)
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

        // 4. Extraer información geográfica e IP de la visita
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

        // 5. Registrar la visita en Cache/Visits/visit_register.json
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
