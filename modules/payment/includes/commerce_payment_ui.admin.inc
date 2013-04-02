<?php

/**
 * @file
 * Administrative page callbacks for the Payment UI module.
 */


/**
 * Displays the payment transaction View in an order's payment tab.
 */
function commerce_payment_ui_order_tab($order) {
  // Display the payments View.
  return commerce_embed_view('commerce_payment_order', 'defaults', array($order->order_id));
}

/**
 * Builds the payment settings page using the Rules UI overview table filtered
 *   to display payment method rules.
 */
function commerce_payment_ui_admin_page() {
  RulesPluginUI::$basePath = 'admin/commerce/config/payment-methods';
  $options = array('show plugin' => FALSE);

  // Add the table for enabled payment method rules.
  $content['enabled']['title']['#markup'] = '<h3>' . t('Enabled payment method rules') . '</h3>';

  $conditions = array('event' => 'commerce_payment_methods', 'plugin' => 'reaction rule', 'active' => TRUE);
  $content['enabled']['rules'] = RulesPluginUI::overviewTable($conditions, $options);
  $content['enabled']['rules']['#empty'] = t('There are no active payment methods.');

  // Add the table for disabled payment method rules.
  $content['disabled']['title']['#markup'] = '<h3>' . t('Disabled payment method rules') . '</h3>';

  $conditions['active'] = FALSE;
  $content['disabled']['rules'] = RulesPluginUI::overviewTable($conditions, $options);
  $content['disabled']['rules']['#empty'] = t('There are no disabled payment methods.');

  // Update the descriptions of items in the tables to show whether or not
  // they're enabled for use on the checkout form and payment terminal form.
  foreach (array('enabled', 'disabled') as $status) {
    foreach ($content[$status]['rules']['#rows'] as &$row) {
      // We don't have access to the actual machine-name of the rule each row
      // represents, so we must extract the name from the machine-name markup.
      $rule_name_markup = $row[0]['data']['description']['settings']['machine_name']['#markup'];
      $rule_name = drupal_substr($rule_name_markup, drupal_strlen(t('Machine name') . ': '));

      // If we were able to extract a valid name from the markup...
      if ($rule = rules_config_load($rule_name)) {
        // Loop over the actions to find the first enabled payment method.
        foreach ($rule->actions() as $action) {
          // Parse the action name into a payment method ID.
          if (strpos($action->getElementName(), 'commerce_payment_enable_') === 0) {
            $method_id = drupal_substr($action->getElementName(), 24);

            // Load the payment method instance and determine availability.
            $payment_method = commerce_payment_method_load($method_id);

            // Create an items array that describes where the payment method
            // will be available for use.
            $items = array();

            if (empty($payment_method['checkout'])) {
              $items[] = t('Not available on the checkout form');
            }
            else {
              $items[] = t('Available on the checkout form');
            }

            if (empty($payment_method['terminal'])) {
              $items[] = t('Not available on the order payment terminal');
            }
            else {
              $items[] = t('Available on the order payment terminal');
            }

            $row[0]['data']['availability'] = array(
              '#theme' => 'item_list',
              '#items' => $items,
            );

            break;
          }
        }
      }
    }
  }

  // Store the function name in the content array to make it easy to alter the
  // contents of this page.
  $content['#page_callback'] = 'commerce_payment_ui_admin_page';

  return $content;
}

/**
 * Displays the full details of a payment transaction.
 */
function commerce_payment_ui_payment_transaction_view($order, $transaction, $view_mode) {
  return entity_view('commerce_payment_transaction', array($transaction->transaction_id => $transaction), $view_mode, NULL, TRUE);
}

/**
 * Form callback wrapper: confirmation form for deleting a payment transaction.
 *
 * @param $order
 *   The order object containing the transaction being deleted by the form.
 * @param $transaction
 *   The actual payment transaction that will be deleted.
 *
 * @see commerce_payment_payment_transaction_delete_form()
 */
function commerce_payment_ui_payment_transaction_delete_form_wrapper($order, $transaction) {
  // Include the forms file from the Payment module.
  module_load_include('inc', 'commerce_payment', 'includes/commerce_payment.forms');
  return drupal_get_form('commerce_payment_ui_payment_transaction_delete_form', $order, $transaction);
}
