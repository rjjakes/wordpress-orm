<?php

namespace Symlink\ORM\Models;

use Symlink\ORM\Mapping as ORM;
use Symlink\ORM\Models\IdModel;

/**
 * @ORM_Type Entity
 * @ORM_Table "users"
 * @ORM_AllowSchemaUpdate False
 * @ORM_Repository \Symlink\ORM\Repositories\UsersRepository
 */
class Users extends IdModel {

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 60
   * @ORM_Column_Null NOT NULL
   */
  protected $user_login;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 255
   * @ORM_Column_Null NOT NULL
   */
  protected $user_pass;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 50
   * @ORM_Column_Null NOT NULL
   */
  protected $user_nicename;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 100
   * @ORM_Column_Null NOT NULL
   */
  protected $user_email;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 100
   * @ORM_Column_Null NOT NULL
   */
  protected $user_url;

  /**
   * @ORM_Column_Type datetime
   * @ORM_Column_Null NOT NULL
   */
  protected $user_registered;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 255
   * @ORM_Column_Null NOT NULL
   */
  protected $user_activation_key;

  /**
   * @ORM_Column_Type int
   * @ORM_Column_Null NOT NULL
   */
  protected $user_status;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 250
   * @ORM_Column_Null NOT NULL
   */
  protected $display_name;

}
