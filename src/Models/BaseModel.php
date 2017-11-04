<?php

namespace Symlink\ORM\Models;

abstract class BaseModel {

  /**
   * Every model has an ID.
   * @var
   */
  protected $id;

  /**
   * BaseModel constructor.
   */
  public function __construct() {
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
   * Generic getter.
   *
   * @param $column
   *
   * @return mixed
   * @throws \Symlink\ORM\Exceptions\PropertyDoesNotExistException
   */
  public function get($property) {
    // Check to see if the property exists on the model.
    if (!property_exists($this, $property)) {
      throw new \Symlink\ORM\Exceptions\PropertyDoesNotExistException(sprintf(__('The property %s does not exist on the model %s.'), $property, get_class($this)));
    }

    // Return the value of the field.
    return $this->$property;
  }

  /**
   * Generic setter.
   *
   * @param $column
   * @param $value
   *
   * @return bool
   * @throws \Symlink\ORM\Exceptions\PropertyDoesNotExistException
   */
  public function set($column, $value) {
    // Check to see if the property exists on the model.
    if (!property_exists($this, $column)) {
      throw new \Symlink\ORM\Exceptions\PropertyDoesNotExistException(sprintf(__('The property does not exist on the model %s.'), get_class($this)));
    }

    // Update the model with the value.
    $this->$column = $value;

    return TRUE;
  }

}