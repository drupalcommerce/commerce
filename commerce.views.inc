<?php

/**
 * @file
 * Views integration for Commerce.
 */

use Drupal\Core\Entity\ContentEntityType;

/**
 * Implements hook_views_data_alter().
 */
function commerce_views_data_alter(array &$data) {
  // Override the bundle views handlers for commerce content entities.
  $entity_types = \Drupal::service('entity_type.manager')->getDefinitions();
  foreach ($entity_types as $entity_type) {
    if ($entity_type instanceof ContentEntityType && strpos($entity_type->id(), 'commerce_') === 0) {
      // Translatable entities have a data table. Non-translatable ones
      // (such as Order) have only a base table.
      if ($data_table = $entity_type->getDataTable()) {
        $data[$data_table][$entity_type->getKey('bundle')]['field']['id'] = 'commerce_entity_bundle';
        $data[$data_table][$entity_type->getKey('bundle')]['filter']['id'] = 'commerce_entity_bundle';
      }
      else {
        $data[$entity_type->getBaseTable()][$entity_type->getKey('bundle')]['field']['id'] = 'commerce_entity_bundle';
        $data[$entity_type->getBaseTable()][$entity_type->getKey('bundle')]['filter']['id'] = 'commerce_entity_bundle';
      }
    }
  }
}
