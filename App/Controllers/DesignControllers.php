<?php
  
namespace App\Controllers;

use App\Models\DesignModels;
use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\LogModule;
use Base\Module\ImgProcessModule;
use Base\Module\RequestMetaModule;
use Base\Module\ResponseModule;
use Base\Module\Session;

class DesignControllers extends Control{

  public static function initialDesign(string $user){

    $user = mb_strtolower($user, 'UTF-8');
    $userData = new UserModels;
    $dataUser = $userData->dataUser($user);

    $userExists = new UserModels;
    $validUser = $userExists->userExists($user);

    if ($validUser == true && $dataUser == false) {
      $data = [
      "card" => [
        "active" => false,//control de activación del perfil, esto lo decide un json el cual tenga todos los datos necesarios para poder visualizar el perfil
        "hide" => false,//control de visualización del perfil. esto lo decide el usuario
        "profile" => $user,
        "avatar" => "no-user.webp",
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

    $customPath = ROOT_PATH . "/Cache/UserData/UserCustom/{$user}.json";
    $officialPath = ROOT_PATH . "/Cache/UserData/{$user}.json";

    if (file_exists($customPath)) {
      $dataRequest = LogModule::readLogLines($customPath);
    } else {
      $dataRequest = LogModule::readLogLines($officialPath);
    }

    $dataRequest = $dataRequest[0]["card"];

    extract($param);

    if (ImgProcessModule::imgUploaded()) {
      // 1. Imagen del avatar de perfil
      if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] === UPLOAD_ERR_OK) {
        $avatar = $_FILES["avatar"]["name"];
        $customDir = ROOT_PATH . DIR_UPLOAD_MEDIA . "/Avatar/";
        $imgProcessor = new ImgProcessModule("avatar", $customDir);
        $nombres = $imgProcessor->save_img_disk(null);

        if ($nombres !== false && !empty($nombres[0])) {
          if (!empty($dataRequest["avatar"]) && $dataRequest["avatar"] !== "no-user.webp" && strpos($dataRequest["avatar"], "Origin/") === false) {
            $imgProcessor->delete_img_disk($customDir, $dataRequest["avatar"]);
          }
          $avatar = $nombres[0];
        }
      }

      // 2. Imágenes de ítems de contenido (guardadas en /Uploads/)
      $contentImgDir = ROOT_PATH . DIR_UPLOAD_MEDIA;
      $uploadedContentImgs = [];
      foreach ($_FILES as $fileKey => $fileVal) {
        if (strpos($fileKey, "content_img_") === 0 && $fileVal["error"] === UPLOAD_ERR_OK) {
          $itemIdx = (int)str_replace("content_img_", "", $fileKey);
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

    $contentImgDir = ROOT_PATH . DIR_UPLOAD_MEDIA;
    $imgProcessor = new ImgProcessModule("", $contentImgDir);

    if (isset($param["content"])) {
      $content = [];
      $existingContentList = $dataRequest["content"] ?? [];

      foreach ($param["content"] as $index => $item) {
        // Imagen que tenía registrada el ítem en la versión previa del JSON
        $oldImg = $existingContentList[$index]["img"] ?? "no-image.webp";

        // 1. Si el ítem fue marcado para eliminar
        if (isset($item["delete"]) && ($item["delete"] === "true" || $item["delete"] === true)) {
          // Eliminar del disco únicamente si NO es la imagen por defecto
          if (!empty($oldImg) && strpos($oldImg, "Custom/") === false && strpos($oldImg, "Origin/") === false && $oldImg !== "no-image.webp") {
            $imgProcessor->delete_img_disk($contentImgDir, $oldImg);
          }
          continue;
        }

        $type  = $item["type"] ?? "link";
        $titleBtn = trim($item["title"] ?? "");
        $url   = trim($item["url"] ?? "");
        if ($url !== "" && !preg_match('#^https?://#i', $url) && strpos($url, 'mailto:') !== 0 && strpos($url, 'tel:') !== 0) {
          $url = "https://" . $url;
        }

        // 1.5 Si se solicitó borrar la imagen del ítem
        if (isset($item["delete_img"]) && ($item["delete_img"] === "true" || $item["delete_img"] === true)) {
          if (!empty($oldImg) && strpos($oldImg, "Custom/") === false && strpos($oldImg, "Origin/") === false && $oldImg !== "no-image.webp") {
            $imgProcessor->delete_img_disk($contentImgDir, $oldImg);
          }
          $item["img"] = "no-image.webp";
        }

        // 2. Determinar la imagen e imgDefault
        if (isset($uploadedContentImgs[$index])) {
          // Eliminar la imagen vieja de disco si se sube una nueva y no era por defecto
          if (!empty($oldImg) && strpos($oldImg, "Custom/") === false && strpos($oldImg, "Origin/") === false && $oldImg !== "no-image.webp") {
            $imgProcessor->delete_img_disk($contentImgDir, $oldImg);
          }
          $img = $uploadedContentImgs[$index];
          $imgDefault = true; // Imagen personalizada subida
        } else {
          $img = $item["img"] ?? "no-image.webp";
          // imgDefault: false si es por defecto, true si es personalizada
          $isDefaultImg = (empty($img) || strpos($img, "Custom/") !== false || strpos($img, "Origin/") !== false || $img === "no-image.webp" || $img === "no-user.webp");
          $imgDefault = !$isDefaultImg;
        }

        // 3. Determinar si el switch está marcado (activo)
        $rawActive = $item["active"] ?? false;
        $active = ($rawActive === "true" || $rawActive === true || $rawActive === 1 || $rawActive === "1");

        // Si el título O la URL están en blanco, el enlace DEBE permanecer inactivo (false)
        if ($titleBtn === "" || $url === "") {
          $active = false;
        }

        // 4. Extraer metadatos (metaTitle, metaDesc, metaImg) mediante RequestMetaModule
        $cardDesc   = $desc ?? $dataRequest["desc"] ?? "";
        $cardAvatar = $avatar ?? $dataRequest["avatar"] ?? "no-user.webp";

        $metaTitle = $item["metaTitle"] ?? "";
        $metaDesc  = $item["metaDesc"] ?? "";
        $metaImg   = $item["metaImg"] ?? "";

        if (!empty($url) && strpos($url, "http") === 0) {
          $metaData = RequestMetaModule::requestMeta($url);
          if ($metaData !== false && is_array($metaData)) {
            // metaTitle: title -> og["title"] -> twitter["title"] -> $titleBtn
            $metaTitle = !empty($metaData["title"])
              ? $metaData["title"]
              : (!empty($metaData["og"]["title"])
                ? $metaData["og"]["title"]
                : (!empty($metaData["twitter"]["title"])
                  ? $metaData["twitter"]["title"]
                  : $titleBtn));

            // metaDesc: description -> og["description"] -> twitter["description"] -> $cardDesc
            $metaDesc = !empty($metaData["description"])
              ? $metaData["description"]
              : (!empty($metaData["og"]["description"])
                ? $metaData["og"]["description"]
                : (!empty($metaData["twitter"]["description"])
                  ? $metaData["twitter"]["description"]
                  : $cardDesc));

            // metaImg: og["image"] -> twitter["image"] -> og["logo"]
            $metaImg = !empty($metaData["og"]["image"])
              ? $metaData["og"]["image"]
              : (!empty($metaData["twitter"]["image"])
                ? $metaData["twitter"]["image"]
                : (!empty($metaData["og"]["logo"])
                  ? $metaData["og"]["logo"]
                  : ""));
          }
        }

        // Fallbacks en caso de estar vacíos
        if (empty($metaTitle)) {
          $metaTitle = $titleBtn;
        }

        if (empty($metaDesc)) {
          $metaDesc = $cardDesc;
        }

        if (empty($metaImg)) {
          if (!empty($img) && $img !== "no-image.webp" && $img !== "no-user.webp" && strpos($img, "Custom/") === false) {
            $metaImg = $img;
          } elseif (!empty($cardAvatar) && $cardAvatar !== "no-user.webp" && strpos($cardAvatar, "Custom/") === false) {
            $metaImg = DIR_SHOW_MEDIA . "Avatar/" . $cardAvatar;
          } else {
            $metaImg = "no-image.webp";
          }
        }

        // Array asociativo
        $content[] = [
          "type" => $type,
          "img" => $img,
          "title" => $titleBtn,
          "url" => $url,
          "active" => $active,
          "imgDefault" => $imgDefault,
          "metaTitle" => $metaTitle,
          "metaDesc" => $metaDesc,
          "metaImg" => $metaImg
        ];
      }
    }

    // Si se presionó uno de los botones iniciadores (+ Enlace, + Producto, etc.)
    if (isset($param["add_content_type"])) {
      $newType = $param["add_content_type"];

      // Plantillas base según el tipo seleccionado (imgDefault = false)
      $typeTemplates = [
        "link"    => ["type" => "link", "img" => "no-image.webp", "title" => "", "url" => "", "active" => false, "imgDefault" => false, "metaTitle" => "", "metaDesc" => "", "metaImg" => ""],
        "product" => ["type" => "product", "img" => "no-image.webp", "title" => "", "url" => "", "active" => false, "imgDefault" => false, "metaTitle" => "", "metaDesc" => "", "metaImg" => ""]
      ];

      $newItem = $typeTemplates[$newType] ?? ["type" => "link", "img" => "no-image.webp", "title" => "", "url" => "", "active" => false, "imgDefault" => false];

      if (!isset($content)) {
        $content = $dataRequest["content"] ?? [];
      }
      $content[] = $newItem;
    }

    // Procesar redes sociales (rrss)
    if (isset($param["rrss"])) {
      $rrss = [];
      foreach ($param["rrss"] as $index => $item) {
        // Si la red social fue marcada para eliminar
        if (isset($item["delete"]) && ($item["delete"] === "true" || $item["delete"] === true)) {
          continue;
        }

        $name = $item[0] ?? $item["name"] ?? "";
        $url  = trim($item[1] ?? $item["url"] ?? "");
        if ($url !== "" && !preg_match('#^https?://#i', $url) && strpos($url, 'mailto:') !== 0 && strpos($url, 'tel:') !== 0) {
          $url = "https://" . $url;
        }

        if (!empty($name)) {
          $rrss[] = [$name, $url];
        }
      }
    }

    // Si se presionó el botón para añadir una nueva red social
    if (isset($param["add_rrss_name"])) {
      $newRrssName = $param["add_rrss_name"];
      if (!isset($rrss)) {
        $rrss = $dataRequest["rrss"] ?? [];
      }
      $rrss[] = [$newRrssName, ""];
    }

    $data = [
      "card" => [
        "active" => $dataRequest["active"] ?? false,//control de activación del perfil, esto lo decide un json el cual tenga todos los datos necesarios para poder visualizar el perfil
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
        "hover" => isset($hover) ? ($hover === "true" || $hover === true || $hover === 1 || $hover === "1") : $dataRequest["hover"],
        "color" => $color ?? $dataRequest["color"],
        "colorShadow3" => $colorShadow3 ?? $dataRequest["colorShadow3"],
        "rrss" => $rrss ?? $dataRequest["rrss"],
        "content" => $content ?? $dataRequest["content"]
      ]
    ];

    LogModule::deleteLog(ROOT_PATH . "/Cache/UserData/UserCustom/{$user}.json");

    LogModule::simpleLog([
      "dir" => ROOT_PATH . "/Cache/UserData/UserCustom/",
      "name" => "{$user}",
      "content" => $data
    ]);

    $user = mb_strtolower($user, "UTF-8");

    ResponseModule::redirect("/panel/{$user}"); 
  
  }

  public function saveDesign(string $user){
    $user = mb_strtolower($user, "UTF-8");
    $customPath   = ROOT_PATH . "/Cache/UserData/UserCustom/{$user}.json";
    $officialPath = ROOT_PATH . "/Cache/UserData/{$user}.json";

    $customData = DesignModels::getCustomDesign($user);

    if ($customData !== false && isset($customData["card"])) {
      $data = $customData;
      // Forzar activación a true al guardar oficialmente
      $data["card"]["active"] = true;

      // 1. Borrar archivo oficial previo para evitar que simpleLog (FILE_APPEND) duplique registros
      LogModule::deleteLog($officialPath);

      // 2. Publicar el nuevo JSON oficial
      LogModule::simpleLog([
        "dir" => ROOT_PATH . "/Cache/UserData/",
        "name" => "{$user}",
        "content" => $data
      ]);

      // 3. Eliminar el archivo borrador temporal de UserCustom
      LogModule::deleteLog($customPath);
    } else {
      $officialData = DesignModels::getOfficialDesign($user);
      if ($officialData !== false && isset($officialData["card"])) {
        $data = $officialData;
        if (!($data["card"]["active"] ?? false)) {
          $data["card"]["active"] = true;
          LogModule::deleteLog($officialPath);
          LogModule::simpleLog([
            "dir" => ROOT_PATH . "/Cache/UserData/",
            "name" => "{$user}",
            "content" => $data
          ]);
        }
      }
    }

    ResponseModule::redirect("/panel/{$user}");
  }

}