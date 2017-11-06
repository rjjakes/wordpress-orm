<?php

namespace Symlink\ORM\Collections;

use DeepCopy\DeepCopy;

define('_OBJECT_NEW', 1);
define('_OBJECT_TRACKED', 2);

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
   * @var
   */
  private $copier;


  /**
   * TrackedCollection constructor.
   */
  public function __construct() {
    $this->list = [];
    $this->copier = new DeepCopy();
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
   * @param mixed $object
   * @param mixed $state
   */
  public function offsetSet($object, $state) {

    // 'annotations' => Mapping::getMapper()->getProcessed(get_class($object))

    // Get the object hash to use as the key.
    $obj_hash = spl_object_hash($object);

    // If new, objects will have a 'model' but no 'last_state',
    if ($state == _OBJECT_NEW) {
      $this->list[$obj_hash] = [
        'model' => $object,
      ];
    }
    // If existing, objects will have a 'model'and a 'last_state'
    else {
      if ($state == _OBJECT_TRACKED) {
        $this->list[$obj_hash] = [
          'model' => $object,
          'last_state' => $this->copier->copy($object)
        ];
      }
    }
  }

  /**
   * @param mixed $object
   *
   * @return bool
   */
  public function offsetExists($object) {
    return isset($this->list[spl_object_hash($object)]);
  }

  /**
   * Queue up an object for removal when calling flush().
   * These objects will have a 'last_state' but no model.
   *
   * @param mixed $object
   */
  public function offsetUnset($object) {

    // Get the object hash to use as the key.
    $obj_hash = spl_object_hash($object);

    // If the object exists in the list.
    if (isset($this->list[$obj_hash])) {

      // If a new object (without a last_state) is being deleted, just delete the entire object.
      if (!isset($this->list[$obj_hash]['last_state'])) {
        unset($this->list[$obj_hash]);
      }
      else {
        unset($this->list[$obj_hash]['model']);
      }
    }

  }

  /**
   * @param mixed $object
   *
   * @return mixed|null
   */
  public function offsetGet($object) {
    // Get the object hash to use as the key.
    $obj_hash = spl_object_hash($object);

    return isset($this->list[$obj_hash]) ? $this->list[$obj_hash]['model'] : NULL;
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
      if (isset($item['model']) && isset($item['last_state']) && $item['model'] !== $item['last_state']) {
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
        yield $item['model'];
      }
    }
  }

}
