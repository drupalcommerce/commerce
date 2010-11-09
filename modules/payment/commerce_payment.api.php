<?php
// $Id$

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
 * Define payment methods.
 *
 * Available keys:
 *  - (mandatory) title: the title of the payment method, as displayed in
 *    administrative screens.
 *  - (mandatory) description: the description of the payment method.
 *  - (optional) default settings: an associative array of default settings
 *    for this payment method.
 *  - (optional) base: the base callback for this payment method. The default
 *    is the key of the payment method.
 *  - (optional) callbacks: an associative array of callback functions.
 *    The default for each key is $base . '_' . $callback. Available keys:
 *      - settings: a callback for the settings form
 *      - submit_form: the form displayed during the checkout
 *      - submit_form_validate: the validation callback for the pane form
 *      - submit_form_submit: the submit callback for the pane form
 *      - redirect_form: the form displayed during (optional) redirection
 *      - redirect_form_validate: the validation callback for the redirect form
 *      - redirect_form_submit: the validation callback for the redirect form
 */
function hook_commerce_payment_method_info() {
  $method['shiny_gateway'] = array(
    'title' => t('Shiny gateway'),
    'description' => t('Secure alliance payment via the Shiny gateway.'),
    'default settings' => array(
      'owner name' => 'Malcolm Reynolds',
      'class code' => '03-K64',
    ),
  );
  return $method;
}
