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
class commerce_line_item_handler_field_edit_delete extends commerce_line_item_handler_field_edit {

  function render($values) {
    $line_item_id = $this->get_value($values, 'line_item_id');

    // Add form that will be used for replacing the placeholder generated bellow
    // at form rendering phase in commerce_line_item_views_post_render().
    $this->form[$line_item_id] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#name' => 'delete-line-item-' . $line_item_id,
      '#attributes' => array('class' => array('delete-line-item')),
    );

    // Add a default delete submit handler.
    $this->form[$line_item_id]['#submit'] = array('commerce_line_item_line_item_views_delete_form_submit');

    // Return the placeholder that will be used as token for replacing itself
    // with the rendered form field created above.
    return '<!--post-commerce-line-item-edit_delete-' . $line_item_id . '-->';
  }

  function edit_form_submit($form, &$form_state) {
    $order = commerce_order_load($form_state['order']->order_id);

    foreach (element_children($form['edit_delete']) as $line_item_id) {
      // Check for the removal of an item.
      if ($form_state['triggering_element']['#name'] == 'delete-line-item-' . $line_item_id) {
        // TODO: Remove this dependence on the Cart module API.
        commerce_cart_order_product_line_item_delete($order, $line_item_id);
      }
    }
  }

}
