<?php

namespace App\Models;

use Base\Builder\Builder;
use Base\Module\LogModule;

/**
 * Clase UserModels
 * 
 * Modelo encargado de la consulta de datos de usuarios, comprobaciones de existencia,
 * formateo de recursos multimedia (avatars e imágenes de botones) y validaciones de autorización.
 */
class UserModels extends Builder {

  protected $table = "users";

  /**
   * Verifica si un usuario existe en el índice del sistema (userlist.json) o en la base de datos.
   *
   * @param string $user Nombre de usuario.
   * @return bool True si el usuario existe, false en caso contrario.
   */
  public static function userExists(string $user): bool {
    $userClean = mb_strtolower($user, "UTF-8");
    $userList  = LogModule::readLogLines(ROOT_PATH . "/Cache/Users/userlist.json");
    if (in_array($userClean, $userList, true)) {
      return true;
    }

    // Consultar en la base de datos si no se encuentra en el archivo JSON
    $dbUser = (new self())->where("username", $userClean)->get_one();
    if ($dbUser) {
      LogModule::simpleLog([
        "dir"     => ROOT_PATH . "/Cache/Users",
        "name"    => "userList",
        "content" => $userClean
      ]);
      return true;
    }

    return false;
  }

  /**
   * Obtiene los datos del archivo oficial del usuario.
   *
   * @param string $user Nombre de usuario.
   * @return bool|array Array con los datos del usuario o false si no existe.
   */
  public function dataUser(string $user): bool|array {
    $userClean = mb_strtolower($user, "UTF-8");
    $data      = LogModule::readLogLines(ROOT_PATH . "/Cache/UserData/{$userClean}.json");
    return (!$data || empty($data)) ? false : $data[0];
  }

  /**
   * Formatea las rutas absolutas/públicas de avatars e imágenes de ítems de contenido para una tarjeta de perfil,
   * garantizando que todos los atributos requeridos existan.
   *
   * @param array $card Estructura de la tarjeta del perfil.
   * @return array Estructura formateada con avatarSrc, imgSrc e imgShow.
   */
  public static function formatCardImages(array $card): array {
    $defaultCard = DesignModels::getDefaultCard($card["profile"] ?? "");
    $card = array_merge($defaultCard, $card);

    if (!isset($card["backCard"]) || !is_array($card["backCard"])) {
      $card["backCard"] = $defaultCard["backCard"];
    } else {
      $card["backCard"] = array_merge($defaultCard["backCard"], $card["backCard"]);
    }

    $avatarVal = $card["avatar"] ?? "no-user.webp";
    $isDefaultAvatar = (empty($avatarVal) || $avatarVal === "no-user.webp" || strpos($avatarVal, "Origin/") !== false || strpos($avatarVal, "Custom/") !== false);
    
    $card["avatarSrc"] = $isDefaultAvatar
      ? DIR_UPLOAD_MEDIA_STATIC . "Custom/no-user.webp"
      : DIR_SHOW_MEDIA . "Avatar/" . $avatarVal;

    if (isset($card["content"]) && is_array($card["content"])) {
      foreach ($card["content"] as &$item) {
        $imgVal     = $item["img"] ?? "no-image.webp";
        $metaImgVal = $item["metaImg"] ?? "";
        $isDefaultImg = (empty($imgVal) || $imgVal === "no-image.webp" || strpos($imgVal, "Origin/") !== false || strpos($imgVal, "Custom/") !== false);
        
        if (!$isDefaultImg) {
          $item["imgSrc"] = DIR_SHOW_MEDIA . $imgVal;
        } elseif (!empty($metaImgVal) && $metaImgVal !== "no-image.webp" && (strpos($metaImgVal, "http://") === 0 || strpos($metaImgVal, "https://") === 0)) {
          $item["imgSrc"] = $metaImgVal;
        } else {
          $item["imgSrc"] = DIR_UPLOAD_MEDIA_STATIC . "Custom/no-image.webp";
        }

        $rawImgShow = $item["imgShow"] ?? true;
        $item["imgShow"] = ($rawImgShow === true || $rawImgShow === "true" || $rawImgShow === 1 || $rawImgShow === "1");
      }
      unset($item);
    } else {
      $card["content"] = [];
    }

    return $card;
  }

  /**
   * Valida si la sesión activa posee permisos para acceder al panel de administración del usuario solicitado.
   *
   * @param string|null $sessionUser Usuario en la sesión activa.
   * @param string $requestedUser Usuario cuyo panel se solicita.
   * @return bool True si tiene permiso, false en caso contrario.
   */
  public static function canAccessDashboard(?string $sessionUser, string $requestedUser): bool {
    if (empty($sessionUser)) {
      return false;
    }
    return mb_strtolower($sessionUser, "UTF-8") === mb_strtolower($requestedUser, "UTF-8");
  }

  /**
   * Verifica si la tarjeta/perfil del usuario está activa (active = true).
   *
   * @param array|string $userOrData Nombre de usuario o estructura de datos del usuario.
   * @return bool True si el perfil está activo, false si no se ha personalizado o está inactivo.
   */
  public static function isUserActive(array|string $userOrData): bool {
    if (is_string($userOrData)) {
      $userData = (new self())->dataUser($userOrData);
      if (!$userData) {
        return false;
      }
      $card = $userData["card"] ?? [];
    } else {
      $card = $userOrData["card"] ?? $userOrData;
    }

    $rawActive = $card["active"] ?? false;
    return filter_var($rawActive, FILTER_VALIDATE_BOOLEAN);
  }

  /**
   * Verifica si el perfil del usuario ha sido ocultado por decisión del usuario (hide = true).
   *
   * @param array|string $userOrData Nombre de usuario o estructura de datos del usuario.
   * @return bool True si el perfil está oculto, false en caso contrario.
   */
  public static function isUserHidden(array|string $userOrData): bool {
    if (is_string($userOrData)) {
      $userData = (new self())->dataUser($userOrData);
      if (!$userData) {
        return false;
      }
      $card = $userData["card"] ?? [];
    } else {
      $card = $userOrData["card"] ?? $userOrData;
    }

    $rawHide = $card["hide"] ?? false;
    return filter_var($rawHide, FILTER_VALIDATE_BOOLEAN);
  }

}