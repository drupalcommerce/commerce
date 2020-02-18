<?php

/**
 * @file
 * Post update functions for Order.
 */

use Drupal\profile\Entity\ProfileType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Revert Order views to fix broken Price fields.
 */
function commerce_order_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'views.view.commerce_order_item_table',
    'views.view.commerce_user_orders',
    'views.view.commerce_orders',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

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
  $result = $config_updater->revert([
    'core.entity_view_display.commerce_order.default.default',
    'core.entity_view_display.commerce_order.default.user',
    'core.entity_view_display.profile.customer.default',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Revert the Order entity view displays.
 */
function commerce_order_post_update_4() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'core.entity_view_display.commerce_order.default.default',
    'core.entity_view_display.commerce_order.default.user',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Revert the Order entity form display.
 */
function commerce_order_post_update_5() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'core.entity_form_display.commerce_order.default.default',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

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
  $result = $config_updater->revert([
    'views.view.commerce_order_item_table',
  ]);
  $message = implode('<br>', $result->getFailed());

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

/**
 * Update the customer profile type.
 */
function commerce_order_post_update_10() {
  $profile_type = ProfileType::load('customer');
  if ($profile_type) {
    $profile_type->setDisplayLabel('Customer information');
    $profile_type->setThirdPartySetting('commerce_order', 'customer_profile_type', TRUE);
    $profile_type->save();
  }
}

/**
 * Add the "admin" view mode to profiles.
 */
function commerce_order_post_update_11() {
  if (!ProfileType::load('customer')) {
    // Commerce expects the "customer" profile type to always be present,
    // but some sites have still succeeded in removing it.
    return '';
  }

  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->import([
    'core.entity_view_mode.profile.admin',
    'core.entity_view_display.profile.customer.admin',
  ]);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Create the default number pattern.
 */
function commerce_order_post_update_12() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->import([
    'commerce_number_pattern.commerce_number_pattern.order_default',
  ]);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Create the "billing" form mode for profiles.
 */
function commerce_order_post_update_13() {
  if (EntityFormMode::load('profile.billing')) {
    return '';
  }

  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->import([
    'core.entity_form_mode.profile.billing',
  ]);
  $message = implode('<br>', $result->getFailed());

  return $message;
}
