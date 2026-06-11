<?php
  
namespace App\Controllers;

use Base\Control\Control;

class LoginControllers extends Control{

  //get: vista principal del login
  public function login(){
    
    return $this->view("Login.index");
  
  }

  //post: procesa los datos del login
  public function processLogin(){
    
  
  }

  //get: register, vista del form registrador de usuarios
  public function register(){
    
    return $this->view("Register.index");
  
  }

  //post: procesa los datos del registro de nuevo usuario
  public function processRegister(){
    
  
  }

}