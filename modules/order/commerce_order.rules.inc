<?php
// $Id$

/**
 * @file
 * Rules integration for orders.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_event_info().
 */
function commerce_order_rules_event_info() {
  $items = array(
    'commerce_order_insert' => array(
      'label' => t('After saving a new order'),
      'group' => t('Commerce'),
      'variables' => commerce_order_rules_variables(t('Created order')),
      'access callback' => 'commerce_order_rules_access',
    ),
    'commerce_order_update' => array(
      'label' => t('After updating an existing order'),
      'group' => t('Commerce'),
      'variables' => commerce_order_rules_variables(t('Updated order'), TRUE),
      'access callback' => 'commerce_order_rules_access',
    ),
    'commerce_order_presave' => array(
      'label' => t('Before saving an order'),
      'group' => t('Commerce'),
      'variables' => commerce_order_rules_variables(t('Saved order'), TRUE),
      'access callback' => 'commerce_order_rules_access',
    ),
    'commerce_order_delete' => array(
      'label' => t('After deleting an order'),
      'group' => t('Commerce'),
      'variables' => commerce_order_rules_variables(t('Deleted order')),
      'access callback' => 'commerce_order_rules_access',
    ),
  );

  // Specify that on presave the order is saved anyway.
  $items['commerce_order_presave']['variables']['order']['skip save'] = TRUE;

  return $items;
}

/**
 * Returns some parameter suitable for using it with a node
 */
function commerce_order_rules_variables($label, $update = FALSE) {
  $args = array(
    'order' => array(
      'type' => 'commerce_order',
      'label' => $label,
    ),
  );

  if ($update) {
    $args += array(
      'order_unchanged' => array(
        'type' => 'commerce_order',
        'label' => t('Unchanged order'),
        'handler' => 'rules_events_entity_unchanged',
      ),
    );
  }

  return $args;
}

/**
 * Rules integration access callback.
 */
function commerce_order_rules_access($type, $name) {
  if ($type == 'event' || $type == 'condition') {
    return commerce_order_access('view');
  }
}

/**
 * @}
 */
