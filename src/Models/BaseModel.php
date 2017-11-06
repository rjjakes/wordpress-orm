<?php

namespace Symlink\ORM\Models;

use Symlink\ORM\Mapping;

abstract class BaseModel {

  /**
   * Every model has an ID.
   * @var
   */
  protected $ID;

  /**
   * @var
   */
  private $hash;

  /**
   * BaseModel constructor.
   */
  public function __construct() {
    $this->hash = spl_object_hash($this);
  }

  /**
   * Getter.
   *
   * @return string
   */
  public function getId() {
    return $this->ID;
  }

  /**
   * @return mixed
   */
  public function getHash() {
    return $this->hash;
  }

  /**
   * @return mixed
   */
  public function getTableName() {
    return Mapping::getMapper()->getProcessed(get_class($this))['ORM_Table'];
  }

  /**
   * @return mixed
   */
  public function getSchema() {
    return Mapping::getMapper()->getProcessed(get_class($this))['schema'];
  }

  /**
   * @return mixed
   */
  public function getPlaceholders() {
    return Mapping::getMapper()->getProcessed(get_class($this))['placeholder'];
  }

  /**
   * Return keyed values from this object as per the schema (no ID).
   * @return array
   */
  public function getAllValues() {
    $values = [];
    foreach (array_keys($this->getSchema()) as $property) {
      $values[$property] = $this->get($property);
    }
    return $values;
  }

  /**
   * Return unkeyed values from this object as per the schema (no ID).
   * @return bool
   */
  public function getAllUnkeyedValues() {
    return array_map(function ($key) {
      return $this->get($key);
    }, array_keys($this->getSchema()));
  }

  /**
   * Generic getter.
   *
   * @param $column
   *
   * @return mixed
   * @throws \Symlink\ORM\Exceptions\PropertyDoesNotExistException
   */
  final public function get($property) {
    // Check to see if the property exists on the model.
    if (!property_exists($this, $property)) {
      throw new \Symlink\ORM\Exceptions\PropertyDoesNotExistException(sprintf(__('The property %s does not exist on the model %s.'), $property, get_class($this)));
    }

    // Return the value of the field.
    return $this->$property;
  }

  /**
   * Get multiple values from this object given an array of properties.
   *
   * @param $columns
   * @return array
   */
  final public function getMultiple($columns) {
    $results = [];

    if (is_array($columns)) {
      foreach ($columns as $column) {
        $results[$column] = $this->get($column);
      }
    }

    return $results;
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
  final public function set($column, $value) {
    // Check to see if the property exists on the model.
    if (!property_exists($this, $column)) {
      throw new \Symlink\ORM\Exceptions\PropertyDoesNotExistException(sprintf(__('The property does not exist on the model %s.'), get_class($this)));
    }

    // Update the model with the value.
    $this->$column = $value;

    return TRUE;
  }

}