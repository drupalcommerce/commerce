<?php

/**
 * @file
 * Default implementation of a payment totals template.
 *
 * Available variables:
 * - $rows: A rows array as used by theme_table(), potentially containing
 *     transaction totals, an order balance, and information specified by
 *     other modules.
 * - $form: When present, a form for adding payments to an order pertinent to
 *     the display.
 *
 * Helper variables:
 * - $totals: An array of transaction totals keyed by currency code.
 * - $view: The View the line item summary is attached to.
 * - $order: If present, the order represented by the totals.
 *
 * @see template_preprocess()
 * @see template_process()
 */
?>
<div class="payment-totals">
  <?php print theme('table', array('rows' => $rows, 'attributes' => array('class' => array('payment-totals-table')))); ?>
  <?php print $form; ?>
</div>
