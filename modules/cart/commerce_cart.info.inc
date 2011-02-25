<?php

/**
 * @file
 * Provides metadata for the shopping cart order.
 */

/**
 * Implements hook_entity_property_info_alter().
 */
function commerce_cart_entity_property_info_alter(&$info) {
  // Add the current user's shopping cart to the site information.
  $info['site']['properties']['current_cart_order'] = array(
    'label' => t("User's shopping cart order"),
    'description' => t('The shopping cart order belonging to the current user.'),
    'getter callback' => 'commerce_cart_get_properties',
    'type' => 'commerce_order',
  );
}
