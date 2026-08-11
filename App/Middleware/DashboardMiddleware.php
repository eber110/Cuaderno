<?php

namespace App\Middleware;

use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use App\Models\DesignModels;
use App\Models\UserModels;
use Base\Module\ResponseModule;
use Base\Module\Session;

/**
 * Clase DashboardMiddleware
 * 
 * Middleware de seguridad encargado de la interceptación y autorización de acceso al panel de administración.
 * Delega las reglas de negocio de propiedad de cuenta e inicialización a UserModels y DesignModels.
 */
class DashboardMiddleware implements MiddlewareInterface {

  /**
   * Maneja la autorización de la petición HTTP al panel.
   *
   * @param mixed $requestData Datos de la petición.
   * @param callable $next Siguiente middleware.
   * @return mixed Respuesta del siguiente middleware o redirección.
   */
  public function handle($requestData, callable $next) {

    // 1. Si el usuario no está logueado, redirigir a ingresar
    if (!Session::session_active()) {
      return ResponseModule::redirect("/ingresar");
    }

    // 2. Obtener el nombre de usuario de la sesión activa
    $sessionUser = Session::session_data("username");
    if (empty($sessionUser)) {
      return ResponseModule::redirect("/ingresar");
    }
    $sessionUserClean = mb_strtolower($sessionUser, "UTF-8");

    // 3. Extraer de forma robusta el parámetro :user de la URL /panel/:user
    $matches = [];
    $uri = parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH);
    $urlUserClean = null;
    if (preg_match('#/panel/([^/]+)#i', $uri, $matches)) {
      $urlUserClean = mb_strtolower(rawurldecode($matches[1]), "UTF-8");
    }

    // 4. Validar permisos mediante UserModels::canAccessDashboard
    if (!empty($urlUserClean) && !UserModels::canAccessDashboard($sessionUserClean, $urlUserClean)) {
      return ResponseModule::redirect("/panel/" . $sessionUserClean);
    }

    // 5. Garantizar que exista la tarjeta inicial si no existía
    DesignModels::createInitialDesign($sessionUserClean);

    $userModel = new UserModels();
    $userData  = $userModel->dataUser($sessionUserClean);

    // 6. Redirigir si los datos están incompletos
    $currentUri = parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH);
    $currentUri = "/" . trim($currentUri, "/");
    $panelUri   = "/panel/" . $sessionUserClean;

    if (($userData === false || empty($userData)) && $currentUri !== $panelUri) {
      return ResponseModule::redirect($panelUri);
    }

    return $next($requestData);
  }

}