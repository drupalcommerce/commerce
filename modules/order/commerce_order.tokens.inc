<?php
// $Id$

/**
 * @file
 * Builds placeholder replacement tokens for order-related data.
 */


/**
 * Implements hook_token_info().
 */
function commerce_order_token_info() {
  $type = array(
    'name' => t('Orders'),
    'description' => t('Tokens related to individual orders.'),
    'needs-data' => 'order',
  );

  // Tokens for orders.
  $order = array();

  $order['order-id'] = array(
    'name' => t('Order ID'),
    'description' => t('The unique numeric ID of the order.'),
  );
  $order['order-number'] = array(
    'name' => t('Order number'),
    'description' => t('The order number displayed to the customer.'),
  );
  $order['edit-url'] = array(
    'name' => t('Edit URL'),
    'description' => t("The URL of the order's edit page."),
  );

  // Chained tokens for orders.
  $order['created'] = array(
    'name' => t('Date created'),
    'description' => t('The date the order was created.'),
    'type' => 'date',
  );

  return array(
    'types' => array('order' => $type),
    'tokens' => array('order' => $order),
  );
}

/**
 * Implements hook_tokens().
 */
function commerce_order_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $url_options = array('absolute' => TRUE);

  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
    $language_code = $options['language']->language;
  }
  else {
    $language_code = NULL;
  }

  $sanitize = !empty($options['sanitize']);

  $replacements = array();

  if ($type == 'order' && !empty($data['order'])) {
    $order = $data['order'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the order.
        case 'order-id':
          $replacements[$original] = $order->order_id;
          break;

        case 'order-number':
          $replacements[$original] = $sanitize ? check_plain($order->order_number) : $order->order_number;
          break;

        case 'edit-url':
          $replacements[$original] = url('admin/commerce/orders/' . $order->order_id . '/edit', $url_options);
          break;

        // Default values for the chained tokens handled below.
        case 'created':
          $replacements[$original] = format_date($order->created, 'medium', '', NULL, $language_code);
          break;
      }
    }

    if ($created_tokens = token_find_with_prefix($tokens, 'created')) {
      $replacements += token_generate('date', $created_tokens, array('date' => $order->created), $options);
    }
  }

  return $replacements;
}
