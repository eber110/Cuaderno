<?php
  
namespace App\Controllers;

use Base\Control\Control;

class HomeControllers extends Control{

  public function home(){
    
    $data = ["user" => "Hola, Home"];
    return $this->view("Home.index", $data);
  
  }

}