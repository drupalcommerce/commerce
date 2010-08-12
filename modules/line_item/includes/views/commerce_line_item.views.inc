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
      'handler' => 'commerce_line_item_handler_filter_line_item_type',
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

  // Expose the created and changed timestamps.
  $data['commerce_line_item']['created'] = array(
    'title' => t('Created date'),
    'help' => t('The date the line item was created.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  $data['commerce_line_item']['changed'] = array(
    'title' => t('Updated date'),
    'help' => t('The date the line item was last updated.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  // Define a handler for an area used to summarize a set of line items.
  $data['commerce_line_item']['line_item_summary'] = array(
    'title' => t('Line item summary'),
    'help' => t('Summarize the line items in a View with an optional link to checkout.'),
    'area' => array(
      'handler' => 'commerce_line_item_handler_area_line_item_summary',
    ),
  );

  return $data;
}
