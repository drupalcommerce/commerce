<?php
// $Id$

/**
 * The page and form callbacks for use in the checkout form.
 */


/**
 * Redirects invalid checkout attempts or displays the checkout form if valid.
 */
function commerce_checkout_router() {
  global $user;

  // Load the shopping cart order.
  $order = commerce_cart_order_load($user->uid);

  // If no shopping cart order could be found, redirect away from checkout.
  if (empty($order)) {
    drupal_set_message(t('Add some items to your cart and then try checking out.'));
    drupal_goto('<front>');
  }

  return drupal_get_form('commerce_checkout_form', $order);
}

/**
 * Builds the checkout form based on the current step in checkout.
 */
function commerce_checkout_form($form, &$form_state, $order = NULL) {
  global $user;

  $form = array(
    '#validate' => array(),
  );

  $form['#attached']['css'] = array(
    drupal_get_path('module', 'commerce_checkout') .'/theme/commerce_checkout.css'
  );

  $form['uid'] = array(
    '#type' => 'hidden',
    '#value' => $user->uid,
  );

  $form['account'] = array(
    '#type' => 'value',
    '#value' => clone($user),
  );

  // If no order was specified, look for one now.
  // TODO: Order should not be an optional argument to this function; business
  // logic should not exist in this callback. There should not be a dependency
  // on Cart here or in the router.
  if (empty($order)) {
    $order = commerce_cart_order_load($user->uid);
  }

  // If an order can't be found, return a blank form.
  // TODO: Also redundant. :-/
  if (empty($order)) {
    $form['empty'] = array(
      '#markup' => t('Add some items to your cart and then try checking out.'),
    );

    return $form;
  }

  $form['order'] = array(
    '#type' => 'value',
    '#value' => $order,
  );

  // Load data for the current page in checkout.
  $checkout_pages = commerce_checkout_pages();
  $page_id = !empty($form_state['rebuild']) ? $form_state['storage']['page_id'] : key($checkout_pages);
  $checkout_page = $checkout_pages[$page_id];

  $form['checkout_page'] = array(
    '#type' => 'value',
    '#value' => $checkout_page,
  );

  // Update the page title if specified.
  if (!empty($checkout_page->title)) {
    drupal_set_title($checkout_page->title);
  }

  // Add the instructions for the checkout page if specified.
  $form['help'] = array(
    '#markup' => filter_xss($checkout_page->help),
  );

  // Add any enabled checkout panes for this page to the form.
  foreach (commerce_checkout_panes(array('enabled' => TRUE, 'page' => $page_id)) as $pane_id => $checkout_pane) {
    if ($callback = commerce_checkout_pane_callback($checkout_pane, 'checkout_form')) {
      // Create a fieldset for the pane.
      $form[$pane_id] = array(
        '#type' => 'fieldset',
        '#title' => check_plain($checkout_pane->title),
        '#collapsible' => $checkout_pane->collapsible,
        '#collapsed' => $checkout_pane->collapsed,
        '#attributes' => array('class' => array($pane_id)),
        '#tree' => TRUE,
      );

      // Add the form data defined in the pane's form callback.
      $form[$pane_id] += $callback($form_state, $checkout_pane, $order);

      // Add the validate handler for the pane to the form array.
      if ($callback = commerce_checkout_pane_callback($checkout_pane, 'checkout_form_validate')) {
        $form['#validate'][] = $callback;
      }
    }
  }

  $form['buttons'] = array(
    '#type' => 'fieldset',
    '#attributes' => array('class' => array('checkout-buttons')),
  );

  // Add a review class to the buttons fieldset on the review page.
  if ($page_id == 'review') {
    $form['buttons']['#attributes']['class'][] = 'review';
  }

  // Add the cancel or back button where appropriate.
  if (!$checkout_page->prev_page && !empty($checkout_page->back_value)) {
    // Add an empty "Back" button value to avoid submission errors.
    $form['buttons']['back'] = array(
      '#type' => 'value',
      '#value' => '',
    );

    // Store the cancel redirect in the form so modules can modify it easily.
    $form['cancel_redirect'] = array(
      '#type' => 'value',
      '#value' => '<front>',
    );

    $form['buttons']['cancel'] = array(
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#validate' => array(),
      '#submit' => array('commerce_checkout_form_cancel_submit'),
    );
  }
  elseif ($checkout_page->prev_page && !empty($checkout_page->back_value)) {
    $form['buttons']['back'] = array(
      '#type' => 'submit',
      '#value' => $checkout_page->back_value,
    );
  }

  $form['buttons']['continue'] = array(
    '#type' => 'submit',
    '#value' => $checkout_page->submit_value,
  );

  return $form;
}

/**
 * Submit handler for the continue and back buttons of the checkout form.
 */
function commerce_checkout_form_submit($form, &$form_state) {
  global $user;

  // Load a fresh copy of the order stored in the form.
  $order = commerce_order_load($form_state['values']['order']->order_id);

  // Loop through the enabled checkout panes.
  foreach (commerce_checkout_panes(array('enabled' => TRUE, 'page' => $form_state['values']['checkout_page']->page_id)) as $pane_id => $checkout_pane) {
    // If the pane has defined a checkout form submit handler...
    if ($callback = commerce_checkout_pane_callback($checkout_pane, 'checkout_form_submit')) {
      // Give it a chance to process the submitted data.
      $callback($form, $form_state, $order);
    }
  }

  // Save the updated order object.
  $order = commerce_order_save($order);

  $checkout_page = $form_state['values']['checkout_page'];

  // Determine which button was used to submit the form.
  switch($form_state['values']['op']) {
    // Customer clicked the back button.
    case $form_state['values']['back']:
      // If there is a previous page...
      if ($checkout_page->prev_page) {
        // Move the form back to that page.
        $form_state['storage']['page_id'] = $checkout_page->prev_page;
      }
      break;

    // Customer clicked the continue button.
    case $form_state['values']['continue']:
      // If there is another checkout page...
      if ($checkout_page->next_page) {
        // Move the form forward to that page.
        $form_state['storage']['page_id'] = $checkout_page->next_page;
        $form_state['rebuild'] = TRUE;
      }
      else {
        // Or go ahead and complete the sale.
        commerce_checkout_complete($form_state, $order);

        // Empty the form storage and proceed to completion.
        $form_state['redirect'] = 'checkout/complete/'. $order->order_id;
        unset($form_state['storage']);
      }
      break;
  }
}

/**
 * Special submit handler for the cancel button to avoid processing orders.
 */
function commerce_checkout_form_cancel_submit($form, &$form_state) {
  if ($form_state['values']['order']) {
    // TODO: Implement order comments to do this.
    // commerce_order_comment_save($form_state['values']['order']->order_id, 0, t('Customer cancelled this order from the checkout form.'));
  }

  drupal_set_message(t('Checkout of your current order has been canceled at may be resumed when you are ready.'));
  unset($form_state['storage']);

  $form_state['redirect'] = $form_state['values']['cancel_redirect'];
}

/**
 * Themes the optional checkout review page data.
 */
function theme_commerce_checkout_review($variables) {
  $form = $variables['form'];

  // Turn the review data array into table rows.
  $rows = array();

  foreach ($form['#data'] as $pane_id => $data) {
    // First add a row for the title.
    $rows[] = array(
      'data' => array(
        array('data' => $data['title'], 'colspan' => 2),
      ),
      'class' => array('pane-title', 'odd'),
    );

    // Next, add the data for this particular section.
    if (is_array($data['data'])) {
      // If it's an array, treat each key / value pair as a 2 column row.
      foreach ($data['data'] as $key => $value) {
        $rows[] = array(
          'data' => array(
            array('data' => $key .':', 'class' => array('pane-data-key')),
            array('data' => $value, 'class' => array('pane-data-value')),
          ),
          'class' => array('pane-data', 'even'),
        );
      }
    }
    else {
      // Otherwise treat it as a block of text in its own row.
      $rows[] = array(
        'data' => array(
          array('data' => $data['data'], 'colspan' => 2, 'class' => array('pane-data-full')),
        ),
        'class' => array('pane-data', 'even'),
      );
    }
  }

  return theme('table', array('rows' => $rows, 'attributes' => array('class' => array('checkout-review'))));
}

/**
 * Displays the checkout completion page for an order.
 */
function commerce_checkout_completion($order) {
  return t('Your order is complete! This is placeholder text for order completion message.');
}
