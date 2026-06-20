<?php
  
namespace App\Models;

use Base\Builder\Builder;

class UserModels extends Builder{

  protected $table = "users";

  public function userExists(string $user): bool|array{
    
    $exists = $this->find("username", $user);
    return $exists;
  
  }

}