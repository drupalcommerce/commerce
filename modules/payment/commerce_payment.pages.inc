<?php
/**
 * @file
 * Callbacks for payment entities add pages.
 */

use Drupal\Component\Utility\Xss;

/**
 * Prepares variables for list of available order type templates.
 *
 * Default template: commerce-payment-info-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of order types.
 */
function template_preprocess_commerce_payment_info_add_list(&$variables) {
  if (!empty($variables['types'])) {
    foreach ($variables['types'] as $payment_info_type) {
      $variables['types'][$payment_info_type->id()] = array(
        'type' => $payment_info_type->id(),
        'add_link' => l($payment_info_type->label(), 'admin/commerce/payment-info/add/' . $payment_info_type->id()),
        'description' => Xss::filterAdmin($payment_info_type->getDescription()),
      );
    }
  }
}

