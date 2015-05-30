<?php

/**
 * Filters a line item View to only include line items referenced by the order
 * they claim to be a part of in their order_id property.
 */

class commerce_line_item_handler_filter_line_item_is_referenced extends views_handler_filter {

  /**
   * Disables exposing this filter.
   */
  function can_expose() {
    return FALSE;
  }

  /**
   * Only add to the admin summary if the filter has been negated.
   */
  function admin_summary() {
    if ($this->operator == 'NOT IN') {
      return 'NOT';
    }

    return '';
  }

  function option_definition() {
    $options  = parent::option_definition();
    $options['operator'] = array('default' => 'IN');
    return $options;
  }

  function operator_options($which = 'title') {
    $options = array();
    $options['IN'] = t('Line item is referenced');
    $options['NOT IN'] = t('Line item is NOT referenced');
    return $options;
  }

  /**
   * Adds a subquery to the WHERE clause that ensures the order_id referenced by
   * the line item is present in the comerce_line_items field's table for the
   * given line_item_id.
   */
  function query() {
    // Ensure we have a valid operator for the subquery.
    if (empty($this->operator)) {
      $this->operator = 'IN';
    }

    // Ensure the commerce_line_items field exists and fetch its table name.
    $field_info = field_info_field('commerce_line_items');

    if (empty($field_info['storage']['details']['sql']['FIELD_LOAD_CURRENT'])) {
      return;
    }

    $field_table = key($field_info['storage']['details']['sql']['FIELD_LOAD_CURRENT']);
    $field_value_column = $field_info['storage']['details']['sql']['FIELD_LOAD_CURRENT'][$field_table]['line_item_id'];

    $this->table_alias = $this->query->ensure_table($this->view->base_table, $this->relationship);

    // Build SQL where snippet using a subquery on the field table for the
    // required default line item reference field on orders.
    $snippet = $this->table_alias . '.line_item_id ' . $this->operator . ' (SELECT ' . $field_value_column . ' FROM {' . $field_table . '} WHERE deleted = 0 AND entity_id != 0 AND entity_id = ' . $this->table_alias . '.order_id)';

    $this->query->add_where_expression($this->options['group'], $snippet);
  }

}
