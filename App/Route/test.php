<?php

use Base\Module\ImgProcessModule;
use Base\Module\LogModule;
use Base\Module\RequestMetaModule;
use Base\Module\ResponseModule;
use Base\Module\Session;
use Base\Module\ShareButtonModule;
use Core\Route;

Route::get("/test/2", function(){

  
  $data = LogModule::readLogLines("/Cache/UserData/".Session::session_data("username").".json");
  if (!$data) {
    $data = false;
  }

  /*$rrss = $data[0]["card"]["content"];

  foreach ($rrss as $key => $value) {
    $data[0]["card"]["content"][$key][] = ShareButtonModule::share($value["url"], $data["card"]["desc"], []);

  }*/

  //$meta = RequestMetaModule::requestMeta("https://www.ebersanchez.cl");

  //var_dump($meta);
  var_dump($data[0]["card"]);

  //$share = ShareButtonModule::share("www.eber.cl", "Eber sanchez", []);

  //var_dump($share);
  //var_dump($_SESSION);

});

Route::post("/test/1/", function($param){
  
  

});

Route::post("/test/3", function($param){

  extract($param);
  $user = Session::session_data("username");
  $data = LogModule::readLogLines("/Cache/UserData/{$user}.json");

  if (isset($param["borders"])) {
    # code...
    $data[0]["card"]["borders"] = explode(",",$borders);
  }

  var_dump($user);
  var_dump($param);
  var_dump($data[0]["card"]);
});