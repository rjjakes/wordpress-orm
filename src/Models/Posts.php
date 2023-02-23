<?php

namespace Symlink\ORM\Models;

use Symlink\ORM\Mapping as ORM;
use Symlink\ORM\Models\IdModel;

/**
 * @ORM_Type Entity
 * @ORM_Table "posts"
 * @ORM_AllowSchemaUpdate False
 * @ORM_Repository \Symlink\ORM\Repositories\PostsRepository
 */
class Posts extends IdModel {

  /**
   * @ORM_Column_Type bigint
   * @ORM_Column_Length 20
   * @ORM_Column_Null NOT NULL
   * @ORM_ManyToOne \Symlink\ORM\Models\Users
   * @ORM_JoinProperty ID
   */
  protected $post_author;

  /**
   * @ORM_Column_Type datetime
   * @ORM_Column_Null NOT NULL
   */
  protected $post_date;

  /**
   * @ORM_Column_Type datetime
   * @ORM_Column_Null NOT NULL
   */
  protected $post_date_gmt;

  /**
   * @ORM_Column_Type longtext
   * @ORM_Column_Null NOT NULL
   */
  protected $post_content;

  /**
   * @ORM_Column_Type text
   * @ORM_Column_Null NOT NULL
   */
  protected $post_title;

  /**
   * @ORM_Column_Type text
   * @ORM_Column_Null NOT NULL
   */
  protected $post_excerpt;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 20
   * @ORM_Column_Null NOT NULL
   */
  protected $post_status;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 20
   * @ORM_Column_Null NOT NULL
   */
  protected $comment_status;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 20
   * @ORM_Column_Null NOT NULL
   */
  protected $ping_status;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 255
   * @ORM_Column_Null NOT NULL
   */
  protected $post_password;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 255
   * @ORM_Column_Null NOT NULL
   */
  protected $post_name;

  /**
   * @ORM_Column_Type text
   * @ORM_Column_Null NOT NULL
   */
  protected $to_ping;

  /**
   * @ORM_Column_Type text
   * @ORM_Column_Null NOT NULL
   */
  protected $pinged;

  /**
   * @ORM_Column_Type datetime
   * @ORM_Column_Null NOT NULL
   */
  protected $post_modified;

  /**
   * @ORM_Column_Type datetime
   * @ORM_Column_Null NOT NULL
   */
  protected $post_modified_gmt;

  /**
   * @ORM_Column_Type longtext
   * @ORM_Column_Null NOT NULL
   */
  protected $post_content_filtered;

  /**
   * @ORM_Column_Type bigint
   * @ORM_Column_Length 20
   * @ORM_Column_Null NOT NULL
   */
  protected $post_parent;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 255
   * @ORM_Column_Null NOT NULL
   */
  protected $guid;

  /**
   * @ORM_Column_Type int
   * @ORM_Column_Length 11
   * @ORM_Column_Null NOT NULL
   */
  protected $menu_order;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 20
   * @ORM_Column_Null NOT NULL
   */
  protected $post_type;

  /**
   * @ORM_Column_Type varchar
   * @ORM_Column_Length 100
   * @ORM_Column_Null NOT NULL
   */
  protected $post_mime_type;

  /**
   * @ORM_Column_Type bigint
   * @ORM_Column_Length 20
   * @ORM_Column_Null NOT NULL
   */
  protected $comment_count;

}
