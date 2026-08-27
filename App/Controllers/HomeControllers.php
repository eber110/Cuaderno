<?php
  
namespace App\Controllers;

use Base\Control\Control;

class HomeControllers extends Control{

  public function home(){
    
    $data = ["user" => "Hola, Home"];

    //Falta todo el seo y la configuración de inicio

    return $this->view("Home.index", $data);
  
  }

}