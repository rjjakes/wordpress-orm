<?php

namespace Symlink\ORM;

define('ORM_PERSIST_INSERT', 1);
define('ORM_PERSIST_UPDATE', 2);
define('ORM_PERSIST_DELETE', 3);

class Manager {

  /**
   * @var \Symlink\ORM\Manager
   */
  static $service;

  /**
   * The changes to apply to the database.
   * @var
   */
  private $changes;

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
   * Changes to this model should be applied when flush() is called.
   * @param $model
   */
  public function persist(\Symlink\ORM\BaseModel $model) {

    // If the model doesn't have an ID it is new, so CREATE.
    if (!$model->getId()) {
      $this->changes[$model->getUUID()] = [
        'type' => ORM_PERSIST_INSERT,
        'model' => $model,
        'annotations' => Mapping::getMapper()->getProcessed(get_class($model))
      ];
    }
    // Otherwise UPDATE.
    else {
      $this->changes[$model->getUUID()] = [
        'type' => ORM_PERSIST_UPDATE,
        'model' => $model,
        'annotations' => Mapping::getMapper()->getProcessed(get_class($model))
      ];
    }
  }

  /**
   * This model should be removed from the db when flush() is called.
   * @param $model
   */
  public function remove(\Symlink\ORM\BaseModel$model) {

    // Add/overwrite the DELETE entry.
    $this->changes[$model->getUUID()] = [
      'type' => ORM_PERSIST_DELETE,
      'mode' => $model
    ];
  }

  /**
   * Apply changes to all models queued up with persist().
   * Attempts to combine queries to reduce MySQL load.
   */
  public function flush() {
    global $wpdb;

    $insert = [];
    $update = [];
    $delete = [];

    if (is_array($this->changes)) {
      foreach ($this->changes as $item) {

        // Build the INSERTs.
        if ($item['type'] == ORM_PERSIST_INSERT) {

          // Get the values from the schema.
          $values = [];
          foreach (array_keys($item['annotations']['schema']) as $fieldname) {
            $insert[$item['annotations']['ORM_Table']]['values'][] = $item['model']->get($fieldname);
          }

          // Add to the combined insert object for this table.
          $insert[$item['annotations']['ORM_Table']]['rows'] = '(' . implode(', ', array_keys($item['annotations']['schema'])) . ')';
          $insert[$item['annotations']['ORM_Table']]['placeholder'][] = '(' . implode(', ', $item['annotations']['placeholder']) . ')';
        }

        // Build the UPDATEs.
        if ($item['type'] == ORM_PERSIST_UPDATE) {
        }

        // Build the DELETEs.
        if ($item['type'] == ORM_PERSIST_DELETE) {
        }

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
            return new \WP_Error('orm_failed_to_insert', __('Failed to insert one or more records into the database.'));
          }
        }
      }

      // Process the UPDATEs

      // Process the DELETEs

    }


    print "flush()";
    print_r($insert);
    print_r($this->changes);
    exit;

  }

}