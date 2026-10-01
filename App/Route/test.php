<?php

use App\Middleware\TestMiddleware;
use App\Models\UserModels;
use Base\Module\Session;
use Core\Route;

Route::get("/test/1", function(){
  //var_dump($_SESSION);
  $userModels = new UserModels();
  $userData   = $userModels->dataOfficialUser(Session::session_data("username"));
  var_dump($userData["card"]);
}, [TestMiddleware::class]);