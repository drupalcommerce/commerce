<?php

/**
 * @file
 * Checkout pane callback functions for the checkout module.
 */


/**
 * Checkout pane callback: returns a pane allowing the customer to review the
 * details of the order.
 */
function commerce_checkout_review_pane_checkout_form($form, &$form_state, $checkout_pane, $order) {
  $pane_form = array();

  // Otherwise add any enabled checkout panes that are visible on review.
  $pane_form['review'] = array(
    '#theme' => 'commerce_checkout_review',
    '#data' => array(),
  );

  // Loop through all the pages before the review page...
  foreach (commerce_checkout_pages() as $page_id => $checkout_page) {
    // Exit the loop once the review page is reached.
    if ($page_id == 'review') {
      break;
    }

    // Loop through all the panes on the current page specifying review...
    foreach (commerce_checkout_panes(array('page' => $page_id, 'enabled' => TRUE, 'review' => TRUE)) as $pane_id => $checkout_pane_local) {
      // If the pane has a valid review callback...
      if ($callback = commerce_checkout_pane_callback($checkout_pane_local, 'review')) {
        // Get the review data for this pane.
        $pane_data = $callback($form, $form_state, $checkout_pane_local, $order);

        // Only display the pane if there is data in the pane.
        if (!empty($pane_data)) {
          // Add a row for it in the review data.
          $pane_form['review']['#data'][$pane_id] = array(
            'title' => $checkout_pane_local['title'],
            'data' => $pane_data,
          );
        }
      }
    }
  }

  return $pane_form;
}

/**
 * Checkout pane callback: returns the settings form elements for the checkout
 * completion message.
 */
function commerce_checkout_completion_message_pane_settings_form($checkout_pane) {
  $form = array();

  // Find our default message and text format values.
  if (!is_null(variable_get('commerce_checkout_completion_message', NULL))) {
    $data = variable_get('commerce_checkout_completion_message', commerce_checkout_completion_message_default());

    $message = $data['value'];
    $text_format = $data['format'];
  }
  else {
    $message = commerce_checkout_completion_message_default();

    if (filter_format_load('filtered_html')) {
      $text_format = 'filtered_html';
    }
    else {
      $text_format = filter_fallback_format();
    }
  }

  $form['commerce_checkout_completion_message_override'] = array(
    '#type' => 'checkbox',
    '#title' => t('Override the default checkout completion message. This may interfere with translation on multilingual sites.'),
    '#default_value' => variable_get('commerce_checkout_completion_message_override', FALSE),
    '#access' => filter_access(filter_format_load($text_format)),
  );

  // Use a container to hide completion message settings unless overriden.
  $form['container'] = array(
    '#type' => 'container',
    '#access' => filter_access(filter_format_load($text_format)),
    '#states' => array(
      'visible' => array(
        ':input[name="commerce_checkout_completion_message_override"]' => array('checked' => TRUE),
      ),
    ),
  );

  $form['container']['commerce_checkout_completion_message'] = array(
    '#type' => 'text_format',
    '#title' => t('Checkout completion message'),
    '#default_value' => $message,
    '#format' => $text_format,
  );

  $var_info = array(
    'site' => array(
      'type' => 'site',
      'label' => t('Site information'),
      'description' => t('Site-wide settings and other global information.'),
    ),
    'commerce_order' => array(
      'label' => t('Order'),
      'type' => 'commerce_order',
    ),
  );

  $form['container']['commerce_checkout_completion_message_help'] = RulesTokenEvaluator::help($var_info);

  return $form;
}

/**
 * Checkout pane callback: presents a completion message on the complete page.
 */
function commerce_checkout_completion_message_pane_checkout_form($form, &$form_state, $checkout_pane, $order) {
  $pane_form = array();

  // Load the appropriate default completion message.
  if (variable_get('commerce_checkout_completion_message_override', FALSE)) {
    $data = variable_get('commerce_checkout_completion_message', commerce_checkout_completion_message_default());

    $message = $data['value'];
    $text_format = $data['format'];
  }
  else {
    $message = commerce_checkout_completion_message_default();

    if (filter_format_load('filtered_html')) {
      $text_format = 'filtered_html';
    }
    else {
      $text_format = filter_fallback_format();
    }
  }

  // Perform token replacement.
  $message = token_replace($message, array('commerce-order' => $order), array('clear' => TRUE));

  // Apply the proper text format.
  $message = check_markup($message, $text_format);

  $pane_form['message'] = array(
    '#markup' => '<div class="checkout-completion-message">' . $message . '</div>',
  );

  return $pane_form;
}
