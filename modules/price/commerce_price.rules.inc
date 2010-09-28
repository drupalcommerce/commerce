<?php
// $Id$

/**
 * @file
 * Rules integration for the Price module.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_event_info().
 */
function commerce_price_rules_event_info() {
  $items = array(
    'commerce_price_alter' => array(
      'label' => t('Alter a price for display / purchase'),
      'group' => t('Commerce - price'),
      'variables' => commerce_price_rules_variables(t('price')),
      'access callback' => 'commerce_order_rules_access',
    ),
  );

  return $items;
}

/**
 * Returns parameters suitable for using Rules to alter prices.
 */
function commerce_price_rules_variables() {
  $args = array(
    'price' => array(
      'type' => 'commerce_price',
      'label' => t('Price'),
    ),
  );

  return $args;
}

/**
 * Implements hook_rules_data_info().
 */
function commerce_price_rules_data_info() {
  return array(
    'commerce_price' => array(
      'label' => t('a modifiable price object'),
      'wrap' => TRUE,
      'property info' => _commerce_price_object_info(),
    ),
  );
}

/**
 * Defines object info for price objects.
 */
function _commerce_price_object_info() {
  return array(
    'price' => array(
      'type' => 'decimal',
      'label' => t('The price amount in decimal format'),
      'getter callback' => 'commerce_price_object_get_price',
    ),
    'currency-code' => array(
      'type' => 'list<text>',
      'label' => ('The currency of this price'),
      'options list' => 'commerce_currency_get_symbol',
      'getter callback' => 'commerce_price_object_get_currency_code',
    ),
  );
}

/**
 * Implements hook_rules_action_info().
 */
function commerce_price_rules_action_info() {
  $actions = array();

  // Prepare an array of arithmetical actions for altering prices.
  $action_types = array(
    'commerce_price_add_price' => array(
      'label' => t('Add an amount to the price'),
      'amount description' => t('Specify a numeric amount to add to the price.'),
    ),
    'commerce_price_subtract_price' => array(
      'label' => t('Subtract an amount from the price'),
      'amount description' => t('Specify a numeric amount to subtract from the price.'),
    ),
    'commerce_price_multiply_price' => array(
      'label' => t('Multiply the price by some amount'),
      'amount description' => t('Specify the numeric amount by which to multiply the price.'),
    ),
    'commerce_price_divide_price' => array(
      'label' => t('Divide the price by some amount'),
      'amount description' => t('Specify a numeric amount by which to divide the price.'),
    ),
    'commerce_price_set_price' => array(
      'label' => t('Set the price to a specific amount'),
      'amount description' => t('Specify the numeric amount to set the price to.'),
    ),
  );

  // Define the action using a common set of parameters.
  foreach ($action_types as $key => $value) {
    $actions[$key] = array(
      'label' => $value['label'],
      'parameter' => array(
        'price' => array(
          'type' => 'commerce_price',
          'label' => t('Price'),
          'default value' => 'price',
        ),
        'amount' => array(
          'type' => 'decimal',
          'label' => t('Amount'),
          'description' => $value['amount description'],
        ),
      ),
      'group' => t('Commerce - price'),
    );
  }

  $actions['commerce_price_set_currency_code'] = array(
    'label' => t('Set the currency code'),
    'parameter' => array(
      'price' => array(
        'type' => 'commerce_price',
        'label' => t('Price'),
        'default value' => 'price',
      ),
      'currency_code' => array(
        'type' => 'text',
        'label' => t('Currency'),
      ),
    ),
    'group' => t('Commerce - price'),
  );

  return $actions;
}

/**
 * Rules action: add an amount to the price.
 */
function commerce_price_add_price($price, $amount) {
  if (is_numeric($amount)) {
    $price->modifyPrice('rules', t('Add an amount to the price'), $amount);
  }
}

/**
 * Rules action: subtract an amount from the price.
 */
function commerce_price_subtract_price($price, $amount) {
  if (is_numeric($amount)) {
    $price->modifyPrice('rules', t('Subtract an amount from the price'), -$amount);
  }
}

/**
 * Rules action: multiply the price by some amount.
 */
function commerce_price_multiply_price($price, $amount) {
  if (is_numeric($amount)) {
    $product = $price->getPrice() * $amount;

    $price->modifyPrice('rules', t('Multiply the price by some amount'), $product - $price->getPrice());
  }
}

/**
 * Rules action: divide the price by some amount.
 */
function commerce_price_divide_price($price, $amount) {
  if (is_numeric($amount)) {
    $quotient = $price->getPrice() / $amount;

    $price->modifyPrice('rules', t('Divide the price by some amount'), $quotient - $price->getPrice());
  }
}

/**
 * Rules action: set the price to a specific amount.
 */
function commerce_price_set_price($price, $amount) {
  if (is_numeric($amount)) {
    $price->modifyPrice('rules', t('Set the price to a specific amount'), $amount - $price->getPrice());
  }
}

/**
 * Rules action: set the currency code.
 */
function commerce_price_set_currency_code($price, $currency_code) {
  $price->modifyPrice('rules', t('Set the currency code'), 0, $currency_code);
}

/**
 * @}
 */
