<?php

namespace Symlink\ORM;

use Symlink\ORM\Models\BaseModel;
use Symlink\ORM\Repositories\BaseRepository;
use Symlink\ORM\Collections\TrackedCollection;

class Manager {

  /**
   * @var \Symlink\ORM\Manager
   */
  private static $manager_service = NULL;

  /**
   * Holds an array of objects the manager knows exist in the database (either
   * from a query or a previous persist() call).
   *
   * @var TrackedCollection
   */
  private $tracked;

  /**
   * Initializes a non static copy of itself when called. Subsequent calls
   * return the same object (fake dependency injection/service).
   *
   * @return \Symlink\ORM\Manager
   */
  public static function getManager() {
    // Initialize the service if it's not already set.
    if (self::$manager_service === NULL) {
      self::$manager_service = new Manager();
    }

    // Return the instance.
    return self::$manager_service;
  }

  /**
   * Manager constructor.
   */
  public function __construct() {
    $this->tracked = new TrackedCollection;
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
   * Queue this model up to be added to the database with flush().
   *
   * @param $object
   */
  public function persist(BaseModel $object) {
    // Start tracking this object (because it is new, it will be tracked as
    // something to be INSERTed)
    $this->tracked[$object] = _OBJECT_NEW;
  }

  /**
   * Start tracking a model known to exist in the database.
   *
   * @param \Symlink\ORM\Models\BaseModel $object
   */
  public function track(BaseModel $object) {
    // Save it against the key.
    $this->tracked[$object] = _OBJECT_TRACKED;
  }

  /**
   * This model should be removed from the db when flush() is called.
   *
   * @param $model
   */
  public function remove(BaseModel $object) {
    unset($this->tracked[$object]);
  }

  /**
   * Start tracking a model known to exist in the database.
   *
   * @param \Symlink\ORM\Models\BaseModel $object
   */
  public function clean(BaseModel $object) {
    // Save it against the key.
    $this->tracked[$object] = _OBJECT_CLEAN;
  }

  /**
   * Add new objects to the database.
   * This will perform one query per table no matter how many records need to
   * be added.
   *
   * @throws \Symlink\ORM\Exceptions\FailedToInsertException
   */
  private function _flush_insert() {
    global $wpdb;

    // Get a list of tables and columns that have data to insert.
    $insert = $this->tracked->getInsertUpdateTableData('getPersistedObjects');

    // Process the INSERTs
    if (count($insert)) {
      // Build the combined query for table: $tablename
      foreach ($insert as $classname => $values) {

        $table_name = $wpdb->prefix . $values['table_name'];

        // Build the placeholder SQL query.
        $sql = "INSERT INTO " . $table_name . "
                  (" . implode(", ", $values['columns']) . ")
              VALUES
              ";

        while ($values['placeholders_count'] > 0) {
          $sql .= "(" . implode(", ", $values['placeholders']) . ")";

          if ($values['placeholders_count'] > 1) {
            $sql .= ",
            ";
          }

          $values['placeholders_count'] -= 1;
        }

        // Insert using Wordpress prepare() which provides SQL injection protection (apparently).
        $prepared = $wpdb->prepare($sql, $values['values']);
        $count = $wpdb->query($prepared);

        // Start tracking all the added objects.
        if ($count) {
          array_walk($values['objects'], function ($object) {
            $this->track($object);
          });
        }
        // Something went wrong.
        else {
          throw new \Symlink\ORM\Exceptions\FailedToInsertException(__('Failed to insert one or more records into the database.'));
        }
      }
    }

  }

  /**
   * Compares known database state of tracked objects and compares them with
   * the current state. Applies any changes to the database.
   *
   * This will perform one query per table no matter how many records need to
   * be updated.
   * https://stackoverflow.com/questions/3432/multiple-updates-in-mysql
   */
  private function _flush_update() {
    global $wpdb;

    // Get a list of tables and columns that have data to update.
    $update = $this->tracked->getInsertUpdateTableData('getChangedObjects');

    // Process the INSERTs
    if (count($update)) {
      // Build the combined query for table: $tablename
      foreach ($update as $classname => $values) {

        $table_name = $wpdb->prefix . $values['table_name'];

        $sql = "INSERT INTO " . $table_name . " (ID, " . implode(", ", $values['columns']) . ")
          VALUES
          ";

        while ($values['placeholders_count'] > 0) {
          $sql .= "(%d, " . implode(", ", $values['placeholders']) . ")";

          if ($values['placeholders_count'] > 1) {
            $sql .= ",
            ";
          }

          $values['placeholders_count'] -= 1;
        }

        $sql .= "
        ON DUPLICATE KEY UPDATE
        ";

        $update_set = [];
        foreach ($values['columns'] as $column) {
          $update_set[] = $column . "=VALUES(" . $column . ")";
        }
        $sql .= implode(", ", $update_set) . ";";

        // Insert using Wordpress prepare() which provides SQL injection protection (apparently).
        $prepared = $wpdb->prepare($sql, $values['values']);

        $count = $wpdb->query($prepared);

        // Start tracking all the added objects.
        if ($count) {
          array_walk($values['objects'], function ($object) {
            $this->track($object);
          });
        }
        // Something went wrong.
        else {
          throw new \Symlink\ORM\Exceptions\FailedToInsertException(__('Failed to update one or more records in the database.'));
        }

      }
    }
  }

  /**
   *
   */
  private function _flush_delete() {

    global $wpdb;

    // Get a list of tables and columns that have data to update.
    $update = $this->tracked->getRemoveTableData();

    // Process the INSERTs
    if (count($update)) {
      // Build the combined query for table: $tablename
      foreach ($update as $classname => $values) {

        $table_name = $wpdb->prefix . $values['table_name'];

        // Build the SQL.
        $sql = "DELETE FROM " . $table_name . " WHERE ID IN (" . implode(", ", array_fill(0, count($values['values']), "%d")) . ");";

        // Process all deletes for a particular table together as a single query.
        $prepared = $wpdb->prepare($sql, $values['values']);
        $count = $wpdb->query($prepared);

        // Really remove the object from the tracking list.
        foreach ($values['objects'] as $obj_hash => $object) {
          $this->clean($object);
        }
      }
    }

  }

  /**
   * Apply changes to all models queued up with persist().
   * Attempts to combine queries to reduce MySQL load.
   *
   * @throws \Symlink\ORM\Exceptions\FailedToInsertException
   */
  public function flush() {
    $this->_flush_update();
    $this->_flush_insert();
    $this->_flush_delete();
  }

}