<?php

/**
 * @file
 * Rules integration for line items.
 *
 * @addtogroup rules
 * @{
 */

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
        'commerce_line_item' => array(
          'type' => 'commerce_line_item',
          'label' => t('Line item'),
        ),
        'amount' => array(
          'type' => 'decimal',
          'label' => t('Amount'),
          'description' => $value['amount description'],
        ),
      ),
      'group' => t('Commerce Line Item'),
    );
  }

  $actions['commerce_line_item_unit_price_currency_code'] = array(
    'label' => t("Set the unit price's currency code"),
    'parameter' => array(
      'commerce_line_item' => array(
        'type' => 'commerce_line_item',
        'label' => t('Line item'),
      ),
      'currency_code' => array(
        'type' => 'text',
        'label' => t('Currency'),
        'options list' => 'commerce_currency_get_code',
      ),
    ),
    'group' => t('Commerce Line Item'),
  );

  $actions['commerce_line_item_unit_price_currency_convert'] = array(
    'label' => t("Convert the unit price to a different currency"),
    'parameter' => array(
      'commerce_line_item' => array(
        'type' => 'commerce_line_item',
        'label' => t('Line item'),
      ),
      'currency_code' => array(
        'type' => 'text',
        'label' => t('Currency'),
        'options list' => 'commerce_currency_get_code',
      ),
    ),
    'group' => t('Commerce Line Item'),
  );

  return $actions;
}

/**
 * Rules action: add an amount to the unit price.
 */
function commerce_line_item_unit_price_add($line_item, $amount) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $current_amount = $wrapper->commerce_unit_price->amount->value();
    $updated_amount = $current_amount + $amount;

    $difference = array(
      'amount' => $updated_amount - $current_amount,
      'currency_code' => $wrapper->commerce_unit_price->currency_code->value(),
      'data' => array(),
    );

    // Set the amount of the unit price and add the difference as a component.
    $wrapper->commerce_unit_price->amount = $updated_amount;

    $wrapper->commerce_unit_price->data = commerce_price_component_add(
      $wrapper->commerce_unit_price->value(),
      'base_price',
      $difference,
      TRUE
    );
  }
}

/**
 * Rules action: subtract an amount from the unit price.
 */
function commerce_line_item_unit_price_subtract($line_item, $amount) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $current_amount = $wrapper->commerce_unit_price->amount->value();
    $updated_amount = $current_amount - $amount;

    $difference = array(
      'amount' => $updated_amount - $current_amount,
      'currency_code' => $wrapper->commerce_unit_price->currency_code->value(),
      'data' => array(),
    );

    // Set the amount of the unit price and add the difference as a component.
    $wrapper->commerce_unit_price->amount = $updated_amount;

    $wrapper->commerce_unit_price->data = commerce_price_component_add(
      $wrapper->commerce_unit_price->value(),
      'base_price',
      $difference,
      TRUE
    );
  }
}

/**
 * Rules action: multiply the unit price by some amount.
 */
function commerce_line_item_unit_price_multiply($line_item, $amount) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $current_amount = $wrapper->commerce_unit_price->amount->value();
    $updated_amount = $current_amount * $amount;

    $difference = array(
      'amount' => $updated_amount - $current_amount,
      'currency_code' => $wrapper->commerce_unit_price->currency_code->value(),
      'data' => array(),
    );

    // Set the amount of the unit price and add the difference as a component.
    $wrapper->commerce_unit_price->amount = $updated_amount;

    $wrapper->commerce_unit_price->data = commerce_price_component_add(
      $wrapper->commerce_unit_price->value(),
      'base_price',
      $difference,
      TRUE
    );
  }
}

/**
 * Rules action: divide the unit price by some amount.
 */
function commerce_line_item_unit_price_divide($line_item, $amount) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $current_amount = $wrapper->commerce_unit_price->amount->value();
    $updated_amount = $current_amount / $amount;

    $difference = array(
      'amount' => $updated_amount - $current_amount,
      'currency_code' => $wrapper->commerce_unit_price->currency_code->value(),
      'data' => array(),
    );

    // Set the amount of the unit price and add the difference as a component.
    $wrapper->commerce_unit_price->amount = $updated_amount;

    $wrapper->commerce_unit_price->data = commerce_price_component_add(
      $wrapper->commerce_unit_price->value(),
      'base_price',
      $difference,
      TRUE
    );
  }
}

/**
 * Rules action: set the unit price to a specific amount.
 */
function commerce_line_item_unit_price_amount($line_item, $amount) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $current_amount = $wrapper->commerce_unit_price->amount->value();
    $updated_amount = $amount;

    $difference = array(
      'amount' => $updated_amount - $current_amount,
      'currency_code' => $wrapper->commerce_unit_price->currency_code->value(),
      'data' => array(),
    );

    // Set the amount of the unit price and add the difference as a component.
    $wrapper->commerce_unit_price->amount = $updated_amount;

    $wrapper->commerce_unit_price->data = commerce_price_component_add(
      $wrapper->commerce_unit_price->value(),
      'base_price',
      $difference,
      TRUE
    );
  }
}

/**
 * Rules action: set the unit price's currency code.
 */
function commerce_line_item_unit_price_currency_code($line_item, $currency_code) {
  $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
  $wrapper->commerce_unit_price->currency_code = $currency_code;

  // Update the currency code of the price's components.
  $data = $wrapper->commerce_unit_price->data->value();

  if (!empty($data['components'])) {
    foreach ($data['components'] as $key => &$component) {
      $component['price']['currency_code'] = $currency_code;
    }

    $wrapper->commerce_unit_price->data = $data;
  }
}

/**
 * Rules action: convert the unit price to a different currency.
 */
function commerce_line_item_unit_price_currency_convert($line_item, $currency_code) {
  $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
  $wrapper->commerce_unit_price->amount = commerce_currency_convert($wrapper->commerce_unit_price->amount->value(), $wrapper->commerce_unit_price->currency_code->value(), $currency_code);
  $wrapper->commerce_unit_price->currency_code = $currency_code;

  // Convert the curreny code of the price's components.
  $data = $wrapper->commerce_unit_price->data->value();

  if (!empty($data['components'])) {
    foreach ($data['components'] as $key => &$component) {
      $component['price']['amount'] = commerce_currency_convert($component['price']['amount'], $component['currency_code'], $currency_code);
      $component['price']['currency_code'] = $currency_code;
    }

    $wrapper->commerce_unit_price->data = $data;
  }
}

/**
 * @}
 */
