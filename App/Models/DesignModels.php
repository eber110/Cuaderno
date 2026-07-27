<?php
  
namespace App\Models;

use Base\Builder\Builder;
use Base\Module\LogModule;

class DesignModels extends Builder{

  protected $table = "";

  /**
   * Obtiene los datos del diseño del usuario (revisa primero UserCustom, luego UserData).
   */
  public static function dataUser(string $user): bool|array {
    $user = mb_strtolower($user, 'UTF-8');
    $customDir = ROOT_PATH . "/Cache/UserData/UserCustom";
    if (!is_dir($customDir)) {
      @mkdir($customDir, 0777, true);
    }

    $customFile = $customDir . "/{$user}.json";
    $officialFile = ROOT_PATH . "/Cache/UserData/{$user}.json";

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
   */
  public static function hasCustomDesign(string $user): bool {
    $user = mb_strtolower($user, 'UTF-8');
    return file_exists(ROOT_PATH . "/Cache/UserData/UserCustom/{$user}.json");
  }

  /**
   * Lee el diseño borrador (custom) del usuario si existe.
   */
  public static function getCustomDesign(string $user): bool|array {
    $user = mb_strtolower($user, 'UTF-8');
    $customFile = ROOT_PATH . "/Cache/UserData/UserCustom/{$user}.json";
    if (file_exists($customFile)) {
      $data = LogModule::readLogLines($customFile);
      return (!$data || empty($data)) ? false : $data[0];
    }
    return false;
  }

  /**
   * Lee el diseño oficial (publicado) del usuario.
   */
  public static function getOfficialDesign(string $user): bool|array {
    $user = mb_strtolower($user, 'UTF-8');
    $officialFile = ROOT_PATH . "/Cache/UserData/{$user}.json";
    if (file_exists($officialFile)) {
      $data = LogModule::readLogLines($officialFile);
      return (!$data || empty($data)) ? false : $data[0];
    }
    return false;
  }

}