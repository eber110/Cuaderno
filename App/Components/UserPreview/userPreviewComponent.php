<?php
  
namespace App\Components\UserPreview;

use App\Models\UserModels;
use Base\Module\ResponseModule;
use Base\Module\Session;

class userPreviewComponent{

  public static function data($view = 'UserPreview.index', $viewType = 'template', $params = []){

    // Obtener el usuario desde los parámetros (con fallback a la sesión activa)
    $user = $params['user'] ?? $params['id'] ?? Session::session_data("username");

    if (empty($user)) {
      return ["card" => []];
    }

    $userDataModel = new UserModels;
    $userData = $userDataModel->dataUser($user);

    if (!$userData  && !Session::session_active()) {// si el usuario no existe
      return ResponseModule::redirect("/", "El usuario {$user}, no existe!", 2);
    }

    if (!$userData) {
      return ResponseModule::redirect("/panel/".Session::session_data("username"));
    }

    // Retorna los datos cargados (que ya incluyen la llave 'card')
    return $userData;

  }

}