<?php

namespace App\Controllers;

use App\Models\DesignModels;
use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\ResponseModule;
use Base\Module\SeoModule;
use Base\Module\Session;
use Base\Module\ShareButtonModule;

/**
 * Clase UserControllers
 * 
 * Controlador delgado encargado exclusivamente de la orquestación HTTP de la página pública del perfil de usuario.
 * Delega el acceso a datos y formateo de tarjetas a UserModels y la inicialización de plantillas a DesignModels.
 */
class UserControllers extends Control {

  /**
   * Muestra la página pública del perfil de un usuario.
   *
   * @param string $user Nombre de usuario del perfil solicitado.
   * @return mixed Renderizado de la vista pública del usuario o redirección.
   */
  public function userPage(string $user) {
    $userClean = mb_strtolower($user, "UTF-8");

    // Garantizar que la plantilla inicial de diseño exista si el usuario es válido
    DesignModels::createInitialDesign($userClean);

    $userModels = new UserModels();
    $userData   = $userModels->dataUser($userClean);
    $userExists = UserModels::userExists($userClean);

    if (!$userExists) {
      $sessionUser = Session::session_data("username");
      if (!empty($sessionUser) && mb_strtolower($sessionUser, "UTF-8") !== $userClean) {
        return ResponseModule::redirect("/panel/" . mb_strtolower($sessionUser, "UTF-8"), "El usuario {$userClean}, no existe!", 2);
      }
      return ResponseModule::redirect("/", "El usuario {$userClean}, no existe!", 2);
    }

    if (!$userData) {
      return ResponseModule::redirect("/", "El usuario {$userClean}, no existe!", 2);
    }

    // 1. Verificar si el perfil está activo (active == true)
    if (!UserModels::isUserActive($userData)) {
      $sessionUser = Session::session_data("username");
      $sessionUserClean = !empty($sessionUser) ? mb_strtolower($sessionUser, "UTF-8") : null;

      if ($sessionUserClean && $sessionUserClean === $userClean) {
        return ResponseModule::redirect("/panel/" . $userClean);
      }

      return ResponseModule::redirect("/", "El perfil de {$userClean} aún no está activo.", 2);
    }

    // 2. Verificar si el perfil está oculto por el usuario (hide == true)
    if (UserModels::isUserHidden($userData)) {
      $sessionUser = Session::session_data("username");
      $sessionUserClean = !empty($sessionUser) ? mb_strtolower($sessionUser, "UTF-8") : null;

      if ($sessionUserClean && $sessionUserClean === $userClean) {
        return ResponseModule::redirect("/panel/" . $userClean, "Tu perfil actualmente está oculto.", 1);
      }

      return ResponseModule::redirect("/", "El perfil de {$userClean} está oculto.", 2);
    }

    $data = $userData;
    // Formatear imágenes a través del modelo
    $data["card"] = UserModels::formatCardImages($data["card"]);
    $contentItems = $data["card"]["content"] ?? [];

    //redes aceptadas con card og:
    $acceptedLinks = DesignControllers::orderShare();

    foreach ($contentItems as $key => $value) {
      $data["card"]["content"][$key]["share"] = ShareButtonModule::share($value["url"] ?? "", $data["card"]["desc"] ?? "", $acceptedLinks);
    }

    // Configuración de metadatos SEO
    SeoModule::setMetaDescription($data["card"]["desc"] ?? "");
    SeoModule::setTitle("Mi Cuaderno: " . ($data["card"]["title"] ?? ""));
    SeoModule::setOpenGraph([
      "title"        => ($data["card"]["title"] ?? "") . ", revisa mi cuaderno.",
      "site_name"    => "Cuaderno",
      "content"      => $data["card"]["desc"] ?? "",
      "image"        => $data["card"]["avatarSrc"] ?? "",
      "image_width"  => 500,
      "image_height" => 500,
      "link"         => DOMAIN . "/" . ltrim($data["card"]["profile"] ?? $userClean, "/"),
      "type"         => "website"
    ]);

    // Preload de la imagen LCP en el head para PageSpeed / Lighthouse
    if (!empty($data["card"]["avatarSrc"])) {
      \Base\Module\ViewOptimizerModule::preload($data["card"]["avatarSrc"], "image", ["fetchpriority" => "high"]);
    }

    return $this->view("User.index", $data);
  }

}