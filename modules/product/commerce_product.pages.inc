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
    foreach ($variables['content'] as $product_type) {
      $variables['types'][$product_type->id()] = array(
        'type' => $product_type->id(),
        'add_link' => Drupal::l($product_type->label(), 'entity.commerce_product.add_form', array('commerce_product_type' => $product_type->id())),
        'description' => Xss::filterAdmin($product_type->getDescription()),
      );
    }
  }
}
