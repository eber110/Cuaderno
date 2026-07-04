<?php
  
namespace App\Controllers;

use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\ResponseModule;
use Base\Module\Session;

class UserControllers extends Control{

  public function userPage(string $user){
  
    //crear un indice con los usuarios para consultar si existe. preferiblemente en formato json en la cache del proyecto
    //y si el usuario existe, se puede consultar la bd a traves de UserModels.

    $userData = new UserModels;
    $userData = $userData->dataUser($user);

    $existsUser = new UserModels;
    $existsUser = $existsUser->userExists($user);

    //esta condición debe consultar a la cache del indice de usuarios, para la seguridad del sitio.
    //la cache se renovara cada ves que se registre un nuevo usuario
    //MODIFICAR ESTA CONDICIÓN
    if (!$userData  && !Session::session_active()) {// si el usuario no existe
      return ResponseModule::redirect("/", "El usuario {$user}, no existe!", 2);
    }

    if (!$userData) {
      return ResponseModule::redirect("/panel/".Session::session_data("username"));
    }

    $data = $userData;
    
    //var_dump($data);
    //var_dump($_SESSION);
    return $this->view("User.index", $data);
  
  }

}