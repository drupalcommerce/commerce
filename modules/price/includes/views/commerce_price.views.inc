<?php

/**
 * @file
 * Provides Views integration for the commerce_price field.
 */

/**
 * Implements hook_field_views_data().
 */
function commerce_price_field_views_data($field) {
  $data = field_views_field_default_views_data($field);

  foreach ($data as $table_name => $table_data) {
    $field_name = $field['field_name'] . '_amount';
    $data[$table_name][$field_name]['filter']['handler'] = 'commerce_price_handler_filter_commerce_price_amount';
    $data[$table_name][$field_name]['filter']['allow empty'] = FALSE;
  }

  return $data;
}
