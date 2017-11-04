<?php

namespace Symlink\ORM\Repositories;

use Symlink\ORM\QueryBuilder;

class BaseRepository {

  private $classname;

  private $annotations;

  /**
   * BaseRepository constructor.
   *
   * @param $classname
   * @param $annotations
   */
  public function __construct($classname, $annotations) {
    $this->classname = $classname;
    $this->annotations = $annotations;
  }

  /**
   * @param $classname
   * @param $annotations
   *
   * @return \Symlink\ORM\Repositories\BaseRepository
   */
  public static function getInstance($classname, $annotations) {
    // Get the class (as this could be a child of BaseRepository)
    $this_repository_class = get_called_class();

    // Return a new instance of the class.
    return new $this_repository_class($classname, $annotations);
  }

  /**
   * @return \Symlink\ORM\QueryBuilder
   */
  public function createQueryBuilder() {
    return new QueryBuilder($this);
  }

  /**
   * Getter used in the query builder.
   * @return mixed
   */
  public function getObjectClass() {
    return $this->classname;
  }

  /**
   * Getter used in the query builder.
   * @return array
   */
  public function getObjectProperties() {
    return array_keys($this->annotations['schema']);
  }

  /**
   * Find a single record by ID.
   * @param $id
   */
  public function find($id) {
  }

  /**
   * Find a single record by UUID.
   * @param $uuid
   */
  public function findByUUID($uuid) {
  }

  /**
   * Return all records in the table.
   * @return array
   */
  public function findAll() {
    return [];
  }

  /**
   * Returns all records with matching column value.
   * @param $column
   * @return array
   */
  public function findBy($column) {
    return [];
  }

}