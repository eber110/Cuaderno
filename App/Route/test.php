<?php

use Base\Module\ImgProcessModule;
use Base\Module\LogModule;
use Base\Module\ResponseModule;
use Base\Module\Session;
use Core\Route;

Route::get("/test/2", function(){

  
  $data = LogModule::readLogLines("/Cache/UserData/tomi.json");
  if (!$data) {
    $data = false;
  }

  var_dump($data[0]["card"]);

});

Route::post("/test/1/", function($param){
  //return var_dump($param);
  $user = Session::session_data("username");//user_index

  $dataRequest = LogModule::readLogLines(ROOT_PATH."/Cache/UserData/{$user}.json");
  $dataRequest = $dataRequest[0]["card"];
  LogModule::deleteLog(ROOT_PATH."/Cache/UserData/{$user}.json");

  extract($param);

  if (ImgProcessModule::imgUploaded()) {
    // 1. Imagen del avatar de perfil
    if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] === UPLOAD_ERR_OK) {
      $avatar = $_FILES["avatar"]["name"];
      $customDir = ROOT_PATH . '/App/Public/Img/Custom/';
      $imgProcessor = new ImgProcessModule("avatar", $customDir);
      $nombres = $imgProcessor->save_img_disk();
      
      if ($nombres !== false && !empty($nombres[0])) {
        if (!empty($dataRequest["avatar"]) && $dataRequest["avatar"] !== "Origin/no-user.webp") {
          $imgProcessor->delete_img_disk($customDir, $dataRequest["avatar"]);
        }
        $avatar = $nombres[0];
      }
    }

    // 2. Imágenes de ítems de contenido (guardadas en /App/Public/Img/)
    $contentImgDir = ROOT_PATH . '/App/Public/Img/';
    $uploadedContentImgs = [];
    foreach ($_FILES as $fileKey => $fileVal) {
      if (strpos($fileKey, 'content_img_') === 0 && $fileVal['error'] === UPLOAD_ERR_OK) {
        $itemIdx = (int)str_replace('content_img_', '', $fileKey);
        $imgProc = new ImgProcessModule($fileKey, $contentImgDir);
        $savedImgs = $imgProc->save_img_disk();
        if ($savedImgs !== false && !empty($savedImgs[0])) {
          $uploadedContentImgs[$itemIdx] = $savedImgs[0];
        }
      }
    }
  }

  if (isset($param["borders"])) {
    $dataRequest["borders"] = explode(",",$borders);
  }

  if (isset($param["colorText"])) {
    $titleColor = $colorText;
  }

  if (isset($param["content"])) {
    $content = [];
    foreach ($param["content"] as $index => $item) {
      // Si fue marcado para eliminar, omitir
      if (isset($item['delete']) && ($item['delete'] === 'true' || $item['delete'] === true)) {
        continue;
      }

      $type   = $item['type']  ?? $item[0] ?? 'link';
      $img    = $uploadedContentImgs[$index] ?? $item['img'] ?? $item[1] ?? 'prod.webp';
      $title  = trim($item['title'] ?? $item[2] ?? '');
      $url    = trim($item['url']   ?? $item[3] ?? '');

      // Determinar si el switch está marcado (activo)
      $rawActive = $item['active'] ?? $item[4] ?? true;
      $active = ($rawActive === 'true' || $rawActive === true || $rawActive === '1' || $rawActive === 1);

      // Si tanto título como URL están vacíos, omitir
      if ($title === '' && $url === '') {
        continue;
      }

      $content[] = [$type, $img, $title, $url, $active];
    }
  }

  // Si se presionó uno de los botones iniciadores (+ Enlace, + Producto, etc.)
  if (isset($param['add_content_type'])) {
    $newType = $param['add_content_type'];

    // Plantillas base según el tipo seleccionado
    $typeTemplates = [
      'link'    => ['link', 'prod.webp', 'Nuevo Enlace', 'https://www.ebersanchez.cl', true],
      'product' => ['product', 'prod.webp', 'Nuevo Producto', 'https://www.ebersanchez.cl', true]
    ];

    $newItem = $typeTemplates[$newType] ?? ['link', 'prod.webp', 'Nuevo Elemento', 'https://www.ebersanchez.cl', true];

    if (!isset($content)) {
      $content = $dataRequest['content'] ?? [];
    }
    $content[] = $newItem;
  }

  $data = [
    "card" => [
      "active" => true ?? $dataRequest["active"],//control de activación del perfil, esto lo decide un json el cual tenga todos los datos necesarios para poder visualizar el perfil
      "hide" => false ?? $dataRequest["hide"],//control de visualización del perfil. esto lo decide el usuario
      "profile" => $profile ?? $dataRequest["profile"],
      "avatar" => $avatar ?? $dataRequest["avatar"],
      "title" => $title ?? $dataRequest["title"],
      "titleColor" => $titleColor ?? $dataRequest["titleColor"],
      "desc" => $desc ?? $dataRequest["desc"],
      "header" => $header ?? $dataRequest["header"],
      "backCard" => [
        "back_perfil" => $back_perfil ?? $dataRequest["backCard"]["back_perfil"],//color del background del perfil
        "style_back" => $style_back ?? $dataRequest["backCard"]["style_back"]//Tipo de background (solido, gradiente, etc.)
      ],
      "colorText" => $colorText ?? $dataRequest["colorText"],
      "style" => $style ?? $dataRequest["style"],
      "borders" => $dataRequest["borders"],
      "shadow" => $shadow ?? $dataRequest["shadow"],
      "back" => $back ?? $dataRequest["back"],
      "hover" => isset($hover) ? ($hover === 'true' || $hover === true || $hover === 1 || $hover === '1') : $dataRequest["hover"],
      "color" => $color ?? $dataRequest["color"],
      "colorShadow3" => $colorShadow3 ?? $dataRequest["colorShadow3"],
      "rrss" => [
        [
          "x",
          "https://x.com/eberestudio"
        ],
        [
          "Linkedin",
          "https://www.linkedin.com/in/eber-s%C3%A1nchez-cornejo-08b1456a/"
        ]
      ],
      "content" => $content ?? $dataRequest["content"]
    ]
  ];

  LogModule::simpleLog([
    "dir" => ROOT_PATH."/Cache/UserData/",
    "name" => "{$user}",
    "content" => $data
  ]);

  $user = mb_strtolower($user, 'UTF-8');

  ResponseModule::redirect("/panel/{$user}"); 

});

Route::post("/test/3", function($param){

  extract($param);
  $user = Session::session_data("username");
  $data = LogModule::readLogLines("/Cache/UserData/{$user}.json");

  if (isset($param["borders"])) {
    # code...
    $data[0]["card"]["borders"] = explode(",",$borders);
  }

  var_dump($user);
  var_dump($param);
  var_dump($data[0]["card"]);
});