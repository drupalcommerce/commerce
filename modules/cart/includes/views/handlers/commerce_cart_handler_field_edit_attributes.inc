<?php

/**
 * @file
 * Field handler to render attribute widgets to select a different product for
 * the line item.
 */

class commerce_cart_handler_field_edit_attributes extends views_handler_field {

  /**
   * Overrides views_handler_field::construct().
   */
  function construct() {
    parent::construct();
    $this->additional_fields['line_item_id'] = 'line_item_id';

    // Set real_field in order to make it generate a field_alias.
    $this->real_field = 'line_item_id';
  }

  /**
   * Overrides views_handler_field::render().
   */
  function render($values) {
    // Render the field as a placeholder for eventual Views form replacement.
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

    // At this point, the query has already been run, so we can access the
    // results in order to get the base key value (for example, nid for nodes).
    foreach ($this->view->result as $row_id => $row) {
      // Load the line item and generate a form ID based on its context data.
      $line_item = commerce_line_item_load($this->get_value($row, 'line_item_id'));
      $product_ids = commerce_cart_add_to_cart_form_product_ids($line_item);
      $form_id = commerce_cart_add_to_cart_form_id($product_ids);

      // Ensure the current line item is a product line item.
      if (!in_array($line_item->type, commerce_product_line_item_types())) {
        continue;
      }

      // Fetch the Add to Cart form and put the attributes section into this
      // field's part of the form if it exists.
      $subform_state = $form_state;
      $subform_state['build_info'] = array(
        'form_id' => $form_id,
        'base_form_id' => 'commerce_cart_add_to_cart_form',
        'args' => array($line_item),
      );

      // If the form has been submitted, copy the attributes values from the
      // edit_attributes sub-array to the top level of the submitted values
      // array so the selected product can be properly matched when the Add to
      // Cart form builds itself.
      if (!empty($form_state['values']['edit_attributes'][$row_id])) {
        $subform_state['values'] += $form_state['values']['edit_attributes'][$row_id];
      }

      $subform = array();
      $subform = commerce_cart_add_to_cart_form($subform, $subform_state, $line_item);

      // If an Ajax refresh resulted in an updated product ID, update the line
      // item accordingly and rebuild the subform. Note that for line items
      // that group products such that a product select list is required, we
      // have to fetch the current product ID differently than if it is stored
      // in a hidden form field.
      $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

      if (!empty($subform['product_id'])) {
        $product_id = NULL;

        if (in_array($subform['product_id']['#type'], array('hidden', 'value'))) {
          $product_id = $subform['product_id']['#value'];
        }
        else {
          $product_id = $subform['product_id']['#default_value'];
        }

        if (isset($product_id) && $product_id != $line_item_wrapper->commerce_product->raw()) {
          $line_item_wrapper->commerce_product = $product_id;
          commerce_line_item_save($line_item);
          commerce_cart_order_refresh($line_item->order_id);

          // Rebuild the subform array.
          $subform_state = $form_state;
          $subform_state['build_info'] = array(
            'form_id' => $form_id,
            'base_form_id' => 'commerce_cart_add_to_cart_form',
            'args' => array($line_item),
          );

          $subform = array();
          $subform = commerce_cart_add_to_cart_form($subform, $subform_state, $line_item);
        }
      }

      // Initialize the element representing the attribute fields in the form.
      $form[$this->options['id']][$row_id] = array();
      $element = &$form[$this->options['id']][$row_id];
      $element['#line_item'] = $line_item->line_item_id;

      foreach (array('attributes', 'unchanged_attributes', 'product_id') as $name) {
        if (!empty($subform[$name])) {
          $element[$name] = $subform[$name];

          if (!empty($element[$name]['#ajax']['callback'])) {
            $element[$name]['#ajax']['callback'] = 'commerce_cart_add_to_cart_views_form_refresh';
          }

          foreach (element_children($element[$name]) as $key) {
            if (!empty($element[$name][$key]['#ajax']['callback'])) {
              $element[$name][$key]['#ajax']['callback'] = 'commerce_cart_add_to_cart_views_form_refresh';
            }
          }
        }
      }
    }
  }
}
