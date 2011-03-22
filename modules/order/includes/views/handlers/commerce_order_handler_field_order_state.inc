<?php

/**
 * Field handler to translate an order state into its readable form.
 */
class commerce_order_handler_field_order_state extends commerce_order_handler_field_order {
  function construct() {
    parent::construct();
    $this->additional_fields['status'] = 'status';
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    $status_name = $this->get_value($values, 'status');
    $order_status = commerce_order_status_load($status_name);

    if (isset($order_status['state'])) {
      $order_state = commerce_order_state_load($order_status['state']);

      // Only attempt to render a valid order state.
      if (!is_array($order_state['title'])) {
        return $this->render_link($this->sanitize_value($order_state['title']), $values);
      }
    }
  }
}
