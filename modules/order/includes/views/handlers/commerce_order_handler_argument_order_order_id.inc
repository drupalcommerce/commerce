<?php

/**
 * @file
 * Provide an order order_id argument handler.
 */

/**
 * Argument handler to accept an order_id.
 */
class commerce_order_handler_argument_order_order_id extends views_handler_argument_numeric {
  /**
   * Override the behavior of title(). Get the number of the order.
   */
  function title_query() {
    $titles = array();

    $result = db_query("SELECT o.order_number FROM {commerce_order} o WHERE o.order_id IN (:order_ids)", array(':order_ids' => $this->value));

    foreach ($result as $order) {
      $titles[] = check_plain($order->order_number);
    }

    return $titles;
  }
}
