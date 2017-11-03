<?php

namespace Symlink\ORM;

use Minime\Annotations\Reader;
use Minime\Annotations\AnnotationsBag;

class Mapping {

  /**
   * @var \Symlink\ORM\Manager
   */
  static $service;

  /**
   * @var \Minime\Annotations\Reader
   */
  private $reader;

  /**
   * The processed models.
   * @var array
   */
  private $models;

  /**
   * Initializes a non static copy of itself when called. Subsequent calls
   * return the same object (fake dependency injection/service).
   *
   * @return \Symlink\ORM\Mapping
   */
  public static function getMapper() {
    // Initialize the service if it's not already set.
    if (!self::$service) {
      self::$service = new Mapping();
    }

    // Return the instance.
    return new Mapping();
  }

  /**
   * Returns an instance of the annotation reader (caches within this request).
   *
   * @return \Minime\Annotations\Interfaces\ReaderInterface|\Minime\Annotations\Reader
   */
  private function getReader() {
    // If the annotation reader isn't set, create it.
    if (!$this->reader) {
      $this->reader = Reader::createFromDefaults();
    }

    return $this->reader;
  }

  /**
   * Process the class annotations, adding an entry the the $this->models array.
   *
   * @param $classname
   * @return mixed|\WP_Error
   */
  public function getProcessed($classname) {

    if (!isset($this->models[$classname])) {
      // Get the annotation reader instance.
      $class_annotations = $this->getReader()->getClassAnnotations($classname);

      // Check the model annotations.
      if (!$class_annotations->get('ORM_Type')) {
        $this->models[$classname]['validated'] = FALSE;
        return new \WP_Error('orm_missing_annotation', __('The annotation ORM_Type does not exist on the model.'));
      }
      else {
        $this->models[$classname]['ORM_Type'] = $class_annotations->get('ORM_Type');
      }

      if (!$class_annotations->get('ORM_Table')) {
        $this->models[$classname]['validated'] = FALSE;
        return new \WP_Error('orm_missing_annotation', __('The annotation ORM_Table does not exist on the model.'));
      }
      else {
        $this->models[$classname]['ORM_Table'] = $class_annotations->get('ORM_Table');
      }

      if (!$class_annotations->get('ORM_UUID')) {
        $this->models[$classname]['validated'] = FALSE;
        return new \WP_Error('orm_missing_annotation', __('The annotation ORM_UUID does not exist on the model.'));
      }
      else {
        $this->models[$classname]['ORM_UUID'] = filter_var($class_annotations->get('ORM_UUID'), FILTER_VALIDATE_BOOLEAN);
      }

      if (!$class_annotations->get('ORM_AllowSchemaUpdate')) {
        $this->models[$classname]['validated'] = FALSE;
        return new \WP_Error('orm_missing_annotation', __('The annotation ORM_AllowSchemaUpdate does not exist on the model.'));
      }
      else {
        $this->models[$classname]['ORM_AllowSchemaUpdate'] = filter_var($class_annotations->get('ORM_AllowSchemaUpdate'), FILTER_VALIDATE_BOOLEAN);;
      }

      // Check the property annotations.
      $reflection_class = new \ReflectionClass($classname);
      $properties = $reflection_class->getProperties();

      // Optionally add the UUID column.
      if ($this->models[$classname]['ORM_UUID']) {
        $this->models[$classname]['schema']['uuid'] = 'uuid varchar(36) NOT NULL';
        $this->models[$classname]['placeholder']['uuid'] = '%s';

      }
      else {
        $this->models[$classname]['schema'] = [];
      }

      // Loop through the class properties.
      if (is_array($properties) || !count($properties)) {
        foreach ($properties as $property) {

          // Get the annotation of this property.
          $property_annotation = $this->getReader()->getPropertyAnnotations($classname, $property->name);

          // Silently ignore properties that do not have the ORM_Column_Type annotation.
          if ($property_annotation->get('ORM_Column_Type')) {

            $column_type = strtolower($property_annotation->get('ORM_Column_Type'));

            // Test the ORM_Column_Type
            if (!in_array($column_type, [
              'datetime',
              'tinyint',
              'smallint',
              'bigint',
              'varchar',
              'text',
              'float',
            ])) {
              return new \WP_Error('orm_unknwon_column_type', __('Unknown model property column type set in @ORM_Column_Type.'));
            }

            // Build the rest of the schema partial.
            $schema_string = $property->name .' ' . $column_type;

            if ($property_annotation->get('ORM_Column_Length')) {
              $schema_string .= '(' . $property_annotation->get('ORM_Column_Length') . ')';
            }

            if ($property_annotation->get('ORM_Column_Null')) {
              $schema_string .= ' ' . $property_annotation->get('ORM_Column_Null');
            }

            // Add the schema to the mapper array for this class.
            $placeholder_values_type = '%s';  // Initially assume column is string type.

            if (in_array($column_type, [  // If the column is a decimal type.
              'tinyint',
              'smallint',
              'bigint',
            ])) {
              $placeholder_values_type = '%d';
            }

            if (in_array($column_type, [  // If the column is a float type.
              'float',
            ])) {
              $placeholder_values_type = '%f';
            }

            $this->models[$classname]['schema'][$property->name] = $schema_string;
            $this->models[$classname]['placeholder'][$property->name] = $placeholder_values_type;
          }
        }
      }
      else {
        return new \WP_Error('orm_missing_properties', __('The model does not contain any properties.'));
      }
    }

    // Return the processed annotations.
    return $this->models[$classname];
  }


  /**
   * Compares a database table schema to the model schema (as defined in the
   * annotations). If there any differences, the database schema is modified
   * to match the model.

   * @param $classname
   *
   * @return bool|\WP_Error
   */
  public function updateSchema($classname) {
    global $wpdb;

    // Get the model annotation data.
    $mapped = $this->getProcessed($classname);

    // Are we allowed to update the schema of this model in the db?
    if (!$mapped['ORM_AllowSchemaUpdate']) {
      return new \WP_Error('orm_schema_update_rejected', __('Refused to update model schema. ORM_AllowSchemaUpdate is FALSE.'));
    }

    // Create an ID type string.
    $id_type = 'id';
    $id_type_string = 'id mediumint(9) NOT NULL AUTO_INCREMENT';

    // Build the SQL CREATE TABLE command for use with dbDelta.
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . $mapped['ORM_Table'];

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE " . $table_name . " (
  " . $id_type_string . ",
  " . implode(",\n  ", $mapped['schema']) . ",
  PRIMARY KEY  (" . $id_type . ")
) $charset_collate;";

    // Use dbDelta to do all the hard work.
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);

    return TRUE;
  }

}
