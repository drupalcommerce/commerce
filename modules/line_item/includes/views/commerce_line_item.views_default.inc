<?php
// $Id$

/**
 * Views for line item reference displays.
 */

/**
 * Implements hook_views_default_views().
 */
function commerce_line_item_views_default_views() {
  $views = array();

  // Line item view for the order view page.
  $view = new view;
  $view->name = 'commerce_line_item_table';
  $view->description = 'Display a set of line items in a table.';
  $view->tag = '';
  $view->view_php = '';
  $view->base_table = 'commerce_line_item';
  $view->is_cacheable = FALSE;
  $view->api_version = '3.0-alpha1';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Defaults */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'none';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'line_item_id' => 'line_item_id',
    'line_item_label' => 'line_item_label',
    'quantity' => 'quantity',
    'entity_id_1' => 'entity_id_1',
    'entity_id' => 'entity_id',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'line_item_id' => array(
      'sortable' => 0,
      'align' => '',
      'separator' => '',
    ),
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
    'entity_id_1' => array(
      'sortable' => 0,
      'align' => 'views-align-right',
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
  /* Empty text: Global: Text area */
  $handler->display->display_options['empty']['area']['id'] = 'area';
  $handler->display->display_options['empty']['area']['table'] = 'views';
  $handler->display->display_options['empty']['area']['field'] = 'area';
  $handler->display->display_options['empty']['area']['label'] = 'Empty line item text.';
  $handler->display->display_options['empty']['area']['empty'] = FALSE;
  $handler->display->display_options['empty']['area']['content'] = 'No line items found.';
  $handler->display->display_options['empty']['area']['format'] = '1';
  /* Field: Commerce Line Item: Line item ID */
  $handler->display->display_options['fields']['line_item_id']['id'] = 'line_item_id';
  $handler->display->display_options['fields']['line_item_id']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['line_item_id']['field'] = 'line_item_id';
  $handler->display->display_options['fields']['line_item_id']['label'] = 'ID';
  $handler->display->display_options['fields']['line_item_id']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['line_item_id']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['line_item_id']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['line_item_id']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['line_item_id']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['line_item_id']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['line_item_id']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['line_item_id']['alter']['html'] = 0;
  $handler->display->display_options['fields']['line_item_id']['hide_empty'] = 0;
  $handler->display->display_options['fields']['line_item_id']['empty_zero'] = 0;
  /* Field: Commerce Line Item: Type */
  $handler->display->display_options['fields']['type']['id'] = 'type';
  $handler->display->display_options['fields']['type']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['type']['field'] = 'type';
  $handler->display->display_options['fields']['type']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['type']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['type']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['type']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['type']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['type']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['type']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['type']['alter']['html'] = 0;
  $handler->display->display_options['fields']['type']['hide_empty'] = 0;
  $handler->display->display_options['fields']['type']['empty_zero'] = 0;
  /* Field: Commerce Line Item: Line item label */
  $handler->display->display_options['fields']['line_item_label']['id'] = 'line_item_label';
  $handler->display->display_options['fields']['line_item_label']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['line_item_label']['field'] = 'line_item_label';
  $handler->display->display_options['fields']['line_item_label']['label'] = 'Label';
  $handler->display->display_options['fields']['line_item_label']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['line_item_label']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['line_item_label']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['html'] = 0;
  $handler->display->display_options['fields']['line_item_label']['hide_empty'] = 0;
  $handler->display->display_options['fields']['line_item_label']['empty_zero'] = 0;
  /* Field: Commerce Line Item: Quantity */
  $handler->display->display_options['fields']['quantity']['id'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['quantity']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity']['empty_zero'] = 0;
  $handler->display->display_options['fields']['quantity']['set_precision'] = 0;
  $handler->display->display_options['fields']['quantity']['precision'] = '0';
  $handler->display->display_options['fields']['quantity']['format_plural'] = 0;
  /* Field: Fields: unit_price */
  $handler->display->display_options['fields']['entity_id_1']['id'] = 'entity_id_1';
  $handler->display->display_options['fields']['entity_id_1']['table'] = 'field_data_unit_price';
  $handler->display->display_options['fields']['entity_id_1']['field'] = 'entity_id';
  $handler->display->display_options['fields']['entity_id_1']['label'] = 'Unit price';
  $handler->display->display_options['fields']['entity_id_1']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['entity_id_1']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['entity_id_1']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['entity_id_1']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['entity_id_1']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['entity_id_1']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['entity_id_1']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['entity_id_1']['alter']['html'] = 0;
  $handler->display->display_options['fields']['entity_id_1']['hide_empty'] = 0;
  $handler->display->display_options['fields']['entity_id_1']['empty_zero'] = 0;
  /* Field: Fields: total */
  $handler->display->display_options['fields']['entity_id']['id'] = 'entity_id';
  $handler->display->display_options['fields']['entity_id']['table'] = 'field_data_total';
  $handler->display->display_options['fields']['entity_id']['field'] = 'entity_id';
  $handler->display->display_options['fields']['entity_id']['label'] = 'Total';
  $handler->display->display_options['fields']['entity_id']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['entity_id']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['entity_id']['alter']['absolute'] = 0;
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
  /* Argument: Commerce Line Item: Line item ID */
  $handler->display->display_options['arguments']['line_item_id']['id'] = 'line_item_id';
  $handler->display->display_options['arguments']['line_item_id']['table'] = 'commerce_line_item';
  $handler->display->display_options['arguments']['line_item_id']['field'] = 'line_item_id';
  $handler->display->display_options['arguments']['line_item_id']['default_action'] = 'empty';
  $handler->display->display_options['arguments']['line_item_id']['style_plugin'] = 'default_summary';
  $handler->display->display_options['arguments']['line_item_id']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['line_item_id']['break_phrase'] = 1;
  $handler->display->display_options['arguments']['line_item_id']['not'] = 0;

  $views[$view->name] = $view;

  return $views;
}
