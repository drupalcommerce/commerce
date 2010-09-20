<?php
// $Id$

/**
 * Views for the default order UI.
 */

/**
 * Implements hook_views_default_views().
 */
function commerce_order_ui_views_default_views() {
  $views = array();

  // Order admin list at admin/commerce/orders.
  $view = new view;
  $view->name = 'commerce_orders';
  $view->description = 'Display a list of orders for the store admin.';
  $view->tag = '';
  $view->view_php = '';
  $view->base_table = 'commerce_order';
  $view->is_cacheable = FALSE;
  $view->api_version = '3.0-alpha1';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Defaults */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->display->display_options['title'] = 'Orders';
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'administer orders';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = 50;
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'order_number' => 'order_number',
    'name' => 'name',
    'created' => 'created',
    'status' => 'status',
    'edit_order' => 'edit_order',
    'delete_order' => 'edit_order',
  );
  $handler->display->display_options['style_options']['default'] = 'created';
  $handler->display->display_options['style_options']['info'] = array(
    'order_number' => array(
      'sortable' => 1,
      'align' => '',
      'separator' => '',
    ),
    'name' => array(
      'sortable' => 1,
      'align' => '',
      'separator' => '',
    ),
    'created' => array(
      'sortable' => 1,
      'align' => '',
      'separator' => '',
    ),
    'status' => array(
      'sortable' => 1,
      'align' => '',
      'separator' => '',
    ),
    'edit_order' => array(
      'align' => '',
      'separator' => ' &nbsp; &nbsp;',
    ),
    'delete_order' => array(
      'align' => '',
      'separator' => '',
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  $handler->display->display_options['style_options']['order'] = 'desc';
  /* Empty text: Global: Text area */
  $handler->display->display_options['empty']['text']['id'] = 'area';
  $handler->display->display_options['empty']['text']['table'] = 'views';
  $handler->display->display_options['empty']['text']['field'] = 'area';
  $handler->display->display_options['empty']['text']['empty'] = FALSE;
  $handler->display->display_options['empty']['text']['content'] = 'No orders have been created yet.';
  /* Relationship: Commerce Order: Customer */
  $handler->display->display_options['relationships']['customer_uid']['id'] = 'customer_uid';
  $handler->display->display_options['relationships']['customer_uid']['table'] = 'commerce_order';
  $handler->display->display_options['relationships']['customer_uid']['field'] = 'customer_uid';
  $handler->display->display_options['relationships']['customer_uid']['required'] = 0;
  /* Field: Commerce Order: Order number */
  $handler->display->display_options['fields']['order_number']['id'] = 'order_number';
  $handler->display->display_options['fields']['order_number']['table'] = 'commerce_order';
  $handler->display->display_options['fields']['order_number']['field'] = 'order_number';
  $handler->display->display_options['fields']['order_number']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['order_number']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['order_number']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['order_number']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['order_number']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['order_number']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['order_number']['alter']['html'] = 0;
  $handler->display->display_options['fields']['order_number']['hide_empty'] = 0;
  $handler->display->display_options['fields']['order_number']['empty_zero'] = 0;
  $handler->display->display_options['fields']['order_number']['link_to_order'] = 1;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'customer_uid';
  $handler->display->display_options['fields']['name']['label'] = 'Customer';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  /* Field: Commerce Order: Created date */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'commerce_order';
  $handler->display->display_options['fields']['created']['field'] = 'created';
  $handler->display->display_options['fields']['created']['label'] = 'Created';
  $handler->display->display_options['fields']['created']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['created']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['created']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['created']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['created']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['created']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['created']['alter']['html'] = 0;
  $handler->display->display_options['fields']['created']['hide_empty'] = 0;
  $handler->display->display_options['fields']['created']['empty_zero'] = 0;
  /* Field: Commerce Order: Order status */
  $handler->display->display_options['fields']['status']['id'] = 'status';
  $handler->display->display_options['fields']['status']['table'] = 'commerce_order';
  $handler->display->display_options['fields']['status']['field'] = 'status';
  $handler->display->display_options['fields']['status']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['status']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['status']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['status']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['status']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['status']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['status']['alter']['html'] = 0;
  $handler->display->display_options['fields']['status']['hide_empty'] = 0;
  $handler->display->display_options['fields']['status']['empty_zero'] = 0;
  $handler->display->display_options['fields']['status']['link_to_order'] = 0;
  /* Field: Commerce Order: Edit link */
  $handler->display->display_options['fields']['edit_order']['id'] = 'edit_order';
  $handler->display->display_options['fields']['edit_order']['table'] = 'commerce_order';
  $handler->display->display_options['fields']['edit_order']['field'] = 'edit_order';
  $handler->display->display_options['fields']['edit_order']['label'] = 'Operations';
  $handler->display->display_options['fields']['edit_order']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['edit_order']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['edit_order']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['edit_order']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['edit_order']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['edit_order']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['edit_order']['alter']['html'] = 0;
  $handler->display->display_options['fields']['edit_order']['hide_empty'] = 0;
  $handler->display->display_options['fields']['edit_order']['empty_zero'] = 0;
  /* Field: Commerce Order: Delete link */
  $handler->display->display_options['fields']['delete_order']['id'] = 'delete_order';
  $handler->display->display_options['fields']['delete_order']['table'] = 'commerce_order';
  $handler->display->display_options['fields']['delete_order']['field'] = 'delete_order';
  $handler->display->display_options['fields']['delete_order']['label'] = '';
  $handler->display->display_options['fields']['delete_order']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['delete_order']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['delete_order']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['delete_order']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['delete_order']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['delete_order']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['delete_order']['alter']['html'] = 0;
  $handler->display->display_options['fields']['delete_order']['hide_empty'] = 0;
  $handler->display->display_options['fields']['delete_order']['empty_zero'] = 0;

  /* Display: Admin page */
  $handler = $view->new_display('page', 'Admin page', 'admin_page');
  $handler->display->display_options['path'] = 'admin/commerce/orders/list';
  $handler->display->display_options['menu']['type'] = 'default tab';
  $handler->display->display_options['menu']['title'] = 'List';
  $handler->display->display_options['menu']['weight'] = '-10';
  $handler->display->display_options['tab_options']['type'] = 'normal';
  $handler->display->display_options['tab_options']['title'] = 'Orders';
  $handler->display->display_options['tab_options']['description'] = 'Manage orders in the store.';
  $handler->display->display_options['tab_options']['weight'] = '';
  $handler->display->display_options['tab_options']['name'] = 'management';

  $views[$view->name] = $view;

  return $views;
}
