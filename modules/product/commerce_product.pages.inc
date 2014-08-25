<?php

/**
 * @file
 * Callbacks for product add pages.
 */

use Drupal\Component\Utility\Xss;

/**
 * Prepares variables for list of available product type templates.
 *
 * Default template: commerce-product-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of product types.
 */
function template_preprocess_commerce_product_add_list(&$variables) {
  $variables['types'] = array();

  if (!empty($variables['content'])) {
    foreach ($variables['content'] as $commerce_product_type) {
      $variables['types'][$commerce_product_type->id()] = array(
        'type' => $commerce_product_type->id(),
        'add_link' => Drupal::l($commerce_product_type->label(), 'entity.commerce_product.add_form', array('commerce_product_type' => $commerce_product_type->id())),
        'description' => Xss::filterAdmin($commerce_product_type->getDescription()),
      );
    }
  }
}
