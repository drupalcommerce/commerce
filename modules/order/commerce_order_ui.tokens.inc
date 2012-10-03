<?php

/**
 * @file
 * Builds placeholder replacement tokens for UI bound order-related data.
 */

/**
 * Implements hook_token_info().
 */
function commerce_order_ui_token_info() {
  $order = array();

  // @deprecated since 7.x-1.2; use [commerce-order:customer-url] instead.
  $order['url'] = array(
    'name' => t('URL'),
    'description' => t('The URL of the order.'),
  );
  $order['customer-url'] = array(
    'name' => t('URL'),
    'description' => t('The URL for customers to view the order.'),
  );
  $order['admin-url'] = array(
    'name' => t('URL'),
    'description' => t('The URL for administrators to view the order.'),
  );
  return array(
    'tokens' => array('commerce-order' => $order),
  );
}

/**
 * Implements hook_tokens().
 */
function commerce_order_ui_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $url_options = array('absolute' => TRUE);

  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
    $language_code = $options['language']->language;
  }
  else {
    $language_code = NULL;
  }

  $replacements = array();

  if ($type == 'commerce-order' && !empty($data['commerce-order'])) {
    $order = $data['commerce-order'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // @deprecated since 7.x-1.2; use [commerce-order:customer-url] instead.
        case 'url':
        case 'customer-url':
          if ($uri = commerce_order_uri($order)) {
            $path = $uri['path'];
          }
          else {
            $path = 'user/' . $order->uid . '/orders/' . $order->order_id;
          }
          $replacements[$original] = url($path, $url_options);
          break;
        case 'admin-url':
          $replacements[$original] = url('admin/commerce/orders/' . $order->order_id, $url_options);
          break;
      }
    }
  }

  return $replacements;
}
