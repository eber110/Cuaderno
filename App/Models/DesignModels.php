<?php

namespace App\Models;

use App\Services\CloudinaryService;
use Base\Builder\Builder;
use Base\Module\ImgProcessModule;
use Base\Module\RequestMetaModule;

/**
 * Clase DesignModels
 * 
 * Modelo encargado de la construcción, lectura, actualización de borradores (is_draft = 1)
 * y publicación oficial (is_draft = 0) de las tarjetas de diseño de usuario en la base de datos SQLite (user_designs).
 */
class DesignModels extends Builder {

  protected $table = "user_designs";

  public static ?string $videoUploadError = null;
  public static ?string $videoUploadSuccess = null;

  /**
   * Transforma una fila de la tabla user_designs en la estructura asociativa de tarjeta ($data['card']).
   *
   * @param array $row Fila obtenida de SQLite.
   * @return array Estructura de tarjeta de usuario.
   */
  private static function formatRowToData(array $row): array {
    $borders = is_string($row["borders"] ?? null) ? json_decode($row["borders"], true) : ($row["borders"] ?? ["br0", "br0"]);
    $rrss    = is_string($row["rrss"] ?? null) ? json_decode($row["rrss"], true) : ($row["rrss"] ?? []);
    $content = is_string($row["content"] ?? null) ? json_decode($row["content"], true) : ($row["content"] ?? []);

    return [
      "card" => [
        "active"       => (bool)($row["active"] ?? 0),
        "hide"         => (bool)($row["hide"] ?? 1),
        "profile"      => $row["profile"] ?? $row["username"] ?? "",
        "avatar"       => $row["avatar"] ?? "no-user.webp",
        "title"        => $row["title"] ?? "Titulo",
        "titleColor"   => $row["title_color"] ?? "#383838",
        "desc"         => $row["desc"] ?? "",
        "header"       => $row["header"] ?? "regularHero",
        "voidHero"     => [
          "space" => isset($row["void_space"]) ? intval($row["void_space"]) : 450
        ],
        "void_space"   => isset($row["void_space"]) ? intval($row["void_space"]) : 450,
        "backCard"     => [
          "back_perfil"          => $row["back_perfil"] ?? "#a0a0a0",
          "style_back"           => $row["style_back"] ?? "solid",
          "back_video"           => $row["back_video"] ?? "",
          "back_video_public_id" => $row["back_video_public_id"] ?? "",
          "back_video_overlay"   => $row["back_video_overlay"] ?? "#000000",
          "back_video_opacity"   => isset($row["back_video_opacity"]) ? max(0, min(95, intval($row["back_video_opacity"]))) : 45
        ],
        "colorText"    => $row["color_text"] ?? "#383838",
        "style"        => $row["style"] ?? "buttonRegular",
        "borders"      => is_array($borders) ? $borders : ["br0", "br0"],
        "shadow"       => $row["shadow"] ?? "shadow-1",
        "back"         => $row["back"] ?? "#d6d6d6",
        "hover"        => (bool)($row["hover"] ?? 0),
        "color"        => $row["color"] ?? "#494949",
        "colorShadow3" => $row["color_shadow3"] ?? "#000000",
        "rrss"         => is_array($rrss) ? $rrss : [],
        "content"      => is_array($content) ? $content : []
      ]
    ];
  }

  /**
   * Persiste la configuración de la tarjeta en la tabla user_designs en SQLite.
   *
   * @param string $username Nombre de usuario.
   * @param int $isDraft 1 para borrador custom, 0 para oficial publicado.
   * @param array $card Estructura de la tarjeta.
   * @return bool True tras guardar con éxito.
   */
  private static function saveDesignToDb(string $username, int $isDraft, array $card): bool {
    $userClean = mb_strtolower($username, "UTF-8");
    $builder   = new Builder("user_designs");

    $payload = [
      "username"             => $userClean,
      "is_draft"             => $isDraft,
      "active"               => !empty($card["active"]) ? 1 : 0,
      "hide"                 => !empty($card["hide"]) ? 1 : 0,
      "profile"              => $card["profile"] ?? $userClean,
      "avatar"               => $card["avatar"] ?? "no-user.webp",
      "title"                => $card["title"] ?? "Titulo",
      "title_color"          => $card["titleColor"] ?? "#383838",
      "desc"                 => $card["desc"] ?? "",
      "header"               => $card["header"] ?? "regularHero",
      "void_space"           => isset($card["voidHero"]["space"]) ? intval($card["voidHero"]["space"]) : (isset($card["void_space"]) ? intval($card["void_space"]) : 450),
      "back_perfil"          => $card["backCard"]["back_perfil"] ?? "#a0a0a0",
      "style_back"           => $card["backCard"]["style_back"] ?? "solid",
      "back_video"           => $card["backCard"]["back_video"] ?? null,
      "back_video_public_id" => $card["backCard"]["back_video_public_id"] ?? null,
      "back_video_overlay"   => $card["backCard"]["back_video_overlay"] ?? "#000000",
      "back_video_opacity"   => isset($card["backCard"]["back_video_opacity"]) ? max(0, min(95, intval($card["backCard"]["back_video_opacity"]))) : 45,
      "color_text"           => $card["colorText"] ?? "#383838",
      "style"                => $card["style"] ?? "buttonRegular",
      "borders"              => json_encode($card["borders"] ?? ["br0", "br0"]),
      "shadow"               => $card["shadow"] ?? "shadow-1",
      "back"                 => $card["back"] ?? "#d6d6d6",
      "hover"                => !empty($card["hover"]) ? 1 : 0,
      "color"                => $card["color"] ?? "#494949",
      "color_shadow3"        => $card["colorShadow3"] ?? "#000000",
      "rrss"                 => json_encode($card["rrss"] ?? []),
      "content"              => json_encode($card["content"] ?? []),
      "updated_at"           => date("Y-m-d H:i:s")
    ];

    $existing = (new Builder("user_designs"))
      ->where("username", $userClean)
      ->where("is_draft", $isDraft)
      ->get_one();

    if ($existing && !empty($existing[0])) {
      $id = $existing[0]["id"];
      (new Builder("user_designs"))->update("id", $id, $payload);
    } else {
      $payload["created_at"] = date("Y-m-d H:i:s");
      (new Builder("user_designs"))->create($payload);
    }

    return true;
  }

  /**
   * Obtiene los datos del diseño del usuario (revisa primero borrador custom, luego oficial publicado).
   *
   * @param string $user Nombre de usuario.
   * @return bool|array Datos del diseño o false si no existe.
   */
  public static function dataUser(string $user): bool|array {
    $userClean = mb_strtolower($user, "UTF-8");

    // 1. Revisar si existe borrador custom (is_draft = 1)
    $custom = self::getCustomDesign($userClean);
    if ($custom !== false) {
      return $custom;
    }

    // 2. Revisar si existe diseño oficial publicado (is_draft = 0)
    $official = self::getOfficialDesign($userClean);
    if ($official !== false) {
      return $official;
    }

    return false;
  }

  /**
   * Verifica si existe un diseño borrador (custom) para el usuario en SQLite.
   *
   * @param string $user Nombre de usuario.
   * @return bool True si existe el registro de borrador.
   */
  public static function hasCustomDesign(string $user): bool {
    $userClean = mb_strtolower($user, "UTF-8");
    $row = (new Builder("user_designs"))
      ->where("username", $userClean)
      ->where("is_draft", 1)
      ->get_one();

    return !empty($row[0]);
  }

  /**
   * Lee el diseño borrador (custom) del usuario si existe en SQLite.
   *
   * @param string $user Nombre de usuario.
   * @return bool|array Datos del borrador o false.
   */
  public static function getCustomDesign(string $user): bool|array {
    $userClean = mb_strtolower($user, "UTF-8");
    $row = (new Builder("user_designs"))
      ->where("username", $userClean)
      ->where("is_draft", 1)
      ->get_one();

    if ($row && !empty($row[0])) {
      return self::formatRowToData($row[0]);
    }

    return false;
  }

  /**
   * Lee el diseño oficial (publicado) del usuario si existe en SQLite.
   *
   * @param string $user Nombre de usuario.
   * @return bool|array Datos oficiales o false.
   */
  public static function getOfficialDesign(string $user): bool|array {
    $userClean = mb_strtolower($user, "UTF-8");
    $row = (new Builder("user_designs"))
      ->where("username", $userClean)
      ->where("is_draft", 0)
      ->get_one();

    if ($row && !empty($row[0])) {
      return self::formatRowToData($row[0]);
    }

    return false;
  }

  /**
   * Retorna la estructura por defecto para la tarjeta de perfil de usuario.
   *
   * @param string $user Nombre de usuario.
   * @return array Estructura con datos predeterminados.
   */
  public static function getDefaultCard(string $user = ""): array {
    $userClean = mb_strtolower($user, "UTF-8");
    return [
      "active"       => false,
      "hide"         => true,
      "profile"      => $userClean,
      "avatar"       => "no-user.webp",
      "title"        => "Titulo",
      "titleColor"   => "#383838",
      "desc"         => "Descripción del usuario",
      "header"       => "regularHero",
      "voidHero"     => [
        "space" => 70
      ],
      "void_space"   => 70,
      "backCard"     => [
        "back_perfil"          => "#a0a0a0",
        "style_back"           => "solid",
        "back_video"           => "",
        "back_video_public_id" => "",
        "back_video_overlay"   => "#000000",
        "back_video_opacity"   => 45
      ],
      "colorText"    => "#383838",
      "style"        => "buttonRegular",
      "borders"      => ["br0", "br0"],
      "shadow"       => "shadow-1",
      "back"         => "#d6d6d6",
      "hover"        => false,
      "color"        => "#494949",
      "colorShadow3" => "#000000",
      "rrss"         => [],
      "content"      => []
    ];
  }

  /**
   * Inicializa la tarjeta de diseño por defecto en SQLite para un nuevo usuario registrado.
   *
   * @param string $user Nombre de usuario.
   * @return bool True si fue creada o ya existía.
   */
  public static function createInitialDesign(string $user): bool {
    $userClean = mb_strtolower($user, "UTF-8");
    $dataUser  = self::dataUser($userClean);
    $validUser = UserModels::userExists($userClean);

    if ($validUser === true && $dataUser === false) {
      $defaultCard = self::getDefaultCard($userClean);
      return self::saveDesignToDb($userClean, 0, $defaultCard);
    }
    return false;
  }

  /**
   * Procesa la actualización de configuración del diseño, subida de imágenes,
   * metadatos de enlaces y guarda el registro borrador (is_draft = 1) en SQLite.
   *
   * @param string $user Nombre de usuario.
   * @param array|string $param Parámetros POST recibidos del formulario.
   * @return bool True tras guardar el borrador en SQLite.
   */
  public static function updateCustomDesign(string $user, array|string $param): bool {
    $userClean = mb_strtolower($user, "UTF-8");

    $currentData  = self::getCustomDesign($userClean);
    $officialData = self::getOfficialDesign($userClean);
    $officialCard = ($officialData !== false && isset($officialData["card"])) ? $officialData["card"] : [];

    if ($currentData === false) {
      $currentData = $officialData;
    }

    $dataRequest = $currentData["card"] ?? self::getDefaultCard($userClean);

    if (is_array($param)) {
      extract($param);
    }

    // 1. Procesar imágenes subidas (Avatar e ítems de contenido)
    $uploadedContentImgs = [];
    if (ImgProcessModule::imgUploaded()) {
      if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] === UPLOAD_ERR_OK) {
        $avatarDir = ROOT_PATH . "/Uploads/Avatar/";
        if (!is_dir($avatarDir)) @mkdir($avatarDir, 0777, true);
        $imgProcessor = new ImgProcessModule("avatar", $avatarDir);
        $nombres = $imgProcessor->save_img_disk(null);

        if ($nombres !== false && !empty($nombres[0])) {
          $officialAvatar = $officialCard["avatar"] ?? "";
          if (!empty($dataRequest["avatar"]) && $dataRequest["avatar"] !== $officialAvatar && $dataRequest["avatar"] !== "no-user.webp" && strpos($dataRequest["avatar"], "Origin/") === false) {
            self::deleteAvatarFromDisk($dataRequest["avatar"]);
          }
          $avatar = $nombres[0];
        }
      }

      $contentImgDir = ROOT_PATH . "/Uploads/";
      if (!is_dir($contentImgDir)) @mkdir($contentImgDir, 0777, true);
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

    // 1.1 Procesar video de fondo (subida directa en 2do plano o subida clásica)
    $backVideo = $dataRequest["backCard"]["back_video"] ?? "";
    $backVideoPublicId = $dataRequest["backCard"]["back_video_public_id"] ?? "";
    $officialVideoPublicId = $officialCard["backCard"]["back_video_public_id"] ?? "";

    self::$videoUploadError = null;
    self::$videoUploadSuccess = null;

    if (!empty($param["back_video_url_direct"])) {
      $newUrl = $param["back_video_url_direct"];
      $newPublicId = $param["back_video_public_id_direct"] ?? "";

      if (!empty($backVideoPublicId) && $backVideoPublicId !== $newPublicId && $backVideoPublicId !== $officialVideoPublicId) {
        CloudinaryService::deleteVideo($backVideoPublicId);
      }
      $backVideo = $newUrl;
      $backVideoPublicId = $newPublicId;
      $styleBack = "video";
      self::$videoUploadSuccess = "Video de fondo subido con éxito a Cloudinary.";
    } elseif (isset($_FILES["back_video"]) && $_FILES["back_video"]["error"] === UPLOAD_ERR_OK) {
      $tmpFile = $_FILES["back_video"]["tmp_name"];
      $uploadResult = CloudinaryService::uploadVideo($tmpFile, [
        "folder"         => "cuaderno/backgrounds/{$userClean}",
        "transformation" => "du_20,w_720,c_limit,q_auto,vc_auto,ac_none,f_auto"
      ]);

      if ($uploadResult !== null && !empty($uploadResult["url"])) {
        if (!empty($backVideoPublicId) && $backVideoPublicId !== $uploadResult["public_id"] && $backVideoPublicId !== $officialVideoPublicId) {
          CloudinaryService::deleteVideo($backVideoPublicId);
        }
        $backVideo = $uploadResult["url"];
        $backVideoPublicId = $uploadResult["public_id"];
        $styleBack = "video";
        self::$videoUploadSuccess = "Video de fondo subido con éxito a Cloudinary.";
      } else {
        self::$videoUploadError = CloudinaryService::getLastErrorMessage() ?? "Error al subir video a Cloudinary.";
      }
    } elseif (isset($_FILES["back_video"]) && $_FILES["back_video"]["error"] === UPLOAD_ERR_INI_SIZE) {
      self::$videoUploadError = "El video excede el límite máximo de tamaño de archivo permitido en el servidor.";
    }

    if (isset($param["delete_video"]) && ($param["delete_video"] === "true" || $param["delete_video"] === true || $param["delete_video"] === "1")) {
      if (!empty($backVideoPublicId) && $backVideoPublicId !== $officialVideoPublicId) {
        CloudinaryService::deleteVideo($backVideoPublicId);
      }
      $backVideo = "";
      $backVideoPublicId = "";
      if (($param["style_back"] ?? "") === "video" || ($dataRequest["backCard"]["style_back"] ?? "") === "video") {
        $styleBack = "solid";
      }
    }

    if (isset($param["borders"]) && is_string($param["borders"])) {
      $dataRequest["borders"] = explode(",", $param["borders"]);
    }

    // 2. Procesar ítems de contenido
    if (isset($param["content"]) && is_array($param["content"])) {
      $content = [];
      $existingContentList = $dataRequest["content"] ?? [];
      $officialContent     = $officialCard["content"] ?? [];
      $officialImages      = [];
      foreach ($officialContent as $offItem) {
        if (!empty($offItem["img"])) {
          $officialImages[] = $offItem["img"];
        }
      }

      foreach ($param["content"] as $index => $item) {
        $oldImg = $existingContentList[$index]["img"] ?? "no-image.webp";

        if (isset($item["delete"]) && ($item["delete"] === "true" || $item["delete"] === true)) {
          if (!empty($oldImg) && !in_array($oldImg, $officialImages, true) && strpos($oldImg, "Custom/") === false && strpos($oldImg, "Origin/") === false && $oldImg !== "no-image.webp") {
            self::deleteContentImageFromDisk($oldImg);
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
          if (!empty($oldImg) && !in_array($oldImg, $officialImages, true) && strpos($oldImg, "Custom/") === false && strpos($oldImg, "Origin/") === false && $oldImg !== "no-image.webp") {
            self::deleteContentImageFromDisk($oldImg);
          }
          $item["img"] = "no-image.webp";
        }

        if (isset($uploadedContentImgs[$index])) {
          if (!empty($oldImg) && !in_array($oldImg, $officialImages, true) && strpos($oldImg, "Custom/") === false && strpos($oldImg, "Origin/") === false && $oldImg !== "no-image.webp") {
            self::deleteContentImageFromDisk($oldImg);
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

        $existingItem = null;
        if (isset($existingContentList[$index]) && ($existingContentList[$index]["url"] ?? "") === $url) {
          $existingItem = $existingContentList[$index];
        } else {
          foreach ($existingContentList as $prevItem) {
            if (($prevItem["url"] ?? "") === $url && !empty($prevItem["metaTitle"])) {
              $existingItem = $prevItem;
              break;
            }
          }
        }

        $metaTitle = $item["metaTitle"] ?? "";
        $metaDesc  = $item["metaDesc"] ?? "";
        $metaImg   = $item["metaImg"] ?? "";

        if ($existingItem !== null && !empty($existingItem["metaTitle"])) {
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
                  : ""));

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
          $metaDesc = "";
        }
        if (empty($metaImg)) {
          if (!empty($img) && $img !== "no-image.webp" && $img !== "no-user.webp" && strpos($img, "Custom/") === false) {
            $metaImg = "/Uploads/" . $img;
          } else {
            $metaImg = "";
          }
        } elseif (!str_starts_with($metaImg, "http://") && !str_starts_with($metaImg, "https://") && !str_starts_with($metaImg, "/")) {
          // Reconstruir con /Uploads/ si contenía solo el nombre del archivo
          $metaImg = "/Uploads/" . $metaImg;
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

    $cardPayload = [
      "active"       => $dataRequest["active"] ?? false,
      "hide"         => isset($param["hide_form_submitted"])
        ? (isset($param["hide"]) && ($param["hide"] === "true" || $param["hide"] === true || $param["hide"] === 1 || $param["hide"] === "1"))
        : (isset($param["hide"]) ? ($param["hide"] === "true" || $param["hide"] === true || $param["hide"] === 1 || $param["hide"] === "1") : ($dataRequest["hide"] ?? false)),
      "profile"      => $param["profile"] ?? $dataRequest["profile"] ?? $userClean,
      "avatar"       => $avatar ?? $dataRequest["avatar"] ?? "no-user.webp",
      "title"        => $param["title"] ?? $dataRequest["title"] ?? "Titulo",
      "titleColor"   => $param["titleColor"] ?? ($param["colorText"] ?? ($dataRequest["titleColor"] ?? "#383838")),
      "desc"         => $param["desc"] ?? $dataRequest["desc"] ?? "",
      "header"       => $param["header"] ?? $dataRequest["header"] ?? "regularHero",
      "voidHero"     => [
        "space" => isset($param["void_space"]) ? intval($param["void_space"]) : ($dataRequest["voidHero"]["space"] ?? $dataRequest["void_space"] ?? 450)
      ],
      "void_space"   => isset($param["void_space"]) ? intval($param["void_space"]) : ($dataRequest["void_space"] ?? $dataRequest["voidHero"]["space"] ?? 450),
      "backCard"     => [
        "back_perfil"          => $param["back_perfil"] ?? $dataRequest["backCard"]["back_perfil"] ?? "#a0a0a0",
        "style_back"           => $styleBack ?? $param["style_back"] ?? $dataRequest["backCard"]["style_back"] ?? "solid",
        "back_video"           => $backVideo ?? $dataRequest["backCard"]["back_video"] ?? "",
        "back_video_public_id" => $backVideoPublicId ?? $dataRequest["backCard"]["back_video_public_id"] ?? "",
        "back_video_overlay"   => $param["back_video_overlay"] ?? $dataRequest["backCard"]["back_video_overlay"] ?? "#000000",
        "back_video_opacity"   => isset($param["back_video_opacity"]) ? max(0, min(95, intval($param["back_video_opacity"]))) : ($dataRequest["backCard"]["back_video_opacity"] ?? 45)
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
    ];

    // Guardar en la tabla user_designs con is_draft = 1
    return self::saveDesignToDb($userClean, 1, $cardPayload);
  }

  /**
   * Elimina un archivo de avatar del disco de manera segura.
   *
   * @param string $avatarFilename Nombre del archivo de avatar.
   * @return void
   */
  public static function deleteAvatarFromDisk(string $avatarFilename): void {
    if (empty($avatarFilename) || $avatarFilename === "no-user.webp" || str_contains($avatarFilename, "Custom/") || str_contains($avatarFilename, "Origin/")) {
      return;
    }
    $avatarDir = ROOT_PATH . "/Uploads/Avatar/";
    $filePath  = $avatarDir . $avatarFilename;
    if (file_exists($filePath) && is_file($filePath)) {
      @unlink($filePath);
    }
  }

  /**
   * Elimina un archivo de imagen de contenido del disco de manera segura.
   *
   * @param string $imageFilename Nombre del archivo de imagen.
   * @return void
   */
  public static function deleteContentImageFromDisk(string $imageFilename): void {
    if (empty($imageFilename) || $imageFilename === "no-image.webp" || $imageFilename === "no-user.webp" || str_contains($imageFilename, "Custom/") || str_contains($imageFilename, "Origin/")) {
      return;
    }
    $contentImgDir = ROOT_PATH . "/Uploads/";
    $filePath      = $contentImgDir . $imageFilename;
    if (file_exists($filePath) && is_file($filePath)) {
      @unlink($filePath);
    }
  }

  /**
   * Publica oficialmente el diseño borrador (is_draft = 1) hacia el diseño público (is_draft = 0)
   * activando el perfil del usuario (active = true) en SQLite y eliminando archivos obsoletos.
   *
   * @param string $user Nombre de usuario.
   * @return bool True tras realizar la publicación oficial.
   */
  public static function publishDesign(string $user): bool {
    $userClean    = mb_strtolower($user, "UTF-8");
    $customData   = self::getCustomDesign($userClean);
    $officialData = self::getOfficialDesign($userClean);

    if ($customData !== false && isset($customData["card"])) {
      $card         = $customData["card"];
      $officialCard = ($officialData !== false && isset($officialData["card"])) ? $officialData["card"] : [];

      // 1. Si el avatar oficial fue reemplazado, eliminar el avatar anterior del disco
      $oldOfficialAvatar = $officialCard["avatar"] ?? "";
      $newAvatar         = $card["avatar"] ?? "";
      if (!empty($oldOfficialAvatar) && $oldOfficialAvatar !== $newAvatar) {
        self::deleteAvatarFromDisk($oldOfficialAvatar);
      }

      // 2. Si el video oficial de Cloudinary fue reemplazado, eliminar el video anterior de Cloudinary
      $oldVideoPublicId = $officialCard["backCard"]["back_video_public_id"] ?? "";
      $newVideoPublicId = $card["backCard"]["back_video_public_id"] ?? "";
      if (!empty($oldVideoPublicId) && $oldVideoPublicId !== $newVideoPublicId) {
        CloudinaryService::deleteVideo($oldVideoPublicId);
      }

      // 3. Si imágenes de contenido oficiales fueron reemplazadas o eliminadas, eliminarlas del disco
      $oldOfficialContent = $officialCard["content"] ?? [];
      $newContent         = $card["content"] ?? [];
      $newImages          = [];
      foreach ($newContent as $nItem) {
        if (!empty($nItem["img"])) {
          $newImages[] = $nItem["img"];
        }
      }

      foreach ($oldOfficialContent as $oldItem) {
        $oldImg = $oldItem["img"] ?? "";
        if (!empty($oldImg) && !in_array($oldImg, $newImages, true)) {
          self::deleteContentImageFromDisk($oldImg);
        }
      }

      $card["active"] = true;

      // 4. Guardar como versión oficial publicada (is_draft = 0)
      self::saveDesignToDb($userClean, 0, $card);

      // 5. Eliminar el registro borrador (is_draft = 1)
      $draftRow = (new Builder("user_designs"))
        ->where("username", $userClean)
        ->where("is_draft", 1)
        ->get_one();

      if (!empty($draftRow[0]["id"])) {
        (new Builder("user_designs"))->delete("id", $draftRow[0]["id"]);
      }

      return true;
    } else {
      if ($officialData !== false && isset($officialData["card"])) {
        $card = $officialData["card"];
        if (!($card["active"] ?? false)) {
          $card["active"] = true;
          self::saveDesignToDb($userClean, 0, $card);
        }
        return true;
      }
    }

    return false;
  }

  /**
   * Descarta y elimina el diseño borrador (is_draft = 1) del usuario en SQLite,
   * eliminando del disco cualquier imagen subida en el borrador y de Cloudinary cualquier video subido,
   * revirtiendo cualquier cambio no guardado a la versión oficial publicada (is_draft = 0).
   *
   * @param string $user Nombre de usuario.
   * @return bool True tras descartar con éxito.
   */
  public static function discardDesign(string $user): bool {
    $userClean = mb_strtolower($user, "UTF-8");

    $draftData    = self::getCustomDesign($userClean);
    $officialData = self::getOfficialDesign($userClean);

    if ($draftData !== false && isset($draftData["card"])) {
      $draftCard    = $draftData["card"];
      $officialCard = ($officialData !== false && isset($officialData["card"])) ? $officialData["card"] : [];

      // 1. Eliminar avatar del borrador si es diferente al oficial
      $draftAvatar    = $draftCard["avatar"] ?? "";
      $officialAvatar = $officialCard["avatar"] ?? "";
      if (!empty($draftAvatar) && $draftAvatar !== $officialAvatar) {
        self::deleteAvatarFromDisk($draftAvatar);
      }

      // 2. Eliminar video de Cloudinary del borrador si es diferente al oficial
      $draftVideoPublicId    = $draftCard["backCard"]["back_video_public_id"] ?? "";
      $officialVideoPublicId = $officialCard["backCard"]["back_video_public_id"] ?? "";
      if (!empty($draftVideoPublicId) && $draftVideoPublicId !== $officialVideoPublicId) {
        CloudinaryService::deleteVideo($draftVideoPublicId);
      }

      // 3. Eliminar imágenes de ítems de contenido del borrador que no pertenezcan a la versión oficial
      $draftContent    = $draftCard["content"] ?? [];
      $officialContent = $officialCard["content"] ?? [];
      $officialImages  = [];
      foreach ($officialContent as $offItem) {
        if (!empty($offItem["img"])) {
          $officialImages[] = $offItem["img"];
        }
      }

      foreach ($draftContent as $draftItem) {
        $draftImg = $draftItem["img"] ?? "";
        if (!empty($draftImg) && !in_array($draftImg, $officialImages, true)) {
          self::deleteContentImageFromDisk($draftImg);
        }
      }

      // 4. Eliminar el registro borrador de SQLite
      $draftRow = (new Builder("user_designs"))
        ->where("username", $userClean)
        ->where("is_draft", 1)
        ->get_one();

      if (!empty($draftRow[0]["id"])) {
        (new Builder("user_designs"))->delete("id", $draftRow[0]["id"]);
      }

      return true;
    }

    return true;
  }

}