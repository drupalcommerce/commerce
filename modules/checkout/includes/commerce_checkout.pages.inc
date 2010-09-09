<?php
// $Id$

/**
 * @file
 * The page and form callbacks for use in the checkout form.
 */


/**
 * Redirects invalid checkout attempts or displays the checkout form if valid.
 */
function commerce_checkout_router($order, $checkout_page = NULL) {
  // If no checkout page is specified, default to the first one.
  if (empty($checkout_page)) {
    $checkout_page = reset(commerce_checkout_pages());
  }

  // If the user does not have access to checkout the order, return a 404. We
  // could return a 403, but then the user would know they've identified a
  // potentially valid checkout URL.
  if (!commerce_checkout_access($order, $checkout_page)) {
    return drupal_not_found();
  }

  // If there are no line items on the order, redirect away.
  if (commerce_line_items_quantity($order->line_items, 'product') == 0) {
    drupal_goto(variable_get('commerce_checkout_empty_redirect', '<front>'));
  }

  // Update the page title if specified.
  if (!empty($checkout_page->title)) {
    drupal_set_title($checkout_page->title);
  }

  return drupal_get_form('commerce_checkout_form_' . $checkout_page->page_id, $order, $checkout_page);
}

/**
 * Builds the checkout form based on the current step in checkout.
 *
 * @param $order
 *   The fully loaded order object being checked out.
 * @param $checkout_page
 *   The checkout page object representing the current step in checkout.
 */
function commerce_checkout_form($form, &$form_state, $order, $checkout_page) {
  global $user;

  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_checkout') .'/theme/commerce_checkout.css';
  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_checkout') . '/commerce_checkout.js';

  $form['account'] = array(
    '#type' => 'value',
    '#value' => clone($user),
  );

  $form['order'] = array(
    '#type' => 'value',
    '#value' => $order,
  );

  $form['checkout_page'] = array(
    '#type' => 'value',
    '#value' => $checkout_page,
  );

  // Add any instructions for the checkout page.
  $form['help'] = array(
    '#markup' => '<div class="checkout-help">' . filter_xss($checkout_page->help) . '</div>',
  );

  // Restore form errors.
  if (!empty($form_state['storage']['errors'])) {
    $form_errors = &drupal_static('form_set_error', array());
    $form_errors = $form_state['storage']['errors'];
  }

  // Add any enabled checkout panes for this page to the form.
  foreach (commerce_checkout_panes(array('enabled' => TRUE, 'page' => $checkout_page->page_id)) as $pane_id => $checkout_pane) {
    if ($callback = commerce_checkout_pane_callback($checkout_pane, 'checkout_form')) {
      // Display error messages before the form.
      if (!empty($form_state['storage']['messages'][$pane_id])) {
        $form[$pane_id . '_errors'] = array(
          '#markup' =>  $form_state['storage']['messages'][$pane_id],
        );
      }

      // Create a fieldset for the pane and add the form data defined in the
      // pane's form callback.
      // $pane_values = isset($form_state['values'][$pane_id]) ? $form_state['values'][$pane_id] : array();
      if ($pane_form = $callback($form, $form_state, $checkout_pane, $order)) {
        $form[$pane_id] = $pane_form + array(
          '#type' => 'fieldset',
          '#title' => check_plain($checkout_pane->title),
          '#collapsible' => $checkout_pane->collapsible,
          '#collapsed' => $checkout_pane->collapsed,
          '#attributes' => array('class' => array($pane_id)),
          '#tree' => TRUE,
        );
      }
    }
  }

  // Only add buttons to the form if the checkout page hasn't disabled them.
  if ($checkout_page->buttons) {
    $form['buttons'] = array(
      '#type' => 'fieldset',
      '#attributes' => array('class' => array('checkout-buttons')),
    );

    // Add the cancel or back button where appropriate. We define button level
    // submit handlers because we're using hook_forms() to use this form builder
    // function and to avoid issues if other modules implement button level submit
    // handlers on these or custom checkout buttons.
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
        '#attributes' => array('class' => array('checkout-cancel')),
        '#validate' => array(),
        '#submit' => array('commerce_checkout_form_cancel_submit'),
      );
    }
    elseif ($checkout_page->prev_page && !empty($checkout_page->back_value)) {
      $form['buttons']['back'] = array(
        '#type' => 'submit',
        '#value' => $checkout_page->back_value,
        '#attributes' => array('class' => array('checkout-back')),
        '#submit' => array('commerce_checkout_form_submit'),
      );
    }

    $form['buttons']['continue'] = array(
      '#type' => 'submit',
      '#value' => $checkout_page->submit_value,
      '#attributes' => array('class' => array('checkout-continue')),
      '#submit' => array('commerce_checkout_form_submit'),
    );
  }

  return $form;
}

/**
 * Submit handler for the continue and back buttons of the checkout form.
 *
 * This function calls the validation function of each pane, followed by
 * the submit function if the validation succeeded. As long as one pane
 * fails validation, we then ask for the form to be rebuilt. Once all the panes
 * are happy, we move on to the next page.
 */
function commerce_checkout_form_submit($form, &$form_state) {
  global $user;

  $checkout_page = $form_state['values']['checkout_page'];

  // Load a fresh copy of the order stored in the form.
  $order = commerce_order_load($form_state['values']['order']->order_id);

  // Catch and clear already pushed messages.
  $previous_messages = drupal_get_messages();

  // Loop through the enabled checkout panes for the current page.
  $form_validate = TRUE;
  foreach (commerce_checkout_panes(array('enabled' => TRUE, 'page' => $checkout_page->page_id)) as $pane_id => $checkout_pane) {
    $validate = TRUE;

    // If the pane has defined a checkout form validate handler...
    if ($callback = commerce_checkout_pane_callback($checkout_pane, 'checkout_form_validate')) {
      // Give it a chance to process the submitted data.
      $validate = $callback($form, $form_state, $checkout_pane, $order);
    }

    // Catch and clear panes' messages.
    $pane_messages = drupal_get_messages();

    // Submit the pane if it validated.
    if ($validate && $callback = commerce_checkout_pane_callback($checkout_pane, 'checkout_form_submit')) {
      $callback($form, $form_state, $checkout_pane, $order);
    }

    // Generate status messages and theme them.
    $_SESSION['messages'] = array_merge_recursive($pane_messages, drupal_get_messages());
    $form_state['storage']['messages'][$pane_id] = theme('status_messages');

    // A failed pane makes the form fail.
    $form_validate &= $validate;
  }

  // Restore messages and form errors.
  $_SESSION['messages'] = $previous_messages;
  $form_errors = &drupal_static('form_set_error', array());
  $form_state['storage']['errors'] = $form_errors;
  $form_errors = array();

  // Save the updated order object.
  $order = commerce_order_save($order);

  if (!$form_validate) {
    // Rebuild the form if one pane failed validation.
    $form_state['rebuild'] = TRUE;
    return;
  }

  // Determine which button was used to submit the form.
  switch($form_state['values']['op']) {
    // Customer clicked the back button.
    case $form_state['values']['back']:
      // If there is a previous page...
      if ($previous_page = commerce_checkout_page_load($checkout_page->prev_page)) {
        // Move the form back to that page.
        if ($previous_page->prev_page) {
          $form_state['redirect'] = 'checkout/' . $order->order_id . '/' . $checkout_page->prev_page;
        }
        else {
          $form_state['redirect'] = 'checkout/' . $order->order_id;
        }

        // Otherwise update the order status for the checkout step.
        $order = commerce_order_status_update($order, 'checkout_' . $checkout_page->prev_page);
      }
      break;

    // Customer clicked the continue button.
    case $form_state['values']['continue']:
      // If there is another checkout page...
      if ($checkout_page->next_page) {
        // If it happens to be the complete page, process completion now.
        if ($checkout_page->next_page == 'complete') {
          // Or go ahead and complete the sale.
          commerce_checkout_complete($form_state, $order);
        }

        // Move the form forward to that page.
        $form_state['redirect'] = 'checkout/' . $order->order_id . '/' . $checkout_page->next_page;

        // Update the order status for the checkout step.
        $order = commerce_order_status_update($order, 'checkout_' . $checkout_page->next_page);
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

  drupal_set_message(t('Checkout of your current order has been canceled and may be resumed when you are ready.'));

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
