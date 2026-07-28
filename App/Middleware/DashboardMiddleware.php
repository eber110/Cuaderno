<?php

namespace App\Middleware;

use App\Controllers\DesignControllers;
use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use App\Models\UserModels;
use Base\Module\ResponseModule;
use Base\Module\Session;

class DashboardMiddleware implements MiddlewareInterface {

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
    $sessionUserClean = mb_strtolower($sessionUser, 'UTF-8');

    // 3. Extraer de forma robusta el parámetro :user de la URL /panel/:user
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $urlUserClean = null;
    if (preg_match('#/panel/([^/]+)#i', $uri, $matches)) {
      $urlUserClean = mb_strtolower(rawurldecode($matches[1]), 'UTF-8');
    }

    // 4. Validar que el usuario de la URL coincide con la sesión activa
    if (!empty($urlUserClean) && $urlUserClean !== $sessionUserClean) {
      return ResponseModule::redirect("/panel/" . $sessionUserClean, "No tienes permisos para acceder al panel de otro usuario.", 1);
    }

    // 5. Consultar si existen los datos del usuario
    $userModel = new UserModels;
    $userData = $userModel->dataUser($sessionUserClean);

    // Crea una plantilla en caso de que el usuario no tenga profile
    DesignControllers::initialDesign($sessionUserClean);

    // 6. Si está logueado pero userData entrega false (datos incompletos),
    // y no está ya en la página de /panel/:user, redirigir a su propio panel
    $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $currentUri = '/' . trim($currentUri, '/');
    $panelUri = '/panel/' . $sessionUserClean;

    if (($userData === false || empty($userData)) && $currentUri !== $panelUri) {
      return ResponseModule::redirect($panelUri);
    }

    return $next($requestData);

  }

}