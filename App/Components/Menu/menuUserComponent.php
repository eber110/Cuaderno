<?php

namespace App\Components\Menu;

use App\Controllers\DesignControllers;
use App\Models\UserModels;
use Base\Module\Session;
use Base\Module\ShareButtonModule;

/**
 * Clase menuUserComponent
 * 
 * Componente autocontenido para renderizar el menú y modales de compartir usuario.
 * Utiliza UserModels para obtener y formatear los datos de la tarjeta.
 */
class menuUserComponent {

  /**
   * Obtiene los datos necesarios para el componente del menú de usuario.
   *
   * @param string $view Vista objetivo.
   * @param string $viewType Tipo de vista.
   * @param array $params Parámetros del componente.
   * @return array Datos formateados para el menú.
   */
  public static function data($view = "User.menuUser", $viewType = "menu", $params = []) {
    $requestUri = $_SERVER["REQUEST_URI"] ?? "";
    $user       = trim(parse_url($requestUri, PHP_URL_PATH), "/");
    $connect    = false;

    if (Session::session_active() && Session::session_data("username") == $user) {
      $connect = true;
    }

    if (empty($user) && Session::session_active()) {
      $user = Session::session_data("username");
    }

    $dataUser = new UserModels();
    $userData = $dataUser->dataUser($user);

    if (!$userData && Session::session_active()) {
      $userData = $dataUser->dataUser(Session::session_data("username"));
    }

    $card = $userData["card"] ?? [];
    if (!empty($card)) {
      $card = UserModels::formatCardImages($card);
    }

    $profileUrl    = DOMAIN . ($card["profile"] ?? $user);
    $profileTitle  = $card["title"] ?? $user;
    $profileDesc   = $card["desc"] ?? "";
    $profileAvatar = $card["avatarSrc"] ?? (DIR_UPLOAD_MEDIA_STATIC . "Custom/no-user.webp");

    //redes aceptadas con card og:
    $acceptedLinks = DesignControllers::orderShare();

    $share = ShareButtonModule::share($profileUrl, $profileDesc, $acceptedLinks);

    return [
      "connect"       => $connect,
      "username"      => Session::session_data("username"),
      "card"          => $card,
      "userShareData" => [
        "url"       => $profileUrl,
        "metaTitle" => $profileTitle,
        "metaDesc"  => $profileDesc,
        "metaImg"   => $profileAvatar,
        "share"     => $share
      ],
      "share"         => $share
    ];
  }

}