<?php

/**
 * Filter line items by whether or not they are of a product line item type.
 */
class commerce_product_reference_handler_filter_product_line_item_type extends views_handler_filter_boolean_operator {
  /**
   * Defaults the filter to True.
   */
  function option_definition() {
    $options = parent::option_definition();
    $options['value']['default'] = TRUE;
    return $options;
  }

  /**
   * Adds a meaningful title to the value form element.
   */
  function value_form(&$form, &$form_state) {
    parent::value_form($form, $form_state);
    $form['value']['#title'] = t('Line item is of a product line item type');
  }

  /**
   * Disables exposing this filter.
   */
  function can_expose() {
    return FALSE;
  }

  /**
   * Query against the line item's type column using an IN() condition.
   */
  function query() {
    $this->ensure_my_table();
    $field = "$this->table_alias.type";
    $types = commerce_product_line_item_types();
    $operator = empty($this->value) ? 'not in' : 'in';

    $this->query->add_where($this->options['group'], $field, $types, $operator);
  }
}
