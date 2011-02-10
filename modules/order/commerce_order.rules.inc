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
    'variables' => commerce_order_rules_event_variables(t('Order', array(), array('context' => 'a drupal commerce order')), TRUE, TRUE),
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
 * Implements hook_rules_action_info().
 */
function commerce_order_rules_action_info() {
  $actions = array();

  $actions['commerce_order_update_state'] = array(
    'label' => t('Update the order state'),
    'parameter' => array(
      'order' => array(
        'type' => 'commerce_order',
        'label' => t('Order to update'),
      ),
      'order_state' => array(
        'type' => 'text',
        'label' => t('Order state'),
        'description' => t('Select the order state whose default status the order will be updated to.'),
        'options list' => 'commerce_order_state_options_list',
      ),
    ),
    'group' => t('Commerce Order'),
    'callbacks' => array(
      'execute' => 'commerce_order_rules_update_state',
    ),
  );

  $actions['commerce_order_update_status'] = array(
    'label' => t('Update the order status'),
    'parameter' => array(
      'order' => array(
        'type' => 'commerce_order',
        'label' => t('Order to update'),
      ),
      'order_status' => array(
        'type' => 'text',
        'label' => t('Order status'),
        'options list' => 'commerce_order_status_options_list',
      ),
    ),
    'group' => t('Commerce Order'),
    'callbacks' => array(
      'execute' => 'commerce_order_rules_update_status',
    ),
  );

  return $actions;
}

/**
 * Rules action: updates an order's status to the default status of the given
 *   order state.
 */
function commerce_order_rules_update_state($order, $name) {
  $order_state = commerce_order_state_load($name);
  commerce_order_status_update($order, $order_state['default_status']);
}

/**
 * Rules action: updates an order's status using the Order API.
 */
function commerce_order_rules_update_status($order, $name) {
  commerce_order_status_update($order, $name);
}

/**
 * @}
 */
