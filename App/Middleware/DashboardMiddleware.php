<?php
  
namespace App\Middleware;

use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use Base\Module\ResponseModule;
use Base\Module\Session;

class DashboardMiddleware implements MiddlewareInterface{

  public function handle($requestData, callable $next){

    if (!Session::my_session(Session::session_data("user_id"))) {
      return ResponseModule::redirect("/ingresar");
    }

    return $next($requestData);

  }

}