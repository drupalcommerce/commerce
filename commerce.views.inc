<?php

/**
 * Implements hook_views_data_alter().
 */
function commerce_views_data_alter(array &$data) {
  // Override the default bundle views field handler.
  $entity_types = \Drupal::service('entity_type.manager')->getDefinitions();
  foreach ($entity_types as $entity_type) {
    // Only override for commerce content entities.
    if ($entity_type instanceof \Drupal\Core\Entity\ContentEntityType && strpos($entity_type->id(), 'commerce_') === 0) {
      // Bundle is stored either in the data or the base table.
      if (!empty($data[$entity_type->getDataTable()])) {
        $data[$entity_type->getDataTable()][$entity_type->getKey('bundle')]['field']['id'] = 'entity_bundle';
      }
      else {
        $data[$entity_type->getBaseTable()][$entity_type->getKey('bundle')]['field']['id'] = 'entity_bundle';
      }
    }
  }
}
