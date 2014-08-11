<?php
/**
 * @file
 * Callbacks for order add pages.
 */

use Drupal\Component\Utility\Xss;

/**
 * Prepares variables for list of available order type templates.
 *
 * Default template: commerce-order-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of order types.
 */
function template_preprocess_commerce_order_add_list(&$variables) {
  if (!empty($variables['types'])) {
    foreach ($variables['types'] as $order_type) {
      $variables['types'][$order_type->id()] = array(
        'type' => $order_type->id(),
        'add_link' => Drupal::l($order_type->label(), 'entity.commerce_order.add', array('commerce_order_type' => $order_type->id())),
        'description' => Xss::filterAdmin($order_type->getDescription()),
      );
    }
  }
}

