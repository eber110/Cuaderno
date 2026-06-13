<?php
  
namespace App\Controllers;

use Base\Control\Control;
use Base\Module\ResponseModule;
use Base\Module\ValidatorModule;

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

    $userName =  ValidatorModule::camp($requestData["username"], [
      "min_length" => [4, "El nombre de usuario debe tener al menos 4 letras"],
      "space" => [false, "El nombre de usuario no debe contener espacios"]
    ]);
    if (!$userName[0]) return ResponseModule::redirect("/ingresar", $userName[1], 2);

    //validación con el model para ver si el login coincide con la bd
    var_dump($userName);
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

    $userName =  ValidatorModule::camp($requestData["username"], [
      "min_length" => [4, "El nombre de usuario debe tener al menos 4 letras"],
      "space" => [false, "El nombre de usuario no debe contener espacios"]
    ]);
    if (!$userName[0]) return ResponseModule::redirect("/registrar", $userName[1], 2);

    var_dump($requestData);
  
  }

}