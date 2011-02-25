<?php

/**
 * @file
 * Base field handler to present a form field to edit a line item field.
 */

/**
 * Field handler to present a field to change quantity of a line item.
 */
class commerce_line_item_handler_field_edit extends views_handler_field {
  var $form;

  function construct() {
    parent::construct();
    $this->additional_fields['line_item_id'] = 'line_item_id';

    // Set real_field in order to make it generate a field_alias.
    $this->real_field = 'line_item_id';
  }

  /**
   * Returns a form field array used to edit the value of this field.
   */
  function get_edit_form() {
    return $this->form;
  }

  /**
   * Validates the new value set to the field.
   */
  function edit_form_validate($form, &$form_state) {}

  /**
   * Process the new field value.
   */
  function edit_form_submit($form, &$form_state) {}

}
