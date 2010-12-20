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
 * Implements hook_rules_action_info().
 */
function commerce_line_item_rules_action_info() {
  $actions = array();

  // Prepare an array of arithmetical actions for altering prices.
  $action_types = array(
    'commerce_line_item_unit_price_add' => array(
      'label' => t('Add an amount to the unit price'),
      'amount description' => t('Specify a numeric amount to add to the unit price.'),
    ),
    'commerce_line_item_unit_price_subtract' => array(
      'label' => t('Subtract an amount from the unit price'),
      'amount description' => t('Specify a numeric amount to subtract from the price.'),
    ),
    'commerce_line_item_unit_price_multiply' => array(
      'label' => t('Multiply the unit price by some amount'),
      'amount description' => t('Specify the numeric amount by which to multiply the price.'),
    ),
    'commerce_line_item_unit_price_divide' => array(
      'label' => t('Divide the unit price by some amount'),
      'amount description' => t('Specify a numeric amount by which to divide the price.'),
    ),
    'commerce_line_item_unit_price_amount' => array(
      'label' => t('Set the unit price to a specific amount'),
      'amount description' => t('Specify the numeric amount to set the price to.'),
    ),
  );

  // Define the action using a common set of parameters.
  foreach ($action_types as $key => $value) {
    $actions[$key] = array(
      'label' => $value['label'],
      'parameter' => array(
        'line_item' => array(
          'type' => 'commerce_line_item',
          'label' => t('Line item'),
        ),
        'amount' => array(
          'type' => 'decimal',
          'label' => t('Amount'),
          'description' => $value['amount description'],
        ),
      ),
      'group' => t('Commerce line item'),
    );
  }

  $actions['commerce_line_item_unit_price_currency_code'] = array(
    'label' => t("Set the unit price's currency code"),
    'parameter' => array(
      'line_item' => array(
        'type' => 'commerce_line_item',
        'label' => t('Line item'),
      ),
      'currency_code' => array(
        'type' => 'text',
        'label' => t('Currency'),
        'options list' => 'commerce_currency_get_code',
      ),
    ),
    'group' => t('Commerce line item'),
  );

  return $actions;
}

/**
 * Rules action: add an amount to the unit price.
 */
function commerce_line_item_unit_price_add($line_item, $amount) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $wrapper->unit_price->amount = $wrapper->unit_price->amount->value() + $amount;
  }
}

/**
 * Rules action: subtract an amount from the unit price.
 */
function commerce_line_item_unit_price_subtract($line_item, $amount) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $wrapper->unit_price->amount = $wrapper->unit_price->amount->value() - $amount;
  }
}

/**
 * Rules action: multiply the unit price by some amount.
 */
function commerce_line_item_unit_price_multiply($line_item, $amount) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $wrapper->unit_price->amount = $wrapper->unit_price->amount->value() * $amount;
  }
}

/**
 * Rules action: divide the unit price by some amount.
 */
function commerce_line_item_unit_price_divide($line_item, $amount) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $wrapper->unit_price->amount = $wrapper->unit_price->amount->value() / $amount;
  }
}

/**
 * Rules action: set the unit price to a specific amount.
 */
function commerce_line_item_unit_price_amount($line_item, $amount) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $wrapper->unit_price->amount = $amount;
  }
}

/**
 * Rules action: set the unit price's currency code.
 */
function commerce_line_item_unit_price_currency_code($line_item, $currency_code) {
  $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
  $wrapper->unit_price->currency_code = $currency_code;
}

/**
 * @}
 */
