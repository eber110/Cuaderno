<?php
  
namespace App\Controllers;

use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\LogModule;
use Base\Module\ImgProcessModule;
use Base\Module\ResponseModule;
use Base\Module\Session;

class DesignControllers extends Control{

  public static function initialDesign(string $user){

    $user = mb_strtolower($user, 'UTF-8');
    $existsProfile = new UserModels;
    $dataUser = $existsProfile->dataUser($user);
    if (!$dataUser) {
      $data = [
      "card" => [
        "active" => false,//control de activación del perfil, esto lo decide un json el cual tenga todos los datos necesarios para poder visualizar el perfil
        "hide" => false,//control de visualización del perfil. esto lo decide el usuario
        "profile" => $user,
        "avatar" => "Origin/no-user.webp",
        "title" => "Titulo",
        "titleColor" => "#383838",
        "desc" => "Descripción del usuario",
        "header" => "regularHero",
        "backCard" => [
          "back_perfil" => "#a0a0a0",//color del background del perfil
          "style_back" => "solid"//Tipo de background (solido, gradiente, etc.)
        ],
        "colorText" => "#383838",
        "style" => "buttonRegular",
        "borders" => ["br0", "br0"],
        "shadow" => "shadow-1",
        "back" => "#d6d6d6",
        "hover" => false,
        "color" => "#494949",
        "colorShadow3" => "#000000",
        "rrss" => [],
        "content" => []
        ]
      ];
      
      LogModule::simpleLog([
        "dir" => ROOT_PATH."/Cache/UserData/",
        "name" => "{$user}",
        "content" => $data
      ]);
    }
  }

  public function configDesign(string $user, array | string $param){
    //return var_dump($param);
    //$user = Session::session_data("username");//user_index

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
        $nombres = $imgProcessor->save_img_disk(null);

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
          $savedImgs = $imgProc->save_img_disk(null);
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

    $contentImgDir = ROOT_PATH . '/App/Public/Img/';
    $imgProcessor = new ImgProcessModule("", $contentImgDir);

    if (isset($param["content"])) {
      $content = [];
      $existingContentList = $dataRequest["content"] ?? [];

      foreach ($param["content"] as $index => $item) {
        // Imagen que tenía registrada el ítem en la versión previa del JSON
        $oldImg = $existingContentList[$index][1] ?? 'Custom/Origin/no-user.webp';

        // 1. Si el ítem fue marcado para eliminar
        if (isset($item['delete']) && ($item['delete'] === 'true' || $item['delete'] === true)) {
          // Eliminar del disco únicamente si NO es la imagen por defecto
          if (!empty($oldImg) && strpos($oldImg, 'Custom/Origin/') === false && $oldImg !== 'Origin/no-user.webp') {
            $imgProcessor->delete_img_disk($contentImgDir, $oldImg);
          }
          continue;
        }

        $type  = $item['type']  ?? $item[0] ?? 'link';
        $title = trim($item['title'] ?? $item[2] ?? '');
        $url   = trim($item['url']   ?? $item[3] ?? '');

        // 2. Determinar la imagen e imgDefault (índice 5)
        if (isset($uploadedContentImgs[$index])) {
          // Eliminar la imagen vieja de disco si se sube una nueva y no era por defecto
          if (!empty($oldImg) && strpos($oldImg, 'Custom/Origin/') === false && $oldImg !== 'Origin/no-user.webp') {
            $imgProcessor->delete_img_disk($contentImgDir, $oldImg);
          }
          $img = $uploadedContentImgs[$index];
          $imgDefault = true; // Imagen personalizada subida
        } else {
          $img = $item['img'] ?? $item[1] ?? 'Custom/Origin/no-user.webp';
          // imgDefault: false si es por defecto, true si es personalizada
          $isDefaultImg = (empty($img) || strpos($img, 'Custom/Origin/') !== false || $img === 'Origin/no-user.webp');
          $imgDefault = !$isDefaultImg;
        }

        // 3. Determinar si el switch está marcado (activo)
        $rawActive = $item['active'] ?? $item[4] ?? false;
        $active = ($rawActive === 'true' || $rawActive === true || $rawActive === 1 || $rawActive === '1');

        // Si el título O la URL están en blanco, el enlace DEBE permanecer inactivo (false)
        if ($title === '' || $url === '') {
          $active = false;
        }

        // Array de 6 elementos: [type, img, title, url, active, imgDefault]
        $content[] = [$type, $img, $title, $url, $active, $imgDefault];
      }
    }

    // Si se presionó uno de los botones iniciadores (+ Enlace, + Producto, etc.)
    if (isset($param['add_content_type'])) {
      $newType = $param['add_content_type'];

      // Plantillas base según el tipo seleccionado (imgDefault = false)
      $typeTemplates = [
        'link'    => ['link', 'Custom/Origin/no-user.webp', '', '', false, false],
        'product' => ['product', 'Custom/Origin/no-user.webp', '', '', false, false]
      ];

      $newItem = $typeTemplates[$newType] ?? ['link', 'Custom/Origin/no-user.webp', '', '', false, false];

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
  
  }

}