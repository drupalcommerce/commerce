<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\entity\BundleFieldDefinition;

class ConfigurableFieldManager implements ConfigurableFieldManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * Constructs a new ConfigurableFieldManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity query factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createField(BundleFieldDefinition $field_definition, $lock = TRUE) {
    $field_name = $field_definition->getName();
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $bundle = $field_definition->getTargetBundle();
    if (empty($field_name) || empty($entity_type_id) || empty($bundle)) {
      throw new \InvalidArgumentException('The passed $field_definition is incomplete.');
    }
    // loadByName() is an API that doesn't exist on the storage classes for
    // the two entity types, so we're using the entity classes directly.
    $field_storage = FieldStorageConfig::loadByName($entity_type_id, $field_name);
    $field = FieldConfig::loadByName($entity_type_id, $bundle, $field_name);
    if (!empty($field)) {
      throw new \RuntimeException(sprintf('The field "%s" already exists on bundle "%s" of entity type "%s".', $field_name, $bundle, $entity_type_id));
    }

    // The field storage might already exist if the field was created earlier
    // on a different bundle of the same entity type.
    if (empty($field_storage)) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'type' => $field_definition->getType(),
        'cardinality' => $field_definition->getCardinality(),
        'settings' => $field_definition->getSettings(),
        'translatable' => $field_definition->isTranslatable(),
        'locked' => $lock,
      ]);
      $field_storage->save();
    }

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => $field_definition->getLabel(),
      'required' => $field_definition->isRequired(),
      'settings' => $field_definition->getSettings(),
      'translatable' => $field_definition->isTranslatable(),
      'default_value' => $field_definition->getDefaultValueLiteral(),
      'default_value_callback' => $field_definition->getDefaultValueCallback(),
    ]);
    $field->save();

    // Show the field on default entity displays, if specified.
    if ($view_display_options = $field_definition->getDisplayOptions('view')) {
      $view_display = commerce_get_entity_display($entity_type_id, $bundle, 'view');
      $view_display->setComponent($field_name, $view_display_options);
      $view_display->save();
    }
    if ($form_display_options = $field_definition->getDisplayOptions('form')) {
      $form_display = commerce_get_entity_display($entity_type_id, $bundle, 'form');
      $form_display->setComponent($field_name, $form_display_options);
      $form_display->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteField(BundleFieldDefinition $field_definition) {
    $field_name = $field_definition->getName();
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $bundle = $field_definition->getTargetBundle();
    if (empty($field_name) || empty($entity_type_id) || empty($bundle)) {
      throw new \InvalidArgumentException('The passed $field_definition is incomplete.');
    }
    $field = FieldConfig::loadByName($entity_type_id, $bundle, $field_name);
    if (empty($field)) {
      throw new \RuntimeException(sprintf('The field "%s" does not exist on bundle "%s" of entity type "%s".', $field_name, $bundle, $entity_type_id));
    }

    $field = FieldConfig::loadByName($entity_type_id, $bundle, $field_name);
    $field->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function hasData(BundleFieldDefinition $field_definition) {
    $field_name = $field_definition->getName();
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $bundle = $field_definition->getTargetBundle();
    if (empty($field_name) || empty($entity_type_id) || empty($bundle)) {
      throw new \InvalidArgumentException('The passed $field_definition is incomplete.');
    }
    // Prevent an EntityQuery crash by first confirming the field exists.
    $field = FieldConfig::loadByName($entity_type_id, $bundle, $field_name);
    if (empty($field)) {
      throw new \RuntimeException(sprintf('The field "%s" does not exist on bundle "%s" of entity type "%s".', $field_name, $bundle, $entity_type_id));
    }
    // EntityQuery crashes if the field doesn't declare a main property.
    $properties = $field->getFieldStorageDefinition()->getPropertyNames();
    $property = reset($properties);

    $query = $this->entityTypeManager->getStorage($entity_type_id)->getQuery();
    $query
      ->condition('type', $bundle)
      ->exists($field_name . '.' . $property)
      ->range(0, 1);
    $result = $query->execute();

    return !empty($result);
  }

}
