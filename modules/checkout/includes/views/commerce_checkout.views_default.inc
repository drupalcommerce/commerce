<?php

/**
 * Implements hook_views_default_views_alter().
 *
 * Add the checkout state to the default commerce_orders (admin)
 * view.
 */
function commerce_checkout_views_default_views_alter(&$views) {
  if(isset($views['commerce_orders'])) {
    $views['commerce_orders']->display['default']->display_options['filters']['state']['value']['checkout'] = 'checkout';
  }
}