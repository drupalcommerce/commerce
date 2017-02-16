<?php

/**
 * @file
 * Provides views execution hooks for Cart.
 */

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;

/**
 * Implements hook_views_query_alter().
 */
function commerce_cart_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
  if ($view->id() == 'commerce_orders') {
    // Filter out carts, they have their own tab.
    $query->addWhere(0, 'cart', 1, '<>');
  }
}
