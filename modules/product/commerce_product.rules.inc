<?php
// $Id$

/**
 * @file
 * Rules integration for products.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_event_info().
 */
function commerce_product_rules_event_info() {
  $items = array(
    'commerce_product_insert' => array(
      'label' => t('After saving a new product'),
      'group' => t('Commerce'),
      'variables' => commerce_product_rules_variables(t('Created product')),
      'access callback' => 'commerce_product_rules_access',
    ),
    'commerce_product_update' => array(
      'label' => t('After updating an existing product'),
      'group' => t('Commerce'),
      'variables' => commerce_product_rules_variables(t('Updated product'), TRUE),
      'access callback' => 'commerce_product_rules_access',
    ),
    'commerce_product_presave' => array(
      'label' => t('Before saving a product'),
      'group' => t('Commerce'),
      'variables' => commerce_product_rules_variables(t('Saved product'), TRUE),
      'access callback' => 'commerce_product_rules_access',
    ),
    'commerce_product_delete' => array(
      'label' => t('After deleting a product'),
      'group' => t('Commerce'),
      'variables' => commerce_product_rules_variables(t('Deleted product')),
      'access callback' => 'commerce_product_rules_access',
    ),
  );
  // Specify that on presave the product is saved anyway.
  $items['commerce_product_presave']['variables']['product']['skip save'] = TRUE;
  return $items;
}

/**
 * Returns some parameter suitable for using it with a node
 */
function commerce_product_rules_variables($label, $update = FALSE) {
  $args = array(
    'product' => array(
      'type' => 'commerce_product',
      'label' => $label,
    ),
  );

  if ($update) {
    $args += array(
      'product_unchanged' => array(
        'type' => 'commerce_product',
        'label' => t('Unchanged product'),
        'handler' => 'rules_events_entity_unchanged',
      ),
    );
  }

  return $args;
}

/**
 * Rules integration access callback.
 */
function commerce_product_rules_access($type, $name) {
  if ($type == 'event' || $type == 'condition') {
    return commerce_product_access('view');
  }
}

/**
 * @}
 */
