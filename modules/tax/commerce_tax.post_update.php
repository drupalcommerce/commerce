<?php

/**
 * @file
 * Post update functions for Tax.
 */

/**
 * Add the tax_number field to customer profiles.
 */
function commerce_tax_post_update_1() {
  if (!\Drupal::moduleHandler()->moduleExists('commerce_order')) {
    return '';
  }
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $config_names = [
    'field.storage.profile.tax_number',
    'field.field.profile.customer.tax_number',
  ];
  $result = $config_updater->import($config_names);

  $success_results = $result->getSucceeded();
  $failure_results = $result->getFailed();
  $message = '';
  if ($success_results) {
    $message = t('Succeeded:') . '<br>';
    foreach ($success_results as $success_message) {
      $message .= $success_message . '<br>';
    }
    $message .= '<br>';
  }
  if ($failure_results) {
    $message .= t('Failed:') . '<br>';
    foreach ($failure_results as $failure_message) {
      $message .= $failure_message . '<br>';
    }
  }

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
