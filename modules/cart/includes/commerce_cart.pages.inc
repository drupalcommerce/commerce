<?php
// $Id$

/**
 * @file
 * The page and form callbacks for use by the shopping cart.
 */


/**
 * Redirects invalid checkout attempts or displays the checkout form if valid.
 */
function commerce_cart_checkout_router() {
  global $user;

  // Load the shopping cart order.
  $order = commerce_cart_order_load($user->uid);

  // If no shopping cart order could be found, redirect away from checkout.
  // TODO: Redirect to the cart page instead which would then appear as an
  // empty shopping cart page. Depends on
  if (empty($order) || commerce_line_items_quantity($order->line_items, 'product') == 0) {
    drupal_set_message(t('Add some items to your cart and then try checking out.'));
    drupal_goto(variable_get('commerce_checkout_empty_redirect', ''));
  }

  drupal_goto('checkout/' . $order->order_id);
}

/**
 * Displays the shopping cart form and associated information.
 */
function commerce_cart_view() {
  global $user;

  // First check for items in the shopping cart.
  $order = commerce_cart_order_load($user->uid);

  // If the cart is empty...
  if (!$order || empty($order->line_items)) {
    // Display an appropriate message.
    $content = theme('commerce_cart_empty_page');
  }
  else {
    // Add the form for editing the cart contents.
    $content = drupal_get_form('commerce_cart_form', $order);
  }

  return $content;
}

/**
 * Builds the editable shopping cart form.
 */
function commerce_cart_form($form, &$form_state, $order) {
  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_cart') . '/theme/commerce_cart.css';

  $form['order'] = array(
    '#type' => 'value',
    '#value' => $order,
  );

  // First build an array of line items.
  $line_item_ids = array();

  foreach ($order->line_items[LANGUAGE_NONE] as $key => $value) {
    $line_item_ids[] = $value['line_item_id'];
  }

  $line_items = commerce_line_item_load_multiple($line_item_ids);

  // The display title should come from the line item type.
  $product_line_item_type = commerce_line_item_type_load('product');
  $title_callback = $product_line_item_type->title_callback;

  // Build the actual form with rows for product line items.
  $form['line_items'] = array(
    '#tree' => TRUE,
    '#theme' => 'commerce_cart_form_product_table',
    '#header' => array(t('Remove'), t('Title'), t('SKU'), t('Qty'), t('Unit Price'), t('Total')),
    '#empty' => t('No products found.'),
    '#attached' => array(
      'js' => array(drupal_get_path('module', 'commerce_cart') . '/commerce_cart.js'),
    ),
  );

  foreach ($line_items as $line_item_id => $line_item) {
    if ($line_item->type == 'product') {
      $form['line_items'][$line_item_id]['line_item'] = array(
        '#type' => 'value',
        '#value' => $line_item,
      );

      $form['line_items'][$line_item_id]['remove'] = array(
        '#type' => 'checkbox',
        '#attributes' => array('class' => array('onclick-submit')),
      );

      $form['line_items'][$line_item_id]['title'] = array(
        '#markup' => $title_callback($line_item),
      );

      $form['line_items'][$line_item_id]['line_item_label'] = array(
        '#markup' => check_plain($line_item->line_item_label),
      );

      $form['line_items'][$line_item_id]['quantity'] = array(
        '#type' => 'textfield',
        '#default_value' => round($line_item->quantity),
        '#size' => 4,
      );

      $form['line_items'][$line_item_id]['unit_price'] = array(
        '#markup' => commerce_currency_format($line_item->unit_price[LANGUAGE_NONE][0]['price'], $line_item->unit_price[LANGUAGE_NONE][0]['currency_code'], $line_item),
      );

      $form['line_items'][$line_item_id]['total'] = array(
        '#markup' => commerce_currency_format($line_item->total[LANGUAGE_NONE][0]['price'], $line_item->total[LANGUAGE_NONE][0]['currency_code'], $line_item),
      );
    }
  }

  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight' => 100,
  );


  // We add the form's #submit array to this button along with the actual submit
  // handler to preserve any submit handlers added by a form callback_wrapper.
  $submit = array();

  if (!empty($form['#submit'])) {
    $submit += $form['#submit'];
  }

  $form['actions']['update'] = array(
    '#type' => 'submit',
    '#value' => t('Update cart'),
    '#submit' => $submit + array('commerce_cart_form_submit'),
    '#weight' => 40,
  );

  $form['actions']['checkout'] = array(
    '#type' => 'submit',
    '#value' => t('Checkout'),
    '#submit' => $submit + array('commerce_cart_form_submit'),
    '#weight' => 45,
  );

  // We append the validate handler to #validate in case a form callback_wrapper
  // is used to add validate handlers earlier.
  $form['#validate'][] = 'commerce_cart_form_validate';

  return $form;
}

/**
 * Validation callback for commerce_cart_form().
 */
function commerce_cart_form_validate($form, &$form_state) {
  foreach (element_children($form['line_items']) as $line_item_id) {
    if (!is_numeric($form_state['values']['line_items'][$line_item_id]['quantity']) || $form_state['values']['line_items'][$line_item_id]['quantity'] <= 0) {
      form_set_error('line_items][' . $line_item_id .'][quantity', t('You must specify a positive numeric value for the quantity'));
    }
  }
}

/**
 * Submit callback for commerce_cart_form().
 */
function commerce_cart_form_submit($form, &$form_state) {
  $order = commerce_order_load($form_state['values']['order']->order_id);

  // Loop through the line items and update the items on the cart order.
  foreach (element_children($form['line_items']) as $line_item_id) {
    // Check for the removal of an item.
    if ($form_state['values']['line_items'][$line_item_id]['remove']) {
      // Remove the line item from the line item reference field.
      foreach ($order->line_items[LANGUAGE_NONE] as $key => $value) {
        if ($value['line_item_id'] == $line_item_id) {
          unset($order->line_items[LANGUAGE_NONE][$key]);
        }
      }

      // Delete the actual line item.
      commerce_line_item_delete($line_item_id);
    }
    else {
      // Otherwise if there is a change in quantity...
      if ($form_state['values']['line_items'][$line_item_id]['quantity'] != $form_state['values']['line_items'][$line_item_id]['line_item']->quantity) {
        // Load the original line item.
        $line_item = commerce_line_item_load($line_item_id);

        // Change the quantity and save it.
        $line_item->quantity = $form_state['values']['line_items'][$line_item_id]['quantity'];
        commerce_line_item_save($line_item);
      }
    }
  }

  // Save the order to capture any changes in line items referenced.
  commerce_order_save($order);

  // Redirect to the checkout page if specified.
  if ($form_state['clicked_button']['#value'] == $form['actions']['checkout']['#value']) {
    $form_state['redirect'] = 'checkout';
  }
  else {
    drupal_set_message(t('Your shopping cart has been updated.'));
  }
}

/**
 * Themes the editable shopping cart form.
 */
function theme_commerce_cart_form_product_table($variables) {
  $form = $variables['form'];
  $rows = array();

  // Add each line item to the table.
  foreach (element_children($form) as $line_item_id) {
    $row = array(
      drupal_render($form[$line_item_id]['remove']),
      drupal_render($form[$line_item_id]['title']),
      drupal_render($form[$line_item_id]['line_item_label']),
      drupal_render($form[$line_item_id]['quantity']),
      drupal_render($form[$line_item_id]['unit_price']),
      drupal_render($form[$line_item_id]['total']),
    );

    $rows[] = $row;
  }

  // Setup the table's variables array and build the output.
  $table_variables = array(
    'header' => $form['#header'],
    'rows' => $rows,
    'empty' => $form['#empty'],
  );

  $output = theme('table', $table_variables);

  return '<div id="cart-form-table">' . $output . '</div>';
}
