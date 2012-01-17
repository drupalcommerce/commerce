<?php

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
  $view->tag = 'commerce';
  $view->base_table = 'commerce_line_item';
  $view->human_name = 'Line items';
  $view->core = 0;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Defaults */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'none';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'line_item_id' => 'line_item_id',
    'type' => 'type',
    'line_item_title' => 'line_item_title',
    'line_item_label' => 'line_item_title',
    'commerce_unit_price' => 'commerce_unit_price',
    'quantity' => 'quantity',
    'commerce_total' => 'commerce_total',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'line_item_id' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'type' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'line_item_title' => array(
      'align' => '',
      'separator' => ' ',
    ),
    'line_item_label' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'commerce_unit_price' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'quantity' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'commerce_total' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => 'views-align-right',
      'separator' => '',
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  /* No results behavior: Global: Text area */
  $handler->display->display_options['empty']['area']['id'] = 'area';
  $handler->display->display_options['empty']['area']['table'] = 'views';
  $handler->display->display_options['empty']['area']['field'] = 'area';
  $handler->display->display_options['empty']['area']['label'] = 'Empty line item text.';
  $handler->display->display_options['empty']['area']['empty'] = FALSE;
  $handler->display->display_options['empty']['area']['content'] = 'No line items found.';
  $handler->display->display_options['empty']['area']['format'] = 'plain_text';
  /* Field: Commerce Line Item: Line item ID */
  $handler->display->display_options['fields']['line_item_id']['id'] = 'line_item_id';
  $handler->display->display_options['fields']['line_item_id']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['line_item_id']['field'] = 'line_item_id';
  $handler->display->display_options['fields']['line_item_id']['label'] = 'ID';
  $handler->display->display_options['fields']['line_item_id']['exclude'] = TRUE;
  $handler->display->display_options['fields']['line_item_id']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['line_item_id']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['line_item_id']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['line_item_id']['alter']['external'] = 0;
  $handler->display->display_options['fields']['line_item_id']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['line_item_id']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['line_item_id']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['line_item_id']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['line_item_id']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['line_item_id']['alter']['html'] = 0;
  $handler->display->display_options['fields']['line_item_id']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['line_item_id']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['line_item_id']['hide_empty'] = 0;
  $handler->display->display_options['fields']['line_item_id']['empty_zero'] = 0;
  /* Field: Commerce Line Item: Type */
  $handler->display->display_options['fields']['type']['id'] = 'type';
  $handler->display->display_options['fields']['type']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['type']['field'] = 'type';
  $handler->display->display_options['fields']['type']['exclude'] = TRUE;
  $handler->display->display_options['fields']['type']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['type']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['type']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['type']['alter']['external'] = 0;
  $handler->display->display_options['fields']['type']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['type']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['type']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['type']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['type']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['type']['alter']['html'] = 0;
  $handler->display->display_options['fields']['type']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['type']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['type']['hide_empty'] = 0;
  $handler->display->display_options['fields']['type']['empty_zero'] = 0;
  /* Field: Commerce Line Item: Title */
  $handler->display->display_options['fields']['line_item_title']['id'] = 'line_item_title';
  $handler->display->display_options['fields']['line_item_title']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['line_item_title']['field'] = 'line_item_title';
  $handler->display->display_options['fields']['line_item_title']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['line_item_title']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['line_item_title']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['line_item_title']['alter']['external'] = 0;
  $handler->display->display_options['fields']['line_item_title']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['line_item_title']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['line_item_title']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['line_item_title']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['line_item_title']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['line_item_title']['alter']['html'] = 0;
  $handler->display->display_options['fields']['line_item_title']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['line_item_title']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['line_item_title']['hide_empty'] = 0;
  $handler->display->display_options['fields']['line_item_title']['empty_zero'] = 0;
  /* Field: Commerce Line Item: Label */
  $handler->display->display_options['fields']['line_item_label']['id'] = 'line_item_label';
  $handler->display->display_options['fields']['line_item_label']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['line_item_label']['field'] = 'line_item_label';
  $handler->display->display_options['fields']['line_item_label']['alter']['alter_text'] = 1;
  $handler->display->display_options['fields']['line_item_label']['alter']['text'] = '([line_item_label])';
  $handler->display->display_options['fields']['line_item_label']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['external'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['line_item_label']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['line_item_label']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['line_item_label']['alter']['html'] = 0;
  $handler->display->display_options['fields']['line_item_label']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['line_item_label']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['line_item_label']['hide_empty'] = 0;
  $handler->display->display_options['fields']['line_item_label']['empty_zero'] = 0;
  /* Field: Commerce Line item: Unit price */
  $handler->display->display_options['fields']['commerce_unit_price']['id'] = 'commerce_unit_price';
  $handler->display->display_options['fields']['commerce_unit_price']['table'] = 'field_data_commerce_unit_price';
  $handler->display->display_options['fields']['commerce_unit_price']['field'] = 'commerce_unit_price';
  $handler->display->display_options['fields']['commerce_unit_price']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['commerce_unit_price']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['commerce_unit_price']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['commerce_unit_price']['alter']['external'] = 0;
  $handler->display->display_options['fields']['commerce_unit_price']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['commerce_unit_price']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['commerce_unit_price']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['commerce_unit_price']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['commerce_unit_price']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['commerce_unit_price']['alter']['html'] = 0;
  $handler->display->display_options['fields']['commerce_unit_price']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['commerce_unit_price']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['commerce_unit_price']['hide_empty'] = 0;
  $handler->display->display_options['fields']['commerce_unit_price']['empty_zero'] = 0;
  $handler->display->display_options['fields']['commerce_unit_price']['click_sort_column'] = 'amount';
  $handler->display->display_options['fields']['commerce_unit_price']['type'] = 'commerce_price_formatted_amount';
  $handler->display->display_options['fields']['commerce_unit_price']['field_api_classes'] = 0;
  /* Field: Commerce Line Item: Quantity */
  $handler->display->display_options['fields']['quantity']['id'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['quantity']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['external'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['quantity']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['quantity']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity']['empty_zero'] = 0;
  $handler->display->display_options['fields']['quantity']['set_precision'] = 0;
  $handler->display->display_options['fields']['quantity']['precision'] = '0';
  $handler->display->display_options['fields']['quantity']['format_plural'] = 0;
  /* Field: Commerce Line item: Total */
  $handler->display->display_options['fields']['commerce_total']['id'] = 'commerce_total';
  $handler->display->display_options['fields']['commerce_total']['table'] = 'field_data_commerce_total';
  $handler->display->display_options['fields']['commerce_total']['field'] = 'commerce_total';
  $handler->display->display_options['fields']['commerce_total']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['commerce_total']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['commerce_total']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['commerce_total']['alter']['external'] = 0;
  $handler->display->display_options['fields']['commerce_total']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['commerce_total']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['commerce_total']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['commerce_total']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['commerce_total']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['commerce_total']['alter']['html'] = 0;
  $handler->display->display_options['fields']['commerce_total']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['commerce_total']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['commerce_total']['hide_empty'] = 0;
  $handler->display->display_options['fields']['commerce_total']['empty_zero'] = 0;
  $handler->display->display_options['fields']['commerce_total']['click_sort_column'] = 'amount';
  $handler->display->display_options['fields']['commerce_total']['type'] = 'commerce_price_formatted_amount';
  $handler->display->display_options['fields']['commerce_total']['field_api_classes'] = 0;
  /* Sort criterion: Commerce Line Item: Line item ID */
  $handler->display->display_options['sorts']['line_item_id']['id'] = 'line_item_id';
  $handler->display->display_options['sorts']['line_item_id']['table'] = 'commerce_line_item';
  $handler->display->display_options['sorts']['line_item_id']['field'] = 'line_item_id';
  /* Contextual filter: Commerce Line Item: Line item ID */
  $handler->display->display_options['arguments']['line_item_id']['id'] = 'line_item_id';
  $handler->display->display_options['arguments']['line_item_id']['table'] = 'commerce_line_item';
  $handler->display->display_options['arguments']['line_item_id']['field'] = 'line_item_id';
  $handler->display->display_options['arguments']['line_item_id']['default_action'] = 'empty';
  $handler->display->display_options['arguments']['line_item_id']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['line_item_id']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['line_item_id']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['line_item_id']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['line_item_id']['break_phrase'] = 1;
  $handler->display->display_options['arguments']['line_item_id']['not'] = 0;
  /* Filter criterion: Commerce Line Item: Line item is of a product line item type */
  $handler->display->display_options['filters']['product_line_item_type']['id'] = 'product_line_item_type';
  $handler->display->display_options['filters']['product_line_item_type']['table'] = 'commerce_line_item';
  $handler->display->display_options['filters']['product_line_item_type']['field'] = 'product_line_item_type';
  $handler->display->display_options['filters']['product_line_item_type']['value'] = '1';
  $translatables['commerce_line_item_table'] = array(
    t('Defaults'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort by'),
    t('Asc'),
    t('Desc'),
    t('Empty line item text.'),
    t('No line items found.'),
    t('ID'),
    t('Type'),
    t('Title'),
    t('Label'),
    t('([line_item_label])'),
    t('Unit price'),
    t('Quantity'),
    t('.'),
    t(','),
    t('Total'),
    t('All'),
  );

  $views[$view->name] = $view;

  return $views;
}
