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
        'component_name' => array(
          'type' => 'text',
          'label' => t('Price component type'),
          'description' => t('Price components track changes to prices made during the price calculation process, and they are carried over from the unit price to the total price of a line item. When an order total is calculated, it combines all the components of every line item on the order. When the unit price is altered by this action, the selected type of price component will be added to its data array and reflected in the order total display when it is formatted with components showing. Defaults to base price, which displays as the order Subtotal.'),
          'options list' => 'commerce_line_item_price_component_options_list',
          'default value' => 'base_price',
        ),
        'round_mode' => array(
          'type' => 'integer',
          'label' => t('Price rounding mode'),
          'description' => t('Round the resulting price amount after performing this operation.'),
          'options list' => 'commerce_round_mode_options_list',
          'default value' => COMMERCE_ROUND_HALF_UP,
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
 * Options list callback: price component selection list.
 */
function commerce_line_item_price_component_options_list() {
  return commerce_price_component_titles();
}

/**
 * Rules action: add an amount to the unit price.
 */
function commerce_line_item_unit_price_add($line_item, $amount, $component_name, $round_mode) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $unit_price = commerce_price_wrapper_value($wrapper, 'commerce_unit_price', TRUE);

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $current_amount = $unit_price['amount'];
    $updated_amount = commerce_round($round_mode, $current_amount + $amount);

    $difference = array(
      'amount' => $updated_amount - $current_amount,
      'currency_code' => $unit_price['currency_code'],
      'data' => array(),
    );

    // Set the amount of the unit price and add the difference as a component.
    $wrapper->commerce_unit_price->amount = $updated_amount;

    $wrapper->commerce_unit_price->data = commerce_price_component_add(
      $wrapper->commerce_unit_price->value(),
      $component_name,
      $difference,
      TRUE
    );
  }
}

/**
 * Rules action: subtract an amount from the unit price.
 */
function commerce_line_item_unit_price_subtract($line_item, $amount, $component_name, $round_mode) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $unit_price = commerce_price_wrapper_value($wrapper, 'commerce_unit_price', TRUE);

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $current_amount = $unit_price['amount'];
    $updated_amount = commerce_round($round_mode, $current_amount - $amount);

    $difference = array(
      'amount' => $updated_amount - $current_amount,
      'currency_code' => $unit_price['currency_code'],
      'data' => array(),
    );

    // Set the amount of the unit price and add the difference as a component.
    $wrapper->commerce_unit_price->amount = $updated_amount;

    $wrapper->commerce_unit_price->data = commerce_price_component_add(
      $wrapper->commerce_unit_price->value(),
      $component_name,
      $difference,
      TRUE
    );
  }
}

/**
 * Rules action: multiply the unit price by some amount.
 */
function commerce_line_item_unit_price_multiply($line_item, $amount, $component_name, $round_mode) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $unit_price = commerce_price_wrapper_value($wrapper, 'commerce_unit_price', TRUE);

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $current_amount = $unit_price['amount'];
    $updated_amount = commerce_round($round_mode, $current_amount * $amount);

    $difference = array(
      'amount' => $updated_amount - $current_amount,
      'currency_code' => $unit_price['currency_code'],
      'data' => array(),
    );

    // Set the amount of the unit price and add the difference as a component.
    $wrapper->commerce_unit_price->amount = $updated_amount;

    $wrapper->commerce_unit_price->data = commerce_price_component_add(
      $wrapper->commerce_unit_price->value(),
      $component_name,
      $difference,
      TRUE
    );
  }
}

/**
 * Rules action: divide the unit price by some amount.
 */
function commerce_line_item_unit_price_divide($line_item, $amount, $component_name, $round_mode) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $unit_price = commerce_price_wrapper_value($wrapper, 'commerce_unit_price', TRUE);

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $current_amount = $unit_price['amount'];
    $updated_amount = commerce_round($round_mode, $current_amount / $amount);

    $difference = array(
      'amount' => $updated_amount - $current_amount,
      'currency_code' => $unit_price['currency_code'],
      'data' => array(),
    );

    // Set the amount of the unit price and add the difference as a component.
    $wrapper->commerce_unit_price->amount = $updated_amount;

    $wrapper->commerce_unit_price->data = commerce_price_component_add(
      $wrapper->commerce_unit_price->value(),
      $component_name,
      $difference,
      TRUE
    );
  }
}

/**
 * Rules action: set the unit price to a specific amount.
 */
function commerce_line_item_unit_price_amount($line_item, $amount, $component_name, $round_mode) {
  if (is_numeric($amount)) {
    $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $unit_price = commerce_price_wrapper_value($wrapper, 'commerce_unit_price', TRUE);

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $current_amount = $unit_price['amount'];
    $updated_amount = commerce_round($round_mode, $amount);

    $difference = array(
      'amount' => $updated_amount - $current_amount,
      'currency_code' => $unit_price['currency_code'],
      'data' => array(),
    );

    // Set the amount of the unit price and add the difference as a component.
    $wrapper->commerce_unit_price->amount = $updated_amount;

    $wrapper->commerce_unit_price->data = commerce_price_component_add(
      $wrapper->commerce_unit_price->value(),
      $component_name,
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
  $unit_price = commerce_price_wrapper_value($wrapper, 'commerce_unit_price');

  // Only set the currency on prices with non-NULL amounts.
  if (empty($unit_price)) {
    return;
  }

  $wrapper->commerce_unit_price->currency_code = $currency_code;

  // Update the currency code of the price's components.
  if (!empty($unit_price['data']['components'])) {
    foreach ($unit_price['data']['components'] as $key => &$component) {
      $component['price']['currency_code'] = $currency_code;
    }

    $wrapper->commerce_unit_price->data = $unit_price['data'];
  }
}

/**
 * Rules action: convert the unit price to a different currency.
 */
function commerce_line_item_unit_price_currency_convert($line_item, $currency_code) {
  $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
  $unit_price = commerce_price_wrapper_value($wrapper, 'commerce_unit_price');

  // Only convert non-NULL amounts since NULL amounts do not have a currency.
  if (empty($unit_price)) {
    return;
  }

  $wrapper->commerce_unit_price->amount = commerce_currency_convert($wrapper->commerce_unit_price->amount->value(), $wrapper->commerce_unit_price->currency_code->value(), $currency_code);
  $wrapper->commerce_unit_price->currency_code = $currency_code;

  // Convert the currency code of the price's components.
  if (!empty($unit_price['data']['components'])) {
    foreach ($unit_price['data']['components'] as $key => &$component) {
      $component['price']['amount'] = commerce_currency_convert($component['price']['amount'], $component['price']['currency_code'], $currency_code);
      $component['price']['currency_code'] = $currency_code;
    }

    $wrapper->commerce_unit_price->data = $unit_price['data'];
  }
}

/**
 * @}
 */
