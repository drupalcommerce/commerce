<?php
// $Id$

/**
 * @file
 * Rules integration for line items.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_event_info().
 */
function commerce_line_item_rules_event_info() {
  $items = array(
    'commerce_line_item_insert' => array(
      'label' => t('After saving a new line item'),
      'group' => t('Commerce'),
      'variables' => commerce_line_item_rules_variables(t('Created line item')),
      'access callback' => 'commerce_line_item_rules_access',
    ),
    'commerce_line_item_update' => array(
      'label' => t('After updating an existing line item'),
      'group' => t('Commerce'),
      'variables' => commerce_line_item_rules_variables(t('Updated line item'), TRUE),
      'access callback' => 'commerce_line_item_rules_access',
    ),
    'commerce_line_item_presave_insert' => array(
      'label' => t('Before saving a new line item'),
      'group' => t('Commerce'),
      'variables' => commerce_line_item_rules_variables(t('Saved line item'), TRUE),
      'access callback' => 'commerce_line_item_rules_access',
    ),
    'commerce_line_item_presave_update' => array(
      'label' => t('Before saving an updated line item'),
      'group' => t('Commerce'),
      'variables' => commerce_line_item_rules_variables(t('Saved line item'), TRUE),
      'access callback' => 'commerce_line_item_rules_access',
    ),
    'commerce_line_item_delete' => array(
      'label' => t('After deleting a line item'),
      'group' => t('Commerce'),
      'variables' => commerce_line_item_rules_variables(t('Deleted line item')),
      'access callback' => 'commerce_line_item_rules_access',
    ),
  );

  // Specify that on presave the line item is saved anyway.
  $items['commerce_line_item_presave_insert']['variables']['line_item']['skip save'] = TRUE;
  $items['commerce_line_item_presave_update']['variables']['line_item']['skip save'] = TRUE;

  return $items;
}

/**
 * Returns some parameter suitable for using it with a node
 */
function commerce_line_item_rules_variables($label, $update = FALSE) {
  $args = array(
    'line_item' => array(
      'type' => 'commerce_line_item',
      'label' => $label,
    ),
  );

  if ($update) {
    $args += array(
      'line_item_unchanged' => array(
        'type' => 'commerce_line_item',
        'label' => t('Unchanged line item'),
        'handler' => 'rules_events_entity_unchanged',
      ),
    );
  }

  return $args;
}

/**
 * Rules integration access callback.
 */
function commerce_line_item_rules_access($type, $name) {
  if ($type == 'event' || $type == 'condition') {
    return TRUE;
  }
}

/**
 * @}
 */
