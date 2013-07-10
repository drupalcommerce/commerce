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

  $message = variable_get('commerce_checkout_completion_message', commerce_checkout_completion_message_default());

  $form['container'] = array(
    '#type' => 'container',
    '#access' => filter_access(filter_format_load($message['format'])),
  );
  $form['container']['commerce_checkout_completion_message'] = array(
    '#type' => 'text_format',
    '#title' => t('Checkout completion message'),
    '#default_value' => $message['value'],
    '#format' => $message['format'],
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

  // Load the completion message.
  $message = variable_get('commerce_checkout_completion_message', commerce_checkout_completion_message_default());

  // Perform translation.
  $message['value'] = commerce_i18n_string('commerce:checkout:complete:message', $message['value'], array('sanitize' => FALSE));

  // Perform token replacement.
  $message['value'] = token_replace($message['value'], array('commerce-order' => $order), array('clear' => TRUE));

  // Apply the proper text format.
  $message['value'] = check_markup($message['value'], $message['format']);

  $pane_form['message'] = array(
    '#markup' => '<div class="checkout-completion-message">' . $message['value'] . '</div>',
  );

  return $pane_form;
}
