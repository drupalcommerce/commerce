<?php
// $Id$

/**
 * Defines a handler area that provides payment totals, the order balance, and
 *   a form to add new payments.
 */
class commerce_payment_handler_area_totals extends views_handler_area {
  function init(&$view, $options) {
    parent::init($view, $options);

    $this->additional_fields['amount'] = 'amount';
    $this->additional_fields['currency_code'] = 'currency_code';
    $this->additional_fields['status'] = 'status';
  }

  function render($empty = FALSE) {
    if (!$empty) {
      // Calculate a total of successful payments for each currency.
      $totals = array();

      foreach ($this->view->result as $result) {
        if ($result->commerce_payment_transaction_status == 'success') {
          if (isset($totals[$result->commerce_payment_transaction_currency_code])) {
            $totals[$result->commerce_payment_transaction_currency_code] += $result->commerce_payment_transaction_amount;
          }
          else {
            $totals[$result->commerce_payment_transaction_currency_code] = $result->commerce_payment_transaction_amount;
          }
        }
      }

      $rows = array();

      // If there's only a single currency total...
      if (count($totals) == 1) {
        $currency_code = array_shift(array_keys($totals));

        // Add a row for the total amount paid.
        $rows[] = array(
          'data' => array(
            array('data' => t('Total paid'), 'class' => array('label')),
            array('data' => commerce_currency_format($totals[$currency_code], $currency_code), 'class' => array('total')),
          ),
          'class' => array('total-paid'),
        );

        // Add a row for the remaining balance on the order.
        $rows[] = array(
          'data' => array(
            array('data' => t('Order balance'), 'class' => array('label')),
            // TODO: Provide an API function to caluclate an order's remaining balance.
            array('data' => '-', 'class' => array('balance')),
          ),
          'class' => array('order-balance'),
        );
      }
      else {
        foreach ($totals as $currency_code => $amount) {
          $rows[] = array(
            'data' => array(
              array('data' => t('Total paid (@currency_code)', array('@currency_code' => $currency_code)), 'class' => array('label')),
              array('data' => commerce_currency_format($amount, $currency_code), 'class' => array('total')),
            ),
            'class' => array('total-paid', 'total-' . $currency_code),
          );
        }
      }

      // Prepare variables for use in the theme function.
      $variables = array(
        'view' => $this->view,
        'totals' => $totals,
        'rows' => $rows,
      );

      return theme('commerce_payment_totals', $variables);
    }
  }
}
