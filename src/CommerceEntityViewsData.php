<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Sql\TableMappingInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\entity\BundleFieldDefinition;
use Drupal\views\EntityViewsData;

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
 * Provides views data for bundle plugin fields,
 * as a workaround for core issue #2898635.
 *
 * Provides the missing delta field for multi-value fields,
 * as a workaround for core issue #3097568.
 *
 * Fixes handling of fields with multiple properties,
 * as a workaround for core issue #3097636.
 *
 * Provides reverse relationships for base entity_reference fields,
 * as a workaround for core issue #2706431.
 */
class CommerceEntityViewsData extends EntityViewsData {

  use EntityManagerBridgeTrait;

  /**
   * The table mapping.
   *
   * @var \Drupal\Core\Entity\Sql\DefaultTableMapping
   */
  protected $tableMapping;

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $this->tableMapping = $this->storage->getTableMapping();
    $entity_type_id = $this->entityType->id();
    // Workaround for core issue #3004300.
    if ($this->entityType->isRevisionable()) {
      $revision_table = $this->tableMapping->getRevisionTable();
      $data[$revision_table]['table']['entity revision'] = TRUE;
    }
    // Add missing reverse relationships. Workaround for core issue #2706431.
    $base_fields = $this->getEntityFieldManager()->getBaseFieldDefinitions($entity_type_id);
    $entity_reference_fields = array_filter($base_fields, function (BaseFieldDefinition $field) {
      return $field->getType() === 'entity_reference' && !$field->isComputed();
    });
    if (in_array($entity_type_id, ['commerce_order', 'commerce_product'])) {
      // Product variations and order items have reference fields pointing
      // to the parent entity, no need for a reverse relationship.
      unset($entity_reference_fields['variations']);
      unset($entity_reference_fields['order_items']);
    }
    $this->addReverseRelationships($data, $entity_reference_fields);
    // Add views integration for bundle plugin fields.
    // Workaround for core issue #2898635.
    if ($this->entityType->hasHandlerClass('bundle_plugin')) {
      $bundles = $this->getEntityTypeBundleInfo()->getBundleInfo($entity_type_id);
      foreach (array_keys($bundles) as $bundle) {
        $field_definitions = $this->getEntityFieldManager()->getFieldDefinitions($entity_type_id, $bundle);
        foreach ($field_definitions as $field_definition) {
          if ($field_definition instanceof BundleFieldDefinition) {
            $this->addBundleFieldData($data, $field_definition);
          }
        }
      }
    }
    // Use custom bundle handlers which know how to handle non-config bundles.
    // Workaround for core issue #3056998.
    if ($bundle_key = $this->entityType->getKey('bundle')) {
      $base_table = $this->getViewsTableForEntityType($this->entityType);
      $data[$base_table][$bundle_key]['field']['id'] = 'commerce_entity_bundle';
      $data[$base_table][$bundle_key]['filter']['id'] = 'commerce_entity_bundle';
    }

    return $data;
  }

  /**
   * Adds views data for the given bundle field.
   *
   * Based on views_field_default_views_data(), which is only invoked
   * for configurable fields.
   *
   * Assumes that the bundle field is not shared between bundles, since
   * the bundle plugin API doesn't support that.
   *
   * @param array $data
   *   The views data.
   * @param \Drupal\entity\BundleFieldDefinition $bundle_field
   *   The bundle field.
   */
  protected function addBundleFieldData(array &$data, BundleFieldDefinition $bundle_field) {
    $field_name = $bundle_field->getName();
    $entity_type_id = $this->entityType->id();
    $base_table = $this->getViewsTableForEntityType($this->entityType);
    $revision_table = '';
    if ($this->entityType->isRevisionable()) {
      $revision_table = $this->tableMapping->getRevisionDataTable();
      if (!$revision_table) {
        $revision_table = $this->tableMapping->getRevisionTable();
      }
    }

    $field_tables = [];
    $field_tables[EntityStorageInterface::FIELD_LOAD_CURRENT] = [
      'table' => $this->tableMapping->getDedicatedDataTableName($bundle_field),
      'alias' => "{$entity_type_id}__{$field_name}",
    ];
    if ($this->entityType->isRevisionable()) {
      $field_tables[EntityStorageInterface::FIELD_LOAD_REVISION] = [
        'table' => $this->tableMapping->getDedicatedRevisionTableName($bundle_field),
        'alias' => "{$entity_type_id}_revision__{$field_name}",
      ];
    }

    $table_alias = $field_tables[EntityStorageInterface::FIELD_LOAD_CURRENT]['alias'];
    $data[$table_alias]['table']['group'] = $this->entityType->getLabel();
    $data[$table_alias]['table']['join'][$base_table] = [
      'table' => $this->tableMapping->getDedicatedDataTableName($bundle_field),
      'left_field' => $this->entityType->getKey('id'),
      'field' => 'entity_id',
      'extra' => [
        ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
      ],
    ];
    if ($bundle_field->isTranslatable()) {
      $data[$table_alias]['table']['join'][$base_table]['extra'][] = [
        'left_field' => 'langcode',
        'field' => 'langcode',
      ];
    }

    if ($this->entityType->isRevisionable()) {
      $table_alias = $field_tables[EntityStorageInterface::FIELD_LOAD_REVISION]['alias'];
      $data[$table_alias]['table']['group'] = $this->t('@group (historical data)', [
        '@group' => $this->entityType->getLabel(),
      ]);
      $data[$table_alias]['table']['join'][$revision_table] = [
        'table' => $this->tableMapping->getDedicatedRevisionTableName($bundle_field),
        'left_field' => $this->entityType->getKey('revision'),
        'field' => 'revision_id',
        'extra' => [
          ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
        ],
      ];
      if ($bundle_field->isTranslatable()) {
        $data[$table_alias]['table']['join'][$revision_table]['extra'][] = [
          'left_field' => 'langcode',
          'field' => 'langcode',
        ];
      }
    }

    foreach ($field_tables as $type => $table_info) {
      $table_alias = $table_info['alias'];
      $data[$table_alias]['table']['title'] = $bundle_field->getLabel();
      $data[$table_alias]['table']['help'] = $bundle_field->getDescription();
      $data[$table_alias]['table']['entity type'] = $this->entityType->id();
      $data[$table_alias]['table']['provider'] = $this->entityType->getProvider();

      $this->mapFieldDefinition($table_info['table'], $field_name, $bundle_field, $this->tableMapping, $data[$table_alias]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function mapFieldDefinition($table, $field_name, FieldDefinitionInterface $field_definition, TableMappingInterface $table_mapping, &$table_data) {
    $field_column_mapping = $table_mapping->getColumnNames($field_name);
    $field_storage = $this->getFieldStorageDefinitions()[$field_name];
    $field_schema = $field_storage->getSchema();

    $field_definition_type = $field_definition->getType();
    // Add all properties to views table data. We need an entry for each
    // column of each field, with the main one given special treatment.
    $main_property = $field_storage->getMainPropertyName();
    if (!$main_property) {
      // The mapSingleFieldViewsData() method always expects a main property,
      // so there must be a fallback to the first defined property.
      // See #2337517 for the related core issue.
      $property_names = array_keys($field_column_mapping);
      $main_property = reset($property_names);
    }
    foreach ($field_column_mapping as $field_column_name => $schema_field_name) {
      $first = ($main_property == $field_column_name);
      $table_data[$schema_field_name] = $this->mapSingleFieldViewsData($table, $field_name, $field_definition_type, $field_column_name, $field_schema['columns'][$field_column_name]['type'], $first, $field_definition);
      $table_data[$schema_field_name]['entity field'] = $field_name;
      // By default core makes every property render the entire field, which
      // confuses users. Fix that so only the property itself is rendered.
      // Workaround for core issue #3097636.
      if (!$first && $table_data[$schema_field_name]['field']['id'] == 'field') {
        $table_data[$schema_field_name]['field']['id'] = 'standard';
      }
      // Many handlers (datetime, list) crash without the field_name defined.
      foreach (['argument', 'filter', 'sort'] as $handler_type) {
        if (isset($table_data[$schema_field_name][$handler_type])) {
          $table_data[$schema_field_name][$handler_type]['field_name'] = $field_name;
        }
      }
    }

    // Expose additional delta column for multiple value fields.
    // Workaround for core issue #3097568.
    if ($field_storage->isMultiple()) {
      $label = $field_definition->getLabel();

      $table_data['delta'] = [
        'title' => t('@label (@name:delta)', ['@label' => $label, '@name' => $field_name]),
        'title short' => t('@label:delta', ['@label' => $label]),
      ];
      $table_data['delta']['field'] = [
        'id' => 'numeric',
      ];
      $table_data['delta']['argument'] = [
        'field' => 'delta',
        'table' => $table,
        'id' => 'numeric',
        'empty field name' => $this->t('- No value -'),
        'field_name' => $field_name,
        'entity_type' => $this->entityType->id(),
      ];
      $table_data['delta']['filter'] = [
        'field' => 'delta',
        'table' => $table,
        'id' => 'numeric',
        'field_name' => $field_name,
        'entity_type' => $this->entityType->id(),
        'allow empty' => TRUE,
      ];
      $table_data['delta']['sort'] = [
        'field' => 'delta',
        'table' => $table,
        'id' => 'standard',
        'field_name' => $field_name,
        'entity_type' => $this->entityType->id(),
      ];
    }
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
    elseif ($field_column_name == 'currency_code') {
      $views_field['filter']['id'] = 'commerce_currency';
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
      $target_entity_type = $this->getEntityTypeManager()->getDefinition($target_entity_type_id);
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
