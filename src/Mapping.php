<?php

namespace Symlink\ORM;

use Minime\Annotations\Reader;
use Minime\Annotations\AnnotationsBag;

class Mapping {

  /**
   * @var \Symlink\ORM\Manager
   */
  private static $mapping_service = NULL;

  /**
   * @var \Minime\Annotations\Reader
   */
  private $reader;

  /**
   * The processed models.
   *
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
    if (self::$mapping_service === NULL) {
      self::$mapping_service = new Mapping();
    }

    // Return the instance.
    return self::$mapping_service;
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
   * @param $classname
   * @param $property
   * @return \Minime\Annotations\Interfaces\AnnotationsBagInterface
   */
  public function getPropertyAnnotationValue($classname, $property, $key) {
    // Get the annotations.
    $annotations = $this->getReader()->getPropertyAnnotations($classname, $property);

    // Return the key we want from this list of property annotations.
    return $annotations->get($key);
  }

  /**
   * Process the class annotations, adding an entry the $this->models array.
   *
   * @param $classname
   *
   * @return mixed
   * @throws \Symlink\ORM\Exceptions\RepositoryClassNotDefinedException
   * @throws \Symlink\ORM\Exceptions\RequiredAnnotationMissingException
   * @throws \Symlink\ORM\Exceptions\UnknownColumnTypeException
   */
  public function getProcessed($classname) {

    if (!isset($this->models[$classname])) {
      // Get the annotation reader instance.
      $class_annotations = $this->getReader()->getClassAnnotations($classname);

      // Validate @ORM_Type
      if (!$class_annotations->get('ORM_Type')) {
        $this->models[$classname]['validated'] = FALSE;

        throw new \Symlink\ORM\Exceptions\RequiredAnnotationMissingException(sprintf(__('The annotation ORM_Type does not exist on the model %s.'), $classname));
      }
      else {
        $this->models[$classname]['ORM_Type'] = $class_annotations->get('ORM_Type');
      }

      // Validate @ORM_Table
      if (!$class_annotations->get('ORM_Table')) {
        $this->models[$classname]['validated'] = FALSE;
        throw new \Symlink\ORM\Exceptions\RequiredAnnotationMissingException(sprintf(__('The annotation ORM_Table does not exist on the model %s.'), $classname));
      }
      else {
        $this->models[$classname]['ORM_Table'] = $class_annotations->get('ORM_Table');
      }

      // Validate @ORM_AllowSchemaUpdate
      if (!$class_annotations->get('ORM_AllowSchemaUpdate')) {
        $this->models[$classname]['validated'] = FALSE;
        throw new \Symlink\ORM\Exceptions\RequiredAnnotationMissingException(sprintf(__('The annotation ORM_AllowSchemaUpdate does not exist on the model.'), $classname));
      }
      else {
        $this->models[$classname]['ORM_AllowSchemaUpdate'] = filter_var($class_annotations->get('ORM_AllowSchemaUpdate'), FILTER_VALIDATE_BOOLEAN);;
      }

      // Validate @ORM_Repository
      if ($class_annotations->get('ORM_Repository')) {
        if (!class_exists($class_annotations->get('ORM_Repository'))) {
          throw new \Symlink\ORM\Exceptions\RepositoryClassNotDefinedException(sprintf(__('Repository class %s does not exist on model %s.'), $class_annotations->get('ORM_Repository'), $classname));
        }
        else {
          $this->models[$classname]['ORM_Repository'] = $class_annotations->get('ORM_Repository');
        }
      }

      // Check the property annotations.
      $reflection_class = new \ReflectionClass($classname);

      // Start with blank schema.
      $this->models[$classname]['schema'] = [];

      // Loop through the class properties.
      foreach ($reflection_class->getProperties() as $property) {

        // Get the annotation of this property.
        $property_annotation = $this->getReader()
                                    ->getPropertyAnnotations($classname, $property->name);

        // Silently ignore properties that do not have the ORM_Column_Type annotation.
        if ($property_annotation->get('ORM_Column_Type')) {

          $column_type = strtolower($property_annotation->get('ORM_Column_Type'));

          // Test the ORM_Column_Type
          if (!in_array($column_type, [
            'datetime',
            'tinyint',
            'smallint',
            'int',
            'bigint',
            'varchar',
            'tinytext',
            'text',
            'mediumtext',
            'longtext',
            'float',
          ])
          ) {
            throw new \Symlink\ORM\Exceptions\UnknownColumnTypeException(sprintf(__('Unknown model property column type %s set in @ORM_Column_Type on model %s..'), $column_type, $classname));
          }

          // Build the rest of the schema partial.
          $schema_string = $property->name . ' ' . $column_type;

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

          $this->models[$classname]['schema'][$property->name]      = $schema_string;
          $this->models[$classname]['placeholder'][$property->name] = $placeholder_values_type;
        }
      }


    }

    // Return the processed annotations.
    return $this->models[$classname];
  }

  /**
   * Compares a database table schema to the model schema (as defined in th
   * annotations). If there any differences, the database schema is modified to
   * match the model.
   *
   * @param $classname
   *
   * @throws \Symlink\ORM\Exceptions\AllowSchemaUpdateIsFalseException
   */
  public function updateSchema($classname) {
    global $wpdb;

    // Get the model annotation data.
    $mapped = $this->getProcessed($classname);

    // Are we allowed to update the schema of this model in the db?
    if (!$mapped['ORM_AllowSchemaUpdate']) {
      throw new \Symlink\ORM\Exceptions\AllowSchemaUpdateIsFalseException(sprintf(__('Refused to update model schema %s. ORM_AllowSchemaUpdate is FALSE.'), $classname));
    }

    // Create an ID type string.
    $id_type        = 'ID';
    $id_type_string = 'ID bigint(20) NOT NULL AUTO_INCREMENT';

    // Build the SQL CREATE TABLE command for use with dbDelta.
    $table_name = $wpdb->prefix . $mapped['ORM_Table'];

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE " . $table_name . " (
  " . $id_type_string . ",
  " . implode(",\n  ", $mapped['schema']) . ",
  PRIMARY KEY  (" . $id_type . ")
) $charset_collate;";

    // Use dbDelta to do all the hard work.
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  public function dropTable($classname) {
    global $wpdb;

    // Get the model annotation data.
    $mapped = $this->getProcessed($classname);

    // Are we allowed to update the schema of this model in the db?
    if (!$mapped['ORM_AllowSchemaUpdate']) {
      throw new \Symlink\ORM\Exceptions\AllowSchemaUpdateIsFalseException(sprintf(__('Refused to drop table for model %s. ORM_AllowSchemaUpdate is FALSE.'), $classname));
    }

    // Drop the table.
    $table_name = $wpdb->prefix . $mapped['ORM_Table'];
    $sql = "DROP TABLE IF EXISTS " . $table_name;
    $wpdb->query($sql);
  }

}
