<?php
  
namespace App\Middleware;

use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use Base\Module\ResponseModule;
use Base\Module\Session;

class AuthMiddleware implements MiddlewareInterface{

  public function handle($requestData, callable $next){

    $user = Session::session_data("username");
    if (Session::session_active() == true) return ResponseModule::redirect("/{$user}");

    return $next($requestData);

  }

}