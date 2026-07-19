<?php
  
namespace App\Controllers;

use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\ResponseModule;
use Base\Module\Session;

class UserControllers extends Control{

  public function userPage(string $user){
  
    $user = mb_strtolower($user, 'UTF-8');
    //crear un indice con los usuarios para consultar si existe. preferiblemente en formato json en la cache del proyecto
    //y si el usuario existe, se puede consultar la bd a traves de UserModels.

    //Si por cualquier caso el usuario no tiene un profile, se creara una plantilla para que la pueda editar
    DesignControllers::initialDesign($user);//user_index

    $userData = new UserModels;
    $userData = $userData->dataUser($user);//user_index

    $existsUser = new UserModels;
    $existsUser = $existsUser->userExists($user);//user_index

    if (!$existsUser) {
      return ResponseModule::redirect("/".Session::session_data("username"), "El usuario {$user}, no existe!", 2);
    }

    //esta condición debe consultar a la cache del indice de usuarios, para la seguridad del sitio.
    //la cache se renovara cada ves que se registre un nuevo usuario
    //MODIFICAR ESTA CONDICIÓN
    if (!$userData  && !Session::session_active()) {// si el usuario no existe
      return ResponseModule::redirect("/", "El usuario {$user}, no existe!", 2);
    }

    if ($userData["card"]["active"] === false) {
      return ResponseModule::redirect("/panel/".Session::session_data("username"));
    }

    $data = $userData;
    
    //var_dump($data);
    //var_dump($_SESSION);
    return $this->view("User.index", $data);
  
  }

}