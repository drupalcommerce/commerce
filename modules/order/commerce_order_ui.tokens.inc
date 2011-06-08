<?php

/**
 * @file
 * Builds placeholder replacement tokens for UI bound order-related data.
 */
function commerce_order_ui_token_info() {
  $order = array();

  $order['url'] = array(
    'name' => t('URL'),
    'description' => t('The URL of the order.'),
  );
  return array(
    'tokens' => array('commerce-order' => $order),
  );
}


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
        case 'url':
          $replacements[$original] = url('user/' . $order->uid . '/orders/' . $order->order_id, $url_options);
          break;
      }
    }
  }

  return $replacements;
}