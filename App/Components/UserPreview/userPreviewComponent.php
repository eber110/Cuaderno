<?php
  
namespace App\Components\UserPreview;

use App\Models\UserModels;
use Base\Module\ResponseModule;
use Base\Module\Session;

class userPreviewComponent{

  public static function data($view = 'UserPreview.index', $viewType = 'template', $params = []){
    
    return ["card" => $params["data"]];

  }

}