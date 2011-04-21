<?php

/**
 * Implements hook_entity_property_info_alter().
 *
 * Add UI bound product properties.
 */
function commerce_product_ui_entity_property_info_alter(&$info) {
  $info['commerce_product']['properties']['edit_url'] = array(
    'label' => t('Edit URL'),
    'description' => t("The URL of the product's edit page."),
    'getter callback' => 'commerce_product_get_properties',
    'type' => 'uri',
  );
}