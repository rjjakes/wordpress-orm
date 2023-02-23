<?php

namespace Symlink\ORM\Models;

use Symlink\ORM\Manager;
use Symlink\ORM\Mapping;

abstract class IdModel extends BaseModel {

  /**
   * Every model has an ID.
   * @var
   */
  protected $ID;

  /**
   * Getter.
   *
   * @return string
   */
  public function getId() {
    return $this->ID;
  }

}