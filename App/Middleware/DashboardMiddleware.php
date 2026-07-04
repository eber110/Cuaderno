<?php
  
namespace App\Middleware;

use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use App\Models\UserModels;
use Base\Module\ResponseModule;
use Base\Module\Session;

class DashboardMiddleware implements MiddlewareInterface{

  public function handle($requestData, callable $next){

    // 1. Si el usuario no está logueado, redirigir a ingresar
    if (!Session::session_active()) {
      ResponseModule::redirect("/ingresar");
    }

    // 2. Obtener el nombre de usuario de la sesión
    $user = Session::session_data("username");
    $user = mb_strtolower($user, 'UTF-8');

    // --- NUEVA VALIDACIÓN: Asegurar que el usuario de la URL coincide con la sesión activa ---
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));
    $urlUser = $segments[1] ?? null;

    if ($urlUser !== null) {
      $urlUserClean = mb_strtolower($urlUser, 'UTF-8');
      if ($urlUserClean !== $user) {
        // Redirigir al usuario logueado a su propio panel con un aviso
        ResponseModule::redirect("/panel/" . $user, "No tienes permisos para acceder al panel de otro usuario.", 1);
      }
    }

    // 3. Consultar si existen los datos del usuario
    $userModel = new UserModels;
    $userData = $userModel->dataUser($user);

    // 4. Si está logueado pero userData entrega false (datos incompletos),
    // y no está ya en la página de /panel/:user, redirigir a rellenar sus datos
    $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? null, PHP_URL_PATH);
    $currentUri = '/' . trim($currentUri, '/');
    $panelUri = '/panel/' . trim($user, '/');

    if (($userData === false || empty($userData)) && $currentUri !== $panelUri) {
      ResponseModule::redirect($panelUri);
    }

    return $next($requestData);

  }

}