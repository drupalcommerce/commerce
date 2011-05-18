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
function hook_commerce_payment_methods(&$order) {
  // No example. See commerce_payment_enable_method() for a guide to what you
  // must add to the order's data array.
}

/**
 * Allows you to prepare payment transaction data before it is saved.
 *
 * @param $transaction
 *   The payment transaction object to be saved.
 *
 * @see rules_invoke_all()
 */
function hook_commerce_payment_transaction_presave(&$transaction) {
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
 */
function hook_commerce_payment_order_paid_in_full($transaction) {
  // No example.
}
