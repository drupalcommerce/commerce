<?php

/**
 * @file
 * Post update functions for Log.
 */

/**
 * Revert the Activity view to make the date column sortable.
 */
function commerce_log_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'views.view.commerce_activity',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Revert the Activity view to change page limit and ordering.
 */
function commerce_log_post_update_2() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'views.view.commerce_activity',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

  return $message;
}
