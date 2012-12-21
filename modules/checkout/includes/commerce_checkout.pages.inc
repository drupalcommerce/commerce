<?php

/**
 * @file
 * The page and form callbacks for use in the checkout form.
 */


/**
 * Redirects invalid checkout attempts or displays the checkout form if valid.
 */
function commerce_checkout_router($order, $checkout_page = NULL) {
  $checkout_pages = commerce_checkout_pages();

  // If no checkout page is specified, default to the first one.
  if (empty($checkout_page)) {
    $checkout_page = reset($checkout_pages);
  }

  // If the user does not have access to checkout the order, return a 404. We
  // could return a 403, but then the user would know they've identified a
  // potentially valid checkout URL.
  if (!commerce_checkout_access($order)) {
    return MENU_NOT_FOUND;
  }

  // If the user is attempting to access an inaccessible page for their order,
  // redirect them to the proper page.
  if (!commerce_checkout_page_access($checkout_page, $order)) {
    $target_uri = commerce_checkout_order_uri($order);

    // Only redirect if the target page is different from the page the user was
    // trying to access. Otherwise give a 403 error.
    if (!empty($target_uri) && $target_uri !== $_GET['q']) {
      drupal_goto($target_uri);
    }
    else {
      return MENU_ACCESS_DENIED;
    }
  }

  // If there are no line items on the order, redirect away.
  $wrapper = entity_metadata_wrapper('commerce_order', $order);

  if (commerce_line_items_quantity($wrapper->commerce_line_items, commerce_product_line_item_types()) == 0) {
    drupal_goto('<front>');
  }

  // Prior to displaying the checkout form, allow other modules to route the
  // checkout form.
  module_invoke_all('commerce_checkout_router', $order, $checkout_page);

  // Update the page title if specified.
  if (!empty($checkout_page['title'])) {
    drupal_set_title($checkout_page['title']);
  }

  return drupal_get_form('commerce_checkout_form_' . $checkout_page['page_id'], $order, $checkout_page);
}

/**
 * Builds the checkout form for the given order on the specified checkout page.
 *
 * @param $order
 *   The fully loaded order object being checked out.
 * @param $checkout_page
 *   The checkout page object representing the current step in checkout.
 */
function commerce_checkout_form($form, &$form_state, $order, $checkout_page) {
  global $user;

  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_checkout') .'/theme/commerce_checkout.base.css';
  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_checkout') .'/theme/commerce_checkout.theme.css';
  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_checkout') . '/commerce_checkout.js';

  $form_state['order'] = $order;
  $form_state['checkout_page'] = $checkout_page;
  $form_state['account'] = clone($user);

  // Add any help text that has been defined for this checkout page.
  $help = filter_xss($checkout_page['help']);

  if (!empty($help)) {
    $form['help'] = array(
      '#markup' => theme('commerce_checkout_help', array('help' => $help)),
    );
  }

  // Restore form errors.
  if (!empty($form_state['storage']['errors'])) {
    $form_errors = &drupal_static('form_set_error', array());
    $form_errors = $form_state['storage']['errors'];
  }

  // Catch and clear already pushed messages.
  $previous_messages = drupal_get_messages();
  $show_errors_message = FALSE;
  $visible_panes = 0;

  // Add any enabled checkout panes for this page to the form.
  foreach (commerce_checkout_panes(array('enabled' => TRUE, 'page' => $checkout_page['page_id'])) as $pane_id => $checkout_pane) {
    if ($callback = commerce_checkout_pane_callback($checkout_pane, 'checkout_form')) {
      // Generate the pane form.
      $pane_form = $callback($form, $form_state, $checkout_pane, $order);

      // Combine the messages that were created during this pane's validation or
      // submit process with any that were created during the pane generation
      // and merge them into the session's current messages array.
      if (!empty($form_state['storage']['messages'][$pane_id])) {
        $_SESSION['messages'] = array_merge_recursive($form_state['storage']['messages'][$pane_id], drupal_get_messages());
      }

      // If there are messages in the session right now for this pane, theme
      // them into the form right above the pane itself.
      if (!empty($_SESSION['messages'])) {
        // If there are error messages and this is not the first pane on the
        // form, then indicate we need to show an error message at the top of
        // the page.
        if ($visible_panes > 0 && !empty($_SESSION['messages']['error'])) {
          $show_errors_message = TRUE;
        }

        // Rendering status messages clears the session of messages, so they
        // will not be visible if the user is redirected. We can at least not
        // render here when we detect the global variable added by Rules to
        // handle redirects, though modules implementing redirects will still
        // encounter the same problem of "lost" messages.
        if (!isset($GLOBALS['_rules_action_drupal_goto_do'])){
          $form_state['storage']['themed_messages'][$pane_id] = theme('status_messages');

          $pane_form[$pane_id . '_messages'] = array(
            '#markup' =>  $form_state['storage']['themed_messages'][$pane_id],
            '#weight' => -50,
          );
        }
      }

      // Create a fieldset for the pane and add the form data defined in the
      // pane's form callback.
      if ($pane_form) {
        $form[$pane_id] = $pane_form + array(
          '#type' => $checkout_pane['fieldset'] ? 'fieldset' : 'container',
          '#title' => check_plain($checkout_pane['title']),
          '#collapsible' => $checkout_pane['collapsible'],
          '#collapsed' => $checkout_pane['collapsed'],
          '#attributes' => array('class' => array($pane_id)),
          '#tree' => TRUE,
        );

        $visible_panes++;
      }
    }
  }

  // Restore general messages to the current session's messages array.
  $_SESSION['messages'] = array_merge_recursive(array_filter($previous_messages), drupal_get_messages());

  // If there are errors on the form, add a message to the top of the page.
  if ($show_errors_message) {
    $form['error_message'] = array(
      '#markup' => theme('commerce_checkout_errors_message', array('label' => t('Errors on form'), 'message' => t('There are errors on the page. Please correct them and resubmit the form.'))),
      '#weight' => -10,
    );
  }

  // Only add buttons to the form if the checkout page hasn't disabled them.
  if ($checkout_page['buttons']) {
    $form['buttons'] = array(
      '#type' => 'fieldset',
      '#attributes' => array('class' => array('checkout-buttons')),
    );
    $form['buttons']['continue'] = array(
      '#type' => 'submit',
      '#value' => $checkout_page['submit_value'],
      '#attributes' => array('class' => array('checkout-continue')),
      '#suffix' => '<span class="checkout-processing element-invisible"></span>',
      '#validate' => array('commerce_checkout_form_validate'),
      '#submit' => array('commerce_checkout_form_submit'),
    );

    // Add the cancel or back button where appropriate. We define button level
    // submit handlers because we're using hook_forms() to use this form builder
    // function and to avoid issues if other modules implement button level submit
    // handlers on these or custom checkout buttons.
    $button_operator = '<span class="button-operator">' . t('or') . '</span>';

    if (!$checkout_page['prev_page'] && !empty($checkout_page['back_value'])) {
      // Add an empty "Back" button value to avoid submission errors.
      $form['buttons']['back'] = array(
        '#type' => 'value',
        '#value' => '',
      );

      // Store the cancel redirect in the form so modules can modify it easily.
      $form_state['cancel_redirect'] = '<front>';

      $form['buttons']['cancel'] = array(
        '#type' => 'submit',
        '#value' => t('Cancel'),
        '#attributes' => array('class' => array('checkout-cancel')),
        '#submit' => array('commerce_checkout_form_cancel_submit'),
        '#limit_validation_errors' => array(),
        '#prefix' => $button_operator,
      );
    }
    elseif ($checkout_page['prev_page'] && !empty($checkout_page['back_value'])) {
      $form['buttons']['back'] = array(
        '#type' => 'submit',
        '#value' => $checkout_page['back_value'],
        '#attributes' => array('class' => array('checkout-back')),
        '#submit' => array('commerce_checkout_form_back_submit'),
        '#limit_validation_errors' => array(),
        '#prefix' => $button_operator,
      );
    }
  }

  // Remove form level validate and submit handlers.
  $form['#validate'] = array();
  $form['#submit'] = array();

  return $form;
}

/**
 * Validate handler for the continue button of the checkout form.
 *
 * This function calls the validation function of each pane, followed by
 * the submit function if the validation succeeded. As long as one pane
 * fails validation, we then ask for the form to be rebuilt. Once all the panes
 * are happy, we move on to the next page.
 */
function commerce_checkout_form_validate($form, &$form_state) {
  global $user;

  $checkout_page = $form_state['checkout_page'];

  // Load a fresh copy of the order stored in the form.
  $order = commerce_order_load($form_state['order']->order_id);

  // Catch and clear already pushed messages.
  $previous_messages = drupal_get_messages();

  // Load any pre-existing validation errors for the elements.
  $errors = array();

  foreach ((array) form_get_errors() as $element_path => $error) {
    list($pane_id, ) = explode('][', $element_path, 2);
    $errors[$pane_id][$element_path] = $error;
  }

  // Loop through the enabled checkout panes for the current page.
  $form_validate = TRUE;
  foreach (commerce_checkout_panes(array('enabled' => TRUE, 'page' => $checkout_page['page_id'])) as $pane_id => $checkout_pane) {
    $validate = TRUE;

    // If any element in the pane failed validation, we mark the pane as
    // unvalidated and replay the validation messages on top of it.
    if (!empty($errors[$pane_id])) {
      $validate = FALSE;

      foreach ($errors[$pane_id] as $element_path => $message) {
        if ($message) {
          drupal_set_message($message, 'error');
        }
      }

      if (isset($previous_messages['error'])) {
        $previous_messages['error'] = array_diff($previous_messages['error'], $errors[$pane_id]);
      }
    }

    // If the pane has defined a checkout form validate handler...
    if ($callback = commerce_checkout_pane_callback($checkout_pane, 'checkout_form_validate')) {
      // Give it a chance to process the submitted data.
      $validate &= $callback($form, $form_state, $checkout_pane, $order);
    }

    // Catch and clear panes' messages.
    $pane_messages = drupal_get_messages();

    // Submit the pane if it validated.
    if ($validate && $callback = commerce_checkout_pane_callback($checkout_pane, 'checkout_form_submit')) {
      $callback($form, $form_state, $checkout_pane, $order);
    }

    // Generate status messages.
    $form_state['storage']['messages'][$pane_id] = array_merge_recursive($pane_messages, drupal_get_messages());

    // A failed pane makes the form fail.
    $form_validate &= $validate;
  }

  // Restore messages and form errors.
  $_SESSION['messages'] = array_merge_recursive(array_filter($previous_messages), drupal_get_messages());
  $form_errors = &drupal_static('form_set_error', array());
  $form_state['storage']['errors'] = $form_errors;
  $form_errors = array();

  // Save the updated order object.
  commerce_order_save($order);

  // If a pane failed validation or the form state has otherwise been altered to
  // initiate a rebuild, return without moving to the next checkout page.
  if (!$form_validate || $form_state['rebuild']) {
    $form_state['rebuild'] = TRUE;
  }
}

/**
 * Submit handler for the continue button of the checkout form.
 */
function commerce_checkout_form_submit($form, &$form_state) {
  $checkout_page = $form_state['checkout_page'];

  // Load a fresh copy of the order stored in the form.
  $order = commerce_order_load($form_state['order']->order_id);

  // If we are going to redirect with checkout pane messages stored in the form
  // state, they will not be displayed on a subsequent form build like normal.
  // Move them out of the form state messages array and into the current
  // session's general message array instead.
  if (!empty($form_state['storage']['messages'])) {
    foreach ($form_state['storage']['messages'] as $pane_id => $pane_messages) {
      $_SESSION['messages'] = array_merge_recursive($_SESSION['messages'], $pane_messages);
    }
  }

  // If the form was submitted via the continue button...
  if (end($form_state['triggering_element']['#array_parents']) == 'continue') {
    // If there is another checkout page...
    if ($checkout_page['next_page']) {
      // Update the order status to reflect the next checkout page.
      $order = commerce_order_status_update($order, 'checkout_' . $checkout_page['next_page'], FALSE, NULL, t('Customer continued to the next checkout page via a submit button.'));

      // If it happens to be the complete page, process completion now.
      if ($checkout_page['next_page'] == 'complete') {
        commerce_checkout_complete($order);
      }

      // Redirect to the next checkout page.
      $form_state['redirect'] = 'checkout/' . $order->order_id . '/' . $checkout_page['next_page'];
    }
  }
}

/**
 * Special submit handler for the back button to avoid processing orders.
 */
function commerce_checkout_form_back_submit($form, &$form_state) {
  // If there is a previous page...
  if ($previous_page = commerce_checkout_page_load($form_state['checkout_page']['prev_page'])) {
    $order = $form_state['order'];

    // Move the form back to that page.
    if ($previous_page['prev_page']) {
      $form_state['redirect'] = 'checkout/' . $order->order_id . '/' . $previous_page['page_id'];
    }
    else {
      $form_state['redirect'] = 'checkout/' . $order->order_id;
    }

    // Update the order status for the checkout step.
    $form_state['order'] = commerce_order_status_update($order, 'checkout_' . $previous_page['page_id'], FALSE, NULL, t('Customer returned to the previous checkout page via a submit button.'));
  }
}

/**
 * Special submit handler for the cancel button to avoid processing orders.
 */
function commerce_checkout_form_cancel_submit($form, &$form_state) {
  $order = commerce_order_load($form_state['order']->order_id);

  // Set the order status back to the first checkout page's status.
  $order_state = commerce_order_state_load('checkout');
  $form_state['order'] = commerce_order_status_update($order, $order_state['default_status'], TRUE);

  // Skip saving in the status update and manually save here to force a save
  // even when the status doesn't actually change.
  if (variable_get('commerce_order_auto_revision', TRUE)) {
    $form_state['order']->revision = TRUE;
    $form_state['order']->log = t('Customer manually canceled the checkout process.');
  }

  commerce_order_save($form_state['order']);

  drupal_set_message(t('Checkout of your current order has been canceled and may be resumed when you are ready.'));

  $form_state['redirect'] = $form_state['cancel_redirect'];
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
