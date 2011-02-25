<?php

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
  $events = array();

  $events['commerce_product_presave'] = array(
    'label' => t('Before saving a product'),
    'group' => t('Commerce Product'),
    'variables' => commerce_product_rules_event_variables(t('Product'), TRUE, TRUE),
    'access callback' => 'commerce_product_rules_access',
  );
  $events['commerce_product_insert'] = array(
    'label' => t('After saving a new product'),
    'group' => t('Commerce Product'),
    'variables' => commerce_product_rules_event_variables(t('Created product'), TRUE),
    'access callback' => 'commerce_product_rules_access',
  );
  $events['commerce_product_update'] = array(
    'label' => t('After updating an existing product'),
    'group' => t('Commerce Product'),
    'variables' => commerce_product_rules_event_variables(t('Updated product'), TRUE),
    'access callback' => 'commerce_product_rules_access',
  );
  $events['commerce_product_delete'] = array(
    'label' => t('After deleting a product'),
    'group' => t('Commerce Product'),
    'variables' => commerce_product_rules_event_variables(t('Deleted product')),
    'access callback' => 'commerce_product_rules_access',
  );

  return $events;
}

/**
 * Returns a variables array for product events.
 *
 * @param $label
 *   The label for the primary product variable.
 * @param $unchanged
 *   Boolean indicating whether or not to include the unchanged product.
 * @param $skip_save
 *   Boolean indicating whether or not the primary product variable should skip
 *     saving after event execution.
 */
function commerce_product_rules_event_variables($label, $unchanged = FALSE, $skip_save = FALSE) {
  $variables = array(
    'product' => array(
      'type' => 'commerce_product',
      'label' => $label,
      'skip save' => $skip_save,
    ),
  );

  if ($unchanged) {
    $variables['product_unchanged'] = array(
      'type' => 'commerce_product',
      'label' => t('Unchanged product'),
      'skip save' => TRUE,
      'handler' => 'rules_events_entity_unchanged',
    );
  }

  return $variables;
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
