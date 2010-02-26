<?php
// $Id$

/**
 * Views for the default product UI.
 */

/**
 * Implements hook_views_default_views().
 */
function commerce_product_ui_views_default_views() {
  $views = array();

  // Products admin list at admin/commerce/products.
  $view = new view;
  $view->name = 'Products';
  $view->description = '';
  $view->tag = '';
  $view->view_php = '';
  $view->base_table = 'commerce_product';
  $view->is_cacheable = FALSE;
  $view->api_version = 2;
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Defaults */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->display->display_options['title'] = 'Products';
  $handler->display->display_options['empty'] = 'No products have been created yet.';
  $handler->display->display_options['items_per_page'] = 50;
  $handler->display->display_options['use_pager'] = TRUE;
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'administer products';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'product_sku' => 'product_sku',
    'title' => 'title',
    'product_type' => 'product_type',
    'edit_product' => 'edit_product',
    'delete_product' => 'edit_product',
  );
  $handler->display->display_options['style_options']['default'] = 'product_sku';
  $handler->display->display_options['style_options']['info'] = array(
    'product_sku' => array(
      'sortable' => 1,
      'align' => '',
      'separator' => '',
    ),
    'title' => array(
      'sortable' => 1,
      'align' => '',
      'separator' => '',
    ),
    'product_type' => array(
      'sortable' => 1,
      'align' => '',
      'separator' => '',
    ),
    'edit_product' => array(
      'align' => '',
      'separator' => ' &nbsp; &nbsp;',
    ),
    'delete_product' => array(
      'align' => '',
      'separator' => '',
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  /* Field: Commerce Product: SKU */
  $handler->display->display_options['fields']['product_sku']['id'] = 'product_sku';
  $handler->display->display_options['fields']['product_sku']['table'] = 'commerce_product';
  $handler->display->display_options['fields']['product_sku']['field'] = 'product_sku';
  $handler->display->display_options['fields']['product_sku']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['product_sku']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['product_sku']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['product_sku']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['product_sku']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['product_sku']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['product_sku']['alter']['html'] = 0;
  $handler->display->display_options['fields']['product_sku']['hide_empty'] = 0;
  $handler->display->display_options['fields']['product_sku']['empty_zero'] = 0;
  $handler->display->display_options['fields']['product_sku']['link_to_product'] = 0;
  /* Field: Commerce Product: Title */
  $handler->display->display_options['fields']['title']['id'] = 'title';
  $handler->display->display_options['fields']['title']['table'] = 'commerce_product';
  $handler->display->display_options['fields']['title']['field'] = 'title';
  $handler->display->display_options['fields']['title']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['title']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['title']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['title']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['title']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['title']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['title']['alter']['html'] = 0;
  $handler->display->display_options['fields']['title']['hide_empty'] = 0;
  $handler->display->display_options['fields']['title']['empty_zero'] = 0;
  $handler->display->display_options['fields']['title']['link_to_product'] = 1;
  /* Field: Commerce Product: Type */
  $handler->display->display_options['fields']['product_type']['id'] = 'product_type';
  $handler->display->display_options['fields']['product_type']['table'] = 'commerce_product';
  $handler->display->display_options['fields']['product_type']['field'] = 'product_type';
  $handler->display->display_options['fields']['product_type']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['product_type']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['product_type']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['product_type']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['product_type']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['product_type']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['product_type']['alter']['html'] = 0;
  $handler->display->display_options['fields']['product_type']['hide_empty'] = 0;
  $handler->display->display_options['fields']['product_type']['empty_zero'] = 0;
  /* Field: Commerce Product: Edit link */
  $handler->display->display_options['fields']['edit_product']['id'] = 'edit_product';
  $handler->display->display_options['fields']['edit_product']['table'] = 'commerce_product';
  $handler->display->display_options['fields']['edit_product']['field'] = 'edit_product';
  $handler->display->display_options['fields']['edit_product']['label'] = 'Operations';
  $handler->display->display_options['fields']['edit_product']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['edit_product']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['edit_product']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['edit_product']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['edit_product']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['edit_product']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['edit_product']['alter']['html'] = 0;
  $handler->display->display_options['fields']['edit_product']['hide_empty'] = 0;
  $handler->display->display_options['fields']['edit_product']['empty_zero'] = 0;
  /* Field: Commerce Product: Delete link */
  $handler->display->display_options['fields']['delete_product']['id'] = 'delete_product';
  $handler->display->display_options['fields']['delete_product']['table'] = 'commerce_product';
  $handler->display->display_options['fields']['delete_product']['field'] = 'delete_product';
  $handler->display->display_options['fields']['delete_product']['label'] = '';
  $handler->display->display_options['fields']['delete_product']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['delete_product']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['delete_product']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['delete_product']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['delete_product']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['delete_product']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['delete_product']['alter']['html'] = 0;
  $handler->display->display_options['fields']['delete_product']['hide_empty'] = 0;
  $handler->display->display_options['fields']['delete_product']['empty_zero'] = 0;
  /* Sort criterion: Commerce Product: SKU */
  $handler->display->display_options['sorts']['product_sku']['id'] = 'product_sku';
  $handler->display->display_options['sorts']['product_sku']['table'] = 'commerce_product';
  $handler->display->display_options['sorts']['product_sku']['field'] = 'product_sku';

  /* Display: Admin page */
  $handler = $view->new_display('page', 'Admin page', 'admin_page');
  $handler->display->display_options['path'] = 'admin/commerce/products/list';
  $handler->display->display_options['menu']['type'] = 'default tab';
  $handler->display->display_options['menu']['title'] = 'Products';
  $handler->display->display_options['menu']['weight'] = '-1';
  $handler->display->display_options['tab_options']['type'] = 'normal';
  $handler->display->display_options['tab_options']['title'] = 'Products';
  $handler->display->display_options['tab_options']['description'] = 'Manage products from the store.';
  $handler->display->display_options['tab_options']['weight'] = '';

  $views[$view->name] = $view;

  return $views;
}
