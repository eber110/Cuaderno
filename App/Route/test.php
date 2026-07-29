<?php

use Base\Module\GeoIpModule;
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
  var_dump($_SESSION ?? []);

});

Route::post("/test/1/", function($param){
  
  

});

Route::get("/test/3", function(){

  $ip = "179.60.66.196";

  echo GeoIpModule::getCountryName($ip) . "<br>";
  echo GeoIpModule::getCountryCode($ip) . "<br>";
  echo GeoIpModule::getCityName($ip) . "<br>";
  echo GeoIpModule::getStateName($ip) . "<br>";
  echo GeoIpModule::getStateCode($ip) . "<br>";

  $meta = RequestMetaModule::requestMeta("https://www.linkedin.com/in/eber-sánchez-cornejo-08b1456a/");

  var_dump($meta);

  if (!empty($meta['og']['image'])) {
      $proxyUrl = "/proxy?url=" . urlencode($meta['og']['image']);
      echo "<h3>Imagen a través de Proxy:</h3>";
      echo "<img src='{$proxyUrl}' alt='Perfil' style='max-width: 200px;'/>";
  }
});

Route::get("/proxy", function(){
    $url = $_GET['url'] ?? '';
    if ($url) {
        // Usamos el ProxyModule que creamos en el framework
        \Base\Module\ProxyModule::proxyImage($url, ['licdn.com', 'linkedin.com']);
    }
});