<?php
  
namespace App\Controllers;

use Base\Control\Control;
use Base\Module\ResponseModule;

class LoginControllers extends Control{

  //get: vista principal del login
  public function login(){
    
    return $this->view("Login.index");
  
  }

  //post: procesa los datos del login
  public function processLogin(array | string $requestData){
    
    foreach ($requestData as $value) {
      if (!$value) {
        return ResponseModule::redirect("/ingresar", "Debes rellenar todos los datos", 2);
      }
    }
    var_dump($requestData);
  
  }

  //get: register, vista del form registrador de usuarios
  public function register(){
    
    return $this->view("Register.index");
  
  }

  //post: procesa los datos del registro de nuevo usuario
  public function processRegister(array | string $requestData){
    
    foreach ($requestData as $value) {
      if ($value == "") {
        return ResponseModule::redirect("/registrar", "Debes rellenar todos los datos", 2);
      }
    }
    var_dump($requestData);
  
  }

}