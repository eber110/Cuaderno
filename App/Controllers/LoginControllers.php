<?php
  
namespace App\Controllers;

use App\Models\LoginModels;
use Base\Control\Control;
use Base\Module\ResponseModule;
use Base\Module\Session;
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
        
    $userExists = new LoginModels;
    $userTrue = $userExists->loginApp($requestData["username"], $requestData["pass"]);

    if (!$userTrue[0]) {
      if (!$userTrue[1] == 1) return ResponseModule::redirect("/ingresar", "El usuario no es valido");
      if (!$userTrue[1] == 0) return ResponseModule::redirect("/ingresar", "La contraseña no es valida");
    }else{
      if (!$userTrue["encrypted"]) return ResponseModule::redirect("/", "Tú contraseña no esta encriptada", 1);
    }

    return ResponseModule::redirect("/");
  
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

    #---Validación de datos
    $userName =  ValidatorModule::camp($requestData["username"], [
      "min_length" => [4, "El nombre de usuario debe tener al menos 4 letras"],
      "space" => [false, "El nombre de usuario no debe contener espacios"]
    ]);
    if (!$userName[0]) return ResponseModule::redirect("/registrar", $userName[1], 2);

    var_dump($requestData);
  
  }

  public function exitApp(){
    
    Session::session_end_all();
    return ResponseModule::redirect("/");
  
  }

}