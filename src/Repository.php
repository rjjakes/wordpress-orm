<?php

namespace Symlink\ORM;


// https://symfony.com/doc/current/doctrine.html#fetching-objects-from-the-database
class Repository {

  /**
   * @var \Symlink\ORM\Repository
   */
  static $service;

  /**
   * Initializes a non static copy of itself when called. Subsequent calls
   * return the same object (fake dependency injection/service).
   *
   * @return \Symlink\ORM\Repository
   */
  public static function getRepository() {
    // Initialize the service if it's not already set.
    if (!self::$service) {
      self::$service = new Repository();
    }

    // Return the instance.
    return new Repository();
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