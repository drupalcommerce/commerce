<?php

/**
 * @file
 * Post update functions for Checkout.
 */

/**
 * Revert Checkout views to fix broken Price fields.
 */
function commerce_checkout_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'views.view.commerce_checkout_order_summary',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

  return $message;
}
