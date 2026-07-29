<?php

namespace App\Middleware;

use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use Base\Module\AnalyticsModule;
use Base\Module\MovilDetectorModule;
use Base\Module\VisitModule;

class VisitMiddleware implements MiddlewareInterface {

  public function handle($requestData, callable $next) {

    try {
      // 1. Obtener la URI
      $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

      // 2. Si la petición es para un archivo de recurso estático, ignorarla de inmediato
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

        // Controlar en sesión la frecuencia de visitas al mismo perfil (1 vez cada 1 hora / 3600s)
        $sessionKey = "visit_registered_" . $visitedUserClean;
        $lastVisitTime = $_SESSION[$sessionKey] ?? 0;

        if ((time() - (int)$lastVisitTime) > 3600) {
          $_SESSION[$sessionKey] = time();

          // Inicializar geolocalización
          VisitModule::initSession("Visit_registration");
          $location = VisitModule::getLocation() ?? [];

          $ip          = $location['ip'] ?? VisitModule::getClientIp() ?? $_SESSION["location"]["ip"] ?? '';
          $countryCode = !empty($location['codigo']) ? $location['codigo'] : ($_SESSION["location"]["codigo"] ?? 'N/A');
          $countryName = !empty($location['pais']) ? $location['pais'] : ($_SESSION["location"]["pais"] ?? 'Desconocido');
          $cityName    = !empty($location['ciudad']) ? $location['ciudad'] : ($_SESSION["location"]["ciudad"] ?? 'Desconocido');
          $deviceType  = MovilDetectorModule::getDeviceType();
          $os          = MovilDetectorModule::getOS();
          $browser     = MovilDetectorModule::getBrowser();
          $referrer    = $_SERVER['HTTP_REFERER'] ?? '';

          // Registrar la visita directamente en la base de datos SQLite (profile_views)
          AnalyticsModule::logProfileView($visitedUserClean, [
            "ip_address"   => $ip,
            "country_code" => $countryCode,
            "country_name" => $countryName,
            "city_name"    => $cityName,
            "device_type"  => $deviceType,
            "os"           => $os,
            "browser"      => $browser,
            "referrer"     => $referrer
          ]);
        }
      }
    } catch (\Throwable $e) {
      error_log("VisitMiddleware error: " . $e->getMessage());
    }

    return $next($requestData);
  }

}
