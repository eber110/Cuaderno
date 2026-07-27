<?php

namespace App\Components\Menu;use App\Controllers\UserControllers;
use App\Models\UserModels;
use Base\Module\Session;
use Base\Module\ShareButtonModule;

class menuUserComponent
{

  public static function data($view = "User.menuUser", $viewType = 'menu', $params = [])
  {

    $requestUri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
    $user = trim(parse_url($requestUri, PHP_URL_PATH), "/");
    $connect = false;

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
      $card = UserControllers::formatCardImages($card);
    }

    $profileUrl = DOMAIN . ($card["profile"] ?? $user);
    $profileTitle = $card["title"] ?? $user;
    $profileDesc = $card["profile"] ?? "";
    $profileAvatar = $card["avatarSrc"] ?? (DIR_UPLOAD_MEDIA_STATIC . "Custom/no-user.webp");

    $share = ShareButtonModule::share($profileUrl, $profileDesc, []);

    return [
      "connect" => $connect,
      "username" => Session::session_data("username"),
      "card" => $card,
      "userShareData" => [
        "url" => $profileUrl,
        "metaTitle" => $profileTitle,
        "metaDesc" => $profileDesc,
        "metaImg" => $profileAvatar,
        "share" => $share
      ],
      "share" => $share
    ];
  }
}