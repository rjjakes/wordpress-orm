<?php

namespace Symlink\ORM;

use Ramsey\Uuid\Uuid;

abstract class BaseModel {

  /**
   * Every model has an ID.
   * @var
   */
  protected $id;

  /**
   * Some models have an UUID.
   * @var
   */
  protected $uuid;

  /**
   * BaseModel constructor.
   */
  public function __construct() {
    $this->uuid = Uuid::uuid4()->toString();
  }

  /**
   * Getter.
   *
   * @return string
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Getter.
   *
   * @return string
   */
  public function getUUID() {
    return $this->uuid;
  }

  /**
   * Generic getter.
   *
   * @param $column
   * @return \WP_Error|mixed
   */
  public function get($column) {
    // Check to see if the property exists on the model.
    if (!property_exists($this, $column)) {
      return new \WP_Error('orm_no_property', __('The property does not exist on the model.') );
    }

    // Return the value of the field.
    return $this->$column;
  }

  /**
   * Generic setter.
   *
   * @param $column
   * @param $value
   * @return \WP_Error|bool
   */
  public function set($column, $value) {
    // Check to see if the property exists on the model.
    if (!property_exists($this, $column)) {
      return new \WP_Error('orm_no_property', __('The property does not exist on the model.') );
    }

    // Update the model with the value.
    $this->$column = $value;

    return TRUE;
  }

}