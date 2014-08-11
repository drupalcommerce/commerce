<?php

/**
 * @file
 * Callbacks for store add pages.
 */

use Drupal\Component\Utility\Xss;

/**
 * Prepares variables for list of available store type templates.
 *
 * Default template: commerce-store-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of store types.
 */
function template_preprocess_commerce_store_add_list(&$variables) {
  $variables['types'] = array();

  if (!empty($variables['content'])) {
    foreach ($variables['content'] as $commerce_store_type) {
      $variables['types'][$commerce_store_type->id()] = array(
        'type' => $commerce_store_type->id(),
        'add_link' => Drupal::l($commerce_store_type->label(), 'commerce.store_add', array('commerce_store_type' => $commerce_store_type->id())),
        'description' => Xss::filterAdmin($commerce_store_type->getDescription()),
      );
    }
  }
}
