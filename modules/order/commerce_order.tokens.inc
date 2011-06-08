<?php

/**
 * @file
 * Builds placeholder replacement tokens for order-related data.
 */


/**
 * Implements hook_token_info().
 */
function commerce_order_token_info() {
  $type = array(
    'name' => t('Orders', array(), array('context' => 'a drupal commerce order')),
    'description' => t('Tokens related to individual orders.'),
    'needs-data' => 'commerce-order',
  );

  // Tokens for orders.
  $order = array();

  $order['order-id'] = array(
    'name' => t('Order ID', array(), array('context' => 'a drupal commerce order')),
    'description' => t('The unique numeric ID of the order.'),
  );
  $order['order-number'] = array(
    'name' => t('Order number', array(), array('context' => 'a drupal commerce order')),
    'description' => t('The order number displayed to the customer.'),
  );
  $order['revision-id'] = array(
    'name' => t('Revision ID'),
    'description' => t("The unique ID of the order's latest revision."),
  );
  $order['type'] = array(
    'name' => t('Order type'),
    'description' => t('The type of the order.'),
  );
  $order['type-name'] = array(
    'name' => t('Order type name'),
    'description' => t('The human-readable name of the order type.'),
  );
  $order['mail'] = array(
    'name' => t('Order e-mail'),
    'description' => t('The e-mail address associated with the order.'),
  );
  $order['status'] = array(
    'name' => t('Order status'),
    'description' => t('The current status of the order.'),
  );
  $order['status-title'] = array(
    'name' => t('Order status title'),
    'description' => t('The human-readable title of the order status.'),
  );
  $order['state'] = array(
    'name' => t('Order state'),
    'description' => t('The current state of the order.'),
  );
  $order['state-title'] = array(
    'name' => t('Order state title'),
    'description' => t('The human-readable title of the order state.'),
  );

  // Chained tokens for orders.
  $order['owner'] = array(
    'name' => t('Owner'),
    'description' => t('The owner of the order.'),
    'type' => 'user',
  );
  $order['created'] = array(
    'name' => t('Date created'),
    'description' => t('The date the order was created.'),
    'type' => 'date',
  );
  $order['changed'] = array(
    'name' => t('Date changed'),
    'description' => t('The date the order was last updated.'),
    'type' => 'date',
  );

  return array(
    'types' => array('commerce-order' => $type),
    'tokens' => array('commerce-order' => $order),
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

  if ($type == 'commerce-order' && !empty($data['commerce-order'])) {
    $order = $data['commerce-order'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the order.
        case 'order-id':
          $replacements[$original] = $order->order_id;
          break;

        case 'order-number':
          $replacements[$original] = $sanitize ? check_plain($order->order_number) : $order->order_number;
          break;

        case 'revision_id':
          $replacements[$original] = $order->revision_id;
          break;

        case 'type':
          $replacements[$original] = $sanitize ? check_plain($order->type) : $order->type;
          break;

        case 'type-name':
          $type_name = commerce_order_type_get_name($order->type);
          $replacements[$original] = $sanitize ? check_plain($type_name) : $type_name;
          break;

        case 'mail':
          $replacements[$original] = $sanitize ? check_plain($order->mail) : $order->mail;
          break;

        case 'status':
          $replacements[$original] = $sanitize ? check_plain($order->status) : $order->status;
          break;

        case 'status-title':
          $replacements[$original] = $sanitize ? check_plain(commerce_order_status_get_title($order->status)) : commerce_order_status_get_title($order->status);
          break;

        case 'state':
          $order_status = commerce_order_status_load($order->status);
          $replacements[$original] = $sanitize ? check_plain($order_status['state']) : $order_status['state'];
          break;

        case 'state-title':
          $order_status = commerce_order_status_load($order->status);
          $replacements[$original] = $sanitize ? check_plain(commerce_order_state_get_title($order_status['state'])) : commerce_order_state_get_title($order_status['state']);
          break;


        // Default values for the chained tokens handled below.
        case 'owner':
          if ($order->uid == 0) {
            $name = variable_get('anonymous', t('Anonymous'));
          }
          else {
            $account = user_load($order->uid);
            $name = $account->name;
          }
          $replacements[$original] = $sanitize ? filter_xss($name) : $name;
          break;
        case 'created':
          $replacements[$original] = format_date($order->created, 'medium', '', NULL, $language_code);
          break;

        case 'changed':
          $replacements[$original] = format_date($order->changed, 'medium', '', NULL, $language_code);
          break;
      }
    }


    if ($owner_tokens = token_find_with_prefix($tokens, 'owner')) {
      $owner = user_load($order->uid);
      $replacements += token_generate('user', $owner_tokens, array('user' => $owner), $options);
    }

    foreach (array('created', 'changed') as $date) {
      if ($created_tokens = token_find_with_prefix($tokens, $date)) {
        $replacements += token_generate('date', $created_tokens, array('date' => $order->{$date}), $options);
      }
    }
  }

  return $replacements;
}