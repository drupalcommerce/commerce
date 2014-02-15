<?php

/**
 * @file
 * Administrative forms and page callbacks for the Cart module.
 */


/**
 * Form callback: confirmation form for manually refreshing an order.
 *
 * @param $order
 *   The order object to apply the card refresh to.
 *
 * @return
 *   The form array to confirm the refresh.
 *
 * @see confirm_form()
 */
function commerce_cart_order_refresh_form($form, &$form_state, $order) {
  $form['order_id'] = array(
    '#type' => 'value',
    '#value' => $order->order_id,
  );

  // Build a description of what the user may expect to occur on submission.
  $items = array(
    t('All product prices will be reset and recalculated using the product pricing rules defined on this site.'),
    t('Non-product line items may or may not be updated depending on the type.'),
    t('Custom prices entered on the edit form will be lost.'),
  );

  $form = confirm_form($form,
    t('Are you sure you want to apply pricing rules to order @number?', array('@number' => $order->order_number)),
    'admin/commerce/orders/' . $order->order_id . '/edit',
    '<p>' . theme('item_list', array('items' => $items)) . '</p>',
    t('Apply pricing rules'),
    t('Cancel')
  );

  return $form;
}

/**
 * Form submit callback for commerce_cart_order_refresh_form().
 */
function commerce_cart_order_refresh_form_submit($form, &$form_state) {
  if ($order = commerce_order_load($form_state['values']['order_id'])) {
    commerce_cart_order_refresh($order);
    drupal_set_message(t('Pricing rules have been applied and the order updated.'));
    $form_state['redirect'] = 'admin/commerce/orders/' . $order->order_id . '/edit';
  }
  else {
    drupal_set_message(t('Order not found.'), 'error');
    $form_state['redirect'] = 'admin/commerce/orders';
  }
}
