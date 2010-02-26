<?php
// $Id$

/**
 * Views for the default product UI.
 */

/**
 * Implements hook_views_default_views().
 */
function commerce_product_ui_views_default_views() {
  $view = new view;
  $view->name = 'Products';
  $view->description = '';
  $view->tag = '';
  $view->view_php = '';
  $view->base_table = 'commerce_product';
  $view->is_cacheable = FALSE;
  $view->api_version = 2;
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->override_option('fields', array(
    'product_sku' => array(
      'label' => 'SKU',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'link_class' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'target' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'strip_tags' => 0,
        'html' => 0,
      ),
      'empty' => '',
      'hide_empty' => 0,
      'empty_zero' => 0,
      'exclude' => 0,
      'id' => 'product_sku',
      'table' => 'commerce_product',
      'field' => 'product_sku',
      'override' => array(
        'button' => 'Override',
      ),
      'relationship' => 'none',
    ),
    'title' => array(
      'label' => 'Title',
      'alter' => array(
        'alter_text' => 0,
        'make_link' => 0,
        'trim' => 0,
        'word_boundary' => 1,
        'ellipsis' => 1,
        'strip_tags' => 0,
        'html' => 0,
      ),
      'hide_empty' => 0,
      'empty_zero' => 0,
      'id' => 'title',
      'table' => 'commerce_product',
      'field' => 'title',
    ),
  ));
  $handler->override_option('sorts', array(
    'product_sku' => array(
      'order' => 'ASC',
      'exposed' => FALSE,
      'expose' => array(
        'label' => '',
      ),
      'id' => 'product_sku',
      'table' => 'commerce_product',
      'field' => 'product_sku',
      'override' => array(
        'button' => 'Override',
      ),
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('access', array(
    'type' => 'perm',
    'perm' => 'administer products',
  ));
  $handler->override_option('cache', array(
    'type' => 'none',
  ));
  $handler->override_option('title', 'Products');
  $handler->override_option('empty', 'There are no products yet.');
  $handler->override_option('items_per_page', 50);
  $handler->override_option('use_pager', '1');
  $handler->override_option('style_plugin', 'table');
  $handler->override_option('style_options', array(
    'grouping' => '',
    'override' => 1,
    'sticky' => 0,
    'order' => 'asc',
    'columns' => array(
      'product_id' => 'product_id',
    ),
    'info' => array(
      'product_id' => array(
        'sortable' => 1,
        'separator' => '',
      ),
    ),
    'default' => 'product_id',
  ));
  $handler->override_option('exposed_form', array(
    'type' => 'basic',
    'options' => array(),
  ));
  $handler = $view->new_display('page', 'Admin page', 'admin_page');
  $handler->override_option('path', 'admin/commerce/products/list');
  $handler->override_option('menu', array(
    'type' => 'default tab',
    'title' => 'Products',
    'description' => '',
    'weight' => '-1',
    'name' => 'navigation',
  ));
  $handler->override_option('tab_options', array(
    'type' => 'normal',
    'title' => 'Products',
    'description' => 'Manage products from the store.',
    'weight' => '',
  ));
  $views[$view->name] = $view;

  return $views;
}
