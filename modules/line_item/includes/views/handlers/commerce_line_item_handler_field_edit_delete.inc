<?php

/**
 * @file
 * Field handler to present a button to remove a line item. It's a dummy
 * handler, most part of the implementation is done via pre and post render
 * hooks.
 */

/**
 * Field handler to present a button to delete a line item.
 */
class commerce_line_item_handler_field_edit_delete extends views_handler_field {

  function construct() {
    parent::construct();
    $this->additional_fields['line_item_id'] = 'line_item_id';

    // Set real_field in order to make it generate a field_alias.
    $this->real_field = 'line_item_id';
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
      $line_item_id = $this->get_value($row);

      $form[$this->options['id']][$row_id] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
        '#name' => 'delete-line-item-' . $row_id,
        '#attributes' => array('class' => array('delete-line-item')),
        '#line_item_id' => $line_item_id,
        '#submit' => array_merge($form['#submit'], array('commerce_line_item_line_item_views_delete_form_submit')),
      );
    }
  }

  function views_form_submit($form, &$form_state) {
    $order = commerce_order_load($form_state['order']->order_id);
    $field_name = $this->options['id'];

    foreach (element_children($form[$field_name]) as $row_id) {
      // Check for the removal of an item.
      if ($form_state['triggering_element']['#name'] == 'delete-line-item-' . $row_id) {
        $line_item_id = $form[$field_name][$row_id]['#line_item_id'];
        // TODO: Remove this dependence on the Cart module API.
        commerce_cart_order_product_line_item_delete($order, $line_item_id);
      }
    }
  }

}
