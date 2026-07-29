<?php
  
namespace App\Controllers;

use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\ResponseModule;
use Base\Module\SeoModule;
use Base\Module\Session;
use Base\Module\ShareButtonModule;

class UserControllers extends Control{

  public static function formatCardImages(array $card): array {
    $avatarVal = $card["avatar"] ?? 'no-user.webp';
    $isDefaultAvatar = (empty($avatarVal) || $avatarVal === 'no-user.webp' || strpos($avatarVal, 'Origin/') !== false || strpos($avatarVal, 'Custom/') !== false);
    
    $card["avatarSrc"] = $isDefaultAvatar
      ? DIR_UPLOAD_MEDIA_STATIC . "Custom/no-user.webp"
      : DIR_SHOW_MEDIA . "Avatar/" . $avatarVal;

    if (isset($card["content"]) && is_array($card["content"])) {
      foreach ($card["content"] as &$item) {
        $imgVal = $item["img"] ?? 'no-image.webp';
        $metaImgVal = $item["metaImg"] ?? '';
        $isDefaultImg = (empty($imgVal) || $imgVal === 'no-image.webp' || strpos($imgVal, 'Origin/') !== false || strpos($imgVal, 'Custom/') !== false);
        
        if (!$isDefaultImg) {
          $item["imgSrc"] = DIR_SHOW_MEDIA . $imgVal;
        } elseif (!empty($metaImgVal) && $metaImgVal !== 'no-image.webp' && (strpos($metaImgVal, 'http://') === 0 || strpos($metaImgVal, 'https://') === 0)) {
          $item["imgSrc"] = $metaImgVal;
        } else {
          $item["imgSrc"] = DIR_UPLOAD_MEDIA_STATIC . "Custom/no-image.webp";
        }

        $rawImgShow = $item["imgShow"] ?? true;
        $item["imgShow"] = ($rawImgShow === true || $rawImgShow === 'true' || $rawImgShow === 1 || $rawImgShow === '1');
      }
      unset($item);
    }

    return $card;
  }

  public function userPage(string $user){
  
    $user = mb_strtolower($user, 'UTF-8');
    //crear un indice con los usuarios para consultar si existe. preferiblemente en formato json en la cache del proyecto
    //y si el usuario existe, se puede consultar la bd a traves de UserModels.

    //Si por cualquier caso el usuario no tiene un profile, se creara una plantilla para que la pueda editar
    DesignControllers::initialDesign($user);//user_index

    $userData = new UserModels;
    $userData = $userData->dataUser($user);//user_index

    $existsUser = new UserModels;
    $existsUser = $existsUser->userExists($user);//user_index

    if (!$existsUser) {
      $sessionUser = Session::session_data("username");
      if (!empty($sessionUser) && mb_strtolower($sessionUser, 'UTF-8') !== $user) {
        return ResponseModule::redirect("/panel/" . mb_strtolower($sessionUser, 'UTF-8'), "El usuario {$user}, no existe!", 2);
      }
      return ResponseModule::redirect("/", "El usuario {$user}, no existe!", 2);
    }

    //esta condición debe consultar a la cache del indice de usuarios, para la seguridad del sitio.
    //la cache se renovara cada ves que se registre un nuevo usuario
    //MODIFICAR ESTA CONDICIÓN
    if (!$userData) {
      return ResponseModule::redirect("/", "El usuario {$user}, no existe!", 2);
    }

    $isActive = filter_var($userData["card"]["active"] ?? false, FILTER_VALIDATE_BOOLEAN);
    if (!$isActive) {
      $sessionUser = Session::session_data("username");
      $sessionUserClean = !empty($sessionUser) ? mb_strtolower($sessionUser, 'UTF-8') : null;

      if ($sessionUserClean && $sessionUserClean === $user) {
        return ResponseModule::redirect("/panel/" . $user);
      }

      return ResponseModule::redirect("/", "El perfil de {$user} aún no está activo.", 2);
    }

    $data = $userData;
    $data["card"] = self::formatCardImages($data["card"]);
    $rrss = $data["card"]["content"];

    foreach ($rrss as $key => $value) {
      $data["card"]["content"][$key]["share"] = ShareButtonModule::share($value["url"], $data["card"]["desc"], []);
    }

    //configuración SEO
    SeoModule::setMetaDescription($data["card"]["desc"]);
    SeoModule::setTitle("Mi Cuaderno: ".$data["card"]["title"]);
    SeoModule::setOpenGraph([
      "title"     => $data["card"]["title"].", revisa mi cuaderno.",
      "site_name" => "Cuaderno",
      "content"   => $data["card"]["desc"],
      "image"     => $data["card"]["avatarSrc"],
      "image_width" => 500,
      "image_height" => 500,
      "link"      => DOMAIN . "/" . ltrim($data["card"]["profile"], '/'),
      "type"      => "website"
    ]);
    

    return $this->view("User.index", $data);
  
  }

}