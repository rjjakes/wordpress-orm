<?php

namespace Symlink\ORM\Collections;

define('_OBJECT_NEW', 1);
define('_OBJECT_TRACKED', 2);
define('_OBJECT_CLEAN', -1);

/**
 * Class TrackedCollection
 *
 * Holds globally tracked objects that have been fetched or persisted to the
 * database.
 */
class TrackedCollection implements \ArrayAccess {

  /**
   * The internal array of objects to track.
   *
   * @var array
   */
  private $list;

  /**
   * TrackedCollection constructor.
   */
  public function __construct() {
    $this->list = [];
  }

  /**
   * Get data used to INSERT/UPDATE objects in the database.
   *
   * @param $iterator
   * @return array
   */
  public function getInsertUpdateTableData($iterator) {
    $data = [];

    // Get the structural data.
    foreach ($this->$iterator() as $item) {

      // Add the table name and schema data (only once).
      if (!isset($data[get_class($item['model'])])) {
        $data[get_class($item['model'])] = [
          'objects' => [],
          'table_name' => $item['model']->getTableName(),
          'columns' => array_keys($item['model']->getSchema()),
          'placeholders' => $item['model']->getPlaceholders(),
          'placeholders_count' => 0,
          'values' => [],
        ];
      }

      // Store the object.
      $data[get_class($item['model'])]['objects'][] = $item['model'];

      // Now add the placeholder and row data.
      $data[get_class($item['model'])]['placeholders_count'] += 1;

      if ($iterator === 'getPersistedObjects') {
        $data[get_class($item['model'])]['values'] = array_merge(
          $data[get_class($item['model'])]['values'],
          $item['model']->getAllUnkeyedValues()
        );
      }
      else {
        $data[get_class($item['model'])]['values'] = array_merge(
          $data[get_class($item['model'])]['values'],
          [$item['model']->getID()],
          $item['model']->getAllUnkeyedValues()
        );
      }
    }

    return $data;
  }

  /**
   * @return array
   */
  public function getRemoveTableData() {
    $data = [];


    foreach ($this->getRemovedObjects() as $item) {

      if (!isset($data[get_class($item['last_state'])])) {
        $data[get_class($item['last_state'])] = [
          'objects' => [],
          'table_name' => $item['last_state']->getTableName(),
          'values' => [],
        ];
      }

      $data[get_class($item['last_state'])]['objects'][] = $item['last_state'];
      $data[get_class($item['last_state'])]['values'][] = $item['last_state']->getId();

    }

    return $data;
  }

  /**
   * @param mixed $object
   * @param mixed $state
   */
  public function offsetSet($object, $state) {

    switch ($state) {
      // If new, objects will have a 'model' but no 'last_state',
      case _OBJECT_NEW:
        $this->list[$object->getHash()] = [
          'model' => $object,
        ];
        break;

      // If new, objects will have both a 'model' and a 'last_state',
      case _OBJECT_TRACKED:
        $this->list[$object->getHash()] = [
          'model' => $object,
          'last_state' => clone $object
        ];
        break;

      // Clean an object out of the $list
      case _OBJECT_CLEAN:
        unset($this->list[$object->getHash()]);
        break;
    }

  }

  /**
   * @param mixed $object
   *
   * @return bool
   */
  public function offsetExists($object) {
    return isset($this->list[$object->getHash()]);
  }

  /**
   * Queue up an object for removal when calling flush().
   * These objects will have a 'last_state' but no model.
   *
   * @param mixed $object
   */
  public function offsetUnset($object) {

    // If the object exists in the list.
    if (isset($this->list[$object->getHash()])) {

      // If a new object (without a last_state) is being deleted, just delete the entire object.
      if (!isset($this->list[$object->getHash()]['last_state'])) {
        unset($this->list[$object->getHash()]);
      }
      else {
        unset($this->list[$object->getHash()]['model']);
      }
    }

  }

  /**
   * @param mixed $object
   *
   * @return mixed|null
   */
  public function offsetGet($object) {
    return isset($this->list[$object->getHash()]) ? $this->list[$object->getHash()]['model'] : NULL;
  }

  public function removeFromCollection($obj_hash) {
    $this->list[$obj_hash] = [];
  }

  /**
   * Return an array of the objects to be INSERTed.
   */
  public function getPersistedObjects() {
    foreach ($this->list as $item) {
      if (isset($item['model']) && !isset($item['last_state'])) {
        yield $item;
      }
    }
  }

  /**
   * Return an array of the objects to be UPDATEd and the changed properties.
   */
  public function getChangedObjects() {
    foreach ($this->list as $item) {
      if (isset($item['model']) && isset($item['last_state']) && $item['model'] != $item['last_state']) {
        yield $item;
      }
    }
  }

  /**
   * Return an array of the objects to be DELETEd.
   */
  public function getRemovedObjects() {
    foreach ($this->list as $item) {
      if (!isset($item['model']) && isset($item['last_state'])) {
        yield $item;
      }
    }
  }

}
