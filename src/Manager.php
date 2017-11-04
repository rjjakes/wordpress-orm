<?php

namespace Symlink\ORM;

use DeepCopy\DeepCopy;
use Symlink\ORM\Models\BaseModel;
use Symlink\ORM\Repositories\BaseRepository;

class Manager {

  /**
   * @var \Symlink\ORM\Manager
   */
  static $service;

  /**
   * Holds the resuable instance of DeepCopy().
   * @var
   */
  private $copier;

  /**
   * Holds an array of objects added via persist().
   * @var
   */
  private $persisted_objects;

  /**
   * Holds an array of objects the manager knows exist in the database (either
   * from a query or a previous persist() call).
   *
   * @var array
   */
  private $tracked_objects;

  /**
   * Initializes a non static copy of itself when called. Subsequent calls
   * return the same object (fake dependency injection/service).
   *
   * @return \Symlink\ORM\Manager
   */
  public static function getManager() {
    // Initialize the service if it's not already set.
    if (!self::$service) {
      self::$service = new Manager();
    }

    // Return the instance.
    return new Manager();
  }

  /**
   * Manager constructor.
   */
  public function __construct() {
    $this->persisted_objects = [];
    $this->tracked_objects = [];
  }

  /**
   * Get repository instance from classname.
   *
   * @param $classname
   *
   * @return \Symlink\ORM\Repositories\BaseRepository
   */
  public function getRepository($classname) {

    // Get the annotations for this class.
    $annotations = Mapping::getMapper()->getProcessed($classname);

    // Get the repository for this class (this has already been validated in the Mapper). .
    if (isset($annotations['ORM_Repository'])) {
      return $annotations['ORM_Repository']::getInstance($classname, $annotations);
    }
    // No repository set, so assume the base.
    else {
      return BaseRepository::getInstance($classname, $annotations);
    }
  }

  /**
   * @return \DeepCopy\DeepCopy
   */
  private function getCopier() {
    if (!$this->copier) {
      $this->copier = new DeepCopy();
    }
    return $this->copier;
  }

  /**
   * Queue this model up to be added to the database with flush().
   *
   * @param $model
   */
  public function persist(BaseModel $object) {
    // Get the unique hash of this $object
    $obj_hash = spl_object_hash($object);

    // Save it against the key (additional persist() calls with the same
    // model will overwrite the previous changes.
    $this->persisted_objects[$obj_hash] = [
      'model' => $object,
      'annotations' => Mapping::getMapper()->getProcessed(get_class($object))
    ];
  }

  /**
   * Start tracking a model known to exist in the database.
   *
   * @param \Symlink\ORM\Models\BaseModel $object
   */
  public function track(BaseModel $object) {
    // Get the unique hash of this $object
    $obj_hash = spl_object_hash($object);

    // Save it against the key and store a clone of the last known state of
    // the model in the database. This will be used to check changes when
    // calling flush() later on.
    $this->tracked_objects[$obj_hash] = [
      'model' => $object,
      'last_state' => $this->getCopier()->copy($object),
      'annotations' => Mapping::getMapper()->getProcessed(get_class($object))
    ];

  }

  /**
   * This model should be removed from the db when flush() is called.
   * @param $model
   */
  public function remove(BaseModel $model) {

    // Add/overwrite the DELETE entry.
    $this->changes[$model->getUUID()] = [
      'type' => ORM_PERSIST_DELETE,
      'mode' => $model
    ];
  }

  /**
   * Add new objects to the database.
   *
   * @throws \Symlink\ORM\Exceptions\FailedToInsertException
   */
  private function _flush_insert() {
    global $wpdb;

    $insert = [];

    // Preprocess data for INSERTs.
    foreach ($this->persisted_objects as $hash => $item) {

      // Get the values from the schema.
      $values = [];
      foreach (array_keys($item['annotations']['schema']) as $fieldname) {
        $insert[$item['annotations']['ORM_Table']]['values'][] = $item['model']->get($fieldname);
      }

      // Add to the combined insert object for this table.
      $insert[$item['annotations']['ORM_Table']]['rows'] = '(' . implode(', ', array_keys($item['annotations']['schema'])) . ')';
      $insert[$item['annotations']['ORM_Table']]['placeholder'][] = '(' . implode(', ', $item['annotations']['placeholder']) . ')';

      // Remove from the persisted_objects and move to the tracked_objects.
      unset($this->persisted_objects[$hash]);
      $this->track($item['model']);
    }

    // Process the INSERTs
    if (count($insert)) {
      // Build the combined query for table: $tablename
      foreach ($insert as $tablename => $values) {

        $table_name = $wpdb->prefix . $tablename;

        // Build the placeholder SQL query.
        $sql = "INSERT INTO " . $table_name . "
                  " . $values['rows'] . "
              VALUES
              " . implode(",
              ", $values['placeholder']);

        // Insert using Wordpress prepare() which provides SQL injection protection (apparently).
        $count = $wpdb->query($wpdb->prepare($sql, $values['values']));

        if (!$count) {
          throw new \Symlink\ORM\Exceptions\FailedToInsertException(__('Failed to insert one or more records into the database.'));
        }
      }
    }

  }

  private function _flush_update() {
  }

  private function _flush_delete() {
  }

  /**
   * Apply changes to all models queued up with persist().
   * Attempts to combine queries to reduce MySQL load.
   * @throws \Symlink\ORM\Exceptions\FailedToInsertException
   */
  public function flush() {
    $this->_flush_update();   // UPDATE must come before INSERT.
    $this->_flush_insert();
    $this->_flush_delete();
  }

}