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
  $events = array();

  $events['commerce_order_presave'] = array(
    'label' => t('Before saving an order'),
    'group' => t('Commerce Order'),
    'variables' => commerce_order_rules_event_variables(t('Order'), TRUE, TRUE),
    'access callback' => 'commerce_order_rules_access',
  );
  $events['commerce_order_insert'] = array(
    'label' => t('After saving a new order'),
    'group' => t('Commerce Order'),
    'variables' => commerce_order_rules_event_variables(t('Created order'), TRUE),
    'access callback' => 'commerce_order_rules_access',
  );
  $events['commerce_order_update'] = array(
    'label' => t('After updating an existing order'),
    'group' => t('Commerce Order'),
    'variables' => commerce_order_rules_event_variables(t('Updated order'), TRUE),
    'access callback' => 'commerce_order_rules_access',
  );
  $events['commerce_order_delete'] = array(
    'label' => t('After deleting an order'),
    'group' => t('Commerce Order'),
    'variables' => commerce_order_rules_event_variables(t('Deleted order')),
    'access callback' => 'commerce_order_rules_access',
  );

  return $events;
}

/**
 * Returns a variables array for order events.
 *
 * @param $label
 *   The label for the primary order variable.
 * @param $unchanged
 *   Boolean indicating whether or not to include the unchanged order.
 * @param $skip_save
 *   Boolean indicating whether or not the primary order variable should skip
 *     saving after event execution.
 */
function commerce_order_rules_event_variables($label, $unchanged = FALSE, $skip_save = FALSE) {
  $variables = array(
    'order' => array(
      'type' => 'commerce_order',
      'label' => $label,
      'skip save' => $skip_save,
    ),
  );

  if ($unchanged) {
    $variables['order_unchanged'] = array(
      'type' => 'commerce_order',
      'label' => t('Unchanged order'),
      'skip save' => TRUE,
      'handler' => 'rules_events_entity_unchanged',
    );
  }

  return $variables;
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
