<?php

/**
 * Implements hook_entity_property_info_alter().
 *
 * Add UI bound order properties.
 */
function commerce_order_ui_entity_property_info_alter(&$info) {
  $info['commerce_order']['properties']['view_url'] = array(
    'label' => t('View URL'),
    'description' => t('The URL a customer can visit to view the order.'),
    'getter callback' => 'commerce_order_get_properties',
    'type' => 'uri',
  );
  $info['commerce_order']['properties']['admin_url'] = array(
    'label' => t('Admin URL'),
    'description' => t("The URL of the order's administrative view page."),
    'getter callback' => 'commerce_order_get_properties',
    'type' => 'uri',
  );
  $info['commerce_order']['properties']['edit_url'] = array(
    'label' => t('Edit URL'),
    'description' => t("The URL of the order's edit page."),
    'getter callback' => 'commerce_order_get_properties',
    'type' => 'uri',
  );
}