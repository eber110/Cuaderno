<?php

namespace App\Models;

use Base\Builder\BuilderSqlite;

/**
 * Clase UserModels
 * 
 * Modelo encargado de la consulta de datos de usuarios, comprobaciones de existencia en SQLite (tabla users),
 * formateo de recursos multimedia (avatars e imágenes de botones) y validaciones de autorización.
 */
class UserModels extends BuilderSqlite {

  protected $table = "users";

  /**
   * Verifica si un usuario existe en la base de datos SQLite (tabla users).
   *
   * @param string $user Nombre de usuario.
   * @return bool True si el usuario existe, false en caso contrario.
   */
  public static function userExists(string $user): bool {
    $userClean = mb_strtolower($user, "UTF-8");
    $dbUser = (new self())->where("username", $userClean)->get_one();

    return !empty($dbUser[0]);
  }

  /**
   * Obtiene los datos del perfil del usuario (borrador o publicado) desde SQLite (tabla user_designs).
   *
   * @param string $user Nombre de usuario.
   * @return bool|array Array con los datos del usuario o false si no existe.
   */
  public function dataUser(string $user): bool|array {
    $userClean = mb_strtolower($user, "UTF-8");
    return DesignModels::dataUser($userClean);
  }

  /**
   * Obtiene los datos del perfil oficial publicado (is_draft = 0) del usuario desde SQLite.
   *
   * @param string $user Nombre de usuario.
   * @return bool|array Array con los datos oficiales o false si no existe.
   */
  public function dataOfficialUser(string $user): bool|array {
    $userClean = mb_strtolower($user, "UTF-8");
    return DesignModels::getOfficialDesign($userClean);
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
    
    if ($isDefaultAvatar) {
      $card["avatarSrc"] = DIR_UPLOAD_MEDIA_STATIC . "Custom/no-user.webp";
    } else {
      $avatarDisk = ROOT_PATH . "/Uploads/Avatar/" . $avatarVal;
      $card["avatarSrc"] = file_exists($avatarDisk)
        ? DIR_SHOW_MEDIA . "Avatar/" . $avatarVal
        : DIR_UPLOAD_MEDIA_STATIC . "Custom/no-user.webp";
    }

    if (isset($card["content"]) && is_array($card["content"])) {
      foreach ($card["content"] as &$item) {
        $imgVal     = $item["img"] ?? "no-image.webp";
        $metaImgVal = $item["metaImg"] ?? "";
        $hasCustomImg = (!empty($imgVal) && $imgVal !== "no-image.webp" && strpos($imgVal, "Origin/") === false && strpos($imgVal, "Custom/") === false);
        
        $resolvedImg = "";
        if ($hasCustomImg) {
          $diskPath = ROOT_PATH . "/Uploads/" . $imgVal;
          if (file_exists($diskPath)) {
            $resolvedImg = DIR_SHOW_MEDIA . $imgVal;
          }
        }

        if (!empty($resolvedImg)) {
          // Prioridad 1: Imagen personalizada asignada por el usuario (si existe en disco)
          $item["imgSrc"] = $resolvedImg;
          if (empty($metaImgVal) || $metaImgVal === $imgVal || str_starts_with($metaImgVal, "/Uploads/")) {
            $item["metaImg"] = $resolvedImg;
          }
        } elseif (!empty($metaImgVal) && $metaImgVal !== "no-image.webp" && (str_starts_with($metaImgVal, "http://") || str_starts_with($metaImgVal, "https://"))) {
          // Prioridad 2: Imagen rescatada de OpenGraph (metaImg)
          $item["imgSrc"] = $metaImgVal;
          $item["metaImg"] = $metaImgVal;
        } elseif (!empty($metaImgVal) && str_starts_with($metaImgVal, "/") && file_exists(ROOT_PATH . $metaImgVal)) {
          // Imagen local válida en metaImg
          $item["imgSrc"] = $metaImgVal;
          $item["metaImg"] = $metaImgVal;
        } else {
          // Si no hay ninguna imagen válida disponible
          $item["imgSrc"] = DIR_UPLOAD_MEDIA_STATIC . "Custom/no-image.webp";
          $item["metaImg"] = "";
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