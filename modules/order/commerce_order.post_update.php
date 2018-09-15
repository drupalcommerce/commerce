<?php

/**
 * @file
 * Post update functions for Order.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Revert Order views to fix broken Price fields.
 */
function commerce_order_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');

  $views = [
    'views.view.commerce_order_item_table',
    'views.view.commerce_user_orders',
    'views.view.commerce_orders',
  ];
  $result = $config_updater->revert($views, FALSE);

  $success_results = $result->getSucceeded();
  $failure_results = $result->getFailed();
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
 * Update order types.
 */
function commerce_order_post_update_2() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $order_type_storage = $entity_type_manager->getStorage('commerce_order_type');
  /** @var \Drupal\commerce_order\Entity\OrderTypeInterface[] $order_types */
  $order_types = $order_type_storage->loadMultiple();
  foreach ($order_types as $order_type) {
    if ($order_type->getRefreshMode() == 'owner_only') {
      $order_type->setRefreshMode('customer');
      $order_type->save();
    }
  }
}

/**
 * Revert the Order and Profile entity view displays.
 */
function commerce_order_post_update_3() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');

  $views = [
    'core.entity_view_display.commerce_order.default.default',
    'core.entity_view_display.commerce_order.default.user',
    'core.entity_view_display.profile.customer.default',
  ];
  $result = $config_updater->revert($views, FALSE);

  $success_results = $result->getSucceeded();
  $failure_results = $result->getFailed();
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
 * Revert the Order entity view displays.
 */
function commerce_order_post_update_4() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');

  $views = [
    'core.entity_view_display.commerce_order.default.default',
    'core.entity_view_display.commerce_order.default.user',
  ];
  $result = $config_updater->revert($views, FALSE);

  $success_results = $result->getSucceeded();
  $failure_results = $result->getFailed();
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
 * Revert the Order entity form display.
 */
function commerce_order_post_update_5() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');

  $views = [
    'core.entity_form_display.commerce_order.default.default',
  ];
  $result = $config_updater->revert($views, FALSE);

  $success_results = $result->getSucceeded();
  $failure_results = $result->getFailed();
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
 * Update the profile address field.
 */
function commerce_order_post_update_6() {
  // Remove the default_country setting from any profile form.
  // That allows Commerce to apply its own default taken from the store.
  $query = \Drupal::entityQuery('entity_form_display')->condition('targetEntityType', 'profile');
  $ids = $query->execute();
  $form_displays = EntityFormDisplay::loadMultiple($ids);
  foreach ($form_displays as $id => $form_display) {
    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $form_display */
    if ($component = $form_display->getComponent('address')) {
      $component['settings'] = [];
      $form_display->setComponent('address', $component);
      $form_display->save();
    }
  }
}

/**
 * Revert the 'commerce_order_item_table' view - empty text added.
 */
function commerce_order_post_update_7() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');

  $views = [
    'views.view.commerce_order_item_table',
  ];
  $result = $config_updater->revert($views);

  $success_results = $result->getSucceeded();
  $failure_results = $result->getFailed();
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
 * Unlock the profile 'address' field.
 */
function commerce_order_post_update_8() {
  $field = FieldStorageConfig::loadByName('profile', 'address');
  if ($field) {
    $field->setLocked(FALSE);
    $field->save();
  }
}

/**
 * Grants the "manage order items" permission to roles that can update orders.
 */
function commerce_order_post_update_9() {
  $entity_type_manager = \Drupal::entityTypeManager();
  /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface[] $order_item_types */
  $order_item_types = $entity_type_manager->getStorage('commerce_order_item_type')->loadMultiple();
  /** @var \Drupal\user\RoleInterface[] $roles */
  $roles = $entity_type_manager->getStorage('user_role')->loadMultiple();

  $order_type_storage = $entity_type_manager->getStorage('commerce_order_type');
  foreach ($roles as $role) {
    foreach ($order_item_types as $order_item_type) {
      $order_type = $order_type_storage->load($order_item_type->getOrderTypeId());
      // If the role can update the order type, then it can also manage the
      // order items of this bundle.
      if ($order_type && $role->hasPermission("update {$order_type->id()} commerce_order")) {
        $role->grantPermission("manage {$order_item_type->id()} commerce_order_item");
      }
    }
    $role->save();
  }
}
