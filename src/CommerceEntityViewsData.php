<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\views\EntityViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides improvements to core's generic views integration for entities.
 *
 * Contains special handling for the following base field types:
 * - address, address_country
 * - commerce_price
 * - datetime
 * - list_float, list_integer, list_string
 * - state
 * Workaround for core issue #2337515.
 *
 * Provides reverse relationships for base entity_reference fields,
 * as a workaround for core issue #2706431.
 */
class CommerceEntityViewsData extends EntityViewsData {

  use EntityManagerBridgeTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The table mapping.
   *
   * @var \Drupal\Core\Entity\Sql\DefaultTableMapping
   */
  protected $tableMapping;

  /**
   * Constructs a new CommerceEntityViewsData object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to provide views integration for.
   * @param \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage
   *   The storage handler used for this entity type.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, SqlEntityStorageInterface $storage, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, TranslationInterface $translation_manager, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $storage, $entity_manager, $module_handler, $translation_manager);

    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->tableMapping = $storage->getTableMapping();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('string_translation'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    // Workaround for core issue #3004300.
    if ($this->entityType->isRevisionable()) {
      $revision_table = $this->tableMapping->getRevisionTable();
      $data[$revision_table]['table']['entity revision'] = TRUE;
    }
    // Add missing reverse relationships. Workaround for core issue #2706431.
    $base_fields = $this->entityFieldManager->getBaseFieldDefinitions($this->entityType->id());
    $entity_reference_fields = array_filter($base_fields, function (BaseFieldDefinition $field) {
      return $field->getType() == 'entity_reference';
    });
    if (in_array($this->entityType->id(), ['commerce_order', 'commerce_product'])) {
      // Product variations and order items have reference fields pointing
      // to the parent entity, no need for a reverse relationship.
      unset($entity_reference_fields['variations']);
      unset($entity_reference_fields['order_items']);
    }
    $this->addReverseRelationships($data, $entity_reference_fields);

    return $data;
  }

  /**
   * Corrects the views data for address base fields.
   *
   * Based on address_field_views_data().
   *
   * @param string $table
   *   The table name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForAddress($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    $handlers_by_property = [
      'country_code' => 'country',
      'administrative_area' => 'subdivision',
      'locality' => 'subdivision',
      'dependent_locality' => 'subdivision',
      'postal_code' => 'standard',
      'sorting_code' => 'standard',
      'address_line1' => 'standard',
      'address_line2' => 'standard',
      'organization' => 'standard',
      'given_name' => 'standard',
      'additional_name' => 'standard',
      'family_name' => 'standard',
    ];
    if (!isset($handlers_by_property[$field_column_name])) {
      return;
    }

    $views_field['field'] = [
      'id' => $handlers_by_property[$field_column_name],
      'field_name' => $field_definition->getName(),
      'property' => $field_column_name,
    ];
    if ($field_column_name == 'country_code') {
      $views_field['filter']['id'] = 'country';
      $views_field['sort']['id'] = 'country';
    }
    elseif ($field_column_name == 'administrative_area') {
      $views_field['filter']['id'] = 'administrative_area';
    }
  }

  /**
   * Corrects the views data for address_country base fields.
   *
   * Based on address_field_views_data().
   *
   * @param string $table
   *   The table name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForAddressCountry($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    if ($field_column_name == 'value') {
      $views_field['field'] = [
        'id' => 'country',
        'field_name' => $field_definition->getName(),
        'property' => 'value',
      ];
      $views_field['filter']['id'] = 'country';
      $views_field['sort']['id'] = 'country';
    }
  }

  /**
   * Corrects the views data for commerce_price base fields.
   *
   * Based on commerce_price_field_views_data().
   *
   * @param string $table
   *   The table name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForCommercePrice($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    if ($field_column_name == 'number') {
      $views_field['filter']['id'] = 'numeric';
    }
  }

  /**
   * Corrects the views data for datetime base fields.
   *
   * Based on datetime_field_views_data().
   *
   * @param string $table
   *   The table name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForDatetime($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    if ($field_column_name == 'value') {
      $views_field['filter']['id'] = 'datetime';
      $views_field['argument']['id'] = 'datetime';
      $views_field['sort']['id'] = 'datetime';
      // These handlers need "field_name", the default only has "entity field".
      $views_field['filter']['field_name'] = $field_definition->getName();
      $views_field['argument']['field_name'] = $field_definition->getName();
      $views_field['sort']['field_name'] = $field_definition->getName();
    }
  }

  /**
   * Corrects the views data for list_float base fields.
   *
   * Based on options_field_views_data().
   *
   * @param string $table
   *   The table name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForListFloat($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    $this->processViewsDataForListInteger($table, $field_definition, $views_field, $field_column_name);
  }

  /**
   * Corrects the views data for list_integer base fields.
   *
   * Based on options_field_views_data().
   *
   * @param string $table
   *   The table name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForListInteger($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    if ($field_column_name == 'value') {
      $views_field['filter']['id'] = 'list_field';
      $views_field['argument']['id'] = 'number_list_field';
      // These handlers need "field_name", the default only has "entity field".
      $views_field['filter']['field_name'] = $field_definition->getName();
      $views_field['argument']['field_name'] = $field_definition->getName();
    }
  }

  /**
   * Corrects the views data for list_string base fields.
   *
   * Based on options_field_views_data().
   *
   * @param string $table
   *   The table name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForListString($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    if ($field_column_name == 'value') {
      $views_field['filter']['id'] = 'list_field';
      $views_field['argument']['id'] = 'string_list_field';
      // These handlers need "field_name", the default only has "entity field".
      $views_field['filter']['field_name'] = $field_definition->getName();
      $views_field['argument']['field_name'] = $field_definition->getName();
    }
  }

  /**
   * Corrects the views data for state base fields.
   *
   * Based on state_machine_field_views_data().
   *
   * @param string $table
   *   The table name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForState($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    if ($field_column_name == 'value') {
      $views_field['filter']['id'] = 'state_machine_state';
    }
  }

  /**
   * Adds reverse relationships for the base entity reference fields.
   *
   * @param array $data
   *   The views data.
   * @param \Drupal\Core\Field\BaseFieldDefinition[] $fields
   *   The entity reference fields.
   */
  protected function addReverseRelationships(array &$data, array $fields) {
    $entity_type_id = $this->entityType->id();
    $base_table = $this->getViewsTableForEntityType($this->entityType);
    assert($this->entityType instanceof ContentEntityType);
    $revision_metadata_field_names = array_flip($this->entityType->getRevisionMetadataKeys());

    foreach ($fields as $field) {
      $target_entity_type_id = $field->getSettings()['target_type'];
      $target_entity_type = $this->entityTypeManager->getDefinition($target_entity_type_id);
      if (!($target_entity_type instanceof ContentEntityType)) {
        continue;
      }
      $target_table = $this->getViewsTableForEntityType($target_entity_type);
      $field_name = $field->getName();
      $field_storage = $field->getFieldStorageDefinition();

      $args = [
        '@label' => $target_entity_type->getSingularLabel(),
        '@entity' => $this->entityType->getLabel(),
        '@field_name' => $field_name,
      ];
      $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
      $relationship_data = [
        'label' => $this->entityType->getLabel(),
        'group' => $target_entity_type->getLabel(),
        'entity_type' => $entity_type_id,
      ];
      if ($this->tableMapping->requiresDedicatedTableStorage($field_storage)) {
        $data[$target_table][$pseudo_field_name]['relationship'] = [
          'id' => 'entity_reverse',
          'title' => $this->t('@entity using @field_name', $args),
          'help' => $this->t('Relate each @entity with a @field_name field set to the @label.', $args),
          'base' => $base_table,
          'base field' => $this->entityType->getKey('id'),
          'field_name' => $field_name,
          'field table' => $this->tableMapping->getFieldTableName($field_name),
          'field field' => $this->tableMapping->getFieldColumnName($field_storage, 'target_id'),
        ] + $relationship_data;
      }
      elseif (isset($revision_metadata_field_names[$field_name])) {
        // Revision metadata fields exist only on the revision table, so the
        // relationship has to be to that rather than to the base table.
        $revision_table = $this->tableMapping->getRevisionTable();

        $data[$target_table][$pseudo_field_name]['relationship'] = [
          'id' => 'standard',
          'title' => $this->t('@entity revision using @field_name', $args),
          'help' => $this->t('Relate each @entity revision with a @field_name field set to the @label.', $args),
          'base' => $revision_table,
          'base field' => $this->tableMapping->getFieldColumnName($field_storage, 'target_id'),
          'relationship field' => $target_entity_type->getKey('id'),
        ] + $relationship_data;
      }
      else {
        // The data is on the base table.
        $data[$target_table][$pseudo_field_name]['relationship'] = [
          'id' => 'standard',
          'title' => $this->t('@entity using @field_name', $args),
          'help' => $this->t('Relate each @entity with a @field_name field set to the @label.', $args),
          'base' => $base_table,
          'base field' => $this->tableMapping->getFieldColumnName($field_storage, 'target_id'),
          'relationship field' => $target_entity_type->getKey('id'),
        ] + $relationship_data;
      }
    }
  }

}
