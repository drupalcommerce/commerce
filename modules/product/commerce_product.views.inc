<?php

/**
 * @file
 * Views integration for Product.
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data_alter().
 *
 * Adds a custom filter to entity reference fields which reference
 * product attribute values. Same approach as taxonomy_field_views_data_alter().
 *
 * @see views_field_default_views_data()
 */
function commerce_product_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  if ($field_storage->getType() == 'entity_reference' && $field_storage->getSetting('target_type') == 'commerce_product_attribute_value') {
    // Due to their nature, attribute fields are always assumed to be limited
    // to a single attribute (== single target bundle).
    $entity_type_id = $field_storage->getTargetEntityTypeId();
    $bundles = $field_storage->getBundles();
    $bundle = reset($bundles);
    $field_name = $field_storage->getName();
    $field = FieldConfig::loadByName($entity_type_id, $bundle, $field_name);
    $handler_settings = $field->getSetting('handler_settings');
    $target_bundles = array_values($handler_settings['target_bundles']);
    $attribute = reset($target_bundles);

    foreach ($data as $table_name => $table_data) {
      foreach ($table_data as $field_name => $field_data) {
        if (isset($field_data['filter']) && $field_name != 'delta') {
          $data[$table_name][$field_name]['filter']['id'] = 'commerce_product_attribute_value';
          $data[$table_name][$field_name]['filter']['attribute'] = $attribute;
        }
      }
    }
  }
}
