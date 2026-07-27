<?php
  
namespace App\Components\UserPreview;

use App\Controllers\UserControllers;
use App\Models\DesignModels;

class userPreviewComponent{

  public static function data($view = 'UserPreview.index', $viewType = 'template', $params = []){
    
    $card = $params["data"] ?? [];
    $user = $card["profile"] ?? "";

    if (!empty($user)) {
      $dataUser = DesignModels::dataUser($user);
      if ($dataUser && isset($dataUser["card"])) {
        $card = UserControllers::formatCardImages($dataUser["card"]);
      }
    }

    return ["card" => $card];

  }

}