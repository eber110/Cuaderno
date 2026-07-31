<?php

namespace App\Models;

use Base\Builder\Builder;
use Base\Module\ImgProcessModule;
use Base\Module\LogModule;
use Base\Module\RequestMetaModule;

/**
 * Clase DesignModels
 * 
 * Modelo encargado de la lógica de construcción, lectura, actualización de borradores (UserCustom)
 * y publicación oficial de los diseños de las tarjetas de usuario en formato JSON.
 */
class DesignModels extends Builder {

  protected $table = "";

  /**
   * Obtiene los datos del diseño del usuario (revisa primero UserCustom, luego UserData oficial).
   *
   * @param string $user Nombre de usuario.
   * @return bool|array Datos del diseño o false si no existe.
   */
  public static function dataUser(string $user): bool|array {
    $userClean = mb_strtolower($user, "UTF-8");
    $customDir = ROOT_PATH . "/Cache/UserData/UserCustom";
    if (!is_dir($customDir)) {
      @mkdir($customDir, 0777, true);
    }

    $customFile  = $customDir . "/{$userClean}.json";
    $officialFile = ROOT_PATH . "/Cache/UserData/{$userClean}.json";

    if (file_exists($customFile)) {
      $data = LogModule::readLogLines($customFile);
      return (!$data || empty($data)) ? false : $data[0];
    }

    if (file_exists($officialFile)) {
      $data = LogModule::readLogLines($officialFile);
      return (!$data || empty($data)) ? false : $data[0];
    }

    return false;
  }

  /**
   * Verifica si existe un diseño borrador (custom) para el usuario.
   *
   * @param string $user Nombre de usuario.
   * @return bool True si existe el archivo borrador.
   */
  public static function hasCustomDesign(string $user): bool {
    $userClean = mb_strtolower($user, "UTF-8");
    return file_exists(ROOT_PATH . "/Cache/UserData/UserCustom/{$userClean}.json");
  }

  /**
   * Lee el diseño borrador (custom) del usuario si existe.
   *
   * @param string $user Nombre de usuario.
   * @return bool|array Datos del borrador o false.
   */
  public static function getCustomDesign(string $user): bool|array {
    $userClean  = mb_strtolower($user, "UTF-8");
    $customFile = ROOT_PATH . "/Cache/UserData/UserCustom/{$userClean}.json";
    if (file_exists($customFile)) {
      $data = LogModule::readLogLines($customFile);
      return (!$data || empty($data)) ? false : $data[0];
    }
    return false;
  }

  /**
   * Lee el diseño oficial (publicado) del usuario si existe.
   *
   * @param string $user Nombre de usuario.
   * @return bool|array Datos oficiales o false.
   */
  public static function getOfficialDesign(string $user): bool|array {
    $userClean    = mb_strtolower($user, "UTF-8");
    $officialFile = ROOT_PATH . "/Cache/UserData/{$userClean}.json";
    if (file_exists($officialFile)) {
      $data = LogModule::readLogLines($officialFile);
      return (!$data || empty($data)) ? false : $data[0];
    }
    return false;
  }

  /**
   * Inicializa la tarjeta de diseño por defecto para un nuevo usuario registrado.
   *
   * @param string $user Nombre de usuario.
   * @return bool True si fue creada o ya existía.
   */
  public static function createInitialDesign(string $user): bool {
    $userClean = mb_strtolower($user, "UTF-8");
    $dataUser  = (new UserModels())->dataUser($userClean);
    $validUser = UserModels::userExists($userClean);

    if ($validUser === true && $dataUser === false) {
      $data = [
        "card" => [
          "active"      => false,
          "hide"        => false,
          "profile"     => $userClean,
          "avatar"      => "no-user.webp",
          "title"       => "Titulo",
          "titleColor"  => "#383838",
          "desc"        => "Descripción del usuario",
          "header"      => "regularHero",
          "backCard"    => [
            "back_perfil" => "#a0a0a0",
            "style_back"  => "solid"
          ],
          "colorText"   => "#383838",
          "style"       => "buttonRegular",
          "borders"     => ["br0", "br0"],
          "shadow"      => "shadow-1",
          "back"        => "#d6d6d6",
          "hover"       => false,
          "color"       => "#494949",
          "colorShadow3"=> "#000000",
          "rrss"        => [],
          "content"     => []
        ]
      ];
      
      LogModule::simpleLog([
        "dir"     => ROOT_PATH . "/Cache/UserData/",
        "name"    => "{$userClean}",
        "content" => $data
      ]);
      return true;
    }
    return false;
  }

  /**
   * Procesa la actualización de configuración del diseño, subida de imágenes,
   * metadatos de enlaces y guarda el JSON borrador en UserCustom.
   *
   * @param string $user Nombre de usuario.
   * @param array|string $param Parámetros POST recibidos del formulario.
   * @return bool True tras guardar el borrador.
   */
  public static function updateCustomDesign(string $user, array|string $param): bool {
    $userClean = mb_strtolower($user, "UTF-8");
    $customDir = ROOT_PATH . "/Cache/UserData/UserCustom";
    if (!is_dir($customDir)) {
      @mkdir($customDir, 0777, true);
    }

    $customPath   = $customDir . "/{$userClean}.json";
    $officialPath = ROOT_PATH . "/Cache/UserData/{$userClean}.json";

    if (file_exists($customPath)) {
      $dataRequest = LogModule::readLogLines($customPath);
    } else {
      $dataRequest = LogModule::readLogLines($officialPath);
    }

    $dataRequest = $dataRequest[0]["card"] ?? [];

    if (is_array($param)) {
      extract($param);
    }

    // 1. Procesar imágenes subidas (Avatar e ítems de contenido)
    $uploadedContentImgs = [];
    if (ImgProcessModule::imgUploaded()) {
      if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] === UPLOAD_ERR_OK) {
        $avatarDir = ROOT_PATH . DIR_UPLOAD_MEDIA . "/Avatar/";
        $imgProcessor = new ImgProcessModule("avatar", $avatarDir);
        $nombres = $imgProcessor->save_img_disk(null);

        if ($nombres !== false && !empty($nombres[0])) {
          if (!empty($dataRequest["avatar"]) && $dataRequest["avatar"] !== "no-user.webp" && strpos($dataRequest["avatar"], "Origin/") === false) {
            $imgProcessor->delete_img_disk($avatarDir, $dataRequest["avatar"]);
          }
          $avatar = $nombres[0];
        }
      }

      $contentImgDir = ROOT_PATH . DIR_UPLOAD_MEDIA;
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

    if (isset($param["borders"]) && is_string($param["borders"])) {
      $dataRequest["borders"] = explode(",", $param["borders"]);
    }

    if (isset($param["colorText"])) {
      $titleColor = $param["colorText"];
    }

    $contentImgDir = ROOT_PATH . DIR_UPLOAD_MEDIA;
    $imgProcessor = new ImgProcessModule("", $contentImgDir);

    // 2. Procesar ítems de contenido
    if (isset($param["content"]) && is_array($param["content"])) {
      $content = [];
      $existingContentList = $dataRequest["content"] ?? [];

      foreach ($param["content"] as $index => $item) {
        $oldImg = $existingContentList[$index]["img"] ?? "no-image.webp";

        if (isset($item["delete"]) && ($item["delete"] === "true" || $item["delete"] === true)) {
          if (!empty($oldImg) && strpos($oldImg, "Custom/") === false && strpos($oldImg, "Origin/") === false && $oldImg !== "no-image.webp") {
            $imgProcessor->delete_img_disk($contentImgDir, $oldImg);
          }
          continue;
        }

        $type     = $item["type"] ?? "link";
        $titleBtn = trim($item["title"] ?? "");
        $url      = trim($item["url"] ?? "");
        if ($url !== "" && !preg_match('#^https?://#i', $url) && strpos($url, "mailto:") !== 0 && strpos($url, "tel:") !== 0) {
          $url = "https://" . $url;
        }

        if (isset($item["delete_img"]) && ($item["delete_img"] === "true" || $item["delete_img"] === true)) {
          if (!empty($oldImg) && strpos($oldImg, "Custom/") === false && strpos($oldImg, "Origin/") === false && $oldImg !== "no-image.webp") {
            $imgProcessor->delete_img_disk($contentImgDir, $oldImg);
          }
          $item["img"] = "no-image.webp";
        }

        if (isset($uploadedContentImgs[$index])) {
          if (!empty($oldImg) && strpos($oldImg, "Custom/") === false && strpos($oldImg, "Origin/") === false && $oldImg !== "no-image.webp") {
            $imgProcessor->delete_img_disk($contentImgDir, $oldImg);
          }
          $img = $uploadedContentImgs[$index];
          $imgDefault = true;
        } else {
          $img = $item["img"] ?? "no-image.webp";
          $isDefaultImg = (empty($img) || strpos($img, "Custom/") !== false || strpos($img, "Origin/") !== false || $img === "no-image.webp" || $img === "no-user.webp");
          $imgDefault = !$isDefaultImg;
        }

        $rawActive = $item["active"] ?? false;
        $active = ($rawActive === "true" || $rawActive === true || $rawActive === 1 || $rawActive === "1");
        if ($titleBtn === "" || $url === "") {
          $active = false;
        }

        $cardDesc   = $param["desc"] ?? $dataRequest["desc"] ?? "";
        $cardAvatar = $avatar ?? $dataRequest["avatar"] ?? "no-user.webp";

        $existingItem = $existingContentList[$index] ?? [];
        $existingUrl  = $existingItem["url"] ?? "";

        $metaTitle = $item["metaTitle"] ?? "";
        $metaDesc  = $item["metaDesc"] ?? "";
        $metaImg   = $item["metaImg"] ?? "";

        if ($existingUrl === $url && !empty($existingItem["metaTitle"])) {
          $metaTitle = $existingItem["metaTitle"];
          $metaDesc  = !empty($existingItem["metaDesc"]) ? $existingItem["metaDesc"] : "";
          $metaImg   = !empty($existingItem["metaImg"]) ? $existingItem["metaImg"] : "";
        } elseif (!empty($url) && strpos($url, "http") === 0) {
          $metaData = RequestMetaModule::requestMeta($url);
          if ($metaData !== false && is_array($metaData)) {
            $metaTitle = !empty($metaData["title"])
              ? $metaData["title"]
              : (!empty($metaData["og"]["title"])
                ? $metaData["og"]["title"]
                : (!empty($metaData["twitter"]["title"])
                  ? $metaData["twitter"]["title"]
                  : $titleBtn));

            $metaDesc = !empty($metaData["description"])
              ? $metaData["description"]
              : (!empty($metaData["og"]["description"])
                ? $metaData["og"]["description"]
                : (!empty($metaData["twitter"]["description"])
                  ? $metaData["twitter"]["description"]
                  : $cardDesc));

            $metaImg = !empty($metaData["og"]["image"])
              ? $metaData["og"]["image"]
              : (!empty($metaData["twitter"]["image"])
                ? $metaData["twitter"]["image"]
                : (!empty($metaData["og"]["logo"])
                  ? $metaData["og"]["logo"]
                  : ""));
          }
        }

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

        $rawImgShow = $item["imgShow"] ?? true;
        $imgShow = ($rawImgShow === "true" || $rawImgShow === true || $rawImgShow === 1 || $rawImgShow === "1");

        if (isset($item["toggle_img_show"]) && ($item["toggle_img_show"] === "true" || $item["toggle_img_show"] === true)) {
          $imgShow = !$imgShow;
        }

        $content[] = [
          "type"       => $type,
          "img"        => $img,
          "title"      => $titleBtn,
          "url"        => $url,
          "active"     => $active,
          "imgDefault" => $imgDefault,
          "imgShow"    => $imgShow,
          "metaTitle"  => $metaTitle,
          "metaDesc"   => $metaDesc,
          "metaImg"    => $metaImg
        ];
      }
    }

    if (isset($param["add_content_type"])) {
      $newType = $param["add_content_type"];
      $typeTemplates = [
        "link"    => ["type" => "link", "img" => "no-image.webp", "title" => "", "url" => "", "active" => false, "imgDefault" => false, "imgShow" => true, "metaTitle" => "", "metaDesc" => "", "metaImg" => ""],
        "product" => ["type" => "product", "img" => "no-image.webp", "title" => "", "url" => "", "active" => false, "imgDefault" => false, "imgShow" => true, "metaTitle" => "", "metaDesc" => "", "metaImg" => ""]
      ];

      $newItem = $typeTemplates[$newType] ?? ["type" => "link", "img" => "no-image.webp", "title" => "", "url" => "", "active" => false, "imgDefault" => false, "imgShow" => true];

      if (!isset($content)) {
        $content = $dataRequest["content"] ?? [];
      }
      $content[] = $newItem;
    }

    // 3. Procesar redes sociales
    if (isset($param["rrss"]) && is_array($param["rrss"])) {
      $rrss = [];
      foreach ($param["rrss"] as $index => $item) {
        if (isset($item["delete"]) && ($item["delete"] === "true" || $item["delete"] === true)) {
          continue;
        }

        $name = $item[0] ?? $item["name"] ?? "";
        $url  = trim($item[1] ?? $item["url"] ?? "");
        if ($url !== "" && !preg_match('#^https?://#i', $url) && strpos($url, "mailto:") !== 0 && strpos($url, "tel:") !== 0) {
          $url = "https://" . $url;
        }

        if (!empty($name)) {
          $rrss[] = [$name, $url];
        }
      }
    }

    if (isset($param["add_rrss_name"])) {
      $newRrssName = $param["add_rrss_name"];
      if (!isset($rrss)) {
        $rrss = $dataRequest["rrss"] ?? [];
      }
      $rrss[] = [$newRrssName, ""];
    }

    $data = [
      "card" => [
        "active"       => $dataRequest["active"] ?? false,
        "hide"         => $dataRequest["hide"] ?? false,
        "profile"      => $param["profile"] ?? $dataRequest["profile"] ?? $userClean,
        "avatar"       => $avatar ?? $dataRequest["avatar"] ?? "no-user.webp",
        "title"        => $param["title"] ?? $dataRequest["title"] ?? "Titulo",
        "titleColor"   => $titleColor ?? $dataRequest["titleColor"] ?? "#383838",
        "desc"         => $param["desc"] ?? $dataRequest["desc"] ?? "",
        "header"       => $param["header"] ?? $dataRequest["header"] ?? "regularHero",
        "backCard"     => [
          "back_perfil" => $param["back_perfil"] ?? $dataRequest["backCard"]["back_perfil"] ?? "#a0a0a0",
          "style_back"  => $param["style_back"] ?? $dataRequest["backCard"]["style_back"] ?? "solid"
        ],
        "colorText"    => $param["colorText"] ?? $dataRequest["colorText"] ?? "#383838",
        "style"        => $param["style"] ?? $dataRequest["style"] ?? "buttonRegular",
        "borders"      => $dataRequest["borders"] ?? ["br0", "br0"],
        "shadow"       => $param["shadow"] ?? $dataRequest["shadow"] ?? "shadow-1",
        "back"         => $param["back"] ?? $dataRequest["back"] ?? "#d6d6d6",
        "hover"        => isset($param["hover"]) ? ($param["hover"] === "true" || $param["hover"] === true || $param["hover"] === 1 || $param["hover"] === "1") : ($dataRequest["hover"] ?? false),
        "color"        => $param["color"] ?? $dataRequest["color"] ?? "#494949",
        "colorShadow3" => $param["colorShadow3"] ?? $dataRequest["colorShadow3"] ?? "#000000",
        "rrss"         => $rrss ?? $dataRequest["rrss"] ?? [],
        "content"      => $content ?? $dataRequest["content"] ?? []
      ]
    ];

    LogModule::deleteLog(ROOT_PATH . "/Cache/UserData/UserCustom/{$userClean}.json");

    LogModule::simpleLog([
      "dir"     => ROOT_PATH . "/Cache/UserData/UserCustom/",
      "name"    => "{$userClean}",
      "content" => $data
    ]);

    return true;
  }

  /**
   * Publica oficialmente el diseño borrador (UserCustom) hacia el diseño público (UserData)
   * activando el perfil del usuario.
   *
   * @param string $user Nombre de usuario.
   * @return bool True tras realizar la publicación oficial.
   */
  public static function publishDesign(string $user): bool {
    $userClean = mb_strtolower($user, "UTF-8");
    $customDir = ROOT_PATH . "/Cache/UserData/UserCustom";
    if (!is_dir($customDir)) {
      @mkdir($customDir, 0777, true);
    }

    $customPath   = $customDir . "/{$userClean}.json";
    $officialPath = ROOT_PATH . "/Cache/UserData/{$userClean}.json";

    $customData = self::getCustomDesign($userClean);

    if ($customData !== false && isset($customData["card"])) {
      $data = $customData;
      $data["card"]["active"] = true;

      LogModule::deleteLog($officialPath);
      LogModule::simpleLog([
        "dir"     => ROOT_PATH . "/Cache/UserData/",
        "name"    => "{$userClean}",
        "content" => $data
      ]);

      LogModule::deleteLog($customPath);
      return true;
    } else {
      $officialData = self::getOfficialDesign($userClean);
      if ($officialData !== false && isset($officialData["card"])) {
        $data = $officialData;
        if (!($data["card"]["active"] ?? false)) {
          $data["card"]["active"] = true;
          LogModule::deleteLog($officialPath);
          LogModule::simpleLog([
            "dir"     => ROOT_PATH . "/Cache/UserData/",
            "name"    => "{$userClean}",
            "content" => $data
          ]);
        }
        return true;
      }
    }

    return false;
  }

}