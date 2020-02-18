<?php

/**
 * @file
 * Post update functions for Product.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Revert the Products view because of the updated permission.
 */
function commerce_product_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'views.view.commerce_products',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Revert the default order item form display.
 */
function commerce_product_post_update_2() {
  if (!\Drupal::moduleHandler()->moduleExists('commerce_order')) {
    return '';
  }

  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'core.entity_form_display.commerce_order_item.default.default',
  ]);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Revert the default order item form display.
 */
function commerce_product_post_update_3() {
  if (!\Drupal::moduleHandler()->moduleExists('commerce_order')) {
    return '';
  }

  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'core.entity_form_display.commerce_order_item.default.default',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Expose the status field on every product form.
 */
function commerce_product_post_update_4() {
  $query = \Drupal::entityQuery('entity_form_display')->condition('targetEntityType', 'commerce_product');
  $ids = $query->execute();
  $form_displays = EntityFormDisplay::loadMultiple($ids);
  foreach ($form_displays as $id => $form_display) {
    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $form_display */
    $form_display->setComponent('status', [
      'type' => 'boolean_checkbox',
      'settings' => [
        'display_label' => TRUE,
      ],
    ])->save();
  }
}

/**
 * Enable the "Duplicate" variation button for every product type.
 */
function commerce_product_post_update_5() {
  $query = \Drupal::entityQuery('entity_form_display')->condition('targetEntityType', 'commerce_product');
  $ids = $query->execute();
  $form_displays = EntityFormDisplay::loadMultiple($ids);
  foreach ($form_displays as $id => $form_display) {
    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $form_display */
    $component = $form_display->getComponent('variations');
    if ($component['type'] == 'inline_entity_form_complex') {
      $component['settings']['allow_duplicate'] = TRUE;
      $form_display->setComponent('variations', $component);
      $form_display->save();
    }
  }
}

/**
 * Grants the "manage variations" permission to roles that can update products.
 */
function commerce_product_post_update_6() {
  $entity_type_manager = \Drupal::entityTypeManager();
  /** @var \Drupal\commerce_product\Entity\ProductTypeInterface[] $product_types */
  $product_types = $entity_type_manager->getStorage('commerce_product_type')->loadMultiple();
  /** @var \Drupal\user\RoleInterface[] $roles */
  $roles = $entity_type_manager->getStorage('user_role')->loadMultiple();

  foreach ($roles as $role) {
    foreach ($product_types as $product_type) {
      // If the role had any update permission, grant the manage permission.
      if (
        $role->hasPermission("update any {$product_type->id()} commerce_product") ||
        $role->hasPermission("update own {$product_type->id()} commerce_product")
      ) {
        $role->grantPermission("manage {$product_type->getVariationTypeId()} commerce_product_variation");
      }
    }
    $role->save();
  }
}

/**
 * Move the variations form to its own tab.
 */
function commerce_product_post_update_7() {
  $entity_type_manager = \Drupal::entityTypeManager();
  /** @var \Drupal\commerce_product\Entity\ProductTypeInterface[] $product_types */
  $product_types = $entity_type_manager->getStorage('commerce_product_type')->loadMultiple();
  foreach ($product_types as $product_type) {
    $form_display = commerce_get_entity_display('commerce_product', $product_type->id(), 'form');
    $form_display->removeComponent('variations');
    $form_display->save();
  }
}
