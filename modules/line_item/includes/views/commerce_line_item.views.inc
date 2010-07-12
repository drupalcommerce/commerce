<?php
// $Id$

/**
 * Export Drupal Commerce line items to Views.
 */

/**
 * Implements hook_views_data()
 */
function commerce_line_item_views_data() {
  $data = array();

  $data['commerce_line_item']['table']['group']  = t('Commerce Line Item');

  $data['commerce_line_item']['table']['base'] = array(
    'field' => 'line_item_id',
    'title' => t('Commerce Line Item'),
    'help' => t('A line item referenced by another entity.'),
  );

  // Expose the line item ID.
  $data['commerce_line_item']['line_item_id'] = array(
    'title' => t('Line item ID'),
    'help' => t('The unique internal identifier of the line item.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'commerce_line_item_handler_argument_line_item_line_item_id',
      'name field' => 'line_item_label',
      'numeric' => TRUE,
      'validate type' => 'line_item_id',
    ),
  );

  // Expose the product type.
  $data['commerce_line_item']['type'] = array(
    'title' => t('Type'),
    'help' => t('The human-readable name of the type of the line item.'),
    'field' => array(
      'handler' => 'commerce_line_item_handler_field_line_item_type',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // TODO: Expose the display view build mode.

  // Expose the line item label.
  $data['commerce_line_item']['line_item_label'] = array(
    'title' => t('Line item label'),
    'help' => t('The label of the line item.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // Expose the line item quantity.
  $data['commerce_line_item']['quantity'] = array(
    'title' => t('Quantity'),
    'help' => t('The quantity of the line item.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
      'float' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_handlers().
 */
function commerce_line_item_views_handlers() {
  return array(
    'info' => array(
      'path' => drupal_get_path('module', 'commerce_line_item') . '/includes/views/handlers',
    ),
    'handlers' => array(
      'commerce_line_item_handler_argument_line_item_line_item_id',
      'commerce_line_item_handler_field_line_item_type',
    ),
  );
}
