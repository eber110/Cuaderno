<?php
  
namespace App\Models;

use Base\Builder\Builder;
use Base\Module\LogModule;
use Base\Module\ResponseModule;

class UserModels extends Builder{

  protected $table = "users";

  public static function userExists(string $user): bool{
    
    $user = mb_strtolower($user, 'UTF-8');
    $userList = LogModule::readLogLines("/Cache/Users/userlist.json");
    $dataUser = in_array($user, $userList, true);
    return $dataUser ?? false;
  
  }

  public function dataUser(string $user): bool|array{

    $user = mb_strtolower($user, 'UTF-8');
    $data = LogModule::readLogLines("/Cache/UserData/{$user}.json");//datos de prueba
    $data =  (!$data || empty($data)) ? false : $data[0];
    return $data;
  
  }

}