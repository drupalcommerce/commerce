<?php

/**
 * @file
 * Field handler to present a form field to change quantity of a line item. It's
 * a dummy handler, most part of the implementation is done via post render
 * hook.
 */

/**
 * Field handler to present a field to change quantity of a line item.
 */
class commerce_line_item_handler_field_edit_quantity extends views_handler_field {

  function construct() {
    parent::construct();
    $this->additional_fields['line_item_id'] = 'line_item_id';
    $this->additional_fields['quantity'] = 'quantity';

    // Set real_field in order to make it generate a field_alias.
    $this->real_field = 'quantity';
  }

  function render($values) {
    return '<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->';
  }

  /**
   * Returns the form which replaces the placeholder from render().
   */
  function views_form(&$form, &$form_state) {
    // The view is empty, abort.
    if (empty($this->view->result)) {
      return;
    }

    $form[$this->options['id']] = array(
      '#tree' => TRUE,
    );
    // At this point, the query has already been run, so we can access the results
    // in order to get the base key value (for example, nid for nodes).
    foreach ($this->view->result as $row_id => $row) {
      $line_item_id = $this->get_value($row, 'line_item_id');
      $quantity = $this->get_value($row, 'quantity');

      $form[$this->options['id']][$row_id] = array(
        '#type' => 'textfield',
        '#datatype' => 'integer',
        '#default_value' => round($quantity),
        '#size' => 4,
        '#maxlength' => max(4, strlen($quantity)),
        '#line_item_id' => $line_item_id,
      );
    }
  }

  function views_form_validate($form, &$form_state) {
    $field_name = $this->options['id'];
    foreach (element_children($form[$field_name]) as $row_id) {
      // Ensure the quantity is actually a numeric value.
      $line_item_id = $form[$field_name][$row_id]['#line_item_id'];
      if (!is_numeric($form_state['values'][$field_name][$row_id]) || $form_state['values'][$field_name][$row_id] < 0) {
        form_set_error($field_name . '][' . $row_id, t('You must specify a positive number for the quantity'));
      }

      // If the custom data type attribute of the quantity element is integer,
      // ensure we only accept whole number values.
      if ($form[$field_name][$row_id]['#datatype'] == 'integer' &&
        (int) $form_state['values'][$field_name][$row_id] != $form_state['values'][$field_name][$row_id]) {
        form_set_error($field_name . '][' . $row_id, t('You must specify a whole number for the quantity.'));
      }
    }
  }

  function views_form_submit($form, &$form_state) {
    $field_name = $this->options['id'];
    foreach (element_children($form[$field_name]) as $row_id) {
      $line_item_id = $form[$field_name][$row_id]['#line_item_id'];
      // If the line item hasn't been deleted...
      if ($line_item = commerce_line_item_load($line_item_id)) {
        // And the quantity on the form is different...
        if ($form_state['values'][$field_name][$row_id] != $line_item->quantity) {
          if ($form_state['values'][$field_name][$row_id] == 0) {
            // If the quantity specified is 0, the line item is deleted.
            $order = commerce_order_load($form_state['order']->order_id);
            commerce_cart_order_product_line_item_delete($order, $line_item_id);
          }
          else {
            // Change the quantity and save the line item.
            $line_item->quantity = $form_state['values'][$field_name][$row_id];

            commerce_line_item_save($line_item);
            entity_get_controller('commerce_line_item')->resetCache(array($line_item->line_item_id));
          }
        }
      }
    }
  }
}
