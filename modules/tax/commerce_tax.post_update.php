<?php

/**
 * @file
 * Post update functions for Tax.
 */

use Drupal\profile\Entity\ProfileType;

/**
 * Add the tax_number field to customer profiles.
 */
function commerce_tax_post_update_1() {
  if (!\Drupal::moduleHandler()->moduleExists('commerce_order')) {
    return '';
  }
  if (!ProfileType::load('customer')) {
    // Commerce expects the "customer" profile type to always be present,
    // but some sites have still succeeded in removing it.
    return '';
  }

  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->import([
    'field.storage.profile.tax_number',
    'field.field.profile.customer.tax_number',
  ]);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Add the tax_number field to customer profile view displays.
 */
function commerce_tax_post_update_2() {
  // Expose the tax_number field on customer profile view displays.
  $storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
  /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $default_display */
  $default_display = $storage->load('profile.customer.default');
  if ($default_display) {
    $default_display->setComponent('tax_number', [
      'type' => 'commerce_tax_number_default',
      'settings' => [
        'show_verification' => FALSE,
      ],
    ]);
    $default_display->save();
  }

  /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $admin_display */
  $admin_display = $storage->load('profile.customer.admin');
  if ($admin_display) {
    $admin_display->setComponent('tax_number', [
      'type' => 'commerce_tax_number_default',
      'settings' => [
        'show_verification' => TRUE,
      ],
    ]);
    $admin_display->save();
  }
}
