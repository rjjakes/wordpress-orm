<?php

namespace Symlink\ORM;

use Symlink\ORM\Repositories\BaseRepository;

class QueryBuilder {

  private $where;

  private $orderBy;

  private $limit;

  /**
   * Reference to the repository.
   * @var \Symlink\ORM\Repositories\BaseRepository
   */
  private $repository;

  /**
   * Valid properties (including the required 'id' field).
   * @var array
   */
  private $valid_properties;

  /**
   * QueryBuilder constructor.
   */
  public function __construct(BaseRepository $repository) {
    // Set some default values.
    $this->where = [];
    $this->orderBy = '';
    $this->limit = '';

    // And store the sent repository.
    $this->repository = $repository;

    // And the valid properties.
    $this->valid_properties = array_merge($this->repository->getObjectProperties(), ['id']);
  }

  /**
   * Add a WHERE clause to the query.
   *
   * @param $property
   * @param $value
   * @param $operator
   *
   * @return $this
   * @throws \Symlink\ORM\Exceptions\InvalidOperatorException
   * @throws \Symlink\ORM\Exceptions\PropertyDoesNotExistException
   */
  public function where($property, $value, $operator) {

    // Check the property exists.
    if (!in_array($property, $this->valid_properties)) {
      throw new \Symlink\ORM\Exceptions\PropertyDoesNotExistException(sprintf(__('Property %s does not exist in model %s.'), $property, $this->repository->getObjectClass()));
    }

    // Check the operator is valid.
    if (!in_array($operator, [
      '<',
      '<=',
      '=',
      '!=',
      '>',
      '>=',
      'IN'
      ])) {
      throw new \Symlink\ORM\Exceptions\InvalidOperatorException(sprintf(__('Operator %s is not valid.'), $operator));
    }

    echo "HERE\n\n";
    print_r($this->repository->getObjectClass());
    echo "\n\n";
    print_r($this->repository->getObjectProperties());
    echo "\n\n";

    print "where";
    print_r($this);
    exit;

    return $this;
  }

  /**
   * Set the ORDER BY clause.
   *
   * @param $property
   * @param $operator
   *
   * @return $this
   * @throws \Symlink\ORM\Exceptions\InvalidOperatorException
   * @throws \Symlink\ORM\Exceptions\PropertyDoesNotExistException
   */
  public function orderBy($property, $operator) {
    // Check the property exists.
    if (!in_array($property, $this->valid_properties)) {
      throw new \Symlink\ORM\Exceptions\PropertyDoesNotExistException(sprintf(__('Property %s does not exist in model %s.'), $property, $this->repository->getObjectClass()));
    }

    // Check the operator is valid.
    if (!in_array($operator, [
      'ASC',
      'DESC',
    ])) {
      throw new \Symlink\ORM\Exceptions\InvalidOperatorException(sprintf(__('Operator %s is not valid.'), $operator));
    }

    return $this;
  }

  /**
   * Set the limit clause.
   *
   * @param $start
   * @param $count
   */
  public function limit($start, $count) {
    return $this;
  }

  /**
   * Build the query into something Wordpress can process.
   */
  public function buildQuery() {
    return $this;
  }

  public function getResult() {
  }
}
