<?php

/**
 * @file
 * Hooks provided by the Payment module.
 */


/**
 * Defines rows for use in payment totals area handlers on Views.
 *
 * The payment totals area handler totals the amount of payments received by
 * currency for all the payment transactions in a View. The array of totals are
 * is used to build a table containing rows for each of the totals and/or the
 * remaining balance of the order by default. Other modules may use this hook to
 * add additional rows to the table.
 *
 * @param $totals
 *   An array of payment totals whose keys are currency codes and values are the
 *   total amount paid in each currency.
 * @param $order
 *   If available, the order object to which the payments apply.
 *
 * @return
 *   An array of table row data as expected by theme_table(). Row arrays may
 *   contain an additional weight key with the value being an integer used to
 *   sort the rows prior to display.
 *
 * @see commerce_payment_commerce_payment_totals_rows()
 * @see commerce_payment_commerce_payment_totals_row_info()
 * @see theme_table()
 */
function hook_commerce_payment_totals_row_info($totals, $order) {
  $rows = array();

  if (count($totals) <= 1) {
    // Add a row for the remaining balance on the order.
    if ($order) {
      $balance = commerce_payment_order_balance($order, $totals);

      $rows[] = array(
        'data' => array(
          array('data' => t('Order balance'), 'class' => array('label')),
          array('data' => commerce_currency_format($balance['amount'], $balance['currency_code']), 'class' => array('balance')),
        ),
        'class' => array('order-balance'),
        'weight' => 10,
      );
    }
  }

  return $rows;
}

/**
 * Allows you to alter payment totals rows.
 *
 * @param $rows
 *   Array of payment totals rows exposed by
 *   hook_commerce_payment_totals_row_info() implementations.
 * @param $totals
 *   An array of payment totals whose keys are currency codes and values are the
 *   total amount paid in each currency.
 * @param $order
 *   If available, the order object to which the payments apply.
 *
 * @see hook_commerce_payment_totals_row_info()
 */
function hook_commerce_payment_totals_row_info_alter(&$rows, $totals, $order) {
  // Alter the weight of order balance rows to appear first.
  foreach ($rows as $key => &$row) {
    if (in_array('order-balance', $row['class'])) {
      $row['weight'] = -10;
    }
  }
}

/**
 * @defgroup commerce_payment_method Payment Method API
 * @{
 * API for integrating payment methods into the Drupal Commerce framework.
 */

/**
 * Define payment methods available to the Commerce Payment framework.
 *
 * The Payment module uses this hook to gather information on payment methods
 * defined by enabled modules.
 *
 * Payment methods depend on a variety of callbacks that are used to configure
 * the payment methods via Rules actions, integrate the payment method with the
 * checkout form, handle display and manipulation of transactions after the fact,
 * and allow for administrative payment entering after checkout. The Payment
 * module ships with payment method modules useful for testing and learning, but
 * all integrations with real payment providers will be provided as contributed
 * modules. The Payment module will include helper code designed to make different
 * types of payment services easier to integrate as mentioned above.
 *
 * Each payment method is an associative array with the following keys:
 *  - method_id: string identifying the payment method (must be a valid PHP
 *    identifier).
 *  - base (optional): string used as the base for callback names, each of which
 *    will be defaulted to [base]_[callback] unless explicitly set; defaults
 *    to the method_id if not set.
 *  - title: the translatable full title of the payment method, used in
 *    administrative interfaces.
 *  - display_title (optional): the title to display on forms where the payment
 *    method is selected and may include HTML for methods that require images and
 *    special descriptions; defaults to the title.
 *  - short_title (optional): an abbreviated title that may simply include the
 *    payment provider’s name as it makes sense to the customer (i.e. you would
 *    display PayPal, not PayPal WPS to a customer); defaults to the title.
 *  - description (optional): a translatable description of the payment method,
 *    including the nature of the payment and the payment gateway that actually
 *    captures the payment.
 *  - active (optional): TRUE of FALSE indicating whether or not the default
 *    payment method rule configuration for this payment method should be
 *    enabled by default; defaults to FALSE.
 *  - terminal (optional): TRUE or FALSE indicating whether or not payments can
 *    be processed via this payment method through the administrative payment
 *    terminal on an order’s Payment tab; defaults to TRUE.
 *  - offsite (optional): TRUE or FALSE indicating whether or not the customer
 *    must be redirected offsite to put in their payment information; used
 *    specifically by the off-site payment redirect checkout pane; defaults to
 *    FALSE.
 *  - offsite_autoredirect (optional): TRUE or FALSE indicating whether or not
 *    the customer should be automatically redirected to an offsite payment site
 *    on the payment step of checkout; defaults to FALSE.
 *  - callbacks (optional): an array of callback function names for the various
 *    types of callback required for all the payment method operations, arguments
 *    per callback in parentheses:
 *      - settings_form: the name of the CALLBACK_commerce_payment_method_settings_form()
 *        of the payment method.
 *      - submit_form: the name of the CALLBACK_commerce_payment_method_submit_form()
 *        of the payment method.
 *      - submit_form_validate: the name of the CALLBACK_commerce_payment_method_submit_form_validate()
 *        of the payment method.
 *      - submit_form_submit: the name of the CALLBACK_commerce_payment_method_submit_form_submit()
 *        of the payment method.
 *      - redirect_form: the name of the CALLBACK_commerce_payment_method_redirect_form()
 *        of the payment method.
 *      - redirect_form_validate: the name of the CALLBACK_commerce_payment_method_redirect_form_validate()
 *        of the payment method.
 *      - redirect_form_submit: the name of the CALLBACK_commerce_payment_method_redirect_form_submit()
 *        of the payment method.
 *  - file (optional): the filepath of an include file relative to the method's
 *    module containing the callback functions for this method, allowing modules
 *    to store payment method code in include files that only get loaded when
 *    necessary (like the menu item file property).
 *
 * @return
 *   An array of payment methods, using the format defined above.
 */
function hook_commerce_payment_method_info() {
  $payment_methods['paypal_wps'] = array(
    'base' => 'commerce_paypal_wps',
    'title' => t('PayPal WPS'),
    'short_title' => t('PayPal'),
    'description' => t('PayPal Website Payments Standard'),
    'terminal' => FALSE,
    'offsite' => TRUE,
    'offsite_autoredirect' => TRUE,
  );
  return $payment_methods;
}

/**
 * Alter payment methods defined by other modules.
 *
 * This function is run before default values have been merged into the payment
 * methods.
 *
 * @param $payment_methods
 *   An array of payment methods, keyed by method id.
 */
function hook_commerce_payment_method_info_alter(&$payment_methods) {
  // No example.
}

/**
 * Payment method callback; return the settings form for a payment method.
 *
 * @param $settings
 *   An array of the current settings.
 * @return
 *   A form snippet.
 */
function CALLBACK_commerce_payment_method_settings_form($settings = NULL) {
  // No example.
}

/**
 * Payment method callback; generation callback for the payment submission form.
 *
 * @param $payment_method
 *   An array of the current settings.
 * @param $pane_values
 *   The current values of the pane.
 * @param $checkout_pane
 *   The checkout pane array. The checkout pane will be NULL if the payment is
 *   being added through the administration form.
 * @param $order
 *   The order object.
 * @return
 *   A form snippet for the checkout pane.
 */
function CALLBACK_commerce_payment_method_submit_form($payment_method, $pane_values, $checkout_pane, $order) {
  // No example.
}

/**
 * Payment method callback; validate callback for the payment submission form.
 *
 * @param $payment_method
 *   An array of the current settings.
 * @param $pane_form
 *   The pane form.
 * @param $pane_values
 *   The current values of the pane.
 * @param $order
 *   The order object.
 * @param $form_parents
 *   The identifier of the base element of the payment pane.
 */
function CALLBACK_commerce_payment_method_submit_form_validate($payment_method, $pane_form, $pane_values, $order, $form_parents = array()) {
  // No example.
}

/**
 * Payment method callback; validate callback for the payment submission form.
 *
 * @param $payment_method
 *   An array of the current settings.
 * @param $pane_form
 *   The pane form.
 * @param $pane_values
 *   The current values of the pane.
 * @param $order
 *   The order object.
 * @param $charge
 *   A price structure that needs to be charged.
 */
function CALLBACK_commerce_payment_method_submit_form_submit($payment_method, $pane_form, $pane_values, $order, $charge) {
  // No example.
}

/**
 * Payment method callback; generation callback for the payment redirect form.
 *
 * Returns form elements that should be submitted to the redirected payment
 * service; because of the array merge that happens upon return, the service’s
 * URL that should receive the POST variables should be set in the #action
 * property of the returned form array.
 */
function CALLBACK_commerce_payment_method_redirect_form($form, &$form_state, $order, $payment_method) {
  // No example.
}

/**
 * Payment method callback; cancellation callback for the redirected payments.
 *
 * If the customer cancels payment or payment fails at the redirected payment
 * service, the custom will be sent back to the previous checkout page upon
 * return from the payment service. Before the redirect occurs, the payment
 * method module has the opportunity to take additional action by implementing
 * this callback. Note that updating the order status and performing the
 * redirect are handled by the Payment module, so these two operations should
 * not be duplicated by the payment method module.
 */
function CALLBACK_commerce_payment_method_redirect_form_back($form, &$form_state, $order, $payment_method) {
  // No example.
}

/**
 * Payment method callback; validation callback for redirected payments.
 *
 * Upon return from a redirected payment service, this callback provides the
 * payment method an opportunity to validate any returned data before proceeding
 * to checkout completion; should return TRUE or FALSE indicating whether or not
 * the customer should proceed to checkout completion or go back a step in the
 * checkout process from the payment page.
 *
 * @param $order
 *   The order object.
 * @param $payment_method
 *   The payment method array.
 * @return
 *   TRUE if the customer should proceed to checkout completion or FALSE to go
 *   back one step in the checkout process.
 */
function CALLBACK_commerce_payment_method_redirect_form_validate($order, $payment_method) {
  // No example.
}

/**
 * Payment method callback; submission callback for redirected payments.
 *
 * Upon return from a redirected payment service, this callback provides the
 * payment method an opportunity to perform any submission functions necessary
 * before the customer is redirected to checkout completion.
 *
 * @param $order
 *   The order object.
 * @param $payment_method
 *   The payment method array.
 */
function CALLBACK_commerce_payment_method_redirect_form_submit($order, $payment_method) {
  // No example.
}

/**
 * @} End of "ingroup commerce_payment_method"
 */

/**
 * Populates an order's data array with payment methods available in checkout.
 *
 * The Payment module primarily depends on Rules to populate the payment method
 * checkout pane with options using an action that enables a particular payment
 * method for use. The action adds payment method instance information to the
 * order's data array that is used by the pane form to add options to the radio
 * select element. This hook may be used to do the same thing, meaning it should
 * not return any information but update the order object's data array just like
 * the payment method enabling action.
 *
 * It should be noted that using Rules is the preferred method, as this hook is
 * being made available secondarily through the use of rules_invoke_all().
 *
 * @param $order
 *   The order object represented on the checkout form.
 *
 * @see commerce_payment_pane_checkout_form()
 * @see commerce_payment_enable_method()
 * @see rules_invoke_all()
 */
function hook_commerce_payment_methods($order) {
  // No example. See commerce_payment_enable_method() for a guide to what you
  // must add to the order's data array.
}

/**
 * Allows modules to specify a uri for a payment transaction.
 *
 * When this hook is invoked, the first returned uri will be used for the
 * payment transaction. Thus to override the default value provided by the
 * Payment UI module, you would need to adjust the order of hook invocation via
 * hook_module_implements_alter() or your module weight values.
 *
 * @param $transaction
 *   The payment transaction object whose uri is being determined.
 *
 * @return
 *  The uri elements of an entity as expected to be returned by entity_uri()
 *  matching the signature of url().
 *
 * @see commerce_payment_transaction_uri()
 * @see hook_module_implements_alter()
 * @see entity_uri()
 * @see url()
 */
function hook_commerce_payment_transaction_uri($transaction) {
  // No example.
}

/**
 * Allows you to prepare payment transaction data before it is saved.
 *
 * @param $transaction
 *   The payment transaction object to be saved.
 *
 * @see rules_invoke_all()
 */
function hook_commerce_payment_transaction_presave($transaction) {
  // No example.
}

/**
 * Allows you to respond when an order is first considered paid in full.
 *
 * The unpaid balance of an order is calculated by subtracting the total amount
 * of all successful payment transactions referencing the order from the order's
 * total. If the balance is less than or equal to zero, it is considered paid in
 * full. The first time an order's balance falls to or below zero, this hook is
 * invoked to allow modules to perform special maintenance as necessary. This
 * hook is invoked after the "When an order is first paid in full" Rules event.
 *
 * Through the administration of payment transactions, it is possible for an
 * order's balance to go above zero. It is then possible for the balance to go
 * back down to or below zero. In either of these cases, no further action is
 * taken. At present, this hook and Rules event are only meant to be invoked the
 * first time an order is considered paid in full.
 *
 * @param $order
 *   The order that was just paid in full.
 * @param $transaction
 *   The successful transaction that just caused the order's balance to drop to
 *   or below zero.
 */
function hook_commerce_payment_order_paid_in_full($order, $transaction) {
  // No example.
}

/**
 * Defines payment transaction statuses.
 *
 * A payment transaction represents any attempted payment via a payment method
 * and includes a variety of properties used for tracking the amount, outcome,
 * and parameters of the transaction. One of these is the transaction’s local
 * status, not to be confused with its remote_status that stores the exact
 * status of the transaction at the payment provider.
 *
 * Transaction statuses are used to visually represent in the order’s Payment
 * tab whether or not the payment should be considered a success (meaning money
 * was actually collected) and are accordingly considered when calculating the
 * remaining balance of an order. Because payment statuses are critical
 * functionality components, the default statuses listed below are actually
 * defined in the function used to load all payment transaction statuses:
 * - Pending: further action is required to determine if the attempted payment
 *   was a success or failure; used for payment methods like e-checks that may
 *   require time to clear or credit card authorizations that haven’t been
 *   captured yet
 * - Success: the transaction is complete and a success, meaning the amount of
 *   this transaction will be subtracted from the order total to determine the
 *   outstanding balance on the order
 * - Failure: the attempted transaction failed and will not be counted in totals
 *
 * Additional statuses may be defined via this hook, but there is no general
 * alteration. The properties of the default statuses may be altered as long as
 * the actual status key is preserved via the use of array merging.
 *
 * The payment transaction status array structure is as follows:
 * - status: machine-name identifying the payment transaction status using
 *   lowercase alphanumeric characters, -, and _
 * - title: the translatable title of the transaction status, used in
 *   administrative interfaces
 * - icon: the path to the status’s icon relative to the Drupal root directory
 * - total: TRUE or FALSE indicating whether or not transactions in this
 *   status should be totaled to determine the balance of an order
 *
 * @return
 *   An array of payment transaction status arrays keyed by status.
 *
 * @see commerce_payment_transaction_statuses()
 */
function hook_commerce_payment_transaction_status_info() {
  $statuses = array();

  // COMMERCE_PAYMENT_STATUS_SUCCESS is a constant defined in the Payment module.
  $statuses[COMMERCE_PAYMENT_STATUS_SUCCESS] = array(
    'status' => COMMERCE_PAYMENT_STATUS_SUCCESS,
    'title' => t('Success'),
    'icon' => drupal_get_path('module', 'commerce_payment') . '/theme/icon-success.png',
    'total' => TRUE,
  );

  return $statuses;
}
