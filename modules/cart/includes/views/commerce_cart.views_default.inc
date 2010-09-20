<?php
// $Id$

/**
 * Views for the shopping cart.
 */

/**
 * Implements hook_views_default_views().
 */
function commerce_cart_views_default_views() {
  $views = array();

  // Shopping cart view for the block and checkout pane.
  $view = new view;
  $view->name = 'commerce_cart_block';
  $view->description = 'Display a list of line items addded to cart.';
  $view->tag = '';
  $view->view_php = '';
  $view->base_table = 'commerce_order';
  $view->is_cacheable = FALSE;
  $view->api_version = '3.0-alpha1';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Defaults */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->display->display_options['title'] = 'Shopping cart';
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'none';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'line_item_label' => 'line_item_label',
    'quantity' => 'quantity',
    'entity_id' => 'entity_id',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'line_item_label' => array(
      'sortable' => 0,
      'align' => '',
      'separator' => '',
    ),
    'quantity' => array(
      'sortable' => 0,
      'align' => '',
      'separator' => '',
    ),
    'entity_id' => array(
      'sortable' => 0,
      'align' => 'views-align-right',
      'separator' => '',
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  /* Footer: Commerce Line Item: Line item summary */
  $handler->display->display_options['footer']['line_item_summary']['id'] = 'line_item_summary';
  $handler->display->display_options['footer']['line_item_summary']['table'] = 'commerce_line_item';
  $handler->display->display_options['footer']['line_item_summary']['field'] = 'line_item_summary';
  $handler->display->display_options['footer']['line_item_summary']['links'] = array(
    'view_cart' => 'view_cart',
    'checkout' => 'checkout',
  );
  /* Relationship: Fields: Referenced line item */
  $handler->display->display_options['relationships']['line_items_line_item_id']['id'] = 'line_items_line_item_id';
  $handler->display->display_options['relationships']['line_items_line_item_id']['table'] = 'field_data_line_items';
  $handler->display->display_options['relationships']['line_items_line_item_id']['field'] = 'line_items_line_item_id';
  $handler->display->display_options['relationships']['line_items_line_item_id']['required'] = 1;
  /* Field: Commerce Line Item: Quantity */
  $handler->display->display_options['fields']['quantity']['id'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['quantity']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['relationship'] = 'line_items_line_item_id';
  $handler->display->display_options['fields']['quantity']['label'] = '';
  $handler->display->display_options['fields']['quantity']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity']['empty_zero'] = 0;
  $handler->display->display_options['fields']['quantity']['set_precision'] = 0;
  $handler->display->display_options['fields']['quantity']['precision'] = '0';
  $handler->display->display_options['fields']['quantity']['suffix'] = ' × ';
  /* Field: Commerce Line Item: Line item label */
  $handler->display->display_options['fields']['line_item_label']['id'] = 'line_item_label';
  $handler->display->display_options['fields']['line_item_label']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['line_item_label']['field'] = 'line_item_label';
  $handler->display->display_options['fields']['line_item_label']['relationship'] = 'line_items_line_item_id';
  $handler->display->display_options['fields']['line_item_label']['label'] = '';
  $handler->display->display_options['fields']['line_item_label']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['line_item_label']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['line_item_label']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['html'] = 0;
  $handler->display->display_options['fields']['line_item_label']['hide_empty'] = 0;
  $handler->display->display_options['fields']['line_item_label']['empty_zero'] = 0;
  /* Field: Fields: total */
  $handler->display->display_options['fields']['entity_id']['id'] = 'entity_id';
  $handler->display->display_options['fields']['entity_id']['table'] = 'field_data_total';
  $handler->display->display_options['fields']['entity_id']['field'] = 'entity_id';
  $handler->display->display_options['fields']['entity_id']['relationship'] = 'line_items_line_item_id';
  $handler->display->display_options['fields']['entity_id']['label'] = '';
  $handler->display->display_options['fields']['entity_id']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['entity_id']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['entity_id']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['entity_id']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['entity_id']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['entity_id']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['entity_id']['alter']['html'] = 0;
  $handler->display->display_options['fields']['entity_id']['hide_empty'] = 0;
  $handler->display->display_options['fields']['entity_id']['empty_zero'] = 0;
  /* Sort criterion: Commerce Line Item: Line item ID */
  $handler->display->display_options['sorts']['line_item_id']['id'] = 'line_item_id';
  $handler->display->display_options['sorts']['line_item_id']['table'] = 'commerce_line_item';
  $handler->display->display_options['sorts']['line_item_id']['field'] = 'line_item_id';
  $handler->display->display_options['sorts']['line_item_id']['relationship'] = 'line_items_line_item_id';
  /* Argument: Commerce Order: Order ID */
  $handler->display->display_options['arguments']['order_id']['id'] = 'order_id';
  $handler->display->display_options['arguments']['order_id']['table'] = 'commerce_order';
  $handler->display->display_options['arguments']['order_id']['field'] = 'order_id';
  $handler->display->display_options['arguments']['order_id']['style_plugin'] = 'default_summary';
  $handler->display->display_options['arguments']['order_id']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['order_id']['break_phrase'] = 0;
  $handler->display->display_options['arguments']['order_id']['not'] = 0;
  /* Filter: Commerce Line Item: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'commerce_line_item';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['relationship'] = 'line_items_line_item_id';
  $handler->display->display_options['filters']['type']['value'] = array(
    'product' => 'product',
  );

  $views[$view->name] = $view;

  // Now add a summary version that don't use links in the line item summary for
  // use during checkout.
  $view = new view;
  $view->name = 'commerce_cart_summary';
  $view->description = 'Cart summary displayed at the url checkout/2.';
  $view->tag = '';
  $view->view_php = '';
  $view->base_table = 'commerce_order';
  $view->is_cacheable = FALSE;
  $view->api_version = '3.0-alpha1';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Defaults */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->display->display_options['title'] = 'Shopping cart';
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'none';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'line_item_label' => 'line_item_label',
    'quantity' => 'quantity',
    'entity_id' => 'entity_id',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'line_item_label' => array(
      'sortable' => 0,
      'align' => '',
      'separator' => '',
    ),
    'quantity' => array(
      'sortable' => 0,
      'align' => '',
      'separator' => '',
    ),
    'entity_id' => array(
      'sortable' => 0,
      'align' => 'views-align-right',
      'separator' => '',
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  /* Footer: Commerce Line Item: Line item summary */
  $handler->display->display_options['footer']['line_item_summary']['id'] = 'line_item_summary';
  $handler->display->display_options['footer']['line_item_summary']['table'] = 'commerce_line_item';
  $handler->display->display_options['footer']['line_item_summary']['field'] = 'line_item_summary';
  $handler->display->display_options['footer']['line_item_summary']['links'] = array(
    'checkout' => 0,
  );
  /* Relationship: Fields: Referenced line item */
  $handler->display->display_options['relationships']['line_items_line_item_id']['id'] = 'line_items_line_item_id';
  $handler->display->display_options['relationships']['line_items_line_item_id']['table'] = 'field_data_line_items';
  $handler->display->display_options['relationships']['line_items_line_item_id']['field'] = 'line_items_line_item_id';
  $handler->display->display_options['relationships']['line_items_line_item_id']['required'] = 1;
  /* Field: Commerce Line Item: Quantity */
  $handler->display->display_options['fields']['quantity']['id'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['quantity']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['relationship'] = 'line_items_line_item_id';
  $handler->display->display_options['fields']['quantity']['label'] = '';
  $handler->display->display_options['fields']['quantity']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity']['empty_zero'] = 0;
  $handler->display->display_options['fields']['quantity']['set_precision'] = 0;
  $handler->display->display_options['fields']['quantity']['precision'] = '0';
  $handler->display->display_options['fields']['quantity']['suffix'] = ' × ';
  /* Field: Commerce Line Item: Line item label */
  $handler->display->display_options['fields']['line_item_label']['id'] = 'line_item_label';
  $handler->display->display_options['fields']['line_item_label']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['line_item_label']['field'] = 'line_item_label';
  $handler->display->display_options['fields']['line_item_label']['relationship'] = 'line_items_line_item_id';
  $handler->display->display_options['fields']['line_item_label']['label'] = '';
  $handler->display->display_options['fields']['line_item_label']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['line_item_label']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['line_item_label']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['html'] = 0;
  $handler->display->display_options['fields']['line_item_label']['hide_empty'] = 0;
  $handler->display->display_options['fields']['line_item_label']['empty_zero'] = 0;
  /* Field: Fields: total */
  $handler->display->display_options['fields']['entity_id']['id'] = 'entity_id';
  $handler->display->display_options['fields']['entity_id']['table'] = 'field_data_total';
  $handler->display->display_options['fields']['entity_id']['field'] = 'entity_id';
  $handler->display->display_options['fields']['entity_id']['relationship'] = 'line_items_line_item_id';
  $handler->display->display_options['fields']['entity_id']['label'] = '';
  $handler->display->display_options['fields']['entity_id']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['entity_id']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['entity_id']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['entity_id']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['entity_id']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['entity_id']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['entity_id']['alter']['html'] = 0;
  $handler->display->display_options['fields']['entity_id']['hide_empty'] = 0;
  $handler->display->display_options['fields']['entity_id']['empty_zero'] = 0;
  /* Sort criterion: Commerce Line Item: Line item ID */
  $handler->display->display_options['sorts']['line_item_id']['id'] = 'line_item_id';
  $handler->display->display_options['sorts']['line_item_id']['table'] = 'commerce_line_item';
  $handler->display->display_options['sorts']['line_item_id']['field'] = 'line_item_id';
  $handler->display->display_options['sorts']['line_item_id']['relationship'] = 'line_items_line_item_id';
  /* Argument: Commerce Order: Order ID */
  $handler->display->display_options['arguments']['order_id']['id'] = 'order_id';
  $handler->display->display_options['arguments']['order_id']['table'] = 'commerce_order';
  $handler->display->display_options['arguments']['order_id']['field'] = 'order_id';
  $handler->display->display_options['arguments']['order_id']['style_plugin'] = 'default_summary';
  $handler->display->display_options['arguments']['order_id']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['order_id']['break_phrase'] = 0;
  $handler->display->display_options['arguments']['order_id']['not'] = 0;
  /* Filter: Commerce Line Item: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'commerce_line_item';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['relationship'] = 'line_items_line_item_id';
  $handler->display->display_options['filters']['type']['value'] = array(
    'product' => 'product',
  );

  $views[$view->name] = $view;

  return $views;
}
