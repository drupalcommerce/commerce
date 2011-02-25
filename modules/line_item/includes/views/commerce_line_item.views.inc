<?php

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

  // Expose the type-dependent line item title.
  $data['commerce_line_item']['line_item_title'] = array(
    'field' => array(
      'title' => t('Title'),
      'help' => t('The title of the line item determined by its type.'),
      'handler' => 'commerce_line_item_handler_field_line_item_title',
    ),
  );

  // Expose the line item label.
  $data['commerce_line_item']['line_item_label'] = array(
    'title' => t('Label'),
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

  // Adds a textfield to edit line item quantity on the view.
  $data['commerce_line_item']['edit_quantity'] = array(
    'field' => array(
      'title' => t('Quantity text field'),
      'help' => t('Adds a text field to edit the line item quantity in the View.'),
      'handler' => 'commerce_line_item_handler_field_edit_quantity',
    ),
  );

  // Adds a button to delete a line item.
  $data['commerce_line_item']['edit_delete'] = array(
    'field' => array(
      'title' => t('Delete button'),
      'help' => t('Adds a button to delete a line item.'),
      'handler' => 'commerce_line_item_handler_field_edit_delete',
    ),
  );

  // Expose the order ID.
  $data['commerce_line_item']['order_id'] = array(
    'title' => t('Order ID', array(), array('context' => 'a drupal commerce order')),
    'help' => t('The unique internal identifier of the associated order.'),
    'field' => array(
      'handler' => 'commerce_order_handler_field_order',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'commerce_order_handler_argument_order_order_id',
      'name field' => 'order_number',
      'numeric' => TRUE,
      'validate type' => 'order_id',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'commerce_order',
      'field' => 'order_id',
      'label' => t('Order', array(), array('context' => 'a drupal commerce order')),
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

  // Define an area handler for displaying line item action buttons.
  $data['commerce_line_item']['line_item_actions'] = array(
    'title' => t('Line item actions'),
    'help' => t('Display buttons to act on line items.'),
    'area' => array(
      'handler' => 'commerce_line_item_handler_area_line_item_actions',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_post_render().
 */
function commerce_line_item_views_post_render(&$view, &$output, &$cache) {
  if ($view->base_table == 'commerce_order') {
    // Check the fields on the View to see if any adding to an edit form by
    // extending the field edit handler.
    $has_form_fields = FALSE;

    foreach ($view->field as $field_name => $field) {
      if(is_a($field, 'commerce_line_item_handler_field_edit')) {
        $has_form_fields = TRUE;
      }
    }

    // If we did find form fields in the View, format the output of the View as
    // a form using the line item Views form.
    if ($has_form_fields) {
      module_load_include('inc', 'commerce_line_item', 'includes/commerce_line_item.forms');
      $form = drupal_get_form(commerce_line_item_views_form_id($view), $view, $output);
      $output = drupal_render($form);
    }
  }
}
