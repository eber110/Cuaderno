<?php
  
namespace App\Middleware;

use App\Controllers\DesignControllers;
use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use Base\Module\ResponseModule;
use Base\Module\Session;

class AuthMiddleware implements MiddlewareInterface{

  public function handle($requestData, callable $next){

    $user = Session::session_data("username");
    if (Session::session_active() && !empty($user)) {
      $userClean = mb_strtolower($user, 'UTF-8');
      return ResponseModule::redirect("/panel/{$userClean}");
    }

    return $next($requestData);

  }

}